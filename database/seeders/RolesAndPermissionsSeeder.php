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

        // Asignar rol admin a usuarios específicos (configurable via env)
        // Example: ADMIN_EMAILS="admin@acme.com,other@acme.com"
        $adminEmailsRaw = (string) env('ADMIN_EMAILS', '');
        $adminEmails = array_values(array_filter(array_map('trim', explode(',', $adminEmailsRaw))));

        foreach ($adminEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user && !$user->hasRole('role.admin')) {
                $user->assignRole('role.admin');
            }
        }

        // También asignar al primer usuario si existe y no está en la lista
        $firstUser = User::first();
        if ($firstUser && !in_array($firstUser->email, $adminEmails, true) && !$firstUser->hasRole('role.admin')) {
            $firstUser->assignRole('role.admin');
        }
    }
}
