<?php

// app/Http/Controllers/TicketController.php

namespace App\Http\Controllers;

use App\Enums\EstadoTicket;
use App\Http\Requests\AnularTicketRequest;
use App\Models\Ticket;
use App\Models\Zona;
use App\Services\TicketService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Backoffice de supervisión y control de tickets digitales (Art. 37 Ordenanza SIMETSA).
 *
 * El comisario puede listar, filtrar, ver detalle y anular tickets.
 * El director_seguridad puede listar y ver detalle (solo lectura).
 */
class TicketController extends Controller
{
    public function __construct(private readonly TicketService $servicio)
    {
        // Backoffice: solo supervisores; conductores tienen tickets.ver solo para la API móvil
        $this->middleware('role:super_admin|comisario|director_seguridad')->only(['index', 'show']);
        $this->middleware('permission:tickets.anular')->only(['anular']);
    }

    /**
     * Lista tickets con filtros: zona, estado, placa, fecha.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = Ticket::with(['conductor.user', 'vehiculo', 'zona'])
            ->when($request->placa, fn ($q, $p) =>
                $q->whereHas('vehiculo', fn ($v) => $v->whereRaw('UPPER(placa) = ?', [strtoupper($p)]))
            )
            ->when($request->zona_id, fn ($q, $z) => $q->where('zona_id', $z))
            ->when($request->estado, fn ($q, $e) => $q->where('estado', $e))
            ->when($request->fecha_desde, fn ($q, $f) => $q->whereDate('comprado_en', '>=', $f))
            ->when($request->fecha_hasta, fn ($q, $f) => $q->whereDate('comprado_en', '<=', $f))
            ->orderByDesc('comprado_en');

        return view('tickets.index', [
            'tickets' => $query->paginate(25)->withQueryString(),
            'zonas'   => Zona::where('activo', true)->orderBy('nombre')->get(),
            'estados' => collect(EstadoTicket::cases())->mapWithKeys(
                fn ($e) => [$e->value => $e->etiqueta()]
            )->all(),
        ]);
    }

    /**
     * Detalle de un ticket con sesión y cancelación asociadas.
     *
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Ticket $ticket): View
    {
        $ticket->load([
            'conductor.user.perfil',
            'vehiculo.tipoVehiculo',
            'zona',
            'calle',
            'sesion.agente',
            'sesion.plaza',
            'cancelacion.canceladoPorUsuario',
        ]);

        return view('tickets.show', compact('ticket'));
    }

    /**
     * Anula administrativamente un ticket.
     *
     * Solo el comisario o super_admin puede ejecutar esta acción.
     *
     * @param  \App\Http\Requests\AnularTicketRequest  $request
     * @param  \App\Models\Ticket                      $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function anular(AnularTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        try {
            $this->servicio->anular($ticket, $request->user(), $request->validated()['motivo']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->codigo} anulado correctamente.");
    }
}
