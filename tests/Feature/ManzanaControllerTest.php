<?php
// tests/Feature/ManzanaControllerTest.php

namespace Tests\Feature;

use App\Models\Manzana;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\ManzanaSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del CRUD de Manzanas.
 *
 * Cubre seed de ejemplo, relación con Zona, parseo de polígono JSON,
 * autorización por rol, validación de geometría y soft delete.
 */
class ManzanaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            ZonaSeeder::class,
            ManzanaSeeder::class,
        ]);
    }

    public function test_seeder_carga_cuatro_manzanas(): void
    {
        $this->assertEquals(4, Manzana::count());
        foreach (['M01', 'M02', 'M03', 'M04'] as $codigo) {
            $this->assertDatabaseHas('manzanas', ['codigo' => $codigo]);
        }
    }

    public function test_manzanas_pertenecen_a_zona_centro(): void
    {
        $zona    = Zona::where('codigo', 'centro')->first();
        $manzana = Manzana::where('codigo', 'M01')->first();
        $this->assertEquals($zona->id, $manzana->zona_id);
        $this->assertEquals(4, $zona->manzanas()->count());
        $this->assertTrue($manzana->tieneGeometria());
    }

    public function test_director_seguridad_lista_manzanas(): void
    {
        $u = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $this->actingAs($u)->get(route('manzanas.index'))->assertOk();
    }

    public function test_conductor_no_lista_manzanas(): void
    {
        $u = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $this->actingAs($u)->get(route('manzanas.index'))->assertForbidden();
    }

    public function test_super_admin_crea_manzana_con_poligono(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::where('codigo', 'centro')->first();

        $poligono = [[-1.0445, -78.5930], [-1.0445, -78.5920], [-1.0455, -78.5920]];

        $this->actingAs($u)->post(route('manzanas.store'), [
            'zona_id'  => $zona->id,
            'codigo'   => 'M05',
            'nombre'   => 'Manzana Test',
            'color'    => '#198754',
            'activo'   => 1,
            'poligono' => json_encode($poligono),
        ])->assertRedirect();

        $manzana = Manzana::where('codigo', 'M05')->first();
        $this->assertNotNull($manzana);
        $this->assertCount(3, $manzana->poligono);
    }

    public function test_codigo_admite_mayusculas_y_guion(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::where('codigo', 'centro')->first();

        $this->actingAs($u)->post(route('manzanas.store'), [
            'zona_id'  => $zona->id,
            'codigo'   => 'MZ-99',
            'color'    => '#000000',
            'activo'   => 1,
            'poligono' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('manzanas', ['codigo' => 'MZ-99']);
    }

    public function test_poligono_con_menos_de_tres_vertices_es_rechazado(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::where('codigo', 'centro')->first();

        $this->actingAs($u)->post(route('manzanas.store'), [
            'zona_id'  => $zona->id,
            'codigo'   => 'M06',
            'color'    => '#000000',
            'activo'   => 1,
            'poligono' => json_encode([[-1.05, -78.59], [-1.06, -78.60]]),
        ])->assertSessionHasErrors('poligono');
    }

    public function test_filtrar_manzanas_por_zona(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::where('codigo', 'centro')->first();

        $this->actingAs($u)
             ->get(route('manzanas.index', ['zona_id' => $zona->id]))
             ->assertOk()
             ->assertSee('M01');
    }

    public function test_destroy_hace_soft_delete(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $manzana = Manzana::first();
        $this->actingAs($u)->delete(route('manzanas.destroy', $manzana))->assertRedirect();
        $this->assertSoftDeleted('manzanas', ['id' => $manzana->id]);
    }
}