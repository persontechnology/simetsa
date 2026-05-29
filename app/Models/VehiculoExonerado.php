<?php
// app/Models/VehiculoExonerado.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vehículo institucional exonerado del pago de parqueo (Art. 27 Ordenanza SIMETSA).
 *
 * No tiene relación con la tabla vehiculos: son vehículos de organismos del Estado,
 * Policía, Bomberos, FF.AA., etc. La exoneración es por placa + institución.
 *
 * @property int     $id
 * @property string  $placa
 * @property string  $institucion
 * @property string  $tipo_exoneracion
 * @property string|null $nombre_funcionario
 * @property string|null $numero_oficio
 * @property int     $tiempo_maximo_horas
 * @property string|null $observaciones
 * @property int     $registrado_por
 * @property bool    $activo
 * @property \Carbon\Carbon $fecha_registro
 */
class VehiculoExonerado extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehiculos_exonerados';

    public const TIPO_AUTORIDAD_ESTADO = 'autoridad_estado';
    public const TIPO_BOMBEROS         = 'bomberos';
    public const TIPO_AMBULANCIA       = 'ambulancia';
    public const TIPO_POLICIA          = 'policia';
    public const TIPO_FFAA             = 'ffaa';
    public const TIPO_MUNICIPAL        = 'municipal';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'placa', 'institucion', 'tipo_exoneracion',
        'nombre_funcionario', 'numero_oficio', 'tiempo_maximo_horas',
        'observaciones', 'registrado_por', 'activo', 'fecha_registro',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'activo'               => 'boolean',
        'fecha_registro'       => 'date',
        'tiempo_maximo_horas'  => 'integer',
    ];

    /**
     * Etiquetas legibles de los tipos de exoneración para selects.
     *
     * @return array<string, string>
     */
    public static function listadoTipos(): array
    {
        return [
            self::TIPO_AUTORIDAD_ESTADO => 'Autoridad del Estado',
            self::TIPO_BOMBEROS         => 'Cuerpo de Bomberos',
            self::TIPO_AMBULANCIA       => 'Ambulancia / Cruz Roja',
            self::TIPO_POLICIA          => 'Policía Nacional',
            self::TIPO_FFAA             => 'Fuerzas Armadas',
            self::TIPO_MUNICIPAL        => 'Municipio / GAD',
        ];
    }

    /**
     * Usuario que registró la exoneración.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
