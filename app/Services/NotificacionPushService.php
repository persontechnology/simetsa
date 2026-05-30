<?php

// app/Services/NotificacionPushService.php

namespace App\Services;

use App\Jobs\EnviarNotificacionFCMJob;
use App\Models\NotificacionPush;
use App\Models\Ticket;
use App\Models\User;

/**
 * Servicio de notificaciones push.
 *
 * Fase 5: encola la intención de notificación.
 * Fase 6: persiste en NotificacionPush y despacha EnviarNotificacionFCMJob.
 *
 * La firma pública de encolar() no cambia — compatibilidad total con callers existentes.
 */
class NotificacionPushService
{
    /**
     * Encola una notificación push y despacha el Job de envío FCM.
     *
     * @param  User         $user          Usuario destinatario.
     * @param  string       $tipo          Constante de NotificacionPush::TIPO_*.
     * @param  array        $payload       Datos a enviar al dispositivo (titulo, cuerpo, datos).
     * @param  Ticket|null  $ticket        Ticket relacionado (opcional).
     * @param  int          $minutosDesde  Minutos de delay desde ahora (0 = inmediato).
     * @return NotificacionPush
     */
    public function encolar(
        User $user,
        string $tipo,
        array $payload,
        ?Ticket $ticket = null,
        int $minutosDesde = 0
    ): NotificacionPush {
        $notificacion = NotificacionPush::create([
            'user_id'         => $user->id,
            'ticket_id'       => $ticket?->id,
            'tipo'            => $tipo,
            'payload'         => $payload,
            'programado_para' => now()->addMinutes($minutosDesde),
            'enviada'         => false,
            'omitida'         => false,
        ]);

        $job = new EnviarNotificacionFCMJob($notificacion);

        if ($minutosDesde > 0) {
            dispatch($job)->delay(now()->addMinutes($minutosDesde));
        } else {
            dispatch($job);
        }

        return $notificacion;
    }

    /**
     * Marca una notificación como enviada manualmente (fallback o reintento externo).
     *
     * @param  NotificacionPush  $notificacion
     * @return NotificacionPush
     */
    public function marcarEnviada(NotificacionPush $notificacion): NotificacionPush
    {
        $notificacion->update([
            'enviada'    => true,
            'enviada_en' => now(),
        ]);

        return $notificacion->fresh();
    }
}
