<?php

namespace App\Console\Commands;

use App\Models\Call;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Twilio\Rest\Client;

class RecoverMissingRecordings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:recover-recordings {--recording-sid= : Recuperar una grabación específica por SID} {--days=7 : Días hacia atrás para buscar grabaciones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recuperar grabaciones de Twilio que existen pero no están guardadas localmente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $specificRecordingSid = $this->option('recording-sid');
        $days = (int) $this->option('days');

        if ($specificRecordingSid) {
            return $this->recoverSpecificRecording($specificRecordingSid);
        }

        return $this->recoverRecentRecordings($days);
    }

    /**
     * Recuperar una grabación específica por SID
     */
    private function recoverSpecificRecording(string $recordingSid): int
    {
        $this->info("Recuperando grabación específica: {$recordingSid}");

        try {
            $twilio = new Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            // Obtener la grabación desde Twilio
            $recording = $twilio->recordings($recordingSid)->fetch();

            $this->info("Grabación encontrada:");
            $this->line("- Call SID: {$recording->callSid}");
            $this->line("- Duración: {$recording->duration} segundos");
            $this->line("- Fecha: {$recording->dateCreated->format('Y-m-d H:i:s')}");

            // Buscar la llamada en nuestra base de datos
            $call = $this->findCallByRecording($recording);

            if (!$call) {
                $this->warn("No se encontró la llamada en la base de datos. Creando registro...");
                $call = $this->createCallFromRecording($recording);
            }

            // Descargar y guardar la grabación
            if ($this->downloadAndSaveRecording($recording, $call)) {
                $this->info("✅ Grabación recuperada exitosamente para llamada ID: {$call->id}");
                return 0;
            } else {
                $this->error("❌ Error al descargar la grabación");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("Error recuperando grabación: {$e->getMessage()}");
            Log::error('Error in recover specific recording', [
                'recording_sid' => $recordingSid,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }

    /**
     * Recuperar grabaciones recientes que faltan
     */
    private function recoverRecentRecordings(int $days): int
    {
        $this->info("Buscando grabaciones de los últimos {$days} días...");

        try {
            $twilio = new Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $dateCreatedAfter = now()->subDays($days);

            $this->info("Consultando grabaciones desde: " . $dateCreatedAfter->format('Y-m-d H:i:s'));

            $recordings = $twilio->recordings->read([
                'dateCreatedAfter' => $dateCreatedAfter,
                'limit' => 100
            ]);

            $this->info("Encontradas " . count($recordings) . " grabaciones en Twilio");

            $recoveredCount = 0;
            $skippedCount = 0;
            $errorCount = 0;

            foreach ($recordings as $recording) {
                try {
                    // Verificar si ya tenemos esta grabación guardada
                    $existingCall = Call::where('recording_sid', $recording->sid)->first();

                    if ($existingCall && $existingCall->audio_path && Storage::exists($existingCall->audio_path)) {
                        $skippedCount++;
                        $this->line("⏭️  Saltando {$recording->sid} (ya existe)");
                        continue;
                    }

                    $this->line("📥 Procesando grabación: {$recording->sid}");

                    // Buscar la llamada correspondiente
                    $call = $this->findCallByRecording($recording);

                    if (!$call) {
                        $this->warn("   No se encontró llamada para {$recording->callSid}, creando registro...");
                        $call = $this->createCallFromRecording($recording);
                    }

                    // Descargar y guardar la grabación
                    if ($this->downloadAndSaveRecording($recording, $call)) {
                        $recoveredCount++;
                        $this->info("   ✅ Recuperada para llamada ID: {$call->id}");
                    } else {
                        $errorCount++;
                        $this->error("   ❌ Error al descargar");
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("   ❌ Error procesando {$recording->sid}: {$e->getMessage()}");
                }
            }

            $this->info("\n📊 Resumen:");
            $this->info("- Grabaciones recuperadas: {$recoveredCount}");
            $this->info("- Grabaciones ya existentes: {$skippedCount}");
            $this->info("- Errores: {$errorCount}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Error general: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Buscar llamada por grabación usando múltiples estrategias
     */
    private function findCallByRecording($recording): ?Call
    {
        // 1. Buscar por recording_sid
        $call = Call::where('recording_sid', $recording->sid)->first();
        if ($call) return $call;

        // 2. Buscar por call_sid exacto
        $call = Call::where('call_sid', $recording->callSid)->first();
        if ($call) return $call;

        // 3. Buscar por fecha/hora aproximada (±2 minutos)
        $recordingTime = \Carbon\Carbon::parse($recording->dateCreated);
        $call = Call::whereBetween('started_at', [
            $recordingTime->copy()->subMinutes(2),
            $recordingTime->copy()->addMinutes(2)
        ])->whereNull('recording_sid')->first();
        if ($call) return $call;

        // 4. Buscar por llamadas recientes sin grabación
        $call = Call::where('status', 'in-progress')
            ->whereNull('recording_sid')
            ->whereBetween('started_at', [
                $recordingTime->copy()->subMinutes(5),
                $recordingTime->copy()->addMinutes(5)
            ])
            ->first();

        return $call;
    }

        /**
     * Crear registro de llamada desde una grabación
     */
    private function createCallFromRecording($recording): Call
    {
        // Obtener el primer ciudadano y usuario disponibles
        $firstCitizen = \App\Models\Ciudadano::first();
        $firstUser = \App\Models\User::first();

        if (!$firstCitizen) {
            throw new \Exception('No hay ciudadanos en la base de datos. Debe crear al menos uno.');
        }

        if (!$firstUser) {
            throw new \Exception('No hay usuarios en la base de datos. Debe crear al menos uno.');
        }

        $this->line("   Usando ciudadano ID: {$firstCitizen->id} (" . ($firstCitizen->nombre_completo ?? 'Sin nombre') . ")");
        $this->line("   Usando usuario ID: {$firstUser->id} (" . ($firstUser->name ?? 'Sin nombre') . ")");

        return Call::create([
            'citizen_id' => $firstCitizen->id,
            'user_id' => $firstUser->id,
            'call_sid' => $recording->callSid,
            'recording_sid' => $recording->sid,
            'status' => 'completed',
            'duration_seconds' => $recording->duration,
            'started_at' => \Carbon\Carbon::parse($recording->dateCreated)->subSeconds($recording->duration),
            'ended_at' => \Carbon\Carbon::parse($recording->dateCreated),
        ]);
    }

    /**
     * Descargar y guardar grabación
     */
    private function downloadAndSaveRecording($recording, Call $call): bool
    {
        try {
            // Descargar el archivo desde Twilio
            $recordingUrl = "https://api.twilio.com/2010-04-01/Accounts/{$recording->accountSid}/Recordings/{$recording->sid}.mp3";

            $response = \Illuminate\Support\Facades\Http::withBasicAuth(
                config('services.twilio.sid'),
                config('services.twilio.token')
            )->get($recordingUrl);

            if (!$response->successful()) {
                $this->error("   Error descargando desde {$recordingUrl}: " . $response->status());
                return false;
            }

            // Guardar el archivo
            $filename = $recording->sid . '.mp3';
            $path = 'recordings/' . $filename;

            Storage::put($path, $response->body());

            // Actualizar el registro de la llamada
            $call->update([
                'recording_sid' => $recording->sid,
                'duration_seconds' => $recording->duration,
                'audio_path' => $path,
                'status' => 'completed'
            ]);

            Log::info('Recording recovered and saved', [
                'recording_sid' => $recording->sid,
                'call_id' => $call->id,
                'path' => $path
            ]);

            return true;

        } catch (\Exception $e) {
            $this->error("   Error guardando grabación: {$e->getMessage()}");
            Log::error('Error saving recovered recording', [
                'recording_sid' => $recording->sid,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
