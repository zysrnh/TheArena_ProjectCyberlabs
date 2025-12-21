<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign key constraint dulu
        Schema::table('page_visits', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
        
        // Sekarang baru drop kolom-kolom
        Schema::table('page_visits', function (Blueprint $table) {
            $table->dropColumn(['client_id', 'url', 'method', 'user_agent', 'session_id', 'ip_address']);
        });
        
        // Tambah kolom baru
        Schema::table('page_visits', function (Blueprint $table) {
            $table->date('visit_date')->after('id');
            $table->integer('total_visits')->default(1)->after('visit_date');
            
            // Drop index lama (setelah kolom visited_at dihapus di bawah)
            // Kita skip dulu karena visited_at masih ada
        });
        
        // Drop visited_at
        Schema::table('page_visits', function (Blueprint $table) {
            $table->dropColumn('visited_at');
        });
        
        // Bikin index baru
        Schema::table('page_visits', function (Blueprint $table) {
            $table->unique('visit_date');
        });
    }

    public function down(): void
    {
        Schema::table('page_visits', function (Blueprint $table) {
            $table->dropUnique(['visit_date']);
            $table->dropColumn(['visit_date', 'total_visits']);
            
            $table->timestamp('visited_at');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45);
            $table->string('url', 500);
            $table->string('method', 10);
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
        });
    }
};