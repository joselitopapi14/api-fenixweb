<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoPersonaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposPersona = [
            [
                'nombre' => 'Persona Natural',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Persona Jurídica',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tipo_personas')->insert($tiposPersona);
    }
}
