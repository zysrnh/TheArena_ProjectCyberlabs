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
        Schema::create('twilio_logs', function (Blueprint $table) {
            $table->id();
            $table->string('message_sid')->unique()->nullable(); // Twilio's message SID
            $table->string('account_sid')->nullable();
            $table->foreignId('registration_id')->constrained()->onDelete('cascade');
            $table->string('to_number')->nullable(); // Will be populated from MessageInstance
            $table->string('from_number')->nullable(); // Will be populated from MessageInstance
            $table->string('direction')->nullable(); // Will be populated from MessageInstance
            $table->string('status')->nullable(); // Will be populated from MessageInstance/webhook
            $table->text('error_message')->nullable();
            $table->integer('error_code')->nullable();
            $table->json('twilio_response')->nullable(); // Store full Twilio MessageInstance data
            $table->json('webhook_data')->nullable(); // Store webhook data when received
            $table->decimal('price')->nullable(); // From MessageInstance
            $table->string('price_unit')->nullable(); // From MessageInstance
            $table->timestamp('twilio_date_created')->nullable(); // From MessageInstance
            $table->timestamp('twilio_date_sent')->nullable(); // From MessageInstance
            $table->timestamp('twilio_date_updated')->nullable(); // From MessageInstance
            $table->timestamps();

            $table->index('registration_id');
            $table->index('message_sid');
            $table->index('status');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('twilio_logs');
    }
};
