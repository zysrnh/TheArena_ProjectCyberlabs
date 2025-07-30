<?php

namespace App\Filament\Admin\Resources\RegistrationResource\Pages;

use App\Filament\Admin\Resources\RegistrationResource;
use App\Models\Seat;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRegistration extends EditRecord
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! str_starts_with($data['phone'], '+62')) {
            $data['phone'] = '+62' . ltrim($data['phone'], '0+');
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $seatId = $this->form->getState()['seat_id'];

        if (!$this->record) return;

        Seat::where('registration_id', $this->record->id)
            ->update(['registration_id' => null]);

        if ($seatId) {
            // Assign selected seat
            Seat::where('id', $seatId)
                ->update(['registration_id' => $this->record->id]);
        }
    }
}
