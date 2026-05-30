<?php

// app/Jobs/EnviarNotificacionFCMJob.php

namespace App\Jobs;

use App\Models\DispositivoMovil;
use App\Models\NotificacionPush;
use App\Services\FCMService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job que envía una notificación push a través de Firebase Cloud Messaging.
 *
 * Reintentos: 3 intentos con backoff exponencial (60s, 120s, 240s).
 * Interruptor: si FCM_ENABLED=false, marca la notificación como omitida
 * sin llamar a Firebase.
 *
 * El modelo NotificacionPush actúa como log inmutable de lo que se envió
 * o intentó — no se elimina, se actualiza el estado.
 */
class EnviarNotificacionFCMJob implements ShouldQueue
{
    use Queueable;

    public int $tries  = 3;
    public int $backoff = 60;

    /**
     * @param  NotificacionPush  $notificacion  Notificación a enviar.
     */
    public function __construct(public readonly NotificacionPush $notificacion)
    {
    }

    /**
     * Envía la notificación push a todos los dispositivos activos del usuario.
     *
     * FCMService se resuelve de forma lazy para evitar inicializar Firebase
     * cuando FCM_ENABLED=false (sin credenciales disponibles).
     *
     * @return void
     */
    public function handle(): void
    {
        if (! config('firebase.fcm_enabled', false)) {
            $this->notificacion->update(['omitida' => true]);
            Log::info('FCM omitida (FCM_ENABLED=false).', [
                'notificacion_id' => $this->notificacion->id,
                'tipo'            => $this->notificacion->tipo,
            ]);
            return;
        }

        $tokens = DispositivoMovil::where('user_id', $this->notificacion->user_id)
            ->where('activo', true)
            ->pluck('token_fcm');

        if ($tokens->isEmpty()) {
            // Sin dispositivos registrados: marcar como enviada (éxito vacío)
            $this->notificacion->update(['enviada' => true, 'enviada_en' => now()]);
            return;
        }

        /** @var FCMService $fcm */
        $fcm     = app(FCMService::class);
        $payload = $this->notificacion->payload;
        $titulo  = $payload['titulo'] ?? 'SIMETSA';
        $cuerpo  = $payload['cuerpo'] ?? '';
        $datos   = $payload['datos'] ?? [];

        foreach ($tokens as $token) {
            $fcm->enviar($token, $titulo, $cuerpo, $datos);
        }

        $this->notificacion->update(['enviada' => true, 'enviada_en' => now()]);
    }

    /**
     * Se llama cuando el job falla definitivamente (todos los reintentos agotados).
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        $this->notificacion->update([
            'fallida_en'   => now(),
            'ultimo_error' => mb_substr($exception->getMessage(), 0, 500),
        ]);

        Log::error('EnviarNotificacionFCMJob: falló definitivamente.', [
            'notificacion_id' => $this->notificacion->id,
            'error'           => $exception->getMessage(),
        ]);
    }
}
