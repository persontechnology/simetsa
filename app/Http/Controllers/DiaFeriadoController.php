<?php
// app/Http/Controllers/DiaFeriadoController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiaFeriadoRequest;
use App\Http\Requests\UpdateDiaFeriadoRequest;
use App\Models\DiaFeriado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiaFeriadoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:feriados.ver')->only('index');
        $this->middleware('permission:feriados.crear')->only(['create', 'store']);
        $this->middleware('permission:feriados.editar')->only(['edit', 'update']);
        $this->middleware('permission:feriados.eliminar')->only('destroy');
    }

    public function index(Request $request): View
    {
        $ano  = (int) $request->input('ano', date('Y'));
        $tipo = $request->input('tipo');

        $query = DiaFeriado::ano($ano)->orderBy('fecha');
        if ($tipo) $query->tipo($tipo);

        return view('dias-feriado.index', [
            'feriados' => $query->paginate(30)->withQueryString(),
            'tipos'    => DiaFeriado::listadoTipos(),
            'filtros'  => ['ano' => $ano, 'tipo' => $tipo],
        ]);
    }

    public function create(): View
    {
        return view('dias-feriado.create', ['tipos' => DiaFeriado::listadoTipos()]);
    }

    public function store(StoreDiaFeriadoRequest $request): RedirectResponse
    {
        $f = DiaFeriado::create($request->validated());
        return redirect()->route('dias-feriado.index')
            ->with('success', "Feriado '{$f->nombre}' creado correctamente.");
    }

    public function edit(DiaFeriado $dia_feriado): View
    {
        return view('dias-feriado.edit', [
            'feriado' => $dia_feriado,
            'tipos'   => DiaFeriado::listadoTipos(),
        ]);
    }

    public function update(UpdateDiaFeriadoRequest $request, DiaFeriado $dia_feriado): RedirectResponse
    {
        $dia_feriado->update($request->validated());
        return redirect()->route('dias-feriado.index')
            ->with('success', "Feriado '{$dia_feriado->nombre}' actualizado.");
    }

    public function destroy(DiaFeriado $dia_feriado): RedirectResponse
    {
        $dia_feriado->delete();
        return redirect()->route('dias-feriado.index')
            ->with('success', "Feriado '{$dia_feriado->nombre}' eliminado (soft delete).");
    }
}