<?php
// app/Rules/CedulaEcuatoriana.php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regla de validación de cédula ecuatoriana.
 *
 * Aplica las 4 validaciones oficiales del Registro Civil:
 *  1) Exactamente 10 dígitos numéricos.
 *  2) Código de provincia entre 01-24 o 30 (Galápagos).
 *  3) Tercer dígito menor a 6 (persona natural).
 *  4) Dígito verificador correcto bajo algoritmo módulo 10.
 */
class CedulaEcuatoriana implements ValidationRule
{
    /**
     * Ejecuta la validación. Si falla, llama a $fail() con el motivo.
     *
     * @param  string                       $attribute  Nombre del campo
     * @param  mixed                        $value      Valor a validar
     * @param  \Closure(string): void       $fail       Callback de error
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1) Formato: exactamente 10 dígitos
        if (!is_string($value) || !preg_match('/^\d{10}$/', $value)) {
            $fail('La cédula debe contener exactamente 10 dígitos numéricos.');
            return;
        }

        // 2) Provincia: 01-24 (provincias del Ecuador) o 30 (Galápagos)
        $provincia = (int) substr($value, 0, 2);
        if (($provincia < 1 || $provincia > 24) && $provincia !== 30) {
            $fail('El código de provincia de la cédula no es válido.');
            return;
        }

        // 3) Tercer dígito debe ser menor a 6 (corresponde a persona natural;
        //    6 = ciudadanos suspendidos, 9 = personas jurídicas, no aplican aquí)
        if ((int) $value[2] >= 6) {
            $fail('El tercer dígito de la cédula no corresponde a persona natural.');
            return;
        }

        // 4) Algoritmo módulo 10 del dígito verificador
        if (!$this->verificarDigitoControl($value)) {
            $fail('El dígito verificador de la cédula no es válido.');
        }
    }

    /**
     * Aplica el algoritmo módulo 10 para validar el último dígito.
     *
     * Se multiplican los 9 primeros dígitos por los coeficientes
     * [2,1,2,1,2,1,2,1,2]. Si el producto supera 9 se le resta 9.
     * El dígito verificador esperado = (10 - (suma % 10)) % 10.
     *
     * @param  string  $cedula  Cédula de 10 dígitos
     * @return bool             true si el dígito verificador coincide
     */
    private function verificarDigitoControl(string $cedula): bool
    {
        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $producto = (int) $cedula[$i] * $coeficientes[$i];
            // Si el producto es de 2 dígitos, se restan 9 para reducirlo a 1
            $suma += $producto >= 10 ? $producto - 9 : $producto;
        }

        $digitoEsperado    = (10 - ($suma % 10)) % 10;
        $digitoVerificador = (int) $cedula[9];

        return $digitoEsperado === $digitoVerificador;
    }
}