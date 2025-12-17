<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoRetencionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposRetencion = [
            ['name' => 'ReteIVA', 'code' => '05', 'impuesto_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ReteRenta', 'code' => '06', 'impuesto_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ReteFuente', 'code' => '06', 'impuesto_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ReteICA', 'code' => '07', 'impuesto_id' => 3, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('tipo_retenciones')->insert($tiposRetencion);
    }
}
