<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VehiculoExonerado>
 */
class VehiculoExoneradoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipos = array_keys(\App\Models\VehiculoExonerado::listadoTipos());

        return [
            'placa'               => strtoupper(fake()->bothify('???-####')),
            'institucion'         => fake()->company(),
            'tipo_exoneracion'    => fake()->randomElement($tipos),
            'nombre_funcionario'  => fake()->name(),
            'numero_oficio'       => fake()->numerify('OFF-####'),
            'tiempo_maximo_horas' => 2,
            'observaciones'       => null,
            'registrado_por'      => \App\Models\User::factory(),
            'activo'              => true,
            'fecha_registro'      => now()->toDateString(),
        ];
    }

    /** Vehículo inactivo. */
    public function inactivo(): static
    {
        return $this->state(['activo' => false]);
    }
}
