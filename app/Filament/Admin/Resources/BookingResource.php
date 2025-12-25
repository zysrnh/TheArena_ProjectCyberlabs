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
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'Booking Lapangan';

    protected static ?string $navigationGroup = 'Booking Management';
    
    protected static ?string $pluralLabel = 'Booking Lapangan';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        static::autoCompleteExpiredBookings();
        
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Booking')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->required()
                            ->preload(),
                        
                        Forms\Components\DatePicker::make('booking_date')
                            ->label('Tanggal Booking')
                            ->required()
                            ->native(false)
                            ->live(), // ✅ TAMBAH: buat trigger price calculation
                        
                        Forms\Components\Select::make('venue_type')
                            ->label('Pilih Venue')
                            ->options([
                                'cibadak_a' => 'Cibadak A (Indoor Premium)',
                                'cibadak_b' => 'Cibadak B (Outdoor)',
                                'pvj' => 'PVJ Mall (Indoor)',
                                'urban' => 'Urban (Ultra Modern)',
                            ])
                            ->required()
                            ->live(), // ✅ TAMBAH: buat trigger price calculation
                    ])->columns(3),
                
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
                                    ->live() // ✅ TAMBAH: trigger price update
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        // ✅ Auto-calculate price
                                        $venueType = $get('../../venue_type');
                                        $bookingDate = $get('../../booking_date');
                                        
                                        if ($state && $venueType && $bookingDate) {
                                            $price = static::calculatePrice($venueType, $bookingDate, $state);
                                            $set('price', $price);
                                        }
                                    }),
                                
                                Forms\Components\TextInput::make('duration')
                                    ->label('Durasi (Menit)')
                                    ->numeric()
                                    ->default(120)
                                    ->required()
                                    ->disabled(), // ✅ Disabled karena fixed 120 menit
                                
                                Forms\Components\TextInput::make('price')
                                    ->label('Harga (Auto)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->disabled() // ✅ DISABLED: otomatis dari calculation
                                    ->dehydrated() // ✅ PENTING: biar tetap ke-save meski disabled
                                    ->helperText('Harga otomatis berdasarkan venue, hari, dan waktu'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->collapsible()
                            ->live() // ✅ TAMBAH: buat update total_price
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // ✅ Auto-calculate total price
                                if (is_array($state)) {
                                    $total = array_sum(array_column($state, 'price'));
                                    $set('total_price', $total);
                                }
                            }),
                        
                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Harga (Auto)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->disabled() // ✅ DISABLED: otomatis dari sum time_slots
                            ->dehydrated() // ✅ PENTING: biar tetap ke-save
                            ->helperText('Total otomatis dari semua time slots'),
                        
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                                'completed' => 'Completed',
                            ])
                            ->required()
                            ->default('pending'),
                        
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Sudah Dibayar')
                            ->default(false)
                            ->inline(false),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        static::autoCompleteExpiredBookings();
        
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
                    ->color(fn (string $state): string => match ($state) {
                        'cibadak_a' => 'success',
                        'cibadak_b' => 'info',
                        'pvj' => 'warning',
                        'urban' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
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
                        
                        $times = [];
                        foreach ($slots as $slot) {
                            if (isset($slot['time'])) {
                                $times[] = $slot['time'];
                            }
                        }
                        
                        if (empty($times)) {
                            return '-';
                        }
                        
                        if (count($times) > 1) {
                            return $times[0] . ' (+' . (count($times) - 1) . ')';
                        }
                        
                        return $times[0];
                    })
                    ->tooltip(function ($record) {
                        $slots = $record->time_slots;
                        
                        if (!is_array($slots) || empty($slots)) {
                            return null;
                        }
                        
                        $times = [];
                        foreach ($slots as $slot) {
                            if (isset($slot['time'])) {
                                $times[] = $slot['time'];
                            }
                        }
                        
                        if (count($times) > 1) {
                            return 'Semua slot: ' . implode(', ', $times);
                        }
                        
                        return null;
                    })
                    ->searchable(false)
                    ->sortable(false),
                
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                
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
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                
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
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('venue_type')
                    ->label('Pilih Venue')
                    ->options([
                        'cibadak_a' => 'Cibadak A',
                        'cibadak_b' => 'Cibadak B',
                        'pvj' => 'PVJ',
                        'urban' => 'Urban',
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
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('booking_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('booking_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Booking $record): string => 'Detail Booking #' . $record->id)
                    ->modalContent(fn (Booking $record): \Illuminate\View\View => view(
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
                            'is_paid' => true,
                            'status' => 'confirmed',
                        ]);
                        
                        Notification::make()
                            ->title('Pembayaran Dikonfirmasi')
                            ->success()
                            ->body('Booking telah dikonfirmasi dan ditandai sebagai sudah dibayar.')
                            ->send();
                    })
                    ->visible(fn (Booking $record): bool => !$record->is_paid),
                
                Tables\Actions\Action::make('toggle_payment')
                    ->label(fn (Booking $record): string => $record->is_paid ? 'Batalkan Bayar' : 'Tandai Bayar')
                    ->icon(fn (Booking $record): string => $record->is_paid ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Booking $record): string => $record->is_paid ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Booking $record) {
                        $isPaid = !$record->is_paid;
                        $record->update(['is_paid' => $isPaid]);
                        
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
                                    'is_paid' => true,
                                    'status' => 'confirmed',
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
                                    'pending' => 'Pending',
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

    /**
     * ✅ DYNAMIC PRICING: Calculate price based on venue, date, and time
     */
    protected static function calculatePrice($venueType, $date, $timeSlot): int
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [0, 6]);

        preg_match('/^(\d{2})\./', $timeSlot, $matches);
        $startHour = isset($matches[1]) ? (int)$matches[1] : 0;

        if ($venueType === 'pvj') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 16) {
                    return 700000;
                } elseif ($startHour >= 16 && $startHour < 20) {
                    return 700000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 500000;
                }
            } else {
                if ($startHour >= 6 && $startHour < 16) {
                    return 350000;
                } elseif ($startHour >= 16 && $startHour < 20) {
                    return 700000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 500000;
                }
            }
        }

        if ($venueType === 'cibadak_a') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 20) {
                    return 700000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 500000;
                }
            } else {
                if ($startHour >= 6 && $startHour < 16) {
                    return 350000;
                } elseif ($startHour >= 16 && $startHour < 24) {
                    return 700000;
                }
            }
        }

        if ($venueType === 'cibadak_b') {
            if ($isWeekend) {
                if ($startHour >= 6 && $startHour < 20) {
                    return 550000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 450000;
                }
            } else {
                if ($startHour >= 6 && $startHour < 16) {
                    return 300000;
                } elseif ($startHour >= 16 && $startHour < 20) {
                    return 550000;
                } elseif ($startHour >= 20 && $startHour < 24) {
                    return 450000;
                }
            }
        }

        if ($venueType === 'urban') {
            if ($isWeekend) {
                return 550000;
            } else {
                if ($startHour >= 6 && $startHour < 16) {
                    return 300000;
                } elseif ($startHour >= 16 && $startHour < 24) {
                    return 550000;
                }
            }
        }

        return 350000;
    }

    protected static function autoCompleteExpiredBookings(): void
    {
        try {
            $bookings = static::getModel()::where('status', 'confirmed')
                ->where('booking_date', '<', Carbon::today())
                ->get();

            $completedCount = 0;

            foreach ($bookings as $booking) {
                if (static::isBookingExpired($booking)) {
                    $booking->update(['status' => 'completed']);
                    $completedCount++;
                    
                    \Log::info("Booking #{$booking->id} auto-completed", [
                        'client' => $booking->client->name,
                        'date' => $booking->booking_date,
                        'venue' => $booking->venue_type,
                    ]);
                }
            }

            if ($completedCount > 0) {
                Notification::make()
                    ->title('Auto-Complete Booking')
                    ->success()
                    ->body("{$completedCount} booking telah otomatis diselesaikan.")
                    ->send();
            }
        } catch (\Exception $e) {
            \Log::error('Error auto-completing bookings: ' . $e->getMessage());
        }
    }

    protected static function isBookingExpired(Booking $booking): bool
    {
        $bookingDate = Carbon::parse($booking->booking_date);
        
        if ($bookingDate->lt(Carbon::today())) {
            return true;
        }

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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
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