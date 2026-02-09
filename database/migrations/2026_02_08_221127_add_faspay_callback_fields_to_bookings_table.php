<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('payment_reff', 255)->nullable()->after('trx_id');
            $table->dateTime('payment_date')->nullable()->after('payment_reff');
            $table->string('payment_status_code', 10)->nullable()->after('payment_date');
            $table->string('payment_status_desc', 255)->nullable()->after('payment_status_code');
            $table->string('payment_channel_uid', 50)->nullable()->after('payment_status_desc');
            $table->string('payment_channel', 100)->nullable()->after('payment_channel_uid');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'payment_reff',
                'payment_date',
                'payment_status_code',
                'payment_status_desc',
                'payment_channel_uid',
                'payment_channel',
            ]);
        });
    }
};