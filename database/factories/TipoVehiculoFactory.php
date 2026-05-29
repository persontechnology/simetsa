<?php
// database/factories/TipoVehiculoFactory.php

namespace Database\Factories;

use App\Models\TipoVehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TipoVehiculo>
 */
class TipoVehiculoFactory extends Factory
{
    protected $model = TipoVehiculo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo'        => fake()->unique()->regexify('[a-z]{4,10}'),
            'nombre'        => fake()->words(3, true),
            'descripcion'   => fake()->sentence(),
            'aplica_tarifa' => true,
            'activo'        => true,
        ];
    }
}
