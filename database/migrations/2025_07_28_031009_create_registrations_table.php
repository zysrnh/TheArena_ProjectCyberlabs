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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('unique_code')->nullable();
            $table->boolean('has_attended')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamp('attended_at')->nullable();
            $table->timestamp('last_blasted_at')->nullable();
            $table->timestamp('last_successful_sent_at')->nullable();
            $table->integer('whatsapp_send_attempts')->default(0);
            $table->json('extras')->nullable();
            $table->foreignId('event_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
