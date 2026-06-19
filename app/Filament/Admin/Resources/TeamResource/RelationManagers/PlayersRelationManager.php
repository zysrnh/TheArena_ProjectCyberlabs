<?php

namespace App\Filament\Admin\Resources\TeamResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PlayersRelationManager extends RelationManager
{
    protected static string $relationship = 'players';

    protected static ?string $title = 'Players';

    protected static ?string $icon = 'heroicon-o-users';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Player Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('jersey_no')
                                    ->label('Jersey Number')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(99)
                                    ->placeholder('e.g., 10')
                                    ->helperText('Nomor jersey pemain (0-99)')
                                    ->rules([
                                        fn (\Filament\Forms\Get $get, ?string $operation, ?\Illuminate\Database\Eloquent\Model $record) => function (string $attribute, $value, \Closure $fail) use ($record) {
                                            $teamId = $this->getOwnerRecord()->id;
                                            $query = \App\Models\Player::where('team_id', $teamId)
                                                ->where('jersey_no', (string) $value);

                                            // When editing, exclude the current record
                                            if ($record) {
                                                $query->where('id', '!=', $record->id);
                                            }

                                            if ($query->exists()) {
                                                $fail('Jersey number sudah digunakan di tim ini.');
                                            }
                                        },
                                    ]),

                                Forms\Components\Select::make('position')
                                    ->label('Position')
                                    ->required()
                                    ->options([
                                        'PG' => 'Point Guard (PG)',
                                        'SG' => 'Shooting Guard (SG)',
                                        'SF' => 'Small Forward (SF)',
                                        'PF' => 'Power Forward (PF)',
                                        'C' => 'Center (C)',
                                    ])
                                    ->searchable()
                                    ->placeholder('Select position')
                                    ->helperText('Posisi pemain di lapangan')
                                    ->default('PG'),
                            ]),

                        Forms\Components\TextInput::make('name')
                            ->label('Player Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Yudha Saputera')
                            ->columnSpanFull(),

                        // ✅ FIX: Hapus filter team_id karena kolom tersebut tidak ada di tabel team_categories
                        // TeamCategory adalah global (tidak terikat ke team tertentu)
                        Forms\Components\Select::make('team_category_id')
                            ->label('Team Category')
                            ->relationship('teamCategory', 'category_name', function ($query) {
                                $query->where('is_active', true)
                                      ->orderBy('category_name');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Select category (optional)')
                            ->helperText('Assign player to category: U-16, U-18, U-22, Senior, etc.')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('photo')
                            ->label('Player Photo')
                            ->image()
                            ->directory('players/photos')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->imageEditorAspectRatios([
                                '1:1',
                                '3:4',
                            ])
                            ->deletable(true)
                            ->downloadable(false)
                            ->openable(true)
                            ->previewable(true)
                            ->disk('public')
                            ->visibility('public')
                            ->columnSpanFull()
                            ->helperText('Upload foto pemain. Maks 2MB. Format: JPG, PNG, WebP.')
                            ->imagePreviewHeight('250')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('center'),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('birth_date')
                                    ->label('Birth Date')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->placeholder('Select birth date'),

                                Forms\Components\TextInput::make('height')
                                    ->label('Height (cm)')
                                    ->numeric()
                                    ->suffix('cm')
                                    ->placeholder('e.g., 185'),

                                Forms\Components\TextInput::make('weight')
                                    ->label('Weight (kg)')
                                    ->numeric()
                                    ->suffix('kg')
                                    ->placeholder('e.g., 78'),
                            ]),
                    ])
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('jersey_no')
                    ->label('No.')
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->alignCenter()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large),

                Tables\Columns\ImageColumn::make('photo')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=013064&background=ffd22f')
                    ->size(50),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->is_active ? '✓ Active for match' : '⊘ Not active'),

                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PG' => 'info',
                        'SG' => 'success',
                        'SF' => 'warning',
                        'PF' => 'danger',
                        'C' => 'primary',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('teamCategory.category_name')
                    ->label('Category')
                    ->badge()
                    ->color('warning')
                    ->sortable()
                    ->searchable()
                    ->default('-')
                    ->toggleable()
                    ->tooltip('U-16, U-18, U-22, Senior, etc.'),

                Tables\Columns\TextColumn::make('height')
                    ->label('Height')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' cm' : '-')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('weight')
                    ->label('Weight')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' kg' : '-')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable()
                    ->onColor('success')
                    ->offColor('danger')
                    ->tooltip('Toggle to activate/deactivate player for matches'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('position')
                    ->options([
                        'PG' => 'Point Guard',
                        'SG' => 'Shooting Guard',
                        'SF' => 'Small Forward',
                        'PF' => 'Power Forward',
                        'C' => 'Center',
                    ])
                    ->label('Position'),

                // ✅ FIX: Hapus filter team_id, query semua category yang aktif
                Tables\Filters\SelectFilter::make('team_category_id')
                    ->label('Category')
                    ->relationship('teamCategory', 'category_name', function ($query) {
                        $query->where('is_active', true)->orderBy('category_name');
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('All Categories'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Players')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Player')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['is_active'] = true;
                        return $data;
                    })
                    ->successNotificationTitle('Player added successfully!')
                    ->after(function ($livewire) {
                        $teamId = $livewire->getOwnerRecord()->id;
                        $playersCount = \App\Models\Player::where('team_id', $teamId)->count();
                        
                        if ($playersCount < 12) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Need more players!')
                                ->body('This team has ' . $playersCount . ' players. Minimum 12 players recommended.')
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Players activated')
                                ->body(count($records) . ' players have been activated for matches.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Players deactivated')
                                ->body(count($records) . ' players have been deactivated from matches.')
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('assign_category')
                        ->label('Assign to Category')
                        ->icon('heroicon-o-tag')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('team_category_id')
                                ->label('Select Category')
                                // ✅ FIX: Hapus filter team_id
                                ->options(function () {
                                    return \App\Models\TeamCategory::where('is_active', true)
                                        ->orderBy('category_name')
                                        ->pluck('category_name', 'id');
                                })
                                ->searchable()
                                ->required()
                                ->placeholder('Choose category'),
                        ])
                        ->action(function (array $data, $records) {
                            $records->each->update(['team_category_id' => $data['team_category_id']]);
                            
                            $categoryName = \App\Models\TeamCategory::find($data['team_category_id'])->category_name;
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Category Assigned')
                                ->body(count($records) . ' players assigned to ' . $categoryName)
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('jersey_no', 'asc')
            ->emptyStateHeading('No players yet')
            ->emptyStateDescription('Add at least 12 players to this team. Click the button above to get started.')
            ->emptyStateIcon('heroicon-o-users')
            ->paginated([12, 25, 50])
            ->defaultPaginationPageOption(12);
    }
}