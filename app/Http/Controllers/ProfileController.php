<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Booking;
use Carbon\Carbon;
use App\Models\Review;

class ProfileController extends Controller
{
   public function index()
{
    $client = Auth::guard('client')->user();
    
    // Ambil semua booking (upcoming & history) dalam satu query
    $allBookings = Booking::where('client_id', $client->id)
        ->with(['bookedTimeSlots'])
        ->orderBy('booking_date', 'desc')
        ->orderBy('created_at', 'desc')
        ->get();

    // Pisahkan upcoming dan history
    $upcomingBookingsRaw = $allBookings->filter(function($booking) {
        return in_array($booking->status, ['pending', 'confirmed']) 
            && $booking->booking_date >= Carbon::today();
    })->sortBy('booking_date')->sortBy('created_at');

    $historyBookingsRaw = $allBookings->filter(function($booking) {
        return $booking->booking_date < Carbon::today() 
            || in_array($booking->status, ['completed', 'cancelled']);
    });

    // Format upcoming bookings
    $upcomingBookings = $upcomingBookingsRaw->map(function($booking) {
        return $this->formatBooking($booking, true);
    })->values();

    // Format history bookings
    $historyBookingsFormatted = $historyBookingsRaw->map(function($booking) {
        return $this->formatBooking($booking, false);
    })->values();

    // Manual pagination for history
    $perPage = 10;
    $currentPage = request()->get('page', 1);
    $historyBookings = new \Illuminate\Pagination\LengthAwarePaginator(
        $historyBookingsFormatted->forPage($currentPage, $perPage),
        $historyBookingsFormatted->count(),
        $perPage,
        $currentPage,
        ['path' => request()->url(), 'query' => request()->query()]
    );

    // ✅ CEK APAKAH USER PERLU DIMINTA REVIEW
    $hasCompletedBooking = Booking::where('client_id', $client->id)
        ->whereIn('status', ['confirmed', 'completed'])
        ->where('booking_date', '<', Carbon::today())
        ->where('payment_status', 'paid')
        ->exists();

    $hasGivenReview = Review::where('client_id', $client->id)->exists();

    $shouldShowReviewReminder = $hasCompletedBooking && !$hasGivenReview;

    $completedBookingCount = Booking::where('client_id', $client->id)
        ->whereIn('status', ['confirmed', 'completed'])
        ->where('booking_date', '<', Carbon::today())
        ->where('payment_status', 'paid')
        ->count();
    
    // ✅ GET REVIEW HISTORY
    $reviewHistory = Review::where('client_id', $client->id)
        ->with(['booking'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function($review) {
            return [
                'id' => $review->id,
                'rating_facilities' => $review->rating_facilities,
                'rating_hospitality' => $review->rating_hospitality,
                'rating_cleanliness' => $review->rating_cleanliness,
                'average_rating' => $review->average_rating,
                'comment' => $review->comment,
                'is_approved' => $review->is_approved,
                'created_at' => $review->created_at->format('d F Y H:i'),
                'booking_info' => $review->booking ? [
                    'date' => $review->booking->booking_date->format('d F Y'),
                    'venue' => $this->formatVenueType($review->booking->venue_type),
                ] : null,
            ];
        });
    
    Log::info('📊 Profile Page Loaded', [
        'client_id' => $client->id,
        'upcoming_count' => $upcomingBookings->count(),
        'history_count' => $historyBookingsFormatted->count(),
        'review_count' => $reviewHistory->count(),
        'has_completed_booking' => $hasCompletedBooking,
        'has_given_review' => $hasGivenReview,
        'should_show_review_reminder' => $shouldShowReviewReminder,
        'completed_booking_count' => $completedBookingCount,
    ]);
    
    return Inertia::render('Profile/Profile', [
        'auth' => [
            'client' => $client
        ],
        'upcomingBookings' => $upcomingBookings,
        'historyBookings' => $historyBookings,
        'reviewHistory' => $reviewHistory,
        'flash' => session('flash'),
        'shouldShowReviewReminder' => $shouldShowReviewReminder,
        'completedBookingCount' => $completedBookingCount,
    ]);
}

    /**
     * Format booking data untuk frontend
     */
    private function formatBooking($booking, $isUpcoming = false)
    {
        // Kumpulkan semua time slots
        $allSlots = [];
        if (is_array($booking->time_slots)) {
            foreach ($booking->time_slots as $slot) {
                if (isset($slot['time'])) {
                    $allSlots[] = $slot['time'];
                }
            }
        }
        
        // Urutkan slots
        if (!empty($allSlots)) {
            usort($allSlots, function($a, $b) {
                return $this->parseTime($a) <=> $this->parseTime($b);
            });
        }
        
        $timeRange = $this->mergeTimeSlots($allSlots);
        
        // ✅ Determine can_pay and can_cancel
        $isPaid = $booking->isPaid();
        $isPending = $booking->isPending();
        $isExpired = $booking->payment_status === 'expired';
        $isCancelled = $booking->status === 'cancelled';
        
        $canPay = $booking->status === 'pending' 
               && !$isPaid 
               && !$isExpired
               && !$isCancelled
               && $isUpcoming;
               
        $canCancel = in_array($booking->status, ['pending', 'confirmed']) 
                  && !$isPaid 
                  && !$isCancelled
                  && $isUpcoming;
        
        $formatted = [
            'id' => $booking->id,
            'booking_date' => $booking->booking_date->format('d F Y'),
            'booking_date_raw' => $booking->booking_date->format('Y-m-d'),
            'time_slot' => $timeRange,
            'time_slots' => $booking->time_slots,
            'venue_type' => $this->formatVenueType($booking->venue_type),
            'venue_type_raw' => $booking->venue_type,
            'total_price' => number_format($booking->total_price, 0, ',', '.'),
            'total_price_raw' => $booking->total_price,
            'status' => $booking->status,
            'status_label' => $this->getStatusLabel($booking->status),
            'status_color' => $this->getStatusColor($booking->status),
            
            // ✅ PAYMENT INFO
            'payment_status' => $booking->payment_status ?? 'pending',
            'payment_status_label' => $this->getPaymentStatusLabel($booking->payment_status),
            'payment_method' => $booking->payment_method,
            'paid_at' => $booking->paid_at ? $booking->paid_at->format('d F Y H:i') : null,
            'bill_no' => $booking->bill_no,
            'trx_id' => $booking->trx_id,
            'is_paid' => $isPaid,
            'is_pending' => $isPending,
            
            // ✅ ACTION FLAGS
            'can_pay' => $canPay,
            'can_cancel' => $canCancel,
            
            'created_at' => $booking->created_at->format('d F Y H:i'),
        ];

        if ($isUpcoming && $canPay) {
            Log::debug('🎫 Booking can be paid', [
                'booking_id' => $booking->id,
                'payment_status' => $booking->payment_status,
                'status' => $booking->status,
                'is_paid' => $isPaid,
            ]);
        }

        if ($isUpcoming) {
            $formatted['booking_ids'] = [$booking->id];
        }

        return $formatted;
    }

    /**
     * Get payment status label
     */
    private function getPaymentStatusLabel($status)
    {
        $labels = [
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Lunas',
            'failed' => 'Gagal',
            'expired' => 'Kadaluarsa',
            'cancelled' => 'Dibatalkan',
        ];

        return $labels[$status] ?? 'Unknown';
    }

    /**
     * Parse waktu dari format "HH.MM - HH.MM" ke minutes sejak midnight
     */
    private function parseTime($timeSlot)
    {
        $parts = explode(' - ', $timeSlot);
        $startTime = trim($parts[0] ?? '');
        
        $timeParts = explode('.', $startTime);
        $hours = intval($timeParts[0] ?? 0);
        $minutes = intval($timeParts[1] ?? 0);
        
        return ($hours * 60) + $minutes;
    }

    /**
     * Merge time slots yang berurutan
     */
    private function mergeTimeSlots($slots)
    {
        if (empty($slots)) {
            return '-';
        }

        if (count($slots) === 1) {
            return $slots[0];
        }

        $parsedSlots = array_map(function($slot) {
            $parts = explode(' - ', $slot);
            return [
                'start' => trim($parts[0] ?? ''),
                'end' => trim($parts[1] ?? ''),
                'start_minutes' => $this->timeToMinutes(trim($parts[0] ?? '')),
                'end_minutes' => $this->timeToMinutes(trim($parts[1] ?? '')),
            ];
        }, $slots);

        $merged = [];
        $current = $parsedSlots[0];

        for ($i = 1; $i < count($parsedSlots); $i++) {
            $next = $parsedSlots[$i];
            
            if ($current['end_minutes'] === $next['start_minutes']) {
                $current['end'] = $next['end'];
                $current['end_minutes'] = $next['end_minutes'];
            } else {
                $merged[] = $current['start'] . ' - ' . $current['end'];
                $current = $next;
            }
        }
        
        $merged[] = $current['start'] . ' - ' . $current['end'];

        if (count($merged) === 1) {
            return $merged[0];
        }

        return implode(', ', $merged);
    }

    /**
     * Convert time string "HH.MM" ke minutes
     */
    private function timeToMinutes($time)
    {
        $parts = explode('.', $time);
        $hours = intval($parts[0] ?? 0);
        $minutes = intval($parts[1] ?? 0);
        
        if ($hours === 0 && $minutes === 0) {
            return 24 * 60;
        }
        
        return ($hours * 60) + $minutes;
    }

    /**
     * Get status label untuk display
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'pending' => 'PENDING',
            'confirmed' => 'BOOKED',
            'completed' => 'COMPLETED',
            'cancelled' => 'CANCELED',
        ];

        return $labels[$status] ?? strtoupper($status);
    }

    /**
     * Get status color
     */
    private function getStatusColor($status)
    {
        $colors = [
            'pending' => 'yellow',
            'confirmed' => 'green',
            'completed' => 'blue',
            'cancelled' => 'red',
        ];

        return $colors[$status] ?? 'gray';
    }

    /**
     * Format venue type untuk display
     */
    private function formatVenueType($venueType)
    {
        $venues = [
            'cibadak_a' => 'Cibadak A',
            'cibadak_b' => 'Cibadak B',
            'pvj' => 'PVJ',
            'urban' => 'Urban',
        ];

        return $venues[$venueType] ?? ucfirst($venueType);
    }

    /**
     * Update profile client
     */
    public function update(Request $request)
    {
        $client = Auth::guard('client')->user();
        
        if (!$client) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:clients,email,' . $client->id,
            'phone' => 'required|string|max:20',
            'province' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'gender' => 'nullable|string|in:Laki-laki,Perempuan',
            'birth_date' => 'nullable|date',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_image')) {
            if ($client->profile_image) {
                Storage::disk('public')->delete($client->profile_image);
            }

            $path = $request->file('profile_image')->store('profile-images', 'public');
            $validated['profile_image'] = $path;
        }

        $client->update($validated);

        return back()->with('success', 'Profil berhasil diperbarui!');
    }

