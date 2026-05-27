<?php
// app/Http/Controllers/HorarioRotativoController.php

namespace App\Http\Controllers;

use App\Models\AgenteParqueo;
use App\Models\HorarioOperacion;
use App\Models\HorarioRotativo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controlador de horarios rotativos de un agente (Art. 37.4).
 *
 * El día debe ser de operación (Art. 12). Regla de duplicado: se bloquea solo
 * un horario activo idéntico (misma zona + mismo día + misma "vigente desde").
 */
class HorarioRotativoController extends Controller
{
    /* public static function middleware(): array
    {
        return [new Middleware('permission:agentes.editar', only: ['store', 'update', 'destroy'])];
    } */
   public function __construct()
    {
        $this->middleware('permission:agentes.editar')->only(['store', 'update', 'destroy']);
    }

    public function store(Request $request, AgenteParqueo $agente): RedirectResponse
    {
        if ($agente->estado === AgenteParqueo::ESTADO_TERMINADO) {
            return back()->with('error', 'No se pueden asignar horarios a un agente terminado.');
        }

        $datos = $this->validar($request);

        if ($this->existeDuplicado($agente, $datos)) {
            return back()->with('error', 'Ya existe un horario activo para esa zona y día con la misma fecha de inicio de vigencia.');
        }

        $agente->horarios()->create($datos + ['activo' => true]);
        return back()->with('success', 'Horario rotativo agregado.');
    }

    public function update(Request $request, HorarioRotativo $horario): RedirectResponse
    {
        $agente = $horario->agente;
        if ($agente && $agente->estado === AgenteParqueo::ESTADO_TERMINADO) {
            return back()->with('error', 'No se pueden editar horarios de un agente terminado.');
        }

        $datos = $this->validar($request);

        if ($this->existeDuplicado($agente, $datos, $horario->id)) {
            return back()->with('error', 'Ya existe un horario activo para esa zona y día con la misma fecha de inicio de vigencia.');
        }

        $horario->update($datos);
        return back()->with('success', 'Horario actualizado.');
    }

    public function destroy(HorarioRotativo $horario): RedirectResponse
    {
        $horario->delete();
        return back()->with('success', 'Horario eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request): array
    {
        $diasOperativos = HorarioOperacion::where('activo', true)->pluck('dia_semana')->all();

        return $request->validate([
            'zona_id'       => ['required', 'integer', 'exists:zonas,id'],
            'dia_semana'    => ['required', 'integer', Rule::in($diasOperativos)],
            'hora_inicio'   => ['required', 'date_format:H:i'],
            'hora_fin'      => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'vigente_desde' => ['required', 'date'],
            'vigente_hasta' => ['nullable', 'date', 'after_or_equal:vigente_desde'],
            'observacion'   => ['nullable', 'string', 'max:255'],
        ], [
            'dia_semana.in' => 'El día seleccionado no es un día de operación del SIMETSA (Art. 12).',
        ]);
    }

    /**
     * Duplicado exacto: misma zona + día + misma fecha de inicio de vigencia activa.
     */
    private function existeDuplicado(AgenteParqueo $agente, array $datos, ?int $exceptoId = null): bool
    {
        return $agente->horarios()
            ->when($exceptoId, fn ($q) => $q->where('id', '!=', $exceptoId))
            ->where('zona_id', $datos['zona_id'])
            ->where('dia_semana', $datos['dia_semana'])
            ->whereDate('vigente_desde', $datos['vigente_desde'])
            ->where('activo', true)
            ->exists();
    }
}