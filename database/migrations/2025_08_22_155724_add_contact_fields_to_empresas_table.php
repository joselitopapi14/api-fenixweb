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
        Schema::table('empresas', function (Blueprint $table) {
            // Agregar campos de contacto
            $table->string('email')->nullable()->after('celular');
            $table->string('pagina_web')->nullable()->after('email');
            $table->string('email_representante')->nullable()->after('cedula_representante');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['email', 'pagina_web', 'email_representante']);
        });
    }
};
