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
        Schema::create('movimiento_inventarios', function (Blueprint $table) {
            $table->id();
            $table->string('numero_contrato')->unique();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->foreignId('sede_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('tipo_movimiento_id')->constrained('tipo_movimientos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('fecha_movimiento');
            $table->text('observaciones')->nullable();
            $table->text('observaciones_generales')->nullable();
            $table->boolean('anulado')->default(false);
            $table->text('razon_anulacion')->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_anulacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_inventarios');
    }
};
