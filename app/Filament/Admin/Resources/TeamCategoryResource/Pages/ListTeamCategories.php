<?php

namespace App\Filament\Admin\Resources\TeamCategoryResource\Pages;

use App\Filament\Admin\Resources\TeamCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeamCategories extends ListRecords
{
    protected static string $resource = TeamCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}