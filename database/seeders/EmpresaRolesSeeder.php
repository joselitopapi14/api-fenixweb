<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class EmpresaRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos específicos para empresas
        $empresaPermissions = [
            'empresas.view' => 'Ver empresas',
            'empresas.create' => 'Crear empresas',
            'empresas.edit' => 'Editar empresas',
            'empresas.delete' => 'Eliminar empresas',
            'empresas.manage_users' => 'Gestionar usuarios de empresa',
        ];

        foreach ($empresaPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        // Crear rol para administrador de empresa
        $adminEmpresaRole = Role::firstOrCreate([
            'name' => 'admin_empresa',
            'guard_name' => 'web'
        ]);

        // Asignar permisos al rol admin_empresa
        $adminEmpresaRole->givePermissionTo([
            'empresas.view',
            'empresas.edit',
            'empresas.manage_users',
            'users.view',
            'users.create',
            'users.edit',
        ]);

        // Crear rol para empleado de empresa
        $empleadoRole = Role::firstOrCreate([
            'name' => 'empleado_empresa',
            'guard_name' => 'web'
        ]);

        // Asignar permisos limitados al empleado
        $empleadoRole->givePermissionTo([
            'users.view',
        ]);

        // Actualizar permisos del admin global
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'empresas.view',
                'empresas.create',
                'empresas.edit',
                'empresas.delete',
                'empresas.manage_users',
            ]);
        }

        $this->command->info('Roles y permisos de empresas creados exitosamente.');
    }
}
