<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// SISTEMA AUTOMÁTICO DE GRABACIONES - PROGRAMACIÓN DE COMANDOS
Schedule::command('calls:auto-recovery --hours=2')
    ->everyFifteenMinutes()
    ->withoutOverlapping(10) // No ejecutar si ya hay uno corriendo, timeout 10 min
    ->runInBackground()
    ->description('Verificación automática de grabaciones cada 15 minutos');

Schedule::command('calls:auto-recovery --hours=6 --force')
    ->hourly()
    ->withoutOverlapping(30)
    ->runInBackground()
    ->description('Verificación exhaustiva cada hora');

Schedule::command('calls:auto-recovery --hours=24')
    ->daily('03:00') // 3 AM
    ->withoutOverlapping(60)
    ->description('Verificación completa diaria a las 3 AM');

// Comando para recuperar grabaciones específicas que fallaron
Schedule::command('calls:recover-recordings --days=1')
    ->daily('04:00') // 4 AM
    ->withoutOverlapping(60)
    ->description('Recuperación masiva diaria a las 4 AM');

// Monitoreo y logs del sistema de grabaciones
Schedule::command('recordings:monitor')
    ->everyFiveMinutes()
    ->runInBackground()
    ->description('Monitoreo continuo del sistema de grabaciones');

Schedule::command('recordings:monitor --detailed')
    ->hourly()
    ->runInBackground()
    ->description('Reporte detallado cada hora del sistema de grabaciones');
