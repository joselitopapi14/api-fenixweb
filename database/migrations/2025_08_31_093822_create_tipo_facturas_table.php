<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipo_facturas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('tipo_facturas')->insert([
            ['name' => 'Factura electrónica de Venta', 'code' => '01'],
            ['name' => 'Factura electrónica de venta ‐exportación', 'code' => '02'],
            ['name' => 'Instrumento electrónico de transmisión – tipo 03', 'code' => '03'],
            ['name' => 'Factura electrónica de Venta ‐ tipo 04', 'code' => '04'],
            ['name' => 'Nota Crédito', 'code' => '91'],
            ['name' => 'Nota Débito', 'code' => '92'],
            ['name' => 'Eventos (ApplicationResponse)', 'code' => '96'],

        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_facturas');
    }
};
