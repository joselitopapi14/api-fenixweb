<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NovedadPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos para el administrador de novedades
        $permissions = [
            'novedades.view' => 'Ver novedades y conflictos de liderazgo',
            'novedades.resolve' => 'Resolver conflictos de liderazgo',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['description' => $description, 'guard_name' => 'sanctum']
            );
        }

        // Asignar permisos al rol de admin (si existe)
        $adminRole = Role::where('name', 'role.admin')->where('guard_name', 'sanctum')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(array_keys($permissions));
            $this->command->info('Permisos de novedades asignados al rol admin');
        }

        $this->command->info('Permisos de novedades creados exitosamente');
    }
}
