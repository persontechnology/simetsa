<?php
// app/Models/ParametroBitacora.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo ParametroBitacora — entrada del log de auditoría de Parametro.
 *
 * Tabla append-only: no se actualiza ni elimina por convención.
 *
 * @property int          $parametro_id
 * @property int|null     $user_id
 * @property string       $campo
 * @property string|null  $valor_anterior
 * @property string|null  $valor_nuevo
 * @property string|null  $ip
 * @property \Carbon\Carbon $ocurrido_en
 */
class ParametroBitacora extends Model
{
    /** @var string */
    protected $table = 'parametros_bitacora';

    /** @var array<int, string> */
    protected $fillable = [
        'parametro_id',
        'user_id',
        'campo',
        'valor_anterior',
        'valor_nuevo',
        'ip',
        'ocurrido_en',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'ocurrido_en' => 'datetime',
    ];

    /**
     * Parámetro al que pertenece este cambio.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parametro(): BelongsTo
    {
        return $this->belongsTo(Parametro::class);
    }

    /**
     * Usuario que ejecutó el cambio. Null si fue automático.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}