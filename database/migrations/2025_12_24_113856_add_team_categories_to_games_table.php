<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->foreignId('team1_category_id')
                ->nullable()
                ->after('team1_id')
                ->constrained('team_categories')
                ->nullOnDelete();
                
            $table->foreignId('team2_category_id')
                ->nullable()
                ->after('team2_id')
                ->constrained('team_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign(['team1_category_id']);
            $table->dropForeign(['team2_category_id']);
            $table->dropColumn(['team1_category_id', 'team2_category_id']);
        });
    }
};