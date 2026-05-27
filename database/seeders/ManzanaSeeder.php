<?php
// database/seeders/ManzanaSeeder.php

namespace Database\Seeders;

use App\Models\Manzana;
use App\Models\Zona;
use Illuminate\Database\Seeder;

/**
 * Seeder de manzanas de ejemplo para la zona 'centro'.
 *
 * La Ordenanza (Art. 10) no enumera manzanas concretas: su codificación
 * corresponde a la Dirección de Seguridad Ciudadana. Aquí sembramos 4
 * cuadrantes APROXIMADOS (M01-M04) que subdividen el polígono de la zona
 * centro, únicamente como punto de partida visual. El administrador debe
 * reemplazarlos con la codificación y los límites reales.
 */
class ManzanaSeeder extends Seeder
{
    public function run(): void
    {
        $zona = Zona::where('codigo', 'centro')->first();

        if (!$zona) {
            $this->command->warn("Zona 'centro' no encontrada; ejecutá ZonaSeeder primero.");
            return;
        }

        // Cuadrantes aproximados sobre el rectángulo de la zona centro
        $manzanas = [
            [
                'codigo' => 'M01', 'nombre' => 'Manzana noroeste',
                'poligono' => [[-1.0440, -78.5935], [-1.0440, -78.5915], [-1.0459, -78.5915], [-1.0459, -78.5935]],
            ],
            [
                'codigo' => 'M02', 'nombre' => 'Manzana noreste',
                'poligono' => [[-1.0440, -78.5915], [-1.0440, -78.5895], [-1.0459, -78.5895], [-1.0459, -78.5915]],
            ],
            [
                'codigo' => 'M03', 'nombre' => 'Manzana suroeste',
                'poligono' => [[-1.0459, -78.5935], [-1.0459, -78.5915], [-1.0478, -78.5915], [-1.0478, -78.5935]],
            ],
            [
                'codigo' => 'M04', 'nombre' => 'Manzana sureste',
                'poligono' => [[-1.0459, -78.5915], [-1.0459, -78.5895], [-1.0478, -78.5895], [-1.0478, -78.5915]],
            ],
        ];

        foreach ($manzanas as $datos) {
            Manzana::firstOrCreate(
                ['codigo' => $datos['codigo']],
                array_merge($datos, [
                    'zona_id'     => $zona->id,
                    'descripcion' => 'Cuadrante aproximado de ejemplo. Reemplazar con la codificación real (Art. 10).',
                    'color'       => '#6c757d',
                    'activo'      => true,
                ])
            );
        }

        $this->command->info('Manzanas de ejemplo cargadas: ' . count($manzanas));
    }
}