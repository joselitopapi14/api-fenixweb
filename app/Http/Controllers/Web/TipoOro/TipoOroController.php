<?php

namespace App\Http\Controllers\Web\TipoOro;

use App\Http\Controllers\Controller;
use App\Models\TipoOro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class TipoOroController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $query = TipoOro::with('empresa');

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
                return view('tipo-oros.partials.tipo-oros-list-with-pagination', compact('tipos'))->render();
            }

            return view('tipo-oros.index', compact('tipos', 'empresas'));
        } catch (Exception $e) {
            Log::error('Error al listar tipos de oro: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar tipos de oro.'], 500);
            }
            return redirect()->back()->with('error', 'Error al cargar tipos de oro.');
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

        return view('tipo-oros.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Validación base
        $rules = [
            'nombre' => 'required|string|max:100',
            'valor_de_mercado' => 'required|numeric|min:0',
            'observacion' => 'nullable|string|max:1000',
        ];

        // Si no es admin global, empresa_id es obligatorio para usuarios normales
        if (!$user->esAdministradorGlobal()) {
            $rules['empresa_id'] = 'required|exists:empresas,id';
        } else {
            $rules['empresa_id'] = 'nullable|exists:empresas,id';
        }

        $request->validate($rules);

        // Verificar unicidad del nombre considerando la empresa
        $existingQuery = TipoOro::where('nombre', $request->nombre);

        if ($request->filled('empresa_id')) {
            $existingQuery->where('empresa_id', $request->empresa_id);
        } else {
            $existingQuery->whereNull('empresa_id');
        }

        if ($existingQuery->exists()) {
            return back()->withInput()->withErrors([
                'nombre' => 'Ya existe un tipo de oro con este nombre en la empresa seleccionada.'
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
            TipoOro::create([
                'nombre' => $request->nombre,
                'valor_de_mercado' => $request->valor_de_mercado,
                'observacion' => $request->observacion,
                'empresa_id' => $request->empresa_id,
            ]);
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Tipo de oro creado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('tipo-oros.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear tipo de oro: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al crear el tipo de oro. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back()->withInput();
        }
    }

    public function show(TipoOro $tipoOro)
    {
        return view('tipo-oros.show', compact('tipoOro'));
    }

    public function edit(TipoOro $tipoOro)
    {
        $user = auth()->user();
        $empresas = null;

        if ($user->esAdministradorGlobal()) {
            $empresas = \App\Models\Empresa::activas()->orderBy('razon_social')->get();
        } else {
            $empresas = $user->empresasActivas;
        }

        return view('tipo-oros.edit', compact('tipoOro', 'empresas'));
    }

    public function update(Request $request, TipoOro $tipoOro)
    {
        $user = auth()->user();

        // Validación base
        $rules = [
            'nombre' => 'required|string|max:100',
            'valor_de_mercado' => 'required|numeric|min:0',
            'observacion' => 'nullable|string|max:1000',
        ];

        // Si no es admin global, empresa_id es obligatorio para usuarios normales
        if (!$user->esAdministradorGlobal()) {
            $rules['empresa_id'] = 'required|exists:empresas,id';
        } else {
            $rules['empresa_id'] = 'nullable|exists:empresas,id';
        }

        $request->validate($rules);

        // Verificar unicidad del nombre considerando la empresa
        $existingQuery = TipoOro::where('nombre', $request->nombre)
                                ->where('id', '!=', $tipoOro->id);

        if ($request->filled('empresa_id')) {
            $existingQuery->where('empresa_id', $request->empresa_id);
        } else {
            $existingQuery->whereNull('empresa_id');
        }

        if ($existingQuery->exists()) {
            return back()->withInput()->withErrors([
                'nombre' => 'Ya existe un tipo de oro con este nombre en la empresa seleccionada.'
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
            $tipoOro->update([
                'nombre' => $request->nombre,
                'valor_de_mercado' => $request->valor_de_mercado,
                'observacion' => $request->observacion,
                'empresa_id' => $request->empresa_id,
            ]);
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Tipo de oro actualizado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('tipo-oros.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tipo de oro: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al actualizar el tipo de oro. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back()->withInput();
        }
    }

    public function destroy(TipoOro $tipoOro)
    {
        DB::beginTransaction();
        try {
            $tipoOro->delete();
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Tipo de oro eliminado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('tipo-oros.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar tipo de oro: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al eliminar el tipo de oro. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back();
        }
    }
}
