<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EventNotifResource\Pages;
use App\Models\EventNotif;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Notifications\Notification;

class EventNotifResource extends Resource
{
    protected static ?string $model = EventNotif::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    
    protected static ?string $navigationLabel = 'Event Notifications';
    
    protected static ?string $modelLabel = 'Event Notification';
    
    protected static ?string $pluralModelLabel = 'Event Notifications';
    
    protected static ?string $navigationGroup = 'Marketing';
    
    protected static ?int $navigationSort = 4;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            Section::make('Informasi Event')
                ->description('Detail utama event yang akan ditampilkan')
                ->schema([
                    TextInput::make('title')
                        ->label('Judul Event')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Contoh: The Arena\'s Annual Event')
                        ->columnSpanFull(),
                    
                    Textarea::make('tagline')
                        ->label('Tagline / Call to Action')
                        ->rows(2)
                        ->placeholder('Contoh: Secure your slot before the quota runs out. Register now and get the best experience.')
                        ->helperText('Kalimat menarik yang ditampilkan di popup')
                        ->columnSpanFull(),
                    
                    Textarea::make('description')
                        ->label('Deskripsi Event')
                        ->required()
                        ->rows(4)
                        ->placeholder('Deskripsi lengkap tentang event...')
                        ->columnSpanFull()
                        ->helperText('Deskripsi detail event'),
                    
                    FileUpload::make('image')
                        ->label('Banner Event (Rasio 4:5)')
                        ->image()
                        ->directory('event-notifs')
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '4:5',
                        ])
                        ->imageEditorMode(2)
                        ->imageEditorEmptyFillColor('#013064')
                        ->maxSize(2048)
                        ->helperText('⚠️ WAJIB rasio 4:5 (Portrait). Gunakan Image Editor untuk crop otomatis. Max 2MB.')
                        ->columnSpanFull()
                        ->required(),
                ])
                ->columns(2),

            Section::make('Jadwal Event')
                ->description('Tanggal dan waktu pelaksanaan event')
                ->schema([
                    DatePicker::make('event_date')
                        ->label('Tanggal Mulai Event')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->closeOnDateSelection()
                        ->helperText('Tanggal dimulainya event'),
                    
                    DatePicker::make('event_end_date')
                        ->label('Tanggal Berakhir Event')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->closeOnDateSelection()
                        ->helperText('Kosongkan jika event hanya 1 hari')
                        ->afterOrEqual('event_date'),
                    
                    TimePicker::make('event_time')
                        ->label('Waktu Event')
                        ->seconds(false)
                        ->helperText('Contoh: 11:11'),
                    
                    TextInput::make('location')
                        ->label('Lokasi')
                        ->maxLength(255)
                        ->placeholder('Contoh: TRANSMART')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Pengaturan Tampilan')
                ->description('Atur elemen yang ditampilkan di popup')
                ->schema([
                    Toggle::make('show_pricing')
                        ->label('Tampilkan Harga')
                        ->helperText('Nonaktifkan jika event tidak memerlukan informasi harga (misalnya: event umum, turnamen, dll)')
                        ->default(true)
                        ->inline(false)
                        ->reactive(),
                ])
                ->collapsible()
                ->collapsed(false),

            Section::make('Opsi Harga')
                ->description('Atur paket harga untuk event (opsional)')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            TextInput::make('monthly_original_price')
                                ->label('Harga Asli Bulanan')
                                ->numeric()
                                ->prefix('Rp')
                                ->placeholder('500000')
                                ->helperText('Harga sebelum diskon'),
                            
                            TextInput::make('monthly_price')
                                ->label('Harga Bulanan (Setelah Diskon)')
                                ->numeric()
                                ->prefix('Rp')
                                ->placeholder('250000')
                                ->helperText('Harga setelah diskon'),
                            
                            TextInput::make('monthly_discount_percent')
                                ->label('Persentase Diskon')
                                ->numeric()
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(100)
                                ->placeholder('50')
                                ->helperText('Contoh: 50 untuk diskon 50%'),
                        ]),
                    
                    TextInput::make('weekly_price')
                        ->label('Harga Mingguan')
                        ->numeric()
                        ->prefix('Rp')
                        ->placeholder('2500000')
                        ->helperText('Harga per pertemuan mingguan')
                        ->columnSpanFull(),
                ])
                ->visible(fn ($get) => $get('show_pricing'))
                ->collapsible()
                ->collapsed(),

            Section::make('Benefit / Including')
                ->description('Benefit yang tercakup dalam event (ditampilkan sebagai "INCLUDING")')
                ->schema([
                    Repeater::make('benefits_list')
                        ->label('Daftar Benefit')
                        ->schema([
                            TextInput::make('label')
                                ->label('Benefit')
                                ->required()
                                ->placeholder('Contoh: Shuttlecock (spin & twitch)')
                                ->columnSpanFull(),
                        ])
                        ->defaultItems(0)
                        ->columnSpanFull()
                        ->helperText('Benefit yang diterima peserta (ditampilkan dengan icon checklist)')
                        ->addActionLabel('Tambah Benefit'),
                ])
                ->collapsible()
                ->collapsed(),

            Section::make('Integrasi WhatsApp')
                ->description('Konfigurasi untuk pendaftaran via WhatsApp')
                ->schema([
                    TextInput::make('whatsapp_number')
                        ->label('Nomor WhatsApp')
                        ->required()
                        ->tel()
                        ->placeholder('6281222977985')
                        ->helperText('Format: 6281222977985 (mulai dari 62, tanpa + atau 0)')
                        ->maxLength(17),
                    
                    Textarea::make('whatsapp_message')
                        ->label('Template Pesan WhatsApp')
                        ->rows(3)
                        ->default('Halo, saya ingin mendaftar untuk event: {title}')
                        ->placeholder('Halo, saya ingin mendaftar untuk event: {title}')
                        ->helperText('Gunakan {title} untuk nama event otomatis')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Status')
                ->description('Aktifkan event notification ini')
                ->schema([
                    Toggle::make('is_active')
                        ->label('Aktifkan Event Notification')
                        ->helperText('Hanya 1 event notification yang bisa aktif. Mengaktifkan ini akan menonaktifkan yang lain.')
                        ->default(false)
                        ->inline(false),
                ]),
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Banner')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-event.png'))
                    ->size(60),
                
                TextColumn::make('title')
                    ->label('Judul Event')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),
                
                TextColumn::make('event_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                
                TextColumn::make('event_time')
                    ->label('Waktu')
                    ->time('H:i')
                    ->toggleable()
                    ->placeholder('-'),
                
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable()
                    ->toggleable()
                    ->limit(30)
                    ->placeholder('-'),
                
                TextColumn::make('monthly_price')
                    ->label('Harga Bulanan')
                    ->money('IDR')
                    ->toggleable()
                    ->placeholder('-'),
                
                TextColumn::make('weekly_price')
                    ->label('Harga Mingguan')
                    ->money('IDR')
                    ->toggleable()
                    ->placeholder('-'),
                
                BooleanColumn::make('is_active')
                    ->label('Status')
                    ->sortable()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua Event')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
                
                Tables\Filters\Filter::make('event_date')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('event_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('event_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['from'])->format('d M Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['until'])->format('d M Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),
                    
                    Tables\Actions\EditAction::make()
                        ->color('warning'),
                    
                    Tables\Actions\Action::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Aktifkan Event Notification')
                        ->modalDescription('Apakah Anda yakin ingin mengaktifkan event notification ini? Event notification lain yang aktif akan dinonaktifkan.')
                        ->modalSubmitActionLabel('Ya, Aktifkan')
                        ->action(function (EventNotif $record) {
                            // Nonaktifkan semua event notif lain
                            EventNotif::where('id', '!=', $record->id)->update(['is_active' => false]);
                            
                            // Aktifkan yang dipilih
                            $record->update(['is_active' => true]);
                            
                            Notification::make()
                                ->title('Event Notification Diaktifkan')
                                ->success()
                                ->body('Event notification berhasil diaktifkan dan akan ditampilkan di homepage.')
                                ->send();
                        })
                        ->visible(fn (EventNotif $record) => !$record->is_active),
                    
                    Tables\Actions\Action::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Nonaktifkan Event Notification')
                        ->modalDescription('Apakah Anda yakin ingin menonaktifkan event notification ini?')
                        ->modalSubmitActionLabel('Ya, Nonaktifkan')
                        ->action(function (EventNotif $record) {
                            $record->update(['is_active' => false]);
                            
                            Notification::make()
                                ->title('Event Notification Dinonaktifkan')
                                ->warning()
                                ->body('Event notification berhasil dinonaktifkan.')
                                ->send();
                        })
                        ->visible(fn (EventNotif $record) => $record->is_active),
                    
                    Tables\Actions\DeleteAction::make()
                        ->modalHeading('Hapus Event Notification')
                        ->modalDescription('Apakah Anda yakin ingin menghapus event notification ini? Tindakan ini tidak dapat dibatalkan.')
                        ->successNotificationTitle('Event notification berhasil dihapus'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('Hapus Event Notifications')
                        ->modalDescription('Apakah Anda yakin ingin menghapus event notifications yang dipilih?')
                        ->successNotificationTitle('Event notifications berhasil dihapus'),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan Semua')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Nonaktifkan Event Notifications')
                        ->modalDescription('Apakah Anda yakin ingin menonaktifkan semua event notifications yang dipilih?')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->title('Event Notifications Dinonaktifkan')
                                ->warning()
                                ->body('Semua event notifications yang dipilih berhasil dinonaktifkan.')
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->emptyStateHeading('Belum Ada Event Notification')
            ->emptyStateDescription('Buat event notification pertama Anda untuk menampilkan popup di homepage.')
            ->emptyStateIcon('heroicon-o-bell-alert')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Event Notification')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventNotifs::route('/'),
            'create' => Pages\CreateEventNotif::route('/create'),
            'edit' => Pages\EditEventNotif::route('/{record}/edit'),
            'view' => Pages\ViewEventNotif::route('/{record}'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count() > 0 
            ? '1 Aktif' 
            : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('is_active', true)->count() > 0 
            ? 'success' 
            : null;
    }
}