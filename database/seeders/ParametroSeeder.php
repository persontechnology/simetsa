<?php
// database/seeders/ParametroSeeder.php

namespace Database\Seeders;

use App\Models\Parametro;
use Illuminate\Database\Seeder;

/**
 * Seeder de parámetros base del SIMETSA conforme a la Ordenanza
 * aprobada el 06-feb-2020 y sancionada el 10-feb-2020.
 *
 * Carga 15 parámetros agrupados en 3 categorías:
 *  - operacion:     SBU, tarifa, tiempos, horarios.
 *  - liquidaciones: porcentajes 60/40 (Agente) y 90/10 (Punto de Venta).
 *  - multas:        porcentajes de SBU según gravedad.
 *
 * Idempotente: usa firstOrCreate para no sobrescribir valores que el
 * admin haya modificado desde el backoffice.
 */
class ParametroSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $parametros = $this->datosParametros();

        foreach ($parametros as $datos) {
            // firstOrCreate: si la clave existe, NO sobreescribe el valor del admin.
            Parametro::firstOrCreate(
                ['clave' => $datos['clave']],
                $datos
            );
        }

        $this->command->info('Parámetros del sistema cargados: ' . count($parametros));
    }

    /**
     * Listado completo de parámetros base del SIMETSA (32 parámetros).
     *
     * @return array<int, array<string, mixed>>
     */
    private function datosParametros(): array
    {
        return [
            // =========================================================
            // INSTITUCIÓN — Datos del GAD Municipal del Cantón Salcedo
            // =========================================================
            [
                'categoria'          => 'institucion',
                'clave'              => 'nombre_gad',
                'valor'              => 'GAD Municipal del Cantón Salcedo',
                'tipo'               => Parametro::TIPO_STRING,
                'descripcion'        => 'Nombre oficial de la institución responsable.',
                'articulo_ordenanza' => null,
            ],
            [
                'categoria'          => 'institucion',
                'clave'              => 'direccion_gad',
                'valor'              => 'Calle Bolívar y Av. Olmedo, Salcedo, Cotopaxi, Ecuador',
                'tipo'               => Parametro::TIPO_STRING,
                'descripcion'        => 'Dirección física del GAD Municipal.',
                'articulo_ordenanza' => null,
            ],
            [
                'categoria'          => 'institucion',
                'clave'              => 'ruc_gad',
                'valor'              => '0560000490001',
                'tipo'               => Parametro::TIPO_STRING,
                'descripcion'        => 'RUC institucional del GAD Municipal (verificar valor real con Tesorería).',
                'articulo_ordenanza' => null,
            ],
            [
                'categoria'          => 'institucion',
                'clave'              => 'email_soporte',
                'valor'              => 'soporte.simetsa@salcedo.gob.ec',
                'tipo'               => Parametro::TIPO_STRING,
                'descripcion'        => 'Email de soporte para usuarios del SIMETSA.',
                'articulo_ordenanza' => null,
            ],
            [
                'categoria'          => 'institucion',
                'clave'              => 'telefono_soporte',
                'valor'              => '032729000',
                'tipo'               => Parametro::TIPO_STRING,
                'descripcion'        => 'Teléfono de soporte y atención de reclamos.',
                'articulo_ordenanza' => 'Art. 37.2',
            ],

            // =========================================================
            // OPERACIÓN — Parámetros operativos del servicio
            // =========================================================
            [
                'categoria'          => 'operacion',
                'clave'              => 'sbu_vigente',
                'valor'              => '460.00',
                'tipo'               => Parametro::TIPO_DECIMAL,
                'descripcion'        => 'Salario Básico Unificado vigente en USD. Base para cálculo de multas.',
                'articulo_ordenanza' => 'Art. 28-30',
            ],
            [
                'categoria'          => 'operacion',
                'clave'              => 'tiempo_maximo_parqueo_minutos',
                'valor'              => '120',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Tiempo máximo de permanencia permitida en una plaza, en minutos.',
                'articulo_ordenanza' => 'Art. 14',
            ],
            [
                'categoria'          => 'operacion',
                'clave'              => 'tiempo_tolerancia_minutos',
                'valor'              => '5',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Tiempo de tolerancia desde estacionar hasta colocar el ticket, en minutos.',
                'articulo_ordenanza' => 'Art. 13',
            ],
            [
                'categoria'          => 'operacion',
                'clave'              => 'ancho_minimo_plaza_metros',
                'valor'              => '2.20',
                'tipo'               => Parametro::TIPO_DECIMAL,
                'descripcion'        => 'Ancho mínimo permitido para una plaza de estacionamiento, en metros.',
                'articulo_ordenanza' => 'Art. 6',
            ],
            [
                'categoria'          => 'operacion',
                'clave'              => 'ancho_maximo_plaza_metros',
                'valor'              => '2.50',
                'tipo'               => Parametro::TIPO_DECIMAL,
                'descripcion'        => 'Ancho máximo permitido para una plaza de estacionamiento, en metros.',
                'articulo_ordenanza' => 'Art. 6',
            ],
            [
                'categoria'          => 'operacion',
                'clave'              => 'ancho_maximo_ingreso_vehicular_metros',
                'valor'              => '3.00',
                'tipo'               => Parametro::TIPO_DECIMAL,
                'descripcion'        => 'Ancho máximo de ingresos vehiculares a inmuebles, en metros.',
                'articulo_ordenanza' => 'Art. 7',
            ],
            [
                'categoria'          => 'operacion',
                'clave'              => 'ancho_maximo_acceso_parqueadero_metros',
                'valor'              => '6.00',
                'tipo'               => Parametro::TIPO_DECIMAL,
                'descripcion'        => 'Ancho máximo de accesos a parqueaderos públicos en solares no edificados, en metros.',
                'articulo_ordenanza' => 'Art. 7',
            ],
            [
                'categoria'          => 'operacion',
                'clave'              => 'tiempo_maximo_exonerados_minutos',
                'valor'              => '120',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Tiempo máximo de parqueo gratuito para vehículos exonerados de autoridades.',
                'articulo_ordenanza' => 'Art. 27',
            ],

            // =========================================================
            // AGENTES DE PARQUEO — Gestión y autorización
            // =========================================================
            [
                'categoria'          => 'agentes',
                'clave'              => 'nota_minima_curso_agente',
                'valor'              => '70',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Puntaje mínimo para aprobar el curso de Agente de Parqueo (sobre 100).',
                'articulo_ordenanza' => 'Art. 33',
            ],
            [
                'categoria'          => 'agentes',
                'clave'              => 'edad_minima_agente',
                'valor'              => '18',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Edad mínima en años para postular como Agente de Parqueo.',
                'articulo_ordenanza' => 'Art. 33.4',
            ],
            [
                'categoria'          => 'agentes',
                'clave'              => 'max_amonestaciones_agente',
                'valor'              => '3',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Cantidad máxima de amonestaciones antes de terminación unilateral.',
                'articulo_ordenanza' => 'Art. 40',
            ],

            // =========================================================
            // PUNTOS DE VENTA — Distribución territorial
            // =========================================================
            [
                'categoria'          => 'puntos_venta',
                'clave'              => 'cuadras_por_punto_venta',
                'valor'              => '3',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Cantidad de cuadras por las que se autoriza un punto de venta.',
                'articulo_ordenanza' => 'Art. 31',
            ],
           //descuento_punto_venta
            [
                'categoria'          => 'puntos_venta',
                'clave'              => 'descuento_punto_venta',
                'valor'              => '10',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Descuento que recibe el Punto de Venta sobre el valor adquirido en tickets.',
                'articulo_ordenanza' => 'Art. 21, Art. 31',
            ],

            // =========================================================
            // APLICACIÓN MÓVIL — Comportamiento del cliente del conductor
            // =========================================================
            [
                'categoria'          => 'app_movil',
                'clave'              => 'minutos_advertencia_expiracion_ticket',
                'valor'              => '10',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Minutos previos a la expiración del ticket para enviar push de advertencia.',
                'articulo_ordenanza' => null,
            ],
            [
                'categoria'          => 'app_movil',
                'clave'              => 'radio_validacion_ubicacion_metros',
                'valor'              => '50',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Radio en metros para validar que el usuario está dentro de la zona al comprar ticket.',
                'articulo_ordenanza' => null,
            ],

            // =========================================================
            // LIQUIDACIONES — Reparto de ingresos (Art. 21)
            // =========================================================
            [
                'categoria'          => 'liquidaciones',
                'clave'              => 'porcentaje_gad_de_agente',
                'valor'              => '60',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Porcentaje que retiene el GAD Municipal de los tickets vendidos por Agente.',
                'articulo_ordenanza' => 'Art. 21',
            ],
            [
                'categoria'          => 'liquidaciones',
                'clave'              => 'porcentaje_agente',
                'valor'              => '40',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Porcentaje que recibe el Agente de Parqueo de los tickets vendidos.',
                'articulo_ordenanza' => 'Art. 21',
            ],
            [
                'categoria'          => 'liquidaciones',
                'clave'              => 'porcentaje_gad_de_punto_venta',
                'valor'              => '90',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Porcentaje que retiene el GAD Municipal de los tickets vendidos por Punto de Venta.',
                'articulo_ordenanza' => 'Art. 21, Art. 31',
            ],
            [
                'categoria'          => 'liquidaciones',
                'clave'              => 'porcentaje_punto_venta',
                'valor'              => '10',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Descuento que recibe el Punto de Venta sobre el valor adquirido en tickets.',
                'articulo_ordenanza' => 'Art. 21, Art. 31',
            ],

            // =========================================================
            // MULTAS — Porcentajes del SBU según gravedad
            // =========================================================
            [
                'categoria'          => 'multas',
                'clave'              => 'multa_porcentaje_6_60min',
                'valor'              => '2',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Porcentaje del SBU para multa por exceso de 6 a 60 minutos.',
                'articulo_ordenanza' => 'Art. 28',
            ],
            [
                'categoria'          => 'multas',
                'clave'              => 'multa_porcentaje_61_120min',
                'valor'              => '4',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Porcentaje del SBU para multa por exceso de 61 a 120 minutos.',
                'articulo_ordenanza' => 'Art. 28',
            ],
            [
                'categoria'          => 'multas',
                'clave'              => 'multa_porcentaje_mas_120min',
                'valor'              => '8',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Porcentaje del SBU para multa por exceso de más de 120 minutos.',
                'articulo_ordenanza' => 'Art. 28',
            ],
            [
                'categoria'          => 'multas',
                'clave'              => 'multa_porcentaje_falta_grave',
                'valor'              => '20',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Porcentaje del SBU para faltas graves: Art. 17 (d,e) y Art. 18 (a,b,c,d,f).',
                'articulo_ordenanza' => 'Art. 29',
            ],
            [
                'categoria'          => 'multas',
                'clave'              => 'multa_porcentaje_falta_severa',
                'valor'              => '50',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Porcentaje del SBU por retirar o intentar retirar el candado inmovilizador.',
                'articulo_ordenanza' => 'Art. 30',
            ],

            // =========================================================
            // SANCIONES ADMINISTRATIVAS — Plazos procesales
            // =========================================================
            [
                'categoria'          => 'sanciones',
                'clave'              => 'dias_para_impugnar_multa',
                'valor'              => '10',
                'tipo'               => Parametro::TIPO_INTEGER,
                'descripcion'        => 'Días hábiles que tiene el conductor para presentar impugnación de una multa.',
                'articulo_ordenanza' => null,
            ],
        ];
    }
}