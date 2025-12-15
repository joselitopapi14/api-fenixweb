<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Comuna;
use App\Models\Barrio;
use Illuminate\Http\Request;

class UbicacionController extends Controller
{
    public function municipios($departamentoId)
    {
        $municipios = Municipio::where('departamento_id', $departamentoId)
                              ->orderBy('name')
                              ->get(['id', 'name']);

        return response()->json($municipios);
    }

    public function comunas($municipioId)
    {
        $comunas = Comuna::where('municipio_id', $municipioId)
                         ->orderBy('nombre')
                         ->get(['id', 'nombre']);

        // Si no hay comunas, crear automáticamente "COMUNA 1"
        if ($comunas->isEmpty()) {
            $comuna = Comuna::create([
                'nombre' => 'COMUNA 1',
                'municipio_id' => $municipioId
            ]);

            $comunas = collect([$comuna]);
        }

        return response()->json($comunas);
    }

    public function barrios($comunaId)
    {
        $barrios = Barrio::where('comuna_id', $comunaId)
                         ->orderBy('nombre')
                         ->get(['id', 'nombre']);

        // Si no hay barrios, crear automáticamente "BARRIO DEFAULT"
        if ($barrios->isEmpty()) {
            $barrio = Barrio::create([
                'nombre' => 'BARRIO DEFAULT',
                'comuna_id' => $comunaId
            ]);

            $barrios = collect([$barrio]);
        }

        return response()->json($barrios);
    }
}
