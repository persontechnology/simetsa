<?php
// app/Http/Controllers/ConductorController.php

namespace App\Http\Controllers;

use App\Models\Conductor;
use App\Services\ConductorService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Backoffice de supervisión de conductores (Art. 37 Ordenanza SIMETSA).
 *
 * El conductor se autoregistra vía app móvil; aquí el comisario o director
 * puede ver el listado, el detalle y bloquear / desbloquear cuentas.
 */
class ConductorController extends Controller
{
    public function __construct(private readonly ConductorService $servicio)
    {
        $this->middleware('permission:conductores.ver')->only(['index', 'show']);
        $this->middleware('permission:conductores.editar')->only(['bloquear', 'desbloquear']);
    }

    /**
     * Lista de conductores con búsqueda y filtro por estado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = Conductor::with('user.perfil')
            ->when($request->buscar, function ($q, $buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('codigo', 'ILIKE', "%{$buscar}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'ILIKE', "%{$buscar}%")
                            ->orWhere('email', 'ILIKE', "%{$buscar}%"))
                        ->orWhereHas('user.perfil', fn ($p) => $p->where('cedula', 'ILIKE', "%{$buscar}%"));
                });
            })
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->orderByDesc('created_at');

        return view('conductores.index', [
            'conductores' => $query->paginate(20)->withQueryString(),
            'estados'     => Conductor::listadoEstados(),
        ]);
    }

    /**
     * Perfil completo del conductor con sus vehículos y credenciales CONADIS.
     *
     * @param  \App\Models\Conductor  $conductor
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Conductor $conductor): View
    {
        $conductor->loadMissing('user.perfil', 'vehiculos.tipoVehiculo', 'vehiculos.credencial');

        return view('conductores.show', compact('conductor'));
    }

    /**
     * Bloquea la cuenta de un conductor.
     *
     * @see Art. 37 Ordenanza SIMETSA (facultades del comisario).
     *
     * @param  \App\Models\Conductor  $conductor
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bloquear(Conductor $conductor): RedirectResponse
    {
        try {
            $this->servicio->cambiarEstado($conductor, Conductor::ESTADO_BLOQUEADO);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Conductor {$conductor->codigo} bloqueado.");
    }

    /**
     * Desbloquea la cuenta de un conductor.
     *
     * @see Art. 37 Ordenanza SIMETSA (facultades del comisario).
     *
     * @param  \App\Models\Conductor  $conductor
     * @return \Illuminate\Http\RedirectResponse
     */
    public function desbloquear(Conductor $conductor): RedirectResponse
    {
        try {
            $this->servicio->cambiarEstado($conductor, Conductor::ESTADO_ACTIVO);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Conductor {$conductor->codigo} desbloqueado.");
    }
}
