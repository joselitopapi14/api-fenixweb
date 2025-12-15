<?php

namespace App\Http\Controllers\Web\CuadreCaja;

use App\Http\Controllers\Controller;
use App\Models\BolletaEmpeno;
use App\Models\BoletaDesempeno;
use App\Models\Cuota;
use App\Models\DocumentoEquivalente;
use App\Models\Empresa;
use App\Models\TipoMovimiento;
use App\Models\TipoProducto;
use App\Models\Cliente;
use App\Exports\CuadreCajaExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Dompdf\Dompdf;
use Dompdf\Options;

class CuadreCajaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Inicializar fechas por defecto (mes actual)
        $today = now();
        $fechaDesde = $request->fecha_desde ?? $today->toDateString();
        $fechaHasta = $request->fecha_hasta ?? $today->toDateString();

        // Obtener datos para filtros
        $empresas = collect();
        if ($user->esAdministradorGlobal()) {
            $empresas = Empresa::orderBy('razon_social')->get();
        } else {
            $empresas = $user->empresasActivas;
        }

        $tiposMovimiento = TipoMovimiento::orderBy('nombre')->get();
        $tiposProducto = TipoProducto::orderBy('nombre')->get();

        $movimientos = collect();
        $totales = [
            'totalMontoNeto' => 0,
            'totalMovimientos' => 0,
            'totalIngresos' => 0,
            'totalEgresos' => 0,
            'totalesPorTipoOro' => [],
            'totalesPorTipoNoOro' => []
        ];

        // Solo buscar si se envían fechas válidas
        if ($fechaDesde && $fechaHasta) {
            $movimientos = $this->obtenerMovimientosUnificados($user, $request, $fechaDesde, $fechaHasta);
            $totales = $this->calcularTotales($movimientos);
        }

        // Si es petición AJAX, devolver JSON
        if ($request->ajax()) {
            $movimientosPaginados = $this->paginarMovimientos($movimientos);

            return response()->json([
                'html' => view('cuadre-caja.partials.movement-list', compact('movimientosPaginados'))->render(),
                'html_mobile' => view('cuadre-caja.partials.movement-mobile-grid', compact('movimientosPaginados'))->render(),
                'totals' => $totales
            ]);
        }

        $movimientosPaginados = $this->paginarMovimientos($movimientos);

        return view('cuadre-caja.index', compact(
            'movimientosPaginados',
            'empresas',
            'tiposMovimiento',
            'tiposProducto',
            'fechaDesde',
            'fechaHasta',
            'totales'
        ));
    }

    /**
     * Obtener movimientos unificados para cuadre de caja
     * BolletaEmpeno: SUMA
     * Cuotas: SUMA
     * BoletaDesempeno: RESTA
     * DocumentoEquivalente: RESTA
     */
    private function obtenerMovimientosUnificados($user, $request, $fechaDesde, $fechaHasta)
    {
        $movimientos = collect();

        // 1. BolletaEmpeno (SUMA) - igual que inventario
        $queryEmpeno = BolletaEmpeno::query()
            ->select([
                'boletas_empeno.id',
                'boletas_empeno.numero_contrato',
                'boletas_empeno.created_at',
                'boletas_empeno.empresa_id',
                'boletas_empeno.cliente_id',
                'boletas_empeno.tipo_movimiento_id',
                'boletas_empeno.monto_prestamo as monto',
                'boletas_empeno.observaciones',
                'boletas_empeno.ubicacion'
            ])
            ->with([
                'empresa:id,razon_social',
                'cliente:id,nombres,apellidos,razon_social,cedula_nit',
                'tipoMovimiento:id,nombre,es_suma',
                'productos.producto.tipoProducto:id,nombre',
                'productos.producto.tipoOro:id,nombre'
            ])
            ->where('anulada', false);

        // 2. Cuotas (SUMA) - nuevas en cuadre de caja
        $queryCuotas = Cuota::query()
            ->select([
                'cuotas.id',
                'cuotas.bolleta_empeno_id',
                'cuotas.monto_pagado as monto',
                'cuotas.created_at',
                'cuotas.observaciones'
            ])
            ->whereHas('boletaEmpeno', function($q) {
                $q->where('anulada', false)
                  ->whereNotNull('empresa_id')
                  ->whereNotNull('cliente_id')
                  ->whereHas('empresa', function($eq) {
                      $eq->where('activa', true);
                  })
                  ->whereHas('cliente');
            })
            ->with([
                'boletaEmpeno:id,numero_contrato,empresa_id,cliente_id',
                'boletaEmpeno.empresa:id,razon_social',
                'boletaEmpeno.cliente:id,nombres,apellidos,razon_social,cedula_nit',
                'boletaEmpeno.productos.producto.tipoProducto:id,nombre',
                'boletaEmpeno.productos.producto.tipoOro:id,nombre'
            ]);

        // 3. BoletaDesempeno (RESTA) - igual que inventario pero con signo negativo
        $queryDesempeno = BoletaDesempeno::query()
            ->select([
                'boleta_desempenos.id',
                'boleta_desempenos.bolleta_empeno_id',
                'boleta_desempenos.created_at',
                'boleta_desempenos.tipo_movimiento_id',
                'boleta_desempenos.monto_pagado as monto',
                'boleta_desempenos.observaciones'
            ])
            ->whereHas('boletaEmpeno', function($q) {
                $q->where('anulada', false)
                  ->whereNotNull('empresa_id')
                  ->whereNotNull('cliente_id')
                  ->whereHas('empresa', function($eq) {
                      $eq->where('activa', true);
                  })
                  ->whereHas('cliente');
            })
            ->with([
                'boletaEmpeno:id,numero_contrato,empresa_id,cliente_id',
                'boletaEmpeno.empresa:id,razon_social',
                'boletaEmpeno.cliente:id,nombres,apellidos,razon_social,cedula_nit',
                'tipoMovimiento:id,nombre,es_suma',
                'boletaEmpeno.productos.producto.tipoProducto:id,nombre',
                'boletaEmpeno.productos.producto.tipoOro:id,nombre'
            ]);

                // 4. DocumentoEquivalente (RESTA) - nuevos en cuadre de caja
        $queryDocumentos = DocumentoEquivalente::query()
            ->select([
                'documento_equivalentes.id',
                'documento_equivalentes.created_at',
                'documento_equivalentes.empresa_id',
                'documento_equivalentes.cliente_id',
                'documento_equivalentes.concepto_id',
                'documento_equivalentes.monto',
                'documento_equivalentes.descripcion as observaciones'
            ])
            ->where('estado', 'activo')
            ->whereNotNull('empresa_id')
            ->whereNotNull('cliente_id')
            ->whereHas('empresa', function($q) {
                $q->where('activa', true);
            })
            ->whereHas('cliente')
            ->with([
                'empresa:id,razon_social',
                'cliente:id,nombres,apellidos,razon_social,cedula_nit',
                'concepto:id,nombre'
            ]);

        // Aplicar filtros según permisos del usuario
        if (!$user->esAdministradorGlobal()) {
            $empresasUsuario = $user->empresasActivas->pluck('id');

            $queryEmpeno->whereIn('empresa_id', $empresasUsuario);
            $queryCuotas->whereHas('boletaEmpeno', function($q) use ($empresasUsuario) {
                $q->whereIn('empresa_id', $empresasUsuario);
            });
            $queryDesempeno->whereHas('boletaEmpeno', function($q) use ($empresasUsuario) {
                $q->whereIn('empresa_id', $empresasUsuario);
            });
            $queryDocumentos->whereIn('empresa_id', $empresasUsuario);
        }

        // Aplicar filtros de fecha
        $queryEmpeno->whereDate('created_at', '>=', $fechaDesde)
                   ->whereDate('created_at', '<=', $fechaHasta);
        $queryCuotas->whereDate('created_at', '>=', $fechaDesde)
                    ->whereDate('created_at', '<=', $fechaHasta);
        $queryDesempeno->whereDate('created_at', '>=', $fechaDesde)
                       ->whereDate('created_at', '<=', $fechaHasta);
        $queryDocumentos->whereDate('created_at', '>=', $fechaDesde)
                        ->whereDate('created_at', '<=', $fechaHasta);

        // Filtro por empresa
        if ($request->filled('empresa_id')) {
            $queryEmpeno->where('empresa_id', $request->empresa_id);
            $queryCuotas->whereHas('boletaEmpeno', function($q) use ($request) {
                $q->where('empresa_id', $request->empresa_id);
            });
            $queryDesempeno->whereHas('boletaEmpeno', function($q) use ($request) {
                $q->where('empresa_id', $request->empresa_id);
            });
            $queryDocumentos->where('empresa_id', $request->empresa_id);
        }

        // Filtro por tipo de movimiento (solo aplica a empeños y desempeños)
        if ($request->filled('tipo_movimiento_id')) {
            $queryEmpeno->where('tipo_movimiento_id', $request->tipo_movimiento_id);
            $queryDesempeno->where('tipo_movimiento_id', $request->tipo_movimiento_id);
        }

        // Filtro por tipo de producto
        if ($request->filled('tipo_producto_id')) {
            $queryEmpeno->whereHas('productos.producto', function($q) use ($request) {
                $q->where('tipo_producto_id', $request->tipo_producto_id);
            });
            $queryCuotas->whereHas('boletaEmpeno.productos.producto', function($q) use ($request) {
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
            $queryCuotas->whereHas('boletaEmpeno', function($q) use ($numeroContrato) {
                $q->whereRaw('LOWER(numero_contrato) LIKE ?', ['%' . strtolower($numeroContrato) . '%']);
            });
            $queryDesempeno->whereHas('boletaEmpeno', function($q) use ($numeroContrato) {
                $q->whereRaw('LOWER(numero_contrato) LIKE ?', ['%' . strtolower($numeroContrato) . '%']);
            });
            $queryDocumentos->whereRaw('LOWER(descripcion) LIKE ?', ['%' . strtolower($numeroContrato) . '%']);
        }

        // Filtro por cliente
        if ($request->filled('cliente_search')) {
            $clienteSearch = trim($request->cliente_search);
            $searchTermLower = strtolower($clienteSearch);

            $queryEmpeno->whereHas('cliente', function($q) use ($searchTermLower) {
                $q->where(function($q2) use ($searchTermLower) {
                    $q2->whereRaw('LOWER(cedula_nit) LIKE ?', ['%' . $searchTermLower . '%'])
                       ->orWhereRaw("LOWER(CONCAT(COALESCE(nombres, ''), ' ', COALESCE(apellidos, ''))) LIKE ?", ['%' . $searchTermLower . '%']);
                });
            });

            $queryCuotas->whereHas('boletaEmpeno.cliente', function($q) use ($searchTermLower) {
                $q->where(function($q2) use ($searchTermLower) {
                    $q2->whereRaw('LOWER(cedula_nit) LIKE ?', ['%' . $searchTermLower . '%'])
                       ->orWhereRaw("LOWER(CONCAT(COALESCE(nombres, ''), ' ', COALESCE(apellidos, ''))) LIKE ?", ['%' . $searchTermLower . '%']);
                });
            });

            $queryDesempeno->whereHas('boletaEmpeno.cliente', function($q) use ($searchTermLower) {
                $q->where(function($q2) use ($searchTermLower) {
                    $q2->whereRaw('LOWER(cedula_nit) LIKE ?', ['%' . $searchTermLower . '%'])
                       ->orWhereRaw("LOWER(CONCAT(COALESCE(nombres, ''), ' ', COALESCE(apellidos, ''))) LIKE ?", ['%' . $searchTermLower . '%']);
                });
            });

            $queryDocumentos->whereHas('cliente', function($q) use ($searchTermLower) {
                $q->where(function($q2) use ($searchTermLower) {
                    $q2->whereRaw('LOWER(cedula_nit) LIKE ?', ['%' . $searchTermLower . '%'])
                       ->orWhereRaw("LOWER(CONCAT(COALESCE(nombres, ''), ' ', COALESCE(apellidos, ''))) LIKE ?", ['%' . $searchTermLower . '%']);
                });
            });
        }

        // Obtener resultados y marcar tipos
        $empenos = $queryEmpeno->get()->map(function($empeno) {
            $empeno->tipo_registro = 'empeno';
            $empeno->signo_movimiento = 'suma';
            return $empeno;
        });

        $cuotas = $queryCuotas->get()->map(function($cuota) {
            $cuota->tipo_registro = 'cuota';
            $cuota->signo_movimiento = 'suma';

            if ($cuota->boletaEmpeno) {
                $cuota->numero_contrato = $cuota->boletaEmpeno->numero_contrato ?? '';
                $cuota->empresa_id = $cuota->boletaEmpeno->empresa_id ?? null;
                $cuota->cliente_id = $cuota->boletaEmpeno->cliente_id ?? null;
                $cuota->empresa = $cuota->boletaEmpeno->empresa ?? null;
                $cuota->cliente = $cuota->boletaEmpeno->cliente ?? null;
                $cuota->productos = $cuota->boletaEmpeno->productos ?? collect();
            } else {
                $cuota->numero_contrato = '';
                $cuota->empresa_id = null;
                $cuota->cliente_id = null;
                $cuota->empresa = null;
                $cuota->cliente = null;
                $cuota->productos = collect();
            }

            return $cuota;
        });

        $desempenos = $queryDesempeno->get()->map(function($desempeno) {
            $desempeno->tipo_registro = 'desempeno';
            $desempeno->signo_movimiento = 'resta';

            if ($desempeno->boletaEmpeno) {
                $desempeno->numero_contrato = $desempeno->boletaEmpeno->numero_contrato ?? '';
                $desempeno->empresa_id = $desempeno->boletaEmpeno->empresa_id ?? null;
                $desempeno->cliente_id = $desempeno->boletaEmpeno->cliente_id ?? null;
                $desempeno->empresa = $desempeno->boletaEmpeno->empresa ?? null;
                $desempeno->cliente = $desempeno->boletaEmpeno->cliente ?? null;
                $desempeno->productos = $desempeno->boletaEmpeno->productos ?? collect();
            } else {
                $desempeno->numero_contrato = '';
                $desempeno->empresa_id = null;
                $desempeno->cliente_id = null;
                $desempeno->empresa = null;
                $desempeno->cliente = null;
                $desempeno->productos = collect();
            }

            return $desempeno;
        });

        $documentos = $queryDocumentos->get()->map(function($documento) {
            $documento->tipo_registro = 'documento_equivalente';
            $documento->signo_movimiento = 'resta';
            $documento->numero_contrato = 'DOC-' . str_pad($documento->id, 6, '0', STR_PAD_LEFT);

            // Los documentos equivalentes tienen conceptos en lugar de productos
            $documento->productos = collect(); // Mantener vacío para compatibilidad
            $documento->concepto_nombre = $documento->concepto->nombre ?? 'Sin concepto';

            return $documento;
        });

        // Combinar y ordenar por fecha
        $movimientos = $empenos->concat($cuotas)->concat($desempenos)->concat($documentos)
                              ->sortByDesc('created_at');

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
     * Calcular totales con lógica de caja
     * Empeños y Cuotas: SUMAN (Total Ingresos)
     * Desempeños y Documentos: RESTAN (Total Egresos)
     */
    private function calcularTotales($movimientos)
    {
        $totalMontoNeto = 0;
        $totalMovimientos = $movimientos->count();
        $totalIngresos = 0;
        $totalEgresos = 0;
        $totalesPorTipoOro = [];
        $totalesPorTipoNoOro = [];

        foreach ($movimientos as $movimiento) {
            $monto = $movimiento->monto ?? 0;
            $signo = $movimiento->signo_movimiento === 'suma' ? 1 : -1;

            // Aplicar signo al monto para el total neto
            $totalMontoNeto += ($monto * $signo);

            // Separar ingresos y egresos
            if ($movimiento->signo_movimiento === 'suma') {
                $totalIngresos += $monto;
            } else {
                $totalEgresos += $monto;
            }

            // Para categorización por productos, solo considerar movimientos positivos
            if ($movimiento->signo_movimiento === 'suma') {
                $productos = $movimiento->productos ?? collect();

                if ($productos && $productos->count() > 0) {
                    $tieneOro = false;
                    $tiposOroEncontrados = [];
                    $tiposNoOroEncontrados = [];

                    foreach ($productos as $producto) {
                        if ($producto->producto) {
                            $tipoProducto = $producto->producto->tipoProducto;
                            $tipoOro = $producto->producto->tipoOro;

                            if ($tipoOro) {
                                $tieneOro = true;
                                $tiposOroEncontrados[] = $tipoOro->nombre;
                            } elseif ($tipoProducto) {
                                $tiposNoOroEncontrados[] = $tipoProducto->nombre;
                            }
                        }
                    }

                    // Remover duplicados
                    $tiposOroEncontrados = array_unique($tiposOroEncontrados);
                    $tiposNoOroEncontrados = array_unique($tiposNoOroEncontrados);

                    // Distribuir montos entre tipos encontrados
                    if ($tieneOro && count($tiposOroEncontrados) > 0) {
                        $montoPorTipoOro = $monto / count($tiposOroEncontrados);

                        foreach ($tiposOroEncontrados as $tipoOroNombre) {
                            if (!isset($totalesPorTipoOro[$tipoOroNombre])) {
                                $totalesPorTipoOro[$tipoOroNombre] = 0;
                            }
                            $totalesPorTipoOro[$tipoOroNombre] += $montoPorTipoOro;
                        }
                    }

                    if (count($tiposNoOroEncontrados) > 0) {
                        $montoPorTipoNoOro = $monto / count($tiposNoOroEncontrados);

                        foreach ($tiposNoOroEncontrados as $tipoProductoNombre) {
                            if (!isset($totalesPorTipoNoOro[$tipoProductoNombre])) {
                                $totalesPorTipoNoOro[$tipoProductoNombre] = 0;
                            }
                            $totalesPorTipoNoOro[$tipoProductoNombre] += $montoPorTipoNoOro;
                        }
                    }

                    // Si no tiene ni oro ni productos identificados, contar como no oro genérico
                    if (!$tieneOro && count($tiposNoOroEncontrados) === 0) {
                        if (!isset($totalesPorTipoNoOro['Sin especificar'])) {
                            $totalesPorTipoNoOro['Sin especificar'] = 0;
                        }
                        $totalesPorTipoNoOro['Sin especificar'] += $monto;
                    }
                } else {
                    // Si no hay productos, considerarlo como no oro sin especificar
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
            'totalIngresos' => $totalIngresos,
            'totalEgresos' => $totalEgresos,
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

        $filename = 'cuadre_caja_' . $request->fecha_desde . '_' . $request->fecha_hasta . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new CuadreCajaExport($movimientos, $request->all()), $filename);
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

        $html = view('cuadre-caja.pdf.reporte', $data)->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="cuadre_caja.pdf"');
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
