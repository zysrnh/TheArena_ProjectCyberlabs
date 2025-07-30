<?php

namespace App\Filament\Admin\Resources\SeatResource\Pages;

use App\Filament\Admin\Resources\SeatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeat extends EditRecord
{
    protected static string $resource = SeatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
