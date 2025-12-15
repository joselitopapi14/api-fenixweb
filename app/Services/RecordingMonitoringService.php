<?php

namespace App\Services;

use App\Models\Call;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RecordingMonitoringService
{
    /**
     * Canal de logs específico para grabaciones
     */
    const LOG_CHANNEL = 'recordings';

    /**
     * Métricas en tiempo real del sistema de grabaciones
     */
    public function getRealtimeMetrics(): array
    {
        $cacheKey = 'recording_metrics_' . now()->format('Y-m-d-H-i');

        return Cache::remember($cacheKey, 300, function () { // Cache por 5 minutos
            return [
                'timestamp' => now()->toISOString(),
                'system_status' => $this->getSystemStatus(),
                'call_statistics' => $this->getCallStatistics(),
                'recording_statistics' => $this->getRecordingStatistics(),
                'storage_metrics' => $this->getStorageMetrics(),
                'recent_activity' => $this->getRecentActivity(),
                'health_indicators' => $this->getHealthIndicators()
            ];
        });
    }

    /**
     * Estado general del sistema de grabaciones
     */
    public function getSystemStatus(): array
    {
        $totalCalls = Call::count();
        $callsWithRecordings = Call::whereNotNull('recording_sid')
                                 ->whereNotNull('audio_path')
                                 ->count();

        $successRate = $totalCalls > 0 ? round(($callsWithRecordings / $totalCalls) * 100, 2) : 0;

        return [
            'total_calls' => $totalCalls,
            'calls_with_recordings' => $callsWithRecordings,
            'success_rate_percent' => $successRate,
            'status' => $this->determineSystemHealth($successRate),
            'last_updated' => now()->toISOString()
        ];
    }

    /**
     * Estadísticas de llamadas
     */
    public function getCallStatistics(): array
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            'today' => [
                'total_calls' => Call::where('created_at', '>=', $today)->count(),
                'completed_calls' => Call::where('created_at', '>=', $today)
                                       ->where('status', 'completed')->count(),
                'with_recordings' => Call::where('created_at', '>=', $today)
                                        ->whereNotNull('recording_sid')->count(),
                'avg_duration' => Call::where('created_at', '>=', $today)
                                     ->where('status', 'completed')
                                     ->avg('duration_seconds') ?: 0
            ],
            'this_week' => [
                'total_calls' => Call::where('created_at', '>=', $thisWeek)->count(),
                'with_recordings' => Call::where('created_at', '>=', $thisWeek)
                                        ->whereNotNull('recording_sid')->count()
            ],
            'this_month' => [
                'total_calls' => Call::where('created_at', '>=', $thisMonth)->count(),
                'with_recordings' => Call::where('created_at', '>=', $thisMonth)
                                        ->whereNotNull('recording_sid')->count()
            ]
        ];
    }

    /**
     * Estadísticas específicas de grabaciones
     */
    public function getRecordingStatistics(): array
    {
        $last24Hours = now()->subHours(24);
        $lastHour = now()->subHour();

        return [
            'last_24_hours' => [
                'recordings_created' => Call::where('updated_at', '>=', $last24Hours)
                                          ->whereNotNull('recording_sid')
                                          ->whereNotNull('audio_path')
                                          ->count(),
                'failed_recordings' => Call::where('updated_at', '>=', $last24Hours)
                                         ->where('status', 'failed')
                                         ->count(),
                'average_file_size_mb' => $this->getAverageFileSize($last24Hours)
            ],
            'last_hour' => [
                'recordings_created' => Call::where('updated_at', '>=', $lastHour)
                                          ->whereNotNull('recording_sid')
                                          ->whereNotNull('audio_path')
                                          ->count(),
                'webhook_responses' => $this->getWebhookActivityCount($lastHour)
            ],
            'total_storage_used_mb' => $this->getTotalStorageUsed()
        ];
    }

    /**
     * Métricas de almacenamiento
     */
    public function getStorageMetrics(): array
    {
        $recordingsPath = 'recordings';
        $totalFiles = 0;
        $totalSizeMB = 0;
        $oldestFile = null;
        $newestFile = null;

        if (Storage::exists($recordingsPath)) {
            $files = Storage::files($recordingsPath);
            $totalFiles = count($files);

            foreach ($files as $file) {
                $sizeMB = Storage::size($file) / 1024 / 1024;
                $totalSizeMB += $sizeMB;

                $lastModified = Storage::lastModified($file);

                if (!$oldestFile || $lastModified < $oldestFile) {
                    $oldestFile = $lastModified;
                }

                if (!$newestFile || $lastModified > $newestFile) {
                    $newestFile = $lastModified;
                }
            }
        }

        return [
            'total_files' => $totalFiles,
            'total_size_mb' => round($totalSizeMB, 2),
            'average_file_size_mb' => $totalFiles > 0 ? round($totalSizeMB / $totalFiles, 2) : 0,
            'oldest_file_date' => $oldestFile ? Carbon::createFromTimestamp($oldestFile)->toISOString() : null,
            'newest_file_date' => $newestFile ? Carbon::createFromTimestamp($newestFile)->toISOString() : null,
            'storage_directory' => $recordingsPath
        ];
    }

    /**
     * Actividad reciente del sistema
     */
    public function getRecentActivity(): array
    {
        $recentCalls = Call::with(['ciudadano', 'user'])
                         ->orderBy('updated_at', 'desc')
                         ->limit(10)
                         ->get()
                         ->map(function ($call) {
                             return [
                                 'id' => $call->id,
                                 'call_sid' => $call->call_sid,
                                 'recording_sid' => $call->recording_sid,
                                 'status' => $call->status,
                                 'duration_seconds' => $call->duration_seconds,
                                 'has_audio' => !empty($call->audio_path),
                                 'audio_exists' => $call->audio_path ? Storage::exists($call->audio_path) : false,
                                 'citizen_name' => $call->ciudadano->nombre_completo ?? 'N/A',
                                 'user_name' => $call->user->name ?? 'N/A',
                                 'updated_at' => $call->updated_at->toISOString()
                             ];
                         });

        return $recentCalls->toArray();
    }

    /**
     * Indicadores de salud del sistema
     */
    public function getHealthIndicators(): array
    {
        $last24Hours = now()->subHours(24);

        // Tasa de éxito en las últimas 24 horas
        $recentCalls = Call::where('created_at', '>=', $last24Hours)->count();
        $recentSuccessful = Call::where('created_at', '>=', $last24Hours)
                              ->whereNotNull('recording_sid')
                              ->whereNotNull('audio_path')
                              ->count();

        $recentSuccessRate = $recentCalls > 0 ? ($recentSuccessful / $recentCalls) * 100 : 0;

        // Llamadas problemáticas
        $callsWithoutRecordings = Call::where('status', 'completed')
                                    ->where('created_at', '>=', $last24Hours)
                                    ->whereNull('recording_sid')
                                    ->count();

        $callsWithMissingFiles = Call::where('created_at', '>=', $last24Hours)
                                   ->whereNotNull('recording_sid')
                                   ->whereNotNull('audio_path')
                                   ->get()
                                   ->filter(function($call) {
                                       return !Storage::exists($call->audio_path);
                                   })
                                   ->count();

        // Webhook health
        $webhookIssues = $this->detectWebhookIssues();

        return [
            'overall_health' => $this->calculateOverallHealth($recentSuccessRate, $callsWithoutRecordings, $callsWithMissingFiles),
            'recent_success_rate' => round($recentSuccessRate, 2),
            'calls_without_recordings' => $callsWithoutRecordings,
            'calls_with_missing_files' => $callsWithMissingFiles,
            'webhook_health' => $webhookIssues,
            'recommendations' => $this->generateRecommendations($recentSuccessRate, $callsWithoutRecordings, $callsWithMissingFiles)
        ];
    }

    /**
     * Registrar evento del sistema de grabaciones
     */
    public function logRecordingEvent(string $eventType, array $data): void
    {
        $logData = array_merge([
            'event_type' => $eventType,
            'timestamp' => now()->toISOString(),
            'system' => 'auto_recording'
        ], $data);

        Log::channel(self::LOG_CHANNEL)->info("🎙️ RECORDING_EVENT: {$eventType}", $logData);
    }

    /**
     * Registrar métricas periódicas
     */
    public function logPeriodicMetrics(): void
    {
        $metrics = $this->getRealtimeMetrics();

        Log::channel(self::LOG_CHANNEL)->info('📊 PERIODIC_METRICS', $metrics);

        // También registrar en canal principal si hay problemas
        if ($metrics['health_indicators']['overall_health'] !== 'healthy') {
            Log::warning('⚠️ RECORDING SYSTEM HEALTH ISSUE', [
                'health_status' => $metrics['health_indicators']['overall_health'],
                'success_rate' => $metrics['health_indicators']['recent_success_rate'],
                'issues' => $metrics['health_indicators']['recommendations']
            ]);
        }
    }

    /**
     * Helpers privados
     */
    private function determineSystemHealth(float $successRate): string
    {
        if ($successRate >= 95) return 'excellent';
        if ($successRate >= 85) return 'good';
        if ($successRate >= 70) return 'warning';
        return 'critical';
    }

    private function getAverageFileSize(Carbon $since): float
    {
        $calls = Call::where('updated_at', '>=', $since)
                   ->whereNotNull('audio_path')
                   ->get();

        if ($calls->isEmpty()) return 0;

        $totalSize = 0;
        $validFiles = 0;

        foreach ($calls as $call) {
            if (Storage::exists($call->audio_path)) {
                $totalSize += Storage::size($call->audio_path);
                $validFiles++;
            }
        }

        return $validFiles > 0 ? round(($totalSize / $validFiles) / 1024 / 1024, 2) : 0;
    }

    private function getTotalStorageUsed(): float
    {
        $totalSize = 0;
        $files = Storage::files('recordings');

        foreach ($files as $file) {
            $totalSize += Storage::size($file);
        }

        return round($totalSize / 1024 / 1024, 2);
    }

    private function getWebhookActivityCount(Carbon $since): int
    {
        // Esto se podría mejorar con una tabla de logs específica para webhooks
        // Por ahora, estimamos basado en llamadas actualizadas recientemente
        return Call::where('updated_at', '>=', $since)
                 ->whereNotNull('recording_sid')
                 ->count();
    }

    private function detectWebhookIssues(): array
    {
        $lastHour = now()->subHour();

        // Detectar patrones problemáticos
        $stuckInProgress = Call::where('status', 'in-progress')
                             ->where('created_at', '<', now()->subMinutes(30))
                             ->count();

        $recentFailed = Call::where('status', 'failed')
                          ->where('updated_at', '>=', $lastHour)
                          ->count();

        return [
            'stuck_in_progress' => $stuckInProgress,
            'recent_failures' => $recentFailed,
            'health_status' => ($stuckInProgress > 5 || $recentFailed > 10) ? 'issues_detected' : 'healthy'
        ];
    }

    private function calculateOverallHealth(float $successRate, int $missingRecordings, int $missingFiles): string
    {
        if ($successRate >= 95 && $missingRecordings === 0 && $missingFiles === 0) {
            return 'excellent';
        }

        if ($successRate >= 85 && $missingRecordings <= 2 && $missingFiles <= 1) {
            return 'good';
        }

        if ($successRate >= 70 && $missingRecordings <= 5 && $missingFiles <= 3) {
            return 'warning';
        }

        return 'critical';
    }

    private function generateRecommendations(float $successRate, int $missingRecordings, int $missingFiles): array
    {
        $recommendations = [];

        if ($successRate < 85) {
            $recommendations[] = "Tasa de éxito baja ({$successRate}%). Verificar webhooks de Twilio.";
        }

        if ($missingRecordings > 0) {
            $recommendations[] = "{$missingRecordings} llamadas sin grabación. Ejecutar comando de recuperación.";
        }

        if ($missingFiles > 0) {
            $recommendations[] = "{$missingFiles} archivos faltantes. Verificar integridad del storage.";
        }

        if (empty($recommendations)) {
            $recommendations[] = "Sistema funcionando correctamente.";
        }

        return $recommendations;
    }
}
