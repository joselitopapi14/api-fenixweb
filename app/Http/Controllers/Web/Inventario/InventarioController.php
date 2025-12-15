<?php

namespace App\Http\Controllers\Web\Inventario;

use App\Http\Controllers\Controller;
use App\Models\BolletaEmpeno;
use App\Models\BoletaDesempeno;
use App\Models\Empresa;
use App\Models\TipoMovimiento;
use App\Models\TipoProducto;
use App\Models\Cliente;
use App\Exports\InventarioExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Obtener empresas disponibles según el tipo de usuario
        if ($user->esAdministradorGlobal()) {
            $empresas = Empresa::activas()->orderBy('razon_social')->get();
        } else {
            $empresas = $user->empresasActivas;
        }

        // Obtener tipos de movimiento disponibles
        $tiposMovimiento = TipoMovimiento::activos()->orderBy('nombre')->get();

        // Obtener tipos de producto disponibles
        $tiposProducto = TipoProducto::orderBy('nombre')->get();

        // Filtro por rango de fechas (OBLIGATORIO)
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        if (!$fechaDesde || !$fechaHasta) {
            // Si no hay fechas, usar mes actual por defecto
            $fechaDesde = now()->startOfMonth()->format('Y-m-d');
            $fechaHasta = now()->endOfMonth()->format('Y-m-d');
        }

        // Construir la consulta unificada para obtener todos los movimientos
        $movimientos = $this->obtenerMovimientosUnificados($user, $request, $fechaDesde, $fechaHasta);

        // Aplicar filtros dinámicos si es AJAX
        if ($request->ajax()) {
            // Paginar los movimientos unificados
            $movimientosPaginados = $this->paginarMovimientos($movimientos, 15);

            // Calcular totales
            $totales = $this->calcularTotales($movimientos);

            return response()->json([
                'html' => view('inventario.partials.inventory-list', ['movimientos' => $movimientosPaginados])->render(),
                'html_mobile' => view('inventario.partials.inventory-mobile-grid', ['movimientos' => $movimientosPaginados])->render(),
                'pagination' => view('inventario.partials.pagination', ['movimientos' => $movimientosPaginados])->render(),
                'totals' => [
                    'total_monto_neto' => number_format($totales['totalMontoNeto'], 2),
                    'total_movimientos' => $totales['totalMovimientos'],
                    'total_oro_neto' => number_format($totales['totalOroNeto'], 2),
                    'total_no_oro_neto' => number_format($totales['totalNoOroNeto'], 2),
                    'totales_por_tipo_oro' => $totales['totalesPorTipoOro'],
                    'totales_por_tipo_no_oro' => $totales['totalesPorTipoNoOro']
                ]
            ]);
        }

        // Para la carga inicial
        $movimientosPaginados = $this->paginarMovimientos($movimientos, 15);
        $totales = $this->calcularTotales($movimientos);

        return view('inventario.index', [
            'movimientos' => $movimientosPaginados,
            'empresas' => $empresas,
            'tiposMovimiento' => $tiposMovimiento,
            'tiposProducto' => $tiposProducto,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'totalMontoNeto' => $totales['totalMontoNeto'],
            'totalMovimientos' => $totales['totalMovimientos'],
            'totalOroNeto' => $totales['totalOroNeto'],
            'totalNoOroNeto' => $totales['totalNoOroNeto'],
            'totalesPorTipoOro' => $totales['totalesPorTipoOro'],
            'totalesPorTipoNoOro' => $totales['totalesPorTipoNoOro']
        ]);
    }

    /**
     * Obtener movimientos unificados (BolletaEmpeno + BoletaDesempeno)
     */
    private function obtenerMovimientosUnificados($user, $request, $fechaDesde, $fechaHasta)
    {
        // Subconsulta para BolletaEmpeno
        $queryEmpeno = BolletaEmpeno::query()
            ->select([
                'id',
                'numero_contrato',
                'cliente_id',
                'empresa_id',
                'sede_id',
                'user_id',
                'monto_prestamo as monto',
                'tipo_movimiento_id',
                'fecha_prestamo',
                'fecha_vencimiento',
                'estado',
                'created_at',
                'updated_at',
                'anulada',
                'foto_prenda',
                'ubicacion',
                DB::raw("'empeno' as tipo_registro"),
                DB::raw('NULL as bolleta_empeno_id'),
                DB::raw('NULL as fecha_abono'),
                DB::raw('NULL as observaciones')
            ])
            ->with([
                'cliente:id,nombres,apellidos,razon_social,cedula_nit,tipo_documento_id,telefono_fijo,celular',
                'cliente.tipoDocumento:id,abreviacion',
                'empresa:id,razon_social',
                'sede:id,nombre',
                'tipoMovimiento:id,nombre,es_suma',
                'productos.producto.tipoProducto:id,nombre',
                'productos.producto.tipoOro:id,nombre'
            ])
            ->where('anulada', false);

        // Subconsulta para BoletaDesempeno
        $queryDesempeno = BoletaDesempeno::query()
            ->select([
                'id',
                DB::raw('NULL as numero_contrato'),
                DB::raw('NULL as cliente_id'),
                DB::raw('NULL as empresa_id'),
                DB::raw('NULL as sede_id'),
                'user_id',
                'monto_pagado as monto',
                'tipo_movimiento_id',
                DB::raw('NULL as fecha_prestamo'),
                DB::raw('NULL as fecha_vencimiento'),
                'estado',
                'created_at',
                'updated_at',
                DB::raw('false as anulada'),
                DB::raw('NULL as foto_prenda'),
                DB::raw('NULL as ubicacion'),
                DB::raw("'desempeno' as tipo_registro"),
                'bolleta_empeno_id',
                'fecha_abono',
                'observaciones'
            ])
            ->with([
                'boletaEmpeno.cliente:id,nombres,apellidos,razon_social,cedula_nit,tipo_documento_id,telefono_fijo,celular',
                'boletaEmpeno.cliente.tipoDocumento:id,abreviacion',
                'boletaEmpeno.empresa:id,razon_social',
                'boletaEmpeno.sede:id,nombre',
                'tipoMovimiento:id,nombre,es_suma',
                'boletaEmpeno.productos.producto.tipoProducto:id,nombre',
                'boletaEmpeno.productos.producto.tipoOro:id,nombre'
            ]);

        // Aplicar filtros según permisos del usuario
        if (!$user->esAdministradorGlobal()) {
            $empresasUsuario = $user->empresasActivas->pluck('id');

            $queryEmpeno->whereIn('empresa_id', $empresasUsuario);

            $queryDesempeno->whereHas('boletaEmpeno', function($q) use ($empresasUsuario) {
                $q->whereIn('empresa_id', $empresasUsuario);
            });
        }

        // Aplicar filtros de fecha
        $queryEmpeno->whereDate('created_at', '>=', $fechaDesde)
                   ->whereDate('created_at', '<=', $fechaHasta);

        $queryDesempeno->whereDate('created_at', '>=', $fechaDesde)
                       ->whereDate('created_at', '<=', $fechaHasta);

        // Filtro por empresa
        if ($request->filled('empresa_id')) {
            $queryEmpeno->where('empresa_id', $request->empresa_id);

            $queryDesempeno->whereHas('boletaEmpeno', function($q) use ($request) {
                $q->where('empresa_id', $request->empresa_id);
            });
        }

        // Filtro por tipo de movimiento
        if ($request->filled('tipo_movimiento_id')) {
            $queryEmpeno->where('tipo_movimiento_id', $request->tipo_movimiento_id);
            $queryDesempeno->where('tipo_movimiento_id', $request->tipo_movimiento_id);
        }

        // Filtro por tipo de producto
        if ($request->filled('tipo_producto_id')) {
            $queryEmpeno->whereHas('productos.producto', function($q) use ($request) {
                $q->where('tipo_producto_id', $request->tipo_producto_id);
            });

            $queryDesempeno->whereHas('boletaEmpeno.productos.producto', function($q) use ($request) {
                $q->where('tipo_producto_id', $request->tipo_producto_id);
            });
        }

        // Filtro por número de contrato
        if ($request->filled('numero_contrato')) {
            $numeroContrato = trim($request->numero_contrato);
            $queryEmpeno->whereRaw('LOWER(numero_contrato) LIKE ?', ['%' . strtolower($numeroContrato) . '%']);

            $queryDesempeno->whereHas('boletaEmpeno', function($q) use ($numeroContrato) {
                $q->whereRaw('LOWER(numero_contrato) LIKE ?', ['%' . strtolower($numeroContrato) . '%']);
            });
        }

        // Filtro por cliente
        if ($request->filled('cliente_search')) {
            $clienteSearch = trim($request->cliente_search);
            $searchTermLower = strtolower($clienteSearch);

            $queryEmpeno->whereHas('cliente', function($q) use ($searchTermLower) {
                $q->where(function($q2) use ($searchTermLower) {
                    $q2->whereRaw('LOWER(cedula_nit) LIKE ?', ['%' . $searchTermLower . '%'])
                       ->orWhereRaw('LOWER(nombres) LIKE ?', ['%' . $searchTermLower . '%'])
                       ->orWhereRaw('LOWER(apellidos) LIKE ?', ['%' . $searchTermLower . '%'])
                       ->orWhereRaw('LOWER(razon_social) LIKE ?', ['%' . $searchTermLower . '%'])
                       ->orWhereRaw("LOWER(CONCAT(COALESCE(nombres, ''), ' ', COALESCE(apellidos, ''))) LIKE ?", ['%' . $searchTermLower . '%']);
                });
            });

            $queryDesempeno->whereHas('boletaEmpeno.cliente', function($q) use ($searchTermLower) {
                $q->where(function($q2) use ($searchTermLower) {
                    $q2->whereRaw('LOWER(cedula_nit) LIKE ?', ['%' . $searchTermLower . '%'])
                       ->orWhereRaw('LOWER(nombres) LIKE ?', ['%' . $searchTermLower . '%'])
                       ->orWhereRaw('LOWER(apellidos) LIKE ?', ['%' . $searchTermLower . '%'])
                       ->orWhereRaw('LOWER(razon_social) LIKE ?', ['%' . $searchTermLower . '%'])
                       ->orWhereRaw("LOWER(CONCAT(COALESCE(nombres, ''), ' ', COALESCE(apellidos, ''))) LIKE ?", ['%' . $searchTermLower . '%']);
                });
            });
        }

        // Obtener resultados
        $empenos = $queryEmpeno->get();
        $desempenos = $queryDesempeno->get();

        // Combinar y ordenar por fecha
        $movimientos = $empenos->concat($desempenos)->sortByDesc('created_at');

        return $movimientos;
    }

    /**
     * Paginar movimientos manualmente
     */
    private function paginarMovimientos($movimientos, $perPage = 15)
    {
        $page = request()->get('page', 1);
        $items = $movimientos->forPage($page, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $movimientos->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    /**
     * Calcular totales solo con valores netos (sin restar)
     */
    private function calcularTotales($movimientos)
    {
        $totalMontoNeto = 0;
        $totalMovimientos = $movimientos->count();
        $totalOroNeto = 0;
        $totalNoOroNeto = 0;
        $totalesPorTipoOro = [];
        $totalesPorTipoNoOro = [];

        foreach ($movimientos as $movimiento) {
            $tipoMovimiento = $movimiento->tipoMovimiento;
            $monto = $movimiento->monto ?? 0;

            // Solo contar sumas para el monto neto (no restar)
            if ($tipoMovimiento && $tipoMovimiento->es_suma) {
                $totalMontoNeto += $monto;

                // Calcular totales por tipo de oro y no oro
                $productos = null;
                if ($movimiento->tipo_registro === 'empeno') {
                    $productos = $movimiento->productos;
                } else {
                    $productos = $movimiento->boletaEmpeno?->productos;
                }

                if ($productos && $productos->count() > 0) {
                    $tieneOro = false;
                    $tiposOroEncontrados = [];
                    $tiposNoOroEncontrados = [];

                    foreach ($productos as $producto) {
                        if ($producto->producto) {
                            if ($producto->producto->tipoOro) {
                                // Producto de oro
                                $tieneOro = true;
                                $tipoOro = $producto->producto->tipoOro;
                                $tipoOroNombre = $tipoOro->nombre;

                                if (!in_array($tipoOroNombre, $tiposOroEncontrados)) {
                                    $tiposOroEncontrados[] = $tipoOroNombre;
                                }
                            } else {
                                // Producto no oro
                                $tipoProducto = $producto->producto->tipoProducto;
                                if ($tipoProducto) {
                                    $tipoProductoNombre = $tipoProducto->nombre;

                                    if (!in_array($tipoProductoNombre, $tiposNoOroEncontrados)) {
                                        $tiposNoOroEncontrados[] = $tipoProductoNombre;
                                    }
                                }
                            }
                        }
                    }

                    // Distribuir montos entre tipos encontrados
                    if ($tieneOro && count($tiposOroEncontrados) > 0) {
                        $montoPorTipoOro = $monto / count($tiposOroEncontrados);
                        $totalOroNeto += $monto;

                        foreach ($tiposOroEncontrados as $tipoOroNombre) {
                            if (!isset($totalesPorTipoOro[$tipoOroNombre])) {
                                $totalesPorTipoOro[$tipoOroNombre] = 0;
                            }
                            $totalesPorTipoOro[$tipoOroNombre] += $montoPorTipoOro;
                        }
                    }

                    if (count($tiposNoOroEncontrados) > 0) {
                        $montoPorTipoNoOro = $monto / count($tiposNoOroEncontrados);
                        $totalNoOroNeto += $monto;

                        foreach ($tiposNoOroEncontrados as $tipoProductoNombre) {
                            if (!isset($totalesPorTipoNoOro[$tipoProductoNombre])) {
                                $totalesPorTipoNoOro[$tipoProductoNombre] = 0;
                            }
                            $totalesPorTipoNoOro[$tipoProductoNombre] += $montoPorTipoNoOro;
                        }
                    }

                    // Si no tiene ni oro ni productos identificados, contar como no oro genérico
                    if (!$tieneOro && count($tiposNoOroEncontrados) === 0) {
                        $totalNoOroNeto += $monto;
                        if (!isset($totalesPorTipoNoOro['Sin especificar'])) {
                            $totalesPorTipoNoOro['Sin especificar'] = 0;
                        }
                        $totalesPorTipoNoOro['Sin especificar'] += $monto;
                    }
                } else {
                    // Si no hay productos, considerarlo como no oro sin especificar
                    $totalNoOroNeto += $monto;
                    if (!isset($totalesPorTipoNoOro['Sin especificar'])) {
                        $totalesPorTipoNoOro['Sin especificar'] = 0;
                    }
                    $totalesPorTipoNoOro['Sin especificar'] += $monto;
                }
            }
        }

        return [
            'totalMontoNeto' => $totalMontoNeto,
            'totalMovimientos' => $totalMovimientos,
            'totalOroNeto' => $totalOroNeto,
            'totalNoOroNeto' => $totalNoOroNeto,
            'totalesPorTipoOro' => $totalesPorTipoOro,
            'totalesPorTipoNoOro' => $totalesPorTipoNoOro
        ];
    }

    public function export(Request $request)
    {
        $user = Auth::user();

        // Validar fechas obligatorias
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde'
        ]);

        // Obtener movimientos unificados con los mismos filtros
        $movimientos = $this->obtenerMovimientosUnificados($user, $request, $request->fecha_desde, $request->fecha_hasta);

        $filename = 'inventario_' . $request->fecha_desde . '_' . $request->fecha_hasta . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new InventarioExport($movimientos, $request->all()), $filename);
    }

    public function buscarClientes(Request $request)
    {
        $user = Auth::user();
        $term = $request->get('term', '');

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $query = Cliente::select('id', 'nombres', 'apellidos', 'razon_social', 'cedula_nit', 'tipo_documento_id')
            ->with('tipoDocumento:id,abreviacion');

        // Filtrar por empresas del usuario
        if (!$user->esAdministradorGlobal()) {
            $empresasUsuario = $user->empresasActivas->pluck('id');
            $query->whereIn('empresa_id', $empresasUsuario);
        }

        $clientes = $query->where(function($q) use ($term) {
            $q->where('cedula_nit', 'like', '%' . $term . '%')
              ->orWhere('nombres', 'like', '%' . $term . '%')
              ->orWhere('apellidos', 'like', '%' . $term . '%')
              ->orWhere('razon_social', 'like', '%' . $term . '%');
        })
        ->limit(10)
        ->get();

        $results = $clientes->map(function($cliente) {
            $nombre = $cliente->razon_social ?: ($cliente->nombres . ' ' . $cliente->apellidos);
            $documento = $cliente->tipoDocumento ? $cliente->tipoDocumento->abreviacion . ': ' : '';

            return [
                'id' => $cliente->id,
                'text' => $nombre,
                'subtitle' => $documento . $cliente->cedula_nit
            ];
        });

        return response()->json($results);
    }

    public function exportPdf(Request $request)
    {
        $user = Auth::user();

        // Validar fechas obligatorias
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde'
        ]);

        // Obtener movimientos unificados con los mismos filtros
        $movimientos = $this->obtenerMovimientosUnificados($user, $request, $request->fecha_desde, $request->fecha_hasta);
        $totales = $this->calcularTotales($movimientos);

        // Obtener información de filtros aplicados para mostrar en el PDF
        $filtrosAplicados = $this->obtenerFiltrosAplicados($request);

        // Obtener la empresa para información del header
        $empresa = null;
        if ($request->filled('empresa_id')) {
            $empresa = Empresa::find($request->empresa_id);
        } elseif (!$user->esAdministradorGlobal() && $user->empresasActivas->count() === 1) {
            $empresa = $user->empresasActivas->first();
        }

        // Datos para la vista
        $data = [
            'movimientos' => $movimientos,
            'totales' => $totales,
            'filtrosAplicados' => $filtrosAplicados,
            'empresa' => $empresa,
            'fechaDesde' => $request->fecha_desde,
            'fechaHasta' => $request->fecha_hasta,
            'fechaGeneracion' => now(),
            'usuario' => $user
        ];

        // Generar PDF usando DOMPDF
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isPhpEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);

        $html = view('inventario.pdf.reporte', $data)->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="reporte.pdf"');
    }

    /**
     * Obtener descripción de filtros aplicados
     */
    private function obtenerFiltrosAplicados($request)
    {
        $filtros = [];

        // Empresa
        if ($request->filled('empresa_id')) {
            $empresa = Empresa::find($request->empresa_id);
            $filtros[] = 'Empresa: ' . ($empresa ? $empresa->razon_social : 'ID ' . $request->empresa_id);
        }

        // Tipo de movimiento
        if ($request->filled('tipo_movimiento_id')) {
            $tipoMovimiento = TipoMovimiento::find($request->tipo_movimiento_id);
            $filtros[] = 'Tipo de Movimiento: ' . ($tipoMovimiento ? $tipoMovimiento->nombre : 'ID ' . $request->tipo_movimiento_id);
        }

        // Tipo de producto
        if ($request->filled('tipo_producto_id')) {
            $tipoProducto = TipoProducto::find($request->tipo_producto_id);
            $filtros[] = 'Tipo de Producto: ' . ($tipoProducto ? $tipoProducto->nombre : 'ID ' . $request->tipo_producto_id);
        }

        // Número de contrato
        if ($request->filled('numero_contrato')) {
            $filtros[] = 'Número de Contrato: ' . $request->numero_contrato;
        }

        // Cliente
        if ($request->filled('cliente_search')) {
            $filtros[] = 'Cliente: ' . $request->cliente_search;
        }

        return $filtros;
    }
}
