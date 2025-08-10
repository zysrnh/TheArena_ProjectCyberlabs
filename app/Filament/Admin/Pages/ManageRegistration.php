<?php

namespace App\Filament\Admin\Pages;

use App\Settings\RegistrationSettings;
use Dom\Text;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageRegistration extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = RegistrationSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('vip_limit')
                    ->label('Limit VIP')
                    ->helperText('Set ke -1 untuk limit tidak terbatas')
                    ->numeric()
                    ->step(1), 
                TextInput::make('pers_limit')
                    ->label('Limit Pers')
                    ->helperText('Set ke -1 untuk limit tidak terbatas')
                    ->numeric()
                    ->step(1),
                TextInput::make('regular_limit')
                    ->label('Limit Umum')
                    ->helperText('Set ke -1 untuk limit tidak terbatas')
                    ->numeric()
                    ->step(1),
            ]);
    }
}
