<?php
// database/seeders/TipoPlazaSeeder.php

namespace Database\Seeders;

use App\Models\TipoPlaza;
use Illuminate\Database\Seeder;

/**
 * Seeder de los 5 tipos de plaza base del SIMETSA, conforme a la Ordenanza.
 * Idempotente: usa firstOrCreate.
 */
class TipoPlazaSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'codigo'              => TipoPlaza::COD_NORMAL,
                'nombre'              => 'Plaza normal',
                'descripcion'         => 'Plaza estándar de estacionamiento tarifado. Aplica tarifa SIMETSA por hora.',
                'requiere_credencial' => false,
                'es_pagado'           => true,
                'color_mapa'          => '#1d6fb8', // azul institucional
                'icono'               => 'bi-car-front',
                'ancho_sugerido'      => 2.40,
                'largo_sugerido'      => 5.00,
            ],
            [
                'codigo'              => TipoPlaza::COD_DISCAPACIDAD,
                'nombre'              => 'Plaza para personas con discapacidad',
                'descripcion'         => 'Exonerada de pago. Requiere credencial CONADIS visible. Junto a rampas de acceso (Art. 5, Art. 26).',
                'requiere_credencial' => true,
                'es_pagado'           => false,
                'color_mapa'          => '#198754', // verde
                'icono'               => 'bi-universal-access',
                'ancho_sugerido'      => 2.50,
                'largo_sugerido'      => 5.00,
            ],
            [
                'codigo'              => TipoPlaza::COD_TAXI,
                'nombre'              => 'Cooperativa de taxis',
                'descripcion'         => 'Espacios asignados a cooperativas de taxis y camionetas de alquiler. Tributan por la Ordenanza de Ocupación de Vía Pública, no por SIMETSA (Art. 8).',
                'requiere_credencial' => true,
                'es_pagado'           => true,
                'color_mapa'          => '#f0a500', // amarillo señalización
                'icono'               => 'bi-taxi-front',
                'ancho_sugerido'      => 2.40,
                'largo_sugerido'      => 5.50,
            ],
            [
                'codigo'              => TipoPlaza::COD_CARGA,
                'nombre'              => 'Carga y descarga',
                'descripcion'         => 'Para vehículos de carga liviana en horarios definidos por Comisaría de Higiene y Salubridad (Art. 25).',
                'requiere_credencial' => false,
                'es_pagado'           => true,
                'color_mapa'          => '#6c757d', // gris
                'icono'               => 'bi-truck',
                'ancho_sugerido'      => 2.50,
                'largo_sugerido'      => 8.00,
            ],
            [
                'codigo'              => TipoPlaza::COD_AUTORIDAD,
                'nombre'              => 'Autoridades y emergencia',
                'descripcion'         => 'Vehículos especiales: Bomberos, Ambulancias, Policía, FF.AA., autoridades. Uso temporal exonerado (Art. 27).',
                'requiere_credencial' => true,
                'es_pagado'           => false,
                'color_mapa'          => '#dc3545', // rojo
                'icono'               => 'bi-shield-fill-check',
                'ancho_sugerido'      => 2.40,
                'largo_sugerido'      => 5.50,
            ],
            [
                'codigo'              => TipoPlaza::COD_MOTO,
                'nombre'              => 'Plaza para motos',
                'descripcion'         => 'Plaza destinada a motocicletas. Puede ser tarifada o gratuita según la regulación local.',
                'requiere_credencial' => false,
                'es_pagado'           => true,
                'color_mapa'          => '#6f42c1', // morado
                'icono'               => 'bi-bicycle',
                'ancho_sugerido'      => 1.20,
                'largo_sugerido'      => 2.50,
            ],
        ];

        foreach ($tipos as $datos) {
            TipoPlaza::firstOrCreate(['codigo' => $datos['codigo']], $datos);
        }

        $this->command->info('Tipos de plaza cargados: ' . count($tipos));
    }
}