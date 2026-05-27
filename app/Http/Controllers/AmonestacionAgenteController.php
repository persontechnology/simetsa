<?php
// app/Http/Controllers/AmonestacionAgenteController.php

namespace App\Http\Controllers;

use App\Models\AgenteParqueo;
use App\Models\AmonestacionAgente;
use App\Services\AmonestacionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Controlador de amonestaciones del agente (Art. 40).
 */
class AmonestacionAgenteController extends Controller
{
    public function __construct(private AmonestacionService $servicio) {
        $this->middleware('permission:agentes.editar')->only(['store','update', 'destroy']);
    }
    

    public function store(Request $request, AgenteParqueo $agente): RedirectResponse
    {
        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
            'fecha'  => ['nullable', 'date'],
        ]);

        try {
            $amonestacion = $this->servicio->registrar($agente, $datos);

            $mensaje = match ($amonestacion->tipo) {
                AmonestacionAgente::TIPO_VERBAL      => 'Amonestación verbal registrada (1.ª falta).',
                AmonestacionAgente::TIPO_ESCRITA     => 'Amonestación escrita registrada (2.ª falta).',
                AmonestacionAgente::TIPO_TERMINACION => 'Tercera falta: la autorización del agente fue terminada (Art. 40.c).',
                default                              => 'Amonestación registrada.',
            };

            return back()->with('success', $mensaje);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(AmonestacionAgente $amonestacion): RedirectResponse
    {

        /* Agente parqueo, validar estado cuando se elimina una amonestación */
        try {
            $this->servicio->eliminar($amonestacion);
            return back()->with('success', 'Amonestación eliminada correctamente.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, AmonestacionAgente $amonestacion): RedirectResponse
    {
        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
            'fecha'  => ['nullable', 'date'],
        ]);

        $this->servicio->actualizar($amonestacion, $datos);
        return back()->with('success', 'Amonestación actualizada.');
    }
}