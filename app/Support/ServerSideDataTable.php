<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Answers a DataTables `serverSide: true` request from one base query.
 *
 * The caller hands over a query builder that already carries every filter except the
 * search box, plus three hooks. This class reads the DataTables protocol fields
 * (draw / start / length / search[value] / order[0]) from the request, runs at most
 * three queries (total count, filtered count, one page of rows) and returns the
 * response shape DataTables expects. No endpoint has to copy the paging code again.
 *
 * Hooks:
 * - searchWith(fn ($query, string $term)): adds the search conditions to a query.
 *   Called once per request, only when the search box holds text.
 * - transformPageWith(fn ($rows): array): turns the page of database rows into the
 *   row arrays the table renders. This is where a page slims the JSON down to the
 *   fields its columns read.
 * - orderColumns([columnIndex => sql column]): which table columns may sort in SQL.
 *   An index not in the map falls back to the default order, so a client cannot
 *   name an arbitrary column.
 */
class ServerSideDataTable
{
    /** @var \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder */
    private $query;

    /** @var array<int, string> DataTables column index => SQL column */
    private $orderColumns = [];

    /** @var string */
    private $defaultOrderColumn = 'id';

    /** @var string */
    private $defaultOrderDirection = 'desc';

    /** @var callable|null fn ($query, string $term): void */
    private $applySearch = null;

    /** @var callable|null fn ($rows): array */
    private $transformPage = null;

    /** @var int|null caller-supplied total, skips the COUNT query */
    private $totalRecords = null;

    /** @var array extra top-level keys for the JSON body (e.g. counts) */
    private $extras = [];

    /**
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     *        every filter applied, no search, no order, no limit
     */
    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * @param array<int, string> $map DataTables column index => SQL column
     */
    public function orderColumns(array $map): self
    {
        $this->orderColumns = $map;

        return $this;
    }

    public function defaultOrder(string $column, string $direction = 'desc'): self
    {
        $this->defaultOrderColumn = $column;
        $this->defaultOrderDirection = $direction === 'asc' ? 'asc' : 'desc';

        return $this;
    }

    public function searchWith(callable $applySearch): self
    {
        $this->applySearch = $applySearch;

        return $this;
    }

    public function transformPageWith(callable $transformPage): self
    {
        $this->transformPage = $transformPage;

        return $this;
    }

    /**
     * Hand in recordsTotal when the caller already knows it (for example from a
     * counters query), so the COUNT query is skipped.
     */
    public function totalRecords(int $count): self
    {
        $this->totalRecords = $count;

        return $this;
    }

    public function withExtras(array $extras): self
    {
        $this->extras = $extras;

        return $this;
    }

    public function respond(Request $request): JsonResponse
    {
        $draw = (int) ($request->input('draw') ?? 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);

        $total = $this->totalRecords ?? (clone $this->query)->count();

        $page = clone $this->query;

        $term = trim((string) $request->input('search.value', ''));
        if ($term !== '' && $this->applySearch !== null) {
            ($this->applySearch)($page, $term);
            $filtered = (clone $page)->count();
        } else {
            $filtered = $total;
        }

        $orderIndex = $request->input('order.0.column');
        $direction = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $orderColumn = null;
        if ($orderIndex !== null && isset($this->orderColumns[(int) $orderIndex])) {
            $orderColumn = $this->orderColumns[(int) $orderIndex];
            $page->orderBy($orderColumn, $direction);
        }
        if ($orderColumn !== $this->defaultOrderColumn) {
            // Always end on the default order, so equal values keep a stable page split.
            $page->orderBy($this->defaultOrderColumn, $this->defaultOrderDirection);
        }

        // length -1 is the DataTables "All" entry: no limit.
        if ($length !== -1) {
            $page->offset($start)->limit(max(1, $length));
        }

        $rows = $page->get();
        $data = $this->transformPage !== null ? ($this->transformPage)($rows) : $rows->all();

        return response()->json(array_merge([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ], $this->extras));
    }
}
