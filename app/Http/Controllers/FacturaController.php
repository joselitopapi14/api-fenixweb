<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Cliente;
use App\Models\TipoMovimiento;
use App\Models\TipoFactura;
use App\Models\Empresa;
use App\Models\MedioPago;
use App\Models\TipoPago;
use App\Models\Impuesto;
use App\Models\Producto;
use App\Models\ResolucionFacturacion;
use App\Models\FacturaHasProduct;
use App\Models\FacturaHasRetencione;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Exception;

class FacturaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $facturas = Factura::with([
            'cliente',
            'user',
            'tipoMovimiento',
            'tipoFactura',
            'empresa',
            'medioPago',
            'tipoPago'
        ])->orderBy('created_at', 'desc')->paginate(15);

        return view('facturas.index', compact('facturas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clientes = Cliente::all();
        $tiposMovimiento = TipoMovimiento::all();
        $tiposFactura = TipoFactura::all();
        $empresas = Empresa::all();
        $mediosPago = MedioPago::all();
        $tiposPago = TipoPago::all();
        $impuestos = Impuesto::all();
        $productos = Producto::all();

        return view('facturas.create', compact(
            'clientes',
            'tiposMovimiento',
            'tiposFactura',
            'empresas',
            'mediosPago',
            'tiposPago',
            'impuestos',
            'productos'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //Log all the request data for debugging
        Log::info('Request data for creating factura', ['data' => $request->all()]);
        $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'resolucion_id' => 'required|exists:resoluciones_facturacion,id',
            'cliente_id' => 'required|exists:clientes,id',
            'tipo_factura_id' => 'required|exists:tipo_facturas,id',
            'medio_pago_id' => 'required|exists:medio_pagos,id',
            'tipo_pago_id' => 'required|exists:tipo_pagos,id',
            'plazo_dias' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'subtotal' => 'required|numeric|min:0',
            'valor_impuestos' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'valor_recibido' => 'nullable|numeric|min:0',
            'cambio' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
            'productos' => 'required|json',
            'retenciones' => 'nullable|json',
        ]);

        DB::beginTransaction();
        try {
            Log::info('Creando factura', ['request_data' => $request->all()]);

            // Decodificar productos y retenciones
            $productosArray = json_decode($request->productos, true);
            $retencionesArray = json_decode($request->retenciones, true) ?? [];

            // LOG: Verificar que los productos incluyen nombre_personalizado
            Log::info('Productos decodificados', ['productos' => $productosArray]);

            if (empty($productosArray)) {
                throw new Exception('Debe agregar al menos un producto a la factura');
            }

            // Cargar resolución
            $resolucion = ResolucionFacturacion::findOrFail($request->resolucion_id);

            // Si la resolución indica que se debe enviar a DIAN, intentar la integración
            if ($resolucion && $resolucion->envia_dian) {
                Log::info('Preparando integración con DIAN para factura', ['resolucion_id' => $resolucion->id]);

                $resultado = $this->enviarFacturaDian($request, $resolucion, $productosArray, $retencionesArray);

                // Verificar que la factura fue validada exitosamente por la DIAN
                if (!$resultado['is_valid']) {
                    DB::rollBack();

                    // Formatear errores para mostrar
                    $errores = $resultado['errors'];
                    $erroresFormateados = [];

                    if (is_array($errores) && !empty($errores)) {
                        foreach ($errores as $error) {
                            $erroresFormateados[] = $error;
                        }
                    }

                    Log::error('Factura rechazada por DIAN', ['errores' => $errores]);

                    return back()->withInput()->withErrors([
                        'dian_errors' => $erroresFormateados ?: ['La factura no pudo ser procesada por la DIAN.']
                    ]);
                }

                // Actualizar consecutivo de la resolución
                $nextConsecutivo = ($resolucion->consecutivo_actual ?? 0) + 1;
                $resolucion->consecutivo_actual = $nextConsecutivo;
                $resolucion->save();

                // Crear la factura con los datos de la DIAN
                $numeroFactura = $resultado['numero_factura'];

                $factura = Factura::create([
                    'numero_factura' => $numeroFactura,
                    'cliente_id' => $request->cliente_id,
                    'user_id' => auth()->id(),
                    'tipo_movimiento_id' => $resolucion->id, // Usamos la resolución como tipo_movimiento
                    'tipo_factura_id' => $request->tipo_factura_id,
                    'empresa_id' => $request->empresa_id,
                    'medio_pago_id' => $request->medio_pago_id,
                    'tipo_pago_id' => $request->tipo_pago_id,
                    'total' => $request->total,
                    'valor_impuestos' => $request->valor_impuestos ?? 0,
                    'issue_date' => now(),
                    'due_date' => $request->due_date,
                    'valor_recibido' => $request->valor_recibido,
                    'cambio' => $request->cambio,
                    'subtotal' => $request->subtotal,
                    'estado' => $resultado['estado'],
                    'cufe' => $resultado['cufe'],
                    'xml_url' => $resultado['xml_url'],
                    'obseraciones' => $request->observaciones,
                ]);

                // Guardar productos
                foreach ($productosArray as $productoData) {
                    $producto = Producto::find($productoData['id']);
                    if (!$producto) continue;

                    $cantidad = $productoData['cantidad'] ?? 1;
                    $descuento = $productoData['descuento'] ?? 0;
                    $recargo = $productoData['recargo'] ?? 0;
                    $nombrePersonalizado = $productoData['nombre_personalizado'] ?? null;
                    $precioUnitario = $producto->precio_venta ?? 0;
                    $subtotalProducto = ($precioUnitario * $cantidad) - $descuento + $recargo;

                    FacturaHasProduct::create([
                        'factura_id' => $factura->id,
                        'producto_id' => $producto->id,
                        'nombre_personalizado' => $nombrePersonalizado,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'descuento' => $descuento,
                        'recargo' => $recargo,
                        'subtotal' => $subtotalProducto,
                    ]);
                }

                // Guardar retenciones si existen
                foreach ($retencionesArray as $retencionData) {
                    if (isset($retencionData['retencion_id']) && isset($retencionData['valor'])) {
                        FacturaHasRetencione::create([
                            'factura_id' => $factura->id,
                            'retencion_id' => $retencionData['retencion_id'],
                            'concepto_retencion_id' => $retencionData['concepto_retencion_id'] ?? null,
                            'valor' => $retencionData['valor'],
                            'percentage' => $retencionData['percentage'] ?? 0,
                        ]);
                    }
                }

                DB::commit();
                Log::info('Factura creada exitosamente con integración DIAN', ['factura_id' => $factura->id]);

                return redirect()->route('facturas.show', $factura)->with('toast', [
                    'type' => 'success',
                    'title' => 'Factura creada exitosamente',
                    'message' => 'La factura fue creada y enviada a la DIAN correctamente. CUFE: ' . ($factura->cufe ?? 'N/A')
                ]);

            } else {
                // Si no se envía a DIAN, crear factura normalmente
                $nextConsecutivo = ($resolucion->consecutivo_actual ?? 0) + 1;
                $numeroFactura = trim($resolucion->prefijo) . str_pad($nextConsecutivo, 4, '0', STR_PAD_LEFT);

                $factura = Factura::create([
                    'numero_factura' => $numeroFactura,
                    'cliente_id' => $request->cliente_id,
                    'user_id' => auth()->id(),
                    'tipo_movimiento_id' => $resolucion->id,
                    'tipo_factura_id' => $request->tipo_factura_id,
                    'empresa_id' => $request->empresa_id,
                    'medio_pago_id' => $request->medio_pago_id,
                    'tipo_pago_id' => $request->tipo_pago_id,
                    'total' => $request->total,
                    'valor_impuestos' => $request->valor_impuestos ?? 0,
                    'issue_date' => now(),
                    'due_date' => $request->due_date,
                    'valor_recibido' => $request->valor_recibido,
                    'cambio' => $request->cambio,
                    'subtotal' => $request->subtotal,
                    'estado' => 'pendiente',
                    'obseraciones' => $request->observaciones,
                ]);

                // Guardar productos
                foreach ($productosArray as $productoData) {
                    $producto = Producto::find($productoData['id']);
                    if (!$producto) continue;

                    $cantidad = $productoData['cantidad'] ?? 1;
                    $descuento = $productoData['descuento'] ?? 0;
                    $recargo = $productoData['recargo'] ?? 0;
                    $nombrePersonalizado = $productoData['nombre_personalizado'] ?? null;
                    $precioUnitario = $producto->precio_venta ?? 0;
                    $subtotalProducto = ($precioUnitario * $cantidad) - $descuento + $recargo;

                    // LOG: Verificar nombre personalizado antes de guardar (SIN DIAN)
                    Log::info('Guardando producto en BD (sin DIAN)', [
                        'producto_id' => $producto->id,
                        'nombre_original' => $producto->nombre,
                        'nombre_personalizado' => $nombrePersonalizado,
                        'cantidad' => $cantidad
                    ]);

                    FacturaHasProduct::create([
                        'factura_id' => $factura->id,
                        'producto_id' => $producto->id,
                        'nombre_personalizado' => $nombrePersonalizado,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'descuento' => $descuento,
                        'recargo' => $recargo,
                        'subtotal' => $subtotalProducto,
                    ]);
                }

                // Guardar retenciones
                foreach ($retencionesArray as $retencionData) {
                    if (isset($retencionData['retencion_id']) && isset($retencionData['valor'])) {
                        FacturaHasRetencione::create([
                            'factura_id' => $factura->id,
                            'retencion_id' => $retencionData['retencion_id'],
                            'concepto_retencion_id' => $retencionData['concepto_retencion_id'] ?? null,
                            'valor' => $retencionData['valor'],
                            'percentage' => $retencionData['percentage'] ?? 0,
                        ]);
                    }
                }

                $resolucion->consecutivo_actual = $nextConsecutivo;
                $resolucion->save();

                DB::commit();
                Log::info('Factura creada exitosamente sin integración DIAN', ['factura_id' => $factura->id]);

                return redirect()->route('facturas.show', $factura)->with('toast', [
                    'type' => 'success',
                    'title' => 'Factura creada exitosamente',
                    'message' => 'La factura fue creada correctamente sin envío a la DIAN.'
                ]);
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear factura', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withInput()->with('toast', [
                'type' => 'error',
                'title' => 'Error al crear la factura',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Factura $factura)
    {
        $factura->load([
            'cliente',
            'user',
            'tipoMovimiento',
            'tipoFactura',
            'empresa',
            'medioPago',
            'tipoPago',
            'facturaHasImpuestos.impuesto',
            'facturaHasProducts.producto',
            'facturaHasRetenciones'
        ]);

        return view('facturas.show', compact('factura'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Factura $factura)
    {
        $clientes = Cliente::all();
        $tiposMovimiento = TipoMovimiento::all();
        $tiposFactura = TipoFactura::all();
        $empresas = Empresa::all();
        $mediosPago = MedioPago::all();
        $tiposPago = TipoPago::all();
        $impuestos = Impuesto::all();
        $productos = Producto::all();

        return view('facturas.edit', compact(
            'factura',
            'clientes',
            'tiposMovimiento',
            'tiposFactura',
            'empresas',
            'mediosPago',
            'tiposPago',
            'impuestos',
            'productos'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Factura $factura)
    {
        $request->validate([
            'numero_factura' => 'required|string|max:255',
            'cliente_id' => 'required|exists:clientes,id',
            'tipo_movimiento_id' => 'required|exists:tipo_movimientos,id',
            'tipo_factura_id' => 'required|exists:tipo_facturas,id',
            'empresa_id' => 'required|exists:empresas,id',
            'medio_pago_id' => 'required|exists:medio_pagos,id',
            'tipo_pago_id' => 'required|exists:tipo_pagos,id',
            'total' => 'required|numeric|min:0',
            'valor_impuestos' => 'nullable|numeric|min:0',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'valor_recibido' => 'nullable|numeric|min:0',
            'cambio' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'obseraciones' => 'nullable|string',
        ]);

        $factura->update([
            'numero_factura' => $request->numero_factura,
            'cliente_id' => $request->cliente_id,
            'tipo_movimiento_id' => $request->tipo_movimiento_id,
            'tipo_factura_id' => $request->tipo_factura_id,
            'empresa_id' => $request->empresa_id,
            'medio_pago_id' => $request->medio_pago_id,
            'tipo_pago_id' => $request->tipo_pago_id,
            'total' => $request->total,
            'valor_impuestos' => $request->valor_impuestos ?? 0,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'valor_recibido' => $request->valor_recibido,
            'cambio' => $request->cambio,
            'subtotal' => $request->subtotal,
            'obseraciones' => $request->obseraciones,
        ]);

        return redirect()->route('facturas.show', $factura)->with('toast', [
            'type' => 'success',
            'title' => 'Factura actualizada',
            'message' => 'La factura ha sido actualizada exitosamente.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Factura $factura)
    {
        $factura->delete();

        return redirect()->route('facturas.index')->with('toast', [
            'type' => 'success',
            'title' => 'Factura eliminada',
            'message' => 'La factura ha sido eliminada exitosamente.'
        ]);
    }

    /**
     * Generate PDF for the factura
     */
    public function pdf(Factura $factura)
    {
        try {
            // Cargar relaciones necesarias
            $factura->load([
                'cliente.tipoDocumento',
                'cliente.municipio',
                'cliente.departamento',
                'empresa',
                'user',
                'tipoFactura',
                'tipoMovimiento',
                'medioPago',
                'tipoPago',
                'facturaHasProducts.producto.impuestos.impuesto',
                'facturaHasProducts.producto.unidadMedida',
                'facturaHasRetenciones.tipoRetencion',
                'vendedor'
            ]);

            // Generar QR Code usando el método del modelo Factura
            $qrCodeImage = $factura->qr_code_image;

            // Pasar variables adicionales a la vista
            $viewData = compact('factura', 'qrCodeImage');

            // Configurar DomPDF
            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('isRemoteEnabled', true);

            $dompdf = new \Dompdf\Dompdf($options);

            // Generar HTML del PDF
            $html = view('facturas.pdf.factura', $viewData)->render();

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $nombreArchivo = "factura_{$factura->numero_factura}.pdf";

            // Mostrar el PDF en el navegador
            return response($dompdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "inline; filename=\"{$nombreArchivo}\"");

        } catch (Exception $e) {
            Log::error('Error al generar PDF de factura: ' . $e->getMessage());
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Ocurrió un error al generar el PDF. Inténtelo de nuevo.'
            ]);
        }
    }

    /**
     * Enviar factura a la DIAN mediante API externa
     */
    private function enviarFacturaDian(Request $request, ResolucionFacturacion $resolucion, array $productosArray, array $retencionesArray)
    {
        // Cargar relaciones necesarias
        $empresa = Empresa::with(['tipoDocumento', 'tipoPersona', 'tipoResponsabilidad', 'departamento', 'municipio'])
            ->findOrFail($request->empresa_id);

        $cliente = Cliente::with(['tipoDocumento', 'tipoPersona', 'tipoResponsabilidad', 'departamento', 'municipio'])
            ->findOrFail($request->cliente_id);

        $tipoFactura = TipoFactura::findOrFail($request->tipo_factura_id);
        $medioPago = MedioPago::findOrFail($request->medio_pago_id);
        $tipoPago = TipoPago::findOrFail($request->tipo_pago_id);

        // Calcular siguiente consecutivo
        $nextConsecutivo = ($resolucion->consecutivo_actual ?? 0) + 1;
        $numeroFactura = trim($resolucion->prefijo) . str_pad($nextConsecutivo, 4, '0', STR_PAD_LEFT);

        // Obtener certificado en base64
        $certificateBase64 = '';
        if ($empresa && !empty($empresa->certificate_path)) {
            try {
                if (Storage::exists($empresa->certificate_path)) {
                    $content = Storage::get($empresa->certificate_path);
                    $certificateBase64 = base64_encode($content);
                } elseif (Storage::disk('public')->exists($empresa->certificate_path)) {
                    $content = Storage::disk('public')->get($empresa->certificate_path);
                    $certificateBase64 = base64_encode($content);
                }
            } catch (Exception $e) {
                Log::warning('Error al leer el certificado', ['empresa_id' => $empresa->id, 'error' => $e->getMessage()]);
            }
        }

        if (empty($certificateBase64)) {
            throw new Exception('La empresa debe tener un certificado digital cargado para enviar facturas a la DIAN');
        }

        // Preparar productos con impuestos
        $productosPayload = [];
        foreach ($productosArray as $productoData) {
            $producto = Producto::with('impuestos')->find($productoData['id']);
            if (!$producto) continue;

            $cantidad = $productoData['cantidad'] ?? 1;
            $descuento = $productoData['descuento'] ?? 0;
            $recargo = $productoData['recargo'] ?? 0;
            $nombrePersonalizado = $productoData['nombre_personalizado'] ?? null;
            $precioUnitario = $producto->precio_venta ?? 0;

            // Usar el nombre personalizado si existe, si no usar el nombre original del producto
            $nombreProducto = !empty($nombrePersonalizado) ? $nombrePersonalizado : $producto->nombre;

            // LOG: Verificar qué nombre se enviará a DIAN
            Log::info('Preparando producto para DIAN', [
                'producto_id' => $producto->id,
                'nombre_original' => $producto->nombre,
                'nombre_personalizado' => $nombrePersonalizado,
                'nombre_a_enviar' => $nombreProducto
            ]);

            // Preparar impuestos del producto
            $impuestosProducto = [];
            foreach ($producto->impuestos as $impuesto) {
                $impuestosProducto[] = [
                    'porcentaje' => $impuesto->percentage ?? 0,
                    'impuesto' => [
                        'code' => $impuesto->code ?? '01',
                        'name' => $impuesto->name ?? 'IVA',
                        'percentage' => $impuesto->percentage ?? 0,
                    ]
                ];
            }

            // Si no tiene impuestos, agregar uno por defecto con 0%
            if (empty($impuestosProducto)) {
                $impuestosProducto[] = [
                    'porcentaje' => 0,
                    'impuesto' => [
                        'code' => '01',
                        'name' => 'IVA',
                        'percentage' => 0,
                    ]
                ];
            }

            $productosPayload[] = [
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'descuento' => $descuento,
                'recargo' => $recargo,
                'producto' => [
                    'id' => (string) $producto->id, // Convertir a string
                    'nombre' => $nombreProducto, // Usar el nombre personalizado si existe
                    'codigo' => $producto->codigo_barras ?? 'PROD' . $producto->id,
                    'unidad_medida' => 'UND',
                    'impuestos' => $impuestosProducto,
                ]
            ];
        }

        // Preparar retenciones
        $retencionesPayload = [];
        foreach ($retencionesArray as $retencionData) {
            if (isset($retencionData['retencion_id']) && isset($retencionData['valor'])) {
                $retencionesPayload[] = [
                    'retencion_id' => $retencionData['retencion_id'],
                    'concepto_retencion_id' => $retencionData['concepto_retencion_id'] ?? null,
                    'valor' => $retencionData['valor'],
                    'percentage' => $retencionData['percentage'] ?? 0,
                ];
            }
        }

        // Determinar tipo de ambiente
        $tipoAmbiente = env('DIAN_ENV') === 'production' ? 1 : 2;
        $testSetId = $tipoAmbiente === 2 ? env('DIAN_TEST_SET_ID', '') : null;

        // Construir el payload según la estructura documentada
        $payload = [
            'factura' => [
                'numero_factura' => $numeroFactura,
                'subtotal' => (float) $request->subtotal,
                'valor_impuestos' => (float) $request->valor_impuestos,
                'total' => (float) $request->total,
                'due_date' => $request->due_date ?? now()->addDays($request->plazo_dias ?? 30)->format('Y-m-d'),
                'observaciones' => $request->observaciones,
            ],
            'empresa' => [
                'name' => $empresa->razon_social ?? '',
                'nit' => $empresa->nit ?? '',
                'dv' => $empresa->dv ?? '',
                'software_id' => $empresa->software_id ?? '',
                'software_pin' => $empresa->software_pin ?? '',
                'certificate_base64' => $certificateBase64,
                'certificate_password' => $empresa->certificate_password ?? '',
                'tipo_ambiente' => $tipoAmbiente,
                'test_set_id' => $testSetId,
                'address' => $empresa->direccion ?? '',
                'phone' => $empresa->telefono_fijo ?? $empresa->celular ?? '',
                'email' => $empresa->email ?? '',
                'matricula_mercantil' => $empresa->matricula_mercantil ?? '',
                'municipio' => [
                    'code' => $empresa->municipio->code ?? '',
                    'name' => $empresa->municipio->name ?? '',
                ],
                'departamento' => [
                    'code' => $empresa->departamento->code ?? '',
                    'name' => $empresa->departamento->name ?? '',
                ],
                'tipoDocumento' => [
                    'code' => $empresa->tipoDocumento->code ?? '31',
                ],
                'tipoResponsabilidad' => [
                    'code' => $empresa->tipoResponsabilidad->code ?? 'O-13',
                ],
            ],
            'cliente' => [
                'name' => $cliente->nombre_completo ?? '',
                'document' => $cliente->cedula_nit ?? '',
                'dv' => $cliente->dv ?? '',
                'address' => $cliente->direccion ?? '',
                'phone' => $cliente->telefono_fijo ?? $cliente->celular ?? '',
                'email' => $cliente->email ?? '',
                'municipio' => [
                    'code' => $cliente->municipio->code ?? '',
                    'name' => $cliente->municipio->name ?? '',
                ],
                'departamento' => [
                    'code' => $cliente->departamento->code ?? '',
                    'name' => $cliente->departamento->name ?? '',
                ],
                'tipoDocumento' => [
                    'code' => $cliente->tipoDocumento->code ?? '13',
                ],
                'tipoPersona' => [
                    'code' => $cliente->tipoPersona->code ?? '1',
                ],
                'tipoResponsabilidad' => [
                    'code' => $cliente->tipoResponsabilidad->code ?? 'R-99-PN',
                ],
            ],
            'tipo_factura' => [
                'code' => $tipoFactura->code ?? '01',
                'name' => $tipoFactura->name ?? 'Factura de Venta',
            ],
            'tipo_movimiento' => [
                'resolucion' => $resolucion->resolucion ?? '',
                'fecha_inicial' => $resolucion->fecha_inicial ? $resolucion->fecha_inicial->format('Y-m-d') : '',
                'fecha_final' => $resolucion->fecha_final ? $resolucion->fecha_final->format('Y-m-d') : '',
                'prefijo' => $resolucion->prefijo ?? '',
                'consecutivo_inicial' => (string) ($resolucion->consecutivo_inicial ?? ''), // Convertir a string
                'consecutivo_final' => (string) ($resolucion->consecutivo_final ?? ''), // Convertir a string
                'clave_tecnica' => $resolucion->clave_tecnica ?? '',
            ],
            'productos' => $productosPayload,
            'medio_pago' => [
                'code' => $medioPago->code ?? '10',
                'name' => $medioPago->name ?? 'Efectivo',
            ],
            'tipoPago' => [
                'code' => $tipoPago->code ?? '1',
                'name' => $tipoPago->name ?? 'Contado',
            ],
            'retenciones' => $retencionesPayload,
        ];

        // Enviar a la API externa
        $apiUrl = rtrim(env('API_URL', ''), '/') . '/facturacion-dian/enviar-factura';
        Log::info('Enviando factura a API externa DIAN', ['url' => $apiUrl, 'numero_factura' => $numeroFactura]);

        try {
            // Obtener clave secreta y construir headers
            $secretKey = env('CLAVE_SECRETA');
            if (!$secretKey) {
                throw new Exception('Clave secreta no configurada (CLAVE_SECRETA)');
            }

            // Generar random pass
            try {
                $randomPass = bin2hex(random_bytes(16));
            } catch (Exception $e) {
                $randomPass = substr(md5(uniqid((string) time(), true)), 0, 32);
            }

            $token = md5($randomPass . $secretKey);

            $headers = [
                'X-Custom-Token' => $token,
                'X-Random-Pass' => $randomPass,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            // Enviar petición
            $start = microtime(true);
            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->post($apiUrl, $payload);

            $durationMs = round((microtime(true) - $start) * 1000, 2);

            Log::info('Respuesta recibida de API externa DIAN', [
                'status' => $response->status(),
                'duration_ms' => $durationMs,
            ]);

            $respBody = $response->json();

            // // Log the raw response for debugging
            // Log::info('Raw API Response', [
            //     'status' => $response->status(),
            //     'body' => $respBody,
            //     'headers' => $response->headers(),
            // ]);

            // Verificar respuesta exitosa
            if (!$response->successful() || !isset($respBody['success']) || $respBody['success'] !== true) {
                $message = 'Error en la respuesta de la API DIAN';
                $errorDetails = [];

                if (isset($respBody['message'])) {
                    $message .= ': ' . $respBody['message'];
                }

                // Extract validation errors if they exist
                if (isset($respBody['errors'])) {
                    $errorDetails = $respBody['errors'];
                    if (is_array($errorDetails)) {
                        $errorMessages = [];
                        foreach ($errorDetails as $field => $errors) {
                            if (is_array($errors)) {
                                $errorMessages[] = $field . ': ' . implode(', ', $errors);
                            } else {
                                $errorMessages[] = $field . ': ' . $errors;
                            }
                        }
                        if (!empty($errorMessages)) {
                            $message .= ' | Detalles: ' . implode(' | ', $errorMessages);
                        }
                    }
                }

                // Log detailed error information
                Log::error('Error detallado de API DIAN', [
                    'message' => $message,
                    'status' => $response->status(),
                    'response_body' => $respBody,
                    'validation_errors' => $errorDetails,
                    'numero_factura' => $numeroFactura,
                ]);

                throw new Exception($message);
            }

            // Extraer datos de la respuesta
            $data = $respBody['data'] ?? [];

            return [
                'is_valid' => $data['is_valid'] ?? false,
                'xml_url' => $data['xml_url'] ?? '',
                'cufe' => $data['cufe'] ?? '',
                'numero_factura' => $numeroFactura,
                'estado' => ($data['is_valid'] ?? false) ? 'enviado a dian' : 'error dian',
                'zip_key' => $data['zip_key'] ?? null,
                'errors' => $data['errors'] ?? [],
                'raw_response' => $respBody,
            ];

        } catch (Exception $e) {
            // Try to get response body if available
            $responseBody = null;
            if (isset($response) && $response) {
                try {
                    $responseBody = $response->json();
                } catch (Exception $jsonEx) {
                    $responseBody = $response->body();
                }
            }

            Log::error('Error al enviar factura a DIAN', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'response_status' => isset($response) ? $response->status() : null,
                'response_body' => $responseBody,
                'numero_factura' => $numeroFactura,
            ]);

            return [
                'is_valid' => false,
                'xml_url' => '',
                'cufe' => '',
                'numero_factura' => $numeroFactura,
                'estado' => 'error dian',
                'zip_key' => null,
                'errors' => ['Error al comunicarse con la API DIAN: ' . $e->getMessage()],
                'raw_response' => $responseBody,
            ];
        }
    }

    /**
     * Enviar factura por correo electrónico
     */
    public function enviarPorCorreo(Factura $factura)
    {
        try {
            // Validar que la factura existe y está enviada a la DIAN
            if (!$factura) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factura no encontrada.'
                ], 404);
            }

            // Validar que la factura fue enviada a la DIAN
            if ($factura->estado !== 'enviado a dian' || !$factura->cufe || !$factura->xml_url) {
                return back()->with('toast', [
                    'type' => 'warning',
                    'title' => 'Advertencia',
                    'message' => 'Solo se pueden enviar por correo facturas que han sido exitosamente enviadas a la DIAN.'
                ]);
            }

            // Cargar relaciones necesarias
            $factura->load(['cliente', 'empresa']);

            // Validar que el cliente tenga email
            if (!$factura->cliente || !$factura->cliente->email) {
                return back()->with('toast', [
                    'type' => 'warning',
                    'title' => 'Advertencia',
                    'message' => 'El cliente no tiene una dirección de correo electrónico asociada.'
                ]);
            }

            // Validar que la empresa tenga configuración básica
            if (!$factura->empresa) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Error',
                    'message' => 'No se pudo encontrar la información de la empresa.'
                ]);
            }

            // Verificar si ya hay un job pendiente para esta factura
            if (\App\Jobs\EnviarCorreoFactura::hasPendingJobs($factura->id)) {
                return back()->with('toast', [
                    'type' => 'info',
                    'title' => 'Información',
                    'message' => 'Ya existe un envío de correo pendiente para esta factura.'
                ]);
            }

            // Encolar el job de envío de correo
            \App\Jobs\EnviarCorreoFactura::dispatch($factura->id)
                ->delay(now()->addSeconds(5)); // Pequeño delay para asegurar que todo está listo

            Log::info('Job de correo manual encolado', [
                'factura_id' => $factura->id,
                'numero_factura' => $factura->numero_factura,
                'empresa_id' => $factura->empresa_id,
                'cliente_email' => $factura->cliente->email,
                'usuario_id' => auth()->id(),
                'tipo_envio' => 'manual'
            ]);

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Éxito',
                'message' => "Factura {$factura->numero_factura} encolada para envío por correo a {$factura->cliente->email}. El proceso puede tardar unos minutos."
            ]);

        } catch (Exception $e) {
            Log::error('Error al encolar envío de correo manual', [
                'factura_id' => $factura->id ?? null,
                'error' => $e->getMessage(),
                'usuario_id' => auth()->id()
            ]);

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Ocurrió un error al enviar la factura por correo. Inténtelo de nuevo.'
            ]);
        }
    }
}
