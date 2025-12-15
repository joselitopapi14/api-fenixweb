<?php

namespace App\Http\Controllers\Web\Barrio;

use App\Models\Barrio;
use App\Models\Comuna;
use App\Models\Municipio;
use App\Models\Departamento;
use App\Models\Pais;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Exception;

class BarrioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Barrio::query();

        // Aplicar búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = strtolower($request->search);
            $query->where(function($q) use ($searchTerm) {
                $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereHas('comuna', function($q) use ($searchTerm) {
                      $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$searchTerm}%"]);
                  })
                  ->orWhereHas('comuna.municipio', function($q) use ($searchTerm) {
                      $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
                  })
                  ->orWhereHas('comuna.municipio.departamento', function($q) use ($searchTerm) {
                      $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
                  })
                  ->orWhereHas('comuna.municipio.departamento.pais', function($q) use ($searchTerm) {
                      $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
                  });
            });
        }

        // Filtro por país
        if ($request->has('pais_id') && !empty($request->pais_id)) {
            $query->whereHas('comuna.municipio.departamento.pais', function($q) use ($request) {
                $q->where('id', $request->pais_id);
            });
        }

        // Filtro por departamento
        if ($request->has('departamento_id') && !empty($request->departamento_id)) {
            $query->whereHas('comuna.municipio.departamento', function($q) use ($request) {
                $q->where('id', $request->departamento_id);
            });
        }

        // Filtro por municipio
        if ($request->has('municipio_id') && !empty($request->municipio_id)) {
            $query->whereHas('comuna.municipio', function($q) use ($request) {
                $q->where('id', $request->municipio_id);
            });
        }

        // Filtro por comuna
        if ($request->has('comuna_id') && !empty($request->comuna_id)) {
            $query->where('comuna_id', $request->comuna_id);
        }

        // Aplicar relaciones necesarias
        $query->with(['comuna.municipio.departamento.pais']);

        // Ordenamiento por defecto
        $query->orderBy('nombre', 'asc');

        // Paginación
        $barrios = $query->paginate(12);

        // Respuesta AJAX
        if ($request->ajax()) {
            return view('barrios.partials.barrios-list-with-pagination', compact('barrios'))->render();
        }

        // Respuesta normal - primera carga
        $paises = Pais::orderBy('name')->get();
        $departamentos = Departamento::orderBy('name')->get();
        $municipios = Municipio::orderBy('name')->get();
        $comunas = Comuna::orderBy('nombre')->get();

        // Estadísticas para las cards
        $totalBarrios = Barrio::count();
        $totalComunas = Comuna::count();

        return view('barrios.index', compact('barrios', 'paises', 'departamentos', 'municipios', 'comunas', 'totalBarrios', 'totalComunas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $paises = Pais::orderBy('name')->get();
        $departamentos = Departamento::with('pais')->orderBy('name')->get();
        $municipios = Municipio::orderBy('name')->get();
        $comunas = Comuna::orderBy('nombre')->get();

        return view('barrios.create', compact('paises', 'departamentos', 'municipios', 'comunas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'comuna_id' => 'required|exists:comunas,id',
        ]);

        DB::beginTransaction();
        try {
            Barrio::create($request->all());

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Barrio creado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('barrios.index');

        } catch (Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al crear el barrio. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Barrio $barrio)
    {
        // Cargar todas las relaciones necesarias
        $barrio->load([
            'comuna.municipio.departamento.pais'
        ]);

        // Obtener barrios relacionados (de la misma comuna)
        $barriosRelacionados = Barrio::where('comuna_id', $barrio->comuna_id)
            ->where('id', '!=', $barrio->id)
            ->orderBy('nombre')
            ->limit(5)
            ->get();

        return view('barrios.show', compact('barrio', 'barriosRelacionados'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Barrio $barrio)
    {
        $barrio->load('comuna.municipio.departamento');
        $paises = Pais::orderBy('name')->get();
        $departamentos = Departamento::with('pais')->orderBy('name')->get();
        $municipios = Municipio::where('departamento_id', $barrio->comuna->municipio->departamento_id)->orderBy('name')->get();
        $comunas = Comuna::where('municipio_id', $barrio->comuna->municipio_id)->orderBy('nombre')->get();

        return view('barrios.edit', compact('barrio', 'paises', 'departamentos', 'municipios', 'comunas'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Barrio $barrio)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'comuna_id' => 'required|exists:comunas,id',
        ]);

        DB::beginTransaction();
        try {
            $barrio->update($request->all());

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Barrio actualizado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('barrios.index');

        } catch (Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al actualizar el barrio. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Barrio $barrio)
    {
        DB::beginTransaction();
        try {
            $barrio->delete();

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Barrio eliminado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('barrios.index');

        } catch (Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al eliminar el barrio. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back();
        }
    }
}
