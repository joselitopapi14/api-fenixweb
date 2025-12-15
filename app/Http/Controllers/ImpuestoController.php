<?php

namespace App\Http\Controllers;

use App\Models\Impuesto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpuestoController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Construir query base
        $query = Impuesto::query();

        // Aplicar búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $query->buscar($request->search);
        }

        // Filtro por estado activo/inactivo
        if ($request->has('estado') && $request->estado !== '') {
            if ($request->estado === 'activo') {
                $query->where('activo', true);
            } else {
                $query->where('activo', false);
            }
        }

        // Aplicar ordenamiento
        $sortBy = $request->get('sort', 'name_asc');
        switch ($sortBy) {
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'code_asc':
                $query->orderBy('code', 'asc');
                break;
            case 'code_desc':
                $query->orderBy('code', 'desc');
                break;
            case 'created_at_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'created_at_asc':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        // Para peticiones de selección (autocompletado/select)
        if ($request->filled('ajax') && $request->ajax === 'select') {
            $impuestos = $query->orderBy('name')
                              ->get(['id', 'name', 'code']);

            return response()->json($impuestos);
        }

        $impuestos = $query->paginate(12);

        // Respuesta AJAX para filtrado/paginación
        if ($request->ajax()) {
            return view('impuestos.partials.impuestos-list', compact('impuestos'))->render();
        }

        // Estadísticas
        $totalImpuestos = Impuesto::count();

        return view('impuestos.index', compact(
            'impuestos',
            'totalImpuestos'
        ));
    }

    public function create()
    {
        return view('impuestos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:impuestos,name',
            'code' => 'nullable|string|max:10|unique:impuestos,code',
        ], [
            'name.required' => 'El nombre del impuesto es obligatorio.',
            'name.unique' => 'Ya existe un impuesto con este nombre.',
            'code.unique' => 'Ya existe un impuesto con este código.',
        ]);

        $impuesto = Impuesto::create([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return redirect()->route('impuestos.index')
            ->with('success', 'Impuesto creado exitosamente.');
    }

    public function show(Impuesto $impuesto)
    {
        return view('impuestos.show', compact('impuesto'));
    }

    public function edit(Impuesto $impuesto)
    {
        return view('impuestos.edit', compact('impuesto'));
    }

    public function update(Request $request, Impuesto $impuesto)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:impuestos,name,' . $impuesto->id,
            'code' => 'nullable|string|max:10|unique:impuestos,code,' . $impuesto->id,
        ], [
            'name.required' => 'El nombre del impuesto es obligatorio.',
            'name.unique' => 'Ya existe un impuesto con este nombre.',
            'code.unique' => 'Ya existe un impuesto con este código.',
        ]);

        $impuesto->update([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return redirect()->route('impuestos.index')
            ->with('success', 'Impuesto actualizado exitosamente.');
    }

    public function destroy(Impuesto $impuesto)
    {
        try {
            $impuesto->delete();
            return redirect()->route('impuestos.index')
                ->with('success', 'Impuesto eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('impuestos.index')
                ->with('error', 'No se pudo eliminar el impuesto. Puede estar siendo utilizado en otros registros.');
        }
    }

    // Métodos para porcentajes
    public function porcentajes(Impuesto $impuesto)
    {
        $porcentajes = $impuesto->impuestoPorcentajes()->orderBy('percentage')->get();
        return view('impuestos.porcentajes.index', compact('impuesto', 'porcentajes'));
    }

    public function createPorcentaje(Impuesto $impuesto)
    {
        return view('impuestos.porcentajes.create', compact('impuesto'));
    }

    public function storePorcentaje(Request $request, Impuesto $impuesto)
    {
        $request->validate([
            'percentage' => 'required|numeric|min:0|max:100',
        ], [
            'percentage.required' => 'El porcentaje es obligatorio.',
            'percentage.numeric' => 'El porcentaje debe ser un número.',
            'percentage.min' => 'El porcentaje no puede ser negativo.',
            'percentage.max' => 'El porcentaje no puede ser mayor a 100.',
        ]);

        $impuesto->impuestoPorcentajes()->create([
            'percentage' => $request->percentage,
        ]);

        return redirect()->route('impuestos.porcentajes', $impuesto)
            ->with('success', 'Porcentaje creado exitosamente.');
    }

    public function editPorcentaje(Impuesto $impuesto, $porcentajeId)
    {
        $porcentaje = $impuesto->impuestoPorcentajes()->findOrFail($porcentajeId);
        return view('impuestos.porcentajes.edit', compact('impuesto', 'porcentaje'));
    }

    public function updatePorcentaje(Request $request, Impuesto $impuesto, $porcentajeId)
    {
        $porcentaje = $impuesto->impuestoPorcentajes()->findOrFail($porcentajeId);

        $request->validate([
            'percentage' => 'required|numeric|min:0|max:100',
        ], [
            'percentage.required' => 'El porcentaje es obligatorio.',
            'percentage.numeric' => 'El porcentaje debe ser un número.',
            'percentage.min' => 'El porcentaje no puede ser negativo.',
            'percentage.max' => 'El porcentaje no puede ser mayor a 100.',
        ]);

        $porcentaje->update([
            'percentage' => $request->percentage,
        ]);

        return redirect()->route('impuestos.porcentajes', $impuesto)
            ->with('success', 'Porcentaje actualizado exitosamente.');
    }

    public function destroyPorcentaje(Impuesto $impuesto, $porcentajeId)
    {
        $porcentaje = $impuesto->impuestoPorcentajes()->findOrFail($porcentajeId);
        $porcentaje->delete();

        return redirect()->route('impuestos.porcentajes', $impuesto)
            ->with('success', 'Porcentaje eliminado exitosamente.');
    }
}
