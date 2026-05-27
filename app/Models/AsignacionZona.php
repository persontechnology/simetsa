<?php
// app/Models/AsignacionZona.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo AsignacionZona — zona asignada a un agente (Art. 16).
 */
class AsignacionZona extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asignaciones_zona';

    protected $fillable = [
        'agente_parqueo_id', 'zona_id', 'fecha_inicio', 'fecha_fin', 'activa', 'observacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activa'       => 'boolean',
    ];

    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteParqueo::class, 'agente_parqueo_id');
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }
}