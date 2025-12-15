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
            // Agregar nuevos campos
            $table->string('nombres')->after('id');
            $table->string('apellidos')->after('nombres');
            $table->string('email')->nullable()->after('apellidos');

            // Eliminar el campo nombre
            $table->dropColumn('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Agregar de vuelta el campo nombre
            $table->string('nombre')->after('id');

            // Eliminar los nuevos campos
            $table->dropColumn(['nombres', 'apellidos', 'email']);
        });
    }
};
