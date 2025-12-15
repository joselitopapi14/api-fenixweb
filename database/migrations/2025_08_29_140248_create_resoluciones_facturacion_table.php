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
        Schema::create('resoluciones_facturacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('prefijo');
            $table->string('resolucion')->nullable();
            $table->date('fecha_resolucion')->nullable();
            $table->date('fecha_inicial')->nullable();
            $table->date('fecha_final')->nullable();
            $table->string('clave_tecnica')->nullable();
            $table->bigInteger('consecutivo_inicial');
            $table->bigInteger('consecutivo_final');
            $table->bigInteger('consecutivo_actual');
            $table->boolean('envia_dian')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['empresa_id', 'activo']);
            $table->index(['prefijo', 'empresa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resoluciones_facturacion');
    }
};
