<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\BookedTimeSlot;
use Filament\Notifications\Notification;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function ($record) {
                    BookedTimeSlot::where('booking_id', $record->id)->delete();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('calendar');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // ✅ AUTO UPDATE STATUS SAMA KAYAK CREATE
        if (isset($data['is_paid']) && $data['is_paid'] === true) {
            $data['status'] = 'confirmed';
            $data['payment_status'] = 'paid';
            if (!$this->record->paid_at) {
                $data['paid_at'] = now();
            }
        } else {
            $data['status'] = 'pending'; // ✅ TAMBAHAN INI (SAMA KAYAK CREATE)
            $data['payment_status'] = 'pending';
            $data['paid_at'] = null;
        }

        return $data;
    }

    protected function beforeSave(): void
    {
        $data = $this->form->getState();
        
        if (!isset($data['time_slots']) || !is_array($data['time_slots'])) {
            return;
        }

        $bookedSlots = [];
        $currentBookingId = $this->record->id;

        foreach ($data['time_slots'] as $slot) {
            $exists = BookedTimeSlot::where('date', $data['booking_date'])
                ->where('venue_type', $data['venue_type'])
                ->where('time_slot', $slot['time'])
                ->where('booking_id', '!=', $currentBookingId)
                ->exists();

            if ($exists) {
                $bookedSlots[] = $slot['time'];
            }
        }

        if (!empty($bookedSlots)) {
            Notification::make()
                ->title('Slot Waktu Sudah Dibooking!')
                ->danger()
                ->body('Slot berikut sudah dibooking oleh client lain: ' . implode(', ', $bookedSlots))
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        $booking = $this->record;
        
        BookedTimeSlot::where('booking_id', $booking->id)->delete();
        
        if ($booking->time_slots && is_array($booking->time_slots)) {
            foreach ($booking->time_slots as $slot) {
                try {
                    BookedTimeSlot::create([
                        'booking_id' => $booking->id,
                        'date' => $booking->booking_date,
                        'time_slot' => $slot['time'],
                        'venue_type' => $booking->venue_type,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    \Log::error('Failed to create booked time slot', [
                        'booking_id' => $booking->id,
                        'slot' => $slot,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
}