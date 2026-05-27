<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Documento de respaldo de una solicitud de punto de venta (Art. 31).
 */
class DocumentoPuntoVenta extends Model
{
    use HasFactory, SoftDeletes;

    public const TIPO_SOLICITUD_ALCALDE = 'solicitud_alcalde';
    public const TIPO_CEDULA = 'cedula';
    public const TIPO_NO_ADEUDAR = 'no_adeudar';
    public const TIPO_PATENTE = 'patente_municipal';
    public const TIPO_OTRO = 'otro';

    protected $table = 'documentos_punto_venta';

    protected $fillable = [
        'solicitud_punto_venta_id', 'tipo', 'nombre_archivo', 'ruta_archivo',
        'validado', 'observacion', 'fecha_validacion', 'validado_por',
    ];

    protected function casts(): array
    {
        return [
            'validado' => 'boolean',
            'fecha_validacion' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function listadoTipos(): array
    {
        return [
            self::TIPO_SOLICITUD_ALCALDE => 'Solicitud dirigida al Alcalde',
            self::TIPO_CEDULA => 'Copia de cédula',
            self::TIPO_NO_ADEUDAR => 'Certificado de no adeudar al GAD',
            self::TIPO_PATENTE => 'Patente municipal',
            self::TIPO_OTRO => 'Otro',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudPuntoVenta::class, 'solicitud_punto_venta_id');
    }

    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    public function getUrlAttribute(): ?string
    {
        return $this->ruta_archivo ? Storage::disk('public')->url($this->ruta_archivo) : null;
    }

    public function getTipoLabelAttribute(): string
    {
        return self::listadoTipos()[$this->tipo] ?? $this->tipo;
    }
}