<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos definidos
        $permissions = [
            'config.show',
            'dashboard.view',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'reports.view',
            'analytics.view',
            'registros.view',
            'registros.create',
            'registros.edit',
            'registros.delete',
        ];

        // Guards a soportar
        // 'web' es el default de Laravel
        // 'sanctum' es el que el modelo User está forzando actualmente
        $guards = ['web', 'sanctum'];

        foreach ($guards as $guard) {
            // Crear permisos para cada guard
            foreach ($permissions as $permissionName) {
                Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => $guard]);
            }

            // Crear roles para cada guard
            // Admin
            $adminRole = Role::firstOrCreate(['name' => 'role.admin', 'guard_name' => $guard]);
            // Asignar todos los permisos al admin
            $adminRole->syncPermissions(Permission::where('guard_name', $guard)->get());

            // Otros roles (solo estructura básica por ahora)
            $otherRoles = [
                'role.employee',
                'role.supervisor1',
                'role.candidatos',
                'role.lider',
                'role.call',
                'role.digitador'
            ];

            foreach ($otherRoles as $roleName) {
                Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
            }
        }

        // Asignar rol al usuario principal si existe
        // El usuario 1 suele ser el creado en UserSeeder o manualmente
        $user = User::first(); // O find(1)
        if ($user) {
            // El modelo User tiene $guard_name = 'sanctum' hardcoded
            // Por lo tanto, debemos asignar el rol que corresponda a 'sanctum'
            // O Spatie intentará buscar el rol 'role.admin' para el guard 'sanctum' automáticamente.
            
            // Verificamos si podemos asignar directamente
            // assignRole busca el rol por nombre. Si el usuario tiene guard 'sanctum', buscará 'role.admin' en 'sanctum'.
            // Como ya creamos 'role.admin' para 'sanctum', esto debería funcionar sin errores.
            if (! $user->hasRole('role.admin')) {
                $user->assignRole('role.admin');
            }
        }
    }
}
