<?php

namespace App\Http\Controllers;

use App\Models\BoletaDesempeno;
use App\Models\BolletaEmpeno;
use App\Models\Cliente;
use App\Helpers\NumberToWordsHelper;
use App\Exports\BoletaDesempenosExportSimple;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class BoletaDesempenoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Query base para boleta desempeños
        $query = BoletaDesempeno::with(['boletaEmpeno.cliente', 'boletaEmpeno.empresa', 'usuario']);

        // Si NO es administrador global, filtrar por empresa del usuario
        if (!$user->esAdministradorGlobal()) {
            $query->deEmpresa($user->empresa_id);
        }

        // Filtros
        if ($request->filled('numero_contrato')) {
            $query->whereHas('boletaEmpeno', function($q) use ($request) {
                $q->where('numero_contrato', 'like', '%' . $request->numero_contrato . '%');
            });
        }

        if ($request->filled('cedula_cliente')) {
            $query->whereHas('boletaEmpeno.cliente', function($q) use ($request) {
                $q->where('cedula_nit', 'like', '%' . $request->cedula_cliente . '%');
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_abono', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_abono', '<=', $request->fecha_hasta);
        }

        $boletaDesempenos = $query->orderBy('fecha_abono', 'desc')->paginate(15);

        // Estadísticas - también considerar administrador global
        $estadisticasQuery = BoletaDesempeno::query();
        if (!$user->esAdministradorGlobal()) {
            $estadisticasQuery->deEmpresa($user->empresa_id);
        }

        $estadisticas = [
            'total_boleta_desempenos' => $boletaDesempenos->total(),
            'total_pagado' => $estadisticasQuery->sum('monto_pagado'),
            'boleta_desempenos_mes' => (clone $estadisticasQuery)
                ->whereMonth('fecha_abono', now()->month)
                ->whereYear('fecha_abono', now()->year)
                ->count(),
            'monto_mes' => (clone $estadisticasQuery)
                ->whereMonth('fecha_abono', now()->month)
                ->whereYear('fecha_abono', now()->year)
                ->sum('monto_pagado')
        ];

        if ($request->ajax()) {
            return view('boleta-desempenos.partials.boleta-desempenos-list-with-pagination', compact('boletaDesempenos'))->render();
        }

        return view('boleta-desempenos.index', compact('boletaDesempenos', 'estadisticas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $boletaEmpeno = null;
        $calculoSugerido = null;

        if ($request->filled('boleta_id')) {
            $user = Auth::user();

            // Query base para boleta
            $query = BolletaEmpeno::with(['cliente', 'empresa', 'tipoInteres', 'boletaDesempenos', 'productos.producto.tipoMedida'])
                ->where('estado', 'activa');

            // Si NO es administrador global, filtrar por empresa del usuario
            if (!$user->esAdministradorGlobal()) {
                $query->where('empresa_id', $user->empresa_id);
            }

            $boletaEmpeno = $query->findOrFail($request->boleta_id);
            $calculoSugerido = BoletaDesempeno::calcularMontoSugerido($boletaEmpeno, $request->fecha_abono);
            $infoCuotas = BoletaDesempeno::calcularInfoCuotas($boletaEmpeno, $request->fecha_abono);

            // Combinar ambos arrays
            $calculoSugerido = array_merge($calculoSugerido, $infoCuotas);
        }

        return view('boleta-desempenos.create', compact('boletaEmpeno', 'calculoSugerido'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'bolleta_empeno_id' => 'required|exists:boletas_empeno,id',
            'monto_pagado' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
            'fecha_abono' => 'required|date',
            'observaciones' => 'nullable|string|max:500'
        ]);

        $user = Auth::user();

        // Query base para boleta
        $query = BolletaEmpeno::where('estado', 'activa');

        // Si NO es administrador global, filtrar por empresa del usuario
        if (!$user->esAdministradorGlobal()) {
            $query->where('empresa_id', $user->empresa_id);
        }

        $boletaEmpeno = $query->findOrFail($request->bolleta_empeno_id);

        DB::beginTransaction();
        try {
            // Calcular sugeridos para mostrar/registro auxiliar si es necesario
            $calculoSugerido = BoletaDesempeno::calcularMontoSugerido($boletaEmpeno, $request->fecha_abono);

            // Crear registro usando sólo las columnas reales de la migración.
            // El campo tipo_movimiento_id debe establecerse siempre a 2 según especificación.
            $boletaDesempeno = BoletaDesempeno::create([
                'bolleta_empeno_id' => $request->bolleta_empeno_id,
                'tipo_movimiento_id' => 2,
                'user_id' => Auth::id(),
                'monto_pagado' => $request->monto_pagado,
                'descuento' => $request->descuento ?? 0,
                'fecha_abono' => $request->fecha_abono,
                'observaciones' => $request->observaciones,
                'estado' => 'pagada'
            ]);

            $boleta = $boletaDesempeno->boletaEmpeno;
            $boleta->estado = 'pagada';
            $boleta->save();

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Boleta de desempeño registrada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('boleta-desempenos.index');

        } catch (\Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al registrar la boleta de desempeño: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(BoletaDesempeno $boletaDesempeno)
    {
        $boletaDesempeno->load(['boletaEmpeno.cliente', 'boletaEmpeno.empresa', 'usuario']);

        $user = Auth::user();

        // Verificar que pertenece a la empresa del usuario o que es admin global
        if (!$user->esAdministradorGlobal() && $boletaDesempeno->boletaEmpeno->empresa_id !== $user->empresa_id) {
            abort(403);
        }

        return view('boleta-desempenos.show', compact('boletaDesempeno'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BoletaDesempeno $boletaDesempeno)
    {
        $boletaDesempeno->load(['boletaEmpeno.cliente']);

        $user = Auth::user();

        // Verificar que pertenece a la empresa del usuario o que es admin global
        if (!$user->esAdministradorGlobal() && $boletaDesempeno->boletaEmpeno->empresa_id !== $user->empresa_id) {
            abort(403);
        }

        return view('boleta-desempenos.edit', compact('boletaDesempeno'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BoletaDesempeno $boletaDesempeno)
    {
        $user = Auth::user();

        // Verificar que pertenece a la empresa del usuario o que es admin global
        if (!$user->esAdministradorGlobal() && $boletaDesempeno->boletaEmpeno->empresa_id !== $user->empresa_id) {
            abort(403);
        }

        $request->validate([
            'monto_pagado' => 'required|numeric|min:0',
            'fecha_abono' => 'required|date',
            'observaciones' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $boletaDesempeno->update([
                'monto_pagado' => $request->monto_pagado,
                'fecha_abono' => $request->fecha_abono,
                'observaciones' => $request->observaciones
            ]);

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Boleta de desempeño actualizada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('boleta-desempenos.index');

        } catch (\Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al actualizar la boleta de desempeño: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BoletaDesempeno $boletaDesempeno)
    {
        $user = Auth::user();

        // Verificar que pertenece a la empresa del usuario o que es admin global
        if (!$user->esAdministradorGlobal() && $boletaDesempeno->boletaEmpeno->empresa_id !== $user->empresa_id) {
            abort(403);
        }

        DB::beginTransaction();
        try {
            $boletaDesempeno->delete();

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Boleta de desempeño eliminada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('boleta-desempenos.index');

        } catch (\Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al eliminar la boleta de desempeño: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return redirect()->back();
        }
    }

    /**
     * Buscar boleta por cédula del cliente
     */
    public function buscarBoleta(Request $request)
    {
        $request->validate([
            'cedula_nit' => 'required|string'
        ]);

        $user = Auth::user();
        $termino = $request->cedula_nit;

        // Query base para boletas
        $query = BolletaEmpeno::with(['cliente', 'empresa', 'tipoInteres', 'productos.producto.tipoMedida'])
            ->whereHas('cliente', function($q) use ($termino) {
                $q->where(function($subQuery) use ($termino) {
                    // Buscar por cédula/NIT
                    $subQuery->where('cedula_nit', 'LIKE', '%' . $termino . '%')
                             // Buscar por nombres (case-insensitive)
                             ->orWhereRaw('LOWER(nombres) LIKE ?', ['%' . strtolower($termino) . '%'])
                             // Buscar por apellidos (case-insensitive)
                             ->orWhereRaw('LOWER(apellidos) LIKE ?', ['%' . strtolower($termino) . '%'])
                             // Buscar por razón social (case-insensitive)
                             ->orWhereRaw('LOWER(razon_social) LIKE ?', ['%' . strtolower($termino) . '%'])
                             // Buscar por nombre completo concatenado (case-insensitive)
                             ->orWhereRaw("LOWER(CONCAT(nombres, ' ', apellidos)) LIKE ?", ['%' . strtolower($termino) . '%']);
                });
            })
            ->where('estado', 'activa');

        // Si NO es administrador global, filtrar por empresa del usuario
        if (!$user->esAdministradorGlobal()) {
            $query->where('empresa_id', $user->empresa_id);
        }

        $boletas = $query->get();

        return response()->json([
            'success' => true,
            'boletas' => $boletas->map(function($boleta) {
                // Formatear productos
                $productos = $boleta->productos->map(function($producto) {
                    $tipoMedida = $producto->producto->tipoMedida->nombre ?? 'N/A';
                    return $producto->producto->nombre . ', ' . $producto->cantidad . ' (' . $tipoMedida . ')';
                })->implode('; ');

                return [
                    'id' => $boleta->id,
                    'numero_contrato' => $boleta->numero_contrato,
                    'cliente_nombre' => $boleta->cliente->nombres . ' ' . $boleta->cliente->apellidos,
                    'cliente_cedula' => $boleta->cliente->cedula_nit,
                    'cliente_razon_social' => $boleta->cliente->razon_social,
                    'monto_prestamo' => $boleta->monto_prestamo,
                    'fecha_prestamo' => $boleta->fecha_prestamo?->format('d/m/Y'),
                    'fecha_vencimiento' => $boleta->fecha_vencimiento?->format('d/m/Y'),
                    'empresa_nombre' => $boleta->empresa->nombre ?? 'N/A',
                    'tipo_interes_nombre' => $boleta->tipoInteres->nombre ?? 'N/A',
                    'tipo_interes_porcentaje' => $boleta->tipoInteres->porcentaje ?? null,
                    'productos' => $productos ?: 'No hay productos registrados'
                ];
            })
        ]);
    }

    /**
     * Calcular cuota sugerida
     */
    public function calcularCuota(Request $request)
    {
        $request->validate([
            'boleta_id' => 'required|exists:boletas_empeno,id',
            'fecha_abono' => 'required|date'
        ]);

        $user = Auth::user();

        // Query base para boleta
        $query = BolletaEmpeno::with(['tipoInteres', 'empresa.tiposInteres', 'boletaDesempenos']);

        // Si NO es administrador global, filtrar por empresa del usuario
        if (!$user->esAdministradorGlobal()) {
            $query->where('empresa_id', $user->empresa_id);
        }

        $boletaEmpeno = $query->findOrFail($request->boleta_id);

        $calculo = BoletaDesempeno::calcularMontoSugerido($boletaEmpeno, $request->fecha_abono);
        $infoCuotas = BoletaDesempeno::calcularInfoCuotas($boletaEmpeno, $request->fecha_abono);

        // Combinar ambos arrays
        $calculo = array_merge($calculo, $infoCuotas);

        return response()->json(array_merge([
            'success' => true,
            'calculo' => $calculo
        ], $calculo));
    }

    /**
     * Obtener boleta desempeños previas de una boleta
     */
    public function boletaDesempenosPrevias($boletaId)
    {
        $user = Auth::user();

        // Query base para boleta
        $query = BolletaEmpeno::with(['boletaDesempenos.usuario']);

        // Si NO es administrador global, filtrar por empresa del usuario
        if (!$user->esAdministradorGlobal()) {
            $query->where('empresa_id', $user->empresa_id);
        }

        $boleta = $query->findOrFail($boletaId);

        $boletaDesempenos = $boleta->boletaDesempenos()
            ->with('usuario')
            ->orderBy('fecha_abono', 'desc')
            ->get()
            ->map(function($boletaDesempeno) {
                return [
                    'id' => $boletaDesempeno->id,
                    'fecha_abono' => $boletaDesempeno->fecha_abono->format('d/m/Y'),
                    'monto_pagado' => $boletaDesempeno->monto_pagado,
                    'observaciones' => $boletaDesempeno->observaciones,
                    'usuario' => $boletaDesempeno->usuario->name ?? 'No registrado'
                ];
            });

        return response()->json([
            'success' => true,
            'boletaDesempenos' => $boletaDesempenos
        ]);
    }

    /**
     * Exportar boleta desempeños a Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            // Debug básico
            \Log::info('Iniciando exportación de boleta desempeños', [
                'user_id' => Auth::id(),
                'user_empresa' => Auth::user()->empresa_id ?? 'null'
            ]);

            $user = Auth::user();

            // Verificar que el usuario existe
            if (!$user) {
                throw new \Exception('Usuario no autenticado');
            }

            $fecha = now()->format('Y-m-d');
            $empresa = $user->esAdministradorGlobal() ? 'Todas_Empresas' : ($user->empresa->razon_social ?? 'Empresa');
            $empresa = str_replace([' ', '.', ','], '_', $empresa);

            $filename = "boleta_desempenos_{$empresa}_{$fecha}.xlsx";

            \Log::info('Creando exportación', [
                'filename' => $filename,
                'es_admin_global' => $user->esAdministradorGlobal()
            ]);

            $export = new BoletaDesempenosExportSimple($request);

            \Log::info('Export creado exitosamente, iniciando descarga');

            return Excel::download($export, $filename);

        } catch (\Exception $e) {
            \Log::error('Error al exportar boleta desempeños: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al exportar boleta desempeños: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return redirect()->back();
        }
    }

    public function generarPdf(BoletaDesempeno $boletaDesempeno)
    {
        $user = Auth::user();

        // Verificar acceso
        if (!$user->esAdministradorGlobal() && $boletaDesempeno->boletaEmpeno->empresa_id !== $user->empresa_id) {
            abort(403, 'No tienes permisos para generar este PDF.');
        }

        // Cargar relaciones necesarias
        $boletaDesempeno->load([
            'boletaEmpeno.cliente',
            'boletaEmpeno.empresa',
            'boletaEmpeno.boletaDesempenos.usuario',
            'usuario'
        ]);

        // Datos para el PDF
    $boleta = $boletaDesempeno->boletaEmpeno;
    $empresa = $boleta->empresa;
    $cliente = $boleta->cliente;
    // Nombre usado en la vista: $todasLosDesempenos
    $todasLosDesempenos = $boleta->boletaDesempenos->sortBy('fecha_abono');

        // Configurar DomPDF
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);

        // Generar HTML del PDF
        $html = view('boleta-desempenos.pdf.recibo', compact(
            'boletaDesempeno',
            'boleta',
            'empresa',
            'cliente',
            'todasLosDesempenos'
        ))->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Mostrar el PDF en el navegador
        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="recibo_boleta_desempeno.pdf"');
    }
}
