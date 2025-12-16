<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoMedidaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposMedida = [
            [
                'nombre' => 'Unidad',
                'abreviatura' => 'UND',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Kilogramo',
                'abreviatura' => 'KG',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Gramo',
                'abreviatura' => 'GR',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Metro',
                'abreviatura' => 'MT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Litro',
                'abreviatura' => 'LT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Caja',
                'abreviatura' => 'CJ',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tipo_medidas')->insert($tiposMedida);
    }
}
