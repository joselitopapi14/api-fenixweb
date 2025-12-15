<?php

namespace App\Http\Controllers;

use App\Models\TipoMovimiento;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoMovimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TipoMovimiento::with('empresa');

        // Aplicar filtros
        if ($request->filled('search')) {
            $query->where('nombre', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('tipo_movimiento')) {
            if ($request->tipo_movimiento === 'suma') {
                $query->where('es_suma', true);
            } elseif ($request->tipo_movimiento === 'resta') {
                $query->where('es_suma', false);
            }
        }

        if ($request->filled('estado')) {
            if ($request->estado === 'activo') {
                $query->where('activo', true);
            } elseif ($request->estado === 'inactivo') {
                $query->where('activo', false);
            }
        }

        // Obtener los tipos de movimientos paginados
        $tiposMovimientos = $query->orderBy('nombre')->paginate(10);

        // Respuesta AJAX
        if ($request->ajax()) {
            return view('tipos-movimientos.partials.tipos-movimientos-list-with-pagination', compact('tiposMovimientos'))->render();
        }

        // Estadísticas
        $tiposSuma = TipoMovimiento::where('es_suma', true)->count();
        $tiposResta = TipoMovimiento::where('es_suma', false)->count();
        $tiposActivos = TipoMovimiento::where('activo', true)->count();

        return view('tipos-movimientos.index', compact(
            'tiposMovimientos',
            'tiposSuma',
            'tiposResta',
            'tiposActivos'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $empresas = Empresa::where('activa', true)->orderBy('razon_social')->get();

        return view('tipos-movimientos.create', compact('empresas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_movimientos,nombre,NULL,id,empresa_id,' . $request->empresa_id,
            'descripcion' => 'nullable|string|max:1000',
            'empresa_id' => 'required|exists:empresas,id',
            'es_suma' => 'required|boolean',
            'activo' => 'nullable|boolean',
        ]);

        TipoMovimiento::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'empresa_id' => $request->empresa_id,
            'es_suma' => $request->boolean('es_suma'),
            'activo' => $request->boolean('activo', true),
        ]);

        session()->flash('toast', [
            'title' => '¡Éxito!',
            'message' => 'Tipo de movimiento creado exitosamente.',
            'status' => 'success'
        ]);

        return redirect()->route('tipos-movimientos.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoMovimiento $tipoMovimiento)
    {
        $tipoMovimiento->load('empresa');

        return view('tipos-movimientos.show', compact('tipoMovimiento'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoMovimiento $tipoMovimiento)
    {
        $empresas = Empresa::where('activa', true)->orderBy('razon_social')->get();

        return view('tipos-movimientos.edit', compact('tipoMovimiento', 'empresas'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoMovimiento $tipoMovimiento)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_movimientos,nombre,' . $tipoMovimiento->id . ',id,empresa_id,' . $request->empresa_id,
            'descripcion' => 'nullable|string|max:1000',
            'empresa_id' => 'required|exists:empresas,id',
            'es_suma' => 'required|boolean',
            'activo' => 'nullable|boolean',
        ]);

        $tipoMovimiento->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'empresa_id' => $request->empresa_id,
            'es_suma' => $request->boolean('es_suma'),
            'activo' => $request->boolean('activo', false),
        ]);

        session()->flash('toast', [
            'title' => '¡Éxito!',
            'message' => 'Tipo de movimiento actualizado exitosamente.',
            'status' => 'success'
        ]);

        return redirect()->route('tipos-movimientos.show', $tipoMovimiento);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoMovimiento $tipoMovimiento)
    {
        try {
            $tipoMovimiento->delete();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Tipo de movimiento eliminado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('tipos-movimientos.index');
        } catch (\Exception $e) {
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'No se pudo eliminar el tipo de movimiento. Puede estar siendo usado en el sistema.',
                'status' => 'error'
            ]);

            return redirect()->route('tipos-movimientos.index');
        }
    }
}
