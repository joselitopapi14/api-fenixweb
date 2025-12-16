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
        $mediosPago = [
            [
                'name' => 'Efectivo',
                'code' => '10',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tarjeta Débito',
                'code' => '48',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tarjeta Crédito',
                'code' => '49',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Transferencia Bancaria',
                'code' => '42',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'PSE',
                'code' => '47',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Consignación Bancaria',
                'code' => '43',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('medio_pagos')->insert($mediosPago);
    }
}
