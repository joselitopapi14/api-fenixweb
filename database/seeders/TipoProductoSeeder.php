<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposProducto = [
            [
                'nombre' => 'Producto',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Servicio',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Oro',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tipo_productos')->insert($tiposProducto);
    }
}
