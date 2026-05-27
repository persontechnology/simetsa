<?php

namespace Tests\Feature;

use App\Models\DocumentoPuntoVenta;
use App\Models\SolicitudPuntoVenta;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SolicitudPuntoVentaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(RolPermisoSeeder::class);
        $this->seed(UsuarioPruebaSeeder::class);
    }

    private function comisario(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function crearSolicitud(): SolicitudPuntoVenta
    {
        $this->actingAs($this->comisario())->post(route('solicitudes-punto-venta.store'), [
            'cedula' => '1710034065',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'email' => 'ana@example.com',
            'nombre_comercial' => 'Tienda Don José',
            'direccion_local' => 'Calle 24 de Mayo',
        ]);

        return SolicitudPuntoVenta::latest('id')->first();
    }

    private function subirYValidar(SolicitudPuntoVenta $s, string $tipo): void
    {
        $this->actingAs($this->comisario())->post(route('documentos-punto-venta.store', $s), [
            'tipo' => $tipo,
            'archivo' => UploadedFile::fake()->create("{$tipo}.pdf", 100, 'application/pdf'),
        ]);
        $doc = $s->documentos()->where('tipo', $tipo)->latest('id')->first();
        $this->actingAs($this->comisario())->patch(route('documentos-punto-venta.validar', $doc));
    }

    public function test_registra_solicitud_en_documentacion(): void
    {
        $s = $this->crearSolicitud();

        $this->assertSame(SolicitudPuntoVenta::ESTADO_DOCUMENTACION, $s->estado);
        $this->assertStringStartsWith('SPV-', $s->codigo);
    }

    public function test_sube_y_valida_documento(): void
    {
        $s = $this->crearSolicitud();
        $this->subirYValidar($s, DocumentoPuntoVenta::TIPO_CEDULA);

        $this->assertTrue($s->documentos()->where('tipo', 'cedula')->first()->validado);
    }

    public function test_no_aprueba_con_documentacion_incompleta(): void
    {
        $s = $this->crearSolicitud();
        $this->subirYValidar($s, DocumentoPuntoVenta::TIPO_CEDULA);

        $this->actingAs($this->comisario())
            ->post(route('solicitudes-punto-venta.aprobar-documentacion', $s))
            ->assertSessionHas('error');

        $this->assertSame(SolicitudPuntoVenta::ESTADO_DOCUMENTACION, $s->fresh()->estado);
    }

    public function test_aprueba_documentacion_completa_pasa_a_contrato(): void
    {
        $s = $this->crearSolicitud();
        foreach ([
            DocumentoPuntoVenta::TIPO_SOLICITUD_ALCALDE,
            DocumentoPuntoVenta::TIPO_CEDULA,
            DocumentoPuntoVenta::TIPO_NO_ADEUDAR,
            DocumentoPuntoVenta::TIPO_PATENTE,
        ] as $tipo) {
            $this->subirYValidar($s, $tipo);
        }

        $this->actingAs($this->comisario())
            ->post(route('solicitudes-punto-venta.aprobar-documentacion', $s))
            ->assertRedirect();

        $this->assertSame(SolicitudPuntoVenta::ESTADO_CONTRATO, $s->fresh()->estado);
    }

    public function test_rechaza_solicitud(): void
    {
        $s = $this->crearSolicitud();

        $this->actingAs($this->comisario())
            ->post(route('solicitudes-punto-venta.rechazar', $s), ['motivo_rechazo' => 'Datos incompletos'])
            ->assertRedirect();

        $this->assertSame(SolicitudPuntoVenta::ESTADO_RECHAZADA, $s->fresh()->estado);
    }
}