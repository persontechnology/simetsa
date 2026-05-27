<?php
// database/seeders/CalleSeeder.php

namespace Database\Seeders;

use App\Models\Calle;
use App\Models\Zona;
use Illuminate\Database\Seeder;

/**
 * Seeder de las 19 calles de la zona tarifada SIMETSA, tomadas
 * textualmente del detalle del Art. 16 de la Ordenanza.
 *
 * Todas se asignan a la zona 'centro'. El sentido y el costado de
 * estacionamiento usan valores por defecto (doble sentido, costado
 * derecho según Art. 5) que el administrador debe ajustar por calle.
 * Las polilíneas se dibujan luego sobre el mapa.
 */
class CalleSeeder extends Seeder
{
    public function run(): void
    {
        $zona = Zona::where('codigo', 'centro')->first();

        if (!$zona) {
            $this->command->warn("Zona 'centro' no encontrada; ejecutá ZonaSeeder primero.");
            return;
        }

        foreach ($this->datosCalles() as $datos) {
            Calle::firstOrCreate(
                ['codigo' => $datos['codigo']],
                array_merge($datos, [
                    'zona_id'              => $zona->id,
                    'sentido'              => Calle::SENTIDO_DOBLE,
                    'lado_estacionamiento' => Calle::LADO_DERECHO,
                    'activo'               => true,
                ])
            );
        }

        $this->command->info('Calles del Art. 16 cargadas: ' . count($this->datosCalles()));
    }

    /**
     * Las 19 calles del Art. 16 con su tramo desde/hasta.
     *
     * @return array<int, array<string, string>>
     */
    private function datosCalles(): array
    {
        return [
            ['codigo' => 'belisario_quevedo', 'nombre' => 'Calle Belisario Quevedo', 'desde' => '9 de Octubre',        'hasta' => 'García Moreno'],
            ['codigo' => 'vicente_leon',      'nombre' => 'Calle Vicente León',      'desde' => '9 de Octubre',        'hasta' => 'Vicente Maldonado'],
            ['codigo' => 'sucre',             'nombre' => 'Calle Sucre',             'desde' => 'Av. Circunvalación',  'hasta' => '9 de Octubre'],
            ['codigo' => '24_de_mayo',        'nombre' => 'Calle 24 de Mayo',        'desde' => '9 de Octubre',        'hasta' => 'Vicente Maldonado'],
            ['codigo' => 'rocafuerte',        'nombre' => 'Calle Rocafuerte',        'desde' => 'Av. Circunvalación',  'hasta' => '9 de Octubre'],
            ['codigo' => 'av_olmedo',         'nombre' => 'Av. Olmedo',              'desde' => 'Mario Mogollón',      'hasta' => 'Vicente Maldonado'],
            ['codigo' => '9_de_octubre',      'nombre' => 'Calle 9 de Octubre',      'desde' => 'Belisario Quevedo',   'hasta' => 'Av. Olmedo'],
            ['codigo' => 'bolivar',           'nombre' => 'Calle Bolívar',           'desde' => 'Quito',               'hasta' => 'Av. Olmedo'],
            ['codigo' => 'garcia_moreno',     'nombre' => 'Calle García Moreno',     'desde' => 'Mejía',               'hasta' => 'Quito'],
            ['codigo' => 'gonzalez_suarez',   'nombre' => 'Calle González Suárez',   'desde' => 'Vicente León',        'hasta' => 'Av. Olmedo'],
            ['codigo' => 'padre_salcedo',     'nombre' => 'Calle Padre Salcedo',     'desde' => 'Vicente León',        'hasta' => 'Av. Olmedo'],
            ['codigo' => 'ana_paredes',       'nombre' => 'Calle Ana Paredes',       'desde' => 'Vicente León',        'hasta' => 'Av. Olmedo'],
            ['codigo' => 'juan_leon_mera',    'nombre' => 'Calle Juan León Mera',    'desde' => 'Vicente León',        'hasta' => 'Av. Olmedo'],
            ['codigo' => 'luis_a_martinez',   'nombre' => 'Calle Luis A. Martínez',  'desde' => 'Vicente León',        'hasta' => 'Av. Olmedo'],
            ['codigo' => 'ricardo_garces',    'nombre' => 'Calle Ricardo Garcés',    'desde' => 'Vicente León',        'hasta' => 'Av. Olmedo'],
            ['codigo' => 'vicente_maldonado', 'nombre' => 'Calle Vicente Maldonado', 'desde' => 'Vicente León',        'hasta' => 'Av. Olmedo'],
            ['codigo' => 'guayaquil',         'nombre' => 'Calle Guayaquil',         'desde' => 'Av. Olmedo',          'hasta' => 'Av. Jaime Mata'],
            ['codigo' => 'alejandro_salgado', 'nombre' => 'Calle Alejandro Salgado', 'desde' => 'Enriqueta Velasco',   'hasta' => 'Guayaquil'],
            ['codigo' => 'enriqueta_velasco', 'nombre' => 'Calle Enriqueta Velasco', 'desde' => 'Av. Olmedo',          'hasta' => 'Alejandro Salgado'],
        ];
    }
}