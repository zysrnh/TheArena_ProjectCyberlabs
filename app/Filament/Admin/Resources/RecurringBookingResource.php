<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RecurringBookingResource\Pages;
use App\Models\Booking;
use App\Models\BookedTimeSlot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class RecurringBookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $navigationLabel = 'Booking Rutin (Bulanan)';

    protected static ?string $navigationGroup = 'Booking Management';
    
    protected static ?string $pluralLabel = 'Booking Rutin';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Customer')
                    ->description('Pilih client dari database atau input nama manual untuk booking offline')
                    ->schema([
                        Forms\Components\Radio::make('customer_type')
                            ->label('Tipe Customer')
                            ->options([
                                'existing' => 'Pilih dari Database Client',
                                'manual' => 'Input Manual (Walk-in/Offline)',
                            ])
                            ->default('existing')
                            ->live()
                            ->required()
                            ->inline(),
                        
                        Forms\Components\Select::make('client_id')
                            ->label('Pilih Client')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('customer_type') === 'existing')
                            ->required(fn (Forms\Get $get) => $get('customer_type') === 'existing'),
                        
                        Forms\Components\TextInput::make('customer_name_manual')
                            ->label('Nama Customer')
                            ->placeholder('Contoh: Tim Basket Garuda')
                            ->visible(fn (Forms\Get $get) => $get('customer_type') === 'manual')
                            ->required(fn (Forms\Get $get) => $get('customer_type') === 'manual')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('customer_phone_manual')
                            ->label('No. Telepon (Opsional)')
                            ->placeholder('08123456789')
                            ->tel()
                            ->visible(fn (Forms\Get $get) => $get('customer_type') === 'manual')
                            ->maxLength(20),
                    ])->columns(1),
                
                Forms\Components\Section::make('Pengaturan Recurring')
                    ->description('Set jadwal rutin untuk beberapa minggu ke depan')
                    ->schema([
                        Forms\Components\Select::make('recurring_month')
                            ->label('Periode Bulan')
                            ->options(function () {
                                $months = [];
                                $currentYear = Carbon::now()->year;
                                
                                // Generate 12 bulan dari bulan sekarang
                                for ($i = 0; $i < 12; $i++) {
                                    $date = Carbon::now()->addMonths($i);
                                    $months[$date->format('Y-m')] = $date->format('F Y');
                                }
                                return $months;
                            })
                            ->default(Carbon::now()->format('Y-m'))
                            ->required()
                            ->live(),
                        
                        Forms\Components\CheckboxList::make('recurring_days')
                            ->label('Hari dalam Seminggu')
                            ->options([
                                '0' => 'Minggu',
                                '1' => 'Senin',
                                '2' => 'Selasa',
                                '3' => 'Rabu',
                                '4' => 'Kamis',
                                '5' => 'Jumat',
                                '6' => 'Sabtu',
                            ])
                            ->required()
                            ->columns(4)
                            ->gridDirection('row')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state && $get('recurring_month')) {
                                    $dates = self::calculateRecurringDates($get('recurring_month'), $state);
                                    $set('preview_dates', implode(', ', array_map(function($date) {
                                        return Carbon::parse($date)->format('d M');
                                    }, $dates)));
                                    $set('total_bookings', count($dates));
                                }
                            }),
                        
                        Forms\Components\Placeholder::make('preview_dates')
                            ->label('Preview Tanggal yang Akan Dibuat')
                            ->content(fn (Forms\Get $get) => $get('preview_dates') ?: '-')
                            ->visible(fn (Forms\Get $get) => !empty($get('recurring_days'))),
                        
                        Forms\Components\Placeholder::make('total_bookings')
                            ->label('Total Booking yang Akan Dibuat')
                            ->content(fn (Forms\Get $get) => ($get('total_bookings') ?? 0) . ' booking')
                            ->visible(fn (Forms\Get $get) => !empty($get('recurring_days'))),
                    ])->columns(2),
                
                Forms\Components\Section::make('Detail Venue & Waktu')
                    ->schema([
                        Forms\Components\Select::make('venue_type')
                            ->label('Pilih Venue')
                            ->options([
                                'cibadak_a' => 'Cibadak A (Indoor Premium) - Rp 350.000',
                                'cibadak_b' => 'Cibadak B (Outdoor) - Rp 300.000',
                                'pvj' => 'PVJ Mall (Indoor) - Rp 350.000',
                                'urban' => 'Urban (Ultra Modern) - Rp 400.000',
                            ])
                            ->required()
                            ->live(),
                        
                        Forms\Components\CheckboxList::make('time_slots_selection')
                            ->label('Pilih Time Slot')
                            ->options([
                                '06.00 - 08.00' => '06.00 - 08.00 (Rp 350.000)',
                                '08.00 - 10.00' => '08.00 - 10.00 (Rp 350.000)',
                                '10.00 - 12.00' => '10.00 - 12.00 (Rp 350.000)',
                                '12.00 - 14.00' => '12.00 - 14.00 (Rp 350.000)',
                                '14.00 - 16.00' => '14.00 - 16.00 (Rp 350.000)',
                                '16.00 - 18.00' => '16.00 - 18.00 (Rp 350.000)',
                                '18.00 - 20.00' => '18.00 - 20.00 (Rp 350.000)',
                                '20.00 - 22.00' => '20.00 - 22.00 (Rp 350.000)',
                                '22.00 - 00.00' => '22.00 - 00.00 (Rp 350.000)',
                            ])
                            ->required()
                            ->columns(3)
                            ->gridDirection('row'),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Tambahan')
                            ->placeholder('Contoh: Booking rutin untuk latihan tim')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status Booking')
                            ->options([
                                'pending' => 'Pending (Belum Bayar)',
                                'confirmed' => 'Confirmed (Sudah Bayar)',
                            ])
                            ->default('confirmed')
                            ->required()
                            ->helperText('Pilih Confirmed jika sudah dibayar lunas'),
                        
                        Forms\Components\Select::make('payment_status')
                            ->label('Status Pembayaran')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                            ])
                            ->default('paid')
                            ->required(),
                        
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Tandai Sudah Dibayar')
                            ->default(true)
                            ->inline(false),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Filter hanya booking yang punya notes recurring atau created by admin
                $query->where(function ($q) {
                    $q->whereNotNull('notes')
                      ->where('notes', 'like', '%rutin%')
                      ->orWhere('notes', 'like', '%recurring%')
                      ->orWhere('notes', 'like', '%bulanan%');
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->default(fn ($record) => $record->notes ? 
                        (preg_match('/Customer: (.+?)(\||$)/i', $record->notes, $matches) ? $matches[1] : 'Manual Input') 
                        : '-'
                    ),
                
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
                        if (!is_array($slots) || empty($slots)) return '-';
                        
                        $times = array_column($slots, 'time');
                        if (count($times) > 1) {
                            return $times[0] . ' (+' . (count($times) - 1) . ')';
                        }
                        return $times[0] ?? '-';
                    })
                    ->tooltip(function ($record) {
                        $slots = $record->time_slots;
                        if (!is_array($slots) || empty($slots)) return null;
                        
                        $times = array_column($slots, 'time');
                        if (count($times) > 1) {
                            return 'Semua slot: ' . implode(', ', $times);
                        }
                        return null;
                    }),
                
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
                    ->falseColor('danger'),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('venue_type')
                    ->label('Venue')
                    ->options([
                        'cibadak_a' => 'Cibadak A',
                        'cibadak_b' => 'Cibadak B',
                        'pvj' => 'PVJ',
                        'urban' => 'Urban',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->multiple(),
                
                Tables\Filters\Filter::make('booking_date')
                    ->form([
                        Forms\Components\DatePicker::make('month')
                            ->label('Bulan')
                            ->displayFormat('F Y')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['month'],
                            fn (Builder $query, $date): Builder => 
                                $query->whereYear('booking_date', Carbon::parse($date)->year)
                                      ->whereMonth('booking_date', Carbon::parse($date)->month)
                        );
                    }),
            ])
            ->actions([
                // Modal View Action - FIXED!
                Tables\Actions\Action::make('view_details')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Booking $record): string => 'Detail Booking Rutin #' . $record->id)
                    ->modalContent(fn (Booking $record): \Illuminate\View\View => view(
                        'filament.admin.resources.recurring-booking.view-modal',
                        ['record' => $record]
                    ))
                    ->modalWidth('2xl')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'pending'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_confirm')
                        ->label('Konfirmasi Pembayaran')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update([
                                'status' => 'confirmed',
                                'payment_status' => 'paid',
                                'is_paid' => true,
                            ]);
                            
                            Notification::make()
                                ->title('Berhasil!')
                                ->success()
                                ->body(count($records) . ' booking telah dikonfirmasi.')
                                ->send();
                        }),
                    
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

    /**
     * Helper: Hitung tanggal recurring berdasarkan bulan dan hari
     */
    protected static function calculateRecurringDates(string $month, array $days): array
    {
        $dates = [];
        $startDate = Carbon::parse($month . '-01');
        $endDate = $startDate->copy()->endOfMonth();
        
        $current = $startDate->copy();
        while ($current <= $endDate) {
            if (in_array($current->dayOfWeek, $days)) {
                $dates[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }
        
        return $dates;
    }
}