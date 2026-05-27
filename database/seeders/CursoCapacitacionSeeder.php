<?php
// database/seeders/CursoCapacitacionSeeder.php

namespace Database\Seeders;

use App\Models\CursoCapacitacion;
use Illuminate\Database\Seeder;

/**
 * Seeder de una edición de curso de ejemplo (Art. 33.5).
 */
class CursoCapacitacionSeeder extends Seeder
{
    public function run(): void
    {
        CursoCapacitacion::firstOrCreate(
            ['codigo' => 'CUR-0001'],
            [
                'nombre'       => 'Curso de Agentes de Parqueo — Edición 2026-I',
                'descripcion'  => 'Capacitación obligatoria en Atención al Cliente, Primeros Auxilios y Educación Vial (Art. 33).',
                'fecha_inicio' => now()->toDateString(),
                'fecha_fin'    => now()->addDays(15)->toDateString(),
                'cupo'         => 30,
                'estado'       => CursoCapacitacion::ESTADO_PLANIFICADO,
            ]
        );

        $this->command->info('Curso de capacitación de ejemplo cargado.');
    }
}