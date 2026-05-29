<?php
// database/seeders/TipoVehiculoSeeder.php

namespace Database\Seeders;

use App\Models\TipoVehiculo;
use Illuminate\Database\Seeder;

/**
 * Catálogo base de tipos de vehículo según Art. 25 de la Ordenanza SIMETSA.
 * Idempotente: usa firstOrCreate.
 */
class TipoVehiculoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'codigo'        => TipoVehiculo::COD_LIVIANO_PRIVADO,
                'nombre'        => 'Liviano privado',
                'descripcion'   => 'Vehículo liviano de uso particular. Sujeto al pago de tarifa SIMETSA (Art. 25).',
                'aplica_tarifa' => true,
                'activo'        => true,
            ],
            [
                'codigo'        => TipoVehiculo::COD_LIVIANO_PUBLICO,
                'nombre'        => 'Liviano de servicio público',
                'descripcion'   => 'Furgonetas de turismo e institucionales de uso público (Art. 25).',
                'aplica_tarifa' => true,
                'activo'        => true,
            ],
            [
                'codigo'        => TipoVehiculo::COD_TAXI,
                'nombre'        => 'Taxi',
                'descripcion'   => 'Taxi convencional y ejecutivo. Sujeto al pago SIMETSA (Art. 25).',
                'aplica_tarifa' => true,
                'activo'        => true,
            ],
            [
                'codigo'        => TipoVehiculo::COD_FURGONETA,
                'nombre'        => 'Furgoneta de alquiler',
                'descripcion'   => 'Furgoneta de alquiler y camionetas livianas (Art. 25).',
                'aplica_tarifa' => true,
                'activo'        => true,
            ],
            [
                'codigo'        => TipoVehiculo::COD_CARGA_LIVIANA,
                'nombre'        => 'Carga liviana',
                'descripcion'   => 'Vehículos de carga liviana. Pueden usar plazas SIMETSA para carga/descarga previo pago en horario establecido por el Comisario (Art. 25).',
                'aplica_tarifa' => true,
                'activo'        => true,
            ],
            [
                'codigo'        => TipoVehiculo::COD_INSTITUCIONAL,
                'nombre'        => 'Institucional',
                'descripcion'   => 'Vehículos institucionales de entidades públicas. La exoneración aplica solo si están registrados en VehiculoExonerado (Art. 27).',
                'aplica_tarifa' => true,
                'activo'        => true,
            ],
        ];

        foreach ($tipos as $datos) {
            TipoVehiculo::firstOrCreate(['codigo' => $datos['codigo']], $datos);
        }

        $this->command->info('Tipos de vehículo cargados: ' . count($tipos));
    }
}
