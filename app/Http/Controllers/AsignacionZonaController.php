<?php
// app/Http/Controllers/AsignacionZonaController.php

namespace App\Http\Controllers;

use App\Models\AgenteParqueo;
use App\Models\AsignacionZona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Controlador de asignaciones de zona a un agente (Art. 16).
 *
 * Regla de duplicado: se bloquea solo una asignación activa idéntica
 * (misma zona + misma fecha de inicio). Distintos periodos se permiten.
 */
class AsignacionZonaController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('permission:agentes.editar')->only(['store', 'update', 'destroy']);
    }


    public function store(Request $request, AgenteParqueo $agente): RedirectResponse
    {
        if ($agente->estado === AgenteParqueo::ESTADO_TERMINADO) {
            return back()->with('error', 'No se puede asignar zonas a un agente terminado.');
        }

        $datos = $this->validar($request);

        if ($this->existeDuplicado($agente, $datos)) {
            return back()->with('error', 'Ya existe una asignación activa para esa zona con la misma fecha de inicio.');
        }

        $agente->asignaciones()->create($datos);
        return back()->with('success', 'Zona asignada al agente.');
    }

    public function update(Request $request, AsignacionZona $asignacion): RedirectResponse
    {
        $agente = $asignacion->agente;
        if ($agente && $agente->estado === AgenteParqueo::ESTADO_TERMINADO) {
            return back()->with('error', 'No se pueden editar asignaciones de un agente terminado.');
        }

        $datos = $this->validar($request);

        if ($this->existeDuplicado($agente, $datos, $asignacion->id)) {
            return back()->with('error', 'Ya existe una asignación activa para esa zona con la misma fecha de inicio.');
        }

        $asignacion->update($datos);
        return back()->with('success', 'Asignación actualizada.');
    }

    public function destroy(AsignacionZona $asignacion): RedirectResponse
    {
        $asignacion->delete();
        return back()->with('success', 'Asignación eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'zona_id'      => ['required', 'integer', 'exists:zonas,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin'    => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'observacion'  => ['nullable', 'string', 'max:255'],
            'activa'       => ['required', 'boolean'],
        ]);
    }

    /**
     * Duplicado exacto: misma zona + misma fecha de inicio activa.
     */
    private function existeDuplicado(AgenteParqueo $agente, array $datos, ?int $exceptoId = null): bool
    {
        if (empty($datos['activa'])) {
            return false; // si queda inactiva, no hay conflicto
        }

        return $agente->asignaciones()
            ->when($exceptoId, fn ($q) => $q->where('id', '!=', $exceptoId))
            ->where('zona_id', $datos['zona_id'])
            ->whereDate('fecha_inicio', $datos['fecha_inicio'])
            ->where('activa', true)
            ->exists();
    }
}