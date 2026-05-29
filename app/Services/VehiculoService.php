<?php
// app/Services/VehiculoService.php

namespace App\Services;

use App\Models\Conductor;
use App\Models\Vehiculo;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de gestión de vehículos de conductores (Art. 25 Ordenanza SIMETSA).
 *
 * Un conductor puede tener múltiples vehículos. La placa es única entre los
 * vehículos no eliminados. Las colisiones de placa se detectan aquí antes
 * de intentar la inserción; el índice parcial de PostgreSQL es la segunda línea
 * de defensa (evita condiciones de carrera bajo concurrencia).
 */
class VehiculoService
{
    /**
     * Registra un nuevo vehículo para el conductor.
     *
     * @see Art. 25 Ordenanza SIMETSA.
     *
     * @param  Conductor  $conductor
     * @param  array{tipo_vehiculo_id:int, placa:string, marca:string, modelo:string, anio:int, color:string, observaciones:?string}  $datos
     * @return Vehiculo
     *
     * @throws DomainException Si la placa ya está registrada en un vehículo activo.
     */
    public function registrar(Conductor $conductor, array $datos): Vehiculo
    {
        $placaUpper = strtoupper($datos['placa']);

        if (Vehiculo::whereRaw('UPPER(placa) = ?', [$placaUpper])->exists()) {
            throw new DomainException("La placa {$placaUpper} ya está registrada en el sistema. (Art. 25)");
        }

        return DB::transaction(function () use ($conductor, $datos, $placaUpper) {
            return $conductor->vehiculos()->create(array_merge($datos, [
                'placa'  => $placaUpper,
                'estado' => Vehiculo::ESTADO_ACTIVO,
            ]));
        });
    }

    /**
     * Actualiza los datos de un vehículo.
     *
     * @see Art. 25 Ordenanza SIMETSA.
     *
     * @param  Vehiculo              $vehiculo
     * @param  array<string, mixed>  $datos
     * @return Vehiculo
     *
     * @throws DomainException Si la placa nueva ya pertenece a otro vehículo activo.
     */
    public function actualizar(Vehiculo $vehiculo, array $datos): Vehiculo
    {
        if (isset($datos['placa'])) {
            $placaUpper     = strtoupper($datos['placa']);
            $datos['placa'] = $placaUpper;

            $conflicto = Vehiculo::whereRaw('UPPER(placa) = ?', [$placaUpper])
                ->where('id', '!=', $vehiculo->id)
                ->exists();

            if ($conflicto) {
                throw new DomainException("La placa {$placaUpper} ya está registrada en el sistema. (Art. 25)");
            }
        }

        return DB::transaction(function () use ($vehiculo, $datos) {
            $vehiculo->update($datos);

            return $vehiculo->fresh();
        });
    }

    /**
     * Cambia el estado activo/inactivo de un vehículo (supervisión backoffice).
     *
     * @see Art. 25 Ordenanza SIMETSA.
     *
     * @param  Vehiculo  $vehiculo
     * @param  string    $estado   'activo' | 'inactivo'
     * @return Vehiculo
     *
     * @throws DomainException Si el estado no es válido.
     */
    public function cambiarEstado(Vehiculo $vehiculo, string $estado): Vehiculo
    {
        if (! in_array($estado, [Vehiculo::ESTADO_ACTIVO, Vehiculo::ESTADO_INACTIVO], true)) {
            throw new DomainException("Estado '{$estado}' no válido para un vehículo.");
        }

        $vehiculo->update(['estado' => $estado]);

        return $vehiculo->fresh();
    }

    /**
     * Elimina el vehículo (soft delete).
     *
     * El índice parcial de PostgreSQL excluye los registros con deleted_at
     * no nulo, por lo que la misma placa puede re-registrarse después.
     *
     * @param  Vehiculo  $vehiculo
     * @return void
     */
    public function eliminar(Vehiculo $vehiculo): void
    {
        $vehiculo->delete();
    }
}
