<?php

namespace App\Filament\Admin\Resources\LeagueResource\Pages;

use App\Filament\Admin\Resources\LeagueResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeague extends CreateRecord
{
    protected static string $resource = LeagueResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}