<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\BookedTimeSlot;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * ✅ MUTATE DATA BEFORE CREATE - Set booking_type = 'manual' untuk booking yang dibuat admin
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ✅ Set booking_type menjadi 'manual' karena dibuat oleh admin
        $data['booking_type'] = 'manual';
        
        // ✅ Set payment_status = 'paid' karena booking manual dianggap sudah dibayar
        $data['payment_status'] = 'paid';
        
        // ✅ Set paid_at jika is_paid = true
        if ($data['is_paid'] ?? false) {
            $data['paid_at'] = now();
        }

        // ✅ Auto-generate venue_id berdasarkan venue_type jika belum ada
        if (!isset($data['venue_id'])) {
            $data['venue_id'] = match($data['venue_type']) {
                'cibadak_a' => 1,
                'cibadak_b' => 2,
                'pvj' => 3,
                'urban' => 4,
                default => 1,
            };
        }

        return $data;
    }

    /**
     * ✅ AFTER CREATE - Buat BookedTimeSlot records
     */
    protected function afterCreate(): void
    {
        $booking = $this->record;

        // ✅ Create booked time slots
        if (!empty($booking->time_slots)) {
            foreach ($booking->time_slots as $slot) {
                BookedTimeSlot::create([
                    'booking_id' => $booking->id,
                    'date' => $booking->booking_date,
                    'time_slot' => $slot['time'],
                    'venue_type' => $booking->venue_type,
                ]);
            }
        }

        // ✅ Show success notification
        Notification::make()
            ->title('Booking Manual Berhasil Dibuat')
            ->success()
            ->body('Booking manual untuk ' . $booking->client->name . ' telah berhasil ditambahkan ke kalender.')
            ->send();
    }

    /**
     * ✅ HANDLE RECORD CREATION - Validate time slots availability
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        DB::beginTransaction();

        try {
            // ✅ Check if time slots are available
            if (!empty($data['time_slots'])) {
                $requestedSlots = array_column($data['time_slots'], 'time');

                // Check confirmed bookings
                $confirmedBooked = BookedTimeSlot::where('date', $data['booking_date'])
                    ->where('venue_type', $data['venue_type'])
                    ->whereIn('time_slot', $requestedSlots)
                    ->whereHas('booking', function ($query) {
                        $query->where(function ($q) {
                            $q->where('payment_status', 'paid')
                                ->orWhere('status', 'confirmed')
                                ->orWhere('is_paid', true);
                        });
                    })
                    ->exists();

                if ($confirmedBooked) {
                    DB::rollBack();
                    
                    Notification::make()
                        ->title('Slot Waktu Tidak Tersedia')
                        ->danger()
                        ->body('Maaf, slot waktu yang Anda pilih sudah dibooking. Silakan pilih slot waktu lain.')
                        ->send();

                    $this->halt();
                }
            }

            // Create the booking record
            $record = static::getModel()::create($data);

            DB::commit();

            return $record;

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Gagal Membuat Booking')
                ->danger()
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->send();

            throw $e;
        }
    }
}