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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('cedula_nit')->unique();
            $table->text('direccion');

            // Campos de ubicación
            $table->foreignId('departamento_id')->constrained('departamentos');
            $table->foreignId('municipio_id')->constrained('municipios');
            $table->foreignId('comuna_id')->constrained('comunas');
            $table->foreignId('barrio_id')->constrained('barrios');

            // Campos de contacto
            $table->string('telefono_fijo')->nullable();
            $table->string('celular');

            // Campo de foto
            $table->string('foto')->nullable();

            // Relación con empresa
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['empresa_id', 'nombre']);
            $table->index(['empresa_id', 'cedula_nit']);
            $table->index(['empresa_id', 'celular']);
            $table->index(['departamento_id', 'municipio_id', 'comuna_id', 'barrio_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
