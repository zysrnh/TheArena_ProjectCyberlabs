<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FacilityResource\Pages;
use App\Models\Facility;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class FacilityResource extends Resource
{
    protected static ?string $model = Facility::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Fasilitas';
    protected static ?string $navigationGroup = 'Manajemen Konten';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Fasilitas')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Fasilitas')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Cafe & Resto'),

                        // ❌ DESKRIPSI DIHAPUS

                        Forms\Components\FileUpload::make('image_url')
    ->label('Gambar Fasilitas')
    ->image()
    ->directory('facility-images')
    ->maxSize(5120) // 5 MB
    ->imageEditor()
    ->imageEditorAspectRatios([
        '16:9',
        '4:3',
        '1:1',
    ])
                            ->helperText('Maksimal 2MB. Format: JPG, PNG. Klik untuk edit/crop gambar.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Pengaturan')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Nonaktifkan untuk menyembunyikan dari halaman'),

                        Forms\Components\TextInput::make('order')
                            ->label('Urutan Tampilan')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Urutan tampilan (semakin kecil, semakin kiri)'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Gambar')
                    ->circular()
                    ->defaultImageUrl('https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=800'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Fasilitas')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // ❌ KOLOM DESKRIPSI DIHAPUS

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('order')
                    ->label('Urutan')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),

                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Facility $record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (Facility $record): string => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Facility $record): string => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Facility $record) {
                        $newStatus = !$record->is_active;
                        $record->update(['is_active' => $newStatus]);
                        
                        Notification::make()
                            ->title($newStatus ? 'Fasilitas Diaktifkan' : 'Fasilitas Dinonaktifkan')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->color('warning'),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                            Notification::make()->title('Fasilitas Diaktifkan')->success()->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            Notification::make()->title('Fasilitas Dinonaktifkan')->success()->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum Ada Fasilitas')
            ->emptyStateDescription('Tambahkan fasilitas baru untuk ditampilkan di halaman About.')
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Fasilitas')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacilities::route('/'),
            'create' => Pages\CreateFacility::route('/create'),
            'edit' => Pages\EditFacility::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}