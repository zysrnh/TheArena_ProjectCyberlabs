<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\BookedTimeSlot;
use App\Models\Client;
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
     * ✅ Mount - baca session prefill dari kalender
     */
    public function mount(): void
    {
        parent::mount();

        $prefill = session()->pull('calendar_prefill');

        if ($prefill) {
            $data = [];

            if (!empty($prefill['booking_date'])) {
                $data['booking_date'] = $prefill['booking_date'];
            }

            if (!empty($prefill['venue_type'])) {
                $data['venue_type'] = $prefill['venue_type'];
            }

            if (!empty($prefill['time_slot'])) {
                $price = BookingResource::calculatePrice(
                    $prefill['venue_type'] ?? 'cibadak_a',
                    $prefill['booking_date'] ?? now()->format('Y-m-d'),
                    $prefill['time_slot']
                );

                $data['time_slots'] = [[
                    'time' => $prefill['time_slot'],
                    'duration' => 120,
                    'price' => $price,
                ]];

                $data['total_price'] = $price;
            }

            if (!empty($data)) {
                $this->form->fill($data);
            }
        }
    }

    /**
     * ✅ MUTATE DATA BEFORE CREATE
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ✅ Handle manual customer
        if (($data['customer_type'] ?? 'existing') === 'manual') {
            $client = Client::create([
                'name' => $data['customer_name_manual'],
                'phone' => $data['customer_phone_manual'] ?? null,
                'email' => 'manual_' . time() . '_' . rand(1000, 9999) . '@thearena.local',
                'password' => bcrypt('dummy_' . time()),
            ]);

            $data['client_id'] = $client->id;

            // Simpan juga di notes
            $manualInfo = "Customer Manual: {$data['customer_name_manual']}";
            if (!empty($data['customer_phone_manual'])) {
                $manualInfo .= " | Phone: {$data['customer_phone_manual']}";
            }
            $data['notes'] = $manualInfo . (!empty($data['notes']) ? " | " . $data['notes'] : '');
        }

        // Bersihkan field virtual
        unset($data['customer_type'], $data['customer_name_manual'], $data['customer_phone_manual']);

        // ✅ Set booking_type = 'manual'
        $data['booking_type'] = 'manual';

        // ✅ Set payment_status = 'paid'
        $data['payment_status'] = 'paid';

        // ✅ Set paid_at jika is_paid = true
        if ($data['is_paid'] ?? false) {
            $data['paid_at'] = now();
        }

        // ✅ Auto-generate venue_id
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

        $clientName = $booking->client?->name ?? 'Guest';

        Notification::make()
            ->title('Booking Manual Berhasil Dibuat')
            ->success()
            ->body("Booking manual untuk {$clientName} telah berhasil ditambahkan ke kalender.")
            ->send();
    }

    /**
     * ✅ HANDLE RECORD CREATION - Validate time slots availability
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        DB::beginTransaction();

        try {
            if (!empty($data['time_slots'])) {
                $requestedSlots = array_column($data['time_slots'], 'time');

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