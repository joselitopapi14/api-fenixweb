<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class MunicipiosSeeder extends Seeder
{
    public function run()
    {
        // Detecta el tipo de base de datos
        $connection = DB::getDriverName();

        // En MySQL desactivamos temporalmente las claves foráneas para poder truncar
        if ($connection === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('municipios')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
        // En PostgreSQL usamos DELETE, ya que TRUNCATE requiere manejar las dependencias explícitamente
        elseif ($connection === 'pgsql') {
            DB::table('municipios')->delete();
            // Opcional: reiniciar el ID si necesitas que arranque desde 1
            DB::statement("ALTER SEQUENCE municipios_id_seq RESTART WITH 1;");
        }

        $csvFile = storage_path('app/municipios.csv');

        if (!File::exists($csvFile)) {
            $this->command->error("El archivo $csvFile no existe.");
            return;
        }

        $csvData = File::get($csvFile);
        $lines = explode("\n", $csvData);
        array_shift($lines); // Quitar encabezados

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $data = str_getcsv($line);

            if (count($data) >= 4) {
                DB::table('municipios')->insert([
                    'id' => $data[0],
                    'name' => $data[1],
                    'code' => $data[2],
                    'departamento_id' => $data[3],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Municipios importados exitosamente!');
    }
}
