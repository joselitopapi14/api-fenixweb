<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoDocumentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposDocumento = [
            ['nombre' => 'RC', 'descripcion' => 'Registro Civil', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'TI', 'descripcion' => 'Tarjeta de Identidad', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'CC', 'descripcion' => 'Cédula de Ciudadanía', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'TE', 'descripcion' => 'Tarjeta de Extranjería', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'CE', 'descripcion' => 'Cédula de Extranjería', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'NIT', 'descripcion' => 'Número de Identificación Tributaria', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'PP', 'descripcion' => 'Pasaporte', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'DIE', 'descripcion' => 'Documento de Identificación Extranjero', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('tipo_documentos')->insert($tiposDocumento);
    }
}
