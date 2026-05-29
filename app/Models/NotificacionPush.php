<?php

// app/Models/NotificacionPush.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cola lógica de notificaciones push pendientes de envío.
 *
 * En Fase 5 se encolan las intenciones de notificación; el envío real a
 * Firebase Cloud Messaging se activa en Fase 6.
 *
 * @property int              $id
 * @property int              $user_id
 * @property int|null         $ticket_id
 * @property string           $tipo
 * @property array            $payload
 * @property \Carbon\Carbon   $programado_para
 * @property bool             $enviada
 * @property \Carbon\Carbon|null $enviada_en
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
    ];

    protected $casts = [
        'payload'          => 'array',
        'programado_para'  => 'datetime',
        'enviada'          => 'boolean',
        'enviada_en'       => 'datetime',
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
            ->where('programado_para', '<=', now());
    }
}
