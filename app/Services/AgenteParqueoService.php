<?php
// app/Services/AgenteParqueoService.php

namespace App\Services;

use App\Enums\RolSistema;
use App\Models\AgenteParqueo;
use App\Models\ExpedienteAgente;
use App\Models\PerfilUsuario;
use App\Models\SolicitudAgente;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de la Etapa 3 — Autorización del agente (Art. 36).
 *
 * Convierte una solicitud aprobada en capacitación en un agente activo:
 * resuelve su cuenta, perfil y rol, el registro de agente y su expediente,
 * todo de forma atómica.
 *
 * La resolución de identidad (cédula → correo → crear) se delega en
 * ResolutorCuentaService, compartido con PuntoVentaService.
 */
class AgenteParqueoService
{
    /**
     * @param  \App\Services\ResolutorCuentaService  $resolutor  Resolución de identidad de cuentas.
     */
    public function __construct(private ResolutorCuentaService $resolutor)
    {
    }

    /**
     * Genera el folio/credencial correlativo (AG-0001, ...).
     *
     * @return string  Código único del agente.
     */
    public function generarCodigo(): string
    {
        $ultimoId = AgenteParqueo::withTrashed()->max('id') ?? 0;

        return 'AG-' . str_pad((string) ($ultimoId + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Autoriza una solicitud en etapa de autorización (Art. 36).
     *
     * La identidad de la persona es la cédula. La cuenta se resuelve por
     * cédula → correo → crear (vía ResolutorCuentaService): si la cédula ya
     * existe debe usarse su mismo correo; si no existe ni cédula ni correo, se
     * crea la cuenta con contraseña temporal. Un usuario que ya es agente no
     * puede volver a autorizarse.
     *
     * @param  array{email:string, numero_credencial:string, numero_oficio_comisario:?string}  $datos
     * @return array{agente: \App\Models\AgenteParqueo, password_temporal: ?string}
     *
     * @throws \DomainException  Si la solicitud no está en la etapa correcta o hay conflicto de identidad.
     */
    public function autorizar(SolicitudAgente $solicitud, array $datos): array
    {
        if ($solicitud->estado !== SolicitudAgente::ESTADO_AUTORIZACION) {
            throw new DomainException('La solicitud no está en la etapa de autorización.');
        }

        return DB::transaction(function () use ($solicitud, $datos) {
            // Resolución de identidad compartida: cédula → correo → crear (Art. 36).
            ['user' => $user, 'password_temporal' => $passwordTemporal] = $this->resolutor->resolver(
                $solicitud->cedula,
                $datos['email'],
                $solicitud->nombre_completo,
                'agente'
            );

            // No se puede autorizar a alguien que ya es agente de parqueo.
            if (AgenteParqueo::where('user_id', $user->id)->exists()) {
                throw new DomainException('Ese usuario ya está registrado como agente de parqueo.');
            }

            // Asegurar el rol agente_parqueo (idempotente).
            if (! $user->hasRole(RolSistema::AgenteParqueo->value)) {
                $user->assignRole(RolSistema::AgenteParqueo->value);
            }

            // Crea el perfil solo si la cuenta aún no lo tiene.
            $this->asegurarPerfil($user, $solicitud);

            $agente = AgenteParqueo::create([
                'codigo'                   => $this->generarCodigo(),
                'solicitud_agente_id'      => $solicitud->id,
                'user_id'                  => $user->id,
                'numero_credencial'        => $datos['numero_credencial'],
                'numero_oficio_comisario'  => $datos['numero_oficio_comisario'] ?? null,
                'carta_compromiso_firmada' => true,                  // Art. 36
                'fecha_autorizacion'       => now()->toDateString(),
                'estado'                   => AgenteParqueo::ESTADO_ACTIVO,
            ]);

            ExpedienteAgente::create([
                'agente_parqueo_id' => $agente->id,
                'fecha_apertura'    => now()->toDateString(),
            ]);

            $solicitud->update(['estado' => SolicitudAgente::ESTADO_AUTORIZADA]);

            return ['agente' => $agente, 'password_temporal' => $passwordTemporal];
        });
    }

    /**
     * Crea el perfil del usuario a partir de la solicitud, solo si aún no lo tiene.
     *
     * Idempotente: un usuario resuelto por cédula ya trae su perfil, por lo que
     * no se vuelve a crear (evita duplicar perfiles_usuario).
     *
     * @param  \App\Models\User            $user       Usuario resuelto.
     * @param  \App\Models\SolicitudAgente $solicitud  Solicitud con los datos personales.
     * @return void
     */
    private function asegurarPerfil(User $user, SolicitudAgente $solicitud): void
    {
        if ($user->perfil) {
            return;
        }

        PerfilUsuario::create([
            'user_id'                   => $user->id,
            'cedula'                    => $solicitud->cedula,
            'telefono'                  => $solicitud->telefono,
            'telefono_celular'          => $solicitud->telefono_celular,
            'direccion'                 => $solicitud->direccion,
            'fecha_nacimiento'          => $solicitud->fecha_nacimiento,
            'acepta_terminos'           => true, // firmó la carta compromiso (Art. 36)
            'fecha_aceptacion_terminos' => now(),
            'activo'                    => true,
        ]);
    }
}