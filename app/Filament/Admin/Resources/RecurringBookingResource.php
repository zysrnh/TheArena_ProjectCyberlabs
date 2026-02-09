<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RecurringBookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;

class RecurringBookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Booking Member';

    protected static ?string $navigationGroup = 'Booking Management';

    protected static ?string $pluralLabel = 'Booking Member';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Customer')
                    ->description('Pilih customer yang sudah terdaftar atau input manual')
                    ->schema([
                        Forms\Components\Radio::make('customer_type')
                            ->label('Tipe Customer')
                            ->options([
                                'existing' => 'Customer Terdaftar',
                                'manual' => 'Input Manual (Guest/Walk-in)',
                            ])
                            ->default('existing')
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('client_id')
                            ->label('Pilih Customer')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn (Forms\Get $get): bool => $get('customer_type') === 'existing'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('customer_name_manual')
                                    ->label('Nama Customer')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('customer_phone_manual')
                                    ->label('No. Telepon')
                                    ->tel()
                                    ->maxLength(20),
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('customer_type') === 'manual'),
                    ])->columns(1),

                Forms\Components\Section::make('Pengaturan Venue & Waktu')
                    ->schema([
                        Forms\Components\Select::make('venue_type')
                            ->label('Pilih Venue')
                            ->options([
                                'cibadak_a' => 'Cibadak A',
                                'cibadak_b' => 'Cibadak B',
                                'pvj' => 'PVJ Mall',
                                'urban' => 'Urban',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\CheckboxList::make('time_slots_selection')
                            ->label('Pilih Waktu Main')
                            ->options([
                                '06.00 - 08.00' => '06.00 - 08.00',
                                '08.00 - 10.00' => '08.00 - 10.00',
                                '10.00 - 12.00' => '10.00 - 12.00',
                                '12.00 - 14.00' => '12.00 - 14.00',
                                '14.00 - 16.00' => '14.00 - 16.00',
                                '16.00 - 18.00' => '16.00 - 18.00',
                                '18.00 - 20.00' => '18.00 - 20.00',
                                '20.00 - 22.00' => '20.00 - 22.00',
                                '22.00 - 00.00' => '22.00 - 00.00',
                            ])
                            ->required()
                            ->columns(3)
                            ->helperText('⚠️ Harga akan otomatis dihitung per tanggal (weekday/weekend)'),
                    ])->columns(1),

                // ✅ NEW: Smart Recurring Pattern Section
                Forms\Components\Section::make('Pola Booking Rutin')
                    ->description('Atur jadwal berulang otomatis - pilih pola yang sesuai')
                    ->schema([
                        Forms\Components\Radio::make('recurring_mode')
                            ->label('Mode Pengulangan')
                            ->options([
                                'weekly' => 'Mingguan (Pilih hari tertentu setiap minggu)',
                                'monthly_date' => 'Bulanan (Tanggal tertentu setiap bulan)',
                                'custom' => 'Custom (Pilih tanggal manual satu-satu)',
                            ])
                            ->default('weekly')
                            ->live()
                            ->required()
                            ->columnSpanFull(),

                        // ========================================
                        // MODE 1: WEEKLY PATTERN
                        // ========================================
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\CheckboxList::make('weekly_days')
                                    ->label('Pilih Hari Main')
                                    ->options([
                                        1 => 'Senin',
                                        2 => 'Selasa',
                                        3 => 'Rabu',
                                        4 => 'Kamis',
                                        5 => 'Jumat',
                                        6 => 'Sabtu',
                                        0 => 'Minggu',
                                    ])
                                    ->columns(2)
                                    ->required()
                                    ->helperText('Contoh: Pilih Senin & Rabu = main setiap Senin & Rabu'),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('weekly_start_date')
                                            ->label('Mulai Tanggal')
                                            ->native(false)
                                            ->required()
                                            ->default(now())
                                            ->minDate(now())
                                            ->displayFormat('d F Y'),

                                        Forms\Components\Select::make('weekly_duration')
                                            ->label('Durasi')
                                            ->options([
                                                4 => '1 Bulan (4 minggu)',
                                                8 => '2 Bulan (8 minggu)',
                                                12 => '3 Bulan (12 minggu)',
                                                24 => '6 Bulan (24 minggu)',
                                                52 => '1 Tahun (52 minggu)',
                                            ])
                                            ->default(4)
                                            ->required(),
                                    ]),
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('recurring_mode') === 'weekly'),

                        Forms\Components\Placeholder::make('weekly_preview')
                            ->label('Preview Jadwal')
                            ->content(function (Forms\Get $get) {
                                $days = $get('weekly_days') ?? [];
                                $startDate = $get('weekly_start_date');
                                $duration = $get('weekly_duration') ?? 4;

                                if (empty($days) || !$startDate) {
                                    return 'Pilih hari dan tanggal mulai untuk melihat preview';
                                }

                                $dayNames = [
                                    0 => 'Minggu',
                                    1 => 'Senin',
                                    2 => 'Selasa',
                                    3 => 'Rabu',
                                    4 => 'Kamis',
                                    5 => 'Jumat',
                                    6 => 'Sabtu',
                                ];

                                $selectedDays = collect($days)->map(fn($d) => $dayNames[$d])->join(', ');
                                $totalBookings = count($days) * $duration;
                                $endDate = Carbon::parse($startDate)->addWeeks($duration)->subDay();

                                return "📅 **{$totalBookings} booking** akan dibuat\n\n" .
                                       "🗓️ Setiap **{$selectedDays}**\n\n" .
                                       "📆 Periode: " . Carbon::parse($startDate)->format('d M Y') . " - " . $endDate->format('d M Y');
                            })
                            ->visible(fn (Forms\Get $get): bool => $get('recurring_mode') === 'weekly'),

                        // ========================================
                        // MODE 2: MONTHLY DATE PATTERN
                        // ========================================
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\CheckboxList::make('monthly_dates')
                                    ->label('Pilih Tanggal (1-31)')
                                    ->options(array_combine(range(1, 31), range(1, 31)))
                                    ->columns(4)
                                    ->required()
                                    ->helperText('Contoh: Pilih 10 & 25 = main setiap tanggal 10 & 25 tiap bulan'),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('monthly_start_date')
                                            ->label('Mulai Bulan')
                                            ->native(false)
                                            ->required()
                                            ->default(now()->startOfMonth())
                                            ->minDate(now())
                                            ->displayFormat('F Y'),

                                        Forms\Components\Select::make('monthly_duration')
                                            ->label('Durasi')
                                            ->options([
                                                1 => '1 Bulan',
                                                2 => '2 Bulan',
                                                3 => '3 Bulan',
                                                6 => '6 Bulan',
                                                12 => '1 Tahun',
                                            ])
                                            ->default(3)
                                            ->required(),
                                    ]),
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('recurring_mode') === 'monthly_date'),

                        Forms\Components\Placeholder::make('monthly_preview')
                            ->label('Preview Jadwal')
                            ->content(function (Forms\Get $get) {
                                $dates = $get('monthly_dates') ?? [];
                                $startDate = $get('monthly_start_date');
                                $duration = $get('monthly_duration') ?? 3;

                                if (empty($dates) || !$startDate) {
                                    return 'Pilih tanggal dan bulan mulai untuk melihat preview';
                                }

                                $selectedDates = collect($dates)->sort()->join(', ');
                                $totalBookings = count($dates) * $duration;
                                $endDate = Carbon::parse($startDate)->addMonths($duration)->subDay();

                                return "📅 **{$totalBookings} booking** akan dibuat\n\n" .
                                       "🗓️ Setiap tanggal **{$selectedDates}**\n\n" .
                                       "📆 Periode: " . Carbon::parse($startDate)->format('F Y') . " - " . $endDate->format('F Y');
                            })
                            ->visible(fn (Forms\Get $get): bool => $get('recurring_mode') === 'monthly_date'),

                        // ========================================
                        // MODE 3: CUSTOM MANUAL DATES
                        // ========================================
                        Forms\Components\Repeater::make('selected_dates')
                            ->label('Daftar Tanggal Custom')
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal')
                                    ->required()
                                    ->native(false)
                                    ->minDate(now())
                                    ->displayFormat('d F Y')
                                    ->helperText('Pilih tanggal yang ingin dibooking'),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('+ Tambah Tanggal')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                !empty($state['date']) 
                                    ? Carbon::parse($state['date'])->format('d F Y') 
                                    : null
                            )
                            ->columns(1)
                            ->required()
                            ->minItems(1)
                            ->visible(fn (Forms\Get $get): bool => $get('recurring_mode') === 'custom'),

                        Forms\Components\Placeholder::make('custom_preview')
                            ->label('Ringkasan Booking')
                            ->content(function (Forms\Get $get) {
                                $dates = $get('selected_dates') ?? [];
                                $validDates = array_filter($dates, fn($d) => !empty($d['date']));
                                
                                if (empty($validDates)) {
                                    return 'Belum ada tanggal yang dipilih';
                                }

                                $count = count($validDates);
                                $sortedDates = collect($validDates)
                                    ->pluck('date')
                                    ->sort()
                                    ->values();

                                $firstDate = Carbon::parse($sortedDates->first())->format('d M Y');
                                $lastDate = Carbon::parse($sortedDates->last())->format('d M Y');

                                return "📅 Total: **{$count} booking**\n\n📆 Periode: {$firstDate} - {$lastDate}";
                            })
                            ->visible(fn (Forms\Get $get): bool => $get('recurring_mode') === 'custom'),
                    ])->columns(1),

                Forms\Components\Section::make('Detail Booking')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                            ])
                            ->default('confirmed')
                            ->required(),

                        Forms\Components\Select::make('payment_status')
                            ->label('Status Pembayaran')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                            ])
                            ->default('paid')
                            ->required(),

                        Forms\Components\Toggle::make('is_paid')
                            ->label('Sudah Dibayar')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->placeholder('Contoh: Member Platinum, Booking rutin setiap minggu')
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query->where(function ($q) {
                    $q->where('booking_type', 'recurring')
                      ->orWhere('notes', 'like', '%Booking Rutin%')
                      ->orWhere('notes', 'like', '%Member%');
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        if ($record->client) {
                            return $record->client->name;
                        }
                        
                        // Extract customer name from notes for manual entries
                        if (preg_match('/Customer Manual: ([^|]+)/', $record->notes, $matches)) {
                            return trim($matches[1]) . ' (Manual)';
                        }
                        
                        return 'Guest';
                    }),

                Tables\Columns\TextColumn::make('booking_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('venue_type')
                    ->label('Venue')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'cibadak_a' => 'success',
                        'cibadak_b' => 'info',
                        'pvj' => 'warning',
                        'urban' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'cibadak_a' => 'Cibadak A',
                        'cibadak_b' => 'Cibadak B',
                        'pvj' => 'PVJ',
                        'urban' => 'Urban',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('time_slots')
                    ->label('Waktu')
                    ->formatStateUsing(function ($record) {
                        $slots = $record->time_slots;
                        if (!is_array($slots) || empty($slots)) {
                            return '-';
                        }

                        $times = array_column($slots, 'time');
                        if (count($times) > 1) {
                            return $times[0] . ' (+' . (count($times) - 1) . ')';
                        }
                        return $times[0];
                    }),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                    }),

                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Paid')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('venue_type')
                    ->label('Venue')
                    ->options([
                        'cibadak_a' => 'Cibadak A',
                        'cibadak_b' => 'Cibadak B',
                        'pvj' => 'PVJ',
                        'urban' => 'Urban',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('booking_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringBookings::route('/'),
            'create' => Pages\CreateRecurringBooking::route('/create'),
            'edit' => Pages\EditRecurringBooking::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where(function ($q) {
            $q->where('booking_type', 'recurring')
              ->orWhere('notes', 'like', '%Booking Rutin%');
        })
        ->where('status', 'confirmed')
        ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}