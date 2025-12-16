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
            [
                'name' => 'ReteIVA',
                'code' => '05',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ReteICA',
                'code' => '07',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ReteFuente',
                'code' => '06',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ReteRenta',
                'code' => '01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tipo_retenciones')->insert($tiposRetencion);
    }
}
