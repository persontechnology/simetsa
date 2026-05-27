<?php
// database/seeders/HorarioOperacionSeeder.php

namespace Database\Seeders;

use App\Models\HorarioOperacion;
use Illuminate\Database\Seeder;

/**
 * Carga los 7 días de la semana con la configuración del Art. 12:
 *   - Martes-Viernes y Domingo: 08:00-18:00 (activos).
 *   - Lunes y Sábado: inactivos.
 */
class HorarioOperacionSeeder extends Seeder
{
    public function run(): void
    {
        $diasActivos = [0, 2, 3, 4, 5]; // dom, mar, mié, jue, vie

        foreach (range(0, 6) as $dia) {
            HorarioOperacion::firstOrCreate(
                ['dia_semana' => $dia],
                [
                    'hora_inicio' => '08:00:00',
                    'hora_fin'    => '18:00:00',
                    'activo'      => in_array($dia, $diasActivos, true),
                ]
            );
        }

        $this->command->info('Horarios de operación cargados: 7 días.');
    }
}