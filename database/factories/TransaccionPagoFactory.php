<?php

// database/factories/TransaccionPagoFactory.php

namespace Database\Factories;

use App\Enums\EstadoTransaccion;
use App\Enums\ProveedorPago;
use App\Models\TransaccionPago;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TransaccionPago>
 */
class TransaccionPagoFactory extends Factory
{
    protected $model = TransaccionPago::class;

    public function definition(): array
    {
        return [
            'proveedor'          => ProveedorPago::Deuna,
            'monto'              => $this->faker->randomFloat(2, 0.25, 10.00),
            'moneda'             => 'USD',
            'external_reference' => 'fake-' . Str::uuid(),
            'payment_url'        => 'https://sandbox.example.invalid/pay/fake-' . Str::uuid(),
            'qr_payload'         => null,
            'estado'             => EstadoTransaccion::Pendiente,
            'payload_request'    => null,
            'payload_response'   => null,
            'callback_recibido_en' => null,
        ];
    }

    /** Estado pendiente (default). */
    public function pendiente(): static
    {
        return $this->state(['estado' => EstadoTransaccion::Pendiente]);
    }

    /** Transacción completada con callback. */
    public function completada(): static
    {
        return $this->state([
            'estado'               => EstadoTransaccion::Completada,
            'callback_recibido_en' => now(),
            'payload_response'     => ['status' => 'approved'],
        ]);
    }

    /** Transacción fallida. */
    public function fallida(): static
    {
        return $this->state([
            'estado'           => EstadoTransaccion::Fallida,
            'payload_response' => ['status' => 'declined', 'reason' => 'insufficient_funds'],
        ]);
    }
}
