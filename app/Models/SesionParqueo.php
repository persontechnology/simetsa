<?php

// app/Models/SesionParqueo.php

namespace App\Models;

use App\Enums\EstadoSesionParqueo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sesión de parqueo: inicio físico del estacionamiento confirmado por el agente.
 *
 * Relación 1:1 con Ticket. Se crea cuando el agente valida el vehículo en calle.
 * La tolerancia de 5 minutos (Art. 13) se calcula contra expira_en del ticket asociado.
 *
 * @property int                   $id
 * @property int                   $ticket_id
 * @property int|null              $agente_id
 * @property int|null              $plaza_id
 * @property float|null            $lat_inicio
 * @property float|null            $lng_inicio
 * @property \Carbon\Carbon        $inicio_at
 * @property \Carbon\Carbon        $fin_programado_at
 * @property \Carbon\Carbon|null   $fin_real_at
 * @property EstadoSesionParqueo   $estado
 */
class SesionParqueo extends Model
{
    use HasFactory;

    protected $table = 'sesiones_parqueo';

    protected $fillable = [
        'ticket_id', 'agente_id', 'plaza_id',
        'lat_inicio', 'lng_inicio',
        'inicio_at', 'fin_programado_at', 'fin_real_at',
        'estado',
    ];

    protected $casts = [
        'estado'           => EstadoSesionParqueo::class,
        'lat_inicio'       => 'decimal:7',
        'lng_inicio'       => 'decimal:7',
        'inicio_at'        => 'datetime',
        'fin_programado_at' => 'datetime',
        'fin_real_at'      => 'datetime',
    ];

    /** Ticket al que pertenece esta sesión (1:1). */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** Agente que confirmó el inicio de la sesión. */
    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteParqueo::class, 'agente_id');
    }

    /** Plaza específica donde se estacionó el vehículo. */
    public function plaza(): BelongsTo
    {
        return $this->belongsTo(Plaza::class);
    }

    /** Color Bootstrap del badge de estado. */
    public function getEstadoColorAttribute(): string
    {
        return $this->estado->color();
    }

    /** Etiqueta legible del estado. */
    public function getEstadoLabelAttribute(): string
    {
        return $this->estado->etiqueta();
    }
}
