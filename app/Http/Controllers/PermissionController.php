<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Permission::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $permissions = $query->orderBy('name', 'ASC')->paginate(10);

        return view('permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions',
        ]);

        DB::beginTransaction();
        try {
            Permission::create(['name' => $request->name]);

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Permiso creado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('permissions.index');

        } catch (Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al crear el permiso. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    public function show(Permission $permission)
    {
        return view('permissions.show', compact('permission'));
    }

    public function edit(Permission $permission)
    {
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
        ]);

        DB::beginTransaction();
        try {
            $permission->update(['name' => $request->name]);

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Permiso actualizado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('permissions.index');

        } catch (Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al actualizar el permiso. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    public function destroy(Permission $permission)
    {
        DB::beginTransaction();
        try {
            $permission->delete();

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Permiso eliminado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('permissions.index');

        } catch (Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al eliminar el permiso. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back();
        }
    }
}
