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
        Schema::table('productos', function (Blueprint $table) {
            $table->string('codigo_barras')->nullable()->after('imagen');
            $table->decimal('precio_venta', 10, 2)->nullable()->after('codigo_barras');
            $table->decimal('precio_compra', 10, 2)->nullable()->after('precio_venta');

            // Agregar índice para código de barras para búsquedas rápidas
            $table->index('codigo_barras');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['codigo_barras']);
            $table->dropColumn(['codigo_barras', 'precio_venta', 'precio_compra']);
        });
    }
};
