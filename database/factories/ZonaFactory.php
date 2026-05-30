<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Zona>
 */
class ZonaFactory extends Factory
{
    protected $model = \App\Models\Zona::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo'   => 'ZN-' . $this->faker->unique()->numerify('#####'),
            'nombre'   => ucfirst($this->faker->words(2, true)),
            'descripcion' => $this->faker->sentence(),
            'poligono' => null,
            'color'    => '#0d4a8f',
            'activo'   => true,
        ];
    }
}
