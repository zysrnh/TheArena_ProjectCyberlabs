<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Booking Lapangan';

    protected static ?string $navigationGroup = 'Booking Management';

    protected static ?string $pluralLabel = 'Booking Lapangan';

    protected static ?int $navigationSort = 3;

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make('Kalender Booking')
                ->group(static::getNavigationGroup())
                ->icon('heroicon-o-calendar')
                ->sort(2)
                ->url(static::getUrl('calendar'))
                ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName() . '.calendar')),

            NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->sort(3)
                ->url(static::getUrl())
                ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName() . '.*') && !request()->routeIs(static::getRouteBaseName() . '.calendar')),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Customer')
                    ->schema([
                        Forms\Components\Radio::make('customer_type')
                            ->label('Tipe Customer')
                            ->options([
                                'existing' => 'Customer Terdaftar',
                                'manual'   => 'Input Manual (Guest/Walk-in)',
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

                Forms\Components\Section::make('Informasi Booking')
                    ->schema([
                        Forms\Components\DatePicker::make('booking_date')
                            ->label('Tanggal Booking')
                            ->required()
                            ->native(false)
                            ->live(),

                        Forms\Components\Select::make('venue_type')
                            ->label('Pilih Venue')
                            ->options([
                                'cibadak_a' => 'Cibadak A',
                                'cibadak_b' => 'Cibadak B',
                                'pvj'       => 'PVJ Mall',
                                'urban'     => 'Urban',
                            ])
                            ->required()
                            ->live(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Booking')
                    ->description('⚠️ Harga akan otomatis disesuaikan berdasarkan venue, hari (weekday/weekend), dan waktu')
                    ->schema([
                        Forms\Components\Repeater::make('time_slots')
                            ->label('Slot Waktu')
                            ->schema([
                                Forms\Components\Select::make('time')
                                    ->label('Waktu')
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
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $venueType = $get('../../venue_type');
                                        $bookingDate = $get('../../booking_date');

                                        if ($state && $venueType && $bookingDate) {
                                            $price = static::calculatePrice($venueType, $bookingDate, $state);
                                            $set('price', $price);
                                            static::updateTotalPrice($set, $get);
                                        }
                                    }),

                                Forms\Components\TextInput::make('duration')
                                    ->label('Durasi (Menit)')
                                    ->numeric()
                                    ->default(120)
                                    ->required()
                                    ->disabled(),

                                Forms\Components\TextInput::make('price')
                                    ->label('Harga (Auto)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Harga otomatis berdasarkan venue, hari, dan waktu'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->collapsible()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                static::updateTotalPrice($set, $get);
                            })
                            ->addActionLabel('Add to slot Waktu')
                            ->deleteAction(
                                fn ($action) => $action->after(fn (Forms\Set $set, Forms\Get $get) => static::updateTotalPrice($set, $get))
                            ),

                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Harga (Auto)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Total otomatis dari semua time slots'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending'   => 'Pending',
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                                'completed' => 'Completed',
                            ])
                            ->required()
                            ->default('confirmed')
                            ->live(),

                        Forms\Components\Toggle::make('is_paid')
                            ->label('Sudah Dibayar')
                            ->default(true)
                            ->inline(false)
                            ->live(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Contoh: Booking manual oleh admin'),
                    ])->columns(2),
            ]);
    }

    protected static function updateTotalPrice(Forms\Set $set, Forms\Get $get): void
    {
        $timeSlots = $get('time_slots') ?? [];

        $total = collect($timeSlots)
            ->sum(fn ($slot) => $slot['price'] ?? 0);

        $set('total_price', $total);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

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
                        'pvj'       => 'warning',
                        'urban'     => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'cibadak_a' => 'Cibadak A',
                        'cibadak_b' => 'Cibadak B',
                        'pvj'       => 'PVJ',
                        'urban'     => 'Urban',
                        default     => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('time_slots')
                    ->label('Waktu')
                    ->formatStateUsing(function ($record) {
                        $slots = $record->time_slots;
                        if (!is_array($slots) || empty($slots)) return '-';
                        $times = array_column($slots, 'time');
                        if (empty($times)) return '-';
                        if (count($times) > 1) return $times[0] . ' (+' . (count($times) - 1) . ')';
                        return $times[0];
                    })
                    ->tooltip(function ($record) {
                        $slots = $record->time_slots;
                        if (!is_array($slots) || empty($slots)) return null;
                        $times = array_column($slots, 'time');
                        if (count($times) > 1) return 'Semua slot: ' . implode(', ', $times);
                        return null;
                    }),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('booking_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'manual'    => 'warning',
                        'recurring' => 'danger',
                        'paid'      => 'success',
                        'pending'   => 'gray',
                        default     => 'info',
                    })
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'manual'    => 'Manual',
                        'recurring' => 'Member',
                        'paid'      => 'Lunas',
                        'pending'   => 'Pending',
                        default     => 'Reguler',
                    }),

                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Bayar')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('booking_type')
                    ->label('Tipe Booking')
                    ->options([
                        'manual'    => 'Manual (Admin)',
                        'recurring' => 'Member',
                        'paid'      => 'Lunas',
                        'pending'   => 'Pending',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('venue_type')
                    ->label('Pilih Venue')
                    ->options([
                        'cibadak_a' => 'Cibadak A',
                        'cibadak_b' => 'Cibadak B',
                        'pvj'       => 'PVJ',
                        'urban'     => 'Urban',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Status Pembayaran')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Dibayar')
                    ->falseLabel('Belum Dibayar')
                    ->native(false),

                Tables\Filters\Filter::make('booking_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn(Builder $query, $date): Builder => $query->whereDate('booking_date', '>=', $date))
                            ->when($data['until'], fn(Builder $query, $date): Builder => $query->whereDate('booking_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn(Booking $record): string => 'Detail Booking #' . $record->id)
                    ->modalContent(fn(Booking $record): \Illuminate\View\View => view(
                        'filament.admin.resources.booking.view-modal',
                        ['record' => $record]
                    ))
                    ->modalWidth('2xl')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Tables\Actions\Action::make('confirm_payment')
                    ->label('Konfirmasi Bayar')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran')
                    ->modalDescription('Apakah Anda yakin pembayaran untuk booking ini sudah diterima?')
                    ->modalSubmitActionLabel('Ya, Konfirmasi')
                    ->action(function (Booking $record) {
                        $record->update([
                            'is_paid'        => true,
                            'payment_status' => 'paid',
                            'paid_at'        => now(),
                            'status'         => 'confirmed',
                        ]);

                        Notification::make()
                            ->title('Pembayaran Dikonfirmasi')
                            ->success()
                            ->body('Booking telah dikonfirmasi dan ditandai sebagai sudah dibayar.')
                            ->send();
                    })
                    ->visible(fn(Booking $record): bool => !$record->is_paid),

                Tables\Actions\Action::make('toggle_payment')
                    ->label(fn(Booking $record): string => $record->is_paid ? 'Batalkan Bayar' : 'Tandai Bayar')
                    ->icon(fn(Booking $record): string => $record->is_paid ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn(Booking $record): string => $record->is_paid ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Booking $record) {
                        $isPaid = !$record->is_paid;
                        $record->update([
                            'is_paid'        => $isPaid,
                            'payment_status' => $isPaid ? 'paid' : 'pending',
                            'paid_at'        => $isPaid ? now() : null,
                        ]);

                        Notification::make()
                            ->title($isPaid ? 'Ditandai Sudah Dibayar' : 'Pembayaran Dibatalkan')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_confirm_payment')
                        ->label('Konfirmasi Pembayaran')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'is_paid'        => true,
                                    'payment_status' => 'paid',
                                    'paid_at'        => now(),
                                    'status'         => 'confirmed',
                                ]);
                            });

                            Notification::make()
                                ->title('Pembayaran Dikonfirmasi')
                                ->success()
                                ->body(count($records) . ' booking telah dikonfirmasi.')
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Status Baru')
                                ->options([
                                    'pending'   => 'Pending',
                                    'confirmed' => 'Confirmed',
                                    'cancelled' => 'Cancelled',
                                    'completed' => 'Completed',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update(['status' => $data['status']]);

                            Notification::make()
                                ->title('Status Diupdate')
                                ->success()
                                ->body(count($records) . ' booking telah diupdate ke status ' . ucfirst($data['status']))
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('booking_date', 'desc')
            ->poll('30s');
    }

    public static function calculatePrice($venueType, $date, $timeSlot): int
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [0, 6]);

        preg_match('/^(\d{2})\./', $timeSlot, $matches);
        $startHour = isset($matches[1]) ? (int)$matches[1] : 0;

        if ($venueType === 'pvj') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 16) return 700000;
                elseif ($startHour >= 16 && $startHour < 20) return 700000;
                elseif ($startHour >= 20 && $startHour < 24) return 500000;
            } else {
                if ($startHour >= 6 && $startHour < 16) return 350000;
                elseif ($startHour >= 16 && $startHour < 20) return 700000;
                elseif ($startHour >= 20 && $startHour < 24) return 500000;
            }
        }

        if ($venueType === 'cibadak_a') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 20) return 700000;
                elseif ($startHour >= 20 && $startHour < 24) return 500000;
            } else {
                if ($startHour >= 6 && $startHour < 16) return 350000;
                elseif ($startHour >= 16 && $startHour < 24) return 700000;
            }
        }

        if ($venueType === 'cibadak_b') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 20) return 550000;
                elseif ($startHour >= 20 && $startHour < 24) return 450000;
            } else {
                if ($startHour >= 6 && $startHour < 16) return 300000;
                elseif ($startHour >= 16 && $startHour < 20) return 550000;
                elseif ($startHour >= 20 && $startHour < 24) return 450000;
            }
        }

        if ($venueType === 'urban') {
            if ($isWeekend) return 550000;
            else {
                if ($startHour >= 6 && $startHour < 16) return 300000;
                elseif ($startHour >= 16 && $startHour < 24) return 550000;
            }
        }

        return 350000;
    }

    public static function autoCompleteExpiredBookings(): void
    {
        try {
            $bookings = static::getModel()::where('status', 'confirmed')
                ->where('is_paid', true)
                ->where('payment_status', 'paid')
                ->where('booking_date', '<', Carbon::today())
                ->get();

            $completedCount = 0;

            foreach ($bookings as $booking) {
                if (static::isBookingExpired($booking)) {
                    $booking->update(['status' => 'completed']);
                    $completedCount++;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error auto-completing bookings: ' . $e->getMessage());
        }
    }

    protected static function isBookingExpired(Booking $booking): bool
    {
        $bookingDate = Carbon::parse($booking->booking_date);

        if ($bookingDate->lt(Carbon::today())) return true;

        if ($bookingDate->isToday() && !empty($booking->time_slots)) {
            $lastSlot = end($booking->time_slots);
            if (isset($lastSlot['time'])) {
                $timeRange = explode(' - ', $lastSlot['time']);
                $endTime = trim(end($timeRange));
                try {
                    $endDateTime = Carbon::parse($booking->booking_date . ' ' . $endTime);
                    return Carbon::now()->gt($endDateTime);
                } catch (\Exception $e) {
                    return $bookingDate->lt(Carbon::today());
                }
            }
        }

        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'    => Pages\ListBookings::route('/'),
            'create'   => Pages\CreateBooking::route('/create'),
            'edit'     => Pages\EditBooking::route('/{record}/edit'),
            'calendar' => Pages\CalendarBooking::route('/calendar'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}