<?php
// app/Http/Controllers/AgenteParqueoController.php

namespace App\Http\Controllers;

use App\Http\Requests\AutorizacionAgenteRequest;
use App\Models\AgenteParqueo;
use App\Models\ExpedienteAgente;
use App\Models\HorarioOperacion;
use App\Models\HorarioRotativo;
use App\Models\SolicitudAgente;
use App\Models\Zona;
use App\Services\AgenteParqueoService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controlador de Agentes de Parqueo (Etapa 3 y gestión del agente activo).
 */
class AgenteParqueoController extends Controller
{
    public function __construct(private AgenteParqueoService $servicio) {
        $this->middleware('permission:agentes.ver')->only(['index', 'show']);
        $this->middleware('permission:agentes.crear')->only('autorizar');
        $this->middleware('permission:agentes.editar')->only(['actualizarExpediente', 'cambiarEstado']);
    }
  

    public function index(Request $request): View
    {
        $estado = $request->input('estado');

        $agentes = AgenteParqueo::with('user')
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('fecha_autorizacion')
            ->paginate(20)->withQueryString();

        return view('agentes-parqueo.index', [
            'agentes' => $agentes,
            'estados' => AgenteParqueo::listadoEstados(),
            'estado'  => $estado,
        ]);
    }

    public function show(AgenteParqueo $agente): View
    {
        $agente->load([
            'user', 'solicitud', 'expediente',
            'asignaciones.zona', 'horarios.zona', 'amonestaciones.registradaPor',
        ]);

        // Días operativos (Art. 12) con su franja horaria, para el formulario de horario
        $diaHorarios = HorarioOperacion::where('activo', true)
            ->orderBy('dia_semana')->get()
            ->mapWithKeys(fn ($ho) => [
                $ho->dia_semana => [
                    'nombre' => HorarioRotativo::listadoDias()[$ho->dia_semana] ?? (string) $ho->dia_semana,
                    'inicio' => Carbon::parse($ho->hora_inicio)->format('H:i'),
                    'fin'    => Carbon::parse($ho->hora_fin)->format('H:i'),
                ],
            ])->toArray();

        return view('agentes-parqueo.show', [
            'agente'      => $agente,
            'zonas'       => Zona::activas()->orderBy('nombre')->get(),
            'diaHorarios' => $diaHorarios,
        ]);
    }

    /**
     * Autoriza una solicitud y crea el agente (Etapa 3, Art. 36).
     * Solo cambia el éxito: la contraseña temporal se muestra solo si se creó una cuenta nueva.
     */
    public function autorizar(AutorizacionAgenteRequest $request, SolicitudAgente $solicitud): RedirectResponse
    {
        try {
            $resultado = $this->servicio->autorizar($solicitud, $request->validated());

            $redirect = redirect()->route('agentes-parqueo.show', $resultado['agente'])
                ->with('success', "Agente autorizado: {$resultado['agente']->codigo}.");

            // Solo hay contraseña temporal cuando se creó una cuenta nueva
            if ($resultado['password_temporal']) {
                $redirect->with('password_temporal', $resultado['password_temporal']);
            }

            return $redirect;
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Error al autorizar agente', ['solicitud' => $solicitud->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'No se pudo autorizar al agente. Verificá que la cédula no esté ya registrada en otro perfil.');
        }
    }

    /**
     * Actualiza las observaciones del expediente del agente.
     */
    public function actualizarExpediente(Request $request, AgenteParqueo $agente): RedirectResponse
    {
        $datos = $request->validate(['observaciones' => ['nullable', 'string']]);

        ExpedienteAgente::updateOrCreate(
            ['agente_parqueo_id' => $agente->id],
            ['observaciones' => $datos['observaciones'], 'fecha_apertura' => $agente->expediente?->fecha_apertura ?? now()->toDateString()]
        );

        return back()->with('success', 'Expediente actualizado.');
    }

    /**
     * Cambia el estado del agente entre activo y suspendido.
     * (La terminación definitiva — Art. 40.c — se maneja en 3.D.)
     */
    public function cambiarEstado(Request $request, AgenteParqueo $agente): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in([AgenteParqueo::ESTADO_ACTIVO, AgenteParqueo::ESTADO_SUSPENDIDO])],
        ]);

        $agente->update(['estado' => $datos['estado']]);
        return back()->with('success', "Estado del agente actualizado a '{$agente->estado_label}'.");
    }
}