<?php

namespace App\Services;

use App\Models\DocumentoPuntoVenta;
use App\Models\SolicitudPuntoVenta;
use DomainException;

/**
 * Lógica de la solicitud de punto de venta (Art. 31).
 */
class SolicitudPuntoVentaService
{
    /**
     * Tipos de documento obligatorios (Art. 31).
     *
     * @return array<int, string>
     */
    public function documentosRequeridos(): array
    {
        return [
            DocumentoPuntoVenta::TIPO_SOLICITUD_ALCALDE,
            DocumentoPuntoVenta::TIPO_CEDULA,
            DocumentoPuntoVenta::TIPO_NO_ADEUDAR,
            DocumentoPuntoVenta::TIPO_PATENTE,
        ];
    }

    public function generarCodigo(): string
    {
        $ultimo = SolicitudPuntoVenta::withTrashed()->max('id') ?? 0;

        return 'SPV-' . str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }

    public function documentacionCompleta(SolicitudPuntoVenta $solicitud): bool
    {
        $validados = $solicitud->documentos()->where('validado', true)->pluck('tipo')->unique();

        foreach ($this->documentosRequeridos() as $requerido) {
            if (! $validados->contains($requerido)) {
                return false;
            }
        }

        return true;
    }

    public function aprobarDocumentacion(SolicitudPuntoVenta $solicitud): void
    {
        if (! $solicitud->enEtapaDocumentacion()) {
            throw new DomainException('La solicitud no está en etapa de documentación.');
        }

        if (! $this->documentacionCompleta($solicitud)) {
            throw new DomainException('Faltan documentos requeridos validados (Art. 31).');
        }

        $solicitud->update(['estado' => SolicitudPuntoVenta::ESTADO_CONTRATO]);
    }

    public function rechazar(SolicitudPuntoVenta $solicitud, string $motivo): void
    {
        $solicitud->update([
            'estado' => SolicitudPuntoVenta::ESTADO_RECHAZADA,
            'motivo_rechazo' => $motivo,
        ]);
    }
}