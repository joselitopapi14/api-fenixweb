<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipo_medidas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('abreviatura');
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();

            // Índices
            $table->index('activo');
            $table->unique(['nombre']);
            $table->unique(['abreviatura']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_medidas');
    }
};
