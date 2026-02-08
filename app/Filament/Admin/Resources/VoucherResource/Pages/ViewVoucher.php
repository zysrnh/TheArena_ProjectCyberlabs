<?php

namespace App\Filament\Admin\Resources\VoucherResource\Pages;

use App\Filament\Admin\Resources\VoucherResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewVoucher extends ViewRecord
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Voucher')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('code')
                                    ->label('Kode Voucher')
                                    ->copyable()
                                    ->copyMessage('Kode berhasil disalin!')
                                    ->copyMessageDuration(1500)
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('primary'),

                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                Infolists\Components\TextEntry::make('usage_stats')
                                    ->label('Penggunaan')
                                    ->formatStateUsing(function ($record) {
                                        if ($record->usage_limit === null) {
                                            return "{$record->used_count} / Unlimited";
                                        }
                                        return "{$record->used_count} / {$record->usage_limit}";
                                    })
                                    ->badge()
                                    ->color(fn ($record) => 
                                        $record->usage_limit && $record->used_count >= $record->usage_limit 
                                            ? 'danger' 
                                            : 'success'
                                    ),
                            ]),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada deskripsi'),
                    ]),

                Infolists\Components\Section::make('Detail Diskon')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('discount_type')
                                    ->label('Tipe Diskon')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'percentage' => 'Persentase',
                                        'fixed' => 'Nominal',
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'percentage' => 'success',
                                        'fixed' => 'info',
                                    }),

                                Infolists\Components\TextEntry::make('discount_value')
                                    ->label('Nilai Diskon')
                                    ->formatStateUsing(function ($record) {
                                        if ($record->discount_type === 'percentage') {
                                            return $record->discount_value . '%';
                                        }
                                        return 'Rp ' . number_format($record->discount_value, 0, ',', '.');
                                    })
                                    ->weight('bold')
                                    ->color('warning'),

                                Infolists\Components\TextEntry::make('max_discount')
                                    ->label('Max Diskon')
                                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                                    ->placeholder('-'),
                            ]),

                        Infolists\Components\TextEntry::make('min_purchase')
                            ->label('Minimal Pembelian')
                            ->formatStateUsing(fn ($state) => $state > 0 ? 'Rp ' . number_format($state, 0, ',', '.') : 'Tidak ada minimum')
                            ->color(fn ($state) => $state > 0 ? 'info' : 'gray'),
                    ]),

                Infolists\Components\Section::make('Periode & Batasan')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('valid_from')
                                    ->label('Berlaku Dari')
                                    ->dateTime('d M Y, H:i')
                                    ->placeholder('Langsung berlaku')
                                    ->icon('heroicon-o-calendar'),

                                Infolists\Components\TextEntry::make('valid_until')
                                    ->label('Berlaku Sampai')
                                    ->dateTime('d M Y, H:i')
                                    ->placeholder('Tidak ada batas waktu')
                                    ->icon('heroicon-o-calendar')
                                    ->color(fn ($record) => 
                                        $record->valid_until && $record->valid_until->isPast() 
                                            ? 'danger' 
                                            : 'success'
                                    )
                                    ->badge(),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('usage_limit')
                                    ->label('Batas Penggunaan')
                                    ->formatStateUsing(fn ($state) => $state ?? 'Unlimited')
                                    ->icon('heroicon-o-users'),

                                Infolists\Components\TextEntry::make('used_count')
                                    ->label('Sudah Digunakan')
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-o-check-badge'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Statistik')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_discount_given')
                                    ->label('Total Diskon Diberikan')
                                    ->state(function ($record) {
                                        $total = $record->usages()->sum('discount_amount');
                                        return 'Rp ' . number_format($total, 0, ',', '.');
                                    })
                                    ->icon('heroicon-o-banknotes')
                                    ->color('success')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('total_bookings')
                                    ->label('Total Booking')
                                    ->state(fn ($record) => $record->usages()->count())
                                    ->icon('heroicon-o-shopping-cart')
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('remaining_uses')
                                    ->label('Sisa Penggunaan')
                                    ->state(function ($record) {
                                        if ($record->usage_limit === null) {
                                            return 'Unlimited';
                                        }
                                        $remaining = $record->usage_limit - $record->used_count;
                                        return max(0, $remaining);
                                    })
                                    ->icon('heroicon-o-ticket')
                                    ->badge()
                                    ->color(fn ($record) => 
                                        $record->usage_limit && ($record->usage_limit - $record->used_count) <= 5 
                                            ? 'warning' 
                                            : 'success'
                                    ),
                            ]),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Informasi Sistem')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Dibuat Pada')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-o-clock'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Terakhir Diubah')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-o-clock')
                                    ->since(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}