<?php

namespace Modules\Contract\app\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LocationTypeCountsExport implements WithMultipleSheets
{
    protected $sheets = [];

    public function __construct(array $sheets)
    {
        // $sheets: array of ['title' => string, 'rows' => [['type'=>..., 'count'=>...], ...], 'count' => int]
        $this->sheets = $sheets;
    }

    public function sheets(): array
    {
        $exports = [];
        foreach ($this->sheets as $s) {
            $exports[] = new LocationTypeSheetExport($s['title'], $s['rows'] ?? [], $s['count'] ?? 0);
        }
        return $exports;
    }
}
