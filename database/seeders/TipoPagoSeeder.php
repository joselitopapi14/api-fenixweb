<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposPago = [
            [
                'name' => 'Contado',
                'code' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Crédito',
                'code' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tipo_pagos')->insert($tiposPago);
    }
}
