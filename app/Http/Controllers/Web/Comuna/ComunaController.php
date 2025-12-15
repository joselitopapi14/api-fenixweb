<?php

namespace App\Http\Controllers\Web\Comuna;

use App\Models\Comuna;
use App\Models\Municipio;
use App\Models\Departamento;
use App\Models\Pais;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Exception;

class ComunaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Comuna::query();

        // Aplicar búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = strtolower($request->search);
            $query->where(function($q) use ($searchTerm) {
                $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereHas('municipio', function($q) use ($searchTerm) {
                      $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
                  })
                  ->orWhereHas('municipio.departamento', function($q) use ($searchTerm) {
                      $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
                  })
                  ->orWhereHas('municipio.departamento.pais', function($q) use ($searchTerm) {
                      $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
                  });
            });
        }

        // Filtro por país
        if ($request->has('pais_id') && !empty($request->pais_id)) {
            $query->whereHas('municipio.departamento.pais', function($q) use ($request) {
                $q->where('id', $request->pais_id);
            });
        }

        // Filtro por departamento
        if ($request->has('departamento_id') && !empty($request->departamento_id)) {
            $query->whereHas('municipio.departamento', function($q) use ($request) {
                $q->where('id', $request->departamento_id);
            });
        }

        // Filtro por municipio
        if ($request->has('municipio_id') && !empty($request->municipio_id)) {
            $query->where('municipio_id', $request->municipio_id);
        }

        // Aplicar relaciones necesarias
        $query->with(['municipio.departamento.pais', 'barrios']);

        // Ordenamiento por defecto
        $query->orderBy('nombre', 'asc');

        // Paginación
        $comunas = $query->paginate(12);

        // Respuesta AJAX
        if ($request->ajax()) {
            return view('comunas.partials.comunas-list-with-pagination', compact('comunas'))->render();
        }

        // Respuesta normal - primera carga
        $paises = Pais::orderBy('name')->get();
        $departamentos = Departamento::orderBy('name')->get();
        $municipios = Municipio::orderBy('name')->get();

        // Estadísticas para las cards
        $totalComunas = Comuna::count();
        $totalBarrios = Comuna::withCount('barrios')->get()->sum('barrios_count');

        return view('comunas.index', compact('comunas', 'paises', 'departamentos', 'municipios', 'totalComunas', 'totalBarrios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departamentos = Departamento::with('pais')->orderBy('name')->get();
        $municipios = Municipio::orderBy('name')->get();

        return view('comunas.create', compact('departamentos', 'municipios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'municipio_id' => 'required|exists:municipios,id',
        ]);

        DB::beginTransaction();
        try {
            Comuna::create($request->all());

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Comuna creada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('comunas.index');

        } catch (Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al crear la comuna. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Comuna $comuna)
    {
        // Cargar todas las relaciones necesarias
        $comuna->load([
            'municipio.departamento.pais',
            'barrios'
        ]);

        // Obtener estadísticas de la comuna
        $totalBarrios = $comuna->barrios->count();

        // Obtener comunas relacionadas (del mismo municipio)
        $comunasRelacionadas = Comuna::where('municipio_id', $comuna->municipio_id)
            ->where('id', '!=', $comuna->id)
            ->with(['barrios'])
            ->orderBy('nombre')
            ->limit(5)
            ->get();

        return view('comunas.show', compact('comuna', 'totalBarrios', 'comunasRelacionadas'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Comuna $comuna)
    {
        $comuna->load('municipio.departamento');
        $departamentos = Departamento::with('pais')->orderBy('name')->get();
        $municipios = Municipio::where('departamento_id', $comuna->municipio->departamento_id)->orderBy('name')->get();

        return view('comunas.edit', compact('comuna', 'departamentos', 'municipios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comuna $comuna)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'municipio_id' => 'required|exists:municipios,id',
        ]);

        DB::beginTransaction();
        try {
            $comuna->update($request->all());

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Comuna actualizada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('comunas.index');

        } catch (Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al actualizar la comuna. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comuna $comuna)
    {
        DB::beginTransaction();
        try {
            $comuna->delete();

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Comuna eliminada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('comunas.index');

        } catch (Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al eliminar la comuna. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back();
        }
    }
}
