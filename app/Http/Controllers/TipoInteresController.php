<?php

namespace App\Http\Controllers;

use App\Models\TipoInteres;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TipoInteresController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Construir query base
        $query = TipoInteres::with('empresa');

        // Aplicar búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('nombre', 'like', "%{$searchTerm}%")
                  ->orWhere('descripcion', 'like', "%{$searchTerm}%")
                  ->orWhere('porcentaje', 'like', "%{$searchTerm}%");
            });
        }

        // Filtrar por empresa específica si se proporciona en la petición
        if ($request->filled('empresa_id')) {
            if ($request->empresa_id === 'global') {
                $query->whereNull('empresa_id');
            } else {
                $query->where('empresa_id', $request->empresa_id);
            }
        } else {
            // Filtrar por empresa según el rol del usuario
            if (!$user->esAdministradorGlobal()) {
                $empresasUsuario = $user->empresasActivas->pluck('id');
                // Incluir tipos globales y tipos de las empresas del usuario
                $query->where(function($q) use ($empresasUsuario) {
                    $q->whereNull('empresa_id') // Tipos globales
                      ->orWhereIn('empresa_id', $empresasUsuario); // Tipos de empresas del usuario
                });
            }
        }

        // Filtro por estado activo/inactivo
        if ($request->has('activo') && $request->activo !== '') {
            if ($request->activo === '1') {
                $query->where('activo', true);
            } else {
                $query->where('activo', false);
            }
        }

        // Aplicar ordenamiento
        $sortBy = $request->get('sort', 'nombre_asc');
        switch ($sortBy) {
            case 'nombre_desc':
                $query->orderBy('nombre', 'desc');
                break;
            case 'porcentaje_asc':
                $query->orderBy('porcentaje', 'asc');
                break;
            case 'porcentaje_desc':
                $query->orderBy('porcentaje', 'desc');
                break;
            case 'empresa_asc':
                $query->orderBy('empresa_id', 'asc');
                break;
            case 'empresa_desc':
                $query->orderBy('empresa_id', 'desc');
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

        // Solo tipos activos para peticiones de selección (autocompletado/select)
        if ($request->filled('ajax') && $request->ajax === 'select') {
            $tiposInteres = $query->activos()
                                 ->orderBy('nombre')
                                 ->get(['id', 'nombre', 'porcentaje', 'empresa_id']);

            return response()->json($tiposInteres);
        }

        $tiposInteres = $query->paginate(12);

        // Respuesta AJAX para filtrado/paginación
        if ($request->ajax()) {
            return view('tipos-interes.partials.tipos-interes-list', compact('tiposInteres'))->render();
        }

        // Obtener empresas para el filtro
        $empresas = null;
        if ($user->esAdministradorGlobal()) {
            $empresas = Empresa::where('activa', true)->orderBy('razon_social')->get();
        } else {
            $empresas = $user->empresasActivas;
        }

        // Estadísticas
        $totalTipos = TipoInteres::when(!$user->esAdministradorGlobal(), function($q) use ($user) {
            $empresasUsuario = $user->empresasActivas->pluck('id');
            $q->where(function($subQ) use ($empresasUsuario) {
                $subQ->whereNull('empresa_id')
                     ->orWhereIn('empresa_id', $empresasUsuario);
            });
        })->count();

        $tiposActivos = TipoInteres::when(!$user->esAdministradorGlobal(), function($q) use ($user) {
            $empresasUsuario = $user->empresasActivas->pluck('id');
            $q->where(function($subQ) use ($empresasUsuario) {
                $subQ->whereNull('empresa_id')
                     ->orWhereIn('empresa_id', $empresasUsuario);
            });
        })->activos()->count();

        $promedioInteres = TipoInteres::when(!$user->esAdministradorGlobal(), function($q) use ($user) {
            $empresasUsuario = $user->empresasActivas->pluck('id');
            $q->where(function($subQ) use ($empresasUsuario) {
                $subQ->whereNull('empresa_id')
                     ->orWhereIn('empresa_id', $empresasUsuario);
            });
        })->activos()->avg('porcentaje') ?? 0;

        return view('tipos-interes.index', compact(
            'tiposInteres',
            'empresas',
            'totalTipos',
            'tiposActivos',
            'promedioInteres'
        ));
    }

    public function create()
    {
        $user = Auth::user();

        // Obtener empresas disponibles según el rol del usuario
        if ($user->esAdministradorGlobal()) {
            $empresas = Empresa::where('activa', true)->orderBy('razon_social')->get();
        } else {
            $empresas = $user->empresasActivas;
        }

        return view('tipos-interes.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'nombre' => 'required|string|max:255',
            'porcentaje' => 'required|numeric|min:0|max:999.99',
            'empresa_id' => 'nullable|exists:empresas,id', // Ahora es nullable para tipos globales
            'activo' => 'boolean',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        // Si no es admin global, no puede crear tipos globales
        if (!$user->esAdministradorGlobal() && is_null($request->empresa_id)) {
            abort(403, 'No tienes permisos para crear tipos de interés globales.');
        }

        // Verificar que el usuario puede crear tipos de interés para esta empresa (si se especifica)
        if ($request->empresa_id && !$user->esAdministradorGlobal() && !$user->empresasActivas->contains($request->empresa_id)) {
            abort(403, 'No tienes permisos para crear tipos de interés en esta empresa.');
        }

        // Verificar unicidad del nombre por empresa (incluyendo tipos globales)
        $exists = TipoInteres::where('empresa_id', $request->empresa_id)
                            ->where('nombre', $request->nombre)
                            ->exists();

        if ($exists) {
            $mensaje = $request->empresa_id
                ? 'Ya existe un tipo de interés con este nombre en la empresa seleccionada.'
                : 'Ya existe un tipo de interés global con este nombre.';

            session()->flash('toast', [
                'title' => 'Error de validación',
                'message' => $mensaje,
                'status' => 'error'
            ]);

            return back()->withInput();
        }

        TipoInteres::create($request->all());

        session()->flash('toast', [
            'title' => '¡Éxito!',
            'message' => 'Tipo de interés creado exitosamente.',
            'status' => 'success'
        ]);

        return redirect()->route('tipos-interes.index');
    }

    public function show(TipoInteres $tipoInteres)
    {
        $user = Auth::user();

        // Verificar acceso
        if (!$user->esAdministradorGlobal()) {
            // Si es tipo global, permitir acceso a todos
            // Si tiene empresa, verificar que el usuario tenga acceso a esa empresa
            if ($tipoInteres->empresa_id && !$user->empresasActivas->contains($tipoInteres->empresa_id)) {
                abort(403, 'No tienes permisos para ver este tipo de interés.');
            }
        }

        $tipoInteres->load('empresa');

        return view('tipos-interes.show', compact('tipoInteres'));
    }

    public function edit(TipoInteres $tipoInteres)
    {
        $user = Auth::user();

        // Verificar acceso
        if (!$user->esAdministradorGlobal()) {
            // Solo admin global puede editar tipos globales
            if (is_null($tipoInteres->empresa_id)) {
                abort(403, 'No tienes permisos para editar tipos de interés globales.');
            }
            // Para tipos con empresa, verificar acceso
            if ($tipoInteres->empresa_id && !$user->empresasActivas->contains($tipoInteres->empresa_id)) {
                abort(403, 'No tienes permisos para editar este tipo de interés.');
            }
        }

        // Obtener empresas disponibles según el rol del usuario
        if ($user->esAdministradorGlobal()) {
            $empresas = Empresa::where('activa', true)->orderBy('razon_social')->get();
        } else {
            $empresas = $user->empresasActivas;
        }

        return view('tipos-interes.edit', compact('tipoInteres', 'empresas'));
    }

    public function update(Request $request, TipoInteres $tipoInteres)
    {
        $user = Auth::user();

        // Verificar acceso
        if (!$user->esAdministradorGlobal()) {
            // Solo admin global puede editar tipos globales
            if (is_null($tipoInteres->empresa_id)) {
                abort(403, 'No tienes permisos para editar tipos de interés globales.');
            }
            // Para tipos con empresa, verificar acceso
            if ($tipoInteres->empresa_id && !$user->empresasActivas->contains($tipoInteres->empresa_id)) {
                abort(403, 'No tienes permisos para actualizar este tipo de interés.');
            }
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'porcentaje' => 'required|numeric|min:0|max:999.99',
            'empresa_id' => 'nullable|exists:empresas,id', // Ahora es nullable
            'activo' => 'boolean',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        // Si no es admin global, no puede crear tipos globales
        if (!$user->esAdministradorGlobal() && is_null($request->empresa_id)) {
            abort(403, 'No tienes permisos para crear tipos de interés globales.');
        }

        // Verificar que el usuario puede actualizar tipos de interés para esta empresa (si se especifica)
        if ($request->empresa_id && !$user->esAdministradorGlobal() && !$user->empresasActivas->contains($request->empresa_id)) {
            abort(403, 'No tienes permisos para asignar tipos de interés a esta empresa.');
        }

        // Verificar unicidad del nombre por empresa (excluyendo el actual)
        $exists = TipoInteres::where('empresa_id', $request->empresa_id)
                            ->where('nombre', $request->nombre)
                            ->where('id', '!=', $tipoInteres->id)
                            ->exists();

        if ($exists) {
            $mensaje = $request->empresa_id
                ? 'Ya existe un tipo de interés con este nombre en la empresa seleccionada.'
                : 'Ya existe un tipo de interés global con este nombre.';

            session()->flash('toast', [
                'title' => 'Error de validación',
                'message' => $mensaje,
                'status' => 'error'
            ]);

            return back()->withInput();
        }

        $tipoInteres->update($request->all());

        session()->flash('toast', [
            'title' => '¡Éxito!',
            'message' => 'Tipo de interés actualizado exitosamente.',
            'status' => 'success'
        ]);

        return redirect()->route('tipos-interes.index');
    }

    public function destroy(TipoInteres $tipoInteres)
    {
        $user = Auth::user();

        // Verificar acceso
        if (!$user->esAdministradorGlobal()) {
            // Solo admin global puede eliminar tipos globales
            if (is_null($tipoInteres->empresa_id)) {
                abort(403, 'No tienes permisos para eliminar tipos de interés globales.');
            }
            // Para tipos con empresa, verificar acceso
            if ($tipoInteres->empresa_id && !$user->empresasActivas->contains($tipoInteres->empresa_id)) {
                abort(403, 'No tienes permisos para eliminar este tipo de interés.');
            }
        }

        $tipoInteres->delete();

        session()->flash('toast', [
            'title' => '¡Éxito!',
            'message' => 'Tipo de interés eliminado exitosamente.',
            'status' => 'success'
        ]);

        return redirect()->route('tipos-interes.index');
    }
}
