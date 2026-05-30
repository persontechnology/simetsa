<?php

// tests/Feature/MetodoPagoTest.php

namespace Tests\Feature;

use App\Enums\MetodoPago;
use App\Enums\ProveedorPago;
use Tests\TestCase;

/**
 * Tests del refactor de MetodoPago — Fase 6.0.
 */
class MetodoPagoTest extends TestCase
{
    public function test_disponibles_incluye_pago_simulado_en_testing(): void
    {
        // APP_ENV=testing en la suite — PagoSimulado debe estar disponible
        $this->assertContains(MetodoPago::PagoSimulado, MetodoPago::disponibles());
    }

    public function test_disponibles_no_incluye_pago_simulado_en_produccion(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $disponibles = MetodoPago::disponibles();

        app()->detectEnvironment(fn () => 'testing'); // restaurar

        $this->assertNotContains(MetodoPago::PagoSimulado, $disponibles);
    }

    public function test_disponibles_siempre_incluye_efectivo(): void
    {
        $this->assertContains(MetodoPago::Efectivo, MetodoPago::disponibles());
    }

    public function test_disponibles_incluye_link_y_qr(): void
    {
        $disponibles = MetodoPago::disponibles();

        $this->assertContains(MetodoPago::Link, $disponibles);
        $this->assertContains(MetodoPago::Qr, $disponibles);
    }

    public function test_payphone_ya_no_existe_como_case(): void
    {
        $this->assertNull(MetodoPago::tryFrom('payphone'));
    }

    public function test_requiere_gateway_es_correcto(): void
    {
        $this->assertFalse(MetodoPago::Efectivo->requiereGateway());
        $this->assertFalse(MetodoPago::PagoSimulado->requiereGateway());
        $this->assertTrue(MetodoPago::Link->requiereGateway());
        $this->assertTrue(MetodoPago::Qr->requiereGateway());
        $this->assertTrue(MetodoPago::Tarjeta->requiereGateway());
    }

    public function test_proveedor_pago_es_digital(): void
    {
        $this->assertFalse(ProveedorPago::None->esDigital());
        $this->assertFalse(ProveedorPago::Manual->esDigital());
        $this->assertTrue(ProveedorPago::Deuna->esDigital());
        $this->assertTrue(ProveedorPago::Pagomedios->esDigital());
    }
}
