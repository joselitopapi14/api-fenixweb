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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();

            // Información básica de la empresa
            $table->string('nit', 20)->unique();
            $table->string('dv', 1);
            $table->string('razon_social', 255);
            $table->text('direccion');

            // Relaciones de ubicación en cascada
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->onDelete('set null');
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->onDelete('set null');
            $table->foreignId('comuna_id')->nullable()->constrained('comunas')->onDelete('set null');
            $table->foreignId('barrio_id')->nullable()->constrained('barrios')->onDelete('set null');

            // Información de contacto
            $table->string('telefono_fijo', 20)->nullable();
            $table->string('celular', 20)->nullable();

            // Archivos
            $table->string('logo')->nullable();

            // Información del representante legal
            $table->string('representante_legal', 255);
            $table->string('cedula_representante', 20);
            $table->text('direccion_representante');

            // Estado
            $table->boolean('activa')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['activa']);
            $table->index(['nit', 'activa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
