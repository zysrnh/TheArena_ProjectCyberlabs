<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete(); // Track client yang login
            $table->string('ip_address', 45);
            $table->string('url', 500);
            $table->string('method', 10);
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('visited_at');
            $table->index(['client_id', 'visited_at']);
            $table->index(['ip_address', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};