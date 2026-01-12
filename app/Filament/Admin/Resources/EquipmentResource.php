<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EquipmentResource\Pages;
use App\Models\Equipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Equipment';

    protected static ?string $modelLabel = 'Peralatan';

    protected static ?string $pluralModelLabel = 'Peralatan';

    protected static ?string $navigationGroup = 'Booking Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Peralatan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Peralatan')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Contoh: Ring Portable Professional'),

                                Forms\Components\Select::make('category')
                                    ->label('Kategori')
                                    ->required()
                                    ->options([
                                        'Ring Portable' => 'Ring Portable',
                                        'Bola Basket' => 'Bola Basket',
                                        'Rompi' => 'Rompi',
                                        'Sepatu' => 'Sepatu',
                                        'Pelindung' => 'Pelindung',
                                        'Aksesoris' => 'Aksesoris',
                                        'Peralatan Basket' => 'Peralatan Basket',
                                        'Event & Supporting Tools' => 'Event & Supporting Tools',
                                    ])
                                    ->native(false)
                                    ->searchable()
                                    ->helperText('Pilih kategori peralatan')
                                    ->placeholder('Pilih kategori...'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('Jelaskan detail peralatan, kondisi, dan kegunaan...'),
                    ]),

                Section::make('Gambar Peralatan (Maksimal 5)')
                    ->description('Upload gambar peralatan. Gambar pertama akan menjadi gambar utama. Format: JPG, PNG, WebP (Max 5MB)')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\FileUpload::make('image_1')
                                    ->label('Gambar 1 (Utama)')
                                    ->image()
                                    ->directory('equipment-images')
                                    ->maxSize(5120)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->required()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->downloadable()
                                    ->previewable(false)
                                    ->helperText('Max 5MB. Upload langsung tanpa resize.'),

                                Forms\Components\FileUpload::make('image_2')
                                    ->label('Gambar 2')
                                    ->image()
                                    ->directory('equipment-images')
                                    ->maxSize(5120)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->disk('public')
                                    ->visibility('public')
                                    ->downloadable()
                                    ->previewable(false),

                                Forms\Components\FileUpload::make('image_3')
                                    ->label('Gambar 3')
                                    ->image()
                                    ->directory('equipment-images')
                                    ->maxSize(5120)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->disk('public')
                                    ->visibility('public')
                                    ->downloadable()
                                    ->previewable(false),

                                Forms\Components\FileUpload::make('image_4')
                                    ->label('Gambar 4')
                                    ->image()
                                    ->directory('equipment-images')
                                    ->maxSize(5120)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->disk('public')
                                    ->visibility('public')
                                    ->downloadable()
                                    ->previewable(false),

                                Forms\Components\FileUpload::make('image_5')
                                    ->label('Gambar 5')
                                    ->image()
                                    ->directory('equipment-images')
                                    ->maxSize(5120)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->disk('public')
                                    ->visibility('public')
                                    ->downloadable()
                                    ->previewable(false),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Harga')
                    ->schema([
                        Forms\Components\TextInput::make('price_per_item')
                            ->label('Harga per Item per Jam')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->step(1000)
                            ->placeholder('50000')
                            ->helperText('Harga Tidak Akan Ditampilkan Dihalaman'),
                    ]),

                Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_available')
                            ->label('Tersedia untuk Booking')
                            ->default(true)
                            ->helperText('Aktifkan untuk menampilkan di halaman booking')
                            ->inline(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Peralatan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('formatted_price')
                    ->label('Harga per Item/Jam')
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy('price_per_item', $direction);
                    }),

                Tables\Columns\IconColumn::make('is_available')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->options([
                        'Ring Portable' => 'Ring Portable',
                        'Bola Basket' => 'Bola Basket',
                        'Rompi' => 'Rompi',
                        'Sepatu' => 'Sepatu',
                        'Pelindung' => 'Pelindung',
                        'Aksesoris' => 'Aksesoris',
                        'Peralatan Basket' => 'Peralatan Basket',
                        'Event & Supporting Tools' => 'Event & Supporting Tools',
                    ])
                    ->multiple()
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Status Ketersediaan')
                    ->placeholder('Semua')
                    ->trueLabel('Tersedia')
                    ->falseLabel('Tidak Tersedia')
                    ->native(false),
            ])
            ->actions([
                // Modal View Action
                Tables\Actions\Action::make('view_details')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn(Equipment $record): string => 'Detail Equipment: ' . $record->name)
                    ->modalContent(fn(Equipment $record): \Illuminate\View\View => view(
                        'filament.admin.resources.equipment.view-modal',
                        ['record' => $record]
                    ))
                    ->modalWidth('4xl')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),


                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->action(function (Equipment $record) {
                        $images = ['image_1', 'image_2', 'image_3', 'image_4', 'image_5'];
                        foreach ($images as $image) {
                            if ($record->$image) {
                                \Storage::disk('public')->delete($record->$image);
                            }
                        }
                        $record->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $images = ['image_1', 'image_2', 'image_3', 'image_4', 'image_5'];
                                foreach ($images as $image) {
                                    if ($record->$image) {
                                        \Storage::disk('public')->delete($record->$image);
                                    }
                                }
                                $record->delete();
                            }
                        }),
                    Tables\Actions\BulkAction::make('toggle_availability')
                        ->label('Toggle Ketersediaan')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_available' => !$record->is_available]);
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->color('warning'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Peralatan')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Nama Peralatan')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('category')
                                    ->label('Kategori')
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('formatted_price')
                                    ->label('Harga per Item/Jam')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->color('success'),

                                Infolists\Components\IconEntry::make('is_available')
                                    ->label('Status Ketersediaan')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ]),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Galeri Gambar')
                    ->schema([
                        Infolists\Components\ImageEntry::make('image_1')
                            ->label('Gambar 1 (Utama)')
                            ->visible(fn($record) => $record->image_1)
                            ->size(300),

                        Infolists\Components\ImageEntry::make('image_2')
                            ->label('Gambar 2')
                            ->visible(fn($record) => $record->image_2)
                            ->size(300),

                        Infolists\Components\ImageEntry::make('image_3')
                            ->label('Gambar 3')
                            ->visible(fn($record) => $record->image_3)
                            ->size(300),

                        Infolists\Components\ImageEntry::make('image_4')
                            ->label('Gambar 4')
                            ->visible(fn($record) => $record->image_4)
                            ->size(300),

                        Infolists\Components\ImageEntry::make('image_5')
                            ->label('Gambar 5')
                            ->visible(fn($record) => $record->image_5)
                            ->size(300),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Infolists\Components\Section::make('Informasi Tambahan')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Dibuat pada')
                                    ->dateTime('d F Y H:i'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Terakhir diupdate')
                                    ->dateTime('d F Y H:i'),
                            ]),
                    ])
                    ->collapsible(),
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
            'index' => Pages\ListEquipment::route('/'),
            'create' => Pages\CreateEquipment::route('/create'),
            'edit' => Pages\EditEquipment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_available', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
