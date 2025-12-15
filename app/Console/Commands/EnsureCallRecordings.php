<?php

namespace App\Console\Commands;

use App\Models\Call;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class EnsureCallRecordings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:ensure-recordings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar y forzar grabaciones para llamadas activas que no las tengan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando llamadas activas sin grabación...');

        // Obtener llamadas en progreso que no tengan grabación
        $calls = Call::where('status', 'in-progress')
            ->whereNull('recording_sid')
            ->whereNotNull('call_sid')
            ->where('started_at', '>=', now()->subHours(2))
            ->get();

        if ($calls->isEmpty()) {
            $this->info('No se encontraron llamadas activas sin grabación.');
            return 0;
        }

        $this->info("Encontradas {$calls->count()} llamadas sin grabación. Verificando...");

        $twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $processedCount = 0;
        $recordingStartedCount = 0;

        foreach ($calls as $call) {
            try {
                $this->line("Verificando llamada {$call->call_sid}...");

                // Obtener información de la llamada desde Twilio
                $twilioCall = $twilio->calls($call->call_sid)->fetch();

                Log::info('Checking call for recording', [
                    'call_sid' => $call->call_sid,
                    'local_status' => $call->status,
                    'twilio_status' => $twilioCall->status,
                    'twilio_recording_sid' => $twilioCall->recordingSid
                ]);

                // Si la llamada ya terminó en Twilio, actualizar estado local
                if (in_array($twilioCall->status, ['completed', 'busy', 'no-answer', 'failed', 'canceled'])) {
                    $call->update([
                        'status' => $twilioCall->status === 'completed' ? 'completed' : 'failed',
                        'ended_at' => now(),
                        'duration_seconds' => $twilioCall->duration ?: 0
                    ]);

                    $this->warn("Llamada {$call->call_sid} ya terminó. Estado actualizado a {$twilioCall->status}");
                    continue;
                }

                // Si la llamada está activa pero no tiene grabación, intentar iniciarla
                if (in_array($twilioCall->status, ['in-progress', 'ringing']) && !$twilioCall->recordingSid) {
                    $this->warn("Llamada {$call->call_sid} está activa pero sin grabación. Iniciando grabación...");

                    try {
                        $recording = $twilio->calls($call->call_sid)->recordings->create([
                            'recordingChannels' => 'dual',
                            'recordingStatusCallback' => route('twilio.recording'),
                            'recordingStatusCallbackMethod' => 'POST'
                        ]);

                        $call->update(['recording_sid' => $recording->sid]);

                        $this->info("✅ Grabación iniciada: {$recording->sid}");
                        $recordingStartedCount++;

                        Log::info('Recording manually started for active call', [
                            'call_sid' => $call->call_sid,
                            'recording_sid' => $recording->sid
                        ]);

                    } catch (\Exception $recordingError) {
                        $this->error("❌ Error iniciando grabación para {$call->call_sid}: {$recordingError->getMessage()}");

                        Log::error('Failed to start recording for call', [
                            'call_sid' => $call->call_sid,
                            'error' => $recordingError->getMessage()
                        ]);
                    }
                } elseif ($twilioCall->recordingSid) {
                    // La llamada ya tiene grabación, actualizar registro local
                    $call->update(['recording_sid' => $twilioCall->recordingSid]);
                    $this->info("✅ Llamada {$call->call_sid} ya tiene grabación: {$twilioCall->recordingSid}");
                }

                $processedCount++;

            } catch (\Exception $e) {
                $this->error("Error procesando llamada {$call->call_sid}: {$e->getMessage()}");

                Log::error('Error processing call in ensure recordings command', [
                    'call_sid' => $call->call_sid,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("\n📊 Resumen:");
        $this->info("- Llamadas procesadas: {$processedCount}");
        $this->info("- Grabaciones iniciadas: {$recordingStartedCount}");

        if ($recordingStartedCount > 0) {
            $this->info("✅ Se iniciaron {$recordingStartedCount} grabaciones manualmente.");
        }

        return 0;
    }
}
