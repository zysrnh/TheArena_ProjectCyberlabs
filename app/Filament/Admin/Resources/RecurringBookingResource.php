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
                                'weekly'       => '🔁 Mingguan Rutin — Hari yang sama setiap minggu (ongoing)',
                                'weekly_flex'  => '📆 Mingguan Fleksibel — Tiap minggu pilih hari berbeda',
                                'monthly_day'  => '🗓️ Bulanan per Hari — Pilih hari berbeda tiap bulan',
                                'custom'       => '✏️ Custom — Pilih tanggal satu-satu',
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
                                try {
                                    $days = $get('weekly_days') ?? [];
                                    $startDate = $get('weekly_start_date');
                                    $duration = (int)($get('weekly_duration') ?? 4);

                                    if (empty($days) || !$startDate) {
                                        return 'Pilih hari dan tanggal mulai untuk melihat preview';
                                    }

                                    $dayNames = [0=>'Minggu',1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu'];
                                    $selectedDays = collect($days)->map(fn($d) => $dayNames[(int)$d] ?? '?')->filter()->join(', ');
                                    $totalBookings = count($days) * $duration;
                                    $endDate = Carbon::parse($startDate)->addWeeks($duration)->subDay();

                                    return "📅 **{$totalBookings} booking** akan dibuat\n\n" .
                                           "🗓️ Setiap **{$selectedDays}**\n\n" .
                                           "📆 Periode: " . Carbon::parse($startDate)->format('d M Y') . " — " . $endDate->format('d M Y');
                                } catch (\Throwable $e) {
                                    return 'Lengkapi data untuk melihat preview';
                                }
                            })
                            ->visible(fn (Forms\Get $get): bool => $get('recurring_mode') === 'weekly'),

                        // ========================================
                        // MODE 1b: MINGGUAN FLEKSIBEL
                        // ========================================

                        // Step 1: Pilih rentang tanggal
                        Forms\Components\Fieldset::make('Rentang Tanggal')
                            ->label('Langkah 1 — Pilih Rentang Tanggal')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('flex_range_start')
                                            ->label('Tanggal Mulai')
                                            ->native(false)
                                            ->live()
                                            ->minDate(now())
                                            ->displayFormat('d F Y')
                                            ->placeholder('Misal: 1 Mei 2026'),

                                        Forms\Components\DatePicker::make('flex_range_end')
                                            ->label('Tanggal Selesai')
                                            ->native(false)
                                            ->live()
                                            ->minDate(now())
                                            ->displayFormat('d F Y')
                                            ->placeholder('Misal: 31 Mei 2026')
                                            ->afterOrEqual('flex_range_start'),
                                    ]),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('generate_weeks')
                                        ->label('🗓️ Buat Minggu Otomatis dari Rentang')
                                        ->color('info')
                                        ->icon('heroicon-o-calendar-days')
                                        ->action(function (Forms\Set $set, Forms\Get $get) {
                                            $start = $get('flex_range_start');
                                            $end   = $get('flex_range_end');

                                            if (!$start || !$end) {
                                                \Filament\Notifications\Notification::make()
                                                    ->warning()
                                                    ->title('Lengkapi Tanggal')
                                                    ->body('Pilih tanggal mulai dan selesai terlebih dahulu.')
                                                    ->send();
                                                return;
                                            }

                                            $startDate = Carbon::parse($start);
                                            $endDate   = Carbon::parse($end);

                                            if ($startDate->gt($endDate)) {
                                                \Filament\Notifications\Notification::make()
                                                    ->warning()
                                                    ->title('Tanggal Tidak Valid')
                                                    ->body('Tanggal mulai harus sebelum tanggal selesai.')
                                                    ->send();
                                                return;
                                            }

                                            // Generate semua minggu dalam rentang
                                            $weeks     = [];
                                            $weekStart = $startDate->copy()->startOfWeek(Carbon::MONDAY);

                                            while ($weekStart->lte($endDate)) {
                                                // Hanya masukkan minggu yang ada irisan dengan rentang
                                                $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
                                                if ($weekEnd->gte($startDate)) {
                                                    $weeks[] = [
                                                        'week_of'     => $weekStart->format('Y-m-d'),
                                                        'days_of_week' => [],
                                                    ];
                                                }
                                                $weekStart->addWeek();
                                            }

                                            $set('weekly_flex_schedule', $weeks);

                                            \Filament\Notifications\Notification::make()
                                                ->success()
                                                ->title(count($weeks) . ' minggu berhasil di-generate!')
                                                ->body('Sekarang centang hari untuk setiap minggu di bawah.')
                                                ->send();
                                        }),
                                ])->fullWidth(),
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('recurring_mode') === 'weekly_flex'),

                        // Step 2: Repeater minggu (hasil generate atau manual)
                        Forms\Components\Repeater::make('weekly_flex_schedule')
                            ->label('Langkah 2 — Centang Hari per Minggu')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('week_of')
                                            ->label('Minggu')
                                            ->native(false)
                                            ->required()
                                            ->minDate(now())
                                            ->displayFormat('d F Y')
                                            ->helperText('Sistem ambil seluruh minggu (Senin–Minggu)'),

                                        Forms\Components\CheckboxList::make('days_of_week')
                                            ->label('Hari Main')
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
                                            ->helperText('Centang hari yang ingin dibooking'),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('+ Tambah Minggu Manual')
                            ->reorderable(false)
                            ->itemLabel(function (array $state): ?string {
                                try {
                                    if (empty($state['week_of'])) return null;
                                    $weekStart = Carbon::parse($state['week_of'])->startOfWeek(Carbon::MONDAY);
                                    $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
                                    $dayNames  = [0=>'Min',1=>'Sen',2=>'Sel',3=>'Rab',4=>'Kam',5=>'Jum',6=>'Sab'];
                                    $days = collect($state['days_of_week'] ?? [])
                                        ->map(fn($d) => $dayNames[(int)$d] ?? '')
                                        ->filter()->join(', ');
                                    $label = $weekStart->format('d M') . ' – ' . $weekEnd->format('d M Y');
                                    return $label . ($days ? " → {$days}" : ' (belum pilih hari)');
                                } catch (\Throwable $e) {
                                    return null;
                                }
                            })
                            ->collapsible()
                            ->collapsed()
                            ->visible(fn (Forms\Get $get): bool => $get('recurring_mode') === 'weekly_flex'),

                        Forms\Components\Placeholder::make('weekly_flex_preview')
                            ->label('Preview Jadwal')
                            ->content(function (Forms\Get $get) {
                                try {
                                    $schedule = $get('weekly_flex_schedule') ?? [];
                                    $dayNames = [0=>'Minggu',1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu'];
                                    $total = 0;
                                    $lines = [];

                                    foreach ($schedule as $item) {
                                        if (empty($item['week_of']) || empty($item['days_of_week'])) {
                                            if (!empty($item['week_of'])) {
                                                $ws = Carbon::parse($item['week_of'])->startOfWeek(Carbon::MONDAY);
                                                $we = $ws->copy()->endOfWeek(Carbon::SUNDAY);
                                                $lines[] = '⏸️ ' . $ws->format('d M') . ' – ' . $we->format('d M Y') . ' — *belum pilih hari*';
                                            }
                                            continue;
                                        }
                                        $weekStart = Carbon::parse($item['week_of'])->startOfWeek(Carbon::MONDAY);
                                        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
                                        $count = 0;
                                        $cur = $weekStart->copy();
                                        while ($cur->lte($weekEnd)) {
                                            if (in_array((int)$cur->dayOfWeek, array_map('intval', $item['days_of_week']))
                                                && $cur->gte(now()->startOfDay())) {
                                                $count++;
                                            }
                                            $cur->addDay();
                                        }
                                        $total += $count;
                                        $dayStr = collect($item['days_of_week'])->map(fn($d) => $dayNames[(int)$d] ?? '?')->join(', ');
                                        $weekLabel = $weekStart->format('d M') . ' – ' . $weekEnd->format('d M Y');
                                        $lines[] = "📆 **{$weekLabel}** — {$dayStr} ({$count}x)";
                                    }

                                    if (empty($lines)) return '1️⃣ Pilih rentang tanggal → 2️⃣ Klik "Buat Minggu Otomatis" → 3️⃣ Centang hari tiap minggu';
                                    return implode("\n\n", $lines) . "\n\n✅ Total: **{$total} booking**";
                                } catch (\Throwable $e) {
                                    return 'Lengkapi data untuk melihat preview';
                                }
                            })
                            ->visible(fn (Forms\Get $get): bool => $get('recurring_mode') === 'weekly_flex'),

                        // ========================================
                        // MODE 2: BULANAN PER HARI
                        // ========================================
                        Forms\Components\Repeater::make('monthly_schedule')
                            ->label('Jadwal per Bulan')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('month_start')
                                            ->label('Bulan')
                                            ->native(false)
                                            ->required()
                                            ->default(now()->startOfMonth())
                                            ->minDate(now()->startOfMonth())
                                            ->displayFormat('F Y')
                                            ->helperText('Pilih bulan yang akan dijadwalkan'),

                                        Forms\Components\CheckboxList::make('days_of_week')
                                            ->label('Hari Main di Bulan Ini')
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
                                            ->helperText('Semua hari ini dalam sebulan penuh akan dibooking'),
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('+ Tambah Bulan')
                            ->reorderable(false)
                            ->itemLabel(function (array $state): ?string {
                                try {
                                    if (empty($state['month_start'])) return null;
                                    $dayNames = [0=>'Min',1=>'Sen',2=>'Sel',3=>'Rab',4=>'Kam',5=>'Jum',6=>'Sab'];
                                    $days = collect($state['days_of_week'] ?? [])
                                        ->map(fn($d) => $dayNames[(int)$d] ?? '')
                                        ->filter()->join(', ');
                                    $month = Carbon::parse($state['month_start'])->locale('id')->isoFormat('MMMM Y');
                                    return $month . ($days ? " → {$days}" : '');
                                } catch (\Throwable $e) {
                                    return null;
                                }
                            })
                            ->collapsible()
                            ->required()
                            ->minItems(1)
                            ->visible(fn (Forms\Get $get): bool => $get('recurring_mode') === 'monthly_day'),

                        Forms\Components\Placeholder::make('monthly_day_preview')
                            ->label('Preview Jadwal')
                            ->content(function (Forms\Get $get) {
                                try {
                                    $schedule = $get('monthly_schedule') ?? [];
                                    $dayNames = [0=>'Minggu',1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu'];
                                    $total = 0;
                                    $lines = [];

                                    foreach ($schedule as $item) {
                                        if (empty($item['month_start']) || empty($item['days_of_week'])) continue;
                                        $start = Carbon::parse($item['month_start'])->startOfMonth();
                                        $end   = $start->copy()->endOfMonth();
                                        $monthName = $start->locale('id')->isoFormat('MMMM Y');
                                        $count = 0;
                                        $cur = $start->copy();
                                        while ($cur->lte($end)) {
                                            if (in_array((int)$cur->dayOfWeek, array_map('intval', $item['days_of_week']))) $count++;
                                            $cur->addDay();
                                        }
                                        $total += $count;
                                        $dayStr = collect($item['days_of_week'])->map(fn($d) => $dayNames[(int)$d] ?? '?')->join(', ');
                                        $lines[] = "📅 **{$monthName}** — {$dayStr} ({$count}x)";
                                    }

                                    if (empty($lines)) return 'Tambahkan minimal 1 bulan dengan hari yang dipilih';
                                    return implode("\n\n", $lines) . "\n\n✅ Total: **{$total} booking**";
                                } catch (\Throwable $e) {
                                    return 'Lengkapi data bulan untuk melihat preview';
                                }
                            })
                            ->visible(fn (Forms\Get $get): bool => $get('recurring_mode') === 'monthly_day'),

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
                                    ? Carbon::parse($state['date'])->locale('id')->isoFormat('dddd, D MMMM Y')
                                    : null
                            )
                            ->columns(1)
                            ->required()
                            ->minItems(1)
                            ->visible(fn (Forms\Get $get): bool => $get('recurring_mode') === 'custom'),

                        Forms\Components\Placeholder::make('custom_preview')
                            ->label('Ringkasan Booking')
                            ->content(function (Forms\Get $get) {
                                try {
                                    $dates = $get('selected_dates') ?? [];
                                    $validDates = array_filter($dates, fn($d) => !empty($d['date']));

                                    if (empty($validDates)) {
                                        return 'Belum ada tanggal yang dipilih';
                                    }

                                    $count = count($validDates);
                                    $sortedDates = collect($validDates)->pluck('date')->sort()->values();
                                    $firstDate = Carbon::parse($sortedDates->first())->locale('id')->isoFormat('dddd, D MMM Y');
                                    $lastDate  = Carbon::parse($sortedDates->last())->locale('id')->isoFormat('dddd, D MMM Y');

                                    return "📅 Total: **{$count} booking**\n\n📆 Periode: {$firstDate} — {$lastDate}";
                                } catch (\Throwable $e) {
                                    return 'Pilih tanggal untuk melihat preview';
                                }
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
                    ->sortable()
                    ->formatStateUsing(fn ($state) =>
                        $state
                            ? \Carbon\Carbon::parse($state)->locale('id')->isoFormat('dddd, D MMM Y')
                            : '-'
                    ),

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