<?php
// database/seeders/DiaFeriadoSeeder.php

namespace Database\Seeders;

use App\Models\DiaFeriado;
use Illuminate\Database\Seeder;

/**
 * Seeder de feriados del Ecuador 2026 + cantonal de Salcedo.
 * Las fechas móviles (Carnaval, Semana Santa) corresponden a 2026.
 */
class DiaFeriadoSeeder extends Seeder
{
    public function run(): void
    {
        $feriados = [
            // ===== Nacionales recurrentes =====
            ['fecha' => '2026-01-01', 'nombre' => 'Año Nuevo',                       'tipo' => DiaFeriado::TIPO_NACIONAL, 'recurrente' => true],
            ['fecha' => '2026-05-01', 'nombre' => 'Día Internacional del Trabajo',   'tipo' => DiaFeriado::TIPO_NACIONAL, 'recurrente' => true],
            ['fecha' => '2026-05-24', 'nombre' => 'Batalla de Pichincha',            'tipo' => DiaFeriado::TIPO_NACIONAL, 'recurrente' => true],
            ['fecha' => '2026-08-10', 'nombre' => 'Primer Grito de Independencia',   'tipo' => DiaFeriado::TIPO_NACIONAL, 'recurrente' => true],
            ['fecha' => '2026-10-09', 'nombre' => 'Independencia de Guayaquil',      'tipo' => DiaFeriado::TIPO_NACIONAL, 'recurrente' => true],
            ['fecha' => '2026-11-02', 'nombre' => 'Día de los Difuntos',             'tipo' => DiaFeriado::TIPO_NACIONAL, 'recurrente' => true],
            ['fecha' => '2026-12-25', 'nombre' => 'Navidad',                         'tipo' => DiaFeriado::TIPO_NACIONAL, 'recurrente' => true],

            // ===== Cívico =====
            ['fecha' => '2026-11-03', 'nombre' => 'Independencia de Cuenca',         'tipo' => DiaFeriado::TIPO_CIVICO,   'recurrente' => true],

            // ===== Móviles 2026 =====
            ['fecha' => '2026-02-16', 'nombre' => 'Carnaval (lunes)',                'tipo' => DiaFeriado::TIPO_MOVIL,    'recurrente' => false],
            ['fecha' => '2026-02-17', 'nombre' => 'Carnaval (martes)',               'tipo' => DiaFeriado::TIPO_MOVIL,    'recurrente' => false],
            ['fecha' => '2026-04-03', 'nombre' => 'Viernes Santo',                   'tipo' => DiaFeriado::TIPO_MOVIL,    'recurrente' => false],

            // ===== Cantonal Salcedo =====
            ['fecha' => '2026-09-19', 'nombre' => 'Cantonización de Salcedo',        'tipo' => DiaFeriado::TIPO_CANTONAL, 'recurrente' => true,
             'descripcion' => 'Fiestas cantonales. Días en que se declara estacionamiento prohibido por desfile (Art. 12).'],
        ];

        foreach ($feriados as $datos) {
            DiaFeriado::firstOrCreate(['fecha' => $datos['fecha']], $datos);
        }

        $this->command->info('Días feriado cargados: ' . count($feriados));
    }
}