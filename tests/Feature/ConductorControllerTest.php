<?php
// tests/Feature/ConductorControllerTest.php

namespace Tests\Feature;

use App\Models\Conductor;
use App\Models\TipoVehiculo;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\TipoVehiculoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del backoffice de conductores (Fase 4.D — Art. 37 Ordenanza SIMETSA).
 */
class ConductorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class, TipoVehiculoSeeder::class]);
    }

    private function comisarioUser(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function directorUser(): User
    {
        return User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
    }

    private function agenteUser(): User
    {
        return User::where('email', 'agente@simetsa.gob.ec')->first();
    }

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function crearConductor(?User $user = null): Conductor
    {
        $user ??= $this->conductorUser();

        return Conductor::firstOrCreate(
            ['user_id' => $user->id],
            ['codigo' => 'CD-' . str_pad((string) ($user->id + 90000), 5, '0', STR_PAD_LEFT), 'estado' => Conductor::ESTADO_ACTIVO],
        );
    }

    // ===== Listado =====

    public function test_comisario_puede_ver_listado_de_conductores(): void
    {
        $this->actingAs($this->comisarioUser())
            ->get(route('conductores.index'))
            ->assertOk();
    }

    public function test_director_puede_ver_listado_de_conductores(): void
    {
        $this->actingAs($this->directorUser())
            ->get(route('conductores.index'))
            ->assertOk();
    }

    public function test_agente_no_puede_ver_listado_de_conductores(): void
    {
        $this->actingAs($this->agenteUser())
            ->get(route('conductores.index'))
            ->assertForbidden();
    }

    // ===== Detalle =====

    public function test_comisario_puede_ver_detalle_de_conductor(): void
    {
        $conductor = $this->crearConductor();

        $this->actingAs($this->comisarioUser())
            ->get(route('conductores.show', $conductor))
            ->assertOk();
    }

    // ===== Bloquear / Desbloquear =====

    public function test_comisario_puede_bloquear_conductor(): void
    {
        $conductor = $this->crearConductor();

        $this->actingAs($this->comisarioUser())
            ->patch(route('conductores.bloquear', $conductor))
            ->assertRedirect();

        $this->assertEquals(Conductor::ESTADO_BLOQUEADO, $conductor->fresh()->estado);
    }

    public function test_comisario_puede_desbloquear_conductor(): void
    {
        $conductor = $this->crearConductor();
        $conductor->update(['estado' => Conductor::ESTADO_BLOQUEADO]);

        $this->actingAs($this->comisarioUser())
            ->patch(route('conductores.desbloquear', $conductor))
            ->assertRedirect();

        $this->assertEquals(Conductor::ESTADO_ACTIVO, $conductor->fresh()->estado);
    }

    public function test_agente_no_puede_bloquear_conductor(): void
    {
        $conductor = $this->crearConductor();

        $this->actingAs($this->agenteUser())
            ->patch(route('conductores.bloquear', $conductor))
            ->assertForbidden();
    }

    // ===== Conductor bloqueado no puede iniciar sesión en la API =====

    public function test_conductor_bloqueado_no_puede_iniciar_sesion_en_api(): void
    {
        $conductor = $this->crearConductor();

        $this->actingAs($this->comisarioUser())
            ->patch(route('conductores.bloquear', $conductor))
            ->assertRedirect();

        $this->assertEquals(Conductor::ESTADO_BLOQUEADO, $conductor->fresh()->estado);

        $this->postJson('/api/v1/login', [
            'email'    => $this->conductorUser()->email,
            'password' => 'password',
        ])->assertForbidden();
    }
}
