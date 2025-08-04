<?php

namespace App\Filament\Admin\Resources\RegistrationResource\Pages;

use App\Filament\Admin\Resources\RegistrationResource;
use App\Jobs\GenerateQr;
use App\Models\Seat;
use App\Services\QrService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CreateRegistration extends CreateRecord
{
    protected static string $resource = RegistrationResource::class;
    protected int $seatId;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->seatId = $data['seat_id'];
        unset($data['seat_id']);

        if (! str_starts_with($data['phone'], '+62')) {
            $data['phone'] = '+62' . ltrim($data['phone'], '0+');
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $registration = $this->record;

        Seat::find($this->seatId)->update([
            'registration_id' => $registration->id,
        ]);

        GenerateQr::dispatch($registration);
    }
}
