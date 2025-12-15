<?php

namespace App\Services;

use App\Models\TipoNotificacion;
use App\Models\Lider;
use App\Models\Novedad;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Exception;

class NotificacionService
{
    private $twilioClient;
    private $fromNumber;

    public function __construct()
    {
        $this->twilioClient = new Client(
            env('TWILIO_SID'),
            env('TWILIO_AUTH_TOKEN')
        );
        $this->fromNumber = env('TWILIO_PHONE_MESSAGE');
    }

    /**
     * Enviar notificación SMS por nueva novedad
     */
    public function notificarNuevaNovedad(Novedad $novedad): void
    {
        try {
            // Verificar si el tipo de notificación SMS está activo
            $tipoSms = TipoNotificacion::where('id', 1)->where('activo', true)->first();

            if (!$tipoSms) {
                Log::info('Notificación SMS deshabilitada - Tipo ID 1 no está activo');
                return;
            }

            // Cargar relaciones necesarias
            $novedad->load(['ciudadano', 'liderOriginal', 'liderActual']);

            if (!$novedad->liderOriginal) {
                Log::warning('No se puede enviar SMS - Líder original no encontrado', [
                    'novedad_id' => $novedad->id
                ]);
                return;
            }

            // Verificar que el líder tenga teléfono
            if (!$novedad->liderOriginal->telefono) {
                Log::warning('No se puede enviar SMS - Líder sin teléfono', [
                    'novedad_id' => $novedad->id,
                    'lider_id' => $novedad->liderOriginal->id,
                    'lider_nombre' => $novedad->liderOriginal->nombre
                ]);
                return;
            }

            // Crear mensaje personalizado según el tipo de novedad
            $mensaje = $this->crearMensajeNovedad($novedad);

            // Formatear número de teléfono (agregar +57 si no lo tiene)
            $numeroDestino = $this->formatearNumeroTelefono($novedad->liderOriginal->telefono);

            // Enviar SMS
            $this->enviarSMS($numeroDestino, $mensaje, $novedad);

        } catch (Exception $e) {
            Log::error('Error al enviar notificación SMS por nueva novedad', [
                'novedad_id' => $novedad->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Crear mensaje personalizado según el tipo de novedad
     */
    private function crearMensajeNovedad(Novedad $novedad): string
    {
        $ciudadano = $novedad->ciudadano;
        $liderActual = $novedad->liderActual;

        // Determinar tipo de novedad
        if (str_contains($novedad->descripcion, 'Doble digitación')) {
            return "🔔 Nueva Novedad: Doble digitación detectada para {$ciudadano->nombre_completo} (CC: {$ciudadano->cedula}). Revisa el sistema para más detalles.";
        } elseif (str_contains($novedad->descripcion, 'Restricción geográfica')) {
            return "🔔 Nueva Novedad: Restricción geográfica para {$ciudadano->nombre_completo} (CC: {$ciudadano->cedula}). Requiere autorización especial.";
        } else {
            // Conflicto de liderazgo
            return "🔔 Nueva Novedad: Conflicto de liderazgo para {$ciudadano->nombre_completo} (CC: {$ciudadano->cedula}). Otro líder ({$liderActual->nombre}) también intenta registrarlo.";
        }
    }

    /**
     * Formatear número de teléfono para Colombia
     */
    private function formatearNumeroTelefono(string $telefono): string
    {
        // Remover espacios y caracteres especiales
        $telefono = preg_replace('/[^0-9]/', '', $telefono);

        // Si ya tiene código de país, devolverlo tal como está
        if (str_starts_with($telefono, '57') && strlen($telefono) == 12) {
            return '+' . $telefono;
        }

        // Si no tiene código de país, agregarlo (+57 para Colombia)
        if (strlen($telefono) == 10) {
            return '+57' . $telefono;
        }

        // Si tiene el formato con 1 al inicio (celular), mantenerlo
        if (str_starts_with($telefono, '1') && strlen($telefono) == 11) {
            return '+57' . $telefono;
        }

        // Por defecto, agregar +57
        return '+57' . $telefono;
    }

    /**
     * Enviar SMS usando Twilio
     */
    private function enviarSMS(string $numeroDestino, string $mensaje, Novedad $novedad): void
    {
        try {
            $message = $this->twilioClient->messages->create(
                $numeroDestino,
                [
                    'from' => $this->fromNumber,
                    'body' => $mensaje
                ]
            );

            Log::info('SMS enviado exitosamente', [
                'novedad_id' => $novedad->id,
                'lider_id' => $novedad->lider_original_id,
                'lider_nombre' => $novedad->liderOriginal->nombre,
                'numero_destino' => $numeroDestino,
                'numero_origen' => $this->fromNumber,
                'message_sid' => $message->sid,
                'mensaje' => $mensaje
            ]);

        } catch (Exception $e) {
            Log::error('Error al enviar SMS con Twilio', [
                'novedad_id' => $novedad->id,
                'numero_destino' => $numeroDestino,
                'numero_origen' => $this->fromNumber,
                'mensaje' => $mensaje,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Probar conexión con Twilio
     */
    public function probarConexion(): array
    {
        try {
            $account = $this->twilioClient->api->v2010->accounts(config('services.twilio.sid'))->fetch();

            return [
                'success' => true,
                'account_sid' => $account->sid,
                'friendly_name' => $account->friendlyName,
                'status' => $account->status
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
