<?php

/**
 * app/Exports/RecaudacionExport.php
 *
 * Exportación Excel del reporte de recaudación SIMETSA. (Fase 8.B)
 * Usa Maatwebsite/Laravel-Excel (FromCollection + WithHeadings + WithMapping).
 */

namespace App\Exports;

use App\Models\Infraccion;
use App\Models\Ticket;
use App\Models\TransaccionPago;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta el listado de transacciones completadas a formato Excel (.xlsx).
 */
class RecaudacionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithStyles
{
    public function __construct(private readonly Collection $filas) {}

    public function collection(): Collection
    {
        return $this->filas;
    }

    public function title(): string
    {
        return 'Recaudación';
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Hora',
            'Tipo',
            'Referencia',
            'Placa',
            'Zona',
            'Proveedor',
            'Monto (USD)',
        ];
    }

    /**
     * Mapea cada TransaccionPago a una fila del Excel.
     *
     * @param  TransaccionPago $row
     */
    public function map($row): array
    {
        $concepto  = $row->concepto;
        $tipo      = match ($row->concepto_type) {
            Ticket::class     => 'Ticket',
            Infraccion::class => 'Infracción',
            default           => 'Otro',
        };
        $referencia = match ($row->concepto_type) {
            Ticket::class     => $concepto?->codigo ?? '—',
            Infraccion::class => 'INF-' . str_pad((string) ($concepto?->id ?? 0), 6, '0', STR_PAD_LEFT),
            default           => '—',
        };
        $placa  = match ($row->concepto_type) {
            Ticket::class     => $concepto?->vehiculo?->placa ?? '—',
            Infraccion::class => $concepto?->placa ?? '—',
            default           => '—',
        };
        $zona = match ($row->concepto_type) {
            Ticket::class     => $concepto?->zona?->nombre ?? '—',
            Infraccion::class => $concepto?->zona?->nombre ?? '—',
            default           => '—',
        };

        return [
            $row->created_at->format('d/m/Y'),
            $row->created_at->format('H:i'),
            $tipo,
            $referencia,
            $placa,
            $zona,
            $row->proveedor->etiqueta(),
            number_format((float) $row->monto, 2),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
