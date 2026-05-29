<?php
// app/Models/TipoVehiculo.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Catálogo de tipos de vehículo (Art. 25 Ordenanza SIMETSA).
 *
 * La exoneración por discapacidad (Art. 26) vive en CredencialDiscapacidad;
 * la de vehículos oficiales (Art. 27) en VehiculoExonerado.
 *
 * @property int         $id
 * @property string      $codigo
 * @property string      $nombre
 * @property string|null $descripcion
 * @property bool        $aplica_tarifa
 * @property bool        $activo
 */
class TipoVehiculo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipos_vehiculo';

    /** Vehículo liviano de uso privado (Art. 25). */
    public const COD_LIVIANO_PRIVADO = 'liviano_privado';

    /** Furgoneta, turismo, institucional de uso público (Art. 25). */
    public const COD_LIVIANO_PUBLICO = 'liviano_publico';

    /** Taxi (Art. 25). */
    public const COD_TAXI = 'taxi';

    /** Furgoneta de alquiler (Art. 25). */
    public const COD_FURGONETA = 'furgoneta';

    /**
     * Carga liviana — puede usar plazas SIMETSA para carga/descarga previo pago
     * en el horario que determine el Comisario (Art. 25).
     */
    public const COD_CARGA_LIVIANA = 'carga_liviana';

    /** Vehículo institucional (la exoneración aplica por Art. 27, no por este tipo). */
    public const COD_INSTITUCIONAL = 'institucional';

    /**
     * @var array<int, string>
     */
    protected $fillable = ['codigo', 'nombre', 'descripcion', 'aplica_tarifa', 'activo'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'aplica_tarifa' => 'boolean',
        'activo'        => 'boolean',
    ];

    /**
     * Tipos activos para selects en formularios.
     *
     * @return array<int|string, string>
     */
    public static function listadoActivos(): array
    {
        return self::where('activo', true)
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->all();
    }
}
