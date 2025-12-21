<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    
    protected static ?string $navigationLabel = 'Reviews';
    
    protected static ?string $navigationGroup = 'Booking Management';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Status Badge
                Tables\Columns\BadgeColumn::make('is_approved')
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Approved' : 'Pending')
                    ->colors([
                        'success' => fn ($state): bool => $state === true,
                        'warning' => fn ($state): bool => $state === false,
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => fn ($state): bool => $state === true,
                        'heroicon-o-clock' => fn ($state): bool => $state === false,
                    ])
                    ->sortable(),

                // Client Name
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                // Client Email
                Tables\Columns\TextColumn::make('client.email')
                    ->label('Email')
                    ->searchable()
                    ->color('gray')
                    ->toggleable(),

                // Rating Rata-rata (Prominently displayed)
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state): string => '⭐ ' . number_format($state, 1) . '/5')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                // Booking Date
                Tables\Columns\TextColumn::make('booking.booking_date')
                    ->label('Tgl Booking')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                // Approved Date
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Disetujui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('Belum disetujui')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Created Date
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Filter Status
                Tables\Filters\SelectFilter::make('is_approved')
                    ->label('Status')
                    ->options([
                        '1' => '✓ Approved',
                        '0' => '⏱ Pending',
                    ])
                    ->placeholder('Semua Status'),

                // Filter Average Rating
                Tables\Filters\SelectFilter::make('rating')
                    ->label('Rating Rata-rata')
                    ->options([
                        5 => '⭐⭐⭐⭐⭐ Excellent (5)',
                        4 => '⭐⭐⭐⭐ Good (4)',
                        3 => '⭐⭐⭐ Average (3)',
                        2 => '⭐⭐ Poor (2)',
                        1 => '⭐ Bad (1)',
                    ]),

                // Date Range Filter
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                // View Detail
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->modalHeading('Detail Review')
                    ->modalWidth('2xl')
                    ->slideOver()
                    ->modalContent(fn (Review $record): \Illuminate\Contracts\View\View => view(
                        'filament.admin.resources.review-resource.pages.view-review',
                        ['record' => $record->load('client', 'booking')]
                    )),

                // Approve Action
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Review $record): bool => !$record->is_approved)
                    ->requiresConfirmation()
                    ->modalHeading('Approve Review')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui review ini?')
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->action(function (Review $record) {
                        $record->update([
                            'is_approved' => true,
                            'approved_at' => now(),
                            'approved_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Review Approved')
                            ->success()
                            ->body('Review berhasil disetujui dan akan tampil di website.')
                            ->send();
                    }),

                // Reject Action
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Review $record): bool => $record->is_approved)
                    ->requiresConfirmation()
                    ->modalHeading('Reject Review')
                    ->modalDescription('Review akan disembunyikan dari website.')
                    ->modalSubmitActionLabel('Ya, Tolak')
                    ->action(function (Review $record) {
                        $record->update([
                            'is_approved' => false,
                            'approved_at' => null,
                            'approved_by' => null,
                        ]);

                        Notification::make()
                            ->title('Review Rejected')
                            ->warning()
                            ->body('Review telah ditolak dan disembunyikan.')
                            ->send();
                    }),
                    
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Review')
                    ->modalDescription('Apakah Anda yakin ingin menghapus review ini?')
                    ->modalSubmitActionLabel('Ya, Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk Approve
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update([
                                'is_approved' => true,
                                'approved_at' => now(),
                                'approved_by' => auth()->id(),
                            ]));

                            Notification::make()
                                ->title('Reviews Approved')
                                ->success()
                                ->body(count($records) . ' review berhasil disetujui.')
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    // Badge untuk pending reviews
    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('is_approved', false)->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}