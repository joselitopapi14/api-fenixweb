<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresas = [
            [
                'razon_social' => 'EMPRESA DE PRUEBA S.A.S',
                'nit' => '900123456',
                'dv' => '7',
                'email' => 'contacto@empresaprueba.com',
                'telefono' => '3001234567',
                'direccion' => 'CALLE 123 # 45-67',
                'ciudad' => 'Bogotá',
                'departamento' => 'Cundinamarca',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('empresas')->insert($empresas);
    }
}
