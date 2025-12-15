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
        Schema::create('empresa_red_social', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('red_social_id')->constrained('redes_sociales')->onDelete('cascade');
            $table->string('usuario_red_social', 255); // @usuario o URL del perfil
            $table->timestamps();

            // Índices únicos
            $table->unique(['empresa_id', 'red_social_id']);
            $table->index(['empresa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa_red_social');
    }
};
