<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_notifs', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_frequency',
                'monthly_loyalty_points',
                'monthly_note',
                'weekly_loyalty_points',
                'weekly_note',
                'participant_count',
                'level_tagline',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('event_notifs', function (Blueprint $table) {
            $table->string('monthly_frequency')->nullable();
            $table->integer('monthly_loyalty_points')->nullable();
            $table->text('monthly_note')->nullable();
            $table->integer('weekly_loyalty_points')->nullable();
            $table->string('weekly_note')->nullable();
            $table->string('participant_count')->nullable();
            $table->text('level_tagline')->nullable();
        });
    }
};