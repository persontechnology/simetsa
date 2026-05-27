<?php
// database/factories/ConductorFactory.php

namespace Database\Factories;

use App\Models\Conductor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory de Conductor para pruebas y seeders.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conductor>
 */
class ConductorFactory extends Factory
{
    protected $model = Conductor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'codigo'  => 'CD-' . fake()->unique()->numerify('#####'),
            'estado'  => Conductor::ESTADO_ACTIVO,
        ];
    }
}