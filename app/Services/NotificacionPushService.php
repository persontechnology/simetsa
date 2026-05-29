<?php

// app/Services/NotificacionPushService.php

namespace App\Services;

use App\Models\NotificacionPush;
use App\Models\Ticket;
use App\Models\User;

/**
 * Cola lógica de notificaciones push.
 *
 * En Fase 5, las notificaciones se encolan pero NO se envían.
 * El envío real a Firebase Cloud Messaging se activa en Fase 6.
 */
class NotificacionPushService
{
    /**
     * Encola una notificación push para un usuario.
     *
     * @param  User         $user
     * @param  string       $tipo      Constante de NotificacionPush::TIPO_*
     * @param  array        $payload   Datos a enviar al dispositivo.
     * @param  Ticket|null  $ticket    Ticket relacionado (opcional).
     * @param  int          $minutosDesde Minutos desde ahora para programar el envío (0 = inmediato).
     * @return NotificacionPush
     */
    public function encolar(
        User $user,
        string $tipo,
        array $payload,
        ?Ticket $ticket = null,
        int $minutosDesde = 0
    ): NotificacionPush {
        return NotificacionPush::create([
            'user_id'         => $user->id,
            'ticket_id'       => $ticket?->id,
            'tipo'            => $tipo,
            'payload'         => $payload,
            'programado_para' => now()->addMinutes($minutosDesde),
            'enviada'         => false,
        ]);
    }

    /**
     * Marca una notificación como enviada.
     *
     * A usar en Fase 6 cuando FCM esté integrado.
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
