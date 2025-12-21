<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PageVisitResource\Pages;
use App\Models\PageVisit;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PageVisitResource extends Resource
{
    protected static ?string $model = PageVisit::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'Statistik Pengunjung';
    
    protected static ?string $navigationGroup = 'Analytics';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('visit_date')
                    ->label('Tanggal')
                    ->date('l, d F Y')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('total_visits')
                    ->label('Total Pengunjung')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->size('lg'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Pertama Kali Ditrack')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('visit_date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('this_week')
                    ->label('📅 Minggu Ini')
                    ->query(fn (Builder $query) => $query->whereBetween('visit_date', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ])),
                    
                Tables\Filters\Filter::make('this_month')
                    ->label('📅 Bulan Ini')
                    ->query(fn (Builder $query) => $query->whereMonth('visit_date', now()->month)
                        ->whereYear('visit_date', now()->year)),
                        
                Tables\Filters\Filter::make('last_30_days')
                    ->label('📅 30 Hari Terakhir')
                    ->query(fn (Builder $query) => $query->where('visit_date', '>=', now()->subDays(30))),
                    
                Tables\Filters\Filter::make('last_120_days')
                    ->label('📅 120 Hari Terakhir')
                    ->query(fn (Builder $query) => $query->where('visit_date', '>=', now()->subDays(120))),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPageVisits::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    // Badge untuk hari ini
    public static function getNavigationBadge(): ?string
    {
        $today = static::getModel()::whereDate('visit_date', today())->first();
        return $today ? (string) $today->total_visits : '0';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
