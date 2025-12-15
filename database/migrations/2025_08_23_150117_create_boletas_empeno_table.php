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
        Schema::create('boletas_empeno', function (Blueprint $table) {
            $table->id();
            $table->string('numero_contrato')->unique();
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->foreignId('sede_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('tipo_interes_id')->constrained('tipo_interes')->onDelete('cascade');
            $table->foreignId('tipo_movimiento_id')->constrained('tipo_movimientos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('monto_prestamo', 15, 2)->default(0);
            $table->enum('estado', ['activa', 'pagada', 'vencida', 'anulada'])->default('activa');
            $table->date('fecha_vencimiento')->nullable();
            $table->date('fecha_prestamo')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('ubicacion')->nullable();
            $table->string('foto_prenda')->nullable();
            $table->string('qr_code')->nullable();

            // Campos de anulación
            $table->boolean('anulada')->default(false);
            $table->text('razon_anulacion')->nullable();
            $table->foreignId('anulada_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_anulacion')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'estado']);
            $table->index(['cliente_id', 'estado']);
            $table->index(['sede_id', 'estado']);
            $table->index(['anulada', 'deleted_at']);
            $table->index('numero_contrato');
        });
    }    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boletas_empeno');
    }
};
