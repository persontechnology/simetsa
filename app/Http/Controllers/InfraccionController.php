<?php

// app/Http/Controllers/InfraccionController.php

namespace App\Http\Controllers;

use App\Enums\EstadoInfraccion;
use App\Enums\TipoInfraccion;
use App\Http\Requests\AnularInfraccionRequest;
use App\Models\AgenteParqueo;
use App\Models\Infraccion;
use App\Models\Zona;
use App\Services\InfraccionService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Backoffice de supervisión y anulación de infracciones (Arts. 15, 28-30 Ordenanza SIMETSA).
 *
 * Acceso:
 *  - super_admin, comisario, director_seguridad: index + show.
 *  - comisario y super_admin: anular (permiso infracciones.registrar).
 */
class InfraccionController extends Controller
{
    public function __construct(private readonly InfraccionService $servicio)
    {
        $this->middleware('role:super_admin|comisario|director_seguridad')
            ->only(['index', 'show']);
        $this->middleware('permission:infracciones.registrar')
            ->only(['anular']);
    }

    /**
     * Lista infracciones con filtros: placa, zona, tipo, estado, agente, fecha.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = Infraccion::with(['agente.user', 'zona', 'calle', 'inmovilizacion'])
            ->when($request->placa, fn ($q, $p) =>
                $q->where('placa', strtoupper(trim($p)))
            )
            ->when($request->zona_id, fn ($q, $z) =>
                $q->where('zona_id', $z)
            )
            ->when($request->tipo_infraccion, fn ($q, $t) =>
                $q->where('tipo_infraccion', $t)
            )
            ->when($request->estado, fn ($q, $e) =>
                $q->where('estado', $e)
            )
            ->when($request->agente_parqueo_id, fn ($q, $a) =>
                $q->where('agente_parqueo_id', $a)
            )
            ->when($request->fecha_desde, fn ($q, $f) =>
                $q->whereDate('created_at', '>=', $f)
            )
            ->when($request->fecha_hasta, fn ($q, $f) =>
                $q->whereDate('created_at', '<=', $f)
            )
            ->orderByDesc('created_at');

        return view('infracciones.index', [
            'infracciones' => $query->paginate(25)->withQueryString(),
            'zonas'        => Zona::where('activo', true)->orderBy('nombre')->get(),
            'agentes'      => AgenteParqueo::where('estado', AgenteParqueo::ESTADO_ACTIVO)
                ->with('user')
                ->orderBy('codigo')
                ->get(),
            'tipos'        => collect(TipoInfraccion::cases())->mapWithKeys(
                fn ($t) => [$t->value => $t->etiqueta()]
            )->all(),
            'estados'      => collect(EstadoInfraccion::cases())->mapWithKeys(
                fn ($e) => [$e->value => $e->etiqueta()]
            )->all(),
        ]);
    }

    /**
     * Detalle de una infracción con inmovilización e historial de pagos.
     *
     * @param  \App\Models\Infraccion  $infraccion
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Infraccion $infraccion): View
    {
        $infraccion->load([
            'agente.user.perfil',
            'zona',
            'calle',
            'conductor.user.perfil',
            'ticket',
            'inmovilizacion.agente.user',
            'transacciones',
            'anuladaPor.perfil',
        ]);

        return view('infracciones.show', compact('infraccion'));
    }

    /**
     * Anula administrativamente una infracción.
     *
     * Solo el comisario o super_admin puede ejecutar esta acción.
     *
     * @param  \App\Http\Requests\AnularInfraccionRequest  $request
     * @param  \App\Models\Infraccion                      $infraccion
     * @return \Illuminate\Http\RedirectResponse
     */
    public function anular(AnularInfraccionRequest $request, Infraccion $infraccion): RedirectResponse
    {
        try {
            $this->servicio->anular($infraccion, $request->user(), $request->validated()['motivo']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('infracciones.show', $infraccion)
            ->with('success', "Infracción #{$infraccion->id} anulada correctamente.");
    }
}
