<?php
// database/seeders/TarifaSeeder.php

namespace Database\Seeders;

use App\Models\Tarifa;
use App\Models\TipoPlaza;
use Illuminate\Database\Seeder;

/**
 * Seeder de tarifas iniciales del SIMETSA conforme al Art. 22 de la Ordenanza:
 * $0.25 por hora o fracción de hora, vigente desde la sanción de la
 * Ordenanza (10-feb-2020).
 *
 * Solo se crean tarifas para tipos pagados (es_pagado = true):
 *  - normal: $0.25/hora (Art. 22)
 *  - taxi:   $0.00 (paga por otra ordenanza — Art. 8)
 *  - carga:  $0.25/hora (asumido por defecto — Art. 25)
 *
 * Para 'discapacidad' y 'autoridad' NO se crea tarifa (exonerados).
 */
class TarifaSeeder extends Seeder
{
    public function run(): void
    {
        $tarifas = [
            [
                'tipo_codigo'   => TipoPlaza::COD_NORMAL,
                'nombre'        => 'Tarifa estándar SIMETSA 2020',
                'valor_hora'    => 0.25,
                'vigente_desde' => '2020-02-10',
                'vigente_hasta' => null,
                'descripcion'   => 'Tarifa oficial establecida en el Art. 22 de la Ordenanza SIMETSA.',
            ],
            [
                'tipo_codigo'   => TipoPlaza::COD_CARGA,
                'nombre'        => 'Tarifa carga/descarga 2020',
                'valor_hora'    => 0.25,
                'vigente_desde' => '2020-02-10',
                'vigente_hasta' => null,
                'descripcion'   => 'Vehículos de carga liviana en horarios autorizados por Comisaría (Art. 25).',
            ],
            [
                'tipo_codigo'   => TipoPlaza::COD_TAXI,
                'nombre'        => 'Tarifa taxis (referencial)',
                'valor_hora'    => 0.0000,
                'vigente_desde' => '2020-02-10',
                'vigente_hasta' => null,
                'descripcion'   => 'Cooperativas de taxis pagan por la Ordenanza de Ocupación de Vía Pública, no por SIMETSA (Art. 8).',
            ],
        ];

        foreach ($tarifas as $datos) {
            $tipo = TipoPlaza::porCodigo($datos['tipo_codigo']);
            if (!$tipo) {
                $this->command->warn("TipoPlaza '{$datos['tipo_codigo']}' no encontrado; salteando.");
                continue;
            }

            Tarifa::firstOrCreate(
                [
                    'tipo_plaza_id' => $tipo->id,
                    'vigente_desde' => $datos['vigente_desde'],
                ],
                [
                    'nombre'        => $datos['nombre'],
                    'valor_hora'    => $datos['valor_hora'],
                    'vigente_hasta' => $datos['vigente_hasta'],
                    'descripcion'   => $datos['descripcion'],
                    'activo'        => true,
                ]
            );
        }

        $this->command->info('Tarifas cargadas: ' . count($tarifas));
    }
}