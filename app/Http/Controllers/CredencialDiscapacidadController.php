<?php
// app/Http/Controllers/CredencialDiscapacidadController.php

namespace App\Http\Controllers;

use App\Http\Requests\AprobacionCredencialRequest;
use App\Models\CredencialDiscapacidad;
use App\Services\CredencialDiscapacidadService;
use DomainException;
use Illuminate\Http\RedirectResponse;

/**
 * Backoffice: aprobación de credenciales CONADIS por comisario o director (Art. 26 Ordenanza SIMETSA).
 *
 * La UI de listado y detalle vive en conductores/show.blade.php (Fase 4.D).
 * Este controller solo expone las acciones de flujo de aprobación.
 */
class CredencialDiscapacidadController extends Controller
{
    public function __construct(private readonly CredencialDiscapacidadService $servicio)
    {
        $this->middleware('permission:credenciales_discapacidad.aprobar')->only(['aprobar', 'rechazar']);
    }

    /**
     * Aprueba una credencial CONADIS en estado pendiente.
     *
     * @see Art. 26 Ordenanza SIMETSA.
     *
     * @param  \App\Http\Requests\AprobacionCredencialRequest  $request
     * @param  \App\Models\CredencialDiscapacidad               $credencial_discapacidad
     * @return \Illuminate\Http\RedirectResponse
     */
    public function aprobar(AprobacionCredencialRequest $request, CredencialDiscapacidad $credencial_discapacidad): RedirectResponse
    {
        try {
            $this->servicio->aprobar($credencial_discapacidad, $request->user(), $request->validated());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Credencial CONADIS aprobada correctamente.');
    }

    /**
     * Rechaza una credencial CONADIS en estado pendiente.
     *
     * @see Art. 26 Ordenanza SIMETSA.
     *
     * @param  \App\Http\Requests\AprobacionCredencialRequest  $request
     * @param  \App\Models\CredencialDiscapacidad               $credencial_discapacidad
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rechazar(AprobacionCredencialRequest $request, CredencialDiscapacidad $credencial_discapacidad): RedirectResponse
    {
        try {
            $this->servicio->rechazar($credencial_discapacidad, $request->validated());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Credencial CONADIS rechazada.');
    }
}
