<?php
// tests/Feature/AgenteParqueoControllerTest.php

namespace Tests\Feature;

use App\Models\AgenteParqueo;
use App\Models\SolicitudAgente;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la Etapa 3 — Autorización y agente activo (Art. 36).
 */
class AgenteParqueoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class]);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@simetsa.gob.ec')->first();
    }

    private function solicitudEnAutorizacion(): SolicitudAgente
    {
        return SolicitudAgente::create([
            'codigo' => 'SA-7001', 'cedula' => '0599999999', 'nombres' => 'Lucía', 'apellidos' => 'Andrade',
            'fecha_nacimiento' => '1992-05-05', 'nivel_educacion' => 'bachillerato',
            'telefono_celular' => '0990000000',
            'estado' => SolicitudAgente::ESTADO_AUTORIZACION, 'fecha_solicitud' => now()->toDateString(),
        ]);
    }

    public function test_conductor_no_lista_agentes(): void
    {
        $u = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $this->actingAs($u)->get(route('agentes-parqueo.index'))->assertForbidden();
    }

    public function test_autoriza_y_crea_agente_usuario_perfil_y_expediente(): void
    {
        $s = $this->solicitudEnAutorizacion();

        $this->actingAs($this->admin())->post(route('agentes-parqueo.autorizar', $s), [
            'email'                    => 'lucia.andrade@simetsa.gob.ec',
            'numero_credencial'        => 'CRED-100',
            'numero_oficio_comisario'  => 'OF-2026-045',
            'carta_compromiso_firmada' => 1,
        ])->assertRedirect();

        // Solicitud cerrada
        $this->assertEquals('autorizada', $s->fresh()->estado);

        // Usuario + rol + perfil
        $user = User::where('email', 'lucia.andrade@simetsa.gob.ec')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('agente_parqueo'));
        $this->assertDatabaseHas('perfiles_usuario', ['user_id' => $user->id, 'cedula' => '0599999999']);

        // Agente + expediente
        $agente = AgenteParqueo::where('user_id', $user->id)->first();
        $this->assertNotNull($agente);
        $this->assertEquals('AG-0001', $agente->codigo);
        $this->assertDatabaseHas('expedientes_agente', ['agente_parqueo_id' => $agente->id]);
    }

    public function test_no_autoriza_si_la_solicitud_no_esta_en_autorizacion(): void
    {
        $s = SolicitudAgente::create([
            'codigo' => 'SA-7002', 'cedula' => '0599999998', 'nombres' => 'X', 'apellidos' => 'Y',
            'fecha_nacimiento' => '1990-01-01', 'nivel_educacion' => 'bachillerato',
            'estado' => SolicitudAgente::ESTADO_CAPACITACION, 'fecha_solicitud' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin())->post(route('agentes-parqueo.autorizar', $s), [
            'email' => 'x@simetsa.gob.ec', 'numero_credencial' => 'C1', 'carta_compromiso_firmada' => 1,
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('agentes_parqueo', ['solicitud_agente_id' => $s->id]);
    }

    public function test_autorizar_requiere_carta_compromiso(): void
    {
        $s = $this->solicitudEnAutorizacion();

        $this->actingAs($this->admin())->post(route('agentes-parqueo.autorizar', $s), [
            'email' => 'sincarta@simetsa.gob.ec', 'numero_credencial' => 'C2',
            // carta_compromiso_firmada ausente
        ])->assertSessionHasErrors('carta_compromiso_firmada');
    }

    public function test_cambia_estado_a_suspendido(): void
    {
        $u = User::where('email', 'agente@simetsa.gob.ec')->first();
        $agente = AgenteParqueo::create([
            'codigo' => 'AG-9001', 'user_id' => $u->id, 'numero_credencial' => 'C9',
            'carta_compromiso_firmada' => true, 'fecha_autorizacion' => now()->toDateString(), 'estado' => 'activo',
        ]);

        $this->actingAs($this->admin())->patch(route('agentes-parqueo.estado', $agente), ['estado' => 'suspendido'])->assertRedirect();
        $this->assertEquals('suspendido', $agente->fresh()->estado);
    }

    public function test_actualiza_expediente(): void
    {
        $u = User::where('email', 'agente@simetsa.gob.ec')->first();
        $agente = AgenteParqueo::create([
            'codigo' => 'AG-9002', 'user_id' => $u->id, 'carta_compromiso_firmada' => true,
            'fecha_autorizacion' => now()->toDateString(), 'estado' => 'activo',
        ]);

        $this->actingAs($this->admin())->patch(route('agentes-parqueo.expediente', $agente), [
            'observaciones' => 'Agente puntual y responsable.',
        ])->assertRedirect();

        $this->assertDatabaseHas('expedientes_agente', [
            'agente_parqueo_id' => $agente->id, 'observaciones' => 'Agente puntual y responsable.',
        ]);
    }
    /**
     * Autorizar con el correo de un usuario existente (no agente) lo vincula,
     * sin crear una cuenta nueva ni contraseña temporal.
     *
     * @return void
     */
    public function test_autoriza_vinculando_usuario_existente(): void
    {
        $conductor = User::where('email', 'conductor@simetsa.gob.ec')->first();
        // La solicitud debe tener la cédula del conductor para que se resuelva correctamente
        $s = SolicitudAgente::create([
            'codigo' => 'SA-7002', 'cedula' => $conductor->perfil->cedula, 'nombres' => 'Pedro', 'apellidos' => 'Tigse',
            'fecha_nacimiento' => '1990-06-12', 'nivel_educacion' => 'bachillerato',
            'telefono_celular' => '0995566778',
            'estado' => SolicitudAgente::ESTADO_AUTORIZACION, 'fecha_solicitud' => now()->toDateString(),
        ]);
        $totalUsuarios = User::count();

        $this->actingAs($this->admin())->post(route('agentes-parqueo.autorizar', $s), [
            'email'                    => 'conductor@simetsa.gob.ec',
            'numero_credencial'        => 'CRED-200',
            'carta_compromiso_firmada' => 1,
        ])->assertRedirect()->assertSessionMissing('password_temporal');

        // No se creó un usuario nuevo
        $this->assertEquals($totalUsuarios, User::count());
        // El usuario existente quedó vinculado como agente y con el rol
        $this->assertTrue($conductor->fresh()->hasRole('agente_parqueo'));
        $this->assertDatabaseHas('agentes_parqueo', ['user_id' => $conductor->id]);
        $this->assertEquals('autorizada', $s->fresh()->estado);
    }

    /**
     * No se puede autorizar usando el correo de un usuario que ya es agente.
     *
     * @return void
     */
    public function test_no_autoriza_si_el_usuario_ya_es_agente(): void
    {
        // El usuario agente@ ya es agente
        $agenteUser = User::where('email', 'agente@simetsa.gob.ec')->first();
        AgenteParqueo::create([
            'codigo' => 'AG-5000', 'user_id' => $agenteUser->id, 'numero_credencial' => 'C-EXIST',
            'carta_compromiso_firmada' => true, 'fecha_autorizacion' => now()->toDateString(), 'estado' => 'activo',
        ]);

        $s = $this->solicitudEnAutorizacion();

        $this->actingAs($this->admin())->post(route('agentes-parqueo.autorizar', $s), [
            'email'                    => 'agente@simetsa.gob.ec',
            'numero_credencial'        => 'CRED-300',
            'carta_compromiso_firmada' => 1,
        ])->assertSessionHas('error');

        $this->assertEquals('autorizacion', $s->fresh()->estado);
    }
}