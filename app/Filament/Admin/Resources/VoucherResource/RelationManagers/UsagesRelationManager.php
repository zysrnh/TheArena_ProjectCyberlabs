<?php

namespace App\Filament\Admin\Resources\VoucherResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Colors\Color;

class UsagesRelationManager extends RelationManager
{
    protected static string $relationship = 'usages';

    protected static ?string $title = 'Riwayat Penggunaan Voucher';

    protected static ?string $modelLabel = 'Penggunaan';

    protected static ?string $pluralModelLabel = 'Riwayat Penggunaan';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('client_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->client?->email)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('client.phone')
                    ->label('No. HP')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->copyMessage('No. HP berhasil disalin!'),

                Tables\Columns\TextColumn::make('booking.bill_no')
                    ->label('No. Booking')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->booking_id 
                        ? route('filament.admin.resources.bookings.view', $record->booking_id) 
                        : null)
                    ->color('primary')
                    ->weight('medium')
                    ->description(fn ($record) => $record->booking 
                        ? 'Tanggal: ' . $record->booking->booking_date->format('d M Y')
                        : null),

                Tables\Columns\TextColumn::make('booking.venue_type')
                    ->label('Venue')
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'pvj' => 'The Arena PVJ',
                            'cibadak_a' => 'The Arena Cibadak A',
                            'cibadak_b' => 'The Arena Cibadak B',
                            'urban' => 'The Arena Urban',
                            default => $state,
                        };
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Nilai Diskon')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                            ->label('Total Diskon'),
                    ]),

                Tables\Columns\TextColumn::make('booking.total_price')
                    ->label('Total Booking')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('booking.payment_status')
                    ->label('Status Bayar')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'expired' => 'gray',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Lunas',
                        'pending' => 'Pending',
                        'failed' => 'Gagal',
                        'expired' => 'Expired',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Digunakan Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans())
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options([
                        'paid' => 'Lunas',
                        'pending' => 'Pending',
                        'failed' => 'Gagal',
                        'expired' => 'Expired',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            return $query->whereHas('booking', function ($q) use ($data) {
                                $q->where('payment_status', $data['value']);
                            });
                        }
                        return $query;
                    }),

                Tables\Filters\SelectFilter::make('venue_type')
                    ->label('Venue')
                    ->options([
                        'pvj' => 'The Arena PVJ',
                        'cibadak_a' => 'The Arena Cibadak A',
                        'cibadak_b' => 'The Arena Cibadak B',
                        'urban' => 'The Arena Urban',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value'])) {
                            return $query->whereHas('booking', function ($q) use ($data) {
                                $q->where('venue_type', $data['value']);
                            });
                        }
                        return $query;
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['dari'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Dari ' . \Carbon\Carbon::parse($data['dari'])->format('d M Y'))
                                ->removeField('dari');
                        }

                        if ($data['sampai'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Sampai ' . \Carbon\Carbon::parse($data['sampai'])->format('d M Y'))
                                ->removeField('sampai');
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export Data')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        // Implement export functionality here
                        \Filament\Notifications\Notification::make()
                            ->title('Export sedang diproses')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detail Penggunaan Voucher')
                    ->modalContent(function ($record) {
                        return view('filament.admin.resources.voucher-resource.view-usage', [
                            'record' => $record,
                        ]);
                    }),

                Tables\Actions\Action::make('view_booking')
                    ->label('Lihat Booking')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => $record->booking_id 
                        ? route('filament.admin.resources.bookings.view', $record->booking_id) 
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->booking_id !== null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // No delete action - usage history should be preserved
                ]),
            ])
            ->emptyStateHeading('Belum ada yang menggunakan voucher ini')
            ->emptyStateDescription('Voucher ini belum pernah digunakan oleh customer.')
            ->emptyStateIcon('heroicon-o-ticket');
    }
}