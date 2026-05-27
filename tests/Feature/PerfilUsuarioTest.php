<?php
// tests/Feature/PerfilUsuarioTest.php

namespace Tests\Feature;

use App\Models\PerfilUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de integración para el modelo PerfilUsuario.
 *
 * Verifica la relación 1:1 con User, la persistencia de datos,
 * el soft delete y el método de aceptación de términos (LOPDP).
 */
class PerfilUsuarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Se puede crear un perfil válido y recuperarlo desde el User.
     *
     * @return void
     */
    public function test_se_crea_perfil_y_se_accede_desde_el_user(): void
    {
        $user = User::factory()->create();

        $perfil = PerfilUsuario::create([
            'user_id'          => $user->id,
            'cedula'           => '1710034065',
            'telefono_celular' => '0991234567',
            'acepta_terminos'  => false,
        ]);

        $this->assertInstanceOf(PerfilUsuario::class, $user->fresh()->perfil);
        $this->assertEquals('1710034065', $user->fresh()->perfil->cedula);
    }

    /**
     * La relación inversa perfil->user devuelve el User correcto.
     *
     * @return void
     */
    public function test_relacion_inversa_perfil_a_user_funciona(): void
    {
        $user   = User::factory()->create();
        $perfil = PerfilUsuario::create([
            'user_id'          => $user->id,
            'cedula'           => '1710034065',
            'telefono_celular' => '0991234567',
        ]);

        $this->assertEquals($user->id, $perfil->user->id);
    }

    /**
     * No se permite crear dos perfiles para el mismo usuario (unique en user_id).
     *
     * @return void
     */
    public function test_no_se_permiten_dos_perfiles_para_el_mismo_usuario(): void
    {
        $user = User::factory()->create();

        PerfilUsuario::create([
            'user_id'          => $user->id,
            'cedula'           => '1710034065',
            'telefono_celular' => '0991234567',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        PerfilUsuario::create([
            'user_id'          => $user->id,
            'cedula'           => '1102345677', // otra cédula válida
            'telefono_celular' => '0991234567',
        ]);
    }

    /**
     * El método aceptarTerminos marca el bool y graba la fecha.
     *
     * @return void
     */
    public function test_aceptar_terminos_registra_fecha_y_bandera(): void
    {
        $user   = User::factory()->create();
        $perfil = PerfilUsuario::create([
            'user_id'          => $user->id,
            'cedula'           => '1710034065',
            'telefono_celular' => '0991234567',
            'acepta_terminos'  => false,
        ]);

        $perfil->aceptarTerminos();
        $perfil->refresh();

        $this->assertTrue($perfil->acepta_terminos);
        $this->assertNotNull($perfil->fecha_aceptacion_terminos);
    }

    /**
     * El método tienePerfilCompleto retorna true solo si hay perfil
     * con consentimiento aceptado.
     *
     * @return void
     */
    public function test_tiene_perfil_completo_funciona(): void
    {
        $userSinPerfil  = User::factory()->create();
        $userConPerfil  = User::factory()->create();
        $userConsentido = User::factory()->create();

        PerfilUsuario::create([
            'user_id'          => $userConPerfil->id,
            'cedula'           => '1710034065',
            'telefono_celular' => '0991234567',
            'acepta_terminos'  => false,
        ]);

        PerfilUsuario::create([
            'user_id'                   => $userConsentido->id,
            'cedula'                    => '1102345677',
            'telefono_celular'          => '0991234567',
            'acepta_terminos'           => true,
            'fecha_aceptacion_terminos' => now(),
        ]);

        $this->assertFalse($userSinPerfil->tienePerfilCompleto());
        $this->assertFalse($userConPerfil->tienePerfilCompleto());
        $this->assertTrue($userConsentido->tienePerfilCompleto());
    }

    /**
     * Soft delete funciona y el perfil deja de aparecer en consultas normales.
     *
     * @return void
     */
    public function test_soft_delete_oculta_el_perfil(): void
    {
        $user   = User::factory()->create();
        $perfil = PerfilUsuario::create([
            'user_id'          => $user->id,
            'cedula'           => '1710034065',
            'telefono_celular' => '0991234567',
        ]);

        $perfil->delete();

        $this->assertNull(PerfilUsuario::find($perfil->id));
        $this->assertNotNull(PerfilUsuario::withTrashed()->find($perfil->id));
    }
}