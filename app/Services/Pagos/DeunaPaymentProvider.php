<?php

// app/Services/Pagos/DeunaPaymentProvider.php

namespace App\Services\Pagos;

use App\Contracts\Cobrable;
use App\Contracts\PaymentProviderInterface;
use App\Enums\EstadoTransaccion;
use App\Enums\ProveedorPago;
use App\Models\TransaccionPago;
use DomainException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Proveedor de pagos Deuna.
 *
 * En Fase 6 opera únicamente en modo fake (DEUNA_ENABLED=false o DEUNA_MODE=fake):
 * genera transacciones simuladas sin realizar ninguna llamada HTTP externa.
 * El modo real se activa cuando se disponga de credenciales oficiales de Deuna.
 *
 * Seguridad: cuando el modo es fake, Http::fake() en tests verifica que
 * nunca se emita una llamada HTTP saliente.
 */
class DeunaPaymentProvider implements PaymentProviderInterface
{
    private bool   $habilitado;
    private string $modo;
    private string $baseUrl;
    private string $apiKey;
    private string $merchantId;
    private string $webhookSecret;

    public function __construct()
    {
        $this->habilitado    = (bool) config('pagos.deuna.enabled', false);
        $this->modo          = config('pagos.deuna.mode', 'fake');
        $this->baseUrl       = config('pagos.deuna.base_url', 'https://sandbox.example.invalid');
        $this->apiKey        = config('pagos.deuna.api_key', '');
        $this->merchantId    = config('pagos.deuna.merchant_id', '');
        $this->webhookSecret = config('pagos.deuna.webhook_secret', '');
    }

    /** {@inheritdoc} */
    public function nombre(): string
    {
        return 'deuna';
    }

    /** {@inheritdoc} */
    public function estaHabilitado(): bool
    {
        return $this->habilitado;
    }

    /**
     * {@inheritdoc}
     *
     * En modo fake: crea la TransaccionPago con datos simulados, sin HTTP.
     * En modo real (futuro): llama al endpoint de creación de orden de Deuna.
     */
    public function iniciarCobro(Cobrable $concepto, array $opciones = []): TransaccionPago
    {
        if ($this->esModoFake()) {
            return $this->iniciarCobroFake($concepto, $opciones);
        }

        return $this->iniciarCobroReal($concepto, $opciones);
    }

    /** {@inheritdoc} */
    public function consultarEstado(TransaccionPago $transaccion): TransaccionPago
    {
        if ($this->esModoFake()) {
            return $transaccion; // En fake: devuelve sin cambios
        }

        // Modo real (futuro): GET al endpoint de consulta de Deuna
        $respuesta = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/v1/orders/{$transaccion->external_reference}");

        $transaccion->update([
            'payload_response' => $respuesta->json(),
            'estado'           => $this->mapearEstado($respuesta->json('status') ?? ''),
        ]);

        return $transaccion->fresh();
    }

    /**
     * {@inheritdoc}
     *
     * En modo fake: acepta cualquier payload con external_reference válido, ignora firma.
     * En modo real: valida la firma HMAC-SHA256 antes de procesar.
     */
    public function procesarWebhook(array $payload, string $firma): TransaccionPago
    {
        if (! $this->esModoFake()) {
            $this->validarFirmaWebhook($payload, $firma);
        }

        $externalRef = $payload['order_id'] ?? $payload['external_reference'] ?? null;

        if (! $externalRef) {
            throw new DomainException('Webhook Deuna: falta el campo external_reference.');
        }

        $transaccion = TransaccionPago::where('external_reference', $externalRef)->first();

        if (! $transaccion) {
            throw new DomainException("Webhook Deuna: transacción '{$externalRef}' no encontrada.");
        }

        // Idempotencia: si ya está en estado terminal, devolver sin cambios
        if ($transaccion->estado->esTerminal()) {
            return $transaccion;
        }

        $nuevoEstado = $this->mapearEstado($payload['status'] ?? '');

        $transaccion->update([
            'estado'               => $nuevoEstado,
            'payload_response'     => $payload,
            'callback_recibido_en' => now(),
        ]);

        return $transaccion->fresh();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Privados
    // ────────────────────────────────────────────────────────────────────────

    private function esModoFake(): bool
    {
        return ! $this->habilitado || $this->modo === 'fake';
    }

    private function iniciarCobroFake(Cobrable $concepto, array $opciones): TransaccionPago
    {
        $uuid = Str::uuid()->toString();

        return TransaccionPago::create([
            'concepto_type'      => get_class($concepto),
            'concepto_id'        => $concepto->getKey(),
            'proveedor'          => ProveedorPago::Deuna,
            'monto'              => $concepto->montoCobrable(),
            'moneda'             => 'USD',
            'external_reference' => 'fake-' . $uuid,
            'payment_url'        => "https://sandbox.example.invalid/pay/fake-{$uuid}",
            'qr_payload'         => null,
            'estado'             => EstadoTransaccion::Pendiente,
            'payload_request'    => array_merge($opciones, [
                'descripcion' => $concepto->descripcionCobro(),
                'monto'       => $concepto->montoCobrable(),
                'modo'        => 'fake',
            ]),
        ]);
    }

    private function iniciarCobroReal(Cobrable $concepto, array $opciones): TransaccionPago
    {
        $body = [
            'merchant_id'  => $this->merchantId,
            'amount'       => $concepto->montoCobrable(),
            'currency'     => 'USD',
            'description'  => $concepto->descripcionCobro(),
            'redirect_url' => $opciones['redirect_url'] ?? '',
        ];

        $respuesta = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/v1/orders", $body);

        if (! $respuesta->successful()) {
            throw new DomainException(
                'Deuna: error al iniciar cobro — ' . $respuesta->body()
            );
        }

        $datos = $respuesta->json();

        return TransaccionPago::create([
            'concepto_type'      => get_class($concepto),
            'concepto_id'        => $concepto->getKey(),
            'proveedor'          => ProveedorPago::Deuna,
            'monto'              => $concepto->montoCobrable(),
            'moneda'             => 'USD',
            'external_reference' => $datos['order_id'] ?? null,
            'payment_url'        => $datos['payment_url'] ?? null,
            'qr_payload'         => $datos['qr_payload'] ?? null,
            'estado'             => EstadoTransaccion::Pendiente,
            'payload_request'    => $body,
            'payload_response'   => $datos,
        ]);
    }

    /** Mapea el estado textual del gateway al enum EstadoTransaccion. */
    private function mapearEstado(string $status): EstadoTransaccion
    {
        return match (strtolower($status)) {
            'approved', 'completed', 'success' => EstadoTransaccion::Completada,
            'processing', 'pending_payment'    => EstadoTransaccion::Procesando,
            'declined', 'failed', 'error'      => EstadoTransaccion::Fallida,
            'refunded', 'reversed'             => EstadoTransaccion::Reembolsada,
            default                            => EstadoTransaccion::Pendiente,
        };
    }

    /** Valida la firma HMAC-SHA256 del webhook de Deuna (modo real). */
    private function validarFirmaWebhook(array $payload, string $firma): void
    {
        $esperada = hash_hmac('sha256', json_encode($payload), $this->webhookSecret);

        if (! hash_equals($esperada, $firma)) {
            throw new DomainException('Webhook Deuna: firma inválida.');
        }
    }
}
