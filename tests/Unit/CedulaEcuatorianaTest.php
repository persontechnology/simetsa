<?php
// tests/Unit/CedulaEcuatorianaTest.php

namespace Tests\Unit;

use App\Rules\CedulaEcuatoriana;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios de la regla CedulaEcuatoriana.
 *
 * Cubre los 4 escenarios oficiales del Registro Civil:
 * formato, provincia, tercer dígito y dígito verificador.
 */
class CedulaEcuatorianaTest extends TestCase
{
    /**
     * Aplica la regla y devuelve el primer mensaje de error, o null si pasa.
     *
     * @param  string  $cedula  Cédula a validar
     * @return string|null      Mensaje de error o null si es válida
     */
    private function validar(string $cedula): ?string
    {
        $error = null;
        $regla = new CedulaEcuatoriana();
        $regla->validate('cedula', $cedula, function (string $mensaje) use (&$error) {
            $error = $mensaje;
        });
        return $error;
    }

    /**
     * Una cédula válida real debe pasar todas las validaciones.
     *
     * @return void
     */
    public function test_cedula_valida_pasa(): void
    {
        // Cédula real con dígito verificador correcto (provincia 17 - Pichincha)
        $this->assertNull($this->validar('1710034065'));
    }

    /**
     * Cédulas con menos de 10 dígitos deben fallar.
     *
     * @return void
     */
    public function test_cedula_con_menos_de_diez_digitos_falla(): void
    {
        $this->assertNotNull($this->validar('171003406'));
    }

    /**
     * Cédulas con más de 10 dígitos deben fallar.
     *
     * @return void
     */
    public function test_cedula_con_mas_de_diez_digitos_falla(): void
    {
        $this->assertNotNull($this->validar('17100340655'));
    }

    /**
     * Cédulas con caracteres no numéricos deben fallar.
     *
     * @return void
     */
    public function test_cedula_con_letras_falla(): void
    {
        $this->assertNotNull($this->validar('17100A4065'));
    }

    /**
     * Código de provincia inválido (>24 y distinto de 30) debe fallar.
     *
     * @return void
     */
    public function test_provincia_invalida_falla(): void
    {
        // Provincia 25 no existe en Ecuador
        $error = $this->validar('2510034065');
        $this->assertNotNull($error);
        $this->assertStringContainsString('provincia', $error);
    }

    /**
     * Tercer dígito >= 6 (no es persona natural) debe fallar.
     *
     * @return void
     */
    public function test_tercer_digito_invalido_falla(): void
    {
        $error = $this->validar('1790034065');
        $this->assertNotNull($error);
        $this->assertStringContainsString('persona natural', $error);
    }

    /**
     * Dígito verificador incorrecto debe fallar.
     *
     * @return void
     */
    public function test_digito_verificador_incorrecto_falla(): void
    {
        // Cédula con todo correcto excepto el último dígito
        $error = $this->validar('1710034060');
        $this->assertNotNull($error);
        $this->assertStringContainsString('verificador', $error);
    }
}