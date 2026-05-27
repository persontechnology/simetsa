<?php
// database/seeders/OperacionAgenteSeeder.php

namespace Database\Seeders;

use App\Models\AgenteParqueo;
use App\Models\Zona;
use Illuminate\Database\Seeder;

/**
 * Seeder de operación del agente de ejemplo (AG-0001): una asignación de
 * zona y un horario rotativo, para tener datos en el expediente.
 */
class OperacionAgenteSeeder extends Seeder
{
    public function run(): void
    {
        $agente = AgenteParqueo::where('codigo', 'AG-0001')->first();
        $zona   = Zona::where('codigo', 'centro')->first();

        if (!$agente || !$zona) {
            $this->command->warn('Falta AG-0001 o la zona centro; ejecutá AgenteParqueoSeeder y ZonaSeeder.');
            return;
        }

        $agente->asignaciones()->firstOrCreate(
            ['zona_id' => $zona->id, 'fecha_inicio' => now()->toDateString()],
            ['activa' => true]
        );

        $agente->horarios()->firstOrCreate(
            ['zona_id' => $zona->id, 'dia_semana' => 2], // martes
            ['hora_inicio' => '08:00', 'hora_fin' => '18:00', 'vigente_desde' => now()->toDateString(), 'activo' => true]
        );

        $this->command->info('Operación del agente AG-0001 cargada (asignación + horario).');
    }
}