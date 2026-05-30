<?php

// database/factories/InfraccionFactory.php

namespace Database\Factories;

use App\Enums\EstadoInfraccion;
use App\Enums\TipoInfraccion;
use App\Models\AgenteParqueo;
use App\Models\Infraccion;
use App\Models\Zona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Infraccion>
 */
class InfraccionFactory extends Factory
{
    protected $model = Infraccion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipo = fake()->randomElement(TipoInfraccion::cases());
        $sbu  = 550.00;

        $porcentaje = $tipo->porcentajeSbu();
        $monto      = $porcentaje !== null
            ? round($sbu * $porcentaje / 100, 2)
            : round($sbu * 0.02, 2);   // fallback: mínimo Art. 28 (2%)

        return [
            'placa'             => strtoupper(fake()->regexify('[A-Z]{3}[0-9]{4}')),
            'conductor_id'      => null,
            'zona_id'           => Zona::inRandomOrder()->value('id') ?? 1,
            'calle_id'          => null,
            'agente_parqueo_id' => AgenteParqueo::inRandomOrder()->value('id') ?? 1,
            'ticket_id'         => null,
            'tipo_infraccion'   => $tipo,
            'estado'            => EstadoInfraccion::Pendiente,
            'monto_multa'       => $monto,
            'sbu_vigente'       => $sbu,
            'minutos_excedidos' => $tipo === TipoInfraccion::TiempoExcedido
                ? fake()->numberBetween(6, 200)
                : null,
            'descripcion'  => fake()->optional(0.5)->sentence(),
            'foto_evidencia' => null,
            'latitud'      => fake()->optional(0.7)->latitude(-1.0, -0.5),
            'longitud'     => fake()->optional(0.7)->longitude(-78.8, -78.5),
        ];
    }

    /** Infracción ya pagada. */
    public function pagada(): static
    {
        return $this->state(['estado' => EstadoInfraccion::Pagada]);
    }

    /** Infracción anulada administrativamente. */
    public function anulada(): static
    {
        return $this->state([
            'estado'           => EstadoInfraccion::Anulada,
            'motivo_anulacion' => fake()->sentence(),
            'anulada_en'       => now(),
        ]);
    }

    /** Infracción por tiempo excedido (Art. 17.a + Art. 28). */
    public function tiempoExcedido(int $minutos = 45): static
    {
        $sbu   = 550.00;
        $pct   = $minutos <= 60 ? 2.0 : ($minutos <= 120 ? 4.0 : 8.0);
        $monto = round($sbu * $pct / 100, 2);

        return $this->state([
            'tipo_infraccion'   => TipoInfraccion::TiempoExcedido,
            'minutos_excedidos' => $minutos,
            'monto_multa'       => $monto,
        ]);
    }
}
