<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoFacturaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposFactura = [
            [
                'name' => 'Factura de Venta',
                'code' => '01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Nota Crédito',
                'code' => '91',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Nota Débito',
                'code' => '92',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tipo_facturas')->insert($tiposFactura);
    }
}
