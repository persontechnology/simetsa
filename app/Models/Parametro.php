<?php
// app/Models/Parametro.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ParametroBitacora;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo Parametro — key-value store tipado de configuración global del SIMETSA.
 *
 * Uso programático (recomendado en lógica de negocio):
 *   $sbu      = Parametro::obtener('sbu_vigente');                  // float 460.00
 *   $tiempo   = Parametro::obtener('tiempo_maximo_parqueo_minutos'); // int 120
 *   $tarifa   = Parametro::obtener('tarifa_por_hora');               // float 0.25
 *
 * @property string $clave
 * @property string $valor
 * @property string $tipo
 * @property string $categoria
 * @property string|null $descripcion
 * @property string|null $articulo_ordenanza
 * @property bool   $editable
 */
class Parametro extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'parametros';

    /**
     * Tipos válidos (espejo del enum de la migración).
     */
    public const TIPO_STRING  = 'string';
    public const TIPO_INTEGER = 'integer';
    public const TIPO_DECIMAL = 'decimal';
    public const TIPO_BOOLEAN = 'boolean';

    /** @var array<int, string> */
    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'categoria',
        'descripcion',
        'articulo_ordenanza',
        'editable',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'editable' => 'boolean',
    ];

    /**
     * Devuelve el valor con su tipo correcto (int, float, bool o string).
     *
     * @return int|float|bool|string
     */
    public function valorTipado(): int|float|bool|string
    {
        return match ($this->tipo) {
            self::TIPO_INTEGER => (int) $this->valor,
            self::TIPO_DECIMAL => (float) $this->valor,
            self::TIPO_BOOLEAN => filter_var($this->valor, FILTER_VALIDATE_BOOLEAN),
            default            => (string) $this->valor,
        };
    }

    /**
     * Helper estático para obtener un parámetro por clave con tipo correcto.
     * Útil en services y controladores: $sbu = Parametro::obtener('sbu_vigente');
     *
     * @param  string     $clave
     * @param  mixed      $defecto  Valor a retornar si el parámetro no existe
     * @return int|float|bool|string|mixed
     */
    public static function obtener(string $clave, mixed $defecto = null): mixed
    {
        $parametro = static::where('clave', $clave)->first();
        return $parametro ? $parametro->valorTipado() : $defecto;
    }

    /**
     * Devuelve la etiqueta legible de la categoría para interfaces.
     *
     * @return string
     */
    public function getCategoriaEtiquetaAttribute(): string
    {
        return match ($this->categoria) {
            'institucion'   => 'Institución',
            'operacion'     => 'Operación',
            'agentes'       => 'Agentes de parqueo',
            'puntos_venta'  => 'Puntos de venta',
            'app_movil'     => 'Aplicación móvil',
            'liquidaciones' => 'Liquidaciones',
            'multas'        => 'Multas y sanciones',
            'sanciones'     => 'Sanciones administrativas',
            default         => ucfirst(str_replace('_', ' ', $this->categoria)),
        };
    }

    /**
     * Devuelve el valor formateado para mostrar en interfaces
     * (con símbolo de moneda, % o sufijo según corresponda).
     *
     * @return string
     */
    public function getValorFormateadoAttribute(): string
    {
        // Heurística basada en la clave: monetario, porcentaje, minutos…
        if (str_contains($this->clave, 'sbu') || str_contains($this->clave, 'tarifa')) {
            return '$ ' . number_format((float) $this->valor, 2, '.', ',');
        }
        if (str_contains($this->clave, 'porcentaje')) {
            return $this->valor . ' %';
        }
        if (str_contains($this->clave, 'minutos')) {
            return $this->valor . ' min';
        }
        return (string) $this->valor;
    }

    /**
     * Scope: filtra por categoría.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string                                  $categoria
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    

    /**
     * Historial completo de cambios sobre este parámetro.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bitacora(): HasMany
    {
        return $this->hasMany(ParametroBitacora::class)
                    ->orderBy('ocurrido_en', 'desc');
    }

    /**
     * Última entrada de bitácora (latestOfMany resuelve eficientemente
     * el "last one" en eager loading: 1 query JOIN, no N+1).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function ultimaBitacora(): HasOne
    {
        return $this->hasOne(ParametroBitacora::class)
                    ->latestOfMany('ocurrido_en');
    }
}