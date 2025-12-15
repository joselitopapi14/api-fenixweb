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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();

            // Relación con usuario
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Información básica del chat
            $table->string('title')->nullable();
            $table->text('first_message')->nullable();

            // Relación opcional con empresa
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('cascade');

            // Estado del chat
            $table->boolean('is_active')->default(true);

            // Metadata adicional (JSON)
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['user_id', 'is_active']);
            $table->index(['empresa_id', 'is_active']);
            $table->index(['created_at', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
