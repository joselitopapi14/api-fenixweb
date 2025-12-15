<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificacionCiudadanoService;

class TestNotificacionCommand extends Command
{
    protected $signature = 'test:notificacion';
    protected $description = 'Probar la configuración de notificaciones';

    public function handle()
    {
        $this->info('=== PRUEBA DE CONFIGURACIÓN DE NOTIFICACIONES ===');

        // Probar instanciación del servicio
        try {
            $service = new NotificacionCiudadanoService();
            $this->info('✅ Servicio instanciado correctamente');
        } catch (\Exception $e) {
            $this->error('❌ Error al instanciar servicio: ' . $e->getMessage());
            return 1;
        }

        // Verificar configuraciones
        $this->info('📋 Configuraciones leídas:');
        $this->line('TWILIO_SID: ' . (env('TWILIO_SID') ? 'Configurado' : 'No configurado'));
        $this->line('TWILIO_AUTH_TOKEN: ' . (env('TWILIO_AUTH_TOKEN') ? 'Configurado' : 'No configurado'));
        $this->line('TWILIO_PHONE_NUMBER (Llamadas): ' . env('TWILIO_PHONE_NUMBER'));
        $this->line('TWILIO_PHONE_MESSAGE (SMS): ' . env('TWILIO_PHONE_MESSAGE'));
        $this->line('MAILJET_API_KEY: ' . (env('MAILJET_API_KEY') ? 'Configurado' : 'No configurado'));
        $this->line('MAILJET_SECRET_KEY: ' . (env('MAILJET_SECRET_KEY') ? 'Configurado' : 'No configurado'));
        $this->line('MAILJET_EMAIL: ' . env('MAILJET_EMAIL'));

        $this->info('✅ Prueba completada. Revisar logs para detalles adicionales.');
        return 0;
    }
}
