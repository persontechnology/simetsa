<?php
// tests/Feature/CredencialDiscapacidadApiTest.php

namespace Tests\Feature;

use App\Models\Conductor;
use App\Models\CredencialDiscapacidad;
use App\Models\TipoVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\TipoVehiculoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests de credenciales CONADIS (Fase 4.C — Art. 26 Ordenanza SIMETSA).
 */
class CredencialDiscapacidadApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class, TipoVehiculoSeeder::class]);
    }

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function comisarioUser(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function crearConductor(?User $user = null): Conductor
    {
        $user ??= $this->conductorUser();

        return Conductor::firstOrCreate(
            ['user_id' => $user->id],
            ['codigo' => 'CD-' . str_pad((string) ($user->id + 90000), 5, '0', STR_PAD_LEFT), 'estado' => Conductor::ESTADO_ACTIVO],
        );
    }

    private function crearVehiculo(?Conductor $conductor = null): Vehiculo
    {
        $conductor ??= $this->crearConductor();

        return Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => TipoVehiculo::where('codigo', 'liviano_privado')->first()->id,
        ]);
    }

    private function token(User $user): string
    {
        return $user->createToken('movil')->plainTextToken;
    }

    private function datosCredencial(): array
    {
        return [
            'numero_conadis'          => '17-AB12-CONADIS',
            'nombre_beneficiario'     => 'Pedro Tigse Caisaguano',
            'fecha_emision'           => '2023-01-15',
            'porcentaje_discapacidad' => 45,
        ];
    }

    // ===== API — Autenticación =====

    public function test_registrar_credencial_requiere_autenticacion(): void
    {
        $vehiculo = $this->crearVehiculo();

        $this->postJson("/api/v1/vehiculos/{$vehiculo->id}/credencial", [])->assertUnauthorized();
    }

    // ===== API — Registro =====

    public function test_conductor_puede_registrar_credencial_sin_archivo(): void
    {
        $vehiculo = $this->crearVehiculo();

        $response = $this->withToken($this->token($this->conductorUser()))
            ->postJson("/api/v1/vehiculos/{$vehiculo->id}/credencial", $this->datosCredencial())
            ->assertCreated();

        $this->assertTrue($response->json('exito'));
        $this->assertEquals('pendiente', $response->json('datos.estado'));
        $this->assertDatabaseHas('credenciales_discapacidad', [
            'vehiculo_id'    => $vehiculo->id,
            'numero_conadis' => '17-AB12-CONADIS',
        ]);
    }

    public function test_conductor_puede_registrar_credencial_con_archivo(): void
    {
        Storage::fake('public');
        $vehiculo = $this->crearVehiculo();

        $response = $this->withToken($this->token($this->conductorUser()))
            ->post("/api/v1/vehiculos/{$vehiculo->id}/credencial", array_merge(
                $this->datosCredencial(),
                ['archivo' => UploadedFile::fake()->create('credencial.pdf', 200, 'application/pdf')],
            ), ['Accept' => 'application/json']);

        $response->assertCreated();
        $this->assertNotNull($response->json('datos.url_archivo'));
    }

    public function test_segunda_credencial_activa_del_mismo_vehiculo_falla(): void
    {
        $vehiculo = $this->crearVehiculo();

        CredencialDiscapacidad::factory()->create([
            'vehiculo_id' => $vehiculo->id,
            'estado'      => CredencialDiscapacidad::ESTADO_PENDIENTE,
        ]);

        $this->withToken($this->token($this->conductorUser()))
            ->postJson("/api/v1/vehiculos/{$vehiculo->id}/credencial", $this->datosCredencial())
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);
    }

    public function test_puede_registrar_nueva_credencial_si_anterior_fue_rechazada(): void
    {
        $vehiculo = $this->crearVehiculo();

        CredencialDiscapacidad::factory()->rechazada()->create(['vehiculo_id' => $vehiculo->id]);

        $this->withToken($this->token($this->conductorUser()))
            ->postJson("/api/v1/vehiculos/{$vehiculo->id}/credencial", $this->datosCredencial())
            ->assertCreated();
    }

    public function test_conductor_no_puede_registrar_credencial_en_vehiculo_ajeno(): void
    {
        $this->crearConductor(); // ensure conductor record for conductorUser()

        $otroConductor = Conductor::factory()->create();
        $vehiculoAjeno = $this->crearVehiculo($otroConductor);

        $this->withToken($this->token($this->conductorUser()))
            ->postJson("/api/v1/vehiculos/{$vehiculoAjeno->id}/credencial", $this->datosCredencial())
            ->assertForbidden();
    }

    // ===== API — Consulta =====

    public function test_conductor_puede_ver_credencial_de_su_vehiculo(): void
    {
        $vehiculo = $this->crearVehiculo();

        CredencialDiscapacidad::factory()->create([
            'vehiculo_id'    => $vehiculo->id,
            'numero_conadis' => '17-TESTCONADIS',
        ]);

        $response = $this->withToken($this->token($this->conductorUser()))
            ->getJson("/api/v1/vehiculos/{$vehiculo->id}/credencial")
            ->assertOk();

        $this->assertEquals('17-TESTCONADIS', $response->json('datos.numero_conadis'));
    }

    public function test_conductor_no_puede_ver_credencial_de_vehiculo_ajeno(): void
    {
        $this->crearConductor();

        $otroConductor = Conductor::factory()->create();
        $vehiculoAjeno = $this->crearVehiculo($otroConductor);
        CredencialDiscapacidad::factory()->create(['vehiculo_id' => $vehiculoAjeno->id]);

        $this->withToken($this->token($this->conductorUser()))
            ->getJson("/api/v1/vehiculos/{$vehiculoAjeno->id}/credencial")
            ->assertForbidden();
    }

    public function test_vehiculo_sin_credencial_retorna_404(): void
    {
        $vehiculo = $this->crearVehiculo();

        $this->withToken($this->token($this->conductorUser()))
            ->getJson("/api/v1/vehiculos/{$vehiculo->id}/credencial")
            ->assertNotFound();
    }

    // ===== Web backoffice — Aprobar / Rechazar =====

    public function test_comisario_puede_aprobar_credencial_pendiente(): void
    {
        $vehiculo = $this->crearVehiculo();
        $credencial = CredencialDiscapacidad::factory()->create([
            'vehiculo_id' => $vehiculo->id,
            'estado'      => CredencialDiscapacidad::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($this->comisarioUser())
            ->patch(route('credenciales-discapacidad.aprobar', $credencial))
            ->assertRedirect();

        $this->assertEquals(CredencialDiscapacidad::ESTADO_APROBADA, $credencial->fresh()->estado);
        $this->assertNotNull($credencial->fresh()->aprobada_por);
    }

    public function test_comisario_puede_rechazar_credencial_pendiente(): void
    {
        $vehiculo = $this->crearVehiculo();
        $credencial = CredencialDiscapacidad::factory()->create([
            'vehiculo_id' => $vehiculo->id,
            'estado'      => CredencialDiscapacidad::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($this->comisarioUser())
            ->patch(route('credenciales-discapacidad.rechazar', $credencial), [
                'observaciones' => 'Número CONADIS no corresponde al beneficiario declarado.',
            ])
            ->assertRedirect();

        $this->assertEquals(CredencialDiscapacidad::ESTADO_RECHAZADA, $credencial->fresh()->estado);
    }

    public function test_rechazar_sin_observaciones_redirige_con_error(): void
    {
        $vehiculo = $this->crearVehiculo();
        $credencial = CredencialDiscapacidad::factory()->create([
            'vehiculo_id' => $vehiculo->id,
            'estado'      => CredencialDiscapacidad::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($this->comisarioUser())
            ->patch(route('credenciales-discapacidad.rechazar', $credencial), [
                'observaciones' => '',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals(CredencialDiscapacidad::ESTADO_PENDIENTE, $credencial->fresh()->estado);
    }

    public function test_conductor_no_puede_aprobar_credencial(): void
    {
        $vehiculo = $this->crearVehiculo();
        $credencial = CredencialDiscapacidad::factory()->create([
            'vehiculo_id' => $vehiculo->id,
        ]);

        $this->actingAs($this->conductorUser())
            ->patch(route('credenciales-discapacidad.aprobar', $credencial))
            ->assertForbidden();
    }
}
