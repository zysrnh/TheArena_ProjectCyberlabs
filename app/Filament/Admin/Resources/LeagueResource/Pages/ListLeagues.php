<?php
// File: app/Filament/Admin/Resources/LeagueResource/Pages/ListLeagues.php

namespace App\Filament\Admin\Resources\LeagueResource\Pages;

use App\Filament\Admin\Resources\LeagueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeagues extends ListRecords
{
    protected static string $resource = LeagueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}