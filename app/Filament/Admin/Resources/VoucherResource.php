<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\VoucherResource\Pages;
use App\Filament\Admin\Resources\VoucherResource\RelationManagers;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Voucher';

    protected static ?string $modelLabel = 'Voucher';

    protected static ?string $pluralModelLabel = 'Voucher';

    protected static ?string $navigationGroup = 'Booking Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Voucher')
                    ->description('Detail informasi voucher')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Kode Voucher')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50)
                                    ->placeholder('Contoh: DISKON50K')
                                    ->helperText('Kode voucher harus unik dan akan otomatis uppercase')
                                    ->columnSpan(1),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Aktifkan/nonaktifkan voucher')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Kosongkan Jika Tidak Perlu')
                            ->helperText('Deskripsi akan ditampilkan ke pengguna')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Pengaturan Diskon')
                    ->description('Atur tipe dan nilai diskon')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('discount_type')
                                    ->label('Tipe Diskon')
                                    ->options([
                                        'percentage' => 'Persentase (%)',
                                        'fixed' => 'Nominal Tetap (Rp)',
                                    ])
                                    ->required()
                                    ->default('percentage')
                                    ->live()
                                    ->helperText('Pilih tipe diskon')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('discount_value')
                                    ->label('Nilai Diskon')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix(fn (Forms\Get $get) => $get('discount_type') === 'percentage' ? '%' : 'Rp')
                                    ->placeholder(fn (Forms\Get $get) => $get('discount_type') === 'percentage' ? '10' : '50000')
                                    ->helperText(fn (Forms\Get $get) => 
                                        $get('discount_type') === 'percentage' 
                                            ? 'Contoh: 10 = 10%' 
                                            : 'Contoh: 50000 = Rp 50.000'
                                    )
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('max_discount')
                                    ->label('Max Diskon (Rp)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->placeholder('100000')
                                    ->helperText('Khusus untuk persentase. Kosongkan jika tidak ada batasan')
                                    ->hidden(fn (Forms\Get $get) => $get('discount_type') !== 'percentage')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\TextInput::make('min_purchase')
                            ->label('Minimal Pembelian (Rp)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('Rp')
                            ->placeholder('300000')
                            ->helperText('Minimal total booking untuk bisa gunakan voucher. 0 = tidak ada minimum')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Periode & Batasan')
                    ->description('Atur masa berlaku dan batasan penggunaan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('valid_from')
                                    ->label('Berlaku Dari')
                                    ->nullable()
                                    ->default(now())
                                    ->helperText('Kosongkan jika langsung berlaku')
                                    ->columnSpan(1),

                                Forms\Components\DateTimePicker::make('valid_until')
                                    ->label('Berlaku Sampai')
                                    ->nullable()
                                    ->helperText('Kosongkan jika tidak ada batas waktu')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('usage_limit')
                                    ->label('Batas Penggunaan')
                                    ->numeric()
                                    ->nullable()
                                    ->minValue(1)
                                    ->placeholder('100')
                                    ->helperText('Total berapa kali voucher bisa digunakan. Kosongkan untuk unlimited')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('used_count')
                                    ->label('Sudah Digunakan')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Otomatis bertambah saat voucher digunakan')
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Kode voucher berhasil disalin!')
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('discount_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'success',
                        'fixed' => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percentage' => 'Persentase',
                        'fixed' => 'Nominal',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_value')
                    ->label('Nilai Diskon')
                    ->formatStateUsing(function ($record) {
                        if ($record->discount_type === 'percentage') {
                            return $record->discount_value . '%';
                        }
                        return 'Rp ' . number_format($record->discount_value, 0, ',', '.');
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('min_purchase')
                    ->label('Min. Pembelian')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('usage_stats')
                    ->label('Penggunaan')
                    ->formatStateUsing(function ($record) {
                        if ($record->usage_limit === null) {
                            return "{$record->used_count} / ∞";
                        }
                        return "{$record->used_count} / {$record->usage_limit}";
                    })
                    ->badge()
                    ->color(fn ($record) => 
                        $record->usage_limit && $record->used_count >= $record->usage_limit 
                            ? 'danger' 
                            : 'success'
                    ),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_from')
                    ->label('Berlaku Dari')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Berlaku Sampai')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->color(fn ($record) => 
                        $record->valid_until && $record->valid_until->isPast() 
                            ? 'danger' 
                            : 'success'
                    )
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('discount_type')
                    ->label('Tipe Diskon')
                    ->options([
                        'percentage' => 'Persentase',
                        'fixed' => 'Nominal',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),

                Filter::make('expired')
                    ->label('Kadaluarsa')
                    ->query(fn (Builder $query): Builder => $query->where('valid_until', '<', now())),

                Filter::make('active_now')
                    ->label('Berlaku Sekarang')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('is_active', true)
                            ->where(function ($q) {
                                $q->whereNull('valid_from')
                                    ->orWhere('valid_from', '<=', now());
                            })
                            ->where(function ($q) {
                                $q->whereNull('valid_until')
                                    ->orWhere('valid_until', '>=', now());
                            })
                    ),

                Filter::make('limit_reached')
                    ->label('Limit Tercapai')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotNull('usage_limit')
                            ->whereColumn('used_count', '>=', 'usage_limit')
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->action(function (Voucher $record) {
                        $newVoucher = $record->replicate();
                        $newVoucher->code = $record->code . '_COPY';
                        $newVoucher->used_count = 0;
                        $newVoucher->save();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Voucher berhasil diduplikat')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVouchers::route('/'),
            'create' => Pages\CreateVoucher::route('/create'),
            'view' => Pages\ViewVoucher::route('/{record}'),
            'edit' => Pages\EditVoucher::route('/{record}/edit'),
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