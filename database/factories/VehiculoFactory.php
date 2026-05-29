<?php
// database/factories/VehiculoFactory.php

namespace Database\Factories;

use App\Models\Conductor;
use App\Models\TipoVehiculo;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehiculo>
 */
class VehiculoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $letras = strtoupper(fake()->unique()->lexify('???'));
        $numeros = fake()->numerify('####');

        return [
            'conductor_id'     => Conductor::factory(),
            'tipo_vehiculo_id' => TipoVehiculo::factory(),
            'placa'            => "{$letras}-{$numeros}",
            'marca'            => fake()->randomElement(['Toyota', 'Chevrolet', 'Hyundai', 'Kia', 'Nissan', 'Ford']),
            'modelo'           => fake()->word(),
            'anio'             => fake()->numberBetween(1990, (int) date('Y')),
            'color'            => fake()->safeColorName(),
            'estado'           => Vehiculo::ESTADO_ACTIVO,
            'observaciones'    => null,
        ];
    }

    /** Estado inactivo. */
    public function inactivo(): static
    {
        return $this->state(['estado' => Vehiculo::ESTADO_INACTIVO]);
    }
}
