<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use App\Models\Booking;
use App\Models\BookedTimeSlot;

class CalendarBooking extends Page
{
    protected static string $resource = BookingResource::class;

    protected static string $view = 'filament.admin.resources.booking.calendar';

    protected static ?string $title = 'Kalender Booking';

    #[Url]
    public $selectedVenue = 'all';

    #[Url]
    public $startDate = null;

    #[Url]
    public $endDate = null;

    public $dateRangeText = null;

    // ✅ NEW: Modal state untuk pilihan booking type
    public $showBookingTypeModal = false;
    public $selectedDate = null;
    public $selectedTimeSlot = null;
    public $selectedVenueForBooking = null;

    public function mount(): void
    {
        // ✅ Auto-cancel expired bookings saat halaman pertama kali dimuat
        $this->cancelExpiredPendingBookings();

        // Set default date range (7 days from today)
        if (!$this->startDate) {
            $this->startDate = Carbon::today()->format('Y-m-d');
        }
        if (!$this->endDate) {
            $this->endDate = Carbon::today()->addDays(6)->format('Y-m-d');
        }
        $this->updateDateRangeText();
    }

    /**
     * ✅ AUTO-CANCEL EXPIRED PENDING BOOKINGS
     */
    private function cancelExpiredPendingBookings()
    {
        try {
            $expirationTime = Carbon::now()->subMinutes(10);

            $expiredBookings = Booking::where('payment_status', 'pending')
                ->where('status', 'pending')
                ->where('created_at', '<', $expirationTime)
                ->get();

            foreach ($expiredBookings as $booking) {
                DB::beginTransaction();
                try {
                    $booking->update([
                        'status' => 'cancelled',
                        'payment_status' => 'cancelled'
                    ]);

                    BookedTimeSlot::where('booking_id', $booking->id)->delete();

                    DB::commit();
                    
                    Log::info("Expired booking #{$booking->id} auto-cancelled from calendar", [
                        'client_id' => $booking->client_id,
                        'booking_date' => $booking->booking_date,
                        'venue_type' => $booking->venue_type,
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Failed to cancel expired booking from calendar: ' . $e->getMessage());
                }
            }

            if ($expiredBookings->count() > 0) {
                Log::info("Auto-cancelled {$expiredBookings->count()} expired bookings from calendar");
            }

            return $expiredBookings->count();
        } catch (\Exception $e) {
            Log::error('Error in cancelExpiredPendingBookings (calendar): ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * ✅ NEW: Open modal untuk pilih booking type
     */
    public function openBookingTypeModal($date, $timeSlot, $venue = null)
    {
        $this->selectedDate = $date;
        $this->selectedTimeSlot = $timeSlot;
        $this->selectedVenueForBooking = $venue !== 'all' ? $venue : null;
        $this->showBookingTypeModal = true;
    }

    /**
     * ✅ NEW: Create booking biasa
     */
    public function createRegularBooking()
    {
        $this->cancelExpiredPendingBookings();

        session([
            'calendar_prefill' => [
                'booking_date' => $this->selectedDate,
                'time_slot' => $this->selectedTimeSlot,
                'venue_type' => $this->selectedVenueForBooking,
            ]
        ]);

        $this->showBookingTypeModal = false;
        return redirect()->route('filament.admin.resources.bookings.create');
    }

    /**
     * ✅ NEW: Create booking bulanan
     */
    public function createRecurringBooking()
    {
        $this->cancelExpiredPendingBookings();

        session([
            'recurring_prefill' => [
                'booking_date' => $this->selectedDate,
                'time_slot' => $this->selectedTimeSlot,
                'venue_type' => $this->selectedVenueForBooking,
            ]
        ]);

        $this->showBookingTypeModal = false;
        return redirect()->route('filament.admin.resources.recurring-bookings.create');
    }

    /**
     * ✅ Close modal
     */
    public function closeBookingTypeModal()
    {
        $this->showBookingTypeModal = false;
        $this->selectedDate = null;
        $this->selectedTimeSlot = null;
        $this->selectedVenueForBooking = null;
    }

    public function applyDateRange()
    {
        $this->cancelExpiredPendingBookings();
        $this->updateDateRangeText();
        $this->dispatch('close-modal', id: 'date-range-modal');
    }

    public function clearDateRange()
    {
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate = Carbon::today()->addDays(6)->format('Y-m-d');
        $this->dateRangeText = null;
        $this->cancelExpiredPendingBookings();
    }

    protected function updateDateRangeText()
    {
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);
            $this->dateRangeText = $start->format('d M Y') . ' - ' . $end->format('d M Y');
        }
    }

    public function getScheduleData()
    {
        $this->cancelExpiredPendingBookings();

        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        $schedules = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $daySchedule = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->isoFormat('dddd'),
                'date_number' => $date->format('d'),
                'month' => $date->isoFormat('MMMM'),
                'is_today' => $date->isToday(),
                'bookings' => []
            ];

            // ✅ Ambil SEMUA booking termasuk recurring
            $query = DB::table('bookings')
                ->join('clients', 'bookings.client_id', '=', 'clients.id')
                ->where('bookings.booking_date', $date->format('Y-m-d'))
                ->whereNotIn('bookings.status', ['cancelled'])
                ->select(
                    'bookings.*',
                    'clients.name as client_name'
                );

            if ($this->selectedVenue !== 'all') {
                $query->where('bookings.venue_type', $this->selectedVenue);
            }

            $bookings = $query->get();

            // Group bookings by time slot
            foreach ($bookings as $booking) {
                $timeSlots = json_decode($booking->time_slots, true);
                
                if (is_array($timeSlots)) {
                    foreach ($timeSlots as $slot) {
                        $time = $slot['time'] ?? null;
                        
                        if ($time) {
                            if (!isset($daySchedule['bookings'][$time])) {
                                $daySchedule['bookings'][$time] = collect();
                            }
                            
                            // ✅ Check if booking is recurring (monthly)
                            $isRecurring = $booking->notes && (
                                stripos($booking->notes, 'rutin') !== false ||
                                stripos($booking->notes, 'recurring') !== false ||
                                stripos($booking->notes, 'bulanan') !== false
                            );
                            
                            $daySchedule['bookings'][$time]->push((object)[
                                'id' => $booking->id,
                                'client' => (object)['name' => $booking->client_name],
                                'venue_type' => $booking->venue_type,
                                'total_price' => $booking->total_price,
                                'is_recurring' => $isRecurring, // ✅ Flag untuk recurring
                                'booking_type' => $isRecurring ? 'recurring' : ($booking->booking_type ?? ($booking->is_paid ? 'paid' : 'pending')),
                            ]);
                        }
                    }
                }
            }

            $schedules[] = $daySchedule;
        }

        return $schedules;
    }

    /**
     * ✅ Get schedule data per venue untuk tampilan "Semua Venue"
     */
    public function getScheduleDataPerVenue()
    {
        $this->cancelExpiredPendingBookings();

        $venues = [
            'cibadak_a' => 'Cibadak A',
            'cibadak_b' => 'Cibadak B',
            'pvj' => 'PVJ Mall',
            'urban' => 'Urban',
        ];

        $schedulesByVenue = [];

        foreach ($venues as $venueKey => $venueName) {
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);
            $schedules = [];

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $daySchedule = [
                    'date' => $date->format('Y-m-d'),
                    'day_name' => $date->isoFormat('dddd'),
                    'date_number' => $date->format('d'),
                    'month' => $date->isoFormat('MMMM'),
                    'is_today' => $date->isToday(),
                    'bookings' => []
                ];

                $bookings = DB::table('bookings')
                    ->join('clients', 'bookings.client_id', '=', 'clients.id')
                    ->where('bookings.booking_date', $date->format('Y-m-d'))
                    ->where('bookings.venue_type', $venueKey)
                    ->whereNotIn('bookings.status', ['cancelled'])
                    ->select(
                        'bookings.*',
                        'clients.name as client_name'
                    )
                    ->get();

                foreach ($bookings as $booking) {
                    $timeSlots = json_decode($booking->time_slots, true);
                    
                    if (is_array($timeSlots)) {
                        foreach ($timeSlots as $slot) {
                            $time = $slot['time'] ?? null;
                            
                            if ($time) {
                                if (!isset($daySchedule['bookings'][$time])) {
                                    $daySchedule['bookings'][$time] = collect();
                                }
                                
                                // ✅ Check if booking is recurring
                                $isRecurring = $booking->notes && (
                                    stripos($booking->notes, 'rutin') !== false ||
                                    stripos($booking->notes, 'recurring') !== false ||
                                    stripos($booking->notes, 'bulanan') !== false
                                );
                                
                                $daySchedule['bookings'][$time]->push((object)[
                                    'id' => $booking->id,
                                    'client' => (object)['name' => $booking->client_name],
                                    'venue_type' => $booking->venue_type,
                                    'total_price' => $booking->total_price,
                                    'is_recurring' => $isRecurring,
                                    'booking_type' => $isRecurring ? 'recurring' : ($booking->booking_type ?? ($booking->is_paid ? 'paid' : 'pending')),
                                ]);
                            }
                        }
                    }
                }

                $schedules[] = $daySchedule;
            }

            $schedulesByVenue[] = [
                'venue_key' => $venueKey,
                'venue_name' => $venueName,
                'schedules' => $schedules
            ];
        }

        return $schedulesByVenue;
    }

    public function getTimeSlots()
    {
        return [
            '06.00 - 08.00',
            '08.00 - 10.00',
            '10.00 - 12.00',
            '12.00 - 14.00',
            '14.00 - 16.00',
            '16.00 - 18.00',
            '18.00 - 20.00',
            '20.00 - 22.00',
            '22.00 - 00.00',
        ];
    }

    public function hydrate()
    {
        $this->cancelExpiredPendingBookings();
    }

    public function refreshCalendar()
    {
        $this->cancelExpiredPendingBookings();
        $this->dispatch('calendar-refreshed');
    }
}