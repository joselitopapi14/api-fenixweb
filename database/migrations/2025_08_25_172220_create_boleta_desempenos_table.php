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
        Schema::create('boleta_desempenos', function (Blueprint $table) {
           $table->id();
            $table->foreignId('bolleta_empeno_id')->constrained('boletas_empeno')->onDelete('cascade');
            $table->foreignId('tipo_movimiento_id')->constrained('tipo_movimientos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->date('fecha_abono');
            $table->text('observaciones')->nullable();
            $table->string('estado')->default('pagada'); // pagada, pendiente
            $table->timestamps();

            // Índices para mejorar performance
            $table->index(['bolleta_empeno_id', 'fecha_abono']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boleta_desempenos');
    }
};
