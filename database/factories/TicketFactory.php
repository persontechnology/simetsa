<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Ticket;
use App\Models\Vehiculo;
use App\Models\Conductor;
use App\Models\Zona;
use App\Models\Calle;
use App\Enums\MetodoPago;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $horas = $this->faker->randomElement([1, 2]);

        $compradoEn = now();
        $expiraEn = (clone $compradoEn)->addHours($horas);

        return [
            'codigo'           => sprintf('T-%d-%05d', now()->year, random_int(10000, 99999)),
            'conductor_id'     => Conductor::factory(),
            'vehiculo_id'      => Vehiculo::factory(),
            'zona_id'          => Zona::factory(),
            'calle_id'         => null,
            'horas_compradas'  => $horas,
            'monto'            => $horas * 0.25,
            'estado'           => 'pendiente',
            'metodo_pago'      => MetodoPago::Efectivo->value ?? 'efectivo',
            'es_exonerado'     => false,
            'tipo_exoneracion' => null,
            'comprado_en'      => $compradoEn,
            'expira_en'        => $expiraEn,
        ];
    }
}
