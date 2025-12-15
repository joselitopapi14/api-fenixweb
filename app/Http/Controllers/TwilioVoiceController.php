<?php

namespace App\Http\Controllers;

use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\VoiceResponse;
use Twilio\Security\RequestValidator;

class TwilioVoiceController extends Controller
{
    /**
     * Webhook para manejar las llamadas de voz (TwiML)
     */
    public function voice(Request $request)
    {
        // Log de la petición para debug
        Log::info('Voice webhook called', [
            'request_data' => $request->all(),
            'user_agent' => $request->userAgent(),
            'from' => $request->input('From'),
            'to' => $request->input('To'),
            'call_sid' => $request->input('CallSid')
        ]);

        // Validar la firma de Twilio (opcional pero recomendado en producción)
        if (!$this->validateTwilioSignature($request)) {
            Log::warning('Invalid Twilio signature', [
                'url' => $request->fullUrl(),
                'signature' => $request->header('X-Twilio-Signature'),
                'request_data' => $request->all()
            ]);
        }

        // Crear respuesta TwiML que conecte inmediatamente agente (client) <-> ciudadano (PSTN)
        $response = new VoiceResponse();

        // El frontend (Twilio Device) envía el parámetro 'To' con el número destino
        $to = $request->input('To');
        $citizenId = $request->input('CitizenId'); // ID del ciudadano desde el frontend
        $userId = $request->input('UserId'); // ID del usuario autenticado desde el frontend
        $callSid = $request->input('CallSid');

        if ($to) {
            // Formatear a E.164 (Colombia +57) en caso de ser necesario
            $e164 = $this->formatE164Col($to);

            Log::info('Setting up dial with recording for call', [
                'from' => $request->input('From'),
                'to' => $e164,
                'call_sid' => $callSid,
                'citizen_id' => $citizenId,
                'user_id' => $userId
            ]);

            // CREAR REGISTRO DE LLAMADA INICIAL para evitar registros órfanos
            if ($citizenId && $callSid) {
                $this->createInitialCallRecord($citizenId, $callSid, $userId);
            }

            // Opciones de marcación: grabación dual, callback de grabación y estado, y answerOnBridge
            $dial = $response->dial(null, [
                'callerId' => config('services.twilio.phone_number'),
                'record' => 'record-from-answer-dual',
                'recordingStatusCallback' => route('twilio.recording'),
                'recordingStatusCallbackMethod' => 'POST',
                'statusCallback' => route('twilio.status'),
                'statusCallbackMethod' => 'POST',
                'statusCallbackEvent' => 'initiated ringing answered completed',
                'answerOnBridge' => true,
                'timeout' => 25,
            ]);

            // Marcar al ciudadano
            $dial->number($e164);

            Log::info('TwiML dial configured with recording', [
                'recording_callback' => route('twilio.recording'),
                'status_callback' => route('twilio.status')
            ]);
        } else {
            Log::warning('No "To" parameter received in voice webhook');
            // Si no llega 'To', finalizar silenciosamente (no reproducir operadora ni música)
            $response->hangup();
        }

        $twimlResponse = (string) $response;
        Log::info('Generated TwiML response', ['twiml' => $twimlResponse]);

        return response($twimlResponse, 200)->header('Content-Type', 'text/xml; charset=utf-8');
    }



