<?php

// app/Services/FCMService.php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use RuntimeException;

/**
 * Envía notificaciones push a dispositivos móviles vía Firebase Cloud Messaging.
 *
 * Requiere: kreait/laravel-firebase con FIREBASE_CREDENTIALS configurado.
 * El interruptor FCM_ENABLED lo maneja EnviarNotificacionFCMJob (no este servicio).
 */
class FCMService
{
    public function __construct(private readonly Messaging $messaging)
    {
    }

    /**
     * Envía una notificación push a un token FCM específico.
     *
     * @param  string  $tokenFcm   Token FCM del dispositivo destino.
     * @param  string  $titulo     Título de la notificación.
     * @param  string  $cuerpo     Cuerpo del mensaje.
     * @param  array   $datos      Datos adicionales (data payload).
     * @return void
     *
     * @throws RuntimeException  Si Firebase rechaza el mensaje.
     */
    public function enviar(string $tokenFcm, string $titulo, string $cuerpo, array $datos = []): void
    {
        try {
            $mensaje = CloudMessage::withTarget('token', $tokenFcm)
                ->withNotification(Notification::create($titulo, $cuerpo))
                ->withData($datos);

            $this->messaging->send($mensaje);
        } catch (\Throwable $e) {
            Log::error('FCMService: error al enviar notificación.', [
                'token'   => substr($tokenFcm, 0, 20) . '...',
                'titulo'  => $titulo,
                'error'   => $e->getMessage(),
            ]);

            throw new RuntimeException(
                'Error al enviar notificación FCM: ' . $e->getMessage(),
                previous: $e
            );
        }
    }
}
