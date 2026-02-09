<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // ✅ Tambahkan kolom booking_type jika belum ada
            if (!Schema::hasColumn('bookings', 'booking_type')) {
                $table->string('booking_type')->default('paid')->after('payment_status');
                // Values: 'manual', 'paid', 'pending', 'recurring', 'member_manual'
            }
        });

        // ✅ Update existing bookings untuk set booking_type
        DB::table('bookings')
            ->where('payment_status', 'paid')
            ->where('is_paid', true)
            ->whereNull('booking_type')
            ->update(['booking_type' => 'paid']);

        DB::table('bookings')
            ->where('payment_status', 'pending')
            ->where('status', 'pending')
            ->whereNull('booking_type')
            ->update(['booking_type' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'booking_type')) {
                $table->dropColumn('booking_type');
            }
        });
    }
};