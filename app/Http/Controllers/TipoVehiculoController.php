<?php
// app/Http/Controllers/TipoVehiculoController.php

namespace App\Http\Controllers;

use App\Http\Requests\TipoVehiculoStoreRequest;
use App\Http\Requests\TipoVehiculoUpdateRequest;
use App\Models\TipoVehiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD del catálogo de tipos de vehículo (Art. 25 Ordenanza SIMETSA).
 *
 * Gestión exclusiva de super_admin y director_seguridad.
 */
class TipoVehiculoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tipos_vehiculo.ver')->only('index');
        $this->middleware('permission:tipos_vehiculo.crear')->only(['create', 'store']);
        $this->middleware('permission:tipos_vehiculo.editar')->only(['edit', 'update']);
        $this->middleware('permission:tipos_vehiculo.eliminar')->only('destroy');
    }

    public function index(): View
    {
        $tipos = TipoVehiculo::orderBy('nombre')->paginate(20);
        return view('tipos-vehiculo.index', compact('tipos'));
    }

    public function create(): View
    {
        return view('tipos-vehiculo.create');
    }

    public function store(TipoVehiculoStoreRequest $request): RedirectResponse
    {
        $tipo = TipoVehiculo::create($request->validated());
        return redirect()->route('tipos-vehiculo.index')
            ->with('success', "Tipo de vehículo '{$tipo->nombre}' creado correctamente.");
    }

    public function edit(TipoVehiculo $tipo_vehiculo): View
    {
        return view('tipos-vehiculo.edit', ['tipoVehiculo' => $tipo_vehiculo]);
    }

    public function update(TipoVehiculoUpdateRequest $request, TipoVehiculo $tipo_vehiculo): RedirectResponse
    {
        $tipo_vehiculo->update($request->validated());
        return redirect()->route('tipos-vehiculo.index')
            ->with('success', "Tipo de vehículo '{$tipo_vehiculo->nombre}' actualizado.");
    }

    public function destroy(TipoVehiculo $tipo_vehiculo): RedirectResponse
    {
        $tipo_vehiculo->delete();
        return redirect()->route('tipos-vehiculo.index')
            ->with('success', "Tipo de vehículo '{$tipo_vehiculo->nombre}' desactivado.");
    }
}
