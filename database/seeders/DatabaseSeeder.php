<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            LocalDefaultUsersSeeder::class,
            PaisesSeeder::class,
            DepartamentosSeeder::class,
            MunicipiosSeeder::class,
            ComunaSeeder::class,
            BarrioSeeder::class,
            RolesAndPermissionsSeeder::class,
            EmpresaRolesSeeder::class
        ]);
    }
}
