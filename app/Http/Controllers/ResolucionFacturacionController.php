<?php

namespace App\Http\Controllers;

use App\Models\ResolucionFacturacion;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ResolucionFacturacionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Construir query base
        $query = ResolucionFacturacion::with('empresa');

        // Filtrar por empresa si el usuario no es administrador global
        if (!$user->esAdministradorGlobal()) {
            $empresasUsuario = $user->empresasActivas->pluck('id');
            $query->whereIn('empresa_id', $empresasUsuario);
        }

        // Aplicar búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $query->buscar($request->search);
        }

        // Filtro por empresa
        if ($request->has('empresa_id') && !empty($request->empresa_id)) {
            $query->where('empresa_id', $request->empresa_id);
        }

        // Filtro por estado activo/inactivo
        if ($request->has('estado') && $request->estado !== '') {
            if ($request->estado === 'activo') {
                $query->where('activo', true);
            } else {
                $query->where('activo', false);
            }
        }

        // Filtro por envía DIAN
        if ($request->has('envia_dian') && $request->envia_dian !== '') {
            $query->where('envia_dian', $request->envia_dian === 'si');
        }

        // Aplicar ordenamiento
        $sortBy = $request->get('sort', 'prefijo_asc');
        switch ($sortBy) {
            case 'prefijo_desc':
                $query->orderBy('prefijo', 'desc');
                break;
            case 'empresa_asc':
                $query->join('empresas', 'resoluciones_facturacion.empresa_id', '=', 'empresas.id')
                      ->orderBy('empresas.razon_social', 'asc')
                      ->select('resoluciones_facturacion.*');
                break;
            case 'empresa_desc':
                $query->join('empresas', 'resoluciones_facturacion.empresa_id', '=', 'empresas.id')
                      ->orderBy('empresas.razon_social', 'desc')
                      ->select('resoluciones_facturacion.*');
                break;
            case 'resolucion_asc':
                $query->orderBy('resolucion', 'asc');
                break;
            case 'resolucion_desc':
                $query->orderBy('resolucion', 'desc');
                break;
            case 'created_at_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'created_at_asc':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderBy('prefijo', 'asc');
        }

        $resoluciones = $query->paginate(10)->withQueryString();

        // Obtener empresas para el filtro
        $empresas = collect();
        if ($user->esAdministradorGlobal()) {
            $empresas = Empresa::activas()->orderBy('razon_social')->get();
        } elseif ($user->esAdministradorEmpresa() || $user->esEmpleadoEmpresa()) {
            $empresas = $user->empresasActivas()->orderBy('razon_social')->get();
        }

        return view('resoluciones-facturacion.index', compact('resoluciones', 'empresas'));
    }

    public function create()
    {
        $user = Auth::user();

        // Obtener empresas disponibles
        $empresas = collect();
        if ($user->esAdministradorGlobal()) {
            $empresas = Empresa::activas()->orderBy('razon_social')->get();
        } elseif ($user->esAdministradorEmpresa() || $user->esEmpleadoEmpresa()) {
            $empresas = $user->empresasActivas()->orderBy('razon_social')->get();
        }

        return view('resoluciones-facturacion.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $rules = ResolucionFacturacion::rules();
        $messages = ResolucionFacturacion::messages();

        $request->validate($rules, $messages);

        // Verificar que el usuario tenga acceso a la empresa
        $user = Auth::user();
        if (!$user->esAdministradorGlobal()) {
            $empresasUsuario = $user->empresasActivas->pluck('id')->toArray();
            if (!in_array($request->empresa_id, $empresasUsuario)) {
                abort(403, 'No tienes permisos para crear resoluciones en esta empresa.');
            }
        }

        ResolucionFacturacion::create($request->all());

        session()->flash('toast', [
            'title' => '¡Éxito!',
            'message' => 'Resolución de facturación creada exitosamente.',
            'status' => 'success'
        ]);

        return redirect()->route('resoluciones-facturacion.index');
    }

    public function show(ResolucionFacturacion $resolucionesFacturacion)
    {
        $resolucionesFacturacion->load('empresa');

        // Verificar permisos
        $this->verificarAccesoEmpresa($resolucionesFacturacion->empresa_id);

        return view('resoluciones-facturacion.show', compact('resolucionesFacturacion'));
    }

    public function edit(ResolucionFacturacion $resolucionesFacturacion)
    {
        $resolucionesFacturacion->load('empresa');

        // Verificar permisos
        $this->verificarAccesoEmpresa($resolucionesFacturacion->empresa_id);

        $user = Auth::user();

        // Obtener empresas disponibles
        $empresas = collect();
        if ($user->esAdministradorGlobal()) {
            $empresas = Empresa::activas()->orderBy('razon_social')->get();
        } elseif ($user->esAdministradorEmpresa() || $user->esEmpleadoEmpresa()) {
            $empresas = $user->empresasActivas()->orderBy('razon_social')->get();
        }

        return view('resoluciones-facturacion.edit', compact('resolucionesFacturacion', 'empresas'));
    }

    public function update(Request $request, ResolucionFacturacion $resolucionesFacturacion)
    {
        // Verificar permisos
        $this->verificarAccesoEmpresa($resolucionesFacturacion->empresa_id);

        $rules = ResolucionFacturacion::rules($resolucionesFacturacion->id);
        $messages = ResolucionFacturacion::messages();

        $request->validate($rules, $messages);

        // Verificar que el usuario tenga acceso a la nueva empresa (si cambió)
        $user = Auth::user();
        if (!$user->esAdministradorGlobal()) {
            $empresasUsuario = $user->empresasActivas->pluck('id')->toArray();
            if (!in_array($request->empresa_id, $empresasUsuario)) {
                abort(403, 'No tienes permisos para asignar esta empresa.');
            }
        }

        $resolucionesFacturacion->update($request->all());

        session()->flash('toast', [
            'title' => '¡Éxito!',
            'message' => 'Resolución de facturación actualizada exitosamente.',
            'status' => 'success'
        ]);

        return redirect()->route('resoluciones-facturacion.index');
    }

    public function destroy(ResolucionFacturacion $resolucionesFacturacion)
    {
        // Verificar permisos
        $this->verificarAccesoEmpresa($resolucionesFacturacion->empresa_id);

        $resolucionesFacturacion->delete();

        session()->flash('toast', [
            'title' => '¡Éxito!',
            'message' => 'Resolución de facturación eliminada exitosamente.',
            'status' => 'success'
        ]);

        return redirect()->route('resoluciones-facturacion.index');
    }

    /**
     * Verificar que el usuario tenga acceso a la empresa
     */
    private function verificarAccesoEmpresa($empresaId)
    {
        $user = Auth::user();

        if (!$user->esAdministradorGlobal()) {
            $empresasUsuario = $user->empresasActivas->pluck('id')->toArray();
            if (!in_array($empresaId, $empresasUsuario)) {
                abort(403, 'No tienes permisos para acceder a esta resolución de facturación.');
            }
        }
    }

    /**
     * Sincronizar resoluciones con DIAN
     */
    public function sincronizar(Request $request)
    {
        $user = auth()->user();

        // Validar entrada
        $request->validate([
            'empresa_id' => 'required|exists:empresas,id'
        ]);

        // Verificar acceso a la empresa
        if (!$user->esAdministradorGlobal()) {
            if (!$user->puedeAccederAEmpresa($request->empresa_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a esta empresa.'
                ], 403);
            }
        }

        try {
            $empresa = Empresa::find($request->empresa_id);

            // Validar que la empresa tenga los datos necesarios
            $errores = [];

            if (empty($empresa->nit)) {
                $errores[] = 'NIT de la empresa no configurado';
            }

            if (empty($empresa->software_id)) {
                $errores[] = 'Software ID no configurado';
            }

            if (empty($empresa->certificate_path)) {
                $errores[] = 'Certificado no configurado';
            }

            if (empty($empresa->certificate_password)) {
                $errores[] = 'Contraseña del certificado no configurada';
            }

            if (!empty($errores)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faltan datos requeridos para la sincronización',
                    'errores' => $errores
                ], 400);
            }

            // Obtener certificado en base64
            $certificateBase64 = '';
            try {
                if (Storage::exists($empresa->certificate_path)) {
                    $content = Storage::get($empresa->certificate_path);
                    $certificateBase64 = base64_encode($content);
                } elseif (Storage::disk('public')->exists($empresa->certificate_path)) {
                    $content = Storage::disk('public')->get($empresa->certificate_path);
                    $certificateBase64 = base64_encode($content);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo encontrar el archivo de certificado'
                    ], 400);
                }
            } catch (\Exception $e) {
                Log::error('Error al leer el certificado para sincronización', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error al leer el certificado: ' . $e->getMessage()
                ], 400);
            }

            // Preparar payload para envío
            $payload = [
                'empresa' => [
                    'nit' => $empresa->nit,
                    'software_id' => $empresa->software_id,
                    'certificate_base64' => $certificateBase64,
                    'certificate_password' => $empresa->certificate_password
                ]
            ];

            // Obtener configuración de API
            $apiUrl = rtrim(env('API_URL', ''), '/') . '/facturacion-dian/obtener-rangos-numeracion';
            $secretKey = env('CLAVE_SECRETA');

            if (!$secretKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Clave secreta no configurada en el sistema'
                ], 500);
            }

            // Generar headers para autenticación
            try {
                $randomPass = bin2hex(random_bytes(16));
            } catch (\Exception $e) {
                $randomPass = uniqid() . time();
            }

            $token = md5($randomPass . $secretKey);

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Custom-Token' => $token,
                'X-Random-Pass' => $randomPass,
            ];

            Log::info('Enviando solicitud de sincronización DIAN', [
                'empresa_id' => $empresa->id,
                'url' => $apiUrl
            ]);

            // Enviar petición HTTP
            $start = microtime(true);
            $response = Http::timeout(60)->withHeaders($headers)->post($apiUrl, $payload);
            $duration = round((microtime(true) - $start) * 1000, 2);

            Log::info('Respuesta de sincronización DIAN recibida', [
                'empresa_id' => $empresa->id,
                'status_code' => $response->status(),
                'duration_ms' => $duration
            ]);

            $responseData = $response->json();

            if ($response->successful()) {
                // Iniciar transacción para asegurar integridad
                DB::beginTransaction();

                try {
                    // Procesar y guardar las resoluciones si la API devuelve datos
                    $savedResolutions = [];
                    $errors = [];

                    if (isset($responseData['data']['rangos_numeracion']) && is_array($responseData['data']['rangos_numeracion'])) {
                        foreach ($responseData['data']['rangos_numeracion'] as $resolucionData) {
                            try {
                                // Extraer clave técnica usando el método mejorado
                                $technicalKey = $resolucionData['technical_key'] ?? '';
                                $claveTecnica = $this->extractTechnicalKey($technicalKey);

                                // Log para debugging de clave técnica
                                Log::info('Procesando clave técnica', [
                                    'empresa_id' => $empresa->id,
                                    'resolution_number' => $resolucionData['resolution_number'] ?? 'N/A',
                                    'technical_key_raw' => $technicalKey,
                                    'technical_key_type' => gettype($technicalKey),
                                    'clave_tecnica_final' => $claveTecnica
                                ]);

                                // Verificar si ya existe una resolución con el mismo número
                                $existingResolution = ResolucionFacturacion::where('empresa_id', $empresa->id)
                                    ->where('resolucion', $resolucionData['resolution_number'])
                                    ->first();

                                if ($existingResolution) {
                                    Log::info('Resolución ya existe, actualizando', [
                                        'empresa_id' => $empresa->id,
                                        'resolucion' => $resolucionData['resolution_number']
                                    ]);

                                    // Actualizar resolución existente
                                    $existingResolution->update([
                                        'prefijo' => $resolucionData['prefix'] ?? '',
                                        'fecha_resolucion' => $resolucionData['resolution_date'] ?? null,
                                        'fecha_inicial' => $resolucionData['valid_date_from'] ?? null,
                                        'fecha_final' => $resolucionData['valid_date_to'] ?? null,
                                        'clave_tecnica' => $claveTecnica,
                                        'consecutivo_inicial' => (int)($resolucionData['from_number'] ?? 1),
                                        'consecutivo_final' => (int)($resolucionData['to_number'] ?? 1),
                                        'consecutivo_actual' => 1, // Siempre 1 por defecto como especificado
                                        'envia_dian' => true,
                                        'activo' => true
                                    ]);

                                    $savedResolutions[] = [
                                        'action' => 'updated',
                                        'resolution' => $existingResolution
                                    ];
                                } else {
                                    // Crear nueva resolución
                                    $newResolution = ResolucionFacturacion::create([
                                        'empresa_id' => $empresa->id,
                                        'prefijo' => $resolucionData['prefix'] ?? '',
                                        'resolucion' => $resolucionData['resolution_number'],
                                        'fecha_resolucion' => $resolucionData['resolution_date'] ?? null,
                                        'fecha_inicial' => $resolucionData['valid_date_from'] ?? null,
                                        'fecha_final' => $resolucionData['valid_date_to'] ?? null,
                                        'clave_tecnica' => $claveTecnica,
                                        'consecutivo_inicial' => (int)($resolucionData['from_number'] ?? 1),
                                        'consecutivo_final' => (int)($resolucionData['to_number'] ?? 1),
                                        'consecutivo_actual' => 1, // Siempre 1 por defecto como especificado
                                        'envia_dian' => true,
                                        'activo' => true
                                    ]);

                                    Log::info('Nueva resolución creada', [
                                        'empresa_id' => $empresa->id,
                                        'resolucion_id' => $newResolution->id,
                                        'resolucion' => $resolucionData['resolution_number']
                                    ]);

                                    $savedResolutions[] = [
                                        'action' => 'created',
                                        'resolution' => $newResolution
                                    ];
                                }
                            } catch (\Exception $e) {
                                Log::error('Error al procesar resolución individual', [
                                    'empresa_id' => $empresa->id,
                                    'resolucion_data' => $resolucionData,
                                    'error' => $e->getMessage()
                                ]);

                                $errors[] = [
                                    'resolution_number' => $resolucionData['resolution_number'] ?? 'N/A',
                                    'error' => $e->getMessage()
                                ];

                                // No lanzar excepción para continuar con otras resoluciones
                            }
                        }
                    }

                    // Confirmar transacción
                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Sincronización completada exitosamente',
                        'saved_resolutions' => count($savedResolutions),
                        'resolutions_data' => $savedResolutions,
                        'errors' => $errors,
                        'api_response' => $responseData,
                        'meta' => [
                            'empresa' => $empresa->razon_social,
                            'duration_ms' => $duration
                        ]
                    ]);

                } catch (\Exception $e) {
                    // Rollback en caso de error
                    DB::rollBack();
                    throw $e;
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error en la respuesta de la API DIAN',
                    'response' => $responseData,
                    'status_code' => $response->status()
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en sincronización con DIAN', [
                'empresa_id' => $request->empresa_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extraer clave técnica del formato que puede venir de la API
     */
    private function extractTechnicalKey($technicalKey)
    {
        // Si es null o vacío, devolver null
        if (empty($technicalKey)) {
            return null;
        }

        // Si es un string que contiene el JSON con nil, devolver null
        if (is_string($technicalKey)) {
            // Verificar si es un JSON string con el formato nil
            if (strpos($technicalKey, '{"_attributes":{"nil":"true"}}') !== false) {
                return null;
            }

            // Verificar otros formatos de nil en JSON
            if (strpos($technicalKey, '"nil":"true"') !== false ||
                strpos($technicalKey, '"nil":true') !== false) {
                return null;
            }

            // Intentar decodificar como JSON para verificar estructura
            $decoded = json_decode($technicalKey, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                if (isset($decoded['_attributes']['nil']) &&
                    ($decoded['_attributes']['nil'] === 'true' || $decoded['_attributes']['nil'] === true)) {
                    return null;
                }
            }

            // Si es un string simple y no es nil, devolverlo (limpiando espacios)
            $cleaned = trim($technicalKey);
            return !empty($cleaned) ? $cleaned : null;
        }

        // Si es un array con formato especial (nil), devolver null
        if (is_array($technicalKey)) {
            if (isset($technicalKey['_attributes']['nil']) &&
                ($technicalKey['_attributes']['nil'] === 'true' || $technicalKey['_attributes']['nil'] === true)) {
                return null;
            }

            // Si es un array pero no tiene nil, intentar extraer valor útil
            if (isset($technicalKey['value'])) {
                return $this->extractTechnicalKey($technicalKey['value']);
            }

            // Si el array tiene contenido directo, convertir a string
            if (count($technicalKey) === 1 && !isset($technicalKey['_attributes'])) {
                $value = reset($technicalKey);
                return $this->extractTechnicalKey($value);
            }
        }

        // Intentar convertir a string si es otro tipo escalar
        if (is_scalar($technicalKey)) {
            $stringValue = (string)$technicalKey;
            return !empty(trim($stringValue)) ? trim($stringValue) : null;
        }

        // Si no se puede procesar, devolver null
        return null;
    }
}
