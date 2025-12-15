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
        Schema::table('clientes', function (Blueprint $table) {
            // Añadir campos para tipo de persona y responsabilidad
            $table->foreignId('tipo_persona_id')->nullable()->constrained('tipo_personas')->onDelete('set null');
            $table->foreignId('tipo_responsabilidad_id')->nullable()->constrained('tipo_responsabilidades')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Eliminar las foreign keys y los campos
            $table->dropForeign(['tipo_persona_id']);
            $table->dropForeign(['tipo_responsabilidad_id']);
            $table->dropColumn(['tipo_persona_id', 'tipo_responsabilidad_id']);
        });
    }
};
