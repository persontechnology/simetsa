<?php
// app/Services/SolicitudAgenteService.php

namespace App\Services;

use App\Models\DocumentoAgente;
use App\Models\SolicitudAgente;
use DomainException;

/**
 * Lógica de negocio del trámite de Agente de Parqueo, Etapa 1 (Art. 32-35).
 *
 * Centraliza: generación de folio, documentos requeridos, verificación de
 * completitud y las transiciones de estado (aprobar documentación / rechazar).
 */
class SolicitudAgenteService
{
    /**
     * Documentos obligatorios para superar la Etapa 1 (Art. 33 req. 1-4 + Art. 34).
     *
     * @return array<int, string>
     */
    public function documentosRequeridos(): array
    {
        return [
            DocumentoAgente::TIPO_OFICIO,
            DocumentoAgente::TIPO_CEDULA,
            DocumentoAgente::TIPO_EDUCACION,
            DocumentoAgente::TIPO_ANTECEDENTES,
            DocumentoAgente::TIPO_NO_ADEUDAR,
        ];
    }

    /**
     * Genera el siguiente folio correlativo (SA-0001, SA-0002, ...).
     */
    public function generarCodigo(): string
    {
        $ultimoId = SolicitudAgente::withTrashed()->max('id') ?? 0;
        return 'SA-' . str_pad((string) ($ultimoId + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * ¿Están cargados y validados TODOS los documentos requeridos?
     */
    public function documentacionCompleta(SolicitudAgente $solicitud): bool
    {
        $validados = $solicitud->documentos()
            ->where('validado', true)
            ->pluck('tipo')->unique()->all();

        return empty(array_diff($this->documentosRequeridos(), $validados));
    }

    /**
     * Aprueba la documentación y mueve la solicitud a la etapa de capacitación.
     *
     * @throws \DomainException Si faltan documentos requeridos validados.
     */
    public function aprobarDocumentacion(SolicitudAgente $solicitud): void
    {
        if (!$solicitud->enEtapaDocumentacion()) {
            throw new DomainException('La solicitud no está en la etapa de documentación.');
        }
        if (!$this->documentacionCompleta($solicitud)) {
            throw new DomainException('Faltan documentos requeridos validados (Art. 33 req. 1-4 + oficio).');
        }

        $solicitud->update([
            'estado'         => SolicitudAgente::ESTADO_CAPACITACION,
            'motivo_rechazo' => null,
        ]);
    }

    /**
     * Rechaza la solicitud con un motivo.
     */
    public function rechazar(SolicitudAgente $solicitud, string $motivo): void
    {
        $solicitud->update([
            'estado'         => SolicitudAgente::ESTADO_RECHAZADA,
            'motivo_rechazo' => $motivo,
        ]);
    }
}