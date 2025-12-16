<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImpuestoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $impuestos = [
            [
                'name' => 'IVA',
                'code' => '01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'INC',
                'code' => '04',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ICA',
                'code' => '03',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('impuestos')->insert($impuestos);

        // Ahora crear los porcentajes de impuestos
        $impuestoPorcentajes = [
            // IVA
            ['impuesto_id' => 1, 'percentage' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 1, 'percentage' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 1, 'percentage' => 19, 'created_at' => now(), 'updated_at' => now()],
            
            // INC
            ['impuesto_id' => 2, 'percentage' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 2, 'percentage' => 8, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 2, 'percentage' => 16, 'created_at' => now(), 'updated_at' => now()],
            
            // ICA
            ['impuesto_id' => 3, 'percentage' => 0.966, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('impuesto_porcentajes')->insert($impuestoPorcentajes);
    }
}
