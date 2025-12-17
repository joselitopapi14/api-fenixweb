<?php

namespace App\Http\Controllers\Web\Sede;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Sede;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SedeController extends Controller
{
    /**
     * Display a listing of the resource for a specific empresa.
     */
    public function index(Request $request, Empresa $empresa)
    {
        try {
            // Verificar acceso
            $user = auth()->user();
            if (!$user->esAdministradorGlobal() && !$user->esAdministradorDeEmpresa($empresa->id)) {
                abort(403, 'No tienes permisos para ver las sedes de esta empresa.');
            }

            // Construir query base
            $query = $empresa->sedes()
                ->with(['departamento', 'municipio', 'comuna', 'barrio']);

            // Aplicar filtros de búsqueda
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('direccion', 'like', "%{$search}%")
                      ->orWhere('telefono', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Aplicar filtro de estado
            if ($request->filled('estado')) {
                if ($request->estado === 'activa') {
                    $query->where('activa', true);
                } elseif ($request->estado === 'inactiva') {
                    $query->where('activa', false);
                }
            }

            // Aplicar filtro de tipo
            if ($request->filled('tipo')) {
                if ($request->tipo === 'principal') {
                    $query->where('es_principal', true);
                } elseif ($request->tipo === 'sucursal') {
                    $query->where('es_principal', false);
                }
            }

            // Ordenamiento
            $orderBy = $request->get('order_by', 'es_principal');
            $order = $request->get('order', 'desc');

            if ($orderBy === 'es_principal') {
                $query->orderBy('es_principal', 'desc')->orderBy('nombre');
            } else {
                $query->orderBy($orderBy, $order);
            }

            $sedes = $query->paginate(10)->appends($request->query());

            return view('sedes.index', compact('empresa', 'sedes'));
        } catch (Exception $e) {
            Log::error('Error al listar sedes: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al cargar las sedes.',
                'status' => 'error'
            ]);

            return redirect()->back();
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Empresa $empresa)
    {
        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->esAdministradorDeEmpresa($empresa->id)) {
            abort(403, 'No tienes permisos para crear sedes para esta empresa.');
        }

        $departamentos = Departamento::orderBy('name')->get();

        return view('sedes.create', compact('empresa', 'departamentos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Empresa $empresa)
    {
        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->esAdministradorDeEmpresa($empresa->id)) {
            abort(403, 'No tienes permisos para crear sedes para esta empresa.');
        }

        $request->validate([
            'nombre' => 'required|string',
            'direccion' => 'required|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'comuna_id' => 'nullable|exists:comunas,id',
            'barrio_id' => 'nullable|exists:barrios,id',
            'es_principal' => 'boolean',
            'activa' => 'boolean',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Si es principal, quitar principal a las demás sedes
            if ($request->boolean('es_principal')) {
                $empresa->sedes()->update(['es_principal' => false]);
            }

            $sede = $empresa->sedes()->create($request->all());

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Sede creada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('sedes.index', $empresa);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error al crear sede: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al crear la sede. Inténtalo de nuevo.',
                'status' => 'error'
            ]);

            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Empresa $empresa, Sede $sede)
    {
        // Verificar que la sede pertenece a la empresa
        if ($sede->empresa_id !== $empresa->id) {
            abort(404);
        }

        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->esAdministradorDeEmpresa($empresa->id)) {
            abort(403, 'No tienes permisos para ver esta sede.');
        }

        $sede->load(['departamento', 'municipio', 'comuna', 'barrio']);

        return view('sedes.show', compact('empresa', 'sede'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Empresa $empresa, Sede $sede)
    {
        // Verificar que la sede pertenece a la empresa
        if ($sede->empresa_id !== $empresa->id) {
            abort(404);
        }

        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->esAdministradorDeEmpresa($empresa->id)) {
            abort(403, 'No tienes permisos para editar esta sede.');
        }

        $departamentos = Departamento::orderBy('name')->get();
        $sede->load(['departamento', 'municipio', 'comuna', 'barrio']);

        return view('sedes.edit', compact('empresa', 'sede', 'departamentos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Empresa $empresa, Sede $sede)
    {
        // Verificar que la sede pertenece a la empresa
        if ($sede->empresa_id !== $empresa->id) {
            abort(404);
        }

        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->esAdministradorDeEmpresa($empresa->id)) {
            abort(403, 'No tienes permisos para editar esta sede.');
        }

        $request->validate([
            'nombre' => 'required|string',
            'direccion' => 'required|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'comuna_id' => 'nullable|exists:comunas,id',
            'barrio_id' => 'nullable|exists:barrios,id',
            'es_principal' => 'boolean',
            'activa' => 'boolean',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Si es principal, quitar principal a las demás sedes
            if ($request->boolean('es_principal') && !$sede->es_principal) {
                $empresa->sedes()->where('id', '!=', $sede->id)->update(['es_principal' => false]);
            }

            $sede->update($request->all());

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Sede actualizada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('sedes.index', $empresa);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error al actualizar sede: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al actualizar la sede. Inténtalo de nuevo.',
                'status' => 'error'
            ]);

            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Empresa $empresa, Sede $sede)
    {
        // Verificar que la sede pertenece a la empresa
        if ($sede->empresa_id !== $empresa->id) {
            abort(404);
        }

        // Verificar acceso
        $user = auth()->user();
        if (!$user->esAdministradorGlobal() && !$user->esAdministradorDeEmpresa($empresa->id)) {
            abort(403, 'No tienes permisos para eliminar esta sede.');
        }

        try {
            // No permitir eliminar la sede principal si es la única
            if ($sede->es_principal && $empresa->sedes()->count() === 1) {
                session()->flash('toast', [
                    'title' => 'Error',
                    'message' => 'No se puede eliminar la única sede de la empresa.',
                    'status' => 'error'
                ]);

                return redirect()->back();
            }

            $sede->delete();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Sede eliminada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('sedes.index', $empresa);
        } catch (Exception $e) {
            Log::error('Error al eliminar sede: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al eliminar la sede. Inténtalo de nuevo.',
                'status' => 'error'
            ]);

            return redirect()->back();
        }
    }
}
