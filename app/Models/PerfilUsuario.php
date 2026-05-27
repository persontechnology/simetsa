<?php
// app/Models/PerfilUsuario.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Modelo PerfilUsuario.
 *
 * Extiende al User base con los datos personales del SIMETSA.
 * Mantiene relación 1:1 con User y soporta soft deletes para
 * cumplir con la política de retención de datos de la LOPDP.
 */
class PerfilUsuario extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre explícito de la tabla (Laravel pluralizaría mal "PerfilUsuario").
     *
     * @var string
     */
    protected $table = 'perfiles_usuario';

    /**
     * Campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'cedula',
        'telefono',
        'telefono_celular',
        'direccion',
        'fecha_nacimiento',
        'genero',
        'foto_perfil',
        'acepta_terminos',
        'fecha_aceptacion_terminos',
        'activo',
    ];

    /**
     * Casts de los atributos para tipado fuerte.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_nacimiento'          => 'date',
        'acepta_terminos'           => 'boolean',
        'fecha_aceptacion_terminos' => 'datetime',
        'activo'                    => 'boolean',
    ];

    /**
     * Relación inversa 1:1 con el modelo User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Marca el consentimiento informado como aceptado y registra la fecha.
     * Cumple con el Art. 7 de la LOPDP (consentimiento informado).
     *
     * @return void
     */
    public function aceptarTerminos(): void
    {
        $this->update([
            'acepta_terminos'           => true,
            'fecha_aceptacion_terminos' => now(),
        ]);
    }

    /**
     * Devuelve la URL pública de la foto de perfil o null si no existe.
     *
     * @return string|null  URL absoluta para mostrar en vistas
     */
    public function getUrlFotoPerfilAttribute(): ?string
    {
        return $this->foto_perfil
            ? Storage::disk('public')->url($this->foto_perfil)
            : null;
    }

    /**
     * Devuelve la etiqueta legible del género para interfaces.
     *
     * @return string|null
     */
    public function getGeneroEtiquetaAttribute(): ?string
    {
        return match ($this->genero) {
            'M'  => 'Masculino',
            'F'  => 'Femenino',
            'O'  => 'Otro',
            'ND' => 'No declara',
            default => null,
        };
    }

    /**
     * Scope: filtra solo perfiles activos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}