<?php
// tests/Feature/OperacionAgenteTest.php

namespace Tests\Feature;

use App\Models\AgenteParqueo;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\HorarioOperacionSeeder;
/**
 * Tests de la operación del agente: asignaciones, horarios y amonestaciones.
 */
class OperacionAgenteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class, ZonaSeeder::class, HorarioOperacionSeeder::class]);

    }

    private function admin(): User
    {
        return User::where('email', 'admin@simetsa.gob.ec')->first();
    }

    private function agente(): AgenteParqueo
    {
        $u = User::where('email', 'agente@simetsa.gob.ec')->first();
        return AgenteParqueo::create([
            'codigo' => 'AG-7001', 'user_id' => $u->id, 'numero_credencial' => 'C-7001',
            'carta_compromiso_firmada' => true, 'fecha_autorizacion' => now()->toDateString(), 'estado' => 'activo',
        ]);
    }

    public function test_asigna_zona_al_agente(): void
    {
        $agente = $this->agente();
        $zona = Zona::where('codigo', 'centro')->first();

        $this->actingAs($this->admin())->post(route('asignaciones-zona.store', $agente), [
            'zona_id' => $zona->id, 'fecha_inicio' => now()->toDateString(), 'activa' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('asignaciones_zona', ['agente_parqueo_id' => $agente->id, 'zona_id' => $zona->id]);
    }

    public function test_agrega_horario_rotativo(): void
    {
        $agente = $this->agente();
        $zona = Zona::where('codigo', 'centro')->first();

        $this->actingAs($this->admin())->post(route('horarios-rotativos.store', $agente), [
            'zona_id' => $zona->id, 'dia_semana' => 3, 'hora_inicio' => '08:00', 'hora_fin' => '18:00',
            'vigente_desde' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('horarios_rotativos', ['agente_parqueo_id' => $agente->id, 'dia_semana' => 3]);
    }

    public function test_horario_fin_antes_de_inicio_es_rechazado(): void
    {
        $agente = $this->agente();
        $zona = Zona::where('codigo', 'centro')->first();

        $this->actingAs($this->admin())->post(route('horarios-rotativos.store', $agente), [
            'zona_id' => $zona->id, 'dia_semana' => 3, 'hora_inicio' => '18:00', 'hora_fin' => '08:00',
            'vigente_desde' => now()->toDateString(),
        ])->assertSessionHasErrors('hora_fin');
    }

    public function test_escalada_de_amonestaciones_y_terminacion(): void
    {
        $agente = $this->agente();
        $admin = $this->admin();

        // 1.ª falta → verbal
        $this->actingAs($admin)->post(route('amonestaciones-agente.store', $agente), ['motivo' => 'Llegada tarde']);
        // 2.ª falta → escrita
        $this->actingAs($admin)->post(route('amonestaciones-agente.store', $agente), ['motivo' => 'Abandonó la zona']);
        // 3.ª falta → terminación
        $this->actingAs($admin)->post(route('amonestaciones-agente.store', $agente), ['motivo' => 'Cobro indebido']);

        $this->assertDatabaseHas('amonestaciones_agente', ['agente_parqueo_id' => $agente->id, 'numero_falta' => 1, 'tipo' => 'verbal']);
        $this->assertDatabaseHas('amonestaciones_agente', ['agente_parqueo_id' => $agente->id, 'numero_falta' => 2, 'tipo' => 'escrita']);
        $this->assertDatabaseHas('amonestaciones_agente', ['agente_parqueo_id' => $agente->id, 'numero_falta' => 3, 'tipo' => 'terminacion']);

        $this->assertEquals('terminado', $agente->fresh()->estado);
    }

    public function test_no_amonesta_a_agente_terminado(): void
    {
        $agente = $this->agente();
        $agente->update(['estado' => 'terminado']);

        $this->actingAs($this->admin())->post(route('amonestaciones-agente.store', $agente), ['motivo' => 'Otra falta'])
             ->assertSessionHas('error');

        $this->assertEquals(0, $agente->amonestaciones()->count());
    }

    public function test_no_asigna_zona_a_agente_terminado(): void
    {
        $agente = $this->agente();
        $agente->update(['estado' => 'terminado']);
        $zona = Zona::where('codigo', 'centro')->first();

        $this->actingAs($this->admin())->post(route('asignaciones-zona.store', $agente), [
            'zona_id' => $zona->id, 'fecha_inicio' => now()->toDateString(),
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('asignaciones_zona', ['agente_parqueo_id' => $agente->id]);
    }

    public function test_no_asigna_zona_duplicada_solapada(): void
    {
        $agente = $this->agente();
        $zona = Zona::where('codigo', 'centro')->first();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('asignaciones-zona.store', $agente), [
            'zona_id' => $zona->id, 'fecha_inicio' => now()->toDateString(), 'activa' => 1,
        ])->assertRedirect();
        // Misma zona, periodo solapado (la primera quedó abierta)
        $this->actingAs($admin)->post(route('asignaciones-zona.store', $agente), [
            'zona_id' => $zona->id, 'fecha_inicio' => now()->toDateString(), 'activa' => 1,
        ])->assertSessionHas('error');

        $this->assertEquals(1, $agente->asignaciones()->count());
    }

    public function test_horario_dia_no_operativo_es_rechazado(): void
    {
        $agente = $this->agente();
        $zona = Zona::where('codigo', 'centro')->first();

        // Lunes (1) no es día de operación (Art. 12)
        $this->actingAs($this->admin())->post(route('horarios-rotativos.store', $agente), [
            'zona_id' => $zona->id, 'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '18:00',
            'vigente_desde' => now()->toDateString(),
        ])->assertSessionHasErrors('dia_semana');
    }

    public function test_no_agrega_horario_duplicado(): void
    {
        $agente = $this->agente();
        $zona = Zona::where('codigo', 'centro')->first();
        $admin = $this->admin();
        $payload = ['zona_id' => $zona->id, 'dia_semana' => 2, 'hora_inicio' => '08:00', 'hora_fin' => '18:00', 'vigente_desde' => now()->toDateString()];

        $this->actingAs($admin)->post(route('horarios-rotativos.store', $agente), $payload);
        $this->actingAs($admin)->post(route('horarios-rotativos.store', $agente), $payload)->assertSessionHas('error');

        $this->assertEquals(1, $agente->horarios()->count());
    }

    public function test_eliminar_amonestacion_recalcula_y_reactiva_al_agente(): void
    {
        $agente = $this->agente();
        $admin = $this->admin();

        foreach (['Falta 1', 'Falta 2', 'Falta 3'] as $m) {
            $this->actingAs($admin)->post(route('amonestaciones-agente.store', $agente), ['motivo' => $m]);
        }
        $this->assertEquals('terminado', $agente->fresh()->estado);

        // Eliminar una amonestación: quedan 2 → agente reactivado
        $primera = $agente->amonestaciones()->orderBy('numero_falta')->first();
        $this->actingAs($admin)->delete(route('amonestaciones-agente.destroy', $primera))->assertRedirect();

        $this->assertEquals(2, \App\Models\AmonestacionAgente::where('agente_parqueo_id', $agente->id)->count());
        $this->assertEquals('activo', $agente->fresh()->estado);
    }
    public function test_permite_misma_zona_en_otro_periodo(): void
    {
        $agente = $this->agente();
        $zona = Zona::where('codigo', 'centro')->first();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('asignaciones-zona.store', $agente), [
            'zona_id' => $zona->id, 'fecha_inicio' => now()->toDateString(), 'activa' => 1,
        ]);
        // Misma zona pero otra fecha de inicio (otro año) → permitido
        $this->actingAs($admin)->post(route('asignaciones-zona.store', $agente), [
            'zona_id' => $zona->id, 'fecha_inicio' => now()->addYear()->toDateString(), 'activa' => 1,
        ])->assertRedirect();

        $this->assertEquals(2, $agente->asignaciones()->count());
    }

    public function test_bloquea_asignacion_duplicada_exacta(): void
    {
        $agente = $this->agente();
        $zona = Zona::where('codigo', 'centro')->first();
        $admin = $this->admin();
        $payload = ['zona_id' => $zona->id, 'fecha_inicio' => now()->toDateString(), 'activa' => 1];

        $this->actingAs($admin)->post(route('asignaciones-zona.store', $agente), $payload);
        $this->actingAs($admin)->post(route('asignaciones-zona.store', $agente), $payload)->assertSessionHas('error');

        $this->assertEquals(1, $agente->asignaciones()->count());
    }

    public function test_edita_asignacion(): void
    {
        $agente = $this->agente();
        $zona = Zona::where('codigo', 'centro')->first();
        $asig = $agente->asignaciones()->create(['zona_id' => $zona->id, 'fecha_inicio' => now()->toDateString(), 'activa' => true]);

        $this->actingAs($this->admin())->patch(route('asignaciones-zona.update', $asig), [
            'zona_id' => $zona->id, 'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addMonths(3)->toDateString(), 'activa' => 1,
        ])->assertRedirect();

        $this->assertNotNull($asig->fresh()->fecha_fin);
    }

    public function test_permite_mismo_dia_en_otra_vigencia(): void
    {
        $agente = $this->agente();
        $zona = Zona::where('codigo', 'centro')->first();
        $admin = $this->admin();
        $base = ['zona_id' => $zona->id, 'dia_semana' => 2, 'hora_inicio' => '08:00', 'hora_fin' => '18:00'];

        $this->actingAs($admin)->post(route('horarios-rotativos.store', $agente), $base + ['vigente_desde' => now()->toDateString()]);
        // Mismo día y zona, otra vigencia → permitido
        $this->actingAs($admin)->post(route('horarios-rotativos.store', $agente), $base + ['vigente_desde' => now()->addYear()->toDateString()])
            ->assertRedirect();

        $this->assertEquals(2, $agente->horarios()->count());
    }

    public function test_edita_motivo_de_amonestacion(): void
    {
        $agente = $this->agente();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('amonestaciones-agente.store', $agente), ['motivo' => 'Original']);
        $am = $agente->amonestaciones()->first();

        $this->actingAs($admin)->patch(route('amonestaciones-agente.update', $am), ['motivo' => 'Motivo corregido'])->assertRedirect();

        $this->assertEquals('Motivo corregido', $am->fresh()->motivo);
    }
}