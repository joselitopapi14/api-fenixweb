<?php

namespace App\Http\Controllers\Web\DocumentoEquivalente;

use App\Http\Controllers\Controller;
use App\Models\DocumentoEquivalente;
use App\Models\Empresa;
use App\Models\Concepto;
use App\Models\Cliente;
use App\Models\TipoPago;
use App\Models\MedioPago;
use App\Models\ResolucionFacturacion;
use App\Models\ErrorDian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Exception;

class DocumentoEquivalenteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $query = DocumentoEquivalente::with(['empresa', 'concepto', 'cliente', 'resolucion', 'tipoPago', 'medioPago']);

            // Filtrar por empresa si no es admin global
            if (!$user->esAdministradorGlobal()) {
                $empresasIds = $user->empresasActivas()->pluck('empresas.id');
                $query->whereIn('empresa_id', $empresasIds);
            }

            // Filtro de búsqueda
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = strtolower($request->search);
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(descripcion) LIKE ?', ["%{$searchTerm}%"])
                      ->orWhereHas('cliente', function ($clienteQuery) use ($searchTerm) {
                          $clienteQuery->whereRaw('LOWER(nombre_completo) LIKE ?', ["%{$searchTerm}%"])
                                      ->orWhereRaw('LOWER(numero_documento) LIKE ?', ["%{$searchTerm}%"]);
                      })
                      ->orWhereHas('concepto', function ($conceptoQuery) use ($searchTerm) {
                          $conceptoQuery->whereRaw('LOWER(nombre) LIKE ?', ["%{$searchTerm}%"]);
                      });
                });
            }

            // Filtro por empresa
            if ($request->filled('empresa_id')) {
                $query->where('empresa_id', $request->empresa_id);
            }

            // Filtro por estado
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            // Filtro por concepto
            if ($request->filled('concepto_id')) {
                $query->where('concepto_id', $request->concepto_id);
            }

            // Filtro por fechas
            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_documento', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_documento', '<=', $request->fecha_hasta);
            }

            // Ordenamiento
            $sortBy = $request->get('sort', 'created_at_desc');
            switch ($sortBy) {
                case 'monto_desc':
                    $query->orderBy('monto', 'desc');
                    break;
                case 'monto_asc':
                    $query->orderBy('monto', 'asc');
                    break;
                case 'created_at_asc':
                    $query->orderBy('created_at', 'asc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $documentos = $query->paginate(10);

            // Obtener estadísticas para las cards
            $estadisticasQuery = DocumentoEquivalente::query();
            if (!$user->esAdministradorGlobal()) {
                $empresasIds = $user->empresasActivas()->pluck('empresas.id');
                $estadisticasQuery->whereIn('empresa_id', $empresasIds);
            }

            $estadisticas = [
                'total_documentos' => (clone $estadisticasQuery)->count(),
                'total_monto' => (clone $estadisticasQuery)->sum('monto'),
                'documentos_mes' => (clone $estadisticasQuery)->whereMonth('fecha_documento', now()->month)
                                                             ->whereYear('fecha_documento', now()->year)
                                                             ->count(),
                'monto_mes' => (clone $estadisticasQuery)->whereMonth('fecha_documento', now()->month)
                                                         ->whereYear('fecha_documento', now()->year)
                                                         ->sum('monto'),
            ];

            // Para admin global, obtener listas para filtros
            $empresas = null;
            $conceptos = collect();

            if ($user->esAdministradorGlobal()) {
                $empresas = Empresa::activas()->orderBy('razon_social')->get();
                $conceptos = Concepto::orderBy('nombre')->get();
            } else {
                $empresasIds = $user->empresasActivas()->pluck('empresas.id');
                $conceptos = Concepto::where(function ($q) use ($empresasIds) {
                    $q->whereIn('empresa_id', $empresasIds)
                      ->orWhereNull('empresa_id');
                })->orderBy('nombre')->get();
            }

            if ($request->ajax()) {
                return view('documento-equivalentes.partials.documentos-list-with-pagination', compact('documentos'))->render();
            }

            return view('documento-equivalentes.index', compact('documentos', 'empresas', 'conceptos', 'estadisticas'));
        } catch (Exception $e) {
            Log::error('Error al listar documentos equivalentes: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['error' => 'Error al cargar documentos equivalentes.'], 500);
            }
            return redirect()->back()->with('error', 'Error al cargar documentos equivalentes.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        $empresas = null;
        $conceptos = collect();

        if ($user->esAdministradorGlobal()) {
            $empresas = Empresa::activas()->orderBy('razon_social')->get();
            $conceptos = Concepto::activos()->orderBy('nombre')->get();
        } else {
            $empresas = $user->empresasActivas;
            $empresasIds = $user->empresasActivas()->pluck('empresas.id');
            $conceptos = Concepto::activos()->where(function ($q) use ($empresasIds) {
                $q->whereIn('empresa_id', $empresasIds)
                  ->orWhereNull('empresa_id');
            })->orderBy('nombre')->get();
        }

        // Obtener tipos de pago y medios de pago
        $tiposPago = TipoPago::orderBy('name')->get();
        $mediosPago = MedioPago::orderBy('name')->get();

        return view('documento-equivalentes.create', compact('empresas', 'conceptos', 'tiposPago', 'mediosPago'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        Log::info('Inicio de la función store', ['user_id' => $user->id]);

        // Validación
        $rules = [
            'fecha_documento' => 'required|date|before_or_equal:today',
            'empresa_id' => 'required|exists:empresas,id',
            'resolucion_id' => 'nullable|exists:resoluciones_facturacion,id',
            'concepto_id' => 'required|exists:conceptos,id',
            'cliente_id' => 'required|exists:clientes,id',
            'tipo_pago_id' => 'required|exists:tipo_pagos,id',
            'medio_pago_id' => 'required|exists:medio_pagos,id',
            'monto' => 'required|numeric|min:0|max:999999999.99',
            'descripcion' => 'required|string|max:255',
        ];

        Log::info('Validando datos de entrada', ['request_data' => $request->all()]);
        $request->validate($rules);

        // Verificar acceso a la empresa
        if (!$user->esAdministradorGlobal()) {
            if (!$user->puedeAccederAEmpresa($request->empresa_id)) {
                abort(403, 'No tienes acceso a esta empresa.');
            }
        }

        // Validar resolución si se envió
        if ($request->filled('resolucion_id')) {
            $res = ResolucionFacturacion::find($request->resolucion_id);
            if (!$res) {
                Log::error('Resolución no encontrada', ['resolucion_id' => $request->resolucion_id]);
                return back()->withInput()->withErrors(['resolucion_id' => 'La resolución seleccionada no existe.']);
            }
            if ($res->empresa_id != $request->empresa_id) {
                Log::error('Resolución no pertenece a la empresa', ['resolucion_id' => $request->resolucion_id, 'empresa_id' => $request->empresa_id]);
                return back()->withInput()->withErrors(['resolucion_id' => 'La resolución seleccionada no pertenece a la empresa indicada.']);
            }
        }

        DB::beginTransaction();
        try {
            Log::info('Creando documento equivalente', ['request_data' => $request->all()]);

            $resolucion = null;
            if ($request->filled('resolucion_id')) {
                $resolucion = ResolucionFacturacion::find($request->resolucion_id);
            }

            // Si la resolución indica que se debe enviar a DIAN, primero intentar la integración.
            if ($resolucion && $resolucion->envia_dian) {
                Log::info('Preparando integración con DIAN', ['resolucion_id' => $resolucion->id]);

                $empresa = Empresa::find($request->empresa_id);
                $cliente = Cliente::find($request->cliente_id);
                $concepto = Concepto::find($request->concepto_id);
                $tipoPago = TipoPago::find($request->tipo_pago_id);
                $medioPago = MedioPago::find($request->medio_pago_id);

                $nextConsecutivo = ($resolucion->consecutivo_actual ?? 0) + 1;
                $numeroFactura = trim($resolucion->prefijo) . str_pad($nextConsecutivo, 4, '0', STR_PAD_LEFT);

                $subtotal = (float) $request->monto;
                $valorImpuestos = round($subtotal * 0, 2);
                $total = round($subtotal + $valorImpuestos, 2);

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

                $tipoAmbiente = env('DIAN_ENV') === 'production' ? 1 : 2;

                $payload = [
                    'documento' => [
                        'numero_factura' => $numeroFactura,
                        'subtotal' => $subtotal,
                        'valor_impuestos' => $valorImpuestos,
                        'total' => $total,
                        'created_at' => now(),
                        'tipoPago' => [
                            'name' => $tipoPago->name ?? 'Contado',
                            'code' => $tipoPago->code ?? 1,
                        ],
                        'medioPago' => [
                            'name' => $medioPago->name ?? 'Efectivo',
                            'code' => $medioPago->code ?? 10,
                        ],
                        'descripcion' => $request->descripcion,
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
                        'tipoDocumento' => $empresa && $empresa->tipoDocumento ? [
                            'name' => $empresa->tipoDocumento->name ?? null,
                            'code' => $empresa->tipoDocumento->code ?? null,
                        ] : null,
                        'tipoPersona' => $empresa && $empresa->tipoPersona ? [
                            'name' => $empresa->tipoPersona->name ?? null,
                            'code' => $empresa->tipoPersona->code ?? null,
                        ] : null,
                        'tipoResponsabilidad' => $empresa && $empresa->tipoResponsabilidad ? [
                            'name' => $empresa->tipoResponsabilidad->name ?? null,
                            'code' => $empresa->tipoResponsabilidad->code ?? null,
                        ] : null,
                        'departamento' => [
                            'code' => $empresa?->departamento?->code ?? null,
                            'name' => $empresa?->departamento?->name ?? ''
                        ],
                        'municipio' => [
                            'code' => $empresa?->municipio?->code ?? null,
                            'name' => $empresa?->municipio?->name ?? ''
                        ],
                        'address' => $empresa?->direccion ?? '',
                        'phone' => $empresa?->telefono ?? '0',
                        'email' => $empresa?->email ?? ''
                    ],
                    'cliente' => [
                        'name' => $cliente->nombre_completo ?? '',
                        'document' => $cliente->cedula_nit ?? '',

                        'tipoDocumento' => $cliente && $cliente->tipoDocumento ? [
                            'name' => $cliente->tipoDocumento->name ?? null,
                            'code' => $cliente->tipoDocumento->code ?? null,
                        ] : null,
                        'tipoPersona' => $cliente && $cliente->tipoPersona ? [
                            'name' => $cliente->tipoPersona->name ?? null,
                            'code' => $cliente->tipoPersona->code ?? null,
                        ] : null,
                        'tipoResponsabilidad' => $cliente && $cliente->tipoResponsabilidad ? [
                            'name' => $cliente->tipoResponsabilidad->name ?? null,
                            'code' => $cliente->tipoResponsabilidad->code ?? null,
                        ] : null,
                        'departamento' => [
                            'code' => $cliente?->departamento?->code ?? null,
                            'name' => $cliente?->departamento?->name ?? ''
                        ],
                        'municipio' => [
                            'code' => $cliente?->municipio?->code ?? null,
                            'name' => $cliente?->municipio?->name ?? ''
                        ],
                        'address' => $cliente?->direccion ?? '',
                        'phone' => $cliente?->telefono ?? '0',
                        'email' => $cliente?->email ?? '',
                        'dv' => $cliente->dv ?? '0',
                    ],
                    'tipo_movimiento' => [
                        'clave_tecnica' => $resolucion->clave_tecnica ?? '',
                        'resolucion' => $resolucion->resolucion ?? '',
                        'fecha_inicial' => $resolucion->fecha_inicial ?? '',
                        'fecha_final' => $resolucion->fecha_final ?? '',
                        'prefijo' => $resolucion->prefijo ?? '',
                        'consecutivo_inicial' => $resolucion->consecutivo_inicial ?? '',
                        'consecutivo_actual' => $resolucion->consecutivo_actual ?? '',
                        'consecutivo_final' => $resolucion->consecutivo_final ?? ''
                    ],
                    'productos' => [
                        [
                            'cantidad' => 1,
                            'precio_unitario' => $subtotal,
                            'producto' => [
                                'nombre' => $concepto->nombre ?? ($request->descripcion ?? 'Producto'),
                                'impuestos' => [
                                    ['impuesto' => ['codigo' => '01', 'name' => 'IVA', 'porcentaje' => 0]]
                                ]
                            ]
                        ]
                    ]
                ];

                // Enviar a la API DIAN y validar respuesta antes de crear el registro.
                $apiUrl = rtrim(env('API_URL', ''), '/') . '/facturacion-dian/enviar-documento-soporte';
                Log::info('Enviando payload a DIAN', ['url' => $apiUrl, 'payload' => $payload]);

                try {
                    // Obtener clave secreta desde .env y construir headers requeridos por el middleware del servicio externo.
                    $secretKey = env('CLAVE_SECRETA');
                    if (!$secretKey) {
                        Log::error('Clave secreta no configurada (CLAVE_SECRETA).');
                        throw new Exception('Clave secreta no configurada');
                    }

                    // Generar random pass seguro
                    try {
                        $randomPass = bin2hex(random_bytes(16));
                    } catch (Exception $e) {
                        // Fallback si random_bytes falla
                        $randomPass = substr(md5(uniqid((string) time(), true)), 0, 32);
                    }

                    $token = md5($randomPass . $secretKey);

                    // Enviar la petición con los headers esperados por el middleware
                    $headers = [
                        'X-Custom-Token' => $token,
                        'X-Random-Pass' => $randomPass,
                    ];

                    // Mask token for logs
                    $maskedToken = strlen($token) > 10 ? substr($token, 0, 6) . '...' . substr($token, -4) : $token;

                    Log::info('Enviando petición HTTP a DIAN', [
                        'url' => $apiUrl,
                        'headers' => [
                            'X-Custom-Token' => $maskedToken,
                            'X-Random-Pass' => '[HIDDEN]'
                        ],
                        'payload_summary' => [
                            'documento' => $payload['documento'] ?? null,
                            'empresa' => [
                                'name' => $payload['empresa']['name'] ?? null,
                                'nit' => $payload['empresa']['nit'] ?? null,
                            ],
                        ]
                    ]);

                    $start = microtime(true);
                    try {
                        $response = Http::withHeaders($headers)
                            ->timeout(15)
                            ->post($apiUrl, $payload);

                        $durationMs = round((microtime(true) - $start) * 1000, 2);

                        // Log status and body (body could be large; keep as string)
                        Log::info('Respuesta recibida de DIAN', [
                            'status' => $response->status(),
                            'duration_ms' => $durationMs,
                            'body' => $response->body()
                        ]);
                    } catch (Exception $e) {
                        $durationMs = round((microtime(true) - $start) * 1000, 2);
                        Log::error('Error al enviar petición a DIAN', [
                            'error' => $e->getMessage(),
                            'duration_ms' => $durationMs,
                            'url' => $apiUrl
                        ]);
                        throw $e;
                    }

                    $respBody = $response->json();

                    Log::info('Respuesta de DIAN', ['response' => $respBody]);

                    // Verificar que la respuesta sea exitosa y válida
                    if (!(is_array($respBody) && array_key_exists('success', $respBody) && $respBody['success'] === true)) {
                        // No se debe crear el documento si la integración falla.
                        $message = 'Integración DIAN fallida.';
                        if (is_array($respBody) && isset($respBody['message'])) {
                            $message = 'Integración DIAN fallida: ' . $respBody['message'];
                        }
                        Log::error('DIAN response error', ['response' => $respBody]);
                        throw new Exception($message);
                    }

                    // Verificar que el documento sea válido según la DIAN
                    if (!(isset($respBody['data']) && is_array($respBody['data']) &&
                          array_key_exists('is_valid', $respBody['data']) &&
                          $respBody['data']['is_valid'] === true)) {

                        $message = 'Error de validación en la DIAN: El documento no es válido según la DIAN.';

                        // Intentar obtener más detalles del error si están disponibles
                        if (isset($respBody['data']['dian_response'])) {
                            Log::error('Detalles de error DIAN', ['dian_response' => $respBody['data']['dian_response']]);
                        }

                        Log::error('DIAN validation error', [
                            'is_valid' => $respBody['data']['is_valid'] ?? 'not_present',
                            'response' => $respBody
                        ]);

                        // Rollback de la transacción antes de extraer y guardar errores
                        DB::rollBack();

                        // Extraer errores detallados de DIAN DESPUÉS del rollback
                        $dianErrors = $this->extractDianErrors($respBody);

                        // Retornar con errores específicos de DIAN para mostrar en la vista
                        return back()->withInput()->withErrors([
                            'dian_errors' => $dianErrors ?: ['El documento no cumple con las validaciones de la DIAN.']
                        ]);
                    }

                    // Si DIAN responde OK, entonces crear documento y actualizar consecutivo.
                    $documento = DocumentoEquivalente::create([
                        'fecha_documento' => $request->fecha_documento,
                        'empresa_id' => $request->empresa_id,
                        'resolucion_id' => $request->resolucion_id ?? null,
                        'concepto_id' => $request->concepto_id,
                        'cliente_id' => $request->cliente_id,
                        'tipo_pago_id' => $request->tipo_pago_id,
                        'medio_pago_id' => $request->medio_pago_id,
                        'monto' => $request->monto,
                        'descripcion' => $request->descripcion,
                        'estado' => 'activo',
                        'xml_url' => $respBody['data']['xml_url'] ?? null,
                        'cuds' => $respBody['data']['cufe'] ?? null,
                        'qr_code' => isset($respBody['data']['cufe']) ? $this->generateQrCodeUrl($respBody['data']['cufe'], $empresa) : null
                    ]);

                    $resolucion->consecutivo_actual = $nextConsecutivo;
                    $resolucion->save();

                    Log::info('Consecutivo actualizado en la resolución', ['resolucion_id' => $resolucion->id, 'consecutivo_actual' => $nextConsecutivo]);
                } catch (Exception $e) {
                    Log::error('Error al comunicarse o validar respuesta de la API DIAN', ['error' => $e->getMessage()]);
                    throw $e; // será atrapado más abajo para hacer rollback y no crear el documento
                }
            } else {
                // No requiere envío a DIAN, crear documento directamente.
                $documento = DocumentoEquivalente::create([
                    'fecha_documento' => $request->fecha_documento,
                    'empresa_id' => $request->empresa_id,
                    'resolucion_id' => $request->resolucion_id ?? null,
                    'concepto_id' => $request->concepto_id,
                    'cliente_id' => $request->cliente_id,
                    'tipo_pago_id' => $request->tipo_pago_id,
                    'medio_pago_id' => $request->medio_pago_id,
                    'monto' => $request->monto,
                    'descripcion' => $request->descripcion,
                    'estado' => 'activo'
                ]);
            }

            DB::commit();
            Log::info('Documento equivalente creado exitosamente', ['documento_id' => $documento->id]);

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Documento equivalente creado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('documento-equivalentes.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear documento equivalente', ['error' => $e->getMessage()]);
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al crear el documento equivalente. ' . $e->getMessage(),
                'status' => 'error'
            ]);
            return back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DocumentoEquivalente $documentoEquivalente)
    {
        $documentoEquivalente->load(['empresa', 'concepto', 'cliente', 'resolucion', 'tipoPago', 'medioPago']);
        return view('documento-equivalentes.show', compact('documentoEquivalente'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DocumentoEquivalente $documentoEquivalente)
    {
        $user = auth()->user();
        $empresas = null;
        $conceptos = collect();

        if ($user->esAdministradorGlobal()) {
            $empresas = Empresa::activas()->orderBy('razon_social')->get();
            $conceptos = Concepto::activos()->orderBy('nombre')->get();
        } else {
            $empresas = $user->empresasActivas;
            $empresasIds = $user->empresasActivas()->pluck('empresas.id');
            $conceptos = Concepto::activos()->where(function ($q) use ($empresasIds) {
                $q->whereIn('empresa_id', $empresasIds)
                  ->orWhereNull('empresa_id');
            })->orderBy('nombre')->get();
        }

        // Obtener tipos de pago y medios de pago
        $tiposPago = TipoPago::orderBy('name')->get();
        $mediosPago = MedioPago::orderBy('name')->get();

        $documentoEquivalente->load(['cliente', 'resolucion', 'tipoPago', 'medioPago']);

        return view('documento-equivalentes.edit', compact('documentoEquivalente', 'empresas', 'conceptos', 'tiposPago', 'mediosPago'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DocumentoEquivalente $documentoEquivalente)
    {
        $user = auth()->user();

        // Validación
        $rules = [
            'fecha_documento' => 'required|date|before_or_equal:today',
            'empresa_id' => 'required|exists:empresas,id',
            'resolucion_id' => 'nullable|exists:resoluciones_facturacion,id',
            'concepto_id' => 'required|exists:conceptos,id',
            'cliente_id' => 'required|exists:clientes,id',
            'tipo_pago_id' => 'required|exists:tipo_pagos,id',
            'medio_pago_id' => 'required|exists:medio_pagos,id',
            'monto' => 'required|numeric|min:0|max:999999999.99',
            'descripcion' => 'required|string|max:255',
        ];

        $request->validate($rules);

        // Verificar acceso a la empresa
        if (!$user->esAdministradorGlobal()) {
            if (!$user->puedeAccederAEmpresa($request->empresa_id)) {
                abort(403, 'No tienes acceso a esta empresa.');
            }
        }

        // Si se envió una resolución, validar que pertenezca a la empresa seleccionada
        if ($request->filled('resolucion_id')) {
            $res = ResolucionFacturacion::find($request->resolucion_id);
            if (!$res) {
                return back()->withInput()->withErrors(['resolucion_id' => 'La resolución seleccionada no existe.']);
            }
            if ($res->empresa_id != $request->empresa_id) {
                return back()->withInput()->withErrors(['resolucion_id' => 'La resolución seleccionada no pertenece a la empresa indicada.']);
            }
        }

        DB::beginTransaction();
        try {
            $documentoEquivalente->update([
                'fecha_documento' => $request->fecha_documento,
                'empresa_id' => $request->empresa_id,
                'resolucion_id' => $request->resolucion_id ?? null,
                'concepto_id' => $request->concepto_id,
                'cliente_id' => $request->cliente_id,
                'tipo_pago_id' => $request->tipo_pago_id,
                'medio_pago_id' => $request->medio_pago_id,
                'monto' => $request->monto,
                'descripcion' => $request->descripcion,
            ]);

            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Documento equivalente actualizado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('documento-equivalentes.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar documento equivalente: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al actualizar el documento equivalente. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DocumentoEquivalente $documentoEquivalente)
    {
        DB::beginTransaction();
        try {
            $documentoEquivalente->delete();
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Documento equivalente eliminado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->route('documento-equivalentes.index');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar documento equivalente: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al eliminar el documento equivalente. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back();
        }
    }

    /**
     * Anular documento equivalente
     */
    public function anular(DocumentoEquivalente $documentoEquivalente)
    {
        DB::beginTransaction();
        try {
            $documentoEquivalente->anular();
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Documento equivalente anulado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->back();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al anular documento equivalente: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al anular el documento equivalente. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back();
        }
    }

    /**
     * Activar documento equivalente
     */
    public function activar(DocumentoEquivalente $documentoEquivalente)
    {
        DB::beginTransaction();
        try {
            $documentoEquivalente->activar();
            DB::commit();

            session()->flash('toast', [
                'title' => '¡Éxito!',
                'message' => 'Documento equivalente activado exitosamente.',
                'status' => 'success'
            ]);

            return redirect()->back();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al activar documento equivalente: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al activar el documento equivalente. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back();
        }
    }

    /**
     * Generar PDF del documento equivalente
     */
    public function pdf(DocumentoEquivalente $documentoEquivalente)
    {
        try {
            $user = auth()->user();

            // Verificar acceso al documento
            if (!$user->esAdministradorGlobal()) {
                if (!$user->puedeAccederAEmpresa($documentoEquivalente->empresa_id)) {
                    abort(403, 'No tienes acceso a este documento.');
                }
            }

            // Cargar relaciones necesarias
            $documentoEquivalente->load([
                'empresa',
                'concepto',
                'cliente.tipoDocumento'
            ]);

            // Configurar DomPDF
            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);

            $dompdf = new \Dompdf\Dompdf($options);

            // Generar HTML del PDF
            $html = view('documento-equivalentes.pdf.documento', compact('documentoEquivalente'))->render();

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $nombreArchivo = "documento_equivalente_{$documentoEquivalente->id}.pdf";

            // Mostrar el PDF en el navegador
            return response($dompdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "inline; filename=\"{$nombreArchivo}\"");

        } catch (Exception $e) {
            Log::error('Error al generar PDF de documento equivalente: ' . $e->getMessage());
            session()->flash('toast', [
                'title' => 'Error',
                'message' => 'Ocurrió un error al generar el PDF. Inténtelo de nuevo.',
                'status' => 'error'
            ]);
            return back();
        }
    }    /**
     * API para buscar clientes
     */
    public function buscarClientes(Request $request)
    {
        $user = auth()->user();
        $term = $request->get('term', '');
        $empresaId = $request->get('empresa_id');

        $query = Cliente::query();

        // Filtrar por empresa si no es admin global
        if (!$user->esAdministradorGlobal()) {
            $empresasIds = $user->empresasActivas()->pluck('empresas.id');
            $query->whereIn('empresa_id', $empresasIds);
        } elseif ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        if ($term) {
            $termLower = strtolower($term);
            $query->where(function ($q) use ($termLower) {
                $q->whereRaw('LOWER(nombres) LIKE ?', ["%{$termLower}%"])
                  ->orWhereRaw('LOWER(apellidos) LIKE ?', ["%{$termLower}%"])
                  ->orWhereRaw('LOWER(razon_social) LIKE ?', ["%{$termLower}%"])
                  ->orWhereRaw('LOWER(cedula_nit) LIKE ?', ["%{$termLower}%"])
                  ->orWhereRaw('CONCAT(LOWER(nombres), \' \', LOWER(apellidos)) LIKE ?', ["%{$termLower}%"]);
            });
        }

        $clientes = $query->select('id', 'nombres', 'apellidos', 'razon_social', 'cedula_nit', 'dv', 'empresa_id')
                         ->orderByRaw('CASE WHEN razon_social IS NOT NULL AND razon_social != \'\' THEN razon_social ELSE CONCAT(nombres, \' \', apellidos) END')
                         ->limit(10)
                         ->get();

        // Transform the results to include computed attributes
        $clientesTransformed = $clientes->map(function ($cliente) {
            return [
                'id' => $cliente->id,
                'nombre_completo' => $cliente->nombre_completo,
                'numero_documento' => $cliente->numero_documento,
                'empresa_id' => $cliente->empresa_id
            ];
        });

        return response()->json($clientesTransformed);
    }

    /**
     * API para obtener resoluciones por empresa (activas)
     */
    public function resolucionesPorEmpresa(Request $request)
    {
        $user = auth()->user();
        $empresaId = $request->get('empresa_id');

        $query = \App\Models\ResolucionFacturacion::query();

        if (!$user->esAdministradorGlobal()) {
            $empresasIds = $user->empresasActivas()->pluck('empresas.id');
            $query->whereIn('empresa_id', $empresasIds);
        } elseif ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $resoluciones = $query->where('activo', true)
                              ->orderBy('prefijo')
                              ->select('id', 'prefijo', 'resolucion')
                              ->get();

        $result = $resoluciones->map(function ($r) {
            return [
                'id' => $r->id,
                'label' => trim(($r->prefijo ? $r->prefijo . ' - ' : '') . ($r->resolucion ?? '')),
            ];
        });

        return response()->json($result);
    }

    /**
     * Generar URL del QR Code para la DIAN
     */
    private function generateQrCodeUrl($cufe, $empresa)
    {
        if (!$cufe || !$empresa) {
            return null;
        }

        $baseUrl = $empresa->tipo_ambiente == 2
            ? 'https://catalogo-vpfe-hab.dian.gov.co/document/searchqr?documentkey='
            : 'https://catalogo-vpfe.dian.gov.co/document/searchqr?documentkey=';

        return trim($baseUrl . $cufe);
    }

    /**
     * Extraer errores específicos de la respuesta de DIAN
     */
    private function extractDianErrors($respBody)
    {
        $errors = [];

        try {
            // Verificar si tenemos la estructura de respuesta de DIAN
            if (isset($respBody['data']['dian_response']['Envelope']['Body']['SendBillSyncResponse']['SendBillSyncResult']['ErrorMessage']['string'])) {
                $errorStrings = $respBody['data']['dian_response']['Envelope']['Body']['SendBillSyncResponse']['SendBillSyncResult']['ErrorMessage']['string'];

                // Puede ser un array o una sola cadena
                if (is_array($errorStrings)) {
                    foreach ($errorStrings as $errorString) {
                        $this->guardarErrorDian($errorString);
                        $errors[] = $this->formatDianError($errorString);
                    }
                } else {
                    $this->guardarErrorDian($errorStrings);
                    $errors[] = $this->formatDianError($errorStrings);
                }
            }

            // Si no encontramos errores en la estructura estándar, intentar otras ubicaciones
            if (empty($errors)) {
                // Intentar extraer de la descripción del estado si existe
                if (isset($respBody['data']['dian_response']['Envelope']['Body']['SendBillSyncResponse']['SendBillSyncResult']['StatusDescription'])) {
                    $statusDescription = $respBody['data']['dian_response']['Envelope']['Body']['SendBillSyncResponse']['SendBillSyncResult']['StatusDescription'];
                    $this->guardarErrorDian($statusDescription);
                    $errors[] = $statusDescription;
                }

                // Si aún no hay errores, usar mensaje genérico
                if (empty($errors)) {
                    $mensajeGenerico = 'El documento no cumple con las validaciones de la DIAN. Revise los datos ingresados.';
                    $this->guardarErrorDian($mensajeGenerico);
                    $errors[] = $mensajeGenerico;
                }
            }

        } catch (Exception $e) {
            Log::warning('Error al extraer mensajes de error de DIAN', ['error' => $e->getMessage()]);
            $mensajeError = 'Error de validación en la DIAN. Revise los datos ingresados.';
            $this->guardarErrorDian($mensajeError);
            $errors[] = $mensajeError;
        }

        return $errors;
    }

    /**
     * Guardar error DIAN en la base de datos si no existe
     * Se ejecuta después del rollback para garantizar que se guarde
     */
    private function guardarErrorDian(string $mensajeOriginal): void
    {
        try {
            // Extraer código y descripción del mensaje
            $codigo = ErrorDian::extraerCodigo($mensajeOriginal);
            $descripcion = ErrorDian::extraerDescripcion($mensajeOriginal);

            // Crear o encontrar el error (se evita duplicado automáticamente)
            $error = ErrorDian::crearOEncontrar($mensajeOriginal, $codigo, $descripcion);

            Log::info('Error DIAN procesado y guardado', [
                'id' => $error->id,
                'codigo' => $error->codigo,
                'es_nuevo' => $error->wasRecentlyCreated,
                'mensaje_original' => $mensajeOriginal
            ]);

        } catch (Exception $e) {
            Log::error('Error al guardar error DIAN en BD', [
                'error' => $e->getMessage(),
                'mensaje_original' => $mensajeOriginal,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Formatear mensaje de error de DIAN para mejor presentación
     */
    private function formatDianError($errorString)
    {
        // Buscar patrones comunes en los errores de DIAN
        if (strpos($errorString, 'Regla:') !== false && strpos($errorString, 'Rechazo:') !== false) {
            // Formato: "Regla: DSAK24b, Rechazo: El DV del NIT del adquiriente no es correcto"
            $parts = explode(', Rechazo: ', $errorString);
            if (count($parts) == 2) {
                $regla = str_replace('Regla: ', '', $parts[0]);
                $descripcion = $parts[1];
                return "Error {$regla}: {$descripcion}";
            }
        }

        // Si no sigue el patrón esperado, devolver tal como está
        return $errorString;
    }
}
