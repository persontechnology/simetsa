<?php
// database/seeders/VehiculoSeeder.php

namespace Database\Seeders;

use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

/**
 * Siembra vehículos de prueba (solo para entorno de desarrollo/testing).
 *
 * En producción los conductores registran sus propios vehículos vía la app móvil.
 */
class VehiculoSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        // No se siembran vehículos por defecto; los conductores los registran
        // desde la app móvil. Activar manualmente en entornos de demo.
        Vehiculo::factory()->count(20)->create();
    }
}
