<?php

namespace App\Jobs;

use App\Models\Call;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessRecordingRetry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $recordingSid;
    public $recordingUrl;
    public $callSid;
    public $duration;

    /**
     * Número máximo de intentos
     */
    public $tries = 5;

    /**
     * Tiempo de espera entre reintentos (en segundos)
     */
    public $backoff = [60, 300, 900, 1800]; // 1min, 5min, 15min, 30min

    /**
     * Create a new job instance.
     */
    public function __construct($recordingSid, $recordingUrl, $callSid, $duration)
    {
        $this->recordingSid = $recordingSid;
        $this->recordingUrl = $recordingUrl;
        $this->callSid = $callSid;
        $this->duration = $duration;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🔄 PROCESANDO REINTENTO DE GRABACIÓN', [
            'recording_sid' => $this->recordingSid,
            'attempt' => $this->attempts(),
            'max_attempts' => $this->tries
        ]);

        try {
            // 1. Verificar si la grabación ya fue procesada exitosamente
            if ($this->recordingAlreadyProcessed()) {
                Log::info('✅ GRABACIÓN YA PROCESADA - CANCELANDO JOB', [
                    'recording_sid' => $this->recordingSid
                ]);
                return;
            }

            // 2. Buscar la llamada asociada
            $call = $this->findAssociatedCall();

            // 3. Descargar la grabación
            $audioContent = $this->downloadRecording();

            if (!$audioContent) {
                throw new Exception('No se pudo descargar el contenido de audio');
            }

            // 4. Guardar en storage
            $this->saveRecordingToStorage($audioContent, $call);

            Log::info('✅ REINTENTO DE GRABACIÓN EXITOSO', [
                'recording_sid' => $this->recordingSid,
                'call_id' => $call->id,
                'attempt' => $this->attempts()
            ]);

        } catch (Exception $e) {
            Log::error('❌ FALLO EN REINTENTO DE GRABACIÓN', [
                'recording_sid' => $this->recordingSid,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage()
            ]);

            // Si es el último intento, registrar como fallo crítico
            if ($this->attempts() >= $this->tries) {
                Log::critical('🚨 GRABACIÓN PERDIDA DEFINITIVAMENTE', [
                    'recording_sid' => $this->recordingSid,
                    'call_sid' => $this->callSid,
                    'final_attempt' => $this->attempts(),
                    'recovery_command' => "php artisan calls:recover-recordings --recording-sid={$this->recordingSid}"
                ]);
            }

            throw $e; // Re-lanzar para que Laravel Queue maneje el reintento
        }
    }

    /**
     * Verificar si la grabación ya fue procesada exitosamente
     */
    private function recordingAlreadyProcessed(): bool
    {
        $call = Call::where('recording_sid', $this->recordingSid)->first();

        return $call &&
               $call->audio_path &&
               Storage::exists($call->audio_path) &&
               $call->status === 'completed';
    }

    /**
     * Buscar la llamada asociada con esta grabación
     */
    private function findAssociatedCall(): Call
    {
        // Intentar múltiples estrategias de búsqueda
        $strategies = [
            fn() => Call::where('recording_sid', $this->recordingSid)->first(),
            fn() => Call::where('call_sid', $this->callSid)->first(),
            fn() => Call::where('status', 'in-progress')
                        ->whereNull('recording_sid')
                        ->where('started_at', '>=', now()->subHours(2))
                        ->orderBy('started_at', 'desc')
                        ->first()
        ];

        foreach ($strategies as $strategy) {
            $call = $strategy();
            if ($call) {
                return $call;
            }
        }

        // Si no se encuentra, crear registro órfano
        return $this->createOrphanCall();
    }

    /**
     * Crear llamada órfana para no perder la grabación
     */
    private function createOrphanCall(): Call
    {
        $firstCitizen = \App\Models\Ciudadano::first();
        $firstUser = \App\Models\User::first();

        if (!$firstCitizen || !$firstUser) {
            throw new Exception('No hay ciudadanos o usuarios disponibles para crear registro órfano');
        }

        $call = Call::create([
            'citizen_id' => $firstCitizen->id,
            'user_id' => $firstUser->id,
            'call_sid' => $this->callSid,
            'status' => 'completed',
            'duration_seconds' => $this->duration,
            'started_at' => now()->subSeconds($this->duration),
            'ended_at' => now()
        ]);

        Log::info('🆕 LLAMADA ÓRFANA CREADA EN REINTENTO', [
            'call_id' => $call->id,
            'call_sid' => $this->callSid,
            'recording_sid' => $this->recordingSid
        ]);

        return $call;
    }

    /**
     * Descargar la grabación desde Twilio
     */
    private function downloadRecording(): ?string
    {
        try {
            $response = Http::timeout(60)
                ->withBasicAuth(
                    config('services.twilio.sid'),
                    config('services.twilio.token')
                )
                ->get($this->recordingUrl . '.mp3');

            if ($response->successful()) {
                $content = $response->body();

                Log::info('📥 GRABACIÓN DESCARGADA EN REINTENTO', [
                    'recording_sid' => $this->recordingSid,
                    'size_bytes' => strlen($content),
                    'attempt' => $this->attempts()
                ]);

                return $content;
            }

            Log::warning('⚠️ FALLO DESCARGA EN REINTENTO', [
                'recording_sid' => $this->recordingSid,
                'status_code' => $response->status(),
                'attempt' => $this->attempts()
            ]);

            return null;

        } catch (Exception $e) {
            Log::error('❌ EXCEPCIÓN EN DESCARGA DE REINTENTO', [
                'recording_sid' => $this->recordingSid,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            return null;
        }
    }

    /**
     * Guardar grabación en storage y actualizar base de datos
     */
    private function saveRecordingToStorage(string $audioContent, Call $call): void
    {
        // Asegurar que el directorio existe
        if (!Storage::exists('recordings')) {
            Storage::makeDirectory('recordings');
        }

        // Generar nombre único para evitar conflictos
        $filename = $this->recordingSid . '_retry_' . now()->format('Y-m-d_H-i-s') . '.mp3';
        $path = 'recordings/' . $filename;

        // Guardar archivo
        Storage::put($path, $audioContent);

        // Verificar que se guardó
        if (!Storage::exists($path)) {
            throw new Exception("No se pudo verificar el guardado del archivo: {$path}");
        }

        // Actualizar base de datos
        $call->update([
            'recording_sid' => $this->recordingSid,
            'duration_seconds' => $this->duration,
            'audio_path' => $path,
            'status' => 'completed',
            'ended_at' => $call->ended_at ?: now()
        ]);

        Log::info('💾 GRABACIÓN GUARDADA EN REINTENTO', [
            'call_id' => $call->id,
            'path' => $path,
            'size_mb' => round(Storage::size($path) / 1024 / 1024, 2)
        ]);
    }

    /**
     * Manejar cuando el job falla definitivamente
     */
    public function failed(Exception $exception): void
    {
        Log::critical('🚨 JOB DE REINTENTO FALLÓ DEFINITIVAMENTE', [
            'recording_sid' => $this->recordingSid,
            'call_sid' => $this->callSid,
            'final_error' => $exception->getMessage(),
            'manual_recovery' => "Ejecutar: php artisan calls:recover-recordings --recording-sid={$this->recordingSid}"
        ]);
    }
}
