<?php

namespace App\Http\Controllers\Web\TipoProducto;

use App\Http\Controllers\Controller;
use App\Models\TipoProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class TipoProductoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $query = TipoProducto::with('empresa');

            // Filtrar por empresa si no es admin global
            if (!$user->esAdministradorGlobal()) {
                $empresasIds = $user->empresasActivas()->pluck('empresas.id');
                $query->where(function ($q) use ($empresasIds) {
                    $q->whereIn('empresa_id', $empresasIds)
                      ->orWhereNull('empresa_id'); // Incluir tipos globales
                });
            }

            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = strtolower($request->search);
                $query->whereRaw('LOWER(nombre) LIKE ?', ["%{$searchTerm}%"]);
            }

            // Filtro por empresa (para admin global)
            if ($request->filled('empresa_id')) {
                if ($request->empresa_id === 'global') {
                    $query->whereNull('empresa_id');
                } else {
                    $query->where('empresa_id', $request->empresa_id);
                }
            }

            $sortBy = $request->get('sort', 'nombre_asc');
            switch ($sortBy) {
                case 'nombre_desc':
                    $query->orderBy('nombre', 'desc');
                    break;
                case 'created_at_desc':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'created_at_asc':
                    $query->orderBy('created_at', 'asc');
                    break;
                default:
                    $query->orderBy('nombre', 'asc');
                    break;
            }

            $tipos = $query->paginate(10);

            // Para admin global, obtener lista de empresas para el filtro
            $empresas = null;
            if ($user->esAdministradorGlobal()) {
                $empresas = \App\Models\Empresa::activas()->orderBy('razon_social')->get();
            }

            if ($request->ajax()) {
                return view('tipo-productos.partials.tipo-productos-list-with-pagination', compact('tipos'))->render();
            }

            return view('tipo-productos.index', compact('tipos', 'empresas'));
        } catch (Exception $e) {
            Log::error('Error al listar tipos de producto: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar tipos de producto.'], 500);
            }
            return redirect()->back()->with('error', 'Error al cargar tipos de producto.');
        }
    }

    public function create()
    {
        $user = auth()->user();
        $empresas = null;

        if ($user->esAdministradorGlobal()) {
            $empresas = \App\Models\Empresa::activas()->orderBy('razon_social')->get();
        } else {
            $empresas = $user->empresasActivas;
        }

        return view('tipo-productos.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Validación base
        $rules = [
            'nombre' => 'required|string|max:100',
        ];

        // Si no es admin global, empresa_id es obligatorio para usuarios normales
        if (!$user->esAdministradorGlobal()) {
            $rules['empresa_id'] = 'required|exists:empresas,id';
        } else {
            $rules['empresa_id'] = 'nullable|exists:empresas,id';
        }

        $request->validate($rules);

        // Verificar unicidad del nombre considerando la empresa
        $existingQuery = TipoProducto::where('nombre', $request->nombre);

        if ($request->filled('empresa_id')) {
            $existingQuery->where('empresa_id', $request->empresa_id);
        } else {
            $existingQuery->whereNull('empresa_id');
        }

        if ($existingQuery->exists()) {
            return back()->withInput()->withErrors([
                'nombre' => 'Ya existe un tipo de producto con este nombre en la empresa seleccionada.'
            ]);
        }

        // Verificar acceso a la empresa
        if ($request->filled('empresa_id') && !$user->esAdministradorGlobal()) {
            if (!$user->puedeAccederAEmpresa($request->empresa_id)) {
                abort(403, 'No tienes acceso a esta empresa.');
            }
        }

        DB::beginTransaction();
        try {
            TipoProducto::create([
                'nombre' => $request->nombre,
                'empresa_id' => $request->empresa_id
            ]);
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Tipo de producto creado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('tipo-productos.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear tipo de producto: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al crear el tipo de producto. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back()->withInput();
        }
    }

    public function show(TipoProducto $tipoProducto)
    {
        return view('tipo-productos.show', compact('tipoProducto'));
    }

    public function edit(TipoProducto $tipoProducto)
    {
        $user = auth()->user();
        $empresas = null;

        if ($user->esAdministradorGlobal()) {
            $empresas = \App\Models\Empresa::activas()->orderBy('razon_social')->get();
        } else {
            $empresas = $user->empresasActivas;
        }

        return view('tipo-productos.edit', compact('tipoProducto', 'empresas'));
    }

    public function update(Request $request, TipoProducto $tipoProducto)
    {
        $user = auth()->user();

        // Validación base
        $rules = [
            'nombre' => 'required|string|max:100',
        ];

        // Si no es admin global, empresa_id es obligatorio para usuarios normales
        if (!$user->esAdministradorGlobal()) {
            $rules['empresa_id'] = 'required|exists:empresas,id';
        } else {
            $rules['empresa_id'] = 'nullable|exists:empresas,id';
        }

        $request->validate($rules);

        // Verificar unicidad del nombre considerando la empresa
        $existingQuery = TipoProducto::where('nombre', $request->nombre)
                                    ->where('id', '!=', $tipoProducto->id);

        if ($request->filled('empresa_id')) {
            $existingQuery->where('empresa_id', $request->empresa_id);
        } else {
            $existingQuery->whereNull('empresa_id');
        }

        if ($existingQuery->exists()) {
            return back()->withInput()->withErrors([
                'nombre' => 'Ya existe un tipo de producto con este nombre en la empresa seleccionada.'
            ]);
        }

        // Verificar acceso a la empresa
        if ($request->filled('empresa_id') && !$user->esAdministradorGlobal()) {
            if (!$user->puedeAccederAEmpresa($request->empresa_id)) {
                abort(403, 'No tienes acceso a esta empresa.');
            }
        }

        DB::beginTransaction();
        try {
            $tipoProducto->update([
                'nombre' => $request->nombre,
                'empresa_id' => $request->empresa_id
            ]);
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Tipo de producto actualizado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('tipo-productos.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tipo de producto: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al actualizar el tipo de producto. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back()->withInput();
        }
    }

    public function destroy(TipoProducto $tipoProducto)
    {
        DB::beginTransaction();
        try {
            $tipoProducto->delete();
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Tipo de producto eliminado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('tipo-productos.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar tipo de producto: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al eliminar el tipo de producto. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back();
        }
    }
}
