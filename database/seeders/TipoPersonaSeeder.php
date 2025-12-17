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
        DB::table('tipo_personas')->insert([
            ['name' => 'Persona Natural', 'code' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Persona Jurídica', 'code' => '1', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
