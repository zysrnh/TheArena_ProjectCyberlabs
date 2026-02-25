<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('games', function (Blueprint $table) {
        $table->string('team1_division')->nullable()->after('team1_id');
        $table->string('team2_division')->nullable()->after('team2_id');
    });
}

public function down(): void
{
    Schema::table('games', function (Blueprint $table) {
        $table->dropColumn(['team1_division', 'team2_division']);
    });
}
};
