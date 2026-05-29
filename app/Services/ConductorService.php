<?php
// app/Services/ConductorService.php

namespace App\Services;

use App\Enums\RolSistema;
use App\Models\Conductor;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de registro de conductores (Fase 4).
 *
 * A diferencia de agentes y puntos de venta (donde el admin crea la cuenta),
 * el conductor se autorregistra eligiendo su propia contraseña. La unicidad de
 * cédula y correo se garantiza en el Form Request, por lo que aquí asumimos
 * datos ya validados.
 */
class ConductorService
{
    /**
     * @param  \App\Services\UsuarioService  $usuarioService  Creación del perfil (reutiliza el helper común).
     */
    public function __construct(private UsuarioService $usuarioService)
    {
    }

    /**
     * Genera el código correlativo del conductor (CD-00001, ...).
     *
     * @return string
     */
    public function generarCodigo(): string
    {
        $ultimo = Conductor::withTrashed()->max('id') ?? 0;

        return 'CD-' . str_pad((string) ($ultimo + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Registra un conductor: cuenta + rol + perfil + registro de conductor.
     *
     * @param  array{cedula:string, nombres:string, apellidos:string, email:string, password:string, telefono?:?string, telefono_celular?:?string, direccion?:?string, fecha_nacimiento?:?string}  $datos
     * @return array{conductor: \App\Models\Conductor, user: \App\Models\User, token: string}
     */
    public function registrar(array $datos): array
    {
        return DB::transaction(function () use ($datos) {
            $user = User::create([
                'name'     => trim("{$datos['nombres']} {$datos['apellidos']}"),
                'email'    => $datos['email'],
                'password' => $datos['password'], // el cast 'hashed' del modelo User la encripta
            ]);

            $user->assignRole(RolSistema::Conductor->value);

            // Perfil con el consentimiento informado ya aceptado (Art. 7 LOPDP)
            $this->usuarioService->crearPerfil($user, [
                'cedula'                    => $datos['cedula'],
                'telefono'                  => $datos['telefono'] ?? null,
                'telefono_celular'          => $datos['telefono_celular'] ?? null,
                'direccion'                 => $datos['direccion'] ?? null,
                'fecha_nacimiento'          => $datos['fecha_nacimiento'] ?? null,
                'acepta_terminos'           => true,
                'fecha_aceptacion_terminos' => now(),
                'activo'                    => true,
            ]);

            $conductor = Conductor::create([
                'user_id' => $user->id,
                'codigo'  => $this->generarCodigo(),
                'estado'  => Conductor::ESTADO_ACTIVO,
            ]);

            $token = $user->createToken('movil')->plainTextToken;

            return ['conductor' => $conductor, 'user' => $user, 'token' => $token];
        });
    }

    /**
     * Cambia el estado de la cuenta del conductor (activo ↔ bloqueado).
     *
     * @see Art. 37 Ordenanza SIMETSA (facultades del comisario).
     *
     * @param  Conductor  $conductor
     * @param  string     $estado  'activo' | 'bloqueado'
     * @return Conductor
     *
     * @throws \DomainException Si el estado no es válido.
     */
    public function cambiarEstado(Conductor $conductor, string $estado): Conductor
    {
        if (! in_array($estado, [Conductor::ESTADO_ACTIVO, Conductor::ESTADO_BLOQUEADO], true)) {
            throw new \DomainException("Estado '{$estado}' no válido para un conductor.");
        }

        $conductor->update(['estado' => $estado]);

        return $conductor->fresh();
    }
}