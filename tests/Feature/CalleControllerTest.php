<?php
// tests/Feature/CalleControllerTest.php

namespace Tests\Feature;

use App\Models\Calle;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\CalleSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del CRUD de Calles.
 *
 * Cubre seed del Art. 16, relación con Zona, parseo de polilínea JSON,
 * autorización por rol, validación de geometría y soft delete.
 */
class CalleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            ZonaSeeder::class,
            CalleSeeder::class,
        ]);
    }

    public function test_seeder_carga_las_diecinueve_calles(): void
    {
        $this->assertEquals(19, Calle::count());
        $this->assertDatabaseHas('calles', ['codigo' => 'vicente_leon']);
        $this->assertDatabaseHas('calles', ['codigo' => '24_de_mayo']);
        $this->assertDatabaseHas('calles', ['codigo' => '9_de_octubre']);
    }

    public function test_calles_pertenecen_a_zona_centro(): void
    {
        $zona  = Zona::where('codigo', 'centro')->first();
        $calle = Calle::where('codigo', 'sucre')->first();
        $this->assertEquals($zona->id, $calle->zona_id);
        $this->assertEquals('Centro SIMETSA', $calle->zona->nombre);
        $this->assertEquals(19, $zona->calles()->count());
    }

    public function test_director_seguridad_lista_calles(): void
    {
        $u = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $this->actingAs($u)->get(route('calles.index'))->assertOk();
    }

    public function test_conductor_no_lista_calles(): void
    {
        $u = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $this->actingAs($u)->get(route('calles.index'))->assertForbidden();
    }

    public function test_super_admin_crea_calle_con_polilinea(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::where('codigo', 'centro')->first();

        $polilinea = [[-1.0450, -78.5920], [-1.0455, -78.5910], [-1.0460, -78.5900]];

        $this->actingAs($u)->post(route('calles.store'), [
            'zona_id'              => $zona->id,
            'codigo'               => 'nueva_calle',
            'nombre'               => 'Calle Nueva',
            'desde'                => 'A',
            'hasta'                => 'B',
            'sentido'              => 'unico',
            'lado_estacionamiento' => 'izquierdo',
            'activo'               => 1,
            'polilinea'            => json_encode($polilinea),
        ])->assertRedirect();

        $calle = Calle::where('codigo', 'nueva_calle')->first();
        $this->assertNotNull($calle);
        $this->assertCount(3, $calle->polilinea);
        $this->assertEquals('unico', $calle->sentido);
    }

    public function test_crear_calle_sin_polilinea_es_valido(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::where('codigo', 'centro')->first();

        $this->actingAs($u)->post(route('calles.store'), [
            'zona_id'              => $zona->id,
            'codigo'               => 'sin_trazo',
            'nombre'               => 'Calle Sin Trazo',
            'sentido'              => 'doble',
            'lado_estacionamiento' => 'derecho',
            'activo'               => 1,
            'polilinea'            => '',
        ])->assertRedirect();

        $this->assertFalse(Calle::where('codigo', 'sin_trazo')->first()->tieneGeometria());
    }

    public function test_polilinea_con_un_solo_punto_es_rechazada(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::where('codigo', 'centro')->first();

        $this->actingAs($u)->post(route('calles.store'), [
            'zona_id'              => $zona->id,
            'codigo'               => 'un_punto',
            'nombre'               => 'Calle Un Punto',
            'sentido'              => 'doble',
            'lado_estacionamiento' => 'derecho',
            'activo'               => 1,
            'polilinea'            => json_encode([[-1.05, -78.59]]),
        ])->assertSessionHasErrors('polilinea');
    }

    public function test_filtrar_calles_por_zona(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::where('codigo', 'centro')->first();

        $this->actingAs($u)
             ->get(route('calles.index', ['zona_id' => $zona->id]))
             ->assertOk()
             ->assertSee('Calle Vicente León');
    }

    public function test_destroy_hace_soft_delete(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $calle = Calle::first();
        $this->actingAs($u)->delete(route('calles.destroy', $calle))->assertRedirect();
        $this->assertSoftDeleted('calles', ['id' => $calle->id]);
    }
}