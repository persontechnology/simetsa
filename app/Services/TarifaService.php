<?php
// app/Services/TarifaService.php

namespace App\Services;

use App\Models\Tarifa;
use Carbon\Carbon;

/**
 * Servicio de gestión y cálculo de tarifas del SIMETSA.
 *
 * Responsabilidades:
 *  - Determinar la tarifa aplicable a un TipoPlaza en una fecha específica.
 *  - Calcular el costo total de parqueo "por hora o fracción" (Art. 22).
 *  - Detectar solapamientos de rangos para validaciones.
 */
class TarifaService
{
    /**
     * Devuelve la tarifa vigente para un tipo de plaza en la fecha dada
     * (por defecto: ahora). Útil para emisión de tickets y reportes
     * históricos.
     *
     * @param  int                              $tipoPlazaId
     * @param  \DateTimeInterface|null          $fecha
     * @return \App\Models\Tarifa|null
     */
    public function tarifaVigente(int $tipoPlazaId, ?\DateTimeInterface $fecha = null): ?Tarifa
    {
        $fecha = $fecha ? Carbon::instance($fecha) : now();

        return Tarifa::where('tipo_plaza_id', $tipoPlazaId)
            ->where('activo', true)
            ->whereDate('vigente_desde', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->whereNull('vigente_hasta')
                  ->orWhereDate('vigente_hasta', '>=', $fecha);
            })
            ->orderBy('vigente_desde', 'desc')
            ->first();
    }

    /**
     * Calcula el costo total en USD para X minutos de parqueo aplicando
     * la regla del Art. 22: "$0.25 por hora o fracción de hora".
     *
     * Redondeo hacia arriba: cualquier minuto iniciado de la siguiente hora
     * cuenta como hora completa.
     *
     * Ejemplos con $0.25/hora:
     *   1   min → $0.25
     *   30  min → $0.25
     *   60  min → $0.25 (exactamente 1 hora)
     *   61  min → $0.50
     *   120 min → $0.50
     *   121 min → $0.75
     *
     * @param  \App\Models\Tarifa  $tarifa
     * @param  int                 $minutos
     * @return float               Costo total en USD con 2 decimales
     */
    public function calcularCosto(Tarifa $tarifa, int $minutos): float
    {
        if ($minutos <= 0) {
            return 0.0;
        }
        $horas = (int) ceil($minutos / 60);
        return round($horas * (float) $tarifa->valor_hora, 2);
    }

    /**
     * Verifica si las fechas dadas se solapan con alguna tarifa activa
     * existente del mismo tipo de plaza.
     *
     * Algoritmo de solapamiento de rangos:
     *  El rango A = [aInicio, aFin] solapa con B = [bInicio, bFin] si:
     *    aInicio <= bFin AND aFin >= bInicio
     *  Cuando un rango no tiene fin (NULL), se asume infinito.
     *
     * @param  int          $tipoPlazaId
     * @param  string       $vigenteDesde   Fecha Y-m-d
     * @param  string|null  $vigenteHasta   Fecha Y-m-d o null (infinito)
     * @param  int|null     $exceptoId      ID a excluir (para updates)
     * @return bool
     */
    public function existeSolapamiento(
        int $tipoPlazaId,
        string $vigenteDesde,
        ?string $vigenteHasta = null,
        ?int $exceptoId = null
    ): bool {
        $finNuevo = $vigenteHasta ?? '9999-12-31';

        $query = Tarifa::where('tipo_plaza_id', $tipoPlazaId)
            ->where('activo', true);

        if ($exceptoId) {
            $query->where('id', '!=', $exceptoId);
        }

        return $query->where(function ($q) use ($vigenteDesde, $finNuevo) {
            // existente.inicio <= nuevo.fin
            $q->whereDate('vigente_desde', '<=', $finNuevo)
              // existente.fin >= nuevo.inicio (o NULL = infinito)
              ->where(function ($q2) use ($vigenteDesde) {
                  $q2->whereNull('vigente_hasta')
                     ->orWhereDate('vigente_hasta', '>=', $vigenteDesde);
              });
        })->exists();
    }
}