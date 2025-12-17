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
            ['name' => 'IVA', 'code' => '01', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'IC', 'code' => '02', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ICA', 'code' => '03', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'INC', 'code' => '04', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'FtoHorticultura', 'code' => '20', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Timbre', 'code' => '21', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'INC Bolsas', 'code' => '22', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'INCarbono', 'code' => '23', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'INCombustibles', 'code' => '24', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sobretasa Combustibles', 'code' => '25', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sordicom', 'code' => '26', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nombre de la figura tributaria**', 'code' => 'ZZ*', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ICL', 'code' => '32', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'INPP', 'code' => '33', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'IBUA', 'code' => '34', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ICUI', 'code' => '35', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ADV', 'code' => '36', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('impuestos')->insert($impuestos);

        // Ahora crear los porcentajes de impuestos
        $impuestoPorcentajes = [
            // IVA (impuesto_id = 1)
            ['impuesto_id' => 1, 'percentage' => 0.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 1, 'percentage' => 5.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 1, 'percentage' => 16.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 1, 'percentage' => 19.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 1, 'percentage' => 20.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 1, 'percentage' => 35.00, 'created_at' => now(), 'updated_at' => now()],
            
            // INC (impuesto_id = 4)
            ['impuesto_id' => 4, 'percentage' => 2.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 4, 'percentage' => 4.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 4, 'percentage' => 6.00, 'created_at' => now(), 'updated_at' => now()],
            ['impuesto_id' => 4, 'percentage' => 16.00, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('impuesto_porcentajes')->insert($impuestoPorcentajes);
    }
}
