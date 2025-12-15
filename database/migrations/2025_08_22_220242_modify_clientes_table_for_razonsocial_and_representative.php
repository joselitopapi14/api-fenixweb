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
            // Hacer campos nullable para personas jurídicas (tipo_documento_id = 6)
            $table->string('nombres')->nullable()->change();
            $table->string('apellidos')->nullable()->change();

            // Agregar campos para personas jurídicas
            $table->string('razon_social')->nullable()->after('apellidos');
            $table->string('dv', 1)->nullable()->after('cedula_nit');

            // Agregar fecha de nacimiento para personas naturales
            $table->date('fecha_nacimiento')->nullable()->after('email');

            // Campos del representante legal (para personas jurídicas)
            $table->string('representante_legal')->nullable()->after('fecha_nacimiento');
            $table->string('cedula_representante', 20)->nullable()->after('representante_legal');
            $table->string('email_representante')->nullable()->after('cedula_representante');
            $table->text('direccion_representante')->nullable()->after('email_representante');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Revertir campos nullable
            $table->string('nombres')->nullable(false)->change();
            $table->string('apellidos')->nullable(false)->change();

            // Eliminar campos agregados
            $table->dropColumn([
                'razon_social',
                'dv',
                'fecha_nacimiento',
                'representante_legal',
                'cedula_representante',
                'email_representante',
                'direccion_representante'
            ]);
        });
    }
};
