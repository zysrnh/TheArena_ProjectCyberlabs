<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SeatResource\Pages;
use App\Filament\Admin\Resources\SeatResource\RelationManagers;
use App\Models\Seat;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SeatResource extends Resource
{
    protected static ?string $model = Seat::class;

    protected static ?string $navigationIcon = 'heroicon-s-map-pin';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('label')->required(),
                Select::make('type')->options(['table' => 'Table', 'theater' => 'Theater'])->required(),
                TextInput::make('group_name')->label('Group/Table Name'),
                TextInput::make('row')->numeric()->nullable(),
                TextInput::make('column')->numeric()->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label'),
                TextColumn::make('type'),
                TextColumn::make('group_name'),
                TextColumn::make('registration.name')
                    ->label('Assigned To')
                    ->default('-'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Action::make('generateSeats')
                    ->form([
                        Select::make('type')->options([
                            'theater' => 'Theater',
                            'table' => 'Table',
                        ])
                            ->required()
                            ->live(),

                        TextInput::make('rows')
                            ->numeric()
                            ->visible(fn($get) => $get('type') === 'theater'),
                        TextInput::make('columns')
                            ->numeric()
                            ->visible(fn($get) => $get('type') === 'theater'),

                        TextInput::make('tables')
                            ->numeric()
                            ->visible(fn($get) => $get('type') === 'table'),
                        TextInput::make('seats_per_table')
                            ->numeric()
                            ->visible(fn($get) => $get('type') === 'table'),
                    ])
                    ->action(function (array $data) {
                        if ($data['type'] === 'theater') {
                            Seat::where('type', 'theater')->delete();
                            foreach (range(1, $data['rows']) as $row) {
                                foreach (range(1, $data['columns']) as $col) {
                                    Seat::create([
                                        'type' => 'theater',
                                        'label' => "R{$row}C{$col}",
                                        'row' => $row,
                                        'column' => $col,
                                    ]);
                                }
                            }
                        } else {
                            Seat::where('type', 'table')->delete();
                            foreach (range(1, $data['tables']) as $table) {
                                foreach (range(1, $data['seats_per_table']) as $seat) {
                                    Seat::create([
                                        'type' => 'table',
                                        'group_name' => "Table {$table}",
                                        'label' => "Table {$table} - Seat {$seat}",
                                    ]);
                                }
                            }
                        }
                    })
                    ->icon('heroicon-o-plus')
                    ->color('success'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeats::route('/'),
            'create' => Pages\CreateSeat::route('/create'),
            'edit' => Pages\EditSeat::route('/{record}/edit'),
        ];
    }
}
