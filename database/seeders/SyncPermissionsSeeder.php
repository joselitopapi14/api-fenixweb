<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SyncPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el rol admin (ahora sanctum)
        $role = Role::where('name', 'role.admin')->where('guard_name', 'sanctum')->first();
        
        if (!$role) {
            $this->command->error("Rol 'role.admin' no encontrado en guard 'sanctum'.");
            return;
        }

        // Obtener todos los permisos de sanctum
        $permissions = Permission::where('guard_name', 'sanctum')->get();

        if ($permissions->isEmpty()) {
            $this->command->error("No se encontraron permisos en guard 'sanctum'.");
            return;
        }

        // Sincronizar
        $role->syncPermissions($permissions);
        
        $this->command->info("Permisos sincronizados para role.admin ({$permissions->count()} permisos).");
    }
}
