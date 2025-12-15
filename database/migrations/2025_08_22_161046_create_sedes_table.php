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
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();

            // Relación con empresa
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');

            // Información básica de la sede
            $table->string('nombre');
            $table->text('direccion');
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();

            // Relaciones de ubicación
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->onDelete('set null');
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->onDelete('set null');
            $table->foreignId('comuna_id')->nullable()->constrained('comunas')->onDelete('set null');
            $table->foreignId('barrio_id')->nullable()->constrained('barrios')->onDelete('set null');

            // Información adicional
            $table->boolean('es_principal')->default(false);
            $table->boolean('activa')->default(true);
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['empresa_id', 'activa']);
            $table->index(['es_principal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sedes');
    }
};
