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
     * Sama seperti di BookingController
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

                    // Hapus BookedTimeSlot yang terkait
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
     * ✅ CREATE BOOKING DARI CALENDAR - AUTO FILL TIME SLOT
     */
    public function createBooking($date, $timeSlot, $venue = null)
    {
        // ✅ Jalankan auto-cancel sebelum create booking
        $this->cancelExpiredPendingBookings();

        // Simpan data ke session untuk auto-fill form
        session([
            'calendar_prefill' => [
                'booking_date' => $date,
                'time_slot' => $timeSlot,
                'venue_type' => $venue !== 'all' ? $venue : null,
            ]
        ]);

        // Redirect ke create page
        return redirect()->route('filament.admin.resources.bookings.create');
    }

    public function applyDateRange()
    {
        // ✅ Auto-cancel sebelum apply filter
        $this->cancelExpiredPendingBookings();
        
        $this->updateDateRangeText();
        $this->dispatch('close-modal', id: 'date-range-modal');
    }

    public function clearDateRange()
    {
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate = Carbon::today()->addDays(6)->format('Y-m-d');
        $this->dateRangeText = null;
        
        // ✅ Auto-cancel setelah clear filter
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
        // ✅ Auto-cancel sebelum load data
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

            // ✅ FIXED: Ambil SEMUA booking kecuali yang cancelled
            $query = DB::table('bookings')
                ->join('clients', 'bookings.client_id', '=', 'clients.id')
                ->where('bookings.booking_date', $date->format('Y-m-d'))
                ->whereNotIn('bookings.status', ['cancelled']) // Exclude yang cancelled aja
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
                            
                            $daySchedule['bookings'][$time]->push((object)[
                                'id' => $booking->id,
                                'client' => (object)['name' => $booking->client_name],
                                'venue_type' => $booking->venue_type,
                                'total_price' => $booking->total_price,
                                'booking_type' => $booking->booking_type ?? ($booking->is_paid ? 'paid' : 'pending'),
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
        // ✅ Auto-cancel sebelum load data
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

                // ✅ FIXED: Ambil SEMUA booking kecuali yang cancelled
                $bookings = DB::table('bookings')
                    ->join('clients', 'bookings.client_id', '=', 'clients.id')
                    ->where('bookings.booking_date', $date->format('Y-m-d'))
                    ->where('bookings.venue_type', $venueKey)
                    ->whereNotIn('bookings.status', ['cancelled']) // Exclude yang cancelled aja
                    ->select(
                        'bookings.*',
                        'clients.name as client_name'
                    )
                    ->get();

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
                                
                                $daySchedule['bookings'][$time]->push((object)[
                                    'id' => $booking->id,
                                    'client' => (object)['name' => $booking->client_name],
                                    'venue_type' => $booking->venue_type,
                                    'total_price' => $booking->total_price,
                                    'booking_type' => $booking->booking_type ?? ($booking->is_paid ? 'paid' : 'pending'),
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

    /**
     * ✅ LIVEWIRE LIFECYCLE: Auto-cancel setiap kali component di-render ulang
     */
    public function hydrate()
    {
        $this->cancelExpiredPendingBookings();
    }

    /**
     * ✅ Manual refresh calendar (bisa dipanggil dari frontend)
     */
    public function refreshCalendar()
    {
        $this->cancelExpiredPendingBookings();
        $this->dispatch('calendar-refreshed');
    }
}