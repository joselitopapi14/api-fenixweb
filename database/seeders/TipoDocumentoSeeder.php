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
        DB::table('tipo_documentos')->insert([
            ['code' => '11', 'name' => 'Registro civil', 'abreviacion' => 'RC', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '12', 'name' => 'Tarjeta de identidad', 'abreviacion' => 'TI', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '13', 'name' => 'Cédula de ciudadanía', 'abreviacion' => 'CC', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '21', 'name' => 'Tarjeta de extranjería', 'abreviacion' => 'TE', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '22', 'name' => 'Cédula de extranjería', 'abreviacion' => 'CE', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '31', 'name' => 'NIT', 'abreviacion' => 'NIT', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '41', 'name' => 'Pasaporte', 'abreviacion' => 'PP', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '42', 'name' => 'Documento de identificación extranjero', 'abreviacion' => 'DIE', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '47', 'name' => 'PEP', 'abreviacion' => 'PEP', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '50', 'name' => 'NIT de otro país', 'abreviacion' => 'NITOP', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '91', 'name' => 'NUIP *', 'abreviacion' => 'NUIP', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
