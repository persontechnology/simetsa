<?php
// app/Models/RegistroAcceso.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de auditoría de accesos al sistema SIMETSA.
 *
 * Registra cada evento del flujo de autenticación. Es una tabla
 * append-only: NO se actualiza ni se elimina (auditoría LOPDP).
 *
 * @property int|null         $user_id
 * @property string|null      $email_intento
 * @property string           $evento
 * @property string|null      $ip
 * @property string|null      $user_agent
 * @property \Carbon\Carbon   $ocurrido_en
 */
class RegistroAcceso extends Model
{
    /**
     * Nombre explícito de la tabla (Laravel pluralizaría mal).
     *
     * @var string
     */
    protected $table = 'registros_acceso';

    /**
     * Tipos de evento (constantes para evitar magic strings).
     */
    public const EVENTO_LOGIN    = 'login';
    public const EVENTO_LOGOUT   = 'logout';
    public const EVENTO_FALLIDO  = 'fallido';
    public const EVENTO_BLOQUEO  = 'bloqueo';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'email_intento',
        'evento',
        'ip',
        'user_agent',
        'ocurrido_en',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'ocurrido_en' => 'datetime',
    ];

    /**
     * Usuario asociado al evento (null si fue intento fallido sin match
     * o si el user fue eliminado posteriormente).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Devuelve la etiqueta legible del evento para interfaces.
     *
     * @return string
     */
    public function getEventoEtiquetaAttribute(): string
    {
        return match ($this->evento) {
            self::EVENTO_LOGIN   => 'Inicio de sesión',
            self::EVENTO_LOGOUT  => 'Cierre de sesión',
            self::EVENTO_FALLIDO => 'Intento fallido',
            self::EVENTO_BLOQUEO => 'Bloqueo por intentos',
            default              => ucfirst($this->evento),
        };
    }

    /**
     * Devuelve el color de badge Bootstrap apropiado para cada evento.
     *
     * @return string  'success' | 'secondary' | 'warning' | 'danger'
     */
    public function getColorBadgeAttribute(): string
    {
        return match ($this->evento) {
            self::EVENTO_LOGIN   => 'success',
            self::EVENTO_LOGOUT  => 'secondary',
            self::EVENTO_FALLIDO => 'warning',
            self::EVENTO_BLOQUEO => 'danger',
            default              => 'light',
        };
    }

    /**
     * Listado de tipos de evento para llenar selects.
     *
     * @return array<string, string>
     */
    public static function listadoEventos(): array
    {
        return [
            self::EVENTO_LOGIN   => 'Inicio de sesión',
            self::EVENTO_LOGOUT  => 'Cierre de sesión',
            self::EVENTO_FALLIDO => 'Intento fallido',
            self::EVENTO_BLOQUEO => 'Bloqueo por intentos',
        ];
    }
}