<?php

namespace App\Filament\Admin\Resources\TeamCategoryResource\Pages;

use App\Filament\Admin\Resources\TeamCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamCategory extends CreateRecord
{
    protected static string $resource = TeamCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}