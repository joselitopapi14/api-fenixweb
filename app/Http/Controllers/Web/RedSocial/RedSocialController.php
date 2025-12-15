<?php

namespace App\Http\Controllers\Web\RedSocial;

use App\Http\Controllers\Controller;
use App\Models\RedSocial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class RedSocialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = RedSocial::query();

            // Aplicar búsqueda
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = strtolower($request->search);
                $query->whereRaw('LOWER(nombre) LIKE ?', ["%{$searchTerm}%"]);
            }

            // Aplicar ordenamiento
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

            $redesSociales = $query->paginate(10);

            // Respuesta AJAX
            if ($request->ajax()) {
                return view('redes-sociales.partials.redes-sociales-list-with-pagination', compact('redesSociales'))->render();
            }

            // Respuesta normal - primera carga
            return view('redes-sociales.index', compact('redesSociales'));
        } catch (\Exception $e) {
            Log::error('Error al listar redes sociales: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar las redes sociales.'], 500);
            }

            return redirect()->back()->with('error', 'Error al cargar las redes sociales.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('redes-sociales.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:redes_sociales,nombre',
        ]);

        DB::beginTransaction();
        try {
            RedSocial::create([
                'nombre' => $request->nombre,
            ]);

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Red social creada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('redes-sociales.index');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear red social: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al crear la red social. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RedSocial $redSocial)
    {
        return view('redes-sociales.show', compact('redSocial'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RedSocial $redSocial)
    {
        return view('redes-sociales.edit', compact('redSocial'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RedSocial $redSocial)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:redes_sociales,nombre,' . $redSocial->id,
        ]);

        DB::beginTransaction();
        try {
            $redSocial->update([
                'nombre' => $request->nombre,
            ]);

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Red social actualizada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('redes-sociales.index');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar red social: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al actualizar la red social. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RedSocial $redSocial)
    {
        DB::beginTransaction();
        try {
            $redSocial->delete();

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Red social eliminada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('redes-sociales.index');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar red social: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al eliminar la red social. Inténtelo de nuevo.',
                'status' => 'error'
            ]);

            return back();
        }
    }
}
