<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendar')
                ->label('Lihat Kalender')
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->url(fn (): string => static::getResource()::getUrl('calendar')),
            
            Actions\CreateAction::make(),
        ];
    }
}