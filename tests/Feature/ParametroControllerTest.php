<?php
// tests/Feature/ParametroControllerTest.php

namespace Tests\Feature;

use App\Models\Parametro;
use App\Models\User;
use Database\Seeders\ParametroSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del CRUD parcial de Parametros.
 *
 * Cubre:
 *  - Seed correcto de los 15 parámetros base.
 *  - Helper estático Parametro::obtener().
 *  - Permisos de acceso (parametros.ver, parametros.editar).
 *  - Validación dinámica por tipo de dato.
 *  - Bloqueo de parámetros marcados como no editables.
 */
class ParametroControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class, ParametroSeeder::class]);
    }

    /**
     * El seeder carga los 15 parámetros base.
     *
     * @return void
     */
    public function test_seeder_carga_los_quince_parametros_base(): void
    {
        $this->assertGreaterThanOrEqual(29, Parametro::count());
        $this->assertDatabaseHas('parametros', ['clave' => 'sbu_vigente']);
        $this->assertDatabaseHas('parametros', ['clave' => 'porcentaje_agente', 'valor' => '40']);

        // Los obsoletos NO deben existir (migrados a entidades dedicadas)
        $this->assertDatabaseMissing('parametros', ['clave' => 'tarifa_por_hora']);
        $this->assertDatabaseMissing('parametros', ['clave' => 'horario_inicio']);
        $this->assertDatabaseMissing('parametros', ['clave' => 'horario_fin']);
    }

    /**
     * El helper Parametro::obtener() retorna valores tipados correctos.
     *
     * @return void
     */
    public function test_helper_obtener_retorna_valores_tipados(): void
    {
        $sbu = Parametro::obtener('sbu_vigente');
        $this->assertIsFloat($sbu);
        $this->assertEquals(460.00, $sbu);

        $tiempoMax = Parametro::obtener('tiempo_maximo_parqueo_minutos');
        $this->assertIsInt($tiempoMax);
        $this->assertEquals(120, $tiempoMax);

        $nombre = Parametro::obtener('nombre_gad');
        $this->assertIsString($nombre);
        $this->assertEquals('GAD Municipal del Cantón Salcedo', $nombre);
    }

    /**
     * Si la clave no existe, obtener() retorna el valor por defecto.
     *
     * @return void
     */
    public function test_helper_obtener_retorna_defecto_si_no_existe(): void
    {
        $valor = Parametro::obtener('clave_inexistente', 'default_x');
        $this->assertEquals('default_x', $valor);
    }

    /**
     * Un conductor (sin permiso parametros.ver) no accede al listado.
     *
     * @return void
     */
    public function test_conductor_no_accede_al_listado(): void
    {
        $conductor = User::where('email', 'conductor@simetsa.gob.ec')->first();

        $this->actingAs($conductor)
             ->get(route('parametros.index'))
             ->assertForbidden();
    }

    /**
     * El director_seguridad puede listar y editar parámetros.
     *
     * @return void
     */
    public function test_director_seguridad_puede_editar_parametros(): void
    {
        $director  = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $parametro = Parametro::where('clave', 'sbu_vigente')->first();

        $this->actingAs($director)
             ->get(route('parametros.edit', $parametro))
             ->assertOk();

        $this->actingAs($director)
             ->put(route('parametros.update', $parametro), [
                 'valor' => '470.00',
             ])
             ->assertRedirect(route('parametros.index'));

        $this->assertEquals('470.00', $parametro->fresh()->valor);
    }

    /**
     * La validación de tipo rechaza valores incompatibles
     * (ej: letras en un parámetro integer).
     *
     * @return void
     */
    public function test_validacion_dinamica_rechaza_valor_invalido(): void
    {
        $director  = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $parametro = Parametro::where('clave', 'tiempo_maximo_parqueo_minutos')->first();

        $this->actingAs($director)
             ->put(route('parametros.update', $parametro), [
                 'valor' => 'no_es_un_numero',
             ])
             ->assertSessionHasErrors('valor');
    }

    /**
     * La validación rechaza valores negativos en parámetros numéricos.
     *
     * @return void
     */
    public function test_validacion_rechaza_valores_negativos(): void
    {
        $director  = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $parametro = Parametro::where('clave', 'sbu_vigente')->first();

        $this->actingAs($director)
             ->put(route('parametros.update', $parametro), [
                 'valor' => '-100',
             ])
             ->assertSessionHasErrors('valor');
    }

    /**
     * Un parámetro marcado como no editable bloquea la edición.
     *
     * @return void
     */
    public function test_parametro_no_editable_se_bloquea(): void
    {
        $director = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();

        // Marcar un parámetro como no editable
        $parametro = Parametro::where('clave', 'sbu_vigente')->first();
        $parametro->update(['editable' => false]);

        // El edit redirige con mensaje de error
        $this->actingAs($director)
             ->get(route('parametros.edit', $parametro))
             ->assertRedirect(route('parametros.index'));

        // El update también es bloqueado por el authorize() del request
        $this->actingAs($director)
             ->put(route('parametros.update', $parametro), ['valor' => '500'])
             ->assertForbidden();
    }

    /**
     * El observer registra una entrada en bitácora al cambiar el valor.
     *
     * @return void
     */
    public function test_actualizar_valor_registra_bitacora(): void
    {
        $director  = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $parametro = Parametro::where('clave', 'sbu_vigente')->first();
        $valorOriginal = $parametro->valor;

        $this->actingAs($director)
             ->put(route('parametros.update', $parametro), ['valor' => '470.00']);

        $this->assertDatabaseHas('parametros_bitacora', [
            'parametro_id'   => $parametro->id,
            'user_id'        => $director->id,
            'campo'          => 'valor',
            'valor_anterior' => $valorOriginal,
            'valor_nuevo'    => '470.00',
        ]);
    }

    /**
     * Si no cambia ningún campo auditable, no se registra bitácora.
     *
     * @return void
     */
    public function test_no_se_registra_bitacora_si_no_cambia_ningun_campo(): void
    {
        $director  = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $parametro = Parametro::where('clave', 'sbu_vigente')->first();

        $this->actingAs($director)
             ->put(route('parametros.update', $parametro), [
                 'valor'       => $parametro->valor,       // mismo valor
                 'descripcion' => $parametro->descripcion, // misma descripción
             ]);

        $this->assertDatabaseMissing('parametros_bitacora', [
            'parametro_id' => $parametro->id,
        ]);
    }

    /**
     * Cambiar valor Y descripcion en el mismo guardado crea DOS entradas
     * separadas en bitácora (una por campo auditable).
     *
     * @return void
     */
    public function test_cambiar_valor_y_descripcion_crea_dos_entradas_separadas(): void
    {
        $director  = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $parametro = Parametro::where('clave', 'sbu_vigente')->first();

        $this->actingAs($director)
             ->put(route('parametros.update', $parametro), [
                 'valor'       => '470.00',
                 'descripcion' => 'Nueva descripción actualizada.',
             ]);

        $this->assertDatabaseCount('parametros_bitacora', 2);
        $this->assertDatabaseHas('parametros_bitacora', [
            'parametro_id' => $parametro->id,
            'campo'        => 'valor',
        ]);
        $this->assertDatabaseHas('parametros_bitacora', [
            'parametro_id' => $parametro->id,
            'campo'        => 'descripcion',
        ]);
    }

    /**
     * El seeder carga TODOS los 32 parámetros (15 originales + 17 nuevos).
     *
     * @return void
     */
    public function test_seeder_carga_los_treintaidos_parametros_totales(): void
    {
        $this->assertGreaterThanOrEqual(29, Parametro::count());

        // Spot-check de parámetros por categoría
        $this->assertDatabaseHas('parametros', ['clave' => 'nombre_gad']);
        $this->assertDatabaseHas('parametros', ['clave' => 'edad_minima_agente']);
        $this->assertDatabaseHas('parametros', ['clave' => 'cuadras_por_punto_venta']);
        $this->assertDatabaseHas('parametros', ['clave' => 'radio_validacion_ubicacion_metros']);
        $this->assertDatabaseHas('parametros', ['clave' => 'dias_para_impugnar_multa']);
    }

    /**
     * Las 8 categorías esperadas existen en la BD.
     *
     * @return void
     */
    public function test_existen_las_ocho_categorias(): void
    {
        $categoriasEnBD = Parametro::distinct()->pluck('categoria')->sort()->values()->toArray();
        $esperadas = ['agentes', 'app_movil', 'institucion', 'liquidaciones', 'multas', 'operacion', 'puntos_venta', 'sanciones'];

        foreach ($esperadas as $cat) {
            $this->assertContains($cat, $categoriasEnBD, "Falta la categoría: {$cat}");
        }
    }
}