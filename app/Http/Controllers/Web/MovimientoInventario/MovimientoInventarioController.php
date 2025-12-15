<?php

namespace App\Http\Controllers\Web\MovimientoInventario;

use App\Http\Controllers\Controller;
use App\Models\MovimientoInventario;
use App\Models\MovimientoInventarioProducto;
use App\Models\BolletaEmpeno;
use App\Models\BoletaDesempeno;
use App\Models\Empresa;
use App\Models\Sede;
use App\Models\TipoMovimiento;
use App\Models\TipoProducto;
use App\Models\Cliente;
use App\Models\Producto;
use App\Exports\MovimientosInventarioExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class MovimientoInventarioController extends Controller
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
                'html' => view('movimientos-inventario.partials.movimientos-list-with-pagination', ['movimientos' => $movimientosPaginados])->render(),
                'totals' => [
                    'totalMovimientos' => number_format($totales['totalMovimientos']),
                    'totalAnulados' => number_format($totales['totalAnulados']),
                    'totalEntradas' => number_format($totales['totalEntradas']),
                    'totalSalidas' => number_format($totales['totalSalidas']),
                    'totalSaldo' => number_format($totales['totalSaldo']),
                    'totalMontoNeto' => number_format($totales['totalMontoNeto'], 2),
                    'totalOroNeto' => number_format($totales['totalOroNeto'], 2),
                    'totalNoOroNeto' => number_format($totales['totalNoOroNeto'], 2),
                    'totalesPorTipoOro' => $totales['totalesPorTipoOro'],
                    'totalesPorTipoNoOro' => $totales['totalesPorTipoNoOro'],
                    'cantidadesPorTipoOro' => $totales['cantidadesPorTipoOro'],
                    'cantidadesPorTipoNoOro' => $totales['cantidadesPorTipoNoOro']
                ]
            ]);
        }

        // Para la carga inicial
        $movimientosPaginados = $this->paginarMovimientos($movimientos, 15);
        $totales = $this->calcularTotales($movimientos);

        return view('movimientos-inventario.index', [
            'movimientos' => $movimientosPaginados,
            'empresas' => $empresas,
            'tiposMovimiento' => $tiposMovimiento,
            'tiposProducto' => $tiposProducto,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'totalMovimientos' => $totales['totalMovimientos'],
            'totalAnulados' => $totales['totalAnulados'],
            'totalEntradas' => $totales['totalEntradas'],
            'totalSalidas' => $totales['totalSalidas'],
            'totalSaldo' => $totales['totalSaldo'],
            'totalMontoNeto' => $totales['totalMontoNeto'],
            'totalOroNeto' => $totales['totalOroNeto'],
            'totalNoOroNeto' => $totales['totalNoOroNeto'],
            'totalesPorTipoOro' => $totales['totalesPorTipoOro'],
            'totalesPorTipoNoOro' => $totales['totalesPorTipoNoOro'],
            'cantidadesPorTipoOro' => $totales['cantidadesPorTipoOro'],
            'cantidadesPorTipoNoOro' => $totales['cantidadesPorTipoNoOro']
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        // Obtener empresas disponibles según el tipo de usuario
        if ($user->esAdministradorGlobal()) {
            // Administrador global: puede ver todas las empresas activas
            $empresas = Empresa::activas()->orderBy('razon_social')->get();
        } else {
            // Usuario regular: solo puede ver las empresas a las que pertenece
            $empresas = $user->empresasActivas;
        }

        // Validar que el usuario tenga acceso a al menos una empresa
        if ($empresas->isEmpty()) {
            abort(403, 'No tienes acceso a ninguna empresa para crear movimientos de inventario.');
        }

        // Si se especifica una empresa en la URL, validar acceso
        $empresa = null;
        if ($request->filled('empresa_id')) {
            $empresaId = $request->input('empresa_id');
            $empresa = $empresas->where('id', $empresaId)->first();

            if (!$empresa) {
                abort(403, 'No tienes acceso a la empresa especificada.');
            }
        } else {
            // Si no se especifica empresa y solo hay una disponible, usarla por defecto
            if ($empresas->count() === 1) {
                $empresa = $empresas->first();
            }
        }

        // Obtener sedes de la empresa seleccionada (si hay alguna)
        $sedes = collect();
        $tiposMovimiento = collect();

        if ($empresa) {
            $sedes = $empresa->sedesActivas()->orderBy('es_principal', 'desc')->orderBy('nombre')->get();
            $tiposMovimiento = TipoMovimiento::activos()
                ->where('empresa_id', $empresa->id)
                ->orderBy('nombre')
                ->get();
        }

        return view('movimientos-inventario.create', compact('empresas', 'sedes', 'empresa', 'tiposMovimiento'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        try {
            // Validación de datos
            $request->validate([
                'empresa_id' => 'required|exists:empresas,id',
                'sede_id' => 'nullable|exists:sedes,id',
                'tipo_movimiento_id' => 'required|exists:tipo_movimientos,id',
                'fecha_movimiento' => 'required|date',
                'observaciones' => 'nullable|string|max:1000',
                'observaciones_generales' => 'nullable|string|max:2000',
                'productos' => 'required|array|min:1',
                'productos.*.producto_id' => 'required|exists:productos,id',
                'productos.*.cantidad' => 'required|numeric|min:0.01',
                'productos.*.descripcion_adicional' => 'nullable|string|max:500'
            ]);

            // Verificar acceso a la empresa
            if (!$user->esAdministradorGlobal()) {
                $empresa = $user->empresasActivas->where('id', $request->empresa_id)->first();
                if (!$empresa) {
                    abort(403, 'No tienes acceso a esta empresa.');
                }
            }

            DB::beginTransaction();

            // Crear el movimiento de inventario
            $movimiento = MovimientoInventario::create([
                'empresa_id' => $request->empresa_id,
                'sede_id' => $request->sede_id,
                'tipo_movimiento_id' => $request->tipo_movimiento_id,
                'user_id' => $user->id,
                'fecha_movimiento' => $request->fecha_movimiento,
                'observaciones' => $request->observaciones,
                'observaciones_generales' => $request->observaciones_generales,
                'numero_contrato' => '', // Se generará automáticamente
            ]);

            // Generar número de contrato
            $movimiento->generarNumeroContrato();

            // Procesar productos
            foreach ($request->productos as $productoData) {
                MovimientoInventarioProducto::create([
                    'movimiento_inventario_id' => $movimiento->id,
                    'producto_id' => $productoData['producto_id'],
                    'cantidad' => $productoData['cantidad'],
                    'descripcion_adicional' => $productoData['descripcion_adicional'] ?? null,
                ]);
            }

            DB::commit();

            session()->flash('toast', [
                'title' => 'Éxito',
                'message' => 'Movimiento de inventario creado correctamente.',
                'status' => 'success'
            ]);

            return redirect()->route('movimientos-inventario.show', $movimiento);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            session()->flash('toast', [
                'title' => 'Error de validación',
                'message' => 'Por favor revisa los datos ingresados.',
                'status' => 'error'
            ]);

            return back()->withInput()->withErrors($e->validator);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear movimiento de inventario: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al crear el movimiento de inventario: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return back()->withInput();
        }
    }

    public function show(MovimientoInventario $movimientoInventario)
    {
        $user = Auth::user();

        // Verificar acceso
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $movimientoInventario->empresa_id)->first();
            if (!$empresa) {
                abort(403, 'No tienes acceso a este movimiento de inventario.');
            }
        }

        $movimientoInventario->load(['empresa', 'sede', 'usuario', 'tipoMovimiento', 'productos.producto', 'anuladoPor']);

        return view('movimientos-inventario.show', compact('movimientoInventario'));
    }

    public function anular(Request $request, MovimientoInventario $movimientoInventario)
    {
        $user = Auth::user();

        try {
            // Verificar acceso
            if (!$user->esAdministradorGlobal()) {
                $empresa = $user->empresasActivas->where('id', $movimientoInventario->empresa_id)->first();
                if (!$empresa) {
                    abort(403, 'No tienes acceso a este movimiento de inventario.');
                }
            }

            // Validar que se puede anular
            if (!$movimientoInventario->puedeSerAnulado()) {
                return response()->json([
                    'title' => 'Error',
                    'message' => 'Este movimiento de inventario no puede ser anulado.',
                    'status' => 'error'
                ], 400);
            }

            $request->validate([
                'razon_anulacion' => 'required|string|max:500'
            ]);

            $movimientoInventario->anular($request->razon_anulacion, $user);

            return response()->json([
                'title' => 'Éxito',
                'message' => 'Movimiento de inventario anulado correctamente.',
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al anular movimiento de inventario: ' . $e->getMessage());

            return response()->json([
                'title' => 'Error',
                'message' => 'Error al anular el movimiento: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    // Métodos AJAX para obtener datos dinámicos
    public function getSedes(Request $request)
    {
        $empresaId = $request->get('empresa_id');

        if (!$empresaId) {
            return response()->json([]);
        }

        $user = Auth::user();

        // Verificar acceso a la empresa
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $empresaId)->first();
            if (!$empresa) {
                return response()->json([
                    'error' => 'No tienes acceso a esta empresa.',
                    'status' => 'error'
                ], 403);
            }
        }

        try {
            $sedes = Sede::where('empresa_id', $empresaId)
                ->where('activa', true)
                ->orderBy('es_principal', 'desc')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'es_principal']);

            return response()->json($sedes);

        } catch (\Exception $e) {
            Log::error('Error al obtener sedes: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener las sedes.',
                'status' => 'error'
            ], 500);
        }
    }

    public function getTiposMovimiento(Request $request)
    {
        $empresaId = $request->get('empresa_id');
        $incluirGlobales = $request->get('incluir_globales', false);

        if (!$empresaId) {
            return response()->json([]);
        }

        $user = Auth::user();

        // Verificar acceso a la empresa
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $empresaId)->first();
            if (!$empresa) {
                return response()->json([
                    'error' => 'No tienes acceso a esta empresa.',
                    'status' => 'error'
                ], 403);
            }
        }

        try {
            if ($incluirGlobales) {
                // Devolver tipos separados por empresa y globales
                $tiposEmpresa = TipoMovimiento::where('empresa_id', $empresaId)
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->get(['id', 'nombre', 'es_suma', 'descripcion', 'empresa_id']);

                $tiposGlobales = TipoMovimiento::whereNull('empresa_id')
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->get(['id', 'nombre', 'es_suma', 'descripcion', 'empresa_id']);

                return response()->json([
                    'empresa' => $tiposEmpresa,
                    'globales' => $tiposGlobales
                ]);
            } else {
                // Devolver todos los tipos juntos (comportamiento original)
                $tiposMovimiento = TipoMovimiento::where(function($query) use ($empresaId) {
                        $query->where('empresa_id', $empresaId)
                              ->orWhereNull('empresa_id'); // Tipos globales
                    })
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->get(['id', 'nombre', 'es_suma', 'descripcion']);

                return response()->json($tiposMovimiento);
            }

        } catch (\Exception $e) {
            Log::error('Error al obtener tipos de movimiento: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener los tipos de movimiento.',
                'status' => 'error'
            ], 500);
        }
    }

    public function getPreviewNumeroContrato(Request $request)
    {
        try {
            $empresaId = $request->get('empresa_id');
            $tipoMovimientoId = $request->get('tipo_movimiento_id');
            $fecha = $request->get('fecha_movimiento', date('Y-m-d'));

            if (!$empresaId || !$tipoMovimientoId) {
                return response()->json(['numero_contrato' => '']);
            }

            $tipoMovimiento = TipoMovimiento::find($tipoMovimientoId);
            if (!$tipoMovimiento) {
                return response()->json(['numero_contrato' => '']);
            }

            $fechaCarbon = Carbon::parse($fecha);
            $anio = $fechaCarbon->year;
            $mes = $fechaCarbon->format('m');

            // Prefijo basado en el tipo de movimiento
            $prefijo = $tipoMovimiento->es_suma ? 'MIN' : 'MOU';

            // Buscar el último número del mes
            $ultimoNumero = MovimientoInventario::where('empresa_id', $empresaId)
                ->whereYear('fecha_movimiento', $anio)
                ->whereMonth('fecha_movimiento', $mes)
                ->whereHas('tipoMovimiento', function($query) use ($tipoMovimiento) {
                    $query->where('es_suma', $tipoMovimiento->es_suma);
                })
                ->orderBy('numero_contrato', 'desc')
                ->value('numero_contrato');

            $siguiente = 1;
            if ($ultimoNumero) {
                // Extraer el número secuencial del formato
                preg_match('/(\d+)$/', $ultimoNumero, $matches);
                if (!empty($matches[1])) {
                    $siguiente = intval($matches[1]) + 1;
                }
            }

            $numeroContrato = sprintf('%s-%04d-%s-%04d',
                $prefijo,
                $empresaId,
                $anio . $mes,
                $siguiente
            );

            return response()->json(['numero_contrato' => $numeroContrato]);

        } catch (\Exception $e) {
            Log::error('Error al generar preview del número de contrato: ' . $e->getMessage());
            return response()->json(['numero_contrato' => '']);
        }
    }

    public function getProductos(Request $request)
    {
        $empresaId = $request->get('empresa_id');

        if (!$empresaId) {
            return response()->json([]);
        }

        $user = Auth::user();

        // Verificar acceso a la empresa
        if (!$user->esAdministradorGlobal()) {
            $empresa = $user->empresasActivas->where('id', $empresaId)->first();
            if (!$empresa) {
                return response()->json([
                    'error' => 'No tienes acceso a esta empresa.',
                    'status' => 'error'
                ], 403);
            }
        }

        try {
            $productos = \App\Models\Producto::where('empresa_id', $empresaId)
                ->with(['tipoProducto', 'tipoOro', 'tipoMedida', 'empresa'])
                ->orderBy('nombre')
                ->get();

            // Agregar URL de imagen a cada producto
            $productos->each(function ($producto) {
                if ($producto->imagen) {
                    $producto->imagen_url = \Illuminate\Support\Facades\Storage::url($producto->imagen);
                } else {
                    $producto->imagen_url = null;
                }
            });

            return response()->json($productos);

        } catch (\Exception $e) {
            Log::error('Error al obtener productos: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener los productos.',
                'status' => 'error'
            ], 500);
        }
    }

    public function crearProducto(Request $request)
    {
        $user = Auth::user();

        try {
            // Validar los datos
            $request->validate([
                'nombre' => 'required|string|max:255',
                'tipo_producto_id' => 'required|exists:tipo_productos,id',
                'tipo_oro_id' => 'nullable|exists:tipo_oros,id',
                'empresa_id' => 'required|exists:empresas,id',
                'descripcion' => 'nullable|string|max:1000',
                'codigo_barras' => 'nullable|string|max:50',
                'precio_venta' => 'nullable|numeric|min:0',
                'precio_compra' => 'nullable|numeric|min:0',
                'tipo_medida_id' => 'nullable|exists:tipo_medidas,id',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            // Verificar acceso a la empresa
            if (!$user->esAdministradorGlobal()) {
                $empresa = $user->empresasActivas->where('id', $request->empresa_id)->first();
                if (!$empresa) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes acceso a esta empresa.'
                    ], 403);
                }
            }

            DB::beginTransaction();

            // Crear el producto
            $producto = \App\Models\Producto::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'codigo_barras' => $request->codigo_barras,
                'precio_venta' => $request->precio_venta,
                'precio_compra' => $request->precio_compra,
                'tipo_producto_id' => $request->tipo_producto_id,
                'tipo_oro_id' => $request->tipo_oro_id,
                'tipo_medida_id' => $request->tipo_medida_id,
                'empresa_id' => $request->empresa_id,
            ]);

            // Manejar la imagen si se proporciona
            if ($request->hasFile('imagen')) {
                $imagen = $request->file('imagen');
                $nombreImagen = time() . '_' . $imagen->getClientOriginalName();
                $rutaImagen = $imagen->storeAs('productos', $nombreImagen, 'public');
                $producto->imagen = $rutaImagen;
                $producto->save();
            }

            // Cargar relaciones para la respuesta
            $producto->load(['tipoProducto', 'tipoOro', 'tipoMedida']);
            $producto->imagen_url = $producto->imagen ? asset('storage/' . $producto->imagen) : null;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente.',
                'producto' => $producto
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear producto: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            $user = Auth::user();

            // Obtener fechas del request o usar valores por defecto
            $fechaDesde = $request->input('fecha_desde');
            $fechaHasta = $request->input('fecha_hasta');

            // Si no hay fechas, usar mes actual por defecto (igual que en index)
            if (!$fechaDesde || !$fechaHasta) {
                $fechaDesde = now()->startOfMonth()->format('Y-m-d');
                $fechaHasta = now()->endOfMonth()->format('Y-m-d');
            }

            // Validar que las fechas sean válidas
            $validator = validator([
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta
            ], [
                'fecha_desde' => 'required|date',
                'fecha_hasta' => 'required|date|after_or_equal:fecha_desde'
            ]);

            if ($validator->fails()) {
                session()->flash('toast', [
                    'title' => 'Error de Validación',
                    'message' => 'Las fechas proporcionadas no son válidas: ' . implode(', ', $validator->errors()->all()),
                    'status' => 'error'
                ]);

                return redirect()->back();
            }

            // Crear un nuevo request con las fechas aseguradas
            $requestWithDates = clone $request;
            $requestWithDates->merge([
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta
            ]);

            // Obtener movimientos unificados con los mismos filtros que InventarioController
            $movimientos = $this->obtenerMovimientosUnificados($user, $requestWithDates, $fechaDesde, $fechaHasta);

            // Debug: Log información de movimientos para export
            Log::info('MovimientoInventarioController - Export datos', [
                'total_movimientos' => $movimientos->count(),
                'tipos_registro' => $movimientos->groupBy('tipo_registro')->map->count()->toArray(),
                'fechas' => ['desde' => $fechaDesde, 'hasta' => $fechaHasta],
                'filtros' => $requestWithDates->all()
            ]);

            $filename = 'movimientos_inventario_' . $fechaDesde . '_' . $fechaHasta . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(new MovimientosInventarioExport($movimientos, $requestWithDates->all()), $filename);

        } catch (\Exception $e) {
            Log::error('Error al exportar movimientos de inventario: ' . $e->getMessage());

            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Error al exportar los movimientos de inventario: ' . $e->getMessage(),
                'status' => 'error'
            ]);

            return redirect()->back();
        }
    }

    /**
     * Obtener movimientos unificados (BolletaEmpeno + BoletaDesempeno + MovimientoInventario)
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
                DB::raw('COALESCE(fecha_prestamo, created_at) as fecha_movimiento'),
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
                DB::raw('NULL as observaciones'),
                DB::raw('NULL as observaciones_generales'),
                DB::raw('NULL as razon_anulacion'),
                DB::raw('NULL as anulado_por'),
                DB::raw('NULL as anulado_at')
            ])
            ->with([
                'cliente:id,nombres,apellidos,razon_social,cedula_nit,tipo_documento_id,telefono_fijo,celular',
                'cliente.tipoDocumento:id,abreviacion',
                'empresa:id,razon_social',
                'sede:id,nombre',
                'tipoMovimiento:id,nombre,es_suma',
                'productos.producto.tipoProducto:id,nombre',
                'productos.producto.tipoOro:id,nombre',
                'usuario:id,name'
            ]);

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
                DB::raw('COALESCE(fecha_abono, created_at) as fecha_movimiento'),
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
                'observaciones',
                DB::raw('NULL as observaciones_generales'),
                DB::raw('NULL as razon_anulacion'),
                DB::raw('NULL as anulado_por'),
                DB::raw('NULL as anulado_at')
            ])
            ->with([
                'boletaEmpeno' => function($query) {
                    $query->select('id', 'cliente_id', 'empresa_id', 'sede_id', 'numero_contrato')
                          ->with([
                              'cliente:id,nombres,apellidos,razon_social,cedula_nit,tipo_documento_id,telefono_fijo,celular',
                              'cliente.tipoDocumento:id,abreviacion',
                              'empresa:id,razon_social',
                              'sede:id,nombre',
                              'productos.producto.tipoProducto:id,nombre',
                              'productos.producto.tipoOro:id,nombre'
                          ]);
                },
                'tipoMovimiento:id,nombre,es_suma',
                'usuario:id,name'
            ]);

        // Subconsulta para MovimientoInventario
        $queryMovimientoInventario = MovimientoInventario::query()
            ->select([
                'id',
                'numero_contrato',
                DB::raw('NULL as cliente_id'),
                'empresa_id',
                'sede_id',
                'user_id',
                DB::raw('0 as monto'), // Los movimientos de inventario no tienen monto directo
                'tipo_movimiento_id',
                'fecha_movimiento',
                DB::raw('NULL as fecha_vencimiento'),
                DB::raw("'activo' as estado"),
                'created_at',
                'updated_at',
                'anulado as anulada',
                DB::raw('NULL as foto_prenda'),
                DB::raw('NULL as ubicacion'),
                DB::raw("'movimiento_inventario' as tipo_registro"),
                DB::raw('NULL as bolleta_empeno_id'),
                DB::raw('NULL as fecha_abono'),
                'observaciones',
                'observaciones_generales',
                'razon_anulacion',
                'anulado_por',
                'fecha_anulacion as anulado_at'
            ])
            ->with([
                'empresa:id,razon_social',
                'sede:id,nombre',
                'tipoMovimiento:id,nombre,es_suma',
                'usuario:id,name',
                'productos.producto.tipoProducto:id,nombre',
                'productos.producto.tipoOro:id,nombre',
                'anuladoPor:id,name'
            ]);

        // Aplicar filtros según permisos del usuario
        if (!$user->esAdministradorGlobal()) {
            $empresasUsuario = $user->empresasActivas->pluck('id');

            $queryEmpeno->whereIn('empresa_id', $empresasUsuario);
            $queryMovimientoInventario->whereIn('empresa_id', $empresasUsuario);

            $queryDesempeno->whereHas('boletaEmpeno', function($q) use ($empresasUsuario) {
                $q->whereIn('empresa_id', $empresasUsuario);
            });
        }

        // Aplicar filtros de fecha
        $queryEmpeno->whereDate('created_at', '>=', $fechaDesde)
                   ->whereDate('created_at', '<=', $fechaHasta);

        $queryDesempeno->whereDate('created_at', '>=', $fechaDesde)
                       ->whereDate('created_at', '<=', $fechaHasta);

        $queryMovimientoInventario->whereDate('fecha_movimiento', '>=', $fechaDesde)
                                  ->whereDate('fecha_movimiento', '<=', $fechaHasta);

        // Filtro para mostrar/ocultar anulados
        $mostrarAnulados = $request->input('mostrar_anulados');

        // Si no se especifica o es 'false', ocultar anulados
        if ($mostrarAnulados !== 'true') {
            $queryEmpeno->where('anulada', false);
            $queryMovimientoInventario->where('anulado', false);
            // BoletaDesempeno generalmente no se anulan, pero por consistencia:
            // $queryDesempeno->where('anulado', false); // Descomentar si BoletaDesempeno tiene campo anulado
        }

        // Filtro por empresa
        if ($request->filled('empresa_id')) {
            $queryEmpeno->where('empresa_id', $request->empresa_id);
            $queryMovimientoInventario->where('empresa_id', $request->empresa_id);

            $queryDesempeno->whereHas('boletaEmpeno', function($q) use ($request) {
                $q->where('empresa_id', $request->empresa_id);
            });
        }

        // Filtro por tipo de movimiento
        if ($request->filled('tipo_movimiento_id')) {
            $queryEmpeno->where('tipo_movimiento_id', $request->tipo_movimiento_id);
            $queryDesempeno->where('tipo_movimiento_id', $request->tipo_movimiento_id);
            $queryMovimientoInventario->where('tipo_movimiento_id', $request->tipo_movimiento_id);
        }

        // Filtro por tipo de producto
        if ($request->filled('tipo_producto_id')) {
            $queryEmpeno->whereHas('productos.producto', function($q) use ($request) {
                $q->where('tipo_producto_id', $request->tipo_producto_id);
            });

            $queryDesempeno->whereHas('boletaEmpeno.productos.producto', function($q) use ($request) {
                $q->where('tipo_producto_id', $request->tipo_producto_id);
            });

            $queryMovimientoInventario->whereHas('productos.producto', function($q) use ($request) {
                $q->where('tipo_producto_id', $request->tipo_producto_id);
            });
        }

        // Filtro por número de contrato
        if ($request->filled('numero_contrato')) {
            $numeroContrato = trim($request->numero_contrato);
            $queryEmpeno->whereRaw('LOWER(numero_contrato) LIKE ?', ['%' . strtolower($numeroContrato) . '%']);
            $queryMovimientoInventario->whereRaw('LOWER(numero_contrato) LIKE ?', ['%' . strtolower($numeroContrato) . '%']);

            $queryDesempeno->whereHas('boletaEmpeno', function($q) use ($numeroContrato) {
                $q->whereRaw('LOWER(numero_contrato) LIKE ?', ['%' . strtolower($numeroContrato) . '%']);
            });
        }

        // Filtro por cliente (solo para empeños)
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

        // Filtro por búsqueda general (para MovimientoInventario)
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $queryMovimientoInventario->where(function($q) use ($searchTerm) {
                $q->where('numero_contrato', 'like', "%{$searchTerm}%")
                  ->orWhere('observaciones', 'like', "%{$searchTerm}%")
                  ->orWhere('observaciones_generales', 'like', "%{$searchTerm}%")
                  ->orWhereHas('usuario', function($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Obtener resultados
        $empenos = $queryEmpeno->get();
        $desempenos = $queryDesempeno->get();
        $movimientosInventario = $queryMovimientoInventario->get();

        // Normalizar datos de desempeños para que tengan acceso directo a empresa, cliente, etc.
        $desempenos->each(function($desempeno) {
            if ($desempeno->boletaEmpeno) {
                $desempeno->empresa = $desempeno->boletaEmpeno->empresa;
                $desempeno->sede = $desempeno->boletaEmpeno->sede;
                $desempeno->cliente = $desempeno->boletaEmpeno->cliente;
                $desempeno->numero_contrato = $desempeno->boletaEmpeno->numero_contrato;
                $desempeno->empresa_id = $desempeno->boletaEmpeno->empresa_id;
                $desempeno->sede_id = $desempeno->boletaEmpeno->sede_id;
                $desempeno->cliente_id = $desempeno->boletaEmpeno->cliente_id;

                // Para acceso a productos
                if (!$desempeno->relationLoaded('productos') && $desempeno->boletaEmpeno->relationLoaded('productos')) {
                    $desempeno->setRelation('productos', $desempeno->boletaEmpeno->productos);
                }
            }
        });

        // Normalizar el atributo anulado/anulada para todos los tipos
        $empenos->each(function($empeno) {
            $empeno->anulado = $empeno->anulada;
        });

        $desempenos->each(function($desempeno) {
            $desempeno->anulado = false; // Los desempeños generalmente no se anulan
        });

        // Combinar y ordenar por fecha
        $movimientos = $empenos->concat($desempenos)->concat($movimientosInventario)->sortByDesc('created_at');

        return $movimientos;
    }

    /**
     * Paginar movimientos manualmente
     * Copiado exactamente del InventarioController
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
     * Calcular totales para la vista de movimientos de inventario
     */
    private function calcularTotales($movimientos)
    {
        $totalMovimientos = $movimientos->count();
        $totalAnulados = 0;
        $totalEntradas = 0;
        $totalSalidas = 0;
        $totalMontoNeto = 0;
        $totalOroNeto = 0;
        $totalNoOroNeto = 0;
        $totalesPorTipoOro = [];
        $totalesPorTipoNoOro = [];
        $cantidadesPorTipoOro = [];
        $cantidadesPorTipoNoOro = [];

        foreach ($movimientos as $movimiento) {
            // Verificar si está anulado
            $esAnulado = $movimiento->anulado || $movimiento->anulada;

            // Contar anulados
            if ($esAnulado) {
                $totalAnulados++;
            }

            // Contar entradas y salidas solo si NO están anulados
            if (!$esAnulado) {
                $tipoMovimiento = $movimiento->tipoMovimiento;
                if ($tipoMovimiento) {
                    if ($tipoMovimiento->es_suma) {
                        $totalEntradas++;
                    } else {
                        $totalSalidas++;
                    }
                }
            }

            // Calcular totales de montos y oro
            $tipoMovimiento = $movimiento->tipoMovimiento;
            $monto = 0;

            // Para movimientos de inventario, calcular el monto basado en productos
            if ($movimiento->tipo_registro === 'movimiento_inventario' && !$esAnulado && $tipoMovimiento) {
                $montoCalculado = 0;
                foreach ($movimiento->productos as $movProducto) {
                    if ($movProducto->producto && $movProducto->producto->precio_compra) {
                        $montoCalculado += $movProducto->producto->precio_compra * $movProducto->cantidad;
                    }
                }

                // Aplicar suma o resta según el tipo de movimiento
                if ($tipoMovimiento->es_suma) {
                    $monto = $montoCalculado;
                    $totalMontoNeto += $monto;
                } else {
                    $monto = $montoCalculado;
                    $totalMontoNeto -= $monto; // Restar para movimientos de resta
                }
            } else {
                // Para empeños y desempeños, usar el monto existente
                $monto = $movimiento->monto ?? 0;

                // Aplicar suma o resta según el tipo de movimiento para empeños y desempeños también
                if (!$esAnulado && $tipoMovimiento) {
                    if ($tipoMovimiento->es_suma) {
                        $totalMontoNeto += $monto; // SUMA (empeños)
                    } else {
                        $totalMontoNeto -= $monto; // RESTA (desempeños)
                    }
                }
            }

            // Calcular totales por tipo de oro y no oro (solo si hay monto y no está anulado)
            if (!$esAnulado && $monto > 0) {
                $productos = null;
                if ($movimiento->tipo_registro === 'empeno') {
                    $productos = $movimiento->productos;
                } elseif ($movimiento->tipo_registro === 'desempeno') {
                    $productos = $movimiento->boletaEmpeno?->productos;
                } elseif ($movimiento->tipo_registro === 'movimiento_inventario') {
                    $productos = $movimiento->productos;
                }

                if ($productos && $productos->count() > 0) {
                    $tieneOro = false;
                    $tiposOroEncontrados = [];
                    $tiposNoOroEncontrados = [];

                    foreach ($productos as $producto) {
                        if ($producto->producto) {
                            $cantidad = $producto->cantidad ?? 1; // Cantidad del producto en el movimiento

                            if ($producto->producto->tipoOro) {
                                // Producto de oro
                                $tieneOro = true;
                                $tipoOro = $producto->producto->tipoOro;
                                $tipoOroNombre = $tipoOro->nombre;

                                if (!in_array($tipoOroNombre, $tiposOroEncontrados)) {
                                    $tiposOroEncontrados[] = $tipoOroNombre;
                                }

                                // Contar cantidades por tipo de oro
                                if (!isset($cantidadesPorTipoOro[$tipoOroNombre])) {
                                    $cantidadesPorTipoOro[$tipoOroNombre] = 0;
                                }
                                $cantidadesPorTipoOro[$tipoOroNombre] += $cantidad;
                            } else {
                                // Producto no oro
                                $tipoProducto = $producto->producto->tipoProducto;
                                if ($tipoProducto) {
                                    $tipoProductoNombre = $tipoProducto->nombre;

                                    if (!in_array($tipoProductoNombre, $tiposNoOroEncontrados)) {
                                        $tiposNoOroEncontrados[] = $tipoProductoNombre;
                                    }

                                    // Contar cantidades por tipo de producto no oro
                                    if (!isset($cantidadesPorTipoNoOro[$tipoProductoNombre])) {
                                        $cantidadesPorTipoNoOro[$tipoProductoNombre] = 0;
                                    }
                                    $cantidadesPorTipoNoOro[$tipoProductoNombre] += $cantidad;
                                }
                            }
                        }
                    }

                    // Determinar el factor multiplicador según el tipo de movimiento
                    $factor = 1; // Por defecto suma
                    if ($tipoMovimiento && !$tipoMovimiento->es_suma) {
                        $factor = -1; // Restar para todos los movimientos de resta (desempeños e inventario)
                    }

                    // Distribuir montos entre tipos encontrados
                    if ($tieneOro && count($tiposOroEncontrados) > 0) {
                        $montoPorTipoOro = $monto / count($tiposOroEncontrados);
                        $totalOroNeto += $monto * $factor;

                        foreach ($tiposOroEncontrados as $tipoOroNombre) {
                            if (!isset($totalesPorTipoOro[$tipoOroNombre])) {
                                $totalesPorTipoOro[$tipoOroNombre] = 0;
                            }
                            $totalesPorTipoOro[$tipoOroNombre] += $montoPorTipoOro * $factor;
                        }
                    }

                    if (count($tiposNoOroEncontrados) > 0) {
                        $montoPorTipoNoOro = $monto / count($tiposNoOroEncontrados);
                        $totalNoOroNeto += $monto * $factor;

                        foreach ($tiposNoOroEncontrados as $tipoProductoNombre) {
                            if (!isset($totalesPorTipoNoOro[$tipoProductoNombre])) {
                                $totalesPorTipoNoOro[$tipoProductoNombre] = 0;
                            }
                            $totalesPorTipoNoOro[$tipoProductoNombre] += $montoPorTipoNoOro * $factor;
                        }
                    }

                    // Si no tiene ni oro ni productos identificados, contar como no oro genérico
                    if (!$tieneOro && count($tiposNoOroEncontrados) === 0) {
                        $totalNoOroNeto += $monto * $factor;
                        if (!isset($totalesPorTipoNoOro['Sin especificar'])) {
                            $totalesPorTipoNoOro['Sin especificar'] = 0;
                        }
                        $totalesPorTipoNoOro['Sin especificar'] += $monto * $factor;
                    }
                } else {
                    // Si no hay productos, considerarlo como no oro sin especificar
                    $factor = 1; // Por defecto suma
                    if ($tipoMovimiento && !$tipoMovimiento->es_suma) {
                        $factor = -1; // Restar para todos los movimientos de resta (desempeños e inventario)
                    }

                    $totalNoOroNeto += $monto * $factor;
                    if (!isset($totalesPorTipoNoOro['Sin especificar'])) {
                        $totalesPorTipoNoOro['Sin especificar'] = 0;
                    }
                    $totalesPorTipoNoOro['Sin especificar'] += $monto * $factor;
                }
            }
        }

        return [
            'totalMovimientos' => $totalMovimientos,
            'totalAnulados' => $totalAnulados,
            'totalEntradas' => $totalEntradas,
            'totalSalidas' => $totalSalidas,
            'totalSaldo' => $totalEntradas - $totalSalidas,
            'totalMontoNeto' => $totalMontoNeto,
            'totalOroNeto' => $totalOroNeto,
            'totalNoOroNeto' => $totalNoOroNeto,
            'totalesPorTipoOro' => $totalesPorTipoOro,
            'totalesPorTipoNoOro' => $totalesPorTipoNoOro,
            'cantidadesPorTipoOro' => $cantidadesPorTipoOro,
            'cantidadesPorTipoNoOro' => $cantidadesPorTipoNoOro
        ];
    }    public function buscarClientes(Request $request)
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
}
