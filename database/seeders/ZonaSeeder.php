<?php
// database/seeders/ZonaSeeder.php

namespace Database\Seeders;

use App\Models\Zona;
use Illuminate\Database\Seeder;

/**
 * Seeder de la zona inicial del SIMETSA: "Centro SIMETSA", sobre la
 * Parroquia San Miguel del Cantón Salcedo (Art. 2, Art. 3).
 *
 * El polígono es un rectángulo aproximado alrededor del centro de Salcedo;
 * el administrador debe ajustarlo sobre el mapa una vez en producción.
 */
class ZonaSeeder extends Seeder
{
    public function run(): void
    {
        Zona::firstOrCreate(
            ['codigo' => 'centro'],
            [
                'nombre'      => 'Centro SIMETSA',
                'descripcion' => 'Zona tarifada sobre la Parroquia San Miguel del Cantón Salcedo. '
                               . 'Ajustar el polígono sobre el mapa según la señalización real (Art. 2, Art. 3).',
                'centro_lat'  => -1.0458000,
                'centro_lng'  => -78.5916000,
                'zoom'        => 16,
                // Rectángulo aproximado (~400 m) alrededor del centro
                'poligono'    => [
                    [-1.0440, -78.5935],
                    [-1.0440, -78.5895],
                    [-1.0478, -78.5895],
                    [-1.0478, -78.5935],
                ],
                'color'       => '#0d4a8f',
                'activo'      => true,
            ]
        );

        $this->command->info('Zona inicial cargada: Centro SIMETSA.');
    }
}