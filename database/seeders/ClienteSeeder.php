<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clientes = [
            [
                'empresa_id' => 1,
                'tipo_documento_id' => 3, // CC - Cédula de Ciudadanía
                'tipo_persona_id' => 1, // Persona Natural
                'tipo_responsabilidad_id' => 1,
                'nombres' => 'JUAN',
                'apellidos' => 'PÉREZ GÓMEZ',
                'razon_social' => null,
                'cedula_nit' => '1234567890',
                'dv' => null,
                'email' => 'juan.perez@example.com',
                'celular' => '3001234567',
                'telefono_fijo' => null,
                'direccion' => 'CALLE 10 # 20-30',
                'departamento_id' => null,
                'municipio_id' => null,
                'comuna_id' => null,
                'barrio_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => 1,
                'tipo_documento_id' => 6, // NIT
                'tipo_persona_id' => 2, // Persona Jurídica
                'tipo_responsabilidad_id' => 1,
                'nombres' => null,
                'apellidos' => null,
                'razon_social' => 'CLIENTE CORPORATIVO S.A.S',
                'cedula_nit' => '900987654',
                'dv' => '3',
                'email' => 'contacto@clientecorp.com',
                'celular' => '3009876543',
                'telefono_fijo' => '6012345678',
                'direccion' => 'CARRERA 50 # 100-200',
                'departamento_id' => null,
                'municipio_id' => null,
                'comuna_id' => null,
                'barrio_id' => null,
                'representante_legal' => 'MARÍA RODRÍGUEZ',
                'cedula_representante' => '9876543210',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('clientes')->insert($clientes);
    }
}
