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
        DB::table('tipo_responsabilidades')->insert([
            ['name' => 'Gran contribuyente', 'code' => 'O-13', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Autorretenedor', 'code' => 'O-15', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Agente de retención IVA', 'code' => 'O-23', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Régimen simple de tributación', 'code' => 'O-47', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'No responsable', 'code' => 'R-99-PN', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
