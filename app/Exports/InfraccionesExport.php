<?php

/**
 * app/Exports/InfraccionesExport.php
 *
 * Exportación Excel del reporte de infracciones SIMETSA. (Fase 8.C)
 */

namespace App\Exports;

use App\Models\Infraccion;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta el listado de infracciones a formato Excel (.xlsx).
 */
class InfraccionesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithStyles
{
    public function __construct(private readonly Collection $filas) {}

    public function collection(): Collection
    {
        return $this->filas;
    }

    public function title(): string
    {
        return 'Infracciones';
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Hora',
            'Tipo',
            'Placa',
            'Zona',
            'Agente',
            'Estado',
            'Inmovilizado',
            'Monto multa (USD)',
        ];
    }

    /**
     * Mapea cada Infraccion a una fila del Excel.
     *
     * @param  Infraccion $row
     */
    public function map($row): array
    {
        return [
            $row->created_at->format('d/m/Y'),
            $row->created_at->format('H:i'),
            $row->tipo_infraccion->etiqueta(),
            $row->placa,
            $row->zona?->nombre ?? '—',
            $row->agente?->nombre_completo ?? '—',
            $row->estado->etiqueta(),
            $row->inmovilizacion ? 'Sí' : 'No',
            number_format((float) $row->monto_multa, 2),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
