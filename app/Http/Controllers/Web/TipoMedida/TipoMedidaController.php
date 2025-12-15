<?php

namespace App\Http\Controllers\Web\TipoMedida;

use App\Http\Controllers\Controller;
use App\Models\TipoMedida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class TipoMedidaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = TipoMedida::query();

            // Aplicar búsqueda
            if ($request->has('search') && !empty($request->search)) {
                $query->buscar($request->search);
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
                case 'abreviatura_asc':
                    $query->orderBy('abreviatura', 'asc');
                    break;
                case 'abreviatura_desc':
                    $query->orderBy('abreviatura', 'desc');
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

            $tipoMedidas = $query->paginate(12);

            // Respuesta AJAX
            if ($request->ajax()) {
                return view('tipo-medidas.partials.tipo-medidas-list-with-pagination', compact('tipoMedidas'))->render();
            }

            // Estadísticas para las cards
            $totalTipoMedidas = TipoMedida::count();
            $tipoMedidasActivos = TipoMedida::activos()->count();
            $tipoMedidasInactivos = TipoMedida::where('activo', false)->count();

            return view('tipo-medidas.index', compact(
                'tipoMedidas',
                'totalTipoMedidas',
                'tipoMedidasActivos',
                'tipoMedidasInactivos'
            ));
        } catch (\Exception $e) {
            Log::error('Error al listar tipos de medida: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar los tipos de medida.'], 500);
            }

            return redirect()->back()->with('error', 'Error al cargar los tipos de medida.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tipo-medidas.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_medidas,nombre',
            'abreviatura' => 'required|string|max:10|unique:tipo_medidas,abreviatura',
            'descripcion' => 'nullable|string|max:1000',
            'activo' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            TipoMedida::create([
                'nombre' => $request->nombre,
                'abreviatura' => $request->abreviatura,
                'descripcion' => $request->descripcion,
                'activo' => $request->boolean('activo', true),
            ]);

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Tipo de medida creado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('tipo-medidas.index');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear tipo de medida: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al crear el tipo de medida.',
                'status' => 'error'
            ]);

            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoMedida $tipoMedida)
    {
        return view('tipo-medidas.show', compact('tipoMedida'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoMedida $tipoMedida)
    {
        return view('tipo-medidas.edit', compact('tipoMedida'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoMedida $tipoMedida)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_medidas,nombre,' . $tipoMedida->id,
            'abreviatura' => 'required|string|max:10|unique:tipo_medidas,abreviatura,' . $tipoMedida->id,
            'descripcion' => 'nullable|string|max:1000',
            'activo' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $tipoMedida->update([
                'nombre' => $request->nombre,
                'abreviatura' => $request->abreviatura,
                'descripcion' => $request->descripcion,
                'activo' => $request->boolean('activo', true),
            ]);

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Tipo de medida actualizado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('tipo-medidas.show', $tipoMedida);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tipo de medida: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al actualizar el tipo de medida.',
                'status' => 'error'
            ]);

            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoMedida $tipoMedida)
    {
        DB::beginTransaction();
        try {
            $tipoMedida->delete();

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Tipo de medida eliminado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('tipo-medidas.index');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar tipo de medida: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al eliminar el tipo de medida. Puede estar en uso.',
                'status' => 'error'
            ]);

            return redirect()->back();
        }
    }
}
