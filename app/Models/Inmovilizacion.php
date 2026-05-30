<?php

// app/Models/Inmovilizacion.php

namespace App\Models;

use App\Enums\EstadoInmovilizacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Registro del candado inmovilizador colocado a un vehículo infraccionado.
 *
 * Relación 1:1 con Infraccion (FK única).
 * Queda sin efecto al pagar la infracción (Art. 15).
 *
 * @property int                    $id
 * @property int                    $infraccion_id
 * @property int                    $agente_parqueo_id
 * @property EstadoInmovilizacion   $estado
 * @property string|null            $foto_candado
 * @property string|null            $notas
 * @property \Carbon\Carbon         $inmovilizada_en
 * @property \Carbon\Carbon|null    $liberada_en
 * @property string|null            $motivo_anulacion
 * @property int|null               $anulada_por
 */
class Inmovilizacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inmovilizaciones';

    protected $fillable = [
        'infraccion_id',
        'agente_parqueo_id',
        'estado',
        'foto_candado',
        'notas',
        'inmovilizada_en',
        'liberada_en',
        'motivo_anulacion',
        'anulada_por',
    ];

    protected $casts = [
        'estado'          => EstadoInmovilizacion::class,
        'inmovilizada_en' => 'datetime',
        'liberada_en'     => 'datetime',
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    /** @return BelongsTo<Infraccion, Inmovilizacion> */
    public function infraccion(): BelongsTo
    {
        return $this->belongsTo(Infraccion::class);
    }

    /** @return BelongsTo<AgenteParqueo, Inmovilizacion> */
    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteParqueo::class, 'agente_parqueo_id');
    }

    /** @return BelongsTo<User, Inmovilizacion> */
    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Indica si el vehículo sigue inmovilizado. */
    public function estaActiva(): bool
    {
        return $this->estado === EstadoInmovilizacion::Activa;
    }
}
