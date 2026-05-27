<?php

namespace Database\Seeders;

use App\Models\SolicitudPuntoVenta;
use Illuminate\Database\Seeder;

class SolicitudPuntoVentaSeeder extends Seeder
{
    public function run(): void
    {
        SolicitudPuntoVenta::firstOrCreate(
            ['codigo' => 'SPV-0001'],
            [
                'cedula' => '1710034065',
                'nombres' => 'María Fernanda',
                'apellidos' => 'Toapanta Llumiquinga',
                'email' => 'puntoventa.demo@simetsa.gob.ec',
                'telefono_celular' => '0998765432',
                'nombre_comercial' => 'Bazar y Papelería El Centro',
                'direccion_local' => 'Calle Bolívar y Sucre, esquina',
                'referencia_ubicacion' => 'Frente al parque central',
                'estado' => SolicitudPuntoVenta::ESTADO_DOCUMENTACION,
                'fecha_solicitud' => now(),
            ]
        );
    }
}