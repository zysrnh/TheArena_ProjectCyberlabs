<?php

namespace App\Filament\Admin\Resources\SeatResource\Pages;

use App\Filament\Admin\Resources\SeatResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ListSeats extends ListRecords
{
    protected static string $resource = SeatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Meja' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'table')),
            'Teater' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('type', 'theater')),
        ];
    }
}
