<?php
// tests/Feature/DiaFeriadoControllerTest.php

namespace Tests\Feature;

use App\Models\DiaFeriado;
use App\Models\User;
use Database\Seeders\DiaFeriadoSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiaFeriadoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class, DiaFeriadoSeeder::class]);
    }

    public function test_seeder_carga_feriados_de_2026(): void
    {
        $this->assertGreaterThanOrEqual(12, DiaFeriado::whereYear('fecha', 2026)->count());
        $this->assertDatabaseHas('dias_feriado', ['nombre' => 'Cantonización de Salcedo']);
    }

    public function test_es_feriado_detecta_anio_nuevo_2030(): void
    {
        $this->assertTrue(DiaFeriado::esFeriado(\Carbon\Carbon::parse('2030-01-01')));
    }

    public function test_es_feriado_no_detecta_dia_normal(): void
    {
        $this->assertFalse(DiaFeriado::esFeriado(\Carbon\Carbon::parse('2026-07-15')));
    }

    public function test_crear_feriado(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $this->actingAs($u)->post(route('dias-feriado.store'), [
            'fecha'=>'2026-12-31','nombre'=>'Fin de año','tipo'=>'nacional',
            'recurrente'=>1,'activo'=>1,
        ])->assertRedirect();
    }

    public function test_filtrar_por_ano_y_tipo(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $this->actingAs($u)
             ->get(route('dias-feriado.index', ['ano' => 2026, 'tipo' => 'cantonal']))
             ->assertOk()
             ->assertSee('Cantonización de Salcedo')
             ->assertDontSee('Año Nuevo');
    }
}