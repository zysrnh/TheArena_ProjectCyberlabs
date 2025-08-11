<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateQr;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Seat;
use App\Settings\RegistrationSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class RegistrationSACVipController extends Controller
{
    public function showWelcome(RegistrationSettings $registrationSettings)
    {
        $count = Registration::where('extras->type', 'vip')->count();
        if ($registrationSettings->vip_limit >= 0 && $count >= $registrationSettings->vip_limit) {
            return redirect()->route('full_registration');
        }

        return Inertia::render('RegistrationWelcome', [
            'redirectTo' => route('sac_vip.registration'),
            'images' => [
                'ekraf_white' => asset('images/ekraf-text-white.png'),
                'kkri_white' => asset('images/kkri-text-white.png'),
                'sby_art_white' => asset('images/sbyart-logo.png'),
            ],
        ]);
    }

    public function showForm()
    {
        $formData = session('registration_data');

        return Inertia::render('RegistrationSACVip', [
            'formData' => $formData,
            'images' => [
                'ekraf_white' => asset('images/ekraf-text-white.png'),
                'kkri_white' => asset('images/kkri-text-white.png'),
                'sby_art_white' => asset('images/sbyart-logo.png'),
            ],
        ]);
    }

    public function submitForm(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'organization' => ['required', 'max:255'],
        ]);

        $registrationData = array_merge($validated, [
            'is_approved' => true,
            'approved_at' => now(),
            'event_id' => Event::where('name', 'SBY Art Community')->first()->id,
            'extras' => [
                'type' => 'vip',
                'is_vip' => true,
                'is_pers' => false,
                'organization' => $validated['organization'],
            ],
        ]);

        session(['registration_data' => $registrationData]);

        // GenerateQr::dispatchSync($registration);
        // Bus::chain([
        //     new SendQrToWhatsapp($registration),
        // ])->dispatch()

        // $signedUrl = URL::temporarySignedRoute(
        //     'registration_success',
        //     now()->addHour(),
        //     ['registration' => $registration->id]
        // );

        // return redirect($signedUrl)->with('info', [
        //     'success' =>  'Berhasil mendaftar pada SAC Opening Ceremony',
        // ]);

        return redirect()->route('sac_vip.seat');
    }

    public function showSeating()
    {
        $formData = session('registration_data');

        if (!$formData) {
            // If user skipped step 1, send back to registration
            return redirect()->route('user.registration')->with('info', 'Harap isi form registrasi terlebih dahulu sebelum memilih kursi.');
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
            'maxColumnCount' => $maxColumnCount,
            'formData' => $formData,
            'images' => [
                'ekraf_white' => asset('images/ekraf-text-white.png'),
                'kkri_white' => asset('images/kkri-text-white.png'),
                'sby_art_white' => asset('images/sbyart-logo.png'),
            ],
        ]);
    }

    public function chooseSeat(Request $request)
    {
        $formData = session('registration_data');

        if (!$formData) {
            // If user skipped step 1, send back to registration
            return redirect()->route('sac_vip.registration')->with('info', 'Session expired. Please start registration again.');
        }

        $validated = $request->validate([
            'seat_id' => 'required|exists:seats,id',
        ]);

        $registrationData = array_merge($formData, ['seat_id' => $validated['seat_id']]);

        try {
            $registration = DB::transaction(function () use ($validated, $registrationData) {
                // Lock the specific seat row to prevent race conditions
                $seat = Seat::where('id', $validated['seat_id'])
                    ->where('type', $formData['seat_type'] ?? 'theater')
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

                $registration = Registration::create($registrationData);
                $seat->update(['registration_id' => $registration->id]);

                GenerateQr::dispatchSync($registration);
                // Bus::chain([
                //     new SendQrToWhatsapp($registration),
                // ])->dispatch();

                return $registration;
            });

            // Clear session data after successful registration
            session()->forget('registration_data');

            return redirect()->to(
                URL::temporarySignedRoute(
                    'registration_success',
                    now()->addMinutes(60),
                    ['registration' => $registration->id]
                ),
            );
        } catch (\Exception $e) {
            if ($e->getMessage() === 'seat_taken') {
                return redirect()->route('sac_vip.choose_seat')->with('info', [
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

            return redirect()->route('sac_vip.choose_seat')->with('info', [
                'error' => 'An error occurred during registration. Please try again.'
            ]);
        }
    }
}
