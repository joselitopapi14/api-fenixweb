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
            ['nombre' => 'Unidad', 'abreviatura' => 'UND'],
            ['nombre' => 'Kilogramo', 'abreviatura' => 'KG'],
            ['nombre' => 'Gramo', 'abreviatura' => 'GR'],
            ['nombre' => 'Metro', 'abreviatura' => 'MT'],
            ['nombre' => 'Litro', 'abreviatura' => 'LT'],
            ['nombre' => 'Caja', 'abreviatura' => 'CJ'],
        ];

        foreach ($tiposMedida as $tipo) {
            \App\Models\TipoMedida::updateOrCreate(
                ['nombre' => $tipo['nombre']],
                [
                    'abreviatura' => $tipo['abreviatura'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
