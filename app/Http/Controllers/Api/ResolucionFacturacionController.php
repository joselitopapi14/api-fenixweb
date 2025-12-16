<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResolucionFacturacion;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ResolucionFacturacionController extends Controller
{
    /**
     * Listar resoluciones de facturación
     */
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
            $val = strtolower($request->envia_dian);
            if ($val === 'si' || $val === 'true' || $val === '1') {
                $query->where('envia_dian', true);
            } else {
                $query->where('envia_dian', false);
            }
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

        $perPage = $request->get('per_page', 10);
        $resoluciones = $query->paginate($perPage);

        return response()->json($resoluciones);
    }

    /**
     * Crear una nueva resolución
     */
    public function store(Request $request)
    {
        $rules = ResolucionFacturacion::rules();
        $messages = ResolucionFacturacion::messages();

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        // Verificar que el usuario tenga acceso a la empresa
        $user = Auth::user();
        if (!$user->esAdministradorGlobal()) {
            $empresasUsuario = $user->empresasActivas->pluck('id')->toArray();
            if (!in_array($request->empresa_id, $empresasUsuario)) {
                return response()->json(['message' => 'No tienes permisos para crear resoluciones en esta empresa.'], 403);
            }
        }

        $resolucion = ResolucionFacturacion::create($request->all());

        return response()->json([
            'message' => 'Resolución de facturación creada exitosamente.',
            'data' => $resolucion
        ], 201);
    }

    /**
     * Mostrar detalles de una resolución
     */
    public function show($id)
    {
        $resolucion = ResolucionFacturacion::with('empresa')->find($id);

        if (!$resolucion) {
            return response()->json(['message' => 'Resolución no encontrada'], 404);
        }

        // Verificar permisos
        if (!$this->tieneAccesoEmpresa($resolucion->empresa_id)) {
            return response()->json(['message' => 'No tienes permisos para acceder a esta resolución.'], 403);
        }

        return response()->json($resolucion);
    }

    /**
     * Actualizar una resolución
     */
    public function update(Request $request, $id)
    {
        $resolucion = ResolucionFacturacion::find($id);

        if (!$resolucion) {
            return response()->json(['message' => 'Resolución no encontrada'], 404);
        }

        // Verificar permisos sobre la resolución original
        if (!$this->tieneAccesoEmpresa($resolucion->empresa_id)) {
            return response()->json(['message' => 'No tienes permisos para editar esta resolución.'], 403);
        }

        $rules = ResolucionFacturacion::rules($id);
        $messages = ResolucionFacturacion::messages();

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        // Verificar que el usuario tenga acceso a la nueva empresa (si la cambia)
        if ($request->has('empresa_id') && $request->empresa_id != $resolucion->empresa_id) {
            if (!$this->tieneAccesoEmpresa($request->empresa_id)) {
                return response()->json(['message' => 'No tienes permisos para asignar esta empresa.'], 403);
            }
        }

        $resolucion->update($request->all());

        return response()->json([
            'message' => 'Resolución actualizada exitosamente.',
            'data' => $resolucion
        ]);
    }

    /**
     * Eliminar una resolución
     */
    public function destroy($id)
    {
        $resolucion = ResolucionFacturacion::find($id);

        if (!$resolucion) {
            return response()->json(['message' => 'Resolución no encontrada'], 404);
        }

        // Verificar permisos
        if (!$this->tieneAccesoEmpresa($resolucion->empresa_id)) {
            return response()->json(['message' => 'No tienes permisos para eliminar esta resolución.'], 403);
        }

        $resolucion->delete();

        return response()->json(['message' => 'Resolución eliminada exitosamente.']);
    }

    /**
     * Sincronizar resoluciones con DIAN
     */
    public function sincronizar(Request $request)
    {
        $user = Auth::user();

        // Validar entrada
        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        // Verificar acceso a la empresa
        if (!$this->tieneAccesoEmpresa($request->empresa_id)) {
            return response()->json(['message' => 'No tienes acceso a esta empresa.'], 403);
        }

        try {
            $empresa = Empresa::find($request->empresa_id);

            // Validar que la empresa tenga los datos necesarios
            $errores = [];

            if (empty($empresa->nit)) $errores[] = 'NIT de la empresa no configurado';
            if (empty($empresa->software_id)) $errores[] = 'Software ID no configurado';
            if (empty($empresa->certificate_path)) $errores[] = 'Certificado no configurado';
            if (empty($empresa->certificate_password)) $errores[] = 'Contraseña del certificado no configurada';

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
                Log::error('Error al leer el certificado', ['empresa_id' => $empresa->id, 'error' => $e->getMessage()]);
                return response()->json(['success' => false, 'message' => 'Error al leer certificado'], 400);
            }

            // Preparar payload
            $payload = [
                'empresa' => [
                    'nit' => $empresa->nit,
                    'software_id' => $empresa->software_id,
                    'certificate_base64' => $certificateBase64,
                    'certificate_password' => $empresa->certificate_password
                ]
            ];

            // Configuración API
            $apiUrl = rtrim(env('API_URL', ''), '/') . '/facturacion-dian/obtener-rangos-numeracion';
            $secretKey = env('CLAVE_SECRETA');

            if (!$secretKey) {
                return response()->json(['message' => 'Clave secreta no configurada en el sistema'], 500);
            }

            // Generar headers
            $randomPass = uniqid() . time();
            $token = md5($randomPass . $secretKey);
            
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Custom-Token' => $token,
                'X-Random-Pass' => $randomPass,
            ];

            // Enviar petición
            $response = Http::timeout(60)->withHeaders($headers)->post($apiUrl, $payload);
            $responseData = $response->json();

            if ($response->successful()) {
                DB::beginTransaction();
                try {
                    $savedResolutions = [];
                    $errors = [];

                    if (isset($responseData['data']['rangos_numeracion']) && is_array($responseData['data']['rangos_numeracion'])) {
                        foreach ($responseData['data']['rangos_numeracion'] as $resolucionData) {
                            try {
                                $claveTecnica = $this->extractTechnicalKey($resolucionData['technical_key'] ?? '');

                                $existingResolution = ResolucionFacturacion::where('empresa_id', $empresa->id)
                                    ->where('resolucion', $resolucionData['resolution_number'])
                                    ->first();

                                $dataToSave = [
                                    'prefijo' => $resolucionData['prefix'] ?? '',
                                    'fecha_resolucion' => $resolucionData['resolution_date'] ?? null,
                                    'fecha_inicial' => $resolucionData['valid_date_from'] ?? null,
                                    'fecha_final' => $resolucionData['valid_date_to'] ?? null,
                                    'clave_tecnica' => $claveTecnica,
                                    'consecutivo_inicial' => (int)($resolucionData['from_number'] ?? 1),
                                    'consecutivo_final' => (int)($resolucionData['to_number'] ?? 1),
                                    'consecutivo_actual' => 1,
                                    'envia_dian' => true,
                                    'activo' => true
                                ];

                                if ($existingResolution) {
                                    $existingResolution->update($dataToSave);
                                    $savedResolution = $existingResolution;
                                    $action = 'updated';
                                } else {
                                    $dataToSave['empresa_id'] = $empresa->id;
                                    $dataToSave['resolucion'] = $resolucionData['resolution_number'];
                                    $savedResolution = ResolucionFacturacion::create($dataToSave);
                                    $action = 'created';
                                }

                                $savedResolutions[] = ['action' => $action, 'resolution' => $savedResolution];

                            } catch (\Exception $e) {
                                $errors[] = ['resolution' => $resolucionData['resolution_number'] ?? 'N/A', 'error' => $e->getMessage()];
                            }
                        }
                    }

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Sincronización completada',
                        'data' => $savedResolutions,
                        'errors' => $errors
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error en API DIAN',
                    'api_response' => $responseData
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error sync DIAN API: ' . $e->getMessage());
            return response()->json(['message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verificar acceso a la empresa
     */
    private function tieneAccesoEmpresa($empresaId)
    {
        $user = Auth::user();
        if ($user->esAdministradorGlobal()) {
            return true;
        }
        $empresasUsuario = $user->empresasActivas->pluck('id')->toArray();
        return in_array($empresaId, $empresasUsuario);
    }

    /**
     * Extraer clave técnica
     */
    private function extractTechnicalKey($technicalKey)
    {
        if (empty($technicalKey)) return null;
        if (is_string($technicalKey)) {
             if (strpos($technicalKey, '"nil":"true"') !== false) return null;
             $decoded = json_decode($technicalKey, true);
             if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                 if (isset($decoded['_attributes']['nil']) && $decoded['_attributes']['nil'] === 'true') return null;
             }
             return trim($technicalKey) ?: null;
        }
        if (is_array($technicalKey)) {
             if (isset($technicalKey['_attributes']['nil']) && $technicalKey['_attributes']['nil'] === 'true') return null;
             if (isset($technicalKey['value'])) return $this->extractTechnicalKey($technicalKey['value']);
             if (count($technicalKey) === 1 && !isset($technicalKey['_attributes'])) return $this->extractTechnicalKey(reset($technicalKey));
        }
        if (is_scalar($technicalKey)) return trim((string)$technicalKey) ?: null;
        return null;
    }
}
