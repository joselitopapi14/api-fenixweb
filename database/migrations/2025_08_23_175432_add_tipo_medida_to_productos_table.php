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
            $table->foreignId('tipo_medida_id')->nullable()->after('precio_compra')->constrained('tipo_medidas')->onDelete('set null');

            // Agregar índice para búsquedas
            $table->index('tipo_medida_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['tipo_medida_id']);
            $table->dropIndex(['tipo_medida_id']);
            $table->dropColumn('tipo_medida_id');
        });
    }
};
