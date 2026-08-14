<?php

namespace Modules\Contract\app\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ContractTypeSubstatusExport implements FromArray, WithTitle
{
    protected $rows;
    protected $substatuses;

    public function __construct(array $rows, array $substatuses)
    {
        $this->rows = $rows;
        $this->substatuses = $substatuses;
    }

    public function array(): array
    {
        $data = [];

        $header = array_merge(['Contract Type'], $this->substatuses, ['Total']);
        $data[] = $header;

        foreach ($this->rows as $typeName => $countsBySubstatus) {
            $row = [$typeName];
            $total = 0;

            foreach ($this->substatuses as $substatus) {
                $count = (int) ($countsBySubstatus[$substatus] ?? 0);
                $row[] = $count;
                $total += $count;
            }

            $row[] = $total;
            $data[] = $row;
        }

        return $data;
    }

    public function title(): string
    {
        return 'Type vs Substatus';
    }
}
