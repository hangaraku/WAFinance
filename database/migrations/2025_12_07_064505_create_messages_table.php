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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('external_id')->nullable()->index(); // Message ID from external platform (WhatsApp, etc.)
            $table->string('channel')->default('whatsapp'); // whatsapp, telegram, sms, etc.
            $table->string('from')->nullable(); // Sender phone/identifier
            $table->string('to')->nullable(); // Recipient phone/identifier
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->text('content');
            $table->json('metadata')->nullable(); // Store additional data (attachments, status, etc.)
            $table->timestamps();
            
            // Composite index for conversation queries
            $table->index(['user_id', 'created_at']);
            $table->index(['from', 'created_at']);
            
            // Ensure external_id is unique per channel to prevent duplicates
            $table->unique(['external_id', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
