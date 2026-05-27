<?php

namespace Database\Seeders;

use App\Models\ContratoPuntoVenta;
use App\Models\PuntoVenta;
use App\Models\User;
use Illuminate\Database\Seeder;

class PuntoVentaSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'puntoventa@simetsa.gob.ec')->first();

        if (! $user) {
            return; // El usuario de prueba lo crea UsuarioPruebaSeeder
        }

        $punto = PuntoVenta::firstOrCreate(
            ['codigo' => 'PV-0001'],
            [
                'user_id' => $user->id,
                'nombre_comercial' => 'Punto de Venta Demo Centro',
                'direccion_local' => 'Calle Sucre y 9 de Octubre',
                'referencia_ubicacion' => 'Junto al mercado',
                'estado' => PuntoVenta::ESTADO_ACTIVO,
            ]
        );

        ContratoPuntoVenta::firstOrCreate(
            ['punto_venta_id' => $punto->id],
            [
                'numero_contrato' => 'CPV-2026-0001',
                'fecha_firma' => now(),
                'fecha_inicio' => now(),
                'porcentaje_descuento' => 10,
                'elaborado_por' => 'Procuraduría Síndica',
            ]
        );
    }
}