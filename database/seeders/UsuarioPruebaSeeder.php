<?php
// database/seeders/UsuarioPruebaSeeder.php

namespace Database\Seeders;

use App\Enums\RolSistema;
use App\Models\PerfilUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder de usuarios de prueba del SIMETSA.
 *
 * Crea un usuario por cada rol del Enum RolSistema con su PerfilUsuario
 * poblado, cédula ecuatoriana válida, dirección en calles reales del
 * Art. 16 de la Ordenanza y consentimiento LOPDP aceptado.
 *
 * IMPORTANTE: las contraseñas de este seeder son únicamente para
 * desarrollo. NO debe ejecutarse en producción. La validación está
 * implementada en el método run().
 */
class UsuarioPruebaSeeder extends Seeder
{
    /**
     * Contraseña uniforme para todos los usuarios de prueba.
     * Cambiar por una segura antes del despliegue.
     *
     * @var string
     */
    private const CONTRASENA_DEV = 'password';

    /**
     * Crea los usuarios de prueba, sus perfiles y asigna roles.
     *
     * @return void
     */
    public function run(): void
    {
        // Salvaguarda: no ejecutar en producción. Comprobar varias fuentes
        // (app env, configuración y variable de entorno) para soportar overrides
        $isProduction = app()->environment('production')
            || config('app.env') === 'production'
            || env('APP_ENV') === 'production';

        if ($isProduction) {
            $this->command->error('UsuarioPruebaSeeder está bloqueado en producción.');
            return;
        }

        foreach ($this->datosUsuarios() as $datos) {
            DB::transaction(function () use ($datos) {
                $user = $this->crearOActualizarUsuario($datos);
                $this->crearOActualizarPerfil($user, $datos);
                $user->syncRoles([$datos['rol']->value]);
            });
        }

        $this->mostrarResumen();
    }

    /**
     * Devuelve el dataset de usuarios a crear. Centralizado para mantenibilidad.
     *
     * Direcciones tomadas del listado de calles del Art. 16 de la Ordenanza.
     * Cédulas con dígito verificador correcto (algoritmo módulo 10),
     * mayoría con prefijo 05 (provincia de Cotopaxi).
     *
     * @return array<int, array<string, mixed>>
     */
    private function datosUsuarios(): array
    {
        return [
            [
                'rol'              => RolSistema::SuperAdmin,
                'name'             => 'Administrador SIMETSA',
                'email'            => 'admin@simetsa.gob.ec',
                'cedula'           => '1710034065',
                'telefono_celular' => '0998765432',
                'telefono'         => '032729001',
                'direccion'        => 'Av. Olmedo y Calle Bolívar',
                'fecha_nacimiento' => '1985-03-15',
                'genero'           => 'M',
            ],
            [
                'rol'              => RolSistema::Comisario,
                'name'             => 'Carlos Vásquez Naranjo',
                'email'            => 'comisario@simetsa.gob.ec',
                'cedula'           => '0502345671',
                'telefono_celular' => '0991122334',
                'telefono'         => '032729002',
                'direccion'        => 'Calle Vicente León y 24 de Mayo',
                'fecha_nacimiento' => '1978-07-22',
                'genero'           => 'M',
            ],
            [
                'rol'              => RolSistema::DirectorSeguridad,
                'name'             => 'María Toapanta Tigse',
                'email'            => 'director.seguridad@simetsa.gob.ec',
                'cedula'           => '0503456782',
                'telefono_celular' => '0992233445',
                'telefono'         => '032729003',
                'direccion'        => 'Calle Sucre y Padre Salcedo',
                'fecha_nacimiento' => '1980-11-05',
                'genero'           => 'F',
            ],
            [
                'rol'              => RolSistema::AgenteParqueo,
                'name'             => 'Juan Quishpe Tipanluiza',
                'email'            => 'agente@simetsa.gob.ec',
                'cedula'           => '0504567892',
                'telefono_celular' => '0993344556',
                'telefono'         => null,
                'direccion'        => 'Calle Juan León Mera y Av. Olmedo',
                'fecha_nacimiento' => '1992-02-18',
                'genero'           => 'M',
            ],
            [
                'rol'              => RolSistema::PuntoVenta,
                'name'             => 'Farmacia Salcedo Centro',
                'email'            => 'puntoventa@simetsa.gob.ec',
                'cedula'           => '0505678904',
                'telefono_celular' => '0994455667',
                'telefono'         => '032729005',
                'direccion'        => 'Calle García Moreno entre Mejía y Quito',
                'fecha_nacimiento' => '1975-09-30',
                'genero'           => 'ND',
            ],
            [
                'rol'              => RolSistema::Conductor,
                'name'             => 'Pedro Tigse Caisaguano',
                'email'            => 'conductor@simetsa.gob.ec',
                'cedula'           => '0506789015',
                'telefono_celular' => '0995566778',
                'telefono'         => null,
                'direccion'        => 'Calle Luis A. Martínez y Vicente León',
                'fecha_nacimiento' => '1990-06-12',
                'genero'           => 'M',
            ],
        ];
    }

    /**
     * Crea el User en `users` o lo actualiza si ya existe (búsqueda por email).
     *
     * @param  array<string, mixed>  $datos  Datos de la fila del dataset
     * @return \App\Models\User
     */
    private function crearOActualizarUsuario(array $datos): User
    {
        return User::updateOrCreate(
            ['email' => $datos['email']],
            [
                'name'              => $datos['name'],
                'password'          => Hash::make(self::CONTRASENA_DEV),
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * Crea o actualiza el PerfilUsuario asociado al User (relación 1:1).
     *
     * @param  \App\Models\User      $user
     * @param  array<string, mixed>  $datos
     * @return \App\Models\PerfilUsuario
     */
    private function crearOActualizarPerfil(User $user, array $datos): PerfilUsuario
    {
        return PerfilUsuario::updateOrCreate(
            ['user_id' => $user->id],
            [
                'cedula'                    => $datos['cedula'],
                'telefono'                  => $datos['telefono'],
                'telefono_celular'          => $datos['telefono_celular'],
                'direccion'                 => $datos['direccion'],
                'fecha_nacimiento'          => $datos['fecha_nacimiento'],
                'genero'                    => $datos['genero'],
                'acepta_terminos'           => true,
                'fecha_aceptacion_terminos' => now(),
                'activo'                    => true,
            ]
        );
    }

    /**
     * Muestra en consola un resumen tabular con las credenciales generadas.
     *
     * @return void
     */
    private function mostrarResumen(): void
    {
        $this->command->info('');
        $this->command->info('=== Usuarios de prueba creados ===');
        $this->command->table(
            ['Rol', 'Nombre', 'Email', 'Cédula', 'Contraseña'],
            array_map(
                fn (array $u) => [
                    $u['rol']->etiqueta(),
                    $u['name'],
                    $u['email'],
                    $u['cedula'],
                    self::CONTRASENA_DEV,
                ],
                $this->datosUsuarios()
            )
        );
        $this->command->warn('Contraseñas SOLO para desarrollo. Rotar antes de producción.');
    }
}