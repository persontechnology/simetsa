<?php
// database/seeders/PlazaSeeder.php

namespace Database\Seeders;

use App\Models\Calle;
use App\Models\Plaza;
use App\Models\TipoPlaza;
use App\Models\Zona;
use Illuminate\Database\Seeder;

/**
 * Seeder de plazas de muestra sobre la Calle Vicente León, zona centro.
 *
 * La Ordenanza no enumera plazas individuales: cada plaza se señaliza en
 * campo (Art. 6). Aquí sembramos 6 plazas de ejemplo (5 normales + 1 de
 * discapacidad) con coordenadas aproximadas, para demostrar el coloreo por
 * tipo en el mapa. El administrador las reemplaza con la señalización real.
 */
class PlazaSeeder extends Seeder
{
    public function run(): void
    {
        $zona  = Zona::where('codigo', 'centro')->first();
        $calle = Calle::where('codigo', 'vicente_leon')->first();
        $normal = TipoPlaza::porCodigo('normal');
        $disc   = TipoPlaza::porCodigo('discapacidad');

        if (!$zona || !$normal) {
            $this->command->warn('Faltan dependencias (zona centro / tipo normal). Ejecutá ZonaSeeder y TipoPlazaSeeder primero.');
            return;
        }

        // 6 puntos aproximados a lo largo de una línea cerca del centro
        // VL? Vicente León, para identificar la calle en el código de plaza
        $plazas = [
            ['codigo' => 'VL-01', 'numero' => '01', 'lat' => -1.0450, 'lng' => -78.5930, 'tipo' => $normal],
            ['codigo' => 'VL-02', 'numero' => '02', 'lat' => -1.0450, 'lng' => -78.5925, 'tipo' => $normal],
            ['codigo' => 'VL-03', 'numero' => '03', 'lat' => -1.0450, 'lng' => -78.5920, 'tipo' => $normal],
            ['codigo' => 'VL-04', 'numero' => '04', 'lat' => -1.0450, 'lng' => -78.5915, 'tipo' => $normal],
            ['codigo' => 'VL-05', 'numero' => '05', 'lat' => -1.0450, 'lng' => -78.5910, 'tipo' => $normal],
            ['codigo' => 'VL-06', 'numero' => '06', 'lat' => -1.0450, 'lng' => -78.5905, 'tipo' => $disc ?? $normal],
        ];
        

        foreach ($plazas as $p) {
            Plaza::firstOrCreate(
                ['codigo' => $p['codigo']],
                [
                    'zona_id'       => $zona->id,
                    'calle_id'      => $calle?->id,
                    'manzana_id'    => null,
                    'tipo_plaza_id' => $p['tipo']->id,
                    'numero'        => $p['numero'],
                    'latitud'       => $p['lat'],
                    'longitud'      => $p['lng'],
                    'ancho_metros'  => 2.40,
                    'largo_metros'  => 5.00,
                    'orientacion'   => Plaza::ORIENTACION_PARALELO,
                    'activo'        => true,
                ]
            );
        }

        $this->command->info('Plazas de muestra cargadas: ' . count($plazas));
    }
}