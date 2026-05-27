<?php
// database/seeders/SolicitudAgenteSeeder.php

namespace Database\Seeders;

use App\Models\SolicitudAgente;
use Illuminate\Database\Seeder;

/**
 * Seeder de solicitudes de agente de ejemplo (Etapa 1).
 *
 * Crea dos postulantes en revisión de documentación, sin archivos cargados
 * (la carga se prueba desde la UI). Datos filticios para demostración.
 */
class SolicitudAgenteSeeder extends Seeder
{
    public function run(): void
    {
        $datos = [
            [
                'codigo' => 'SA-0001', 'cedula' => '0501234567',
                'nombres' => 'María Fernanda', 'apellidos' => 'Yánez Pila',
                'fecha_nacimiento' => '1995-04-12', 'nivel_educacion' => 'bachillerato',
                'telefono_celular' => '0987654321',
            ],
            [
                'codigo' => 'SA-0002', 'cedula' => '0509876543',
                'nombres' => 'Carlos Andrés', 'apellidos' => 'Toapanta Caiza',
                'fecha_nacimiento' => '1990-09-30', 'nivel_educacion' => 'superior',
                'telefono_celular' => '0998877665',
            ],
        ];

        foreach ($datos as $d) {
            SolicitudAgente::firstOrCreate(
                ['codigo' => $d['codigo']],
                array_merge($d, [
                    'estado'          => SolicitudAgente::ESTADO_DOCUMENTACION,
                    'fecha_solicitud' => now()->toDateString(),
                ])
            );
        }

        $this->command->info('Solicitudes de agente de ejemplo cargadas: ' . count($datos));
    }
}