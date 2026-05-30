<?php

/**
 * app/Enums/TipoInfraccion.php
 *
 * Tipos de infracción según la Ordenanza SIMETSA.
 * Art. 17 (7 casos) + Art. 18 con multa explícita en Arts. 29-30 (5 casos).
 */

namespace App\Enums;

enum TipoInfraccion: string
{
    // ── Art. 17 ──────────────────────────────────────────────────────────────

    /** Art. 17.a — Permanencia continua mayor a 2 horas. Multa escalonada: Art. 28. */
    case TiempoExcedido = 'tiempo_excedido';

    /** Art. 17.b — No colocar ticket visible en ventana del conductor. 2% SBU (Art. 29). */
    case SinTicketVisible = 'sin_ticket_visible';

    /** Art. 17.c — No adquirir ticket pasados los 5 minutos de tolerancia. 2% SBU (Art. 29). */
    case SinAdquirirTicket = 'sin_adquirir_ticket';

    /** Art. 17.d — Alterar el ticket o sus datos. 20% SBU (Art. 29). */
    case TicketAlterado = 'ticket_alterado';

    /** Art. 17.e — Retirar o intentar retirar el candado inmovilizador. 20% SBU (Art. 29). */
    case RetirarCandado = 'retirar_candado';

    /** Art. 17.f — Intercambiar tickets entre usuarios del sistema. 2% SBU (Art. 29). */
    case IntercambioTickets = 'intercambio_tickets';

    /** Art. 17.g — Negar el pago durante jornadas y horarios. Sin multa explícita en Art. 29; se registra sin cargo económico. */
    case NegarPago = 'negar_pago';

    // ── Art. 18 con multa en Arts. 29–30 ─────────────────────────────────────

    /** Art. 18.a — Doble columna en vía unidireccional. 20% SBU (Art. 29). */
    case DobleColumna = 'doble_columna';

    /** Art. 18.b — Estacionarse en calle con buses y calzada ≤ 6m. 20% SBU (Art. 29). */
    case CalleProhibidaBuses = 'calle_prohibida_buses';

    /** Art. 18.c — Bus urbano o carga pesada ocupa plaza SIMETSA en horario. 20% SBU (Art. 29). */
    case VehiculoProhibido = 'vehiculo_prohibido';

    /** Art. 18.d — Estacionar fuera del área señalizada (acera, parterre, rampa, etc.). 20% SBU (Art. 29). */
    case FueraDeArea = 'fuera_de_area';

    /** Art. 18.e — Agredir física o verbalmente al Agente de Parqueo. 50% SBU (Art. 30). */
    case AgresionAgente = 'agresion_agente';

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Etiqueta legible para humanos. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::TiempoExcedido      => 'Tiempo excedido (Art. 17.a)',
            self::SinTicketVisible    => 'Sin ticket visible (Art. 17.b)',
            self::SinAdquirirTicket   => 'Sin adquirir ticket (Art. 17.c)',
            self::TicketAlterado      => 'Ticket alterado (Art. 17.d)',
            self::RetirarCandado      => 'Intento de retirar candado (Art. 17.e)',
            self::IntercambioTickets  => 'Intercambio de tickets (Art. 17.f)',
            self::NegarPago           => 'Negativa de pago (Art. 17.g)',
            self::DobleColumna        => 'Doble columna (Art. 18.a)',
            self::CalleProhibidaBuses => 'Calle prohibida con buses (Art. 18.b)',
            self::VehiculoProhibido   => 'Vehículo no permitido (Art. 18.c)',
            self::FueraDeArea         => 'Fuera del área señalizada (Art. 18.d)',
            self::AgresionAgente      => 'Agresión al agente (Art. 18.e)',
        };
    }

    /**
     * Porcentaje base del SBU para esta infracción.
     * TiempoExcedido usa tabla escalonada (Art. 28) → retorna null; calcular por separado.
     * NegarPago no tiene sanción económica en la Ordenanza → retorna 0.
     *
     * @return float|null  Porcentaje (2, 4, 8, 20 o 50), null si escalonado, 0 si sin cargo.
     */
    public function porcentajeSbu(): ?float
    {
        return match ($this) {
            self::TiempoExcedido      => null,   // Art. 28: escalonado por minutos
            self::SinTicketVisible    => 2.0,    // Art. 29
            self::SinAdquirirTicket   => 2.0,    // Art. 29
            self::TicketAlterado      => 20.0,   // Art. 29
            self::RetirarCandado      => 20.0,   // Art. 29
            self::IntercambioTickets  => 2.0,    // Art. 29
            self::NegarPago           => 0.0,    // Art. 17.g: sin cargo explícito
            self::DobleColumna        => 20.0,   // Art. 29
            self::CalleProhibidaBuses => 20.0,   // Art. 29
            self::VehiculoProhibido   => 20.0,   // Art. 29
            self::FueraDeArea         => 20.0,   // Art. 29
            self::AgresionAgente      => 50.0,   // Art. 30
        };
    }

    /** Indica si este tipo genera inmovilización automática (Art. 15). */
    public function requiereInmovilizacion(): bool
    {
        return in_array($this, [
            self::TiempoExcedido,
            self::SinTicketVisible,
            self::SinAdquirirTicket,
        ], true);
    }
}
