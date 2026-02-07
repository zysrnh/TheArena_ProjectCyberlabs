<?php

namespace App\Filament\Admin\Resources\VoucherResource\Pages;

use App\Filament\Admin\Resources\VoucherResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListVouchers extends ListRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Voucher Baru')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => \App\Models\Voucher::count()),

            'active' => Tab::make('Aktif')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('is_active', true)
                        ->where(function ($q) {
                            $q->whereNull('valid_until')
                                ->orWhere('valid_until', '>=', now());
                        })
                )
                ->badge(fn () => \App\Models\Voucher::where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('valid_until')
                            ->orWhere('valid_until', '>=', now());
                    })->count())
                ->badgeColor('success'),

            'expired' => Tab::make('Kadaluarsa')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereNotNull('valid_until')
                        ->where('valid_until', '<', now())
                )
                ->badge(fn () => \App\Models\Voucher::whereNotNull('valid_until')
                    ->where('valid_until', '<', now())
                    ->count())
                ->badgeColor('danger'),

            'inactive' => Tab::make('Tidak Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(fn () => \App\Models\Voucher::where('is_active', false)->count())
                ->badgeColor('warning'),

            'limit_reached' => Tab::make('Limit Tercapai')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereNotNull('usage_limit')
                        ->whereColumn('used_count', '>=', 'usage_limit')
                )
                ->badge(fn () => \App\Models\Voucher::whereNotNull('usage_limit')
                    ->whereColumn('used_count', '>=', 'usage_limit')
                    ->count())
                ->badgeColor('gray'),
        ];
    }
}