<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LiveMatchResource\Pages;
use App\Models\LiveMatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class LiveMatchResource extends Resource
{
    protected static ?string $model = LiveMatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    
    protected static ?string $navigationLabel = 'Live Streaming';

    protected static ?string $navigationGroup = 'Content Management';
    
    protected static ?string $modelLabel = 'Live Match';
    
    protected static ?string $pluralModelLabel = 'Live Matches';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pertandingan')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Pertandingan')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('Contoh: SMP JUBILEE JAKARTA VS SMP DIAN HARAPAN'),

                        Forms\Components\TextInput::make('team_home')
                            ->label('Tim Home')
                            ->maxLength(255)
                            ->placeholder('Contoh: SMP Jubilee Jakarta'),

                        Forms\Components\TextInput::make('team_away')
                            ->label('Tim Away')
                            ->maxLength(255)
                            ->placeholder('Contoh: SMP Dian Harapan'),

                        Forms\Components\TextInput::make('category')
                            ->label('Kategori')
                            ->maxLength(255)
                            ->placeholder('Contoh: U-12 Putra, Professional Men, dll')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('venue')
                            ->label('Venue')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Plazza Gandaria City'),

                        Forms\Components\TextInput::make('court')
                            ->label('Lapangan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Fantastic 4'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Jadwal & Status')
                    ->schema([
                        Forms\Components\DatePicker::make('match_date')
                            ->label('Tanggal Pertandingan')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TextInput::make('time')
                            ->label('Waktu')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('10:30')
                            ->helperText('Format: HH:MM (contoh: 10:30, 14:00)'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'scheduled' => 'Scheduled',
                                'live' => 'Live',
                                'ended' => 'Ended',
                            ])
                            ->default('scheduled')
                            ->native(false),

                        Forms\Components\Select::make('series')
                            ->label('Series')
                            ->required()
                            ->options([
                                'regular' => 'Regular Season',
                                'playoff' => 'Playoff',
                                'final' => 'Final',
                            ])
                            ->default('regular')
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Media & Streaming')
                    ->schema([
                        Forms\Components\FileUpload::make('thumbnail')
                            ->label('Thumbnail')
                            ->image()
                            ->directory('live-matches')
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                            ])
                            ->deletable(true)
                            ->downloadable(false)
                            ->openable(true)
                            ->previewable(true)
                            ->disk('public')
                            ->visibility('public')
                            ->columnSpanFull()
                            ->helperText('Upload thumbnail pertandingan. Maks 5MB. Format: JPG, PNG, WebP.')
                            ->imagePreviewHeight('250')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('center'),

                        Forms\Components\TextInput::make('stream_url')
                            ->label('URL Streaming')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://youtube.com/watch?v=...')
                            ->helperText('Masukkan URL streaming (YouTube, Twitch, dll)'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Informasi Tambahan')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Deskripsi tambahan tentang pertandingan'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Non-aktifkan untuk menyembunyikan dari halaman live'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Pertandingan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40)
                    ->wrap()
                    ->description(fn (LiveMatch $record): string => 
                        ($record->team_home ?? 'TBA') . ' vs ' . ($record->team_away ?? 'TBA')
                    ),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('match_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn (LiveMatch $record): string => 
                        $record->time ?? '-'
                    ),

                Tables\Columns\TextColumn::make('venue')
                    ->label('Venue')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'live' => 'danger',
                        'scheduled' => 'warning',
                        'ended' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'live' => 'Live',
                        'scheduled' => 'Scheduled',
                        'ended' => 'Ended',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'live' => 'Live',
                        'ended' => 'Ended',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('series')
                    ->label('Series')
                    ->options([
                        'regular' => 'Regular Season',
                        'playoff' => 'Playoff',
                        'final' => 'Final',
                    ])
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (LiveMatch $record): string => 'Live Match: ' . $record->title)
                    ->modalContent(fn (LiveMatch $record): \Illuminate\View\View => 
                        view('filament.admin.resources.live-match.view-modal', ['record' => $record])
                    )
                    ->modalWidth('3xl')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Tables\Actions\Action::make('setLive')
                    ->label('Set Live')
                    ->icon('heroicon-o-signal')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Set Status Live')
                    ->modalDescription('Apakah Anda yakin ingin mengubah status pertandingan ini menjadi LIVE?')
                    ->modalSubmitActionLabel('Ya, Set Live')
                    ->action(function (LiveMatch $record) {
                        $record->update(['status' => 'live']);
                        
                        Notification::make()
                            ->success()
                            ->title('Status diubah menjadi LIVE')
                            ->body('Pertandingan sekarang ditampilkan sebagai LIVE.')
                            ->send();
                    })
                    ->visible(fn (LiveMatch $record): bool => $record->status !== 'live'),

                Tables\Actions\Action::make('setEnded')
                    ->label('End Match')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Set Status Ended')
                    ->modalDescription('Apakah Anda yakin ingin mengubah status pertandingan ini menjadi ENDED?')
                    ->modalSubmitActionLabel('Ya, Set Ended')
                    ->action(function (LiveMatch $record) {
                        $record->update(['status' => 'ended']);
                        
                        Notification::make()
                            ->success()
                            ->title('Status diubah menjadi ENDED')
                            ->body('Pertandingan telah selesai.')
                            ->send();
                    })
                    ->visible(fn (LiveMatch $record): bool => $record->status === 'live'),

                Tables\Actions\EditAction::make()
                    ->slideOver(),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('setLiveBulk')
                        ->label('Set Live')
                        ->icon('heroicon-o-signal')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Set Status Live')
                        ->modalDescription('Mengubah status pertandingan yang dipilih menjadi LIVE')
                        ->action(function ($records) {
                            $records->each->update(['status' => 'live']);
                            
                            Notification::make()
                                ->success()
                                ->title('Status diubah')
                                ->body(count($records) . ' pertandingan diubah menjadi LIVE.')
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('setEndedBulk')
                        ->label('Set Ended')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Set Status Ended')
                        ->modalDescription('Mengubah status pertandingan yang dipilih menjadi ENDED')
                        ->action(function ($records) {
                            $records->each->update(['status' => 'ended']);
                            
                            Notification::make()
                                ->success()
                                ->title('Status diubah')
                                ->body(count($records) . ' pertandingan diubah menjadi ENDED.')
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                            
                            Notification::make()
                                ->success()
                                ->title('Pertandingan diaktifkan')
                                ->body(count($records) . ' pertandingan telah diaktifkan.')
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->success()
                                ->title('Pertandingan dinonaktifkan')
                                ->body(count($records) . ' pertandingan telah dinonaktifkan.')
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('match_date', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLiveMatches::route('/'),
            'create' => Pages\CreateLiveMatch::route('/create'),
            'edit' => Pages\EditLiveMatch::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'live')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}