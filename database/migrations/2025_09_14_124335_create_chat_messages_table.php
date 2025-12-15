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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();

            // Relación con el chat
            $table->foreignId('chat_id')->constrained('chats')->onDelete('cascade');

            // Contenido del mensaje
            $table->longText('content');

            // Información del mensaje
            $table->boolean('is_from_user')->default(true);
            $table->string('message_type')->default('text'); // text, system, error, info

            // Metadata del mensaje (JSON)
            $table->json('metadata')->nullable();

            // Información de la respuesta AI (solo para mensajes del asistente)
            $table->integer('tokens_used')->nullable();
            $table->string('model_used')->nullable();
            $table->integer('response_time_ms')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['chat_id', 'created_at']);
            $table->index(['chat_id', 'is_from_user']);
            $table->index(['message_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
