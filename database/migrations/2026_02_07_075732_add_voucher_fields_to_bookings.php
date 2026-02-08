<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('original_price', 12, 2)->nullable()->after('total_price');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('original_price');
            $table->string('voucher_code', 50)->nullable()->after('discount_amount');
            
            $table->index('voucher_code');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['voucher_code']);
            $table->dropColumn(['original_price', 'discount_amount', 'voucher_code']);
        });
    }
};