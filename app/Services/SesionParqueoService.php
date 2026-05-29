<?php

// app/Services/SesionParqueoService.php

namespace App\Services;

use App\Enums\EstadoSesionParqueo;
use App\Enums\EstadoTicket;
use App\Models\AgenteParqueo;
use App\Models\SesionParqueo;
use App\Models\Ticket;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de inicio y gestión de sesiones de parqueo (acción del agente en calle).
 *
 * La sesión confirma el inicio físico del estacionamiento.
 * Relación 1:1 con Ticket; solo puede existir una sesión por ticket.
 */
class SesionParqueoService
{
    /**
     * Inicia la sesión de parqueo para un ticket validado por el agente.
     *
     * El ticket debe estar en estado 'pendiente' o 'activo'.
     * No puede iniciarse si ya existe una sesión para ese ticket.
     *
     * @param  Ticket            $ticket
     * @param  AgenteParqueo     $agente
     * @param  array{
     *     plaza_id: ?int,
     *     latitud:  ?float,
     *     longitud: ?float
     * }  $datos
     * @return SesionParqueo
     *
     * @throws DomainException
     */
    public function iniciar(Ticket $ticket, AgenteParqueo $agente, array $datos): SesionParqueo
    {
        if ($ticket->sesion()->exists()) {
            throw new DomainException('Este ticket ya tiene una sesión de parqueo iniciada.');
        }

        if (! in_array($ticket->estado, [EstadoTicket::Pendiente, EstadoTicket::Activo], true)) {
            throw new DomainException(
                "No se puede iniciar sesión para un ticket en estado '{$ticket->estado->etiqueta()}'."
            );
        }

        return DB::transaction(function () use ($ticket, $agente, $datos) {
            $ahora = now();

            $sesion = SesionParqueo::create([
                'ticket_id'         => $ticket->id,
                'agente_id'         => $agente->id,
                'plaza_id'          => $datos['plaza_id'] ?? null,
                'lat_inicio'        => $datos['latitud'] ?? null,
                'lng_inicio'        => $datos['longitud'] ?? null,
                'inicio_at'         => $ahora,
                'fin_programado_at' => $ahora->copy()->addHours($ticket->horas_compradas),
                'estado'            => EstadoSesionParqueo::Activa,
            ]);

            // Actualizar estado del ticket a 'activo' si estaba 'pendiente'
            if ($ticket->estado === EstadoTicket::Pendiente) {
                $ticket->update(['estado' => EstadoTicket::Activo]);
            }

            return $sesion;
        });
    }
}
