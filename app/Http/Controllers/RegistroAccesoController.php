<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroAcceso;

class RegistroAccesoController extends Controller
{
    //
    /**
     * Mostrar el listado de registros de acceso con filtros.
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['buscar', 'evento', 'desde', 'hasta']);

        $query = RegistroAcceso::with('user')->orderBy('ocurrido_en', 'desc');

        if (!empty($filtros['buscar'])) {
            $buscar = $filtros['buscar'];
            $query->where(function ($q) use ($buscar) {
                $q->where('email_intento', 'like', "%{$buscar}%")
                  ->orWhereHas('user', function ($q2) use ($buscar) {
                      $q2->where('name', 'like', "%{$buscar}%")
                         ->orWhere('email', 'like', "%{$buscar}%");
                  });
            });
        }

        if (!empty($filtros['evento'])) {
            $query->where('evento', $filtros['evento']);
        }

        if (!empty($filtros['desde'])) {
            $query->whereDate('ocurrido_en', '>=', $filtros['desde']);
        }

        if (!empty($filtros['hasta'])) {
            $query->whereDate('ocurrido_en', '<=', $filtros['hasta']);
        }

        $registros = $query->paginate(15)->withQueryString();

        $eventos = RegistroAcceso::listadoEventos();

        return view('registro-accesos.index', compact('registros', 'eventos', 'filtros'));
    }
}