    /**
     * Webhook para manejar las grabaciones completadas - SISTEMA AUTOMÁTICO GARANTIZADO
     */
    public function recording(Request $request)
    {
        // Log completo del webhook recibido para debugging
        Log::info('🎙️ WEBHOOK GRABACIÓN RECIBIDO', [
            'timestamp' => now()->toISOString(),
            'request_data' => $request->all(),
            'headers' => $request->headers->all(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip()
        ]);

        // Validar la firma de Twilio
        if (!$this->validateTwilioSignature($request)) {
            Log::error('❌ FIRMA TWILIO INVÁLIDA', ['url' => $request->fullUrl()]);
            return response('Invalid signature', 403);
        }

        // Extraer datos principales del webhook
        $dialSid = $request->input('DialCallSid');
        $parentSid = $request->input('CallSid');
        $callSid = $dialSid ?: $parentSid;
        $recordingSid = $request->input('RecordingSid');
        $recordingUrl = $request->input('RecordingUrl');
        $duration = (int) $request->input('RecordingDuration', 0);
        $recordingStatus = $request->input('RecordingStatus', 'completed');

        // Validar datos esenciales
        if (empty($recordingSid) || empty($recordingUrl)) {
            Log::error('❌ WEBHOOK GRABACIÓN INVÁLIDO - Faltan datos esenciales', [
                'recording_sid' => $recordingSid,
                'recording_url' => $recordingUrl,
                'call_sid' => $callSid
            ]);
            return response('Missing required recording data', 400);
        }

        Log::info('📋 PROCESANDO GRABACIÓN', [
            'call_sid' => $callSid,
            'dial_call_sid' => $dialSid,
            'parent_call_sid' => $parentSid,
            'recording_sid' => $recordingSid,
            'duration' => $duration,
            'status' => $recordingStatus
        ]);

        try {
            // PASO 1: Buscar la llamada usando estrategias múltiples y robustas
            $call = $this->findCallWithAdvancedStrategies($callSid, $dialSid, $parentSid, $recordingSid, $duration);

            // PASO 2: Verificar si la grabación ya existe para evitar duplicados
            if ($this->recordingAlreadyExists($recordingSid)) {
                Log::info('⏭️ GRABACIÓN YA EXISTE', [
                    'recording_sid' => $recordingSid,
                    'call_id' => $call ? $call->id : 'N/A'
                ]);
                return response('Recording already exists', 200);
            }

            // PASO 3: Descargar la grabación inmediatamente con reintentos automáticos
            $this->downloadRecordingWithRetries($recordingSid, $recordingUrl, $call, $duration);

            Log::info('✅ GRABACIÓN PROCESADA EXITOSAMENTE', [
                'call_id' => $call->id,
                'recording_sid' => $recordingSid,
                'call_sid' => $callSid
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ERROR CRÍTICO PROCESANDO GRABACIÓN', [
                'call_sid' => $callSid,
                'recording_sid' => $recordingSid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Encolar para reintento automático en caso de fallo
            $this->enqueueRecordingForRetry($recordingSid, $recordingUrl, $callSid, $duration);

            return response('Internal server error', 500);
        }

        return response('OK', 200);
    }

    /**
     * Webhook para recibir cambios de estado de las llamadas
     */
    public function status(Request $request)
    {
        // Validar la firma de Twilio
        if (!$this->validateTwilioSignature($request)) {
            Log::warning('Invalid Twilio signature for status webhook', ['url' => $request->fullUrl()]);
            return response('Invalid signature', 403);
        }

    // Con <Dial> recibiremos DialCallSid/DialCallStatus para el leg saliente
    $dialSid = $request->input('DialCallSid');
    $parentSid = $request->input('CallSid');
    $callSid = $dialSid ?: $parentSid;
    $callStatus = $request->input('DialCallStatus') ?: $request->input('CallStatus');
        $callDuration = $request->input('CallDuration');

        Log::info('Call status update received', [
            'CallSid' => $callSid,
            'CallStatus' => $callStatus,
            'CallDuration' => $callDuration
        ]);

        try {
            // Buscar por PSTN leg primero, luego por parent leg
            $call = Call::where('call_sid', $callSid)->first();
            if (!$call && $dialSid && $parentSid) {
                $call = Call::where('call_sid', $parentSid)->first();
                // Normalizar a PSTN leg si existe
                if ($call) {
                    $call->update(['call_sid' => $dialSid]);
                }
            }

            if ($call) {
                // Actualizar estado según el status de Twilio
                $status = $this->mapTwilioStatus($callStatus);

                $updateData = ['status' => $status];

                // Si la llamada terminó, guardar duración
                if (in_array($callStatus, ['completed', 'busy', 'no-answer', 'failed', 'canceled'])) {
                    $updateData['ended_at'] = now();
                    if ($callDuration) {
                        $updateData['duration_seconds'] = (int) $callDuration;
                    }
                }

                $call->update($updateData);
            }

        } catch (\Exception $e) {
            Log::error('Error processing status webhook', [
                'CallSid' => $callSid,
                'Error' => $e->getMessage()
            ]);
        }

        return response('OK', 200);
    }

    /**
     * Mapear estados de Twilio a estados internos
     */
    private function mapTwilioStatus($twilioStatus)
    {
        return match($twilioStatus) {
            'queued', 'ringing' => 'initiated',
            'in-progress' => 'in-progress',
            'completed' => 'completed',
            'busy', 'no-answer', 'failed', 'canceled' => 'failed',
            default => 'initiated'
        };
    }

    /**
     * Descargar la grabación desde Twilio
     */
    private function downloadRecording(string $recordingUrl): ?string
    {
        try {
            // Twilio requiere autenticación básica para descargar grabaciones
            $response = Http::withBasicAuth(
                config('services.twilio.sid'),
                config('services.twilio.token')
            )->get($recordingUrl . '.mp3');

            if ($response->successful()) {
                return $response->body();
            }

            Log::error('Failed to download recording', [
                'URL' => $recordingUrl,
                'Status' => $response->status()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Exception downloading recording', [
                'URL' => $recordingUrl,
                'Error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Validar la firma de Twilio para seguridad
     */
    private function validateTwilioSignature(Request $request): bool
    {
        // En desarrollo, puedes desactivar esta validación
        if (config('app.env') === 'local') {
            Log::info('Skipping Twilio signature validation in local environment');
            return true;
        }

        $validator = new RequestValidator(config('services.twilio.token'));

        $signature = $request->header('X-Twilio-Signature', '');
        $url = $request->fullUrl();
        $params = $request->all();

        $isValid = $validator->validate($signature, $url, $params);

        if (!$isValid) {
            Log::error('Twilio signature validation failed', [
                'url' => $url,
                'signature' => $signature,
                'params' => $params
            ]);
        }

        return $isValid;
    }

    /**
     * Formatea a E.164 para Colombia (+57)
     */
    private function formatE164Col(string $phone): string
    {
        $clean = preg_replace('/[^\d+]/', '', $phone);

        // Ya viene con +57
        if (str_starts_with($clean, '+57')) {
            return $clean;
        }

        // Si comienza con 57 sin +
        if (str_starts_with($clean, '57')) {
            return '+' . $clean;
        }

        // Si tiene 10 dígitos, es celular típico colombiano
        $digits = preg_replace('/\D/', '', $clean);
        if (strlen($digits) === 10) {
            return '+57' . $digits;
        }

        // Por defecto, anteponer +57
        return '+57' . $digits;
    }

    /**
     * SISTEMA AVANZADO: Buscar llamada con estrategias múltiples y robustas
     */
    private function findCallWithAdvancedStrategies($callSid, $dialSid, $parentSid, $recordingSid, $duration): Call
    {
        $strategies = [
            'recording_sid_exact' => function() use ($recordingSid) {
                return Call::where('recording_sid', $recordingSid)->first();
            },
            'call_sid_exact' => function() use ($callSid) {
                return Call::where('call_sid', $callSid)->first();
            },
            'dial_sid_exact' => function() use ($dialSid) {
                return $dialSid ? Call::where('call_sid', $dialSid)->first() : null;
            },
            'parent_sid_exact' => function() use ($parentSid) {
                return $parentSid ? Call::where('call_sid', $parentSid)->first() : null;
            },
            'recent_in_progress' => function() use ($duration) {
                return Call::where('status', 'in-progress')
                    ->whereNull('recording_sid')
                    ->where('started_at', '>=', now()->subMinutes(15))
                    ->orderBy('started_at', 'desc')
                    ->first();
            },
            'recent_completed' => function() use ($duration) {
                return Call::whereIn('status', ['in-progress', 'completed'])
                    ->whereNull('recording_sid')
                    ->where('updated_at', '>=', now()->subMinutes(10))
                    ->orderBy('updated_at', 'desc')
                    ->first();
            },
            'time_window_match' => function() use ($duration) {
                $estimatedEndTime = now();
                $estimatedStartTime = $estimatedEndTime->copy()->subSeconds($duration);

                return Call::whereBetween('started_at', [
                    $estimatedStartTime->subMinutes(3),
                    $estimatedStartTime->addMinutes(3)
                ])
                ->whereNull('recording_sid')
                ->orderBy('started_at', 'desc')
                ->first();
            }
        ];

        foreach ($strategies as $strategyName => $strategy) {
            $call = $strategy();
            if ($call) {
                Log::info("🎯 LLAMADA ENCONTRADA CON ESTRATEGIA: {$strategyName}", [
                    'strategy' => $strategyName,
                    'call_id' => $call->id,
                    'call_sid' => $call->call_sid,
                    'original_lookup_sid' => $callSid
                ]);
                return $call;
            }
        }

        // Si no se encuentra, crear registro órfano como ÚLTIMO RECURSO
        Log::error('🔍 NO SE ENCONTRÓ LLAMADA DESPUÉS DE TODAS LAS ESTRATEGIAS', [
            'call_sid' => $callSid,
            'dial_call_sid' => $dialSid,
            'parent_call_sid' => $parentSid,
            'recording_sid' => $recordingSid,
            'warning' => 'Esto indica un problema en el flujo - debería existir registro inicial'
        ]);

        return $this->createOrphanCallRecord($callSid, $recordingSid, $duration);
    }

    /**
     * Verificar si una grabación ya existe para evitar duplicados
     */
    private function recordingAlreadyExists($recordingSid): bool
    {
        $existingCall = Call::where('recording_sid', $recordingSid)->first();

        if (!$existingCall) {
            return false;
        }

        // Verificar que el archivo físico también existe
        if ($existingCall->audio_path && Storage::exists($existingCall->audio_path)) {
            return true;
        }

        // Si existe el registro pero no el archivo, eliminar registro para reprocessar
        Log::warning('🗑️ LIMPIANDO REGISTRO SIN ARCHIVO', [
            'call_id' => $existingCall->id,
            'recording_sid' => $recordingSid,
            'missing_path' => $existingCall->audio_path
        ]);

        $existingCall->update([
            'recording_sid' => null,
            'audio_path' => null
        ]);

        return false;
    }

    /**
     * Descargar grabación con sistema de reintentos automáticos
     */
    private function downloadRecordingWithRetries($recordingSid, $recordingUrl, Call $call, $duration): void
    {
        $maxRetries = 3;
        $retryDelay = 2; // segundos

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("📥 INTENTO DESCARGA #{$attempt}", [
                    'recording_sid' => $recordingSid,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries
                ]);

                $audioContent = $this->downloadRecording($recordingUrl);

                if ($audioContent) {
                    $this->saveRecordingToStorage($recordingSid, $audioContent, $call, $duration);

                    Log::info('✅ GRABACIÓN DESCARGADA Y GUARDADA', [
                        'recording_sid' => $recordingSid,
                        'attempt' => $attempt,
                        'call_id' => $call->id,
                        'file_size' => strlen($audioContent)
                    ]);

                    return; // Éxito, salir del bucle
                }

                throw new \Exception("Contenido de audio vacío en intento {$attempt}");

            } catch (\Exception $e) {
                Log::warning("⚠️ FALLO EN INTENTO #{$attempt}", [
                    'recording_sid' => $recordingSid,
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);

                if ($attempt === $maxRetries) {
                    // Último intento fallido, encolar para reintento posterior
                    $this->enqueueRecordingForRetry($recordingSid, $recordingUrl, $call->call_sid, $duration);
                    throw $e;
                }

                // Esperar antes del siguiente intento
                sleep($retryDelay * $attempt);
            }
        }
    }

    /**
     * Guardar grabación en storage y actualizar base de datos
     */
    private function saveRecordingToStorage($recordingSid, $audioContent, Call $call, $duration): void
    {
        // Verificar el directorio recordings existe
        if (!Storage::exists('recordings')) {
            Storage::makeDirectory('recordings');
            Log::info('📁 DIRECTORIO RECORDINGS CREADO');
        }

        // Generar nombre de archivo único con timestamp
        $filename = $recordingSid . '_' . now()->format('Y-m-d_H-i-s') . '.mp3';
        $path = 'recordings/' . $filename;

        // Guardar archivo
        Storage::put($path, $audioContent);

        // Verificar que se guardó correctamente
        if (!Storage::exists($path)) {
            throw new \Exception("No se pudo verificar que el archivo se guardó correctamente: {$path}");
        }

        $fileSize = Storage::size($path);

        Log::info('💾 ARCHIVO GUARDADO EN STORAGE', [
            'path' => $path,
            'size_bytes' => $fileSize,
            'size_mb' => round($fileSize / 1024 / 1024, 2)
        ]);

        // Actualizar registro de llamada con datos completos
        $call->update([
            'recording_sid' => $recordingSid,
            'duration_seconds' => $duration,
            'audio_path' => $path,
            'status' => 'completed',
            'ended_at' => $call->ended_at ?: now()
        ]);

        Log::info('🗃️ BASE DE DATOS ACTUALIZADA', [
            'call_id' => $call->id,
            'recording_sid' => $recordingSid,
            'audio_path' => $path
        ]);
    }

    /**
     * Crear registro órfano para grabaciones sin llamada asociada
     */
    private function createOrphanCallRecord($callSid, $recordingSid, $duration): Call
    {
        // Obtener primer ciudadano y usuario disponibles
        $firstCitizen = \App\Models\Ciudadano::first();
        $firstUser = \App\Models\User::first();

        if (!$firstCitizen || !$firstUser) {
            throw new \Exception('No hay ciudadanos o usuarios disponibles para crear registro órfano');
        }

        $call = Call::create([
            'citizen_id' => $firstCitizen->id,
            'user_id' => $firstUser->id,
            'call_sid' => $callSid,
            'status' => 'completed',
            'duration_seconds' => $duration,
            'started_at' => now()->subSeconds($duration),
            'ended_at' => now()
        ]);

        Log::info('🆕 REGISTRO ÓRFANO CREADO', [
            'call_id' => $call->id,
            'call_sid' => $callSid,
            'citizen_id' => $firstCitizen->id,
            'user_id' => $firstUser->id
        ]);

        return $call;
    }

        /**
     * Encolar grabación para reintento posterior usando queue system
     */
    private function enqueueRecordingForRetry($recordingSid, $recordingUrl, $callSid, $duration): void
    {
        try {
            // Si Laravel Queues está configurado, usar jobs
            if (config('queue.default') !== 'sync') {
                \App\Jobs\ProcessRecordingRetry::dispatch($recordingSid, $recordingUrl, $callSid, $duration)
                    ->delay(now()->addMinutes(5)); // Reintento en 5 minutos

                Log::info('📤 GRABACIÓN ENCOLADA PARA REINTENTO', [
                    'recording_sid' => $recordingSid,
                    'retry_in_minutes' => 5
                ]);
            } else {
                // Fallback: crear log para procesamiento manual
                Log::error('🔄 REINTENTO MANUAL REQUERIDO', [
                    'recording_sid' => $recordingSid,
                    'recording_url' => $recordingUrl,
                    'call_sid' => $callSid,
                    'duration' => $duration,
                    'command_to_run' => "php artisan calls:recover-recordings --recording-sid={$recordingSid}"
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ ERROR ENCOLANDO REINTENTO', [
                'recording_sid' => $recordingSid,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Crear o actualizar registro inicial de llamada para evitar registros órfanos
     */
    private function createInitialCallRecord($citizenId, $callSid, $userId = null): void
    {
        try {
            // ESTRATEGIA 1: Verificar si ya existe un registro para este call_sid
            $existingCall = Call::where('call_sid', $callSid)->first();
            if ($existingCall) {
                Log::info('📞 LLAMADA YA REGISTRADA CON CALL_SID', [
                    'call_sid' => $callSid,
                    'existing_call_id' => $existingCall->id,
                    'citizen_id' => $existingCall->citizen_id
                ]);
                return;
            }

            // ESTRATEGIA 2: Buscar llamada reciente del mismo ciudadano sin call_sid
            $recentCall = Call::where('citizen_id', $citizenId)
                ->whereNull('call_sid')
                ->where('status', 'initiated')
                ->where('created_at', '>=', now()->subMinutes(2))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($recentCall) {
                // Actualizar la llamada existente con el call_sid y user_id
                $updateData = [
                    'call_sid' => $callSid,
                    'status' => 'in-progress',
                    'started_at' => now()
                ];

                // Si tenemos userId del frontend, actualizarlo
                if ($userId) {
                    $updateData['user_id'] = $userId;
                }

                $recentCall->update($updateData);

                Log::info('🔄 LLAMADA EXISTENTE ACTUALIZADA CON CALL_SID', [
                    'call_id' => $recentCall->id,
                    'call_sid' => $callSid,
                    'citizen_id' => $citizenId,
                    'user_id' => $userId,
                    'updated_from_status' => 'initiated'
                ]);
                return;
            }

            // ESTRATEGIA 3: Crear nuevo registro si no existe
            $ciudadano = \App\Models\Ciudadano::find($citizenId);
            if (!$ciudadano) {
                Log::warning('⚠️ CIUDADANO NO ENCONTRADO PARA LLAMADA INICIAL', [
                    'citizen_id' => $citizenId,
                    'call_sid' => $callSid
                ]);
                return;
            }

            // Obtener el usuario: del frontend, autenticado, o el primero disponible
            if (!$userId) {
                $userId = auth()->id() ?: \App\Models\User::first()?->id;
            }

            if (!$userId) {
                Log::error('❌ NO HAY USUARIOS DISPONIBLES', [
                    'call_sid' => $callSid,
                    'citizen_id' => $citizenId
                ]);
                return;
            }

            // Crear el registro inicial de la llamada
            $call = Call::create([
                'citizen_id' => $citizenId,
                'user_id' => $userId,
                'call_sid' => $callSid,
                'status' => 'in-progress',
                'started_at' => now()
            ]);

            Log::info('✅ NUEVO REGISTRO DE LLAMADA CREADO', [
                'call_id' => $call->id,
                'call_sid' => $callSid,
                'citizen_id' => $citizenId,
                'citizen_name' => $ciudadano->nombre_completo,
                'user_id' => $userId
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ERROR CREANDO/ACTUALIZANDO REGISTRO DE LLAMADA', [
                'call_sid' => $callSid,
                'citizen_id' => $citizenId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
