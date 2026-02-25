<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TeamResource\Pages;
use App\Filament\Admin\Resources\TeamResource\RelationManagers\PlayersRelationManager;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Teams';

    protected static ?string $navigationGroup = 'Teams Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Team Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Team Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., PRAWIRA BANDUNG'),

                        Forms\Components\TextInput::make('division')
                            ->label('Division')
                            ->maxLength(50)
                            ->placeholder('e.g., Blue, White...')
                            ->datalist([
                                'Blue',
                                'White',
                            ])
                            ->helperText('Opsional - ketik atau pilih divisi tim'),

                        Forms\Components\FileUpload::make('logo')
                            ->label('Team Logo')
                            ->image()
                            ->directory('teams/logos')
                            ->required()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->imageEditorAspectRatios([
                                '1:1',
                                '16:9',
                            ])
                            ->deletable(true)
                            ->downloadable(false)
                            ->openable(true)
                            ->previewable(true)
                            ->disk('public')
                            ->visibility('public')
                            ->columnSpanFull()
                            ->helperText('Upload logo tim. Maks 2MB. Format: JPG, PNG, WebP.')
                            ->imagePreviewHeight('250')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('center'),

                        Forms\Components\Select::make('region')
                            ->label('Region')
                            ->options([
                                'Jakarta' => 'Jakarta',
                                'Bandung' => 'Bandung',
                                'Surabaya' => 'Surabaya',
                                'Semarang' => 'Semarang',
                                'Medan' => 'Medan',
                                'Bali' => 'Bali',
                            ])
                            ->searchable()
                            ->placeholder('Select region'),

                        Forms\Components\TextInput::make('city')
                            ->label('City')
                            ->maxLength(255)
                            ->placeholder('e.g., Bandung'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('Brief description about the team')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Status')
                            ->default(true)
                            ->helperText('Inactive teams won\'t appear in match creation'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Team Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Team $record): ?string => $record->division ? 'Divisi: ' . $record->division : null),

                Tables\Columns\TextColumn::make('division')
                    ->label('Division')
                    ->badge()
                    ->color(fn (?string $state): string => match($state) {
                        'Blue'  => 'info',
                        'White' => 'gray',
                        default => 'primary',
                    })
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('region')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('players_count')
                    ->counts('players')
                    ->label('Total Players')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('division')
                    ->options([
                        'Blue'  => 'Blue',
                        'White' => 'White',
                    ])
                    ->label('Division')
                    ->placeholder('All Divisions'),

                Tables\Filters\SelectFilter::make('region')
                    ->options([
                        'Jakarta' => 'Jakarta',
                        'Bandung' => 'Bandung',
                        'Surabaya' => 'Surabaya',
                        'Semarang' => 'Semarang',
                        'Medan' => 'Medan',
                        'Bali' => 'Bali',
                    ])
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Teams')
                    ->trueLabel('Active Teams')
                    ->falseLabel('Inactive Teams')
                    ->native(false),

                Tables\Filters\Filter::make('has_minimum_players')
                    ->label('Minimum Active Players (5+)')
                    ->query(fn ($query) => $query->whereHas('players', function ($q) {
                        $q->where('is_active', true);
                    }, '>=', 5))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Team $record): string => 'Detail Tim: ' . $record->name)
                    ->modalContent(fn (Team $record): \Illuminate\View\View => view(
                        'filament.admin.resources.team.view-modal',
                        ['record' => $record]
                    ))
                    ->modalWidth('3xl')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Tables\Actions\Action::make('viewLogo')
                    ->label('Lihat Logo')
                    ->icon('heroicon-o-photo')
                    ->color('gray')
                    ->modalContent(fn (Team $record) => view('filament.modals.logo-preview', [
                        'logo' => $record->logo,
                        'name' => $record->name,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->visible(fn (Team $record): bool => !empty($record->logo)),

                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Team $record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (Team $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Team $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Team $record): string => $record->is_active ? 'Nonaktifkan Tim?' : 'Aktifkan Tim?')
                    ->modalDescription(fn (Team $record): string => 
                        $record->is_active 
                            ? 'Tim yang dinonaktifkan tidak akan muncul di pembuatan pertandingan.' 
                            : 'Pastikan tim memiliki minimal 5 player aktif sebelum diaktifkan.'
                    )
                    ->action(function (Team $record) {
                        $activePlayersCount = $record->players()->where('is_active', true)->count();
                        
                        if (!$record->is_active && $activePlayersCount < 5) {
                            Notification::make()
                                ->title('Tidak Dapat Mengaktifkan Tim')
                                ->danger()
                                ->body("Tim harus memiliki minimal 5 player aktif. Saat ini hanya ada {$activePlayersCount} player aktif.")
                                ->send();
                            return;
                        }
                        
                        $record->update(['is_active' => !$record->is_active]);
                        
                        Notification::make()
                            ->title($record->is_active ? 'Tim Diaktifkan' : 'Tim Dinonaktifkan')
                            ->success()
                            ->body($record->is_active ? 'Tim sekarang aktif dan dapat digunakan untuk pertandingan.' : 'Tim telah dinonaktifkan.')
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('Aktifkan Tim')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Aktifkan Tim Terpilih')
                        ->modalDescription('Hanya tim dengan minimal 5 player aktif yang akan diaktifkan.')
                        ->action(function ($records) {
                            $activated = 0;
                            $failed = 0;
                            
                            foreach ($records as $record) {
                                $activePlayersCount = $record->players()->where('is_active', true)->count();
                                
                                if ($activePlayersCount >= 5) {
                                    $record->update(['is_active' => true]);
                                    $activated++;
                                } else {
                                    $failed++;
                                }
                            }
                            
                            $message = "{$activated} tim berhasil diaktifkan.";
                            if ($failed > 0) {
                                $message .= " {$failed} tim gagal diaktifkan karena tidak memenuhi minimal player aktif.";
                            }
                            
                            Notification::make()
                                ->title('Proses Aktivasi Selesai')
                                ->success()
                                ->body($message)
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('bulk_deactivate')
                        ->label('Nonaktifkan Tim')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->title('Tim Dinonaktifkan')
                                ->success()
                                ->body(count($records) . ' tim telah dinonaktifkan.')
                                ->send();
                        }),
                    
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            PlayersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $teamsWithoutMinPlayers = static::getModel()::whereDoesntHave('players', function ($query) {
            $query->where('is_active', true);
        }, '>=', 5)->count();
        
        return $teamsWithoutMinPlayers > 0 ? (string) $teamsWithoutMinPlayers : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
    
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Tim dengan player aktif < 5';
    }
}