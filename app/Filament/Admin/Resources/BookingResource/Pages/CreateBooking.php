<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Models\BookedTimeSlot;
use Filament\Notifications\Notification;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    public function mount(): void
    {
        parent::mount();

        $prefillData = session('calendar_prefill', []);

        if (!empty($prefillData)) {
            session()->forget('calendar_prefill');

            $timeSlotWithPrice = [[
                'time' => $prefillData['time_slot'],
                'duration' => 120,
                'price' => BookingResource::calculatePrice(
                    $prefillData['venue_type'],
                    $prefillData['booking_date'],
                    $prefillData['time_slot']
                ),
            ]];

            $this->form->fill([
                'booking_date' => $prefillData['booking_date'],
                'venue_type' => $prefillData['venue_type'],
                'time_slots' => $timeSlotWithPrice,
                'total_price' => $timeSlotWithPrice[0]['price'],
                'status' => 'pending',
                'is_paid' => false,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('calendar');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['is_paid']) && $data['is_paid'] === true) {
            $data['status'] = 'confirmed';
            $data['payment_status'] = 'paid';
            $data['paid_at'] = now();
        } else {
            $data['payment_status'] = 'pending';
        }

        return $data;
    }

    // ✅ VALIDASI: Cek slot sudah dibooking atau belum SEBELUM create
    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        
        if (!isset($data['time_slots']) || !is_array($data['time_slots'])) {
            return;
        }

        $bookedSlots = [];

        foreach ($data['time_slots'] as $slot) {
            // Cek apakah slot ini sudah ada di database
            $exists = BookedTimeSlot::where('date', $data['booking_date'])
                ->where('venue_type', $data['venue_type'])
                ->where('time_slot', $slot['time'])
                ->exists();

            if ($exists) {
                $bookedSlots[] = $slot['time'];
            }
        }

        // Jika ada slot yang sudah dibooking, tampilkan error
        if (!empty($bookedSlots)) {
            Notification::make()
                ->title('Slot Waktu Sudah Dibooking!')
                ->danger()
                ->body('Slot berikut sudah dibooking oleh client lain: ' . implode(', ', $bookedSlots))
                ->persistent()
                ->send();

            // Stop proses create
            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $booking = $this->record;
        
        if ($booking->time_slots && is_array($booking->time_slots)) {
            foreach ($booking->time_slots as $slot) {
                // ✅ Gunakan try-catch untuk handle duplicate entry
                try {
                    BookedTimeSlot::create([
                        'booking_id' => $booking->id,
                        'date' => $booking->booking_date,
                        'time_slot' => $slot['time'],
                        'venue_type' => $booking->venue_type,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Jika terjadi duplicate, rollback booking
                    $booking->delete();
                    
                    Notification::make()
                        ->title('Booking Gagal!')
                        ->danger()
                        ->body('Slot waktu "' . $slot['time'] . '" sudah dibooking oleh client lain. Silakan pilih waktu lain.')
                        ->persistent()
                        ->send();
                    
                    // Redirect ke create page
                    $this->redirect($this->getResource()::getUrl('create'));
                    return;
                }
            }
        }
    }
}