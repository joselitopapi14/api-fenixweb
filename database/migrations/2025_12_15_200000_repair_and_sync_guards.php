<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration repairs the database by ensuring all Roles and Permissions 
        // exist for BOTH 'web' and 'sanctum' guards.
        // This satisfies the requirement to support Sanctum ('everything be sanctum')
        // while preserving the functionality of the monolithic app (which uses 'web').

        // 1. Ensure all Permissions exist for both guards
        $permissions = DB::table('permissions')->get();
        $uniquePermissions = $permissions->unique('name')->pluck('name');

        foreach ($uniquePermissions as $permName) {
            foreach (['web', 'sanctum'] as $guard) {
                if (!DB::table('permissions')->where('name', $permName)->where('guard_name', $guard)->exists()) {
                    DB::table('permissions')->insert([
                        'name' => $permName,
                        'guard_name' => $guard,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 2. Ensure all Roles exist for both guards
        $roles = DB::table('roles')->get();
        $uniqueRoles = $roles->unique('name')->pluck('name');

        foreach ($uniqueRoles as $roleName) {
            foreach (['web', 'sanctum'] as $guard) {
                if (!DB::table('roles')->where('name', $roleName)->where('guard_name', $guard)->exists()) {
                    DB::table('roles')->insert([
                        'name' => $roleName,
                        'guard_name' => $guard,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 3. Sync permissions to roles (Cross-Copy)
        // If Role 'Admin' (web) has Permission 'Edit' (web), ensure 'Admin' (sanctum) has 'Edit' (sanctum)
        $rolePermissions = DB::table('role_has_permissions')
            ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->select('roles.name as role_name', 'permissions.name as perm_name')
            ->distinct()
            ->get();

        foreach ($rolePermissions as $rp) {
            foreach (['web', 'sanctum'] as $guard) {
                $roleId = DB::table('roles')->where('name', $rp->role_name)->where('guard_name', $guard)->value('id');
                $permId = DB::table('permissions')->where('name', $rp->perm_name)->where('guard_name', $guard)->value('id');

                if ($roleId && $permId) {
                    $exists = DB::table('role_has_permissions')
                        ->where('permission_id', $permId)
                        ->where('role_id', $roleId)
                        ->exists();
                    
                    if (!$exists) {
                        DB::table('role_has_permissions')->insert([
                            'permission_id' => $permId,
                            'role_id' => $roleId
                        ]);
                    }
                }
            }
        }

        // 4. Sync roles to users (Cross-Copy)
        // If User 1 has 'Admin' (web), ensure User 1 has 'Admin' (sanctum)
        $modelRoles = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('model_has_roles.model_type', 'model_has_roles.model_id', 'roles.name as role_name')
            ->distinct()
            ->get();

        foreach ($modelRoles as $mr) {
            foreach (['web', 'sanctum'] as $guard) {
                $roleId = DB::table('roles')->where('name', $mr->role_name)->where('guard_name', $guard)->value('id');

                if ($roleId) {
                    $exists = DB::table('model_has_roles')
                        ->where('role_id', $roleId)
                        ->where('model_type', $mr->model_type)
                        ->where('model_id', $mr->model_id)
                        ->exists();

                    if (!$exists) {
                        DB::table('model_has_roles')->insert([
                            'role_id' => $roleId,
                            'model_type' => $mr->model_type,
                            'model_id' => $mr->model_id
                        ]);
                    }
                }
            }
        }
        
        // 5. Sync direct permissions to users (Cross-Copy)
        $modelPerms = DB::table('model_has_permissions')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->select('model_has_permissions.model_type', 'model_has_permissions.model_id', 'permissions.name as perm_name')
            ->distinct()
            ->get();
            
        foreach ($modelPerms as $mp) {
            foreach (['web', 'sanctum'] as $guard) {
                $permId = DB::table('permissions')->where('name', $mp->perm_name)->where('guard_name', $guard)->value('id');
                
                if ($permId) {
                    $exists = DB::table('model_has_permissions')
                        ->where('permission_id', $permId)
                        ->where('model_type', $mp->model_type)
                        ->where('model_id', $mp->model_id)
                        ->exists();
                        
                    if (!$exists) {
                        DB::table('model_has_permissions')->insert([
                            'permission_id' => $permId,
                            'model_type' => $mp->model_type,
                            'model_id' => $mp->model_id
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No automatic rollback as it's hard to distinguish what was original vs what was copied.
    }
};
