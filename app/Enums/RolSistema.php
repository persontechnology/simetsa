<?php
// app/Enums/RolSistema.php

namespace App\Enums;

/**
 * Enum con los 6 roles autorizados del SIMETSA.
 *
 * - SuperAdmin:         Administrador técnico del sistema (uso interno).
 * - Comisario:          Comisario de Higiene y Salubridad (Art. 4, 11, 15, 28, 37).
 * - DirectorSeguridad:  Director de Seguridad Ciudadana (Art. 4, 10, 20, 36).
 * - AgenteParqueo:      Agente de Parqueo en calle (Art. 32-40).
 * - PuntoVenta:         Punto de venta autorizado en local comercial (Art. 31).
 * - Conductor:          Usuario final de la app móvil (Art. 41, 42).
 */
enum RolSistema: string
{
    case SuperAdmin        = 'super_admin';
    case Comisario         = 'comisario';
    case DirectorSeguridad = 'director_seguridad';
    case AgenteParqueo     = 'agente_parqueo';
    case PuntoVenta        = 'punto_venta';
    case Conductor         = 'conductor';

    /**
     * Devuelve la etiqueta legible del rol para interfaces de usuario.
     *
     * @return string  Nombre del rol en español formal
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::SuperAdmin        => 'Super Administrador',
            self::Comisario         => 'Comisario de Higiene y Salubridad',
            self::DirectorSeguridad => 'Director de Seguridad Ciudadana',
            self::AgenteParqueo     => 'Agente de Parqueo',
            self::PuntoVenta        => 'Punto de Venta',
            self::Conductor         => 'Conductor',
        };
    }

    /**
     * Devuelve todos los roles como array [valor => etiqueta].
     * Útil para llenar selects en formularios Blade.
     *
     * @return array<string, string>
     */
    public static function listado(): array
    {
        $listado = [];
        foreach (self::cases() as $rol) {
            $listado[$rol->value] = $rol->etiqueta();
        }
        return $listado;
    }
}