<?php
// tests/Feature/ZonaControllerTest.php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Zona;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del CRUD de Zonas.
 *
 * Cubre seed inicial, parseo del polígono JSON, autorización por rol,
 * validación de geometría y soft delete.
 */
class ZonaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class, ZonaSeeder::class]);
    }

    public function test_seeder_carga_zona_centro(): void
    {
        $this->assertDatabaseHas('zonas', ['codigo' => 'centro']);
        $zona = Zona::porCodigoCentro();
        $this->assertTrue($zona->tieneGeometria());
        $this->assertCount(4, $zona->poligono);
    }

    public function test_director_seguridad_lista_zonas(): void
    {
        $u = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $this->actingAs($u)->get(route('zonas.index'))->assertOk();
    }

    public function test_conductor_no_lista_zonas(): void
    {
        $u = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $this->actingAs($u)->get(route('zonas.index'))->assertForbidden();
    }

    public function test_super_admin_crea_zona_con_poligono_json(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();

        $poligono = [[-1.0440, -78.5935], [-1.0440, -78.5895], [-1.0478, -78.5895]];

        $this->actingAs($u)->post(route('zonas.store'), [
            'codigo'     => 'norte',
            'nombre'     => 'Zona Norte',
            'centro_lat' => -1.0440,
            'centro_lng' => -78.5910,
            'zoom'       => 16,
            'color'      => '#198754',
            'activo'     => 1,
            'poligono'   => json_encode($poligono), // como llega del editor
        ])->assertRedirect();

        $zona = Zona::where('codigo', 'norte')->first();
        $this->assertNotNull($zona);
        $this->assertCount(3, $zona->poligono);
        $this->assertEquals(-1.0440, $zona->poligono[0][0]);
    }

    public function test_crear_zona_sin_poligono_es_valido(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $this->actingAs($u)->post(route('zonas.store'), [
            'codigo'     => 'sur',
            'nombre'     => 'Zona Sur',
            'centro_lat' => -1.05,
            'centro_lng' => -78.59,
            'zoom'       => 16,
            'color'      => '#dc3545',
            'activo'     => 1,
            'poligono'   => '', // sin geometría todavía
        ])->assertRedirect();

        $this->assertDatabaseHas('zonas', ['codigo' => 'sur']);
        $this->assertFalse(Zona::where('codigo', 'sur')->first()->tieneGeometria());
    }

    public function test_poligono_con_menos_de_tres_vertices_es_rechazado(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $this->actingAs($u)->post(route('zonas.store'), [
            'codigo'     => 'mini',
            'nombre'     => 'Zona Mini',
            'centro_lat' => -1.05,
            'centro_lng' => -78.59,
            'zoom'       => 16,
            'color'      => '#000000',
            'activo'     => 1,
            'poligono'   => json_encode([[-1.05, -78.59], [-1.06, -78.60]]), // solo 2
        ])->assertSessionHasErrors('poligono');
    }

    public function test_destroy_hace_soft_delete(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $zona = Zona::first();
        $this->actingAs($u)->delete(route('zonas.destroy', $zona))->assertRedirect();
        $this->assertSoftDeleted('zonas', ['id' => $zona->id]);
    }
}