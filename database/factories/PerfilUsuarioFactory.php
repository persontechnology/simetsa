<?php
// database/factories/PerfilUsuarioFactory.php

namespace Database\Factories;

use App\Models\PerfilUsuario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory de PerfilUsuario.
 *
 * Genera perfiles con cédulas ecuatorianas matemáticamente válidas
 * (cumplen el algoritmo módulo 10). Útil para tests de fases futuras
 * que necesiten muchos usuarios con perfil completo.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PerfilUsuario>
 */
class PerfilUsuarioFactory extends Factory
{
    /**
     * Modelo asociado al factory.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = PerfilUsuario::class;

    /**
     * Definición por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'                   => User::factory(),
            'cedula'                    => $this->generarCedulaValida(),
            'telefono'                  => $this->faker->optional()->numerify('03#######'),
            'telefono_celular'          => '09' . $this->faker->numerify('########'),
            'direccion'                 => $this->faker->streetAddress(),
            'fecha_nacimiento'          => $this->faker->dateTimeBetween('-60 years', '-19 years'),
            'genero'                    => $this->faker->randomElement(['M', 'F', 'O', 'ND']),
            'acepta_terminos'           => true,
            'fecha_aceptacion_terminos' => now(),
            'activo'                    => true,
        ];
    }

    /**
     * Estado para perfiles que aún no aceptaron términos.
     *
     * @return self
     */
    public function sinConsentimiento(): self
    {
        return $this->state(fn () => [
            'acepta_terminos'           => false,
            'fecha_aceptacion_terminos' => null,
        ]);
    }

    /**
     * Estado para perfiles inactivos (soft-disabled).
     *
     * @return self
     */
    public function inactivo(): self
    {
        return $this->state(fn () => ['activo' => false]);
    }

    /**
     * Genera una cédula ecuatoriana de 10 dígitos matemáticamente válida.
     *
     * Pasos:
     *  1) Provincia aleatoria 01-24.
     *  2) Tercer dígito 0-5 (persona natural).
     *  3) 6 dígitos aleatorios.
     *  4) Cálculo del dígito verificador con coeficientes [2,1,2,1,2,1,2,1,2].
     *
     * @return string  Cédula de 10 dígitos válida
     */
    private function generarCedulaValida(): string
    {
        $provincia    = str_pad((string) random_int(1, 24), 2, '0', STR_PAD_LEFT);
        $tercerDigito = (string) random_int(0, 5);
        $aleatorios   = '';
        for ($i = 0; $i < 6; $i++) {
            $aleatorios .= (string) random_int(0, 9);
        }

        $nueveDigitos = $provincia . $tercerDigito . $aleatorios;

        // Cálculo del décimo dígito (verificador) usando módulo 10
        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $producto = (int) $nueveDigitos[$i] * $coeficientes[$i];
            $suma += $producto >= 10 ? $producto - 9 : $producto;
        }
        $verificador = (10 - ($suma % 10)) % 10;

        return $nueveDigitos . $verificador;
    }
}