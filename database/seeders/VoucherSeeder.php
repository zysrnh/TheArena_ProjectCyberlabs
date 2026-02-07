<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $nextMonth = Carbon::now()->addMonth();

        $vouchers = [
            // Voucher Fixed Amount
            [
                'code' => 'WELCOME50K',
                'discount_type' => 'fixed',
                'discount_value' => 50000,
                'min_purchase' => 300000,
                'max_discount' => null,
                'valid_from' => $now,
                'valid_until' => $nextMonth,
                'usage_limit' => 100,
                'used_count' => 0,
                'is_active' => true,
                'description' => 'Diskon Rp 50.000 untuk pembelian minimal Rp 300.000',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'DISKON100K',
                'discount_type' => 'fixed',
                'discount_value' => 100000,
                'min_purchase' => 500000,
                'max_discount' => null,
                'valid_from' => $now,
                'valid_until' => $nextMonth,
                'usage_limit' => 50,
                'used_count' => 0,
                'is_active' => true,
                'description' => 'Diskon Rp 100.000 untuk pembelian minimal Rp 500.000',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'NEWYEAR150K',
                'discount_type' => 'fixed',
                'discount_value' => 150000,
                'min_purchase' => 700000,
                'max_discount' => null,
                'valid_from' => $now,
                'valid_until' => $nextMonth,
                'usage_limit' => 30,
                'used_count' => 0,
                'is_active' => true,
                'description' => 'Diskon Tahun Baru Rp 150.000 untuk pembelian minimal Rp 700.000',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Voucher Percentage
            [
                'code' => 'DISKON10',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'min_purchase' => 0,
                'max_discount' => 100000,
                'valid_from' => $now,
                'valid_until' => $nextMonth,
                'usage_limit' => 200,
                'used_count' => 0,
                'is_active' => true,
                'description' => 'Diskon 10% maksimal Rp 100.000',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'PROMO15',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'min_purchase' => 400000,
                'max_discount' => 150000,
                'valid_from' => $now,
                'valid_until' => $nextMonth,
                'usage_limit' => 100,
                'used_count' => 0,
                'is_active' => true,
                'description' => 'Diskon 15% maksimal Rp 150.000 untuk pembelian minimal Rp 400.000',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'MEMBER20',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'min_purchase' => 500000,
                'max_discount' => 200000,
                'valid_from' => $now,
                'valid_until' => $nextMonth,
                'usage_limit' => 50,
                'used_count' => 0,
                'is_active' => true,
                'description' => 'Diskon Member 20% maksimal Rp 200.000 untuk pembelian minimal Rp 500.000',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Voucher Special Event
            [
                'code' => 'WEEKEND25',
                'discount_type' => 'percentage',
                'discount_value' => 25,
                'min_purchase' => 600000,
                'max_discount' => 250000,
                'valid_from' => $now,
                'valid_until' => $nextMonth,
                'usage_limit' => 25,
                'used_count' => 0,
                'is_active' => true,
                'description' => 'Diskon Weekend 25% maksimal Rp 250.000 untuk pembelian minimal Rp 600.000',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'GRANDOPENING',
                'discount_type' => 'fixed',
                'discount_value' => 200000,
                'min_purchase' => 800000,
                'max_discount' => null,
                'valid_from' => $now,
                'valid_until' => $nextMonth,
                'usage_limit' => 20,
                'used_count' => 0,
                'is_active' => true,
                'description' => 'Voucher Grand Opening Rp 200.000 untuk pembelian minimal Rp 800.000',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Voucher Unlimited (no usage limit)
            [
                'code' => 'FIRSTTIME',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'min_purchase' => 300000,
                'max_discount' => 75000,
                'valid_from' => $now,
                'valid_until' => $nextMonth,
                'usage_limit' => null, // Unlimited
                'used_count' => 0,
                'is_active' => true,
                'description' => 'Diskon 10% untuk pengguna baru maksimal Rp 75.000',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Voucher Not Active (untuk testing)
            [
                'code' => 'EXPIRED',
                'discount_type' => 'fixed',
                'discount_value' => 50000,
                'min_purchase' => 200000,
                'max_discount' => null,
                'valid_from' => Carbon::now()->subDays(30),
                'valid_until' => Carbon::now()->subDays(1), // Sudah expired
                'usage_limit' => 100,
                'used_count' => 0,
                'is_active' => false,
                'description' => 'Voucher yang sudah tidak aktif',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('vouchers')->insert($vouchers);

        $this->command->info('✅ Voucher seeder completed! ' . count($vouchers) . ' vouchers created.');
    }
}