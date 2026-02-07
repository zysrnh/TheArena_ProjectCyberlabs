<?php

namespace App\Filament\Admin\Resources\VoucherResource\Pages;

use App\Filament\Admin\Resources\VoucherResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVoucher extends CreateRecord
{
    protected static string $resource = VoucherResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Voucher berhasil dibuat!';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Uppercase kode voucher
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        // Set default used_count
        $data['used_count'] = 0;

        return $data;
    }
}