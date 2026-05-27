<?php
// tests/Feature/SolicitudAgenteControllerTest.php

namespace Tests\Feature;

use App\Models\DocumentoAgente;
use App\Models\SolicitudAgente;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests del trámite de Agente — Etapa 1 (solicitud + documentación).
 */
class SolicitudAgenteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class]);
        Storage::fake('public');
    }

    private function admin(): User
    {
        return User::where('email', 'admin@simetsa.gob.ec')->first();
    }

    public function test_conductor_no_lista_solicitudes(): void
    {
        $u = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $this->actingAs($u)->get(route('solicitudes-agente.index'))->assertForbidden();
    }

    public function test_crea_solicitud_con_folio_automatico(): void
    {
        $this->actingAs($this->admin())->post(route('solicitudes-agente.store'), [
            'cedula'           => '1710034065',
            'nombres'          => 'Ana',
            'apellidos'        => 'Pérez',
            'fecha_nacimiento' => '1996-01-01',
            'nivel_educacion'  => 'bachillerato',
        ])->assertRedirect();

        $s = SolicitudAgente::first();
        $this->assertNotNull($s);
        $this->assertEquals('SA-0001', $s->codigo);
        $this->assertEquals('documentacion', $s->estado);
    }

    public function test_postulante_menor_de_edad_es_rechazado(): void
    {
        $this->actingAs($this->admin())->post(route('solicitudes-agente.store'), [
            'cedula'           => '1710034065',
            'nombres'          => 'Niño',
            'apellidos'        => 'Test',
            'fecha_nacimiento' => now()->subYears(16)->toDateString(),
            'nivel_educacion'  => 'basica_media',
        ])->assertSessionHasErrors('fecha_nacimiento');
    }

    public function test_carga_y_valida_documento(): void
    {
        $s = SolicitudAgente::create([
            'codigo' => 'SA-0009', 'cedula' => '1710034065', 'nombres' => 'X', 'apellidos' => 'Y',
            'fecha_nacimiento' => '1990-01-01', 'nivel_educacion' => 'bachillerato',
            'estado' => 'documentacion', 'fecha_solicitud' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin())->post(route('documentos-agente.store', $s), [
            'tipo'    => DocumentoAgente::TIPO_CEDULA,
            'archivo' => UploadedFile::fake()->create('cedula.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $doc = DocumentoAgente::first();
        $this->assertNotNull($doc);
        Storage::disk('public')->assertExists($doc->ruta_archivo);

        $this->actingAs($this->admin())->patch(route('documentos-agente.validar', $doc))->assertRedirect();
        $this->assertTrue($doc->fresh()->validado);
    }

    public function test_no_aprueba_documentacion_si_faltan_documentos(): void
    {
        $s = SolicitudAgente::create([
            'codigo' => 'SA-0010', 'cedula' => '1710034065', 'nombres' => 'X', 'apellidos' => 'Y',
            'fecha_nacimiento' => '1990-01-01', 'nivel_educacion' => 'bachillerato',
            'estado' => 'documentacion', 'fecha_solicitud' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin())
             ->post(route('solicitudes-agente.aprobar-documentacion', $s))
             ->assertSessionHas('error');

        $this->assertEquals('documentacion', $s->fresh()->estado);
    }

    public function test_aprueba_documentacion_completa_pasa_a_capacitacion(): void
    {
        $s = SolicitudAgente::create([
            'codigo' => 'SA-0011', 'cedula' => '1710034065', 'nombres' => 'X', 'apellidos' => 'Y',
            'fecha_nacimiento' => '1990-01-01', 'nivel_educacion' => 'bachillerato',
            'estado' => 'documentacion', 'fecha_solicitud' => now()->toDateString(),
        ]);

        // Carga y valida los 5 documentos requeridos
        foreach ([DocumentoAgente::TIPO_OFICIO, DocumentoAgente::TIPO_CEDULA, DocumentoAgente::TIPO_EDUCACION,
                  DocumentoAgente::TIPO_ANTECEDENTES, DocumentoAgente::TIPO_NO_ADEUDAR] as $tipo) {
            DocumentoAgente::create([
                'solicitud_agente_id' => $s->id, 'tipo' => $tipo,
                'nombre_archivo' => "$tipo.pdf", 'ruta_archivo' => "documentos_agente/{$s->id}/$tipo.pdf",
                'validado' => true,
            ]);
        }

        $this->actingAs($this->admin())
             ->post(route('solicitudes-agente.aprobar-documentacion', $s))
             ->assertSessionHas('success');

        $this->assertEquals('capacitacion', $s->fresh()->estado);
    }

    public function test_rechaza_solicitud_con_motivo(): void
    {
        $s = SolicitudAgente::create([
            'codigo' => 'SA-0012', 'cedula' => '1710034065', 'nombres' => 'X', 'apellidos' => 'Y',
            'fecha_nacimiento' => '1990-01-01', 'nivel_educacion' => 'bachillerato',
            'estado' => 'documentacion', 'fecha_solicitud' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin())->post(route('solicitudes-agente.rechazar', $s), [
            'motivo_rechazo' => 'Documentación inconsistente.',
        ])->assertRedirect();

        $this->assertEquals('rechazada', $s->fresh()->estado);
        $this->assertNotNull($s->fresh()->motivo_rechazo);
    }
}