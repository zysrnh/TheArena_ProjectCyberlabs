<?php

namespace App\Filament\Admin\Resources\RecurringBookingResource\Pages;

use App\Filament\Admin\Resources\RecurringBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Carbon\Carbon;

class EditRecurringBooking extends EditRecord
{
    protected static string $resource = RecurringBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!empty($data['notes']) && stripos($data['notes'], 'Customer:') !== false) {
            $data['customer_type'] = 'manual';
            
            if (preg_match('/Customer: (.+?)(\||$)/i', $data['notes'], $matches)) {
                $data['customer_name_manual'] = trim($matches[1]);
            }
            
            if (preg_match('/Phone: (.+?)(\||$)/i', $data['notes'], $matches)) {
                $data['customer_phone_manual'] = trim($matches[1]);
            }
        } else {
            $data['customer_type'] = 'existing';
        }

        if (!empty($data['time_slots']) && is_array($data['time_slots'])) {
            $data['time_slots_selection'] = array_column($data['time_slots'], 'time');
        }

        $data['recurring_month'] = Carbon::parse($data['booking_date'])->format('Y-m');
        $data['recurring_days'] = [Carbon::parse($data['booking_date'])->dayOfWeek];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['time_slots_selection'])) {
            $date = $data['booking_date'] ?? now()->format('Y-m-d');
            
            // ✅ DYNAMIC PRICING per date
            $data['time_slots'] = array_map(function ($time) use ($data, $date) {
                return [
                    'time' => $time,
                    'duration' => 120,
                    'price' => $this->calculatePrice($data['venue_type'], $date, $time),
                ];
            }, $data['time_slots_selection']);

            $data['total_price'] = array_sum(array_column($data['time_slots'], 'price'));
        }

        $notes = $data['notes'] ?? '';
        if ($data['customer_type'] === 'manual') {
            $notes = preg_replace('/Customer: .+?(\||$)/i', '', $notes);
            $notes = preg_replace('/Phone: .+?(\||$)/i', '', $notes);
            
            $customerInfo = "Customer: {$data['customer_name_manual']}";
            if (!empty($data['customer_phone_manual'])) {
                $customerInfo .= " | Phone: {$data['customer_phone_manual']}";
            }
            $notes = trim($customerInfo . ($notes ? " | " . $notes : ''));
            
            $data['client_id'] = null;
        }

        $data['notes'] = $notes;

        unset($data['customer_type']);
        unset($data['customer_name_manual']);
        unset($data['customer_phone_manual']);
        unset($data['time_slots_selection']);
        unset($data['recurring_month']);
        unset($data['recurring_days']);
        unset($data['preview_dates']);
        unset($data['total_bookings']);

        return $data;
    }

    /**
     * ✅ DYNAMIC PRICING: Calculate price based on venue, date, and time
     */
    protected function calculatePrice($venueType, $date, $timeSlot): int
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [0, 6]);

        preg_match('/^(\d{2})\./', $timeSlot, $matches);
        $startHour = isset($matches[1]) ? (int)$matches[1] : 0;

        if ($venueType === 'pvj') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 16) {
                    return 700000;
                } elseif ($startHour >= 16 && $startHour < 20) {
                    return 700000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 500000;
                }
            } else {
                if ($startHour >= 6 && $startHour < 16) {
                    return 350000;
                } elseif ($startHour >= 16 && $startHour < 20) {
                    return 700000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 500000;
                }
            }
        }

        if ($venueType === 'cibadak_a') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 20) {
                    return 700000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 500000;
                }
            } else {
                if ($startHour >= 6 && $startHour < 16) {
                    return 350000;
                } elseif ($startHour >= 16 && $startHour < 24) {
                    return 700000;
                }
            }
        }

        if ($venueType === 'cibadak_b') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 20) {
                    return 550000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 450000;
                }
            } else {
                if ($startHour >= 6 && $startHour < 16) {
                    return 300000;
                } elseif ($startHour >= 16 && $startHour < 20) {
                    return 550000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 450000;
                }
            }
        }

        if ($venueType === 'urban') {
            if ($isWeekend) {
                return 550000;
            } else {
                if ($startHour >= 6 && $startHour < 16) {
                    return 300000;
                } elseif ($startHour >= 16 && $startHour < 24) {
                    return 550000;
                }
            }
        }

        return 350000;
    }
}