<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComunaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('comunas')->insert([
            [
                'municipio_id' => 428,
                'nombre' => 'COMUNA 1',
                'created_at' => '2023-06-10 16:14:21',
                'updated_at' => '2023-06-10 16:14:21'
            ],
            [
                'municipio_id' => 428,
                'nombre' => 'COMUNA 2',
                'created_at' => '2023-06-10 16:19:49',
                'updated_at' => '2023-06-13 15:42:09'
            ],
            [
                'municipio_id' => 428,
                'nombre' => 'COMUNA 3',
                'created_at' => '2023-06-10 16:19:55',
                'updated_at' => '2023-06-13 15:43:06'
            ],
            [
                'municipio_id' => 428,
                'nombre' => 'COMUNA 4',
                'created_at' => '2023-06-10 16:20:01',
                'updated_at' => '2023-06-13 15:44:23'
            ],
            [
                'municipio_id' => 428,
                'nombre' => 'COMUNA 5',
                'created_at' => '2023-06-10 16:20:09',
                'updated_at' => '2023-06-13 15:53:25'
            ],
            [
                'municipio_id' => 428,
                'nombre' => 'COMUNA 6',
                'created_at' => '2023-06-10 16:20:15',
                'updated_at' => '2023-06-13 15:50:40'
            ],
            [
                'municipio_id' => 428,
                'nombre' => 'COMUNA 7',
                'created_at' => '2023-06-10 16:20:21',
                'updated_at' => '2023-06-13 15:53:49'
            ],
            [
                'municipio_id' => 428,
                'nombre' => 'COMUNA 8',
                'created_at' => '2023-06-10 16:20:27',
                'updated_at' => '2023-06-13 15:55:18'
            ],
            [
                'municipio_id' => 428,
                'nombre' => 'COMUNA 9',
                'created_at' => '2023-06-10 16:20:33',
                'updated_at' => '2023-06-13 19:26:10'
            ],
            [
                'municipio_id' => 428,
                'nombre' => 'SAN ANTERITO',
                'created_at' => '2023-06-13 19:15:28',
                'updated_at' => '2023-10-17 11:42:54'
            ],
            [
                'municipio_id' => 428,
                'nombre' => 'AGUAS NEGRAS',
                'created_at' => '2023-06-13 19:21:59',
                'updated_at' => '2023-06-13 19:23:56'
            ],
            [
                'municipio_id' => 428,
                'nombre' => 'SANTA CLARA',
                'created_at' => '2023-06-14 13:32:19',
                'updated_at' => '2023-06-14 13:32:19'
            ],
            [
                'municipio_id' => 428,
                'nombre' => 'LOMA VERDE',
                'created_at' => '2023-06-14 13:33:30',
                'updated_at' => '2023-06-14 13:33:30'
            ]
        ]);
    }
}
