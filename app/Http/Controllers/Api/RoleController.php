<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Exception;

class RoleController extends BaseController
{
    public function __construct()
    {
        // Protegemos con guard 'sanctum' (por default en API) y permisos 'web' (si aún los usas así) o 'sanctum'
        $this->middleware('permission:roles.view')->only(['index', 'show', 'permissions']);
        $this->middleware('permission:roles.create')->only(['store']);
        $this->middleware('permission:roles.edit')->only(['update', 'syncPermissions']);
        $this->middleware('permission:roles.delete')->only(['destroy']);
    }

    public function index()
    {
        $roles = Role::with('permissions')->paginate(10);
        return $this->sendResponse($roles, 'Roles retrieved successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array'
        ]);

        DB::beginTransaction();
        try {
            // Forzamos guard sanctum para nuevos roles
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'sanctum'
            ]);

            if ($request->has('permissions')) {
                // Aseguramos que los permisos existan en sanctum
                $perms = Permission::whereIn('id', $request->permissions)->where('guard_name', 'sanctum')->get(); 
                $role->syncPermissions($perms);
            }

            DB::commit();
            return $this->sendResponse($role->load('permissions'), 'Role created successfully');

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show($id)
    {
        $role = Role::with('permissions')->find($id);
        if (!$role) return $this->sendError('Role not found');
        
        return $this->sendResponse($role, 'Role retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $role = Role::find($id);
        if (!$role) return $this->sendError('Role not found');

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'array'
        ]);

        DB::beginTransaction();
        try {
            $role->update(['name' => $request->name]);

            if ($request->has('permissions')) {
                // Igual, filtrar por guard sanctum
                $perms = Permission::whereIn('id', $request->permissions)->where('guard_name', 'sanctum')->get();
                $role->syncPermissions($perms);
            }

            DB::commit();
            return $this->sendResponse($role->load('permissions'), 'Role updated successfully');

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy($id)
    {
        $role = Role::find($id);
        if (!$role) return $this->sendError('Role not found');

        // Evitar borrar admin
        if ($role->name === 'role.admin') {
            return $this->sendError('Cannot delete super admin role', [], 403);
        }

        $role->delete();
        return $this->sendResponse([], 'Role deleted successfully');
    }

    // Endpoint extra para listar permisos disponibles
    public function allPermissions()
    {
        $permissions = Permission::where('guard_name', 'sanctum')->get();
        return $this->sendResponse($permissions, 'Permissions retrieved successfully');
    }
}
