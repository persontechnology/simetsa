<?php

// database/seeders/InmovilizacionSeeder.php

namespace Database\Seeders;

use App\Enums\TipoInfraccion;
use App\Models\AgenteParqueo;
use App\Models\Infraccion;
use App\Models\Inmovilizacion;
use Illuminate\Database\Seeder;

/**
 * Siembra inmovilizaciones de muestra sobre infracciones existentes.
 * Requiere que InfraccionSeeder y AgenteParqueoSeeder hayan corrido primero.
 *
 * Art. 15 — Ordenanza SIMETSA.
 */
class InmovilizacionSeeder extends Seeder
{
    public function run(): void
    {
        $agente = AgenteParqueo::where('estado', AgenteParqueo::ESTADO_ACTIVO)->first();

        if (! $agente) {
            return;
        }

        // Solo inmovilizar infracciones cuyo tipo lo requiere (Art. 15)
        Infraccion::whereIn('tipo_infraccion', [
            TipoInfraccion::TiempoExcedido->value,
            TipoInfraccion::SinTicketVisible->value,
            TipoInfraccion::SinAdquirirTicket->value,
        ])
            ->whereDoesntHave('inmovilizacion')
            ->limit(10)
            ->get()
            ->each(fn (Infraccion $inf) => Inmovilizacion::factory()->create([
                'infraccion_id'    => $inf->id,
                'agente_parqueo_id'=> $agente->id,
            ]));
    }
}
