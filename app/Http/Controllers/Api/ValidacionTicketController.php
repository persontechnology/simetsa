<?php

// app/Http/Controllers/Api/ValidacionTicketController.php

namespace App\Http\Controllers\Api;

use App\Http\Resources\TicketResource;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Validación de ticket en calle por placa del vehículo (uso exclusivo del agente).
 *
 * Devuelve el estado calculado en tiempo real, incluyendo la tolerancia
 * de 5 minutos post-expiración (Art. 13 Ordenanza SIMETSA).
 *
 * El permiso sesiones_parqueo.ver se aplica en routes/api.php.
 */
class ValidacionTicketController extends ApiController
{
    public function __construct(private readonly TicketService $servicio)
    {
    }

    /**
     * Valida el ticket vigente de un vehículo buscando por placa.
     *
     * @see Art. 13 Ordenanza SIMETSA (tolerancia 5 min).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $placa   Placa del vehículo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function validar(Request $request, string $placa): JsonResponse
    {
        $resultado = $this->servicio->validarPorPlaca($placa, now());

        $ticket = $resultado['ticket'];

        return $this->exito([
            'estado'               => $resultado['estado'],
            'ticket'               => $ticket ? new TicketResource($ticket) : null,
            'minutos_restantes'    => $resultado['minutos_restantes'],
            'en_tolerancia'        => $resultado['en_tolerancia'],
            'tolerancia_expira_en' => $resultado['tolerancia_expira_en']?->toIso8601String(),
        ], 'Validación de ticket por placa.');
    }
}
