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
        Schema::create('errores_dian', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->comment('Código del error DIAN (ej: DSAK24b)');
            $table->text('descripcion')->comment('Descripción completa del error');
            $table->text('mensaje_original')->comment('Mensaje original completo de DIAN');
            $table->string('hash_error', 64)->unique()->comment('Hash MD5 del mensaje original para evitar duplicados');
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index('codigo');
            $table->index('hash_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('errores_dian');
    }
};
