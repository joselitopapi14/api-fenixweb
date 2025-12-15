<?php

namespace App\Http\Controllers\Web\Concepto;

use App\Http\Controllers\Controller;
use App\Models\Concepto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class ConceptoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $query = Concepto::with('empresa');

            // Filtrar por empresa si no es admin global
            if (!$user->esAdministradorGlobal()) {
                $empresasIds = $user->empresasActivas()->pluck('empresas.id');
                $query->where(function ($q) use ($empresasIds) {
                    $q->whereIn('empresa_id', $empresasIds)
                      ->orWhereNull('empresa_id'); // Incluir conceptos globales
                });
            }

            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = strtolower($request->search);
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$searchTerm}%"])
                      ->orWhereRaw('LOWER(descripcion) LIKE ?', ["%{$searchTerm}%"]);
                });
            }

            // Filtro por empresa (para admin global)
            if ($request->filled('empresa_id')) {
                if ($request->empresa_id === 'global') {
                    $query->whereNull('empresa_id');
                } else {
                    $query->where('empresa_id', $request->empresa_id);
                }
            }

            // Filtro por estado activo
            if ($request->filled('activo')) {
                $query->where('activo', $request->activo === '1');
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

            $conceptos = $query->paginate(10);

            // Para admin global, obtener lista de empresas para el filtro
            $empresas = null;
            if ($user->esAdministradorGlobal()) {
                $empresas = \App\Models\Empresa::activas()->orderBy('razon_social')->get();
            }

            if ($request->ajax()) {
                return view('conceptos.partials.conceptos-list-with-pagination', compact('conceptos'))->render();
            }

            return view('conceptos.index', compact('conceptos', 'empresas'));
        } catch (Exception $e) {
            Log::error('Error al listar conceptos: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar conceptos.'], 500);
            }
            return redirect()->back()->with('error', 'Error al cargar conceptos.');
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

        return view('conceptos.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Validación base
        $rules = [
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'activo' => 'boolean',
        ];

        // Si no es admin global, empresa_id es obligatorio para usuarios normales
        if (!$user->esAdministradorGlobal()) {
            $rules['empresa_id'] = 'required|exists:empresas,id';
        } else {
            $rules['empresa_id'] = 'nullable|exists:empresas,id';
        }

        $request->validate($rules);

        // Verificar unicidad del nombre considerando la empresa
        $existingQuery = Concepto::where('nombre', $request->nombre);

        if ($request->filled('empresa_id')) {
            $existingQuery->where('empresa_id', $request->empresa_id);
        } else {
            $existingQuery->whereNull('empresa_id');
        }

        if ($existingQuery->exists()) {
            return back()->withInput()->withErrors([
                'nombre' => 'Ya existe un concepto con este nombre en la empresa seleccionada.'
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
            Concepto::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'activo' => $request->boolean('activo', true),
                'empresa_id' => $request->empresa_id
            ]);
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Concepto creado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('conceptos.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear concepto: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al crear el concepto. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back()->withInput();
        }
    }

    public function show(Concepto $concepto)
    {
        return view('conceptos.show', compact('concepto'));
    }

    public function edit(Concepto $concepto)
    {
        $user = auth()->user();
        $empresas = null;

        if ($user->esAdministradorGlobal()) {
            $empresas = \App\Models\Empresa::activas()->orderBy('razon_social')->get();
        } else {
            $empresas = $user->empresasActivas;
        }

        return view('conceptos.edit', compact('concepto', 'empresas'));
    }

    public function update(Request $request, Concepto $concepto)
    {
        $user = auth()->user();

        // Validación base
        $rules = [
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'activo' => 'boolean',
        ];

        // Si no es admin global, empresa_id es obligatorio para usuarios normales
        if (!$user->esAdministradorGlobal()) {
            $rules['empresa_id'] = 'required|exists:empresas,id';
        } else {
            $rules['empresa_id'] = 'nullable|exists:empresas,id';
        }

        $request->validate($rules);

        // Verificar unicidad del nombre considerando la empresa
        $existingQuery = Concepto::where('nombre', $request->nombre)
                                ->where('id', '!=', $concepto->id);

        if ($request->filled('empresa_id')) {
            $existingQuery->where('empresa_id', $request->empresa_id);
        } else {
            $existingQuery->whereNull('empresa_id');
        }

        if ($existingQuery->exists()) {
            return back()->withInput()->withErrors([
                'nombre' => 'Ya existe un concepto con este nombre en la empresa seleccionada.'
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
            $concepto->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'activo' => $request->boolean('activo', true),
                'empresa_id' => $request->empresa_id
            ]);
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Concepto actualizado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('conceptos.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar concepto: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al actualizar el concepto. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back()->withInput();
        }
    }

    public function destroy(Concepto $concepto)
    {
        DB::beginTransaction();
        try {
            $concepto->delete();
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Concepto eliminado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('conceptos.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar concepto: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al eliminar el concepto. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back();
        }
    }
}
