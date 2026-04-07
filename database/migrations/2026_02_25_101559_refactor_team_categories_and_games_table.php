<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. SKIP drop team_id karena emang gak ada di production
        // (Komentar aja bagian ini)
        
        // 2. Drop kolom lama di games (kalau ada)
        Schema::table('games', function (Blueprint $table) {
            // Drop team1_category_id
            if (Schema::hasColumn('games', 'team1_category_id')) {
                try {
                    $table->dropForeign(['team1_category_id']);
                } catch (\Exception $e) {
                    // Skip
                }
                $table->dropColumn('team1_category_id');
            }

            // Drop team2_category_id
            if (Schema::hasColumn('games', 'team2_category_id')) {
                try {
                    $table->dropForeign(['team2_category_id']);
                } catch (\Exception $e) {
                    // Skip
                }
                $table->dropColumn('team2_category_id');
            }
        });

        // 3. Tambah category_id baru
        if (!Schema::hasColumn('games', 'category_id')) {
            Schema::table('games', function (Blueprint $table) {
                $table->foreignId('category_id')
                    ->nullable()
                    ->after('team2_id')
                    ->constrained('team_categories')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Rollback category_id
        if (Schema::hasColumn('games', 'category_id')) {
            try {
                Schema::table('games', function (Blueprint $table) {
                    $table->dropForeign(['category_id']);
                });
            } catch (\Exception $e) {
                // Skip
            }

            Schema::table('games', function (Blueprint $table) {
                $table->dropColumn('category_id');
            });
        }

        // Restore old columns
        Schema::table('games', function (Blueprint $table) {
            if (!Schema::hasColumn('games', 'team1_category_id')) {
                $table->foreignId('team1_category_id')
                    ->nullable()
                    ->constrained('team_categories')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('games', 'team2_category_id')) {
                $table->foreignId('team2_category_id')
                    ->nullable()
                    ->constrained('team_categories')
                    ->nullOnDelete();
            }
        });
    }
};