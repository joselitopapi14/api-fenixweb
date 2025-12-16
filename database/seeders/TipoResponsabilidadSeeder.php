<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoResponsabilidadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposResponsabilidad = [
            ['codigo' => 'O-13', 'nombre' => 'Gran Contribuyente', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'O-15', 'nombre' => 'Autorretenedor', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'O-23', 'nombre' => 'Agente de Retención IVA', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'O-47', 'nombre' => 'Régimen Simple de Tributación', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'R-99-PN', 'nombre' => 'No Responsable de IVA', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('tipo_responsabilidades')->insert($tiposResponsabilidad);
    }
}
