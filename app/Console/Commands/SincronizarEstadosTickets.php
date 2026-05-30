<?php

// app/Console/Commands/SincronizarEstadosTickets.php

namespace App\Console\Commands;

use App\Enums\EstadoTicket;
use App\Models\Ticket;
use App\Services\TicketService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Sincroniza los estados de tickets cuyo tiempo ha expirado.
 *
 * Problema que resuelve: TicketService::calcularEstadoActual() calcula el
 * estado en tiempo real para los agentes, pero la BD puede quedar con
 * estado 'activo' si el conductor no renovó. Este comando reconcilia la BD.
 *
 * Cron sugerido (cada minuto):
 *   * * * * * php artisan simetsa:sincronizar-estados-tickets
 *
 * Resuelve deuda técnica "Estados de ticket en BD" — ver CLAUDE.md.
 */
class SincronizarEstadosTickets extends Command
{
    protected $signature = 'simetsa:sincronizar-estados-tickets
                            {--dry-run : Muestra los tickets a actualizar sin modificar la BD}';

    protected $description = 'Transiciona tickets activos/en_tolerancia vencidos al estado correcto (expirado/en_tolerancia).';

    /**
     * @param  TicketService  $servicio
     * @return int
     */
    public function handle(TicketService $servicio): int
    {
        $ahora  = Carbon::now();
        $dryRun = $this->option('dry-run');

        // Solo tickets en estados transitables que pueden haber vencido
        $tickets = Ticket::whereIn('estado', [
            EstadoTicket::Activo->value,
            EstadoTicket::EnTolerancia->value,
        ])
            ->where('expira_en', '<', $ahora)
            ->get();

        if ($tickets->isEmpty()) {
            $this->info('Sin tickets a sincronizar.');

            return self::SUCCESS;
        }

        $actualizados = 0;

        foreach ($tickets as $ticket) {
            $estadoCorrecto = $servicio->calcularEstadoActual($ticket, $ahora);

            if ($estadoCorrecto === $ticket->estado) {
                continue;
            }

            if ($dryRun) {
                $this->line("  [dry-run] Ticket {$ticket->codigo}: {$ticket->estado->value} → {$estadoCorrecto->value}");
                continue;
            }

            $ticket->update(['estado' => $estadoCorrecto]);
            $actualizados++;
        }

        $msg = $dryRun
            ? "Dry-run: {$tickets->count()} ticket(s) candidato(s)."
            : "{$actualizados} ticket(s) sincronizados.";

        $this->info($msg);

        return self::SUCCESS;
    }
}
