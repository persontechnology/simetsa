<?php
// app/Http/Controllers/VehiculoController.php

namespace App\Http\Controllers;

use App\Models\TipoVehiculo;
use App\Models\Vehiculo;
use App\Services\VehiculoService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Supervisión de vehículos de conductores desde el backoffice (Art. 25 Ordenanza SIMETSA).
 *
 * El CRUD lo ejecuta el conductor desde la app móvil (Api\VehiculoController).
 * El backoffice permite listar, ver detalle y cambiar el estado de cualquier vehículo.
 */
class VehiculoController extends Controller
{
    public function __construct(private readonly VehiculoService $servicio)
    {
        $this->middleware('permission:vehiculos.ver')->only(['index', 'show']);
        $this->middleware('permission:vehiculos.editar')->only('cambiarEstado');
    }

    /**
     * Lista todos los vehículos con filtros opcionales (placa, tipo, estado).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $query = Vehiculo::with(['conductor.user.perfil', 'tipoVehiculo'])
            ->orderByDesc('created_at');

        if ($placa = $request->query('placa')) {
            $query->whereRaw('UPPER(placa) LIKE ?', ['%' . strtoupper($placa) . '%']);
        }

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        if ($tipoId = $request->query('tipo_vehiculo_id')) {
            $query->where('tipo_vehiculo_id', (int) $tipoId);
        }

        $vehiculos = $query->paginate(20)->withQueryString();
        $tipos     = TipoVehiculo::where('activo', true)->orderBy('nombre')->get();

        return view('vehiculos.index', compact('vehiculos', 'tipos'));
    }

    /**
     * Muestra el detalle de un vehículo con su conductor propietario.
     *
     * @param  \App\Models\Vehiculo  $vehiculo
     * @return \Illuminate\View\View
     */
    public function show(Vehiculo $vehiculo): View
    {
        $vehiculo->loadMissing(['conductor.user.perfil', 'tipoVehiculo']);

        return view('vehiculos.show', compact('vehiculo'));
    }

    /**
     * Cambia el estado activo/inactivo de un vehículo (Art. 25).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Vehiculo      $vehiculo
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cambiarEstado(Request $request, Vehiculo $vehiculo): RedirectResponse
    {
        $request->validate(['estado' => ['required', 'string', 'in:activo,inactivo']]);

        try {
            $this->servicio->cambiarEstado($vehiculo, $request->estado);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Estado del vehículo {$vehiculo->placa} actualizado.");
    }
}
