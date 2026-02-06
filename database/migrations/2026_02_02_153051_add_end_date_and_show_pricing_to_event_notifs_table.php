<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_notifs', function (Blueprint $table) {
            $table->date('event_end_date')->nullable()->after('event_date');
            $table->boolean('show_pricing')->default(true)->after('is_active');
            $table->text('tagline')->nullable()->after('description'); // ✅ UNTUK "Secure your slot before..."
        });
    }

    public function down(): void
    {
        Schema::table('event_notifs', function (Blueprint $table) {
            $table->dropColumn(['event_end_date', 'show_pricing', 'tagline']);
        });
    }
};