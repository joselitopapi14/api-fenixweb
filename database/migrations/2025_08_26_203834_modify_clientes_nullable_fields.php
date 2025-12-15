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
            // Hacer nullable los campos requeridos
            $table->date('fecha_nacimiento')->nullable()->change();
            $table->text('direccion')->nullable()->change();
            $table->string('celular')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Restaurar los campos como requeridos
            $table->date('fecha_nacimiento')->nullable(false)->change();
            $table->text('direccion')->nullable(false)->change();
            $table->string('celular')->nullable(false)->change();
        });
    }
};
