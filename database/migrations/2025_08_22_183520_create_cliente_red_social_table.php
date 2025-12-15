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
        Schema::create('cliente_red_social', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('red_social_id')->constrained('redes_sociales')->onDelete('cascade');
            $table->string('valor'); // URL o usuario de la red social
            $table->timestamps();

            // Evitar duplicados
            $table->unique(['cliente_id', 'red_social_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_red_social');
    }
};
