<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Hapus team_id dari team_categories (jadiin global)
        Schema::table('team_categories', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });

        // 2. Update tabel game
        Schema::table('games', function (Blueprint $table) {
            // Hapus foreign key & kolom lama
            $table->dropForeign(['team1_category_id']);
            $table->dropForeign(['team2_category_id']);
            $table->dropColumn(['team1_category_id', 'team2_category_id']);

            // Tambah 1 kolom category_id global
            $table->foreignId('category_id')
                ->nullable()
                ->after('team2_id')
                ->constrained('team_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');

            $table->foreignId('team1_category_id')->nullable()->constrained('team_categories')->nullOnDelete();
            $table->foreignId('team2_category_id')->nullable()->constrained('team_categories')->nullOnDelete();
        });

        Schema::table('team_categories', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
        });
    }
};