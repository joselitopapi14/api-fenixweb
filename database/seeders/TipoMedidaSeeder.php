<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoMedidaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tipo_medidas')->insert([
            ['nombre' => 'Unidad', 'abreviatura' => 'und', 'descripcion' => 'Unidad de medida por defecto', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Kilogramo', 'abreviatura' => 'kg', 'descripcion' => 'Peso en kilogramos', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Gramo', 'abreviatura' => 'gr', 'descripcion' => 'Peso en gramos', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Tonelada', 'abreviatura' => 't', 'descripcion' => 'Peso en toneladas', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Mililitro', 'abreviatura' => 'ml', 'descripcion' => 'Volumen en mililitros', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Centímetro', 'abreviatura' => 'cm', 'descripcion' => 'Longitud en centímetros', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Metro', 'abreviatura' => 'mt', 'descripcion' => 'Longitud en metros', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Hora', 'abreviatura' => 'hr', 'descripcion' => 'Tiempo en horas', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Minuto', 'abreviatura' => 'min', 'descripcion' => 'Tiempo en minutos', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
