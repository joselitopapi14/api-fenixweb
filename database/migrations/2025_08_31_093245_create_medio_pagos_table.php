<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMedioPagosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medio_pagos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('medio_pagos')->insert([
            ['name' => 'Efectivo', 'code' => '10'],
            ['name' => 'Tarjeta Débito', 'code' => '49'],
            ['name' => 'Consignación bancaria', 'code' => '42'],
            ['name' => 'Débito ACH', 'code' => '3'],
            ['name' => 'Cheque certificado', 'code' => '25'],
            ['name' => 'Cheque Local', 'code' => '26'],
            ['name' => 'Nota cambiaria esperando aceptación', 'code' => '24'],
            ['name' => 'Nota promisoria firmada pro el banco', 'code' => '64'],
            ['name' => 'Nota promisoria firmada por un banco avalada por otro banco', 'code' => '65'],
            ['name' => 'Nota promisoria firmada', 'code' => '66'],
            ['name' => 'Nota promisoria firmada por un tercero avalada por un banco', 'code' => '67'],
            ['name' => 'Crédito ACH', 'code' => '2'],
            ['name' => 'Otro*', 'code' => 'ZZZ'],
            ['name' => 'Giro formato abierto', 'code' => '95'],
            ['name' => 'Crédito Ahorro', 'code' => '13'],
            ['name' => 'Débito Ahorro', 'code' => '14'],
            ['name' => 'Crédito Intercambio Corporativo (CTX)', 'code' => '39'],
            ['name' => 'Reversión débito de demanda ACH', 'code' => '4'],
            ['name' => 'Reversión crédito de demanda ACH', 'code' => '5'],
            ['name' => 'Crédito de demanda ACH', 'code' => '6'],
            ['name' => 'Débito de demanda ACH', 'code' => '7'],
            ['name' => 'Clearing Nacional o Regional', 'code' => '9'],
            ['name' => 'Reversión Crédito Ahorro', 'code' => '11'],
            ['name' => 'Reversión Débito Ahorro', 'code' => '12'],
            ['name' => 'Desembolso (CCD) débito', 'code' => '16'],
            ['name' => 'Crédito Pago negocio corporativo (CTP)', 'code' => '18'],
            ['name' => 'Poyecto bancario', 'code' => '54'],
            ['name' => 'Proyecto bancario certificado', 'code' => '19'],
            ['name' => 'Débito Pago Negocio Corporativo (CTP)', 'code' => '27'],
            ['name' => 'Débito Negocio Intercambio Corporativo (CTX)', 'code' => '29'],
            ['name' => 'Desembolso Débito (CCD)', 'code' => '36'],
            ['name' => 'Transferencia Débito', 'code' => '31'],
            ['name' => 'Desembolso Crédito plus (CCD+)', 'code' => '32'],
            ['name' => 'Desembolso Débito plus (CCD+)', 'code' => '33'],
            ['name' => 'Pago y depósito de paracho (PPD)', 'code' => '34'],
            ['name' => 'Pago Negocio Corporativo Ahorros Crédito (CTP)', 'code' => '37'],

            ['name' => 'Tarjeta Crédito', 'code' => '48'],
            ['name' => 'Transferencia Débito Bancaria', 'code' => '47'],
            ['name' => 'Nota cambiaria', 'code' => '44'],
            ['name' => 'Cheque', 'code' => '20'],
            ['name' => 'Cheque bancario de gerencia', 'code' => '23'],
            ['name' => 'Bonos', 'code' => '71'],
            ['name' => 'Vales', 'code' => '72'],
            ['name' => 'Nota promisoria firmada por el acreedor', 'code' => '61'],
            ['name' => 'Nota promisoria firmada por el acreedor, avalada por el banco', 'code' => '62'],
            ['name' => 'Nota promisoria firmada por el acreedor, avalada por un tercero', 'code' => '63'],
            ['name' => 'Nota promisoria', 'code' => '60'],
            ['name' => 'Método de pago solicitado no usado', 'code' => '96'],
            ['name' => 'Nota bancaria transferible', 'code' => '91'],
            ['name' => 'Cheque local transferible', 'code' => '92'],
            ['name' => 'Giro referenciado', 'code' => '93'],
            ['name' => 'Giro urgente', 'code' => '94'],
            ['name' => 'Débito Intercambio Corporativo (CTX)', 'code' => '40'],
            ['name' => 'Desembolso Crédito plus (CCD+)', 'code' => '41'],
            ['name' => 'Desembolso Débito plus (CCD+)', 'code' => '43'],
            ['name' => 'Transferencia Crédito Bancario', 'code' => '45'],
            ['name' => 'Transferencia Débito Interbancario', 'code' => '46'],
            ['name' => 'Postigro', 'code' => '50'],
            ['name' => 'Telex estándar bancario', 'code' => '51'],
            ['name' => 'Pago comercial urgente', 'code' => '52'],
            ['name' => 'Pago Tesorería Urgente', 'code' => '53'],
            ['name' => 'Bookentry Crédito', 'code' => '81'],
            ['name' => 'Bookentry Débito', 'code' => '82'],
            ['name' => 'Desembolso Crédito (CCD)', 'code' => '55'],
            ['name' => 'Retiro de nota por el por el acreedor', 'code' => '70'],
            ['name' => 'Retiro de nota por el acreedor, avalada por otro banco', 'code' => '75'],
            ['name' => 'Retiro de una nota por el acreedor sobre un banco avalada por un tercero', 'code' => '77'],
            ['name' => 'Retiro de nota por el por el acreedor sobre un tercero avalada por un banco', 'code' => '76'],
            ['name' => 'Instrumento no definido', 'code' => '78'],
            ['name' => 'Pago Negocio Corporativo Ahorros Débito (CTP)', 'code' => '38'],
            ['name' => 'Clearing entre partners', 'code' => '97'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('medio_pagos');
    }
}
