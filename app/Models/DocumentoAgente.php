<?php
// app/Models/DocumentoAgente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Modelo DocumentoAgente — archivo de respaldo de una solicitud (Art. 33-34).
 */
class DocumentoAgente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'documentos_agente';

    public const TIPO_OFICIO       = 'oficio';
    public const TIPO_CEDULA       = 'cedula';
    public const TIPO_EDUCACION    = 'educacion';
    public const TIPO_ANTECEDENTES = 'antecedentes_penales';
    public const TIPO_NO_ADEUDAR   = 'no_adeudar';
    public const TIPO_OTRO         = 'otro';

    protected $fillable = [
        'solicitud_agente_id', 'tipo', 'nombre_archivo', 'ruta_archivo',
        'validado', 'observacion', 'fecha_validacion', 'validado_por',
    ];

    protected $casts = [
        'validado'         => 'boolean',
        'fecha_validacion' => 'datetime',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudAgente::class, 'solicitud_agente_id');
    }

    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'validado_por');
    }

    /**
     * Tipos de documento con su etiqueta (Art. 33 req. 1-4, Art. 34).
     *
     * @return array<string, string>
     */
    public static function listadoTipos(): array
    {
        return [
            self::TIPO_OFICIO       => 'Oficio dirigido al Alcalde (Art. 34)',
            self::TIPO_CEDULA       => 'Copia de cédula (Art. 33.4)',
            self::TIPO_EDUCACION    => 'Certificado de educación (Art. 33.1)',
            self::TIPO_ANTECEDENTES => 'Certificado de antecedentes penales (Art. 33.2)',
            self::TIPO_NO_ADEUDAR   => 'Certificado de no adeudar al Municipio (Art. 33.3)',
            self::TIPO_OTRO         => 'Otro documento',
        ];
    }

    public function getTipoLabelAttribute(): string
    {
        return self::listadoTipos()[$this->tipo] ?? $this->tipo;
    }

    /**
     * URL pública del archivo almacenado.
     */
    public function getUrlAttribute(): ?string
    {
        return $this->ruta_archivo ? Storage::disk('public')->url($this->ruta_archivo) : null;
    }
}