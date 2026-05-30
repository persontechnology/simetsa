<?php

// database/factories/InmovilizacionFactory.php

namespace Database\Factories;

use App\Enums\EstadoInmovilizacion;
use App\Models\AgenteParqueo;
use App\Models\Infraccion;
use App\Models\Inmovilizacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inmovilizacion>
 */
class InmovilizacionFactory extends Factory
{
    protected $model = Inmovilizacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'infraccion_id'    => Infraccion::factory(),
            'agente_parqueo_id'=> AgenteParqueo::inRandomOrder()->value('id') ?? 1,
            'estado'           => EstadoInmovilizacion::Activa,
            'foto_candado'     => null,
            'notas'            => fake()->optional(0.4)->sentence(),
            'inmovilizada_en'  => now(),
            'liberada_en'      => null,
        ];
    }

    /** Inmovilización liberada tras pago (Art. 15). */
    public function liberada(): static
    {
        return $this->state([
            'estado'      => EstadoInmovilizacion::Liberada,
            'liberada_en' => now()->addMinutes(fake()->numberBetween(5, 120)),
        ]);
    }

    /** Inmovilización anulada administrativamente. */
    public function anulada(): static
    {
        return $this->state([
            'estado'           => EstadoInmovilizacion::Anulada,
            'motivo_anulacion' => fake()->sentence(),
        ]);
    }
}
