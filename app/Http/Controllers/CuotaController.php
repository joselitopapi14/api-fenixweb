<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\BolletaEmpeno;
use App\Models\Cliente;
use App\Helpers\NumberToWordsHelper;
use App\Exports\CuotasExportSimple;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class CuotaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Query base para cuotas
        $query = Cuota::with(['boletaEmpeno.cliente', 'boletaEmpeno.empresa', 'usuario']);

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

        $cuotas = $query->orderBy('fecha_abono', 'desc')->paginate(15);

        // Estadísticas - también considerar administrador global
        $estadisticasQuery = Cuota::query();
        if (!$user->esAdministradorGlobal()) {
            $estadisticasQuery->deEmpresa($user->empresa_id);
        }

        $estadisticas = [
            'total_cuotas' => $cuotas->total(),
            'total_pagado' => $estadisticasQuery->sum('monto_pagado'),
            'cuotas_mes' => (clone $estadisticasQuery)
                ->whereMonth('fecha_abono', now()->month)
                ->whereYear('fecha_abono', now()->year)
                ->count(),
            'monto_mes' => (clone $estadisticasQuery)
                ->whereMonth('fecha_abono', now()->month)
                ->whereYear('fecha_abono', now()->year)
                ->sum('monto_pagado')
        ];

        if ($request->ajax()) {
            return view('cuotas.partials.cuotas-list-with-pagination', compact('cuotas'))->render();
        }

        return view('cuotas.index', compact('cuotas', 'estadisticas'));
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
            $query = BolletaEmpeno::with(['cliente', 'empresa', 'tipoInteres', 'cuotas', 'productos.producto.tipoMedida'])
                ->where('estado', 'activa');

            // Si NO es administrador global, filtrar por empresa del usuario
            if (!$user->esAdministradorGlobal()) {
                $query->where('empresa_id', $user->empresa_id);
            }

            $boletaEmpeno = $query->findOrFail($request->boleta_id);
            $calculoSugerido = Cuota::calcularMontoSugerido($boletaEmpeno, $request->fecha_abono);
            $infoCuotas = Cuota::calcularInfoCuotas($boletaEmpeno, $request->fecha_abono);

            // Combinar ambos arrays
            $calculoSugerido = array_merge($calculoSugerido, $infoCuotas);
        }

        return view('cuotas.create', compact('boletaEmpeno', 'calculoSugerido'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'bolleta_empeno_id' => 'required|exists:boletas_empeno,id',
            'monto_pagado' => 'required|numeric|min:0',
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
            $calculoSugerido = Cuota::calcularMontoSugerido($boletaEmpeno, $request->fecha_abono);

            $cuota = Cuota::create([
                'bolleta_empeno_id' => $request->bolleta_empeno_id,
                'user_id' => Auth::id(),
                'monto_sugerido' => $calculoSugerido['monto_sugerido'],
                'monto_pagado' => $request->monto_pagado,
                'interes_calculado' => $calculoSugerido['interes_calculado'],
                'fecha_abono' => $request->fecha_abono,
                'observaciones' => $request->observaciones,
                'estado' => 'pagada'
            ]);

            // Actualizar fecha_vencimiento de la boleta relacionada: sumar 30 días
            try {
                $nuevaFecha = Carbon::parse($boletaEmpeno->fecha_vencimiento ?? now())->addDays(30)->toDateString();
                $boletaEmpeno->fecha_vencimiento = $nuevaFecha;
                $boletaEmpeno->save();
            } catch (\Exception $e) {
                // Si falla la actualización de la boleta, lanzar excepción para rollback
                throw new \Exception('Error al actualizar fecha de vencimiento de la boleta: ' . $e->getMessage());
            }

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Cuota registrada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('cuotas.index');

        } catch (\Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al registrar la cuota: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Cuota $cuota)
    {
        $cuota->load(['boletaEmpeno.cliente', 'boletaEmpeno.empresa', 'usuario']);

        $user = Auth::user();

        // Verificar que pertenece a la empresa del usuario o que es admin global
        if (!$user->esAdministradorGlobal() && $cuota->boletaEmpeno->empresa_id !== $user->empresa_id) {
            abort(403);
        }

        return view('cuotas.show', compact('cuota'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cuota $cuota)
    {
        $cuota->load(['boletaEmpeno.cliente']);

        $user = Auth::user();

        // Verificar que pertenece a la empresa del usuario o que es admin global
        if (!$user->esAdministradorGlobal() && $cuota->boletaEmpeno->empresa_id !== $user->empresa_id) {
            abort(403);
        }

        return view('cuotas.edit', compact('cuota'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cuota $cuota)
    {
        $user = Auth::user();

        // Verificar que pertenece a la empresa del usuario o que es admin global
        if (!$user->esAdministradorGlobal() && $cuota->boletaEmpeno->empresa_id !== $user->empresa_id) {
            abort(403);
        }

        $request->validate([
            'monto_pagado' => 'required|numeric|min:0',
            'fecha_abono' => 'required|date',
            'observaciones' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $cuota->update([
                'monto_pagado' => $request->monto_pagado,
                'fecha_abono' => $request->fecha_abono,
                'observaciones' => $request->observaciones
            ]);

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Cuota actualizada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('cuotas.index');

        } catch (\Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al actualizar la cuota: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cuota $cuota)
    {
        $user = Auth::user();

        // Verificar que pertenece a la empresa del usuario o que es admin global
        if (!$user->esAdministradorGlobal() && $cuota->boletaEmpeno->empresa_id !== $user->empresa_id) {
            abort(403);
        }

        DB::beginTransaction();
        try {
            $cuota->delete();

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Cuota eliminada exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('cuotas.index');

        } catch (\Exception $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al eliminar la cuota: ' . $e->getMessage(),
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
        $query = BolletaEmpeno::with(['tipoInteres', 'empresa.tiposInteres', 'cuotas']);

        // Si NO es administrador global, filtrar por empresa del usuario
        if (!$user->esAdministradorGlobal()) {
            $query->where('empresa_id', $user->empresa_id);
        }

        $boletaEmpeno = $query->findOrFail($request->boleta_id);

        $calculo = Cuota::calcularMontoSugerido($boletaEmpeno, $request->fecha_abono);
        $infoCuotas = Cuota::calcularInfoCuotas($boletaEmpeno, $request->fecha_abono);

        // Combinar ambos arrays
        $calculo = array_merge($calculo, $infoCuotas);

        return response()->json(array_merge([
            'success' => true,
            'calculo' => $calculo
        ], $calculo));
    }

    /**
     * Obtener cuotas previas de una boleta
     */
    public function cuotasPrevias($boletaId)
    {
        $user = Auth::user();

        // Query base para boleta
        $query = BolletaEmpeno::with(['cuotas.usuario']);

        // Si NO es administrador global, filtrar por empresa del usuario
        if (!$user->esAdministradorGlobal()) {
            $query->where('empresa_id', $user->empresa_id);
        }

        $boleta = $query->findOrFail($boletaId);

        $cuotas = $boleta->cuotas()
            ->with('usuario')
            ->orderBy('fecha_abono', 'desc')
            ->get()
            ->map(function($cuota) {
                return [
                    'id' => $cuota->id,
                    'fecha_abono' => $cuota->fecha_abono->format('d/m/Y'),
                    'monto_pagado' => $cuota->monto_pagado,
                    'observaciones' => $cuota->observaciones,
                    'usuario' => $cuota->usuario->name ?? 'No registrado'
                ];
            });

        return response()->json([
            'success' => true,
            'cuotas' => $cuotas
        ]);
    }

    /**
     * Obtener total de cuotas pagadas (suma de monto_pagado) para una boleta
     */
    public function totalCuotas($boletaId)
    {
        $user = Auth::user();

        // Query base para boleta
        $query = BolletaEmpeno::with(['cuotas']);

        // Si NO es administrador global, filtrar por empresa del usuario
        if (!$user->esAdministradorGlobal()) {
            $query->where('empresa_id', $user->empresa_id);
        }

        $boleta = $query->findOrFail($boletaId);

        $total = $boleta->cuotas()
            ->sum('monto_pagado');

        return response()->json([
            'success' => true,
            'total' => (float) $total
        ]);
    }

    /**
     * Exportar cuotas a Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            // Debug básico
            \Log::info('Iniciando exportación de cuotas', [
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

            $filename = "registros_{$empresa}_{$fecha}.xlsx";

            \Log::info('Creando exportación', [
                'filename' => $filename,
                'es_admin_global' => $user->esAdministradorGlobal()
            ]);

            $export = new CuotasExportSimple($request);

            \Log::info('Export creado exitosamente, iniciando descarga');

            return Excel::download($export, $filename);

        } catch (\Exception $e) {
            \Log::error('Error al exportar cuotas: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al exportar cuotas: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return redirect()->back();
        }
    }

    public function generarPdf(Cuota $cuota)
    {
        $user = Auth::user();

        // Verificar acceso
        if (!$user->esAdministradorGlobal() && $cuota->boletaEmpeno->empresa_id !== $user->empresa_id) {
            abort(403, 'No tienes permisos para generar este PDF.');
        }

        // Cargar relaciones necesarias
        $cuota->load([
            'boletaEmpeno.cliente',
            'boletaEmpeno.empresa',
            'boletaEmpeno.cuotas.usuario',
            'usuario'
        ]);

        // Datos para el PDF
        $boleta = $cuota->boletaEmpeno;
        $empresa = $boleta->empresa;
        $cliente = $boleta->cliente;
        $todasLasCuotas = $boleta->cuotas->sortBy('fecha_abono');

        // Configurar DomPDF
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);

        // Generar HTML del PDF
        $html = view('cuotas.pdf.recibo', compact(
            'cuota',
            'boleta',
            'empresa',
            'cliente',
            'todasLasCuotas'
        ))->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Mostrar el PDF en el navegador
        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="recibo_cuota.pdf"');
    }
}
