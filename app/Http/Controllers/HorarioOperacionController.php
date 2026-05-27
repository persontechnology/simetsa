<?php
// app/Http/Controllers/HorarioOperacionController.php

namespace App\Http\Controllers;

use App\Http\Requests\HorarioOperacionUpdateRequest;
use App\Models\HorarioOperacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador de HorarioOperacion. Solo expone index + edit + update,
 * porque hay exactamente 7 registros fijos (uno por día de la semana).
 */
class HorarioOperacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:horarios.ver')->only('index');
        $this->middleware('permission:horarios.editar')->only(['edit', 'update']);
    }

    public function index(): View
    {
        $horarios = HorarioOperacion::orderBy('dia_semana')->get();
        return view('horarios-operacion.index', compact('horarios'));
    }

    public function edit(HorarioOperacion $horario_operacion): View
    {
        return view('horarios-operacion.edit', ['horario' => $horario_operacion]);
    }

    public function update(HorarioOperacionUpdateRequest $request, HorarioOperacion $horario_operacion): RedirectResponse
    {
        $horario_operacion->update($request->validated());
        return redirect()->route('horarios-operacion.index')
            ->with('success', "Horario de {$horario_operacion->nombre_dia} actualizado.");
    }
}