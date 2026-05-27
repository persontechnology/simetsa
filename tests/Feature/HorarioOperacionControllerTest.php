<?php
// tests/Feature/HorarioOperacionControllerTest.php

namespace Tests\Feature;

use App\Models\HorarioOperacion;
use App\Models\User;
use Database\Seeders\DiaFeriadoSeeder;
use Database\Seeders\HorarioOperacionSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorarioOperacionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class, HorarioOperacionSeeder::class, DiaFeriadoSeeder::class]);
    }

    public function test_seeder_carga_los_siete_dias(): void
    {
        $this->assertEquals(7, HorarioOperacion::count());
        $this->assertEquals(5, HorarioOperacion::where('activo', true)->count()); // dom + mar-vie
    }

    public function test_lunes_y_sabado_estan_inactivos(): void
    {
        $this->assertFalse(HorarioOperacion::where('dia_semana', 1)->first()->activo);
        $this->assertFalse(HorarioOperacion::where('dia_semana', 6)->first()->activo);
    }

    public function test_editar_horario(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $martes = HorarioOperacion::where('dia_semana', 2)->first();

        $this->actingAs($u)->put(route('horarios-operacion.update', $martes), [
            'hora_inicio'=>'09:00','hora_fin'=>'17:00','activo'=>1,
        ])->assertRedirect();

        $this->assertEquals('09:00:00', $martes->fresh()->hora_inicio);
    }

    public function test_hora_fin_debe_ser_posterior_a_inicio(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $horario = HorarioOperacion::first();

        $this->actingAs($u)->put(route('horarios-operacion.update', $horario), [
            'hora_inicio'=>'18:00','hora_fin'=>'08:00','activo'=>1,
        ])->assertSessionHasErrors('hora_fin');
    }
}