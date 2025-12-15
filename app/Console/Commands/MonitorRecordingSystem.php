<?php

namespace App\Console\Commands;

use App\Services\RecordingMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorRecordingSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recordings:monitor {--detailed : Mostrar métricas detalladas} {--json : Salida en formato JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor del sistema automático de grabaciones con métricas en tiempo real';

    protected RecordingMonitoringService $monitoringService;

    /**
     * Create a new command instance.
     */
    public function __construct(RecordingMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $detailed = $this->option('detailed');
        $jsonOutput = $this->option('json');

        try {
            $this->info("🔍 OBTENIENDO MÉTRICAS DEL SISTEMA DE GRABACIONES...\n");

            // Obtener métricas en tiempo real
            $metrics = $this->monitoringService->getRealtimeMetrics();

            if ($jsonOutput) {
                $this->line(json_encode($metrics, JSON_PRETTY_PRINT));
                return 0;
            }

            // Mostrar dashboard en consola
            $this->displayDashboard($metrics, $detailed);

            // Registrar métricas en logs
            $this->monitoringService->logPeriodicMetrics();

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error obteniendo métricas: {$e->getMessage()}");
            Log::error('Error en comando de monitoreo de grabaciones', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Mostrar dashboard visual en consola
     */
    private function displayDashboard(array $metrics, bool $detailed): void
    {
        $this->displayHeader();
        $this->displaySystemStatus($metrics['system_status']);
        $this->displayHealthIndicators($metrics['health_indicators']);

        if ($detailed) {
            $this->displayCallStatistics($metrics['call_statistics']);
            $this->displayRecordingStatistics($metrics['recording_statistics']);
            $this->displayStorageMetrics($metrics['storage_metrics']);
            $this->displayRecentActivity($metrics['recent_activity']);
        }

        $this->displayFooter($metrics['timestamp']);
    }

    private function displayHeader(): void
    {
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🎙️  SISTEMA AUTOMÁTICO DE GRABACIONES - DASHBOARD EN TIEMPO REAL");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    }

    private function displaySystemStatus(array $status): void
    {
        $statusIcon = match($status['status']) {
            'excellent' => '🟢',
            'good' => '🟡',
            'warning' => '🟠',
            'critical' => '🔴',
            default => '⚪'
        };

        $this->info("\n📊 ESTADO GENERAL DEL SISTEMA");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("Estado: {$statusIcon} " . strtoupper($status['status']));
        $this->line("Total de llamadas: {$status['total_calls']}");
        $this->line("Llamadas con grabación: {$status['calls_with_recordings']}");
        $this->line("Tasa de éxito: {$status['success_rate_percent']}%");
    }

    private function displayHealthIndicators(array $health): void
    {
        $healthIcon = match($health['overall_health']) {
            'excellent' => '💚',
            'good' => '💛',
            'warning' => '🧡',
            'critical' => '❤️',
            default => '🤍'
        };

        $this->info("\n🏥 INDICADORES DE SALUD");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("Salud general: {$healthIcon} " . strtoupper($health['overall_health']));
        $this->line("Éxito reciente (24h): {$health['recent_success_rate']}%");

        if ($health['calls_without_recordings'] > 0) {
            $this->line("⚠️  Sin grabación: {$health['calls_without_recordings']} llamadas");
        }

        if ($health['calls_with_missing_files'] > 0) {
            $this->line("📁 Archivos faltantes: {$health['calls_with_missing_files']}");
        }

        if ($health['webhook_health']['health_status'] !== 'healthy') {
            $this->line("🔗 Webhooks: " . $health['webhook_health']['health_status']);
        }

        $this->info("\n📋 RECOMENDACIONES:");
        foreach ($health['recommendations'] as $recommendation) {
            $this->line("• {$recommendation}");
        }
    }

    private function displayCallStatistics(array $stats): void
    {
        $this->info("\n📞 ESTADÍSTICAS DE LLAMADAS");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        // Hoy
        $todayTotal = $stats['today']['total_calls'];
        $todayRecorded = $stats['today']['with_recordings'];
        $todayRate = $todayTotal > 0 ? round(($todayRecorded / $todayTotal) * 100, 1) : 0;
        $avgDuration = round($stats['today']['avg_duration'], 0);

        $this->line("📅 HOY:");
        $this->line("  Total: {$todayTotal} | Con grabación: {$todayRecorded} | Tasa: {$todayRate}%");
        $this->line("  Duración promedio: {$avgDuration} segundos");

        // Esta semana
        $weekTotal = $stats['this_week']['total_calls'];
        $weekRecorded = $stats['this_week']['with_recordings'];
        $weekRate = $weekTotal > 0 ? round(($weekRecorded / $weekTotal) * 100, 1) : 0;

        $this->line("\n📅 ESTA SEMANA:");
        $this->line("  Total: {$weekTotal} | Con grabación: {$weekRecorded} | Tasa: {$weekRate}%");

        // Este mes
        $monthTotal = $stats['this_month']['total_calls'];
        $monthRecorded = $stats['this_month']['with_recordings'];
        $monthRate = $monthTotal > 0 ? round(($monthRecorded / $monthTotal) * 100, 1) : 0;

        $this->line("\n📅 ESTE MES:");
        $this->line("  Total: {$monthTotal} | Con grabación: {$monthRecorded} | Tasa: {$monthRate}%");
    }

    private function displayRecordingStatistics(array $stats): void
    {
        $this->info("\n🎵 ESTADÍSTICAS DE GRABACIONES");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $this->line("📅 ÚLTIMAS 24 HORAS:");
        $this->line("  Grabaciones creadas: {$stats['last_24_hours']['recordings_created']}");
        $this->line("  Grabaciones fallidas: {$stats['last_24_hours']['failed_recordings']}");
        $this->line("  Tamaño promedio: {$stats['last_24_hours']['average_file_size_mb']} MB");

        $this->line("\n⏰ ÚLTIMA HORA:");
        $this->line("  Grabaciones creadas: {$stats['last_hour']['recordings_created']}");
        $this->line("  Actividad webhooks: {$stats['last_hour']['webhook_responses']}");

        $this->line("\n💾 ALMACENAMIENTO TOTAL:");
        $this->line("  Espacio usado: {$stats['total_storage_used_mb']} MB");
    }

    private function displayStorageMetrics(array $storage): void
    {
        $this->info("\n💾 MÉTRICAS DE ALMACENAMIENTO");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $this->line("Total de archivos: {$storage['total_files']}");
        $this->line("Espacio total usado: {$storage['total_size_mb']} MB");
        $this->line("Tamaño promedio por archivo: {$storage['average_file_size_mb']} MB");
        $this->line("Directorio: {$storage['storage_directory']}");

        if ($storage['oldest_file_date']) {
            $oldestDate = \Carbon\Carbon::parse($storage['oldest_file_date'])->format('Y-m-d H:i:s');
            $this->line("Archivo más antiguo: {$oldestDate}");
        }

        if ($storage['newest_file_date']) {
            $newestDate = \Carbon\Carbon::parse($storage['newest_file_date'])->format('Y-m-d H:i:s');
            $this->line("Archivo más reciente: {$newestDate}");
        }
    }

    private function displayRecentActivity(array $activity): void
    {
        $this->info("\n⚡ ACTIVIDAD RECIENTE (Últimas 10 llamadas)");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        if (empty($activity)) {
            $this->line("No hay actividad reciente.");
            return;
        }

        foreach (array_slice($activity, 0, 5) as $call) { // Mostrar solo las 5 más recientes para no saturar
            $statusIcon = match($call['status']) {
                'completed' => '✅',
                'in-progress' => '🔄',
                'failed' => '❌',
                default => '⚪'
            };

            $recordingIcon = $call['has_audio'] && $call['audio_exists'] ? '🎵' : '❌';
            $duration = $call['duration_seconds'] ? "{$call['duration_seconds']}s" : 'N/A';
            $updatedAt = \Carbon\Carbon::parse($call['updated_at'])->format('H:i:s');

            $this->line("{$statusIcon} {$recordingIcon} ID:{$call['id']} | {$call['citizen_name']} | {$duration} | {$updatedAt}");
        }
    }

    private function displayFooter(string $timestamp): void
    {
        $formattedTime = \Carbon\Carbon::parse($timestamp)->format('Y-m-d H:i:s');

        $this->info("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("⏰ Última actualización: {$formattedTime}");
        $this->info("🔄 Para métricas detalladas: php artisan recordings:monitor --detailed");
        $this->info("📊 Para salida JSON: php artisan recordings:monitor --json");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    }
}
