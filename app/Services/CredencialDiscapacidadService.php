<?php
// app/Services/CredencialDiscapacidadService.php

namespace App\Services;

use App\Models\CredencialDiscapacidad;
use App\Models\User;
use App\Models\Vehiculo;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Lógica de gestión de credenciales CONADIS (Art. 26 Ordenanza SIMETSA).
 *
 * Un vehículo solo puede tener una credencial activa (pendiente o aprobada)
 * a la vez. El conductor solicita; el comisario o director aprueba o rechaza.
 */
class CredencialDiscapacidadService
{
    /**
     * Registra una solicitud de credencial CONADIS para un vehículo.
     *
     * @see Art. 26 Ordenanza SIMETSA.
     *
     * @param  Vehiculo  $vehiculo
     * @param  array{numero_conadis:string, nombre_beneficiario:string, fecha_emision:string, fecha_vencimiento:?string, porcentaje_discapacidad:?int, archivo:?UploadedFile, observaciones:?string}  $datos
     * @return CredencialDiscapacidad
     *
     * @throws DomainException Si el vehículo ya tiene una credencial activa.
     */
    public function solicitar(Vehiculo $vehiculo, array $datos): CredencialDiscapacidad
    {
        $activa = CredencialDiscapacidad::where('vehiculo_id', $vehiculo->id)
            ->whereIn('estado', [CredencialDiscapacidad::ESTADO_PENDIENTE, CredencialDiscapacidad::ESTADO_APROBADA])
            ->exists();

        if ($activa) {
            throw new DomainException('Este vehículo ya tiene una credencial CONADIS activa. (Art. 26)');
        }

        return DB::transaction(function () use ($vehiculo, $datos) {
            if (isset($datos['archivo']) && $datos['archivo'] instanceof UploadedFile) {
                try {
                    $ruta = $datos['archivo']->store(
                        "credenciales/{$vehiculo->conductor_id}/{$vehiculo->id}",
                        'public',
                    );
                    $datos['ruta_archivo'] = $ruta;
                } catch (\Throwable $e) {
                    Log::error('Error al guardar archivo de credencial CONADIS', [
                        'vehiculo_id' => $vehiculo->id,
                        'error'       => $e->getMessage(),
                    ]);
                }
                unset($datos['archivo']);
            }

            return CredencialDiscapacidad::create(array_merge($datos, [
                'vehiculo_id' => $vehiculo->id,
                'estado'      => CredencialDiscapacidad::ESTADO_PENDIENTE,
            ]));
        });
    }

    /**
     * Aprueba una credencial CONADIS en estado pendiente.
     *
     * @see Art. 26 Ordenanza SIMETSA.
     *
     * @param  CredencialDiscapacidad  $credencial
     * @param  User                   $aprobadaPor  Comisario o director que aprueba.
     * @param  array<string, mixed>   $datos
     * @return CredencialDiscapacidad
     *
     * @throws DomainException Si la credencial no está en estado pendiente.
     */
    public function aprobar(CredencialDiscapacidad $credencial, User $aprobadaPor, array $datos = []): CredencialDiscapacidad
    {
        if ($credencial->estado !== CredencialDiscapacidad::ESTADO_PENDIENTE) {
            throw new DomainException('Solo se pueden aprobar credenciales en estado pendiente.');
        }

        $credencial->update([
            'estado'           => CredencialDiscapacidad::ESTADO_APROBADA,
            'aprobada_por'     => $aprobadaPor->id,
            'fecha_aprobacion' => now(),
            'observaciones'    => $datos['observaciones'] ?? $credencial->observaciones,
        ]);

        return $credencial->fresh();
    }

    /**
     * Rechaza una credencial CONADIS en estado pendiente.
     *
     * @see Art. 26 Ordenanza SIMETSA.
     *
     * @param  CredencialDiscapacidad  $credencial
     * @param  array{observaciones:string}  $datos  Debe incluir observaciones del motivo de rechazo.
     * @return CredencialDiscapacidad
     *
     * @throws DomainException Si la credencial no está en estado pendiente o falta la justificación.
     */
    public function rechazar(CredencialDiscapacidad $credencial, array $datos): CredencialDiscapacidad
    {
        if ($credencial->estado !== CredencialDiscapacidad::ESTADO_PENDIENTE) {
            throw new DomainException('Solo se pueden rechazar credenciales en estado pendiente.');
        }

        if (empty($datos['observaciones'])) {
            throw new DomainException('Las observaciones son obligatorias al rechazar una credencial.');
        }

        $credencial->update([
            'estado'        => CredencialDiscapacidad::ESTADO_RECHAZADA,
            'observaciones' => $datos['observaciones'],
        ]);

        return $credencial->fresh();
    }
}
