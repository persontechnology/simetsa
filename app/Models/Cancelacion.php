<?php

// app/Models/Cancelacion.php

namespace App\Models;

use App\Enums\EstadoReembolso;
use App\Enums\TipoCancelacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de cancelación o anulación de un ticket.
 *
 * Cubre dos casos según el campo 'tipo' (App\Enums\TipoCancelacion):
 *   - conductor: el conductor cancela voluntariamente antes de iniciar sesión.
 *   - admin: comisario o super_admin anula el ticket administrativamente.
 *
 * El campo estado_reembolso indica si corresponde un reembolso digital (Fase 6.C).
 *
 * @property int              $id
 * @property int              $ticket_id
 * @property int              $cancelado_por
 * @property TipoCancelacion  $tipo
 * @property string           $motivo
 * @property float            $monto_reembolsado
 * @property EstadoReembolso  $estado_reembolso
 * @property \Carbon\Carbon   $cancelado_en
 */
class Cancelacion extends Model
{
    use HasFactory;

    protected $table = 'cancelaciones';

    protected $fillable = [
        'ticket_id', 'cancelado_por', 'tipo',
        'motivo', 'monto_reembolsado', 'estado_reembolso', 'cancelado_en',
    ];

    protected $casts = [
        'tipo'              => TipoCancelacion::class,
        'estado_reembolso'  => EstadoReembolso::class,
        'monto_reembolsado' => 'decimal:2',
        'cancelado_en'      => 'datetime',
    ];

    /** Ticket que fue cancelado o anulado. */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** Usuario que realizó la acción (conductor o comisario/admin). */
    public function canceladoPorUsuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }

    /** Etiqueta legible del tipo. */
    public function getTipoLabelAttribute(): string
    {
        return $this->tipo->etiqueta();
    }
}
