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
            ['name' => 'Factura electrónica de Venta', 'code' => '01', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Factura electrónica de venta ‐exportación', 'code' => '02', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Instrumento electrónico de transmisión – tipo 03', 'code' => '03', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Factura electrónica de Venta ‐ tipo 04', 'code' => '04', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nota Crédito', 'code' => '91', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nota Débito', 'code' => '92', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Eventos (ApplicationResponse)', 'code' => '96', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('tipo_facturas')->insert($tiposFactura);
    }
}