    /**
     * Cancel booking
     */
    public function cancelBooking(Request $request, $id)
    {
        $client = Auth::guard('client')->user();
        
        if (!$client) {
            return back()->with('error', 'Silakan login terlebih dahulu');
        }
        
        Log::info('🚫 Cancel Booking Request', [
            'booking_id' => $id,
            'client_id' => $client->id,
        ]);
        
        $bookingIds = $request->input('booking_ids', [$id]);
        
        $bookings = Booking::whereIn('id', $bookingIds)
            ->where('client_id', $client->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        if ($bookings->isEmpty()) {
            Log::warning('⚠️ Booking not found or cannot be cancelled', [
                'booking_ids' => $bookingIds,
            ]);
            return back()->with('error', 'Booking tidak ditemukan atau tidak dapat dibatalkan');
        }

        $hasPaid = $bookings->filter(function($booking) {
            return $booking->isPaid();
        })->isNotEmpty();

        if ($hasPaid) {
            Log::warning('⚠️ Cannot cancel paid booking', [
                'booking_ids' => $bookingIds,
            ]);
            return back()->with('error', 'Booking yang sudah dibayar tidak dapat dibatalkan');
        }

        foreach ($bookings as $booking) {
            $booking->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);
            
            $booking->bookedTimeSlots()->delete();
            
            Log::info('✅ Booking cancelled', [
                'booking_id' => $booking->id,
                'bill_no' => $booking->bill_no,
            ]);
        }

        return back()->with('success', 'Booking berhasil dibatalkan');
    }
}