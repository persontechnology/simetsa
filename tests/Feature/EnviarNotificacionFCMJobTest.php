<?php

// tests/Feature/EnviarNotificacionFCMJobTest.php

namespace Tests\Feature;

use App\Jobs\EnviarNotificacionFCMJob;
use App\Models\NotificacionPush;
use App\Models\User;
use App\Services\NotificacionPushService;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests del EnviarNotificacionFCMJob y NotificacionPushService — Fase 6.B.
 */
class EnviarNotificacionFCMJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
        ]);
    }

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function crearNotificacion(User $user): NotificacionPush
    {
        return NotificacionPush::create([
            'user_id'         => $user->id,
            'tipo'            => NotificacionPush::TIPO_EXPIRA_PRONTO,
            'payload'         => ['titulo' => 'Test', 'cuerpo' => 'Mensaje de prueba', 'datos' => []],
            'programado_para' => now(),
            'enviada'         => false,
            'omitida'         => false,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Interruptor FCM_ENABLED=false
    // ────────────────────────────────────────────────────────────────────────

    public function test_job_marca_omitida_cuando_fcm_disabled(): void
    {
        config(['firebase.fcm_enabled' => false]);

        $user         = $this->conductorUser();
        $notificacion = $this->crearNotificacion($user);

        $job = new EnviarNotificacionFCMJob($notificacion);
        $job->handle(); // FCMService NO se resuelve cuando FCM_ENABLED=false

        $this->assertTrue($notificacion->fresh()->omitida);
        $this->assertFalse($notificacion->fresh()->enviada);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Sin dispositivos registrados
    // ────────────────────────────────────────────────────────────────────────

    public function test_job_marca_enviada_sin_tokens_disponibles(): void
    {
        config(['firebase.fcm_enabled' => true]);

        $user         = $this->conductorUser();
        $notificacion = $this->crearNotificacion($user);

        // Usuario sin dispositivos registrados → éxito vacío (FCMService no se llama)
        $job = new EnviarNotificacionFCMJob($notificacion);
        $job->handle();

        $fresh = $notificacion->fresh();
        $this->assertTrue($fresh->enviada);
        $this->assertNotNull($fresh->enviada_en);
        $this->assertFalse($fresh->omitida);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Fallo definitivo
    // ────────────────────────────────────────────────────────────────────────

    public function test_failed_actualiza_fallida_en_y_ultimo_error(): void
    {
        $user         = $this->conductorUser();
        $notificacion = $this->crearNotificacion($user);

        $job       = new EnviarNotificacionFCMJob($notificacion);
        $excepcion = new \RuntimeException('Token FCM inválido');
        $job->failed($excepcion);

        $fresh = $notificacion->fresh();
        $this->assertNotNull($fresh->fallida_en);
        $this->assertEquals('Token FCM inválido', $fresh->ultimo_error);
    }

    // ────────────────────────────────────────────────────────────────────────
    // NotificacionPushService::encolar() despacha el Job
    // ────────────────────────────────────────────────────────────────────────

    public function test_encolar_despacha_job_en_cola(): void
    {
        Queue::fake();

        $user    = $this->conductorUser();
        $service = app(NotificacionPushService::class);

        $service->encolar($user, NotificacionPush::TIPO_ANULADO, [
            'titulo' => 'Ticket anulado',
            'cuerpo' => 'Su ticket fue anulado.',
        ]);

        Queue::assertPushed(EnviarNotificacionFCMJob::class);
    }

    public function test_encolar_persiste_notificacion_en_base_de_datos(): void
    {
        Queue::fake();

        $user    = $this->conductorUser();
        $service = app(NotificacionPushService::class);

        $notificacion = $service->encolar($user, NotificacionPush::TIPO_EXPIRADO, [
            'titulo' => 'Ticket expirado',
            'cuerpo' => 'Su ticket ha expirado.',
        ]);

        $this->assertDatabaseHas('notificaciones_push', [
            'id'      => $notificacion->id,
            'user_id' => $user->id,
            'tipo'    => NotificacionPush::TIPO_EXPIRADO,
            'enviada' => false,
            'omitida' => false,
        ]);
    }

    public function test_encolar_con_delay_agenda_el_job(): void
    {
        Queue::fake();

        $user    = $this->conductorUser();
        $service = app(NotificacionPushService::class);

        $service->encolar(
            user: $user,
            tipo: NotificacionPush::TIPO_EXPIRA_PRONTO,
            payload: ['titulo' => 'Aviso', 'cuerpo' => 'Ticket expira pronto.'],
            minutosDesde: 10
        );

        Queue::assertPushed(EnviarNotificacionFCMJob::class);
    }
}
