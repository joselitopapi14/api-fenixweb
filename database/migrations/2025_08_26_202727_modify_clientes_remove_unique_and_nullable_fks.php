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
        Schema::table('clientes', function (Blueprint $table) {
            // Quitar el unique constraint de cedula_nit
            $table->dropUnique(['cedula_nit']);

            // Hacer nullable las foreign keys de ubicación
            $table->foreignId('departamento_id')->nullable()->change();
            $table->foreignId('municipio_id')->nullable()->change();
            $table->foreignId('comuna_id')->nullable()->change();
            $table->foreignId('barrio_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Restaurar el unique constraint de cedula_nit
            $table->unique('cedula_nit');

            // Quitar nullable de las foreign keys de ubicación
            $table->foreignId('departamento_id')->nullable(false)->change();
            $table->foreignId('municipio_id')->nullable(false)->change();
            $table->foreignId('comuna_id')->nullable(false)->change();
            $table->foreignId('barrio_id')->nullable(false)->change();
        });
    }
};
