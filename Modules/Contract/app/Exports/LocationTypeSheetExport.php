<?php

namespace Modules\Contract\app\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class LocationTypeSheetExport implements FromArray, WithTitle
{
    protected $title;
    protected $rows;
    protected $count;

    public function __construct(string $title, array $rows = [], int $count = 0)
    {
        $this->title = $title;
        $this->rows = $rows;
        $this->count = $count;
    }

    public function array(): array
    {
        // Header + data rows
        $data = [];
        $data[] = ['Contract Type', 'Count'];
        foreach ($this->rows as $row) {
            $data[] = [$row['type'], $row['count']];
        }
        return $data;
    }

    public function title(): string
    {
        // Excel sheet title max length is 31 chars. Include count but keep within limit.
        $base = ' (' . $this->count . ')' .$this->title;
        if (strlen($base) > 31) {
            return substr($base, 0, 31);
        }
        return $base;
    }
}
