<?php

namespace App\Console\Commands;

use App\Models\Call;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Twilio\Rest\Client;
use Carbon\Carbon;

class AutoRecoveryRecordings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:auto-recovery {--hours=2 : Horas hacia atrás para verificar} {--force : Forzar verificación completa}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sistema automático de verificación y recuperación de grabaciones perdidas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');
        $force = $this->option('force');

        $this->info("🔍 INICIANDO VERIFICACIÓN AUTOMÁTICA DE GRABACIONES");
        $this->info("📅 Verificando últimas {$hours} horas");

        Log::info('🤖 AUTO-RECOVERY INICIADO', [
            'hours_back' => $hours,
            'force_check' => $force,
            'executed_at' => now()->toISOString()
        ]);

        try {
            // PASO 1: Verificar llamadas sin grabación
            $callsWithoutRecordings = $this->findCallsWithoutRecordings($hours);
            $this->info("📊 Encontradas {$callsWithoutRecordings->count()} llamadas sin grabación");

            // PASO 2: Verificar llamadas con registro pero sin archivo físico
            $callsWithMissingFiles = $this->findCallsWithMissingFiles($hours);
            $this->info("📊 Encontradas {$callsWithMissingFiles->count()} llamadas con archivos faltantes");

            // PASO 3: Recuperar grabaciones de Twilio
            $recoveredCount = $this->recoverMissingRecordings($hours, $force);

            // PASO 4: Limpiar registros inconsistentes
            $cleanedCount = $this->cleanInconsistentRecords();

            // PASO 5: Generar reporte
            $this->generateRecoveryReport($callsWithoutRecordings->count(), $callsWithMissingFiles->count(), $recoveredCount, $cleanedCount);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error en verificación automática: {$e->getMessage()}");
            Log::error('❌ AUTO-RECOVERY FALLÓ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Buscar llamadas completadas sin grabación
     */
    private function findCallsWithoutRecordings(int $hours)
    {
        $cutoffTime = now()->subHours($hours);

        return Call::where('status', 'completed')
            ->where('created_at', '>=', $cutoffTime)
            ->where(function($query) {
                $query->whereNull('recording_sid')
                      ->orWhereNull('audio_path');
            })
            ->where('duration_seconds', '>', 5) // Solo llamadas de más de 5 segundos
            ->get();
    }

    /**
     * Buscar llamadas con grabación registrada pero archivo físico faltante
     */
    private function findCallsWithMissingFiles(int $hours)
    {
        $cutoffTime = now()->subHours($hours);

        return Call::where('status', 'completed')
            ->where('created_at', '>=', $cutoffTime)
            ->whereNotNull('recording_sid')
            ->whereNotNull('audio_path')
            ->get()
            ->filter(function($call) {
                return !Storage::exists($call->audio_path);
            });
    }

    /**
     * Recuperar grabaciones faltantes desde Twilio
     */
    private function recoverMissingRecordings(int $hours, bool $force): int
    {
        try {
            $twilio = new Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $dateAfter = now()->subHours($hours);
            $recoveredCount = 0;

            $this->info("🔄 Consultando grabaciones de Twilio desde: " . $dateAfter->format('Y-m-d H:i:s'));

            $recordings = $twilio->recordings->read([
                'dateCreatedAfter' => $dateAfter,
                'limit' => 200
            ]);

            $this->info("📦 Encontradas " . count($recordings) . " grabaciones en Twilio");

            foreach ($recordings as $recording) {
                try {
                    if ($this->shouldProcessRecording($recording, $force)) {
                        if ($this->processRecordingRecovery($recording)) {
                            $recoveredCount++;
                            $this->line("✅ Recuperada: {$recording->sid}");
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error procesando {$recording->sid}: {$e->getMessage()}");
                }
            }

            return $recoveredCount;

        } catch (\Exception $e) {
            $this->error("❌ Error conectando con Twilio: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Determinar si una grabación debe ser procesada
     */
    private function shouldProcessRecording($recording, bool $force): bool
    {
        // Si force está activado, procesar todas
        if ($force) {
            return true;
        }

        // Verificar si ya existe localmente
        $existingCall = Call::where('recording_sid', $recording->sid)->first();

        if (!$existingCall) {
            return true; // No existe en BD
        }

        if (!$existingCall->audio_path) {
            return true; // Existe en BD pero sin ruta de audio
        }

        if (!Storage::exists($existingCall->audio_path)) {
            return true; // Existe en BD pero archivo físico faltante
        }

        return false; // Todo está bien, no procesar
    }

    /**
     * Procesar recuperación de una grabación específica
     */
    private function processRecordingRecovery($recording): bool
    {
        try {
            // Buscar o crear llamada
            $call = $this->findOrCreateCallForRecording($recording);

            // Descargar y guardar
            $audioContent = $this->downloadRecordingFromTwilio($recording);

            if (!$audioContent) {
                return false;
            }

            $this->saveRecordingToStorage($recording, $audioContent, $call);

            Log::info('🔧 AUTO-RECOVERY EXITOSO', [
                'recording_sid' => $recording->sid,
                'call_id' => $call->id,
                'call_sid' => $recording->callSid
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('❌ ERROR EN AUTO-RECOVERY', [
                'recording_sid' => $recording->sid,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Buscar o crear llamada para la grabación
     */
    private function findOrCreateCallForRecording($recording): Call
    {
        // Estrategias de búsqueda
        $strategies = [
            fn() => Call::where('recording_sid', $recording->sid)->first(),
            fn() => Call::where('call_sid', $recording->callSid)->first(),
            fn() => $this->findCallByTimeWindow($recording)
        ];

        foreach ($strategies as $strategy) {
            $call = $strategy();
            if ($call) {
                return $call;
            }
        }

        // Crear registro órfano si no se encuentra
        return $this->createOrphanCallRecord($recording);
    }

    /**
     * Buscar llamada por ventana de tiempo
     */
    private function findCallByTimeWindow($recording): ?Call
    {
        $recordingTime = Carbon::parse($recording->dateCreated);

        return Call::whereBetween('started_at', [
            $recordingTime->copy()->subMinutes(5),
            $recordingTime->copy()->addMinutes(5)
        ])
        ->whereNull('recording_sid')
        ->where('status', 'completed')
        ->orderBy('started_at', 'desc')
        ->first();
    }

    /**
     * Crear registro órfano para grabación
     */
    private function createOrphanCallRecord($recording): Call
    {
        $firstCitizen = \App\Models\Ciudadano::first();
        $firstUser = \App\Models\User::first();

        if (!$firstCitizen || !$firstUser) {
            throw new \Exception('No hay ciudadanos o usuarios disponibles');
        }

        return Call::create([
            'citizen_id' => $firstCitizen->id,
            'user_id' => $firstUser->id,
            'call_sid' => $recording->callSid,
            'status' => 'completed',
            'duration_seconds' => $recording->duration,
            'started_at' => Carbon::parse($recording->dateCreated)->subSeconds($recording->duration),
            'ended_at' => Carbon::parse($recording->dateCreated)
        ]);
    }

    /**
     * Descargar grabación desde Twilio
     */
    private function downloadRecordingFromTwilio($recording): ?string
    {
        try {
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$recording->accountSid}/Recordings/{$recording->sid}.mp3";

            $response = \Illuminate\Support\Facades\Http::timeout(120)
                ->withBasicAuth(
                    config('services.twilio.sid'),
                    config('services.twilio.token')
                )
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('🔽 FALLO DESCARGA AUTO-RECOVERY', [
                'recording_sid' => $recording->sid,
                'status_code' => $response->status()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('❌ EXCEPCIÓN DESCARGA AUTO-RECOVERY', [
                'recording_sid' => $recording->sid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Guardar grabación en storage
     */
    private function saveRecordingToStorage($recording, $audioContent, Call $call): void
    {
        if (!Storage::exists('recordings')) {
            Storage::makeDirectory('recordings');
        }

        $filename = $recording->sid . '_auto_' . now()->format('Y-m-d_H-i-s') . '.mp3';
        $path = 'recordings/' . $filename;

        Storage::put($path, $audioContent);

        $call->update([
            'recording_sid' => $recording->sid,
            'duration_seconds' => $recording->duration,
            'audio_path' => $path,
            'status' => 'completed'
        ]);
    }

    /**
     * Limpiar registros inconsistentes
     */
    private function cleanInconsistentRecords(): int
    {
        $cleaned = 0;

        // Limpiar llamadas con audio_path pero sin recording_sid
        $inconsistentCalls = Call::whereNotNull('audio_path')
            ->whereNull('recording_sid')
            ->get();

        foreach ($inconsistentCalls as $call) {
            if (!Storage::exists($call->audio_path)) {
                $call->update(['audio_path' => null]);
                $cleaned++;
            }
        }

        // Limpiar llamadas con recording_sid pero sin archivo
        $missingFiles = Call::whereNotNull('recording_sid')
            ->whereNotNull('audio_path')
            ->get()
            ->filter(fn($call) => !Storage::exists($call->audio_path));

        foreach ($missingFiles as $call) {
            $call->update(['audio_path' => null]);
            $cleaned++;
        }

        return $cleaned;
    }

    /**
     * Generar reporte de recuperación
     */
    private function generateRecoveryReport(int $withoutRecordings, int $missingFiles, int $recovered, int $cleaned): void
    {
        $this->info("\n📊 REPORTE DE VERIFICACIÓN AUTOMÁTICA");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📞 Llamadas sin grabación: {$withoutRecordings}");
        $this->info("📁 Archivos faltantes: {$missingFiles}");
        $this->info("🔧 Grabaciones recuperadas: {$recovered}");
        $this->info("🧹 Registros limpiados: {$cleaned}");
        $this->info("⏰ Ejecutado en: " . now()->format('Y-m-d H:i:s'));

        // Log para monitoring
        Log::info('📈 REPORTE AUTO-RECOVERY', [
            'calls_without_recordings' => $withoutRecordings,
            'missing_files' => $missingFiles,
            'recordings_recovered' => $recovered,
            'records_cleaned' => $cleaned,
            'execution_time' => now()->toISOString()
        ]);

        if ($recovered > 0) {
            $this->info("\n✅ Se recuperaron {$recovered} grabaciones automáticamente");
        }

        if ($withoutRecordings > $recovered) {
            $remaining = $withoutRecordings - $recovered;
            $this->warn("\n⚠️  Quedan {$remaining} llamadas sin grabación para revisión manual");
        }
    }
}
