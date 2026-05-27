<?php
// app/Services/ResolutorCuentaService.php

namespace App\Services;

use App\Models\User;
use DomainException;
use Illuminate\Support\Str;

/**
 * Servicio compartido de resolución de identidad de cuentas.
 *
 * Centraliza la creación/vinculación de la cuenta `User` cuando un trámite
 * (activación de Punto de Venta — Art. 31, o autorización de Agente — Art. 36)
 * debe asociarse a una persona. La identidad de la persona es la CÉDULA, y la
 * cuenta de acceso se resuelve en este orden estricto: cédula → correo → crear.
 *
 * Antes esta lógica estaba duplicada (PuntoVentaService la tenía corregida;
 * AgenteParqueoService no), lo que provocaba un unique violation (SQLSTATE 23505)
 * al autorizar un agente cuya cédula ya existía en otro perfil. Unificarla aquí
 * elimina esa divergencia de comportamiento entre módulos.
 */
class ResolutorCuentaService
{
    /**
     * Resuelve la cuenta de usuario a partir de la cédula (identidad de la
     * persona) y del correo de acceso indicado en el trámite.
     *
     * Reglas:
     *  - Si la cédula ya identifica a un usuario, el trámite debe usar ESE mismo
     *    correo; en caso contrario se rechaza para no duplicar la identidad.
     *  - Si el correo ya existe pero pertenece a una persona con otra cédula,
     *    se rechaza para no secuestrar una cuenta ajena.
     *  - Si ni la cédula ni el correo existen, se crea la cuenta con una
     *    contraseña temporal segura.
     *
     * @param  string  $cedula    Cédula de la persona (identidad).
     * @param  string  $email     Correo de acceso solicitado para la cuenta.
     * @param  string  $nombre    Nombre completo para una cuenta nueva.
     * @param  string  $contexto  Etiqueta del trámite para los mensajes ("punto de venta", "agente").
     * @return array{user: User, password_temporal: ?string}  Usuario resuelto y la contraseña
     *                                                          temporal SOLO si se creó la cuenta.
     *
     * @throws \DomainException  Si la cédula o el correo entran en conflicto con otra cuenta.
     */
    public function resolver(string $cedula, string $email, string $nombre, string $contexto = 'registro'): array
    {
        // Usuario que YA tiene esta cédula (identidad de la persona en el sistema).
        $usuarioPorCedula = User::whereHas('perfil', fn ($q) => $q->where('cedula', $cedula))->first();
        // Usuario que YA usa este correo de acceso.
        $usuarioPorEmail = User::where('email', $email)->first();

        $passwordTemporal = null;

        if ($usuarioPorCedula) {
            // La cédula ya identifica a una persona: el trámite debe apuntar a su
            // misma cuenta (mismo correo), o se rechaza con un mensaje accionable.
            if (! $usuarioPorEmail || $usuarioPorEmail->isNot($usuarioPorCedula)) {
                throw new DomainException(
                    "La cédula {$cedula} ya pertenece al usuario «{$usuarioPorCedula->name}» " .
                    "({$usuarioPorCedula->email}). Para vincular este {$contexto}, usá ese mismo correo, " .
                    'o corregí la cédula de la solicitud.'
                );
            }
            $user = $usuarioPorCedula;
        } elseif ($usuarioPorEmail) {
            // El correo ya existe; no debe corresponder a otra persona (otra cédula).
            if ($usuarioPorEmail->perfil && $usuarioPorEmail->perfil->cedula !== $cedula) {
                throw new DomainException('El correo indicado pertenece a otra persona (con cédula distinta). Usá un correo diferente.');
            }
            $user = $usuarioPorEmail;
        } else {
            // Persona y correo nuevos: creamos la cuenta con contraseña temporal.
            $passwordTemporal = Str::password(10);
            $user = User::create([
                'name'     => $nombre,
                'email'    => $email,
                'password' => $passwordTemporal, // El cast 'hashed' del modelo User la encripta
            ]);
        }

        return ['user' => $user, 'password_temporal' => $passwordTemporal];
    }
}