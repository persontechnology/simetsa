<?php
// tests/Feature/PlazaControllerTest.php

namespace Tests\Feature;

use App\Models\Plaza;
use App\Models\TipoPlaza;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\CalleSeeder;
use Database\Seeders\PlazaSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\TipoPlazaSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del CRUD de Plazas.
 *
 * Cubre seed de muestra, relaciones, autorización por rol, validación del
 * ancho (Art. 6), coherencia lat/lng y soft delete.
 */
class PlazaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            TipoPlazaSeeder::class,
            ZonaSeeder::class,
            CalleSeeder::class,
            PlazaSeeder::class,
        ]);
    }

    public function test_seeder_carga_seis_plazas(): void
    {
        $this->assertEquals(6, Plaza::count());
        $this->assertDatabaseHas('plazas', ['codigo' => 'VL-01']);
        $this->assertDatabaseHas('plazas', ['codigo' => 'VL-06']);
    }

    public function test_plaza_tiene_relaciones(): void
    {
        $plaza = Plaza::where('codigo', 'VL-01')->first();
        $this->assertEquals('Centro SIMETSA', $plaza->zona->nombre);
        $this->assertEquals('Calle Vicente León', $plaza->calle->nombre);
        $this->assertEquals('normal', $plaza->tipoPlaza->codigo);
        $this->assertTrue($plaza->tieneUbicacion());
    }

    public function test_director_seguridad_lista_plazas(): void
    {
        $u = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $this->actingAs($u)->get(route('plazas.index'))->assertOk();
    }

    public function test_conductor_no_lista_plazas(): void
    {
        $u = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $this->actingAs($u)->get(route('plazas.index'))->assertForbidden();
    }

    public function test_super_admin_crea_plaza(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::where('codigo', 'centro')->first();
        $tipo = TipoPlaza::porCodigo('normal');

        $this->actingAs($u)->post(route('plazas.store'), [
            'zona_id'       => $zona->id,
            'tipo_plaza_id' => $tipo->id,
            'codigo'        => 'VL-99',
            'numero'        => '99',
            'latitud'       => -1.0451,
            'longitud'      => -78.5912,
            'ancho_metros'  => 2.40,
            'orientacion'   => 'paralelo',
            'activo'        => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('plazas', ['codigo' => 'VL-99']);
    }

    public function test_ancho_fuera_de_rango_es_rechazado(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::where('codigo', 'centro')->first();
        $tipo = TipoPlaza::porCodigo('normal');

        $this->actingAs($u)->post(route('plazas.store'), [
            'zona_id'       => $zona->id,
            'tipo_plaza_id' => $tipo->id,
            'codigo'        => 'VL-XX',
            'ancho_metros'  => 3.00, // fuera de 2.20-2.50
            'orientacion'   => 'paralelo',
            'activo'        => 1,
        ])->assertSessionHasErrors('ancho_metros');
    }

    public function test_latitud_sin_longitud_es_rechazada(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::where('codigo', 'centro')->first();
        $tipo = TipoPlaza::porCodigo('normal');

        $this->actingAs($u)->post(route('plazas.store'), [
            'zona_id'       => $zona->id,
            'tipo_plaza_id' => $tipo->id,
            'codigo'        => 'VL-YY',
            'latitud'       => -1.0451, // sin longitud
            'orientacion'   => 'paralelo',
            'activo'        => 1,
        ])->assertSessionHasErrors('longitud');
    }

    public function test_filtrar_plazas_por_tipo(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $disc = TipoPlaza::porCodigo('discapacidad');

        $this->actingAs($u)
             ->get(route('plazas.index', ['tipo_plaza_id' => $disc->id]))
             ->assertOk()
             ->assertSee('VL-06')
             ->assertDontSee('VL-01');
    }

    public function test_destroy_hace_soft_delete(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $plaza = Plaza::first();
        $this->actingAs($u)->delete(route('plazas.destroy', $plaza))->assertRedirect();
        $this->assertSoftDeleted('plazas', ['id' => $plaza->id]);
    }
    
    /**
     * Crea una plaza con largo y verifica que persiste.
     *
     * @return void
     */
    public function test_crea_plaza_con_largo(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::where('codigo', 'centro')->first();
        $tipo = TipoPlaza::porCodigo('normal');

        $this->actingAs($u)->post(route('plazas.store'), [
            'zona_id'       => $zona->id,
            'tipo_plaza_id' => $tipo->id,
            'codigo'        => 'VL-50',
            'ancho_metros'  => 2.40,
            'largo_metros'  => 6.00,
            'orientacion'   => 'paralelo',
            'activo'        => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('plazas', ['codigo' => 'VL-50', 'largo_metros' => '6.00']);
    }

    /**
     * El largo fuera del rango práctico (3.00-15.00) es rechazado.
     *
     * @return void
     */
    public function test_largo_fuera_de_rango_es_rechazado(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::where('codigo', 'centro')->first();
        $tipo = TipoPlaza::porCodigo('normal');

        $this->actingAs($u)->post(route('plazas.store'), [
            'zona_id'       => $zona->id,
            'tipo_plaza_id' => $tipo->id,
            'codigo'        => 'VL-51',
            'largo_metros'  => 20.00, // fuera de rango
            'orientacion'   => 'paralelo',
            'activo'        => 1,
        ])->assertSessionHasErrors('largo_metros');
    }
}