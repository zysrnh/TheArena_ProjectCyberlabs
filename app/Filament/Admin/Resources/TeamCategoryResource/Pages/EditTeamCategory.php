<?php

namespace App\Filament\Admin\Resources\TeamCategoryResource\Pages;

use App\Filament\Admin\Resources\TeamCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeamCategory extends EditRecord
{
    protected static string $resource = TeamCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}