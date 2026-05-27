<?php
// tests/Feature/TipoPlazaControllerTest.php

namespace Tests\Feature;

use App\Models\TipoPlaza;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\TipoPlazaSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TipoPlazaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class, TipoPlazaSeeder::class]);
    }

    public function test_seeder_carga_cinco_tipos_de_plaza(): void
    {
        $this->assertEquals(6, TipoPlaza::count());
        foreach (['normal','discapacidad','taxi','carga','autoridad','moto'] as $c) {
            $this->assertDatabaseHas('tipos_plaza', ['codigo' => $c]);
        }
    }

    public function test_director_lista_tipos(): void
    {
        $u = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $this->actingAs($u)->get(route('tipos-plaza.index'))->assertOk();
    }

    public function test_conductor_no_lista_tipos(): void
    {
        $u = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $this->actingAs($u)->get(route('tipos-plaza.index'))->assertForbidden();
    }

    public function test_super_admin_crea_tipo(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $this->actingAs($u)->post(route('tipos-plaza.store'), [
            'codigo'=>'electrico','nombre'=>'Vehículo eléctrico','color_mapa'=>'#00ff00',
            'requiere_credencial'=>1,'es_pagado'=>1,'activo'=>1,
        ])->assertRedirect();
        $this->assertDatabaseHas('tipos_plaza', ['codigo' => 'electrico']);
    }

    public function test_validar_codigo_snake_case(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $this->actingAs($u)->post(route('tipos-plaza.store'), [
            'codigo'=>'Mi Tipo Mal','nombre'=>'Test','color_mapa'=>'#000000',
            'requiere_credencial'=>0,'es_pagado'=>1,'activo'=>1,
        ])->assertSessionHasErrors('codigo');
    }

    public function test_destroy_soft_deletes(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $tipo = TipoPlaza::first();
        $this->actingAs($u)->delete(route('tipos-plaza.destroy', $tipo))->assertRedirect();
        $this->assertSoftDeleted('tipos_plaza', ['id' => $tipo->id]);
    }
    /**
     * El backfill de la migración deja dimensiones sugeridas en los tipos estándar.
     *
     * @return void
     */
    public function test_tipos_estandar_tienen_dimensiones_sugeridas(): void
    {
        $normal = \App\Models\TipoPlaza::porCodigo('normal');
        $carga  = \App\Models\TipoPlaza::porCodigo('carga');

        $this->assertEquals('2.40', $normal->ancho_sugerido);
        $this->assertEquals('5.00', $normal->largo_sugerido);
        $this->assertEquals('8.00', $carga->largo_sugerido);
        $this->assertEquals('2.40 × 5.00 m', $normal->dimensiones_sugeridas);
    }

    /**
     * El ancho sugerido fuera del rango del Art. 6 (2.20-2.50) es rechazado.
     * Ajustá los campos del payload a los que exige tu TipoPlazaStoreRequest.
     *
     * @return void
     */
    public function test_ancho_sugerido_fuera_de_rango_es_rechazado(): void
    {
        $u = \App\Models\User::where('email', 'admin@simetsa.gob.ec')->first();

        $this->actingAs($u)->post(route('tipos-plaza.store'), [
            'codigo'            => 'prueba_dim',
            'nombre'            => 'Prueba dimensiones',
            'color_mapa'        => '#123456',
            'requiere_credencial' => 0,
            'es_pagado'         => 1,
            'activo'            => 1,
            'ancho_sugerido'    => 3.00, // fuera de 2.20-2.50
            'largo_sugerido'    => 5.00,
        ])->assertSessionHasErrors('ancho_sugerido');
    }
}