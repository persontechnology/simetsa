<?php
// database/seeders/AgenteParqueoSeeder.php

namespace Database\Seeders;

use App\Models\AgenteParqueo;
use App\Models\ExpedienteAgente;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder de un agente de ejemplo, vinculado al usuario de prueba `agente@`.
 *
 * Reutiliza la cuenta de prueba existente (rol agente_parqueo) para tener
 * datos en las vistas de agentes sin recorrer todo el flujo de autorización.
 */
class AgenteParqueoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'agente@simetsa.gob.ec')->first();

        if (!$user) {
            $this->command->warn('Usuario agente@ no encontrado; ejecutá UsuarioPruebaSeeder primero.');
            return;
        }

        $agente = AgenteParqueo::firstOrCreate(
            ['user_id' => $user->id],
            [
                'codigo'                   => 'AG-0001',
                'numero_credencial'        => 'CRED-001',
                'carta_compromiso_firmada' => true,
                'fecha_autorizacion'       => now()->toDateString(),
                'estado'                   => AgenteParqueo::ESTADO_ACTIVO,
            ]
        );

        ExpedienteAgente::firstOrCreate(
            ['agente_parqueo_id' => $agente->id],
            ['fecha_apertura' => now()->toDateString()]
        );

        $this->command->info('Agente de ejemplo (AG-0001) cargado.');
    }
}