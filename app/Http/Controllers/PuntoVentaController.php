<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivacionPuntoVentaRequest;
use App\Models\PuntoVenta;
use App\Models\SolicitudPuntoVenta;
use App\Services\PuntoVentaService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

/**
 * Gestión de puntos de venta activos y su activación (Art. 31, Art. 21).
 */
class PuntoVentaController extends Controller
{
    public function __construct(private readonly PuntoVentaService $servicio)
    {
        $this->middleware('permission:puntos_venta.ver', ['only' => ['index', 'show']]);
        $this->middleware('permission:puntos_venta.crear', ['only' => ['activar']]);
        $this->middleware('permission:puntos_venta.editar', ['only' => ['cambiarEstado']]);
    }
    

    public function index(): View
    {
        $puntos = PuntoVenta::with(['user', 'contrato'])->orderByDesc('id')->paginate(15);

        return view('puntos-venta.index', ['puntos' => $puntos]);
    }

    public function show(PuntoVenta $punto): View
    {
        $punto->load(['user', 'contrato', 'solicitud']);

        return view('puntos-venta.show', ['punto' => $punto]);
    }

    /**
     * Firma el contrato y activa el punto de venta a partir de su solicitud.
     */
    public function activar(ActivacionPuntoVentaRequest $request, SolicitudPuntoVenta $solicitud): RedirectResponse
    {
        try {
            $resultado = $this->servicio->activar($solicitud, $request->validated());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $redireccion = redirect()->route('puntos-venta.show', $resultado['punto_venta'])
            ->with('success', 'Punto de venta activado correctamente.');

        // La contraseña temporal solo existe si se creó una cuenta nueva.
        if ($resultado['password_temporal'] !== null) {
            $redireccion->with('password_temporal', $resultado['password_temporal']);
        }

        return $redireccion;
    }

    public function cambiarEstado(Request $request, PuntoVenta $punto): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in([
                PuntoVenta::ESTADO_ACTIVO,
                PuntoVenta::ESTADO_SUSPENDIDO,
                PuntoVenta::ESTADO_INACTIVO,
            ])],
        ]);

        $punto->update($datos);

        return back()->with('success', 'Estado del punto de venta actualizado.');
    }
}