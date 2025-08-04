<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateQr;
use App\Jobs\SendQrToWhatsapp;
use App\Models\Registration;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class RegistrationController extends Controller
{
    private $registrationSessionKey = 'registration_form_key';
    private $sessionTimeout = 1800; // 30 minutes

    public function showRegistrationPage()
    {
        $formData = session($this->registrationSessionKey);

        return Inertia::render('Registration', [
            'form_data' => $formData,
        ]);
    }

    public function submitRegistration(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'phone:ID'],
        ], [
            'phone' => 'Pastikan nomor terdiri hanya dari angka dan berawalan "+62" atau "0"'
        ]);

        // Store with timestamp for cleanup
        session([
            $this->registrationSessionKey => array_merge($validated, [
                'created_at' => now(),
                'expires_at' => now()->addSeconds($this->sessionTimeout)
            ])
        ]);

        return to_route('user.choose_seat');
    }

    public function showChooseSeat()
    {
        $formData = $this->getValidFormData();
        if (!$formData) {
            return redirect()->route('user.registration')
                ->with('info', ['error' => 'Harap isi form registrasi terlebih dahulu sebelum memilih kursi.']);
        }

        $seatingType = 'theater';
        $seats = Seat::where('type', $seatingType)
            ->orderBy('row')
            ->orderBy('column')
            ->get();
        $maxColumnCount = Seat::where('type', $seatingType)->max('column');

        return Inertia::render('ChooseSeat', [
            'seatingType' => $seatingType,
            'seats' => $seats,
            'formData' => $formData,
            'maxColumnCount' => $maxColumnCount,
        ]);
    }

    public function submitChosenSeat(Request $request)
    {
        $formData = $this->getValidFormData();
        if (!$formData) {
            return redirect()->route('user.registration')->with('info', [
                'redirect_url' => route('user.registration'),
                'error' => 'Session expired. Please start registration again.'
            ]);
        }

        $validated = $request->validate([
            'seat_id' => ['required', 'integer', 'exists:seats,id']
        ]);

        try {
            $registration = DB::transaction(function () use ($validated, $formData) {
                // Lock the specific seat row to prevent race conditions
                $seat = Seat::where('id', $validated['seat_id'])
                    ->where('type', 'theater')
                    ->lockForUpdate()
                    ->first();

                // Check if seat exists
                if (!$seat) {
                    throw new \Exception('Seat not found');
                }

                // Check if seat is still available
                if ($seat->registration_id !== null) {
                    throw new \Exception('seat_taken');
                }

                // Create registration
                $registration = Registration::create([
                    'name' => $formData['name'],
                    'email' => $formData['email'],
                    'phone' => $formData['phone'],
                    'seat_id' => $seat->id,
                    'registered_at' => now(),
                ]);

                // Update seat
                $seat->update(['registration_id' => $registration->id]);

                Bus::chain([
                    new GenerateQr($registration),
                    new SendQrToWhatsapp($registration),
                ])->dispatch();

                return $registration;
            });

            // Clear session data after successful registration
            session()->forget([$this->registrationSessionKey, 'registration_token']);

            return redirect()->to(
                URL::temporarySignedRoute(
                    'user.registration_success',
                    now()->addMinutes(60),
                    ['registration' => $registration->id]
                ),
            );
        } catch (\Exception $e) {
            if ($e->getMessage() === 'seat_taken') {
                return redirect()->route('user.choose_seat')->with('info', [
                    'error' => 'Sorry, this seat was just taken by another user. Please choose a different seat.',
                    'seat_taken' => true
                ]);
            }

            // Log the error for debugging
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'user_data' => $formData,
                'seat_id' => $validated['seat_id']
            ]);

            return redirect()->route('user.choose_seat')->with('info', [
                'error' => 'An error occurred during registration. Please try again.'
            ]);
        }
    }

    public function showRegistrationSuccess(Registration $registration)
    {
        return Inertia::render('RegistrationSuccess', [
            'registration' => $registration->load('seat'),
            'success_image' => asset('images/undraw_success_288.png'),
        ]);
    }

    /**
     * Get form data and validate it hasn't expired
     */
    private function getValidFormData()
    {
        $formData = session($this->registrationSessionKey);

        if (!$formData) {
            return null;
        }

        // Check if session has expired
        if (isset($formData['expires_at']) && now()->gt($formData['expires_at'])) {
            session()->forget($this->registrationSessionKey);
            return null;
        }

        return $formData;
    }
}
