<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConceptoRetencionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conceptos = [
            // ReteIVA (tipo_retencion_id = 1)
            [
                'tipo_retencion_id' => 1,
                'name' => 'Bienes',
                'percentage' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_retencion_id' => 1,
                'name' => 'Servicios',
                'percentage' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // ReteICA (tipo_retencion_id = 2)
            [
                'tipo_retencion_id' => 2,
                'name' => 'Actividades Industriales',
                'percentage' => 0.414,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_retencion_id' => 2,
                'name' => 'Actividades Comerciales',
                'percentage' => 0.966,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_retencion_id' => 2,
                'name' => 'Actividades de Servicios',
                'percentage' => 0.966,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // ReteFuente (tipo_retencion_id = 3)
            [
                'tipo_retencion_id' => 3,
                'name' => 'Compras',
                'percentage' => 2.5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_retencion_id' => 3,
                'name' => 'Honorarios',
                'percentage' => 11,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_retencion_id' => 3,
                'name' => 'Servicios',
                'percentage' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_retencion_id' => 3,
                'name' => 'Arrendamientos',
                'percentage' => 3.5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('concepto_retenciones')->insert($conceptos);
    }
}
