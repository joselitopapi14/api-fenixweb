<?php

namespace App\Http\Controllers\Api\Factura;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\TipoMovimiento;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\MedioPago;
use App\Models\TipoPago;
use App\Models\Producto;
use App\Models\FacturaHasProduct;
use App\Models\FacturaHasRetencione;
use App\Models\TipoRetencion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        $query = Factura::with(['cliente:id,nombres,apellidos,razon_social,cedula_nit', 'tipoMovimiento:id,nombre', 'empresa:id,razon_social']);

        // Filtro por Usuario/Rol (Seguridad y multi-tenancy)
        $user = auth()->user();
        if (!$user->esAdministradorGlobal()) {
            $empresasIds = $user->empresasActivas->pluck('id');
            $query->whereIn('empresa_id', $empresasIds);
        }

        // Filtro: Buscar (Número de factura o Cliente)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_factura', 'ilike', "%{$search}%")
                  ->orWhereHas('cliente', function ($q2) use ($search) {
                      $q2->where('nombres', 'ilike', "%{$search}%")
                         ->orWhere('apellidos', 'ilike', "%{$search}%")
                         ->orWhere('razon_social', 'ilike', "%{$search}%")
                         ->orWhereRaw('CAST(cedula_nit AS TEXT) ilike ?', ["%{$search}%"]);
                  });
            });
        }

        // Filtro: Estado
        if ($request->filled('estado') && $request->estado !== 'Todos') {
            $query->where('estado', $request->estado);
        }

        // Filtro: Fecha desde (Issue Date)
        if ($request->filled('fecha_desde')) {
            $query->whereDate('issue_date', '>=', $request->fecha_desde);
        }
        
        // Filtro: Fecha hasta
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('issue_date', '<=', $request->fecha_hasta);
        }

        // Ordenamiento
        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 15);
        $facturas = $query->paginate($perPage);

        return response()->json($facturas);
    }

    public function show($id)
    {
        $factura = Factura::with(['cliente', 'tipoMovimiento', 'facturaHasProducts.product', 'facturaHasRetenciones.retencion', 'vendedor'])
            ->find($id);

        if (!$factura) {
            return response()->json(['message' => 'Factura no encontrada'], 404);
        }

        return response()->json($factura);
    }

    public function store(Request $request)
    {
        try {
            // 1. Recepción y limpieza de datos
            $request->offsetUnset('total');
            $request->offsetUnset('subtotal');
            $request->offsetUnset('valor_impuestos');
            $this->cleanRequestData($request);

            // Asignar vendedor_id
            $request->merge(['user_id' => auth()->id() ?? $request->user_id]);

            // 2. Validaciones básicas
            $this->validateBasicData($request);

            // 3. Validación de productos e impuestos
            $this->validateTaxesProducts($request);

            // 5. Generación de número de factura
            $numeroFactura = $this->generateFacturaNumber($request);

            // 6. Cálculo de montos totales
            $amounts = $this->calculateFacturaAmounts($request);

            // 7. Validación de reglas de negocio
            $this->validateBusinessRules($request, $amounts);

            // 9. Transacción de base de datos
            return DB::transaction(function () use ($request, $numeroFactura, $amounts) {
                // Crear factura principal
                $factura = Factura::create(array_merge($request->all(), [
                    'numero_factura' => $numeroFactura,
                    'subtotal' => $amounts['subtotal'],
                    'valor_impuestos' => $amounts['valor_impuestos'],
                    'total' => $amounts['total'],
                    'cambio' => $amounts['cambio'] ?? null,
                    'issue_date' => now()->toDateString(),
                    'estado' => 'creada',
                ]));

                // Guardar productos
                $this->saveFacturaDetails($factura, $request->productos);

                // Guardar retenciones si aplica
                if ($request->has('retenciones') && !empty($request->retenciones)) {
                    $this->saveFacturaRetenciones($factura, $request->retenciones, $amounts['total']);
                }

                // Actualizar consecutivo
                // $this->updateConsecutiveNumber($request->tipo_movimiento_id);

                return response()->json([
                    'message' => 'Factura creada exitosamente',
                    'factura' => $factura->load(['facturaHasProducts.product', 'facturaHasRetenciones']),
                ], 201);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación al crear factura', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al crear factura', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'message' => 'Error al crear la factura',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function cleanRequestData(Request $request)
    {
        // Implementar limpieza de datos no permitidos
    }

    private function validateBasicData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_movimiento_id' => 'required|exists:tipo_movimientos,id',
            'tipo_factura_id' => 'required|exists:tipo_facturas,id',
            'cliente_id' => 'required|exists:clientes,id',
            'empresa_id' => 'required|exists:empresas,id',
            'medio_pago_id' => 'required|exists:medio_pagos,id',
            'tipo_pagos_id' => 'required|exists:tipo_pagos,id',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|numeric|min:0.01',
            'productos.*.descuento' => 'nullable|numeric|min:0',
            'productos.*.recargo' => 'nullable|numeric|min:0',
            'retenciones' => 'nullable|array',
            'retenciones.*.retencion_id' => 'required|exists:tipo_retenciones,id',
            'retenciones.*.concepto_retencion_id' => 'required|exists:concepto_retenciones,id',
            'retenciones.*.porcentaje_valor' => 'required|numeric|min:0|max:100',
            'valor_recibido' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $this->logValidationError($validator->errors());
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    private function validateTaxesProducts(Request $request)
    {
        // Verificar que productos existen y pertenecen a la empresa
        foreach ($request->productos as $producto) {
            $prod = Producto::where('id', $producto['id'])
                ->where('empresa_id', $request->empresa_id)
                ->first();
            if (!$prod) {
                throw new \Exception("Producto {$producto['id']} no encontrado o no pertenece a la empresa");
            }
        }
    }

    private function generateFacturaNumber(Request $request)
    {
        if ($request->has('numero_factura')) {
            return $request->numero_factura;
        } else {
            // Generar nuevo número simple
            return 'FV-' . time();
        }
    }

    private function calculateFacturaAmounts(Request $request)
    {
        $subtotal = 0;
        $valorImpuestos = 0;

        foreach ($request->productos as $producto) {
            $prod = Producto::with('impuestos.impuestoPorcentajes')->find($producto['id']);
            $cantidad = $producto['cantidad'];
            $descuento = $producto['descuento'] ?? 0;
            $recargo = $producto['recargo'] ?? 0;

            $subtotalBase = $cantidad * $prod->precio_venta;
            $subtotalAjustado = $subtotalBase - $descuento + $recargo;

            // Calcular impuestos
            $tasaImpuestoTotal = 0;
            foreach ($prod->impuestos as $impuesto) {
                foreach ($impuesto->impuestoPorcentajes as $porcentaje) {
                    $tasaImpuestoTotal += $porcentaje->percentage / 100;
                }
            }

            if ($tasaImpuestoTotal > 0) {
                $baseGravable = $subtotalAjustado / (1 + $tasaImpuestoTotal);
                $valorImpuesto = $subtotalAjustado - $baseGravable;
            } else {
                $baseGravable = $subtotalAjustado;
                $valorImpuesto = 0;
            }

            $subtotal += round($baseGravable, 2);
            $valorImpuestos += round($valorImpuesto, 2);
        }

        $total = $subtotal + $valorImpuestos;
        $cambio = null;
        if ($request->has('valor_recibido')) {
            $cambio = $request->valor_recibido - $total;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'valor_impuestos' => round($valorImpuestos, 2),
            'total' => round($total, 2),
            'cambio' => $cambio ? round($cambio, 2) : null,
        ];
    }

    private function validateBusinessRules(Request $request, $amounts)
    {
        if ($request->has('valor_recibido') && $request->valor_recibido < $amounts['total']) {
            throw new \Exception("Valor recibido es menor al total de la factura");
        }
    }

    private function saveFacturaDetails(Factura $factura, $productos)
    {
        foreach ($productos as $producto) {
            $prod = Producto::find($producto['id']);
            $cantidad = $producto['cantidad'];
            $descuento = $producto['descuento'] ?? 0;
            $recargo = $producto['recargo'] ?? 0;

            $subtotalBase = $cantidad * $prod->precio_venta;
            $subtotalAjustado = $subtotalBase - $descuento + $recargo;

            FacturaHasProduct::create([
                'factura_id' => $factura->id,
                'producto_id' => $prod->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $prod->precio_venta,
                'subtotal' => round($subtotalAjustado, 2),
                'descuento' => $descuento,
                'recargo' => $recargo,
            ]);
        }
    }

    private function saveFacturaRetenciones(Factura $factura, $retenciones, $total)
    {
        foreach ($retenciones as $retencion) {
            $valor = ($total * $retencion['porcentaje_valor']) / 100;

            FacturaHasRetencione::create([
                'factura_id' => $factura->id,
                'retencion_id' => $retencion['retencion_id'],
                'concepto_retencion_id' => $retencion['concepto_retencion_id'],
                'valor' => round($valor, 2),
                'percentage' => $retencion['porcentaje_valor'],
            ]);
        }
    }

    private function logValidationError($errors)
    {
        Log::error('Errores de validación en creación de factura', ['errors' => $errors]);
    }
}
