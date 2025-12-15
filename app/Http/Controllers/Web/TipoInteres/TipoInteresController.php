<?php

namespace App\Http\Controllers\Web\TipoInteres;

use App\Http\Controllers\Controller;
use App\Models\TipoInteres;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TipoInteresController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Construir query base
            $query = TipoInteres::with('empresa');

            // Aplicar filtros de búsqueda
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            // Aplicar filtro de empresa
            if ($request->filled('empresa')) {
                if ($request->empresa === 'global') {
                    $query->whereNull('empresa_id');
                } else {
                    $query->where('empresa_id', $request->empresa);
                }
            }

            // Aplicar filtro de estado
            if ($request->filled('estado')) {
                if ($request->estado === 'activo') {
                    $query->where('activo', true);
                } elseif ($request->estado === 'inactivo') {
                    $query->where('activo', false);
                }
            }

            // Aplicar ordenamiento
            $sort = $request->get('sort', 'nombre');
            switch ($sort) {
                case 'nombre_desc':
                    $query->orderBy('nombre', 'desc');
                    break;
                case 'porcentaje_asc':
                    $query->orderBy('porcentaje', 'asc');
                    break;
                case 'porcentaje_desc':
                    $query->orderBy('porcentaje', 'desc');
                    break;
                case 'created_at_desc':
                    $query->orderBy('created_at', 'desc');
                    break;
                default:
                    $query->orderBy('nombre', 'asc');
                    break;
            }

            $tiposInteres = $query->paginate(10)->appends($request->query());

            if ($request->ajax()) {
                return view('tipos-interes.partials.tipos-interes-list', compact('tiposInteres'))->render();
            }

            // Obtener empresas para el filtro
            $empresas = Empresa::activas()->orderBy('razon_social')->get();

            return view('tipos-interes.index', compact('tiposInteres', 'empresas'));
        } catch (Exception $e) {
            Log::error('Error al listar tipos de interés: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al cargar los tipos de interés.',
                'status' => 'error'
            ]);

            return redirect()->back();
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tipos-interes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_interes,nombre',
            'porcentaje' => 'required|numeric|min:0|max:999.99',
            'activo' => 'boolean',
            'descripcion' => 'nullable|string',
        ]);

        try {
            TipoInteres::create($request->all());

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Tipo de interés creado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('tipos-interes.index');
        } catch (Exception $e) {
            Log::error('Error al crear tipo de interés: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al crear el tipo de interés. Inténtalo de nuevo.',
                'status' => 'error'
            ]);

            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoInteres $tipoInteres)
    {
        return view('tipos-interes.show', compact('tipoInteres'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoInteres $tipoInteres)
    {
        return view('tipos-interes.edit', compact('tipoInteres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoInteres $tipoInteres)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_interes,nombre,' . $tipoInteres->id,
            'porcentaje' => 'required|numeric|min:0|max:999.99',
            'activo' => 'boolean',
            'descripcion' => 'nullable|string',
        ]);

        try {
            $tipoInteres->update($request->all());

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Tipo de interés actualizado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('tipos-interes.index');
        } catch (Exception $e) {
            Log::error('Error al actualizar tipo de interés: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al actualizar el tipo de interés. Inténtalo de nuevo.',
                'status' => 'error'
            ]);

            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoInteres $tipoInteres)
    {
        try {
            $tipoInteres->delete();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Tipo de interés eliminado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('tipos-interes.index');
        } catch (Exception $e) {
            Log::error('Error al eliminar tipo de interés: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al eliminar el tipo de interés. Inténtalo de nuevo.',
                'status' => 'error'
            ]);

            return redirect()->back();
        }
    }
}
