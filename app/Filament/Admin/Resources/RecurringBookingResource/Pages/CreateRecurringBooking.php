<?php

namespace App\Filament\Admin\Resources\RecurringBookingResource\Pages;

use App\Filament\Admin\Resources\RecurringBookingResource;
use App\Models\Booking;
use App\Models\BookedTimeSlot;
use App\Models\Client;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateRecurringBooking extends CreateRecord
{
    protected static string $resource = RecurringBookingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        DB::beginTransaction();
        
        try {
            // ✅ Generate dates based on recurring mode
            $selectedDates = $this->generateRecurringDates($data);

            if (empty($selectedDates)) {
                throw new \RuntimeException('Tidak ada tanggal yang berhasil di-generate');
            }

            // ✅ Check conflicts untuk semua tanggal
            $conflicts = $this->checkConflicts(
                $selectedDates,
                $data['venue_type'],
                $data['time_slots_selection']
            );

            if (!empty($conflicts)) {
                DB::rollBack();
                
                $conflictDates = implode(', ', array_map(function($date) {
                    return Carbon::parse($date)->format('d M');
                }, array_slice($conflicts, 0, 5))); // Show first 5
                
                $totalConflicts = count($conflicts);
                $message = $totalConflicts > 5 
                    ? "Ada {$totalConflicts} tanggal konflik. Contoh: {$conflictDates}..."
                    : "Tanggal konflik: {$conflictDates}";
                
                Notification::make()
                    ->title('Ada Konflik Booking!')
                    ->danger()
                    ->body($message)
                    ->persistent()
                    ->send();
                
                $this->halt();
                throw new \RuntimeException('Booking conflict detected');
            }

            // ✅ Handle manual customer dengan membuat dummy client
            $clientId = null;
            
            if ($data['customer_type'] === 'manual') {
                $guestClient = Client::firstOrCreate(
                    ['email' => 'guest_manual@thearena.local'],
                    [
                        'name' => $data['customer_name_manual'],
                        'phone' => $data['customer_phone_manual'] ?? null,
                        'password' => bcrypt('dummy_password_' . time()),
                    ]
                );
                
                $clientId = $guestClient->id;
            } else {
                $clientId = $data['client_id'];
            }

            // ✅ Build notes dengan pattern info
            $notes = $this->buildNotesWithPattern($data);

            $createdCount = 0;
            $firstBooking = null;
            $totalPrice = 0;

            // ✅ Create booking untuk setiap tanggal yang di-generate
            foreach ($selectedDates as $date) {
                $timeSlots = $this->formatTimeSlots(
                    $data['time_slots_selection'], 
                    $data['venue_type'],
                    $date
                );
                $bookingPrice = array_sum(array_column($timeSlots, 'price'));
                $totalPrice += $bookingPrice;

                $booking = Booking::create([
                    'client_id' => $clientId,
                    'venue_id' => $this->getVenueId($data['venue_type']),
                    'booking_date' => $date,
                    'venue_type' => $data['venue_type'],
                    'time_slots' => $timeSlots,
                    'total_price' => $bookingPrice,
                    'status' => $data['status'] ?? 'confirmed',
                    'payment_status' => $data['payment_status'] ?? 'paid',
                    'is_paid' => $data['is_paid'] ?? true,
                    'booking_type' => 'recurring',
                    'notes' => $notes,
                ]);

                // ✅ Create booked time slots
                foreach ($timeSlots as $slot) {
                    BookedTimeSlot::create([
                        'booking_id' => $booking->id,
                        'date' => $date,
                        'time_slot' => $slot['time'],
                        'venue_type' => $data['venue_type'],
                    ]);
                }

                if (!$firstBooking) {
                    $firstBooking = $booking;
                }
                
                $createdCount++;
            }

            DB::commit();

            // ✅ Success notification dengan info pattern
            $firstDate = Carbon::parse($selectedDates[0])->format('d M Y');
            $lastDate = Carbon::parse(end($selectedDates))->format('d M Y');
            
            $customerName = $data['customer_type'] === 'manual' 
                ? $data['customer_name_manual'] 
                : 'Selected Customer';

            $patternInfo = $this->getPatternDescription($data);

            Notification::make()
                ->title('Booking Member Berhasil!')
                ->success()
                ->body("✅ {$customerName}\n📅 {$createdCount} booking ({$patternInfo})\n💰 Total: Rp " . number_format($totalPrice, 0, ',', '.') . "\n📆 {$firstDate} - {$lastDate}")
                ->send();

            return $firstBooking;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create recurring booking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Gagal Membuat Booking')
                ->danger()
                ->body('Error: ' . $e->getMessage())
                ->persistent()
                ->send();
            
            $this->halt();
            throw $e;
        }
    }

    /**
     * ✅ Generate dates based on recurring pattern
     */
    protected function generateRecurringDates(array $data): array
    {
        $mode = $data['recurring_mode'] ?? 'custom';
        
        switch ($mode) {
            case 'weekly':
                return $this->generateWeeklyDates($data);
            
            case 'monthly_date':
                return $this->generateMonthlyDates($data);
            
            case 'custom':
            default:
                return $this->generateCustomDates($data);
        }
    }

    /**
     * ✅ Generate dates for WEEKLY pattern
     * Example: Every Monday & Wednesday for 12 weeks
     */
    protected function generateWeeklyDates(array $data): array
    {
        $dates = [];
        $selectedDays = $data['weekly_days'] ?? [];
        $startDate = Carbon::parse($data['weekly_start_date'] ?? now());
        $duration = (int)($data['weekly_duration'] ?? 4); // weeks
        
        if (empty($selectedDays)) {
            return [];
        }

        // Start from the beginning of the week
        $currentDate = $startDate->copy()->startOfWeek();
        $endDate = $startDate->copy()->addWeeks($duration);

        while ($currentDate->lt($endDate)) {
            $dayOfWeek = $currentDate->dayOfWeek;
            
            // Check if this day is selected AND date is >= start date
            if (in_array($dayOfWeek, $selectedDays) && $currentDate->gte($startDate)) {
                $dates[] = $currentDate->format('Y-m-d');
            }
            
            $currentDate->addDay();
        }

        return $dates;
    }

    /**
     * ✅ Generate dates for MONTHLY DATE pattern
     * Example: Every 10th and 25th for 6 months
     */
    protected function generateMonthlyDates(array $data): array
    {
        $dates = [];
        $selectedDates = $data['monthly_dates'] ?? [];
        $startDate = Carbon::parse($data['monthly_start_date'] ?? now())->startOfMonth();
        $duration = (int)($data['monthly_duration'] ?? 3); // months
        
        if (empty($selectedDates)) {
            return [];
        }

        for ($i = 0; $i < $duration; $i++) {
            $currentMonth = $startDate->copy()->addMonths($i);
            
            foreach ($selectedDates as $day) {
                try {
                    // Create date for this day in current month
                    $date = $currentMonth->copy()->day((int)$day);
                    
                    // Only add if date is in the future or today
                    if ($date->gte(now()->startOfDay())) {
                        $dates[] = $date->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    // Skip invalid dates (e.g., Feb 30)
                    continue;
                }
            }
        }

        sort($dates);
        return $dates;
    }

    /**
     * ✅ Generate dates for CUSTOM pattern (manual selection)
     */
    protected function generateCustomDates(array $data): array
    {
        return collect($data['selected_dates'] ?? [])
            ->pluck('date')
            ->filter()
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * ✅ Build notes with pattern information
     */
    protected function buildNotesWithPattern(array $data): string
    {
        $notes = $data['notes'] ?? '';
        
        // Add customer info if manual
        if ($data['customer_type'] === 'manual') {
            $customerInfo = "Customer Manual: {$data['customer_name_manual']}";
            if (!empty($data['customer_phone_manual'])) {
                $customerInfo .= " | Phone: {$data['customer_phone_manual']}";
            }
            $notes = $customerInfo . ($notes ? " | " . $notes : '');
        }

        // Add pattern info
        $patternInfo = $this->getPatternDescription($data);
        $notes .= " | Booking Member ({$patternInfo})";
        $notes .= " | Generated: " . Carbon::now()->format('d M Y H:i');

        return $notes;
    }

    /**
     * ✅ Get human-readable pattern description
     */
    protected function getPatternDescription(array $data): string
    {
        $mode = $data['recurring_mode'] ?? 'custom';
        
        switch ($mode) {
            case 'weekly':
                $days = collect($data['weekly_days'] ?? [])->map(function($d) {
                    $names = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 
                             4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
                    return $names[$d] ?? '';
                })->filter()->join(', ');
                $duration = $data['weekly_duration'] ?? 4;
                return "Setiap {$days} selama {$duration} minggu";
            
            case 'monthly_date':
                $dates = collect($data['monthly_dates'] ?? [])->sort()->join(', ');
                $duration = $data['monthly_duration'] ?? 3;
                return "Tanggal {$dates} tiap bulan selama {$duration} bulan";
            
            case 'custom':
            default:
                $count = count($data['selected_dates'] ?? []);
                return "{$count} tanggal custom";
        }
    }

    /**
     * ✅ Calculate price based on venue, date, and time
     */
    protected function calculatePrice($venueType, $date, $timeSlot): int
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [0, 6]);

        preg_match('/^(\d{2})\./', $timeSlot, $matches);
        $startHour = isset($matches[1]) ? (int)$matches[1] : 0;

        if ($venueType === 'pvj') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 16) return 700000;
                elseif ($startHour >= 16 && $startHour < 20) return 700000;
                elseif ($startHour >= 20 && $startHour < 24) return 500000;
            } else {
                if ($startHour >= 6 && $startHour < 16) return 350000;
                elseif ($startHour >= 16 && $startHour < 20) return 700000;
                elseif ($startHour >= 20 && $startHour < 24) return 500000;
            }
        }

        if ($venueType === 'cibadak_a') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 20) return 700000;
                elseif ($startHour >= 20 && $startHour < 24) return 500000;
            } else {
                if ($startHour >= 6 && $startHour < 16) return 350000;
                elseif ($startHour >= 16 && $startHour < 24) return 700000;
            }
        }

        if ($venueType === 'cibadak_b') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 20) return 550000;
                elseif ($startHour >= 20 && $startHour < 24) return 450000;
            } else {
                if ($startHour >= 6 && $startHour < 16) return 300000;
                elseif ($startHour >= 16 && $startHour < 20) return 550000;
                elseif ($startHour >= 20 && $startHour < 24) return 450000;
            }
        }

        if ($venueType === 'urban') {
            if ($isWeekend) return 550000;
            else {
                if ($startHour >= 6 && $startHour < 16) return 300000;
                elseif ($startHour >= 16 && $startHour < 24) return 550000;
            }
        }

        return 350000;
    }

    /**
     * ✅ Check conflicts untuk tanggal-tanggal yang dipilih
     */
    protected function checkConflicts(array $dates, string $venueType, array $timeSlots): array
    {
        $conflicts = [];

        foreach ($dates as $date) {
            $hasConflict = BookedTimeSlot::where('date', $date)
                ->where('venue_type', $venueType)
                ->whereIn('time_slot', $timeSlots)
                ->whereHas('booking', function ($query) {
                    $query->where(function($q) {
                        $q->where('payment_status', 'paid')
                          ->orWhere('status', 'confirmed')
                          ->orWhere('is_paid', true);
                    });
                })
                ->exists();

            if ($hasConflict) {
                $conflicts[] = $date;
            }
        }

        return $conflicts;
    }

    /**
     * ✅ Format time slots with DYNAMIC pricing per date
     */
    protected function formatTimeSlots(array $selectedSlots, string $venueType, string $date): array
    {
        return array_map(function ($time) use ($venueType, $date) {
            return [
                'time' => $time,
                'duration' => 120,
                'price' => $this->calculatePrice($venueType, $date, $time),
            ];
        }, $selectedSlots);
    }

    /**
     * ✅ Get venue ID from venue type
     */
    protected function getVenueId(string $venueType): int
    {
        $venueMap = [
            'cibadak_a' => 1,
            'cibadak_b' => 2,
            'pvj' => 3,
            'urban' => 4,
        ];

        return $venueMap[$venueType] ?? 1;
    }
}