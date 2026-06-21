<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop unique constraint if exists
        $indexes = Schema::getIndexes('team_categories');
        $hasUnique = collect($indexes)->contains(function ($index) {
            return in_array('team_id', $index['columns']) && in_array('category_name', $index['columns']);
        });

        if ($hasUnique) {
            Schema::table('team_categories', function (Blueprint $table) {
                // Find the name of the index to drop it
                // Usually it's team_categories_team_id_category_name_unique
                $table->dropUnique(['team_id', 'category_name']);
            });
        }

        // 2. Drop foreign key if exists
        $foreignKeys = Schema::getForeignKeys('team_categories');
        $hasForeignKey = collect($foreignKeys)->contains(function ($fk) {
            return in_array('team_id', $fk['columns']);
        });

        if ($hasForeignKey) {
            Schema::table('team_categories', function (Blueprint $table) {
                $table->dropForeign(['team_id']);
            });
        }

        // 3. Drop column if exists
        if (Schema::hasColumn('team_categories', 'team_id')) {
            Schema::table('team_categories', function (Blueprint $table) {
                $table->dropColumn('team_id');
            });
        }
    }

    public function down(): void
    {
        // Reverse operations
        if (!Schema::hasColumn('team_categories', 'team_id')) {
            Schema::table('team_categories', function (Blueprint $table) {
                $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
                $table->unique(['team_id', 'category_name']);
            });
        }
    }
};
