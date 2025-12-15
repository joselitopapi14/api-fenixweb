<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AnalisisPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos para análisis
        $analisisPermissions = [
            'analisis.view' => 'Ver análisis de encuestas',
            'analisis.export' => 'Exportar análisis',
        ];

        foreach ($analisisPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'sanctum']
            );
        }

        // Asignar permisos al rol de admin
        $adminRole = Role::where('name', 'role.admin')->where('guard_name', 'sanctum')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(array_keys($analisisPermissions));
        }

        // También podemos asignar a otros roles si existen
        $userRole = Role::where('name', 'role.user')->where('guard_name', 'sanctum')->first();
        if ($userRole) {
            $userRole->givePermissionTo('analisis.view');
        }

        $this->command->info('Permisos de análisis creados y asignados correctamente.');
    }
}
