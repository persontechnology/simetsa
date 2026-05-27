<?php
// app/Models/ExpedienteAgente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo ExpedienteAgente — expediente personal del agente (Art. 40).
 */
class ExpedienteAgente extends Model
{
    use HasFactory;

    protected $table = 'expedientes_agente';

    protected $fillable = ['agente_parqueo_id', 'observaciones', 'fecha_apertura'];

    protected $casts = ['fecha_apertura' => 'date'];

    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteParqueo::class, 'agente_parqueo_id');
    }
}