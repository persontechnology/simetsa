<?php

// app/Models/NotificacionPush.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log inmutable de notificaciones push enviadas o intentadas.
 *
 * Fase 5: encola intenciones de notificación.
 * Fase 6: EnviarNotificacionFCMJob consume la cola y actualiza el estado.
 *
 * @property int                 $id
 * @property int                 $user_id
 * @property int|null            $ticket_id
 * @property string              $tipo
 * @property array               $payload
 * @property \Carbon\Carbon      $programado_para
 * @property bool                $enviada
 * @property \Carbon\Carbon|null $enviada_en
 * @property \Carbon\Carbon|null $fallida_en
 * @property string|null         $ultimo_error
 * @property bool                $omitida
 */
class NotificacionPush extends Model
{
    use HasFactory;

    protected $table = 'notificaciones_push';

    public const TIPO_EXPIRA_PRONTO = 'ticket_expira_pronto';
    public const TIPO_EXPIRADO      = 'ticket_expirado';
    public const TIPO_ANULADO       = 'ticket_anulado';

    protected $fillable = [
        'user_id', 'ticket_id', 'tipo', 'payload',
        'programado_para', 'enviada', 'enviada_en',
        'fallida_en', 'ultimo_error', 'omitida',
    ];

    protected $casts = [
        'payload'         => 'array',
        'programado_para' => 'datetime',
        'enviada'         => 'boolean',
        'enviada_en'      => 'datetime',
        'fallida_en'      => 'datetime',
        'omitida'         => 'boolean',
    ];

    /** Usuario destinatario de la notificación. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Ticket relacionado con la notificación. */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** Scope: notificaciones pendientes de envío cuyo tiempo ya llegó. */
    public function scopePendientes($query)
    {
        return $query->where('enviada', false)
            ->where('omitida', false)
            ->where('programado_para', '<=', now());
    }

    /** Scope: notificaciones omitidas por FCM_ENABLED=false. */
    public function scopeOmitidas($query)
    {
        return $query->where('omitida', true);
    }
}
