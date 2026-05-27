<?php
// app/Services/AmonestacionService.php

namespace App\Services;

use App\Models\AgenteParqueo;
use App\Models\AmonestacionAgente;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de la escalada de amonestaciones del agente (Art. 40).
 *
 * 1.ª falta → verbal, 2.ª → escrita, 3.ª → terminación. El tipo y el número
 * se recalculan en orden cronológico tanto al registrar como al eliminar, y
 * el estado del agente se ajusta (terminado / reactivado) en consecuencia.
 */
class AmonestacionService
{
    /**
     * Registra una amonestación y recalcula la escalada.
     *
     * @param  array{motivo:string, fecha:?string}  $datos
     *
     * @throws \DomainException Si el agente ya está terminado.
     */
    public function registrar(AgenteParqueo $agente, array $datos): AmonestacionAgente
    {
        if ($agente->estado === AgenteParqueo::ESTADO_TERMINADO) {
            throw new DomainException('El agente ya tiene la autorización terminada; no admite nuevas amonestaciones.');
        }

        return DB::transaction(function () use ($agente, $datos) {
            $amonestacion = $agente->amonestaciones()->create([
                'tipo'           => AmonestacionAgente::TIPO_VERBAL, // provisional; se recalcula
                'numero_falta'   => 0,
                'motivo'         => $datos['motivo'],
                'fecha'          => $datos['fecha'] ?? now()->toDateString(),
                'registrada_por' => auth()->id(),
            ]);

            $this->recalcular($agente);

            return $amonestacion->fresh();
        });
    }

    /**
     * Actualiza el motivo/fecha de una amonestación y recalcula la escalada
     * (por si el cambio de fecha altera el orden cronológico).
     *
     * @param  array{motivo:string, fecha:?string}  $datos
     */
    public function actualizar(AmonestacionAgente $amonestacion, array $datos): void
    {
        DB::transaction(function () use ($amonestacion, $datos) {
            $amonestacion->update([
                'motivo' => $datos['motivo'],
                'fecha'  => $datos['fecha'] ?? $amonestacion->fecha,
            ]);

            if ($amonestacion->agente) {
                $this->recalcular($amonestacion->agente);
            }
        });
    }

    /**
     * Elimina una amonestación y recalcula la escalada (puede reactivar al agente).
     */
    public function eliminar(AmonestacionAgente $amonestacion): void
    {
        $agente = $amonestacion->agente;

        DB::transaction(function () use ($amonestacion, $agente) {
            $amonestacion->delete();
            if ($agente) {
                $this->recalcular($agente);
            }
        });
    }

    /**
     * Recalcula número y tipo de las faltas vigentes (orden cronológico) y
     * ajusta el estado del agente: terminado si hay 3.ª falta, reactivado a
     * activo si ya no la hay (la terminación solo proviene de amonestaciones).
     */
    private function recalcular(AgenteParqueo $agente): void
    {
        $lista = AmonestacionAgente::where('agente_parqueo_id', $agente->id)
            ->orderBy('fecha')->orderBy('id')->get();

        $hayTerminacion = false;

        foreach ($lista as $indice => $am) {
            $numero = $indice + 1;
            $tipo = match (true) {
                $numero >= 3  => AmonestacionAgente::TIPO_TERMINACION,
                $numero === 2 => AmonestacionAgente::TIPO_ESCRITA,
                default       => AmonestacionAgente::TIPO_VERBAL,
            };
            if ($numero >= 3) {
                $hayTerminacion = true;
            }
            $am->update(['numero_falta' => $numero, 'tipo' => $tipo]);
        }

        if ($hayTerminacion) {
            $agente->update(['estado' => AgenteParqueo::ESTADO_TERMINADO]);
        } elseif ($agente->estado === AgenteParqueo::ESTADO_TERMINADO) {
            // Ya no hay 3.ª falta → se revierte la terminación
            $agente->update(['estado' => AgenteParqueo::ESTADO_ACTIVO]);
        }
    }
}