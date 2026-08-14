<?php

namespace Modules\Contract\app\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LocationStatusSummaryExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $rows;
    protected string $reportedAt;

    /**
     * @param array $rows  Each element: [
     *   'location_name' => string,
     *   'active'        => int,
     *   'expired'       => int,
     *   'expiring_soon' => int,
     *   'total'         => int,
     * ]
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->reportedAt = now()->format('d M Y, H:i');
    }

    public function headings(): array
    {
        return [
            'Location (Internal First-Party)',
            'Active',
            'Expired',
            'Going to Expire (Next 90 Days)',
            'Total Executed',
        ];
    }

    public function array(): array
    {
        $data = [];
        foreach ($this->rows as $row) {
            $data[] = [
                $row['location_name'],
                $row['active'],
                $row['expired'],
                $row['expiring_soon'],
                $row['total'],
            ];
        }

        // Totals row
        $data[] = [
            'TOTAL',
            array_sum(array_column($this->rows, 'active')),
            array_sum(array_column($this->rows, 'expired')),
            array_sum(array_column($this->rows, 'expiring_soon')),
            array_sum(array_column($this->rows, 'total')),
        ];

        // Blank row + report metadata
        $data[] = [];
        $data[] = ['Report generated on: ' . $this->reportedAt];

        return $data;
    }

    public function title(): string
    {
        return 'Location Status Summary';
    }

    public function styles(Worksheet $sheet): array
    {
        // Bold the header row and the totals row
        $lastDataRow = count($this->rows) + 2; // 1-indexed header + data rows + totals

        return [
            1              => ['font' => ['bold' => true]],
            $lastDataRow   => ['font' => ['bold' => true]],
        ];
    }
}
