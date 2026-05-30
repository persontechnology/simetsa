<?php

// database/migrations/2026_05_29_202218_create_transaccion_pagos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla polimórfica de transacciones de pago (Fase 6.A).
 *
 * Registra cada intento de cobro a un gateway digital.
 * El campo concepto_type/concepto_id permite cobrar Tickets hoy
 * y Multas (Fase 7) u otras entidades en el futuro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacciones_pago', function (Blueprint $table) {
            $table->id();

            /** Entidad cobrada (polimórfica): App\Models\Ticket, App\Models\Multa, etc. */
            $table->string('concepto_type');
            $table->unsignedBigInteger('concepto_id');

            /** Proveedor gateway (App\Enums\ProveedorPago). */
            $table->string('proveedor', 20);

            $table->decimal('monto', 8, 2);
            $table->char('moneda', 3)->default('USD');

            /** ID de la transacción en el sistema del gateway (único por proveedor). */
            $table->string('external_reference')->nullable()->unique();

            /** URL de pago generada por el gateway (para redirect). */
            $table->text('payment_url')->nullable();

            /** Payload QR en base64 o SVG (para integración QR). */
            $table->text('qr_payload')->nullable();

            /** Estado del pago (App\Enums\EstadoTransaccion). */
            $table->string('estado', 20)->default('pendiente');

            /** Payload enviado al gateway (para auditoría). */
            $table->json('payload_request')->nullable();

            /** Respuesta recibida del gateway (para auditoría y debug). */
            $table->json('payload_response')->nullable();

            /** Momento en que se recibió el callback/webhook del gateway. */
            $table->timestamp('callback_recibido_en')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['concepto_type', 'concepto_id'], 'transacciones_concepto_idx');
            $table->index('estado', 'transacciones_estado_idx');
            $table->index(['proveedor', 'estado'], 'transacciones_proveedor_estado_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones_pago');
    }
};
