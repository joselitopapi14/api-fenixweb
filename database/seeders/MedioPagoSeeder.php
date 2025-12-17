<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedioPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('medio_pagos')->insert([
            ['name' => 'Efectivo', 'code' => '10', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tarjeta Débito', 'code' => '49', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Consignación bancaria', 'code' => '42', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Débito ACH', 'code' => '3', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cheque certificado', 'code' => '25', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cheque Local', 'code' => '26', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nota cambiaria esperando aceptación', 'code' => '24', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nota promisoria firmada pro el banco', 'code' => '64', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nota promisoria firmada por un banco avalada por otro banco', 'code' => '65', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nota promisoria firmada', 'code' => '66', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nota promisoria firmada por un tercero avalada por un banco', 'code' => '67', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Crédito ACH', 'code' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Otro*', 'code' => 'ZZZ', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Giro formato abierto', 'code' => '95', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Crédito Ahorro', 'code' => '13', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Débito Ahorro', 'code' => '14', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Crédito Intercambio Corporativo (CTX)', 'code' => '39', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Reversión débito de demanda ACH', 'code' => '4', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Reversión crédito de demanda ACH', 'code' => '5', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Crédito de demanda ACH', 'code' => '6', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Débito de demanda ACH', 'code' => '7', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Clearing Nacional o Regional', 'code' => '9', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Reversión Crédito Ahorro', 'code' => '11', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Reversión Débito Ahorro', 'code' => '12', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Desembolso (CCD) débito', 'code' => '16', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Crédito Pago negocio corporativo (CTP)', 'code' => '18', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Poyecto bancario', 'code' => '54', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Proyecto bancario certificado', 'code' => '19', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Débito Pago Negocio Corporativo (CTP)', 'code' => '27', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Débito Negocio Intercambio Corporativo (CTX)', 'code' => '29', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Desembolso Débito (CCD)', 'code' => '36', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transferencia Débito', 'code' => '31', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Desembolso Crédito plus (CCD+)', 'code' => '32', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Desembolso Débito plus (CCD+)', 'code' => '33', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pago y depósito de paracho (PPD)', 'code' => '34', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pago Negocio Corporativo Ahorros Crédito (CTP)', 'code' => '37', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tarjeta Crédito', 'code' => '48', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transferencia Débito Bancaria', 'code' => '47', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nota cambiaria', 'code' => '44', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cheque', 'code' => '20', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cheque bancario de gerencia', 'code' => '23', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bonos', 'code' => '71', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Vales', 'code' => '72', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nota promisoria firmada por el acreedor', 'code' => '61', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nota promisoria firmada por el acreedor, avalada por el banco', 'code' => '62', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nota promisoria firmada por el acreedor, avalada por un tercero', 'code' => '63', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nota promisoria', 'code' => '60', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Método de pago solicitado no usado', 'code' => '96', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nota bancaria transferible', 'code' => '91', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cheque local transferible', 'code' => '92', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Giro referenciado', 'code' => '93', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Giro urgente', 'code' => '94', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Débito Intercambio Corporativo (CTX)', 'code' => '40', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Desembolso Crédito plus (CCD+)', 'code' => '41', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Desembolso Débito plus (CCD+)', 'code' => '43', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transferencia Crédito Bancario', 'code' => '45', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transferencia Débito Interbancario', 'code' => '46', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Postigro', 'code' => '50', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Telex estándar bancario', 'code' => '51', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pago comercial urgente', 'code' => '52', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pago Tesorería Urgente', 'code' => '53', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bookentry Crédito', 'code' => '81', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bookentry Débito', 'code' => '82', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Desembolso Crédito (CCD)', 'code' => '55', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retiro de nota por el por el acreedor', 'code' => '70', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retiro de nota por el acreedor, avalada por otro banco', 'code' => '75', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retiro de una nota por el acreedor sobre un banco avalada por un tercero', 'code' => '77', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retiro de nota por el por el acreedor sobre un tercero avalada por un banco', 'code' => '76', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Instrumento no definido', 'code' => '78', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pago Negocio Corporativo Ahorros Débito (CTP)', 'code' => '38', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Clearing entre partners', 'code' => '97', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
