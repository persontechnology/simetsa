<?php
// tests/Feature/CursoCapacitacionControllerTest.php

namespace Tests\Feature;

use App\Models\CursoCapacitacion;
use App\Models\InscripcionCurso;
use App\Models\SolicitudAgente;
use App\Models\User;
use Database\Seeders\ParametroSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la Etapa 2 — Capacitación (cursos, inscripciones, calificaciones).
 */
class CursoCapacitacionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class, ParametroSeeder::class]);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@simetsa.gob.ec')->first();
    }

    private function solicitudEnCapacitacion(string $codigo = 'SA-9001'): SolicitudAgente
    {
        return SolicitudAgente::create([
            'codigo' => $codigo, 'cedula' => '1710034065', 'nombres' => 'Test', 'apellidos' => 'Capacitación',
            'fecha_nacimiento' => '1990-01-01', 'nivel_educacion' => 'bachillerato',
            'estado' => SolicitudAgente::ESTADO_CAPACITACION, 'fecha_solicitud' => now()->toDateString(),
        ]);
    }

    private function curso(): CursoCapacitacion
    {
        return CursoCapacitacion::create([
            'codigo' => 'CUR-9001', 'nombre' => 'Curso test',
            'fecha_inicio' => now()->toDateString(), 'estado' => 'en_curso',
        ]);
    }

    public function test_conductor_no_lista_cursos(): void
    {
        $u = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $this->actingAs($u)->get(route('cursos-capacitacion.index'))->assertForbidden();
    }

    public function test_crea_curso_con_folio_automatico(): void
    {
        $this->actingAs($this->admin())->post(route('cursos-capacitacion.store'), [
            'nombre' => 'Edición test', 'fecha_inicio' => now()->toDateString(), 'estado' => 'planificado',
        ])->assertRedirect();

        $this->assertEquals('CUR-0001', CursoCapacitacion::first()->codigo);
    }

    public function test_inscribe_solicitud_en_capacitacion(): void
    {
        $curso = $this->curso();
        $s = $this->solicitudEnCapacitacion();

        $this->actingAs($this->admin())
             ->post(route('inscripciones-curso.store', $curso), ['solicitud_agente_id' => $s->id])
             ->assertRedirect();

        $this->assertDatabaseHas('inscripciones_curso', [
            'curso_capacitacion_id' => $curso->id, 'solicitud_agente_id' => $s->id, 'estado' => 'inscrito',
        ]);
    }

    public function test_no_inscribe_solicitud_fuera_de_capacitacion(): void
    {
        $curso = $this->curso();
        $s = SolicitudAgente::create([
            'codigo' => 'SA-9002', 'cedula' => '1710034065', 'nombres' => 'X', 'apellidos' => 'Y',
            'fecha_nacimiento' => '1990-01-01', 'nivel_educacion' => 'bachillerato',
            'estado' => SolicitudAgente::ESTADO_DOCUMENTACION, 'fecha_solicitud' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin())
             ->post(route('inscripciones-curso.store', $curso), ['solicitud_agente_id' => $s->id])
             ->assertSessionHas('error');

        $this->assertDatabaseMissing('inscripciones_curso', ['solicitud_agente_id' => $s->id]);
    }

    public function test_no_inscribe_dos_veces_en_el_mismo_curso(): void
    {
        $curso = $this->curso();
        $s = $this->solicitudEnCapacitacion();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('inscripciones-curso.store', $curso), ['solicitud_agente_id' => $s->id]);
        $this->actingAs($admin)->post(route('inscripciones-curso.store', $curso), ['solicitud_agente_id' => $s->id])
             ->assertSessionHas('error');

        $this->assertEquals(1, InscripcionCurso::where('solicitud_agente_id', $s->id)->count());
    }

    public function test_reinscribe_solicitud_despues_de_eliminarla(): void
    {
        $curso = $this->curso();
        $s = $this->solicitudEnCapacitacion();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('inscripciones-curso.store', $curso), ['solicitud_agente_id' => $s->id]);
        $inscripcion = InscripcionCurso::where('solicitud_agente_id', $s->id)->first();
        $inscripcion->delete();

        $this->assertSoftDeleted('inscripciones_curso', ['id' => $inscripcion->id]);

        $this->actingAs($admin)->post(route('inscripciones-curso.store', $curso), ['solicitud_agente_id' => $s->id])
             ->assertRedirect();

        $this->assertEquals(1, InscripcionCurso::where('solicitud_agente_id', $s->id)->count());
        $this->assertDatabaseHas('inscripciones_curso', [
            'id' => $inscripcion->id,
            'curso_capacitacion_id' => $curso->id,
            'solicitud_agente_id' => $s->id,
            'estado' => 'inscrito',
            'deleted_at' => null,
        ]);
    }

    public function test_aprobar_promedio_mueve_solicitud_a_autorizacion(): void
    {
        $curso = $this->curso();
        $s = $this->solicitudEnCapacitacion();
        $insc = InscripcionCurso::create([
            'curso_capacitacion_id' => $curso->id, 'solicitud_agente_id' => $s->id,
            'fecha_inscripcion' => now()->toDateString(), 'estado' => 'inscrito',
        ]);

        // (80 + 75 + 90) / 3 = 81.67 ≥ 70 → aprobado
        $this->actingAs($this->admin())->post(route('inscripciones-curso.calificar', $insc), [
            'notas' => ['atencion_cliente' => 80, 'primeros_auxilios' => 75, 'educacion_vial' => 90],
        ])->assertRedirect();

        $insc->refresh();
        $this->assertEquals('aprobado', $insc->estado);
        $this->assertEquals('81.67', $insc->promedio_final);
        $this->assertEquals('autorizacion', $s->fresh()->estado);
    }

    public function test_reprobar_no_avanza_la_solicitud(): void
    {
        $curso = $this->curso();
        $s = $this->solicitudEnCapacitacion();
        $insc = InscripcionCurso::create([
            'curso_capacitacion_id' => $curso->id, 'solicitud_agente_id' => $s->id,
            'fecha_inscripcion' => now()->toDateString(), 'estado' => 'inscrito',
        ]);

        // (50 + 60 + 40) / 3 = 50 < 70 → reprobado
        $this->actingAs($this->admin())->post(route('inscripciones-curso.calificar', $insc), [
            'notas' => ['atencion_cliente' => 50, 'primeros_auxilios' => 60, 'educacion_vial' => 40],
        ])->assertRedirect();

        $this->assertEquals('reprobado', $insc->fresh()->estado);
        $this->assertEquals('capacitacion', $s->fresh()->estado);
    }
}