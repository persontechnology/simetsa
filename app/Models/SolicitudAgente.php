<?php
// app/Models/SolicitudAgente.php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
/**
 * Modelo SolicitudAgente — trámite de postulación a Agente de Parqueo (Art. 32).
 */
class SolicitudAgente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'solicitudes_agente';

    public const ESTADO_DOCUMENTACION = 'documentacion';
    public const ESTADO_CAPACITACION  = 'capacitacion';
    public const ESTADO_AUTORIZACION  = 'autorizacion';
    public const ESTADO_AUTORIZADA    = 'autorizada';
    public const ESTADO_RECHAZADA     = 'rechazada';

    public const EDAD_MINIMA = 18; // Art. 33 req. 4

    protected $fillable = [
        'codigo', 'cedula', 'nombres', 'apellidos', 'fecha_nacimiento',
        'telefono', 'telefono_celular', 'email', 'direccion', 'nivel_educacion',
        'estado', 'observaciones', 'motivo_rechazo', 'fecha_solicitud', 'usuario_registro_id',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_solicitud'  => 'date',
    ];

    /**
     * Documentos cargados para la solicitud.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoAgente::class)->orderBy('tipo');
    }

    public function usuarioRegistro(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_registro_id');
    }

    /**
     * Niveles de educación para selects (Art. 33 req. 1: mínimo básica media).
     *
     * @return array<string, string>
     */
    public static function listadoNivelesEducacion(): array
    {
        return [
            'basica_media'    => 'Educación Básica Media',
            'basica_superior' => 'Educación Básica Superior',
            'bachillerato'    => 'Bachillerato',
            'tecnico'         => 'Técnico / Tecnológico',
            'superior'        => 'Tercer nivel (superior)',
        ];
    }

    /**
     * Etiquetas y colores de estado para la UI.
     *
     * @return array<string, array{label: string, color: string}>
     */
    public static function metaEstados(): array
    {
        return [
            self::ESTADO_DOCUMENTACION => ['label' => 'Revisión de documentación', 'color' => 'info'],
            self::ESTADO_CAPACITACION  => ['label' => 'En capacitación',            'color' => 'primary'],
            self::ESTADO_AUTORIZACION  => ['label' => 'En autorización',            'color' => 'warning'],
            self::ESTADO_AUTORIZADA    => ['label' => 'Autorizada',                 'color' => 'success'],
            self::ESTADO_RECHAZADA     => ['label' => 'Rechazada',                  'color' => 'danger'],
        ];
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::metaEstados()[$this->estado]['label'] ?? $this->estado;
    }

    public function getEstadoColorAttribute(): string
    {
        return self::metaEstados()[$this->estado]['color'] ?? 'secondary';
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    /**
     * Edad calculada del postulante.
     */
    public function getEdadAttribute(): int
    {
        return $this->fecha_nacimiento ? Carbon::parse($this->fecha_nacimiento)->age : 0;
    }

    /**
     * ¿La solicitud sigue en la etapa de revisión de documentación?
     */
    public function enEtapaDocumentacion(): bool
    {
        return $this->estado === self::ESTADO_DOCUMENTACION;
    }

    /**
     * Inscripciones del postulante en cursos de capacitación (Etapa 2).
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(InscripcionCurso::class)->orderByDesc('fecha_inscripcion');
    }

    

    /** Agente autorizado generado a partir de esta solicitud (Etapa 3).*/
    public function agente(): HasOne
    {
        return $this->hasOne(AgenteParqueo::class);
    }
}