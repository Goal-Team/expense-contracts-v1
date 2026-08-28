<?php

namespace Modules\Contract\Services;

use App\Helpers\Helpers;
use App\Models\ApprovalContracts;
use App\Models\BranchUser;
use App\Models\Contract;
use App\Models\ContractCategories;
use App\Models\ContractType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The list page's filters, in SQL - the one place that turns the URL filter
 * values (contype, concates, locations, my, party_id, status, search) into a
 * contracts query.
 *
 * Two callers: ContractController::listContractData (the list AJAX) and
 * ContractExportController::bulkDownload (the bulk export, ticket 10). Both
 * build the query here, so the list and the export cannot drift apart.
 *
 * The caller picks its own select(): the list keeps its slim 12-column
 * select, the export selects every column its sheet writer reads.
 */
class ContractListFilters
{
    /** @var ContractVisibilityQuery */
    private $visibility;

    public function __construct(?ContractVisibilityQuery $visibility = null)
    {
        $this->visibility = $visibility ?? new ContractVisibilityQuery();
    }

    /**
     * Visibility plus every list filter except the status tab and the search
     * box. The list computes its tab counters on this query before the status
     * narrows it, so applyStatus() and applySearch() are separate steps.
     *
     * $filters keys, all optional:
     *   contype, concates, locations - comma-separated id strings from the URL
     *   party_id                     - one external-party id
     *   my                           - truthy = only contracts waiting on this user
     */
    public function filtered(array $filters = []): Builder
    {
        // Who may see which contracts, in SQL. ContractVisibilityQuery is the query form of
        // the branch, department and role checks availableContracts() ran row by row in PHP -
        // the dashboard effort proved the two agree. With the drop rule in the query, LIMIT
        // and COUNT are safe.
        $query = $this->visibility->visibleContracts();

        // The filter fields arrive as comma-separated ints (contype=1,2 - dev
        // call 2026-08-28, no JSON). parseIdList drops junk tokens, so a
        // malformed value filters down to the valid ints and never throws.
        $contypes = self::parseIdList($filters['contype'] ?? null);
        if (count($contypes) > 0) {
            $query->whereIn('contracts.contract_type', $contypes);
        }

        $concates = self::parseIdList($filters['concates'] ?? null);
        if (count($concates) > 0) {
            $query->whereIn('contracts.catgoery_id', $concates);
        }

        $locations = self::parseIdList($filters['locations'] ?? null);
        if (count($locations) > 0) {
            $this->visibility->applyPartyLocationFilter($query, 'contracts', $locations);
        }

        $partyId = (int) ($filters['party_id'] ?? 0);
        if ($partyId > 0) {
            $query->whereExists(function ($sub) use ($partyId) {
                $sub->select(DB::raw(1))
                    ->from('contract_party_data')
                    ->whereColumn('contract_party_data.custom_field_group_id', 'contracts.id')
                    ->where('contract_party_data.contract_party_exe_id', $partyId);
            });
        }

        // "My contracts". '0' and absent mean off.
        if (!empty($filters['my'])) {
            // This id list cannot become a subquery: it is the result of accessInfo() over
            // decrypted usernames, a check only PHP can run. whereIntegerInRaw inlines the
            // integers into the SQL text - zero bound values - so the 1,000-binding bug
            // (.scratch/wherein-1000-bug/spec.md) does not apply, same as the framework's
            // own eager-load queries.
            $query->whereIntegerInRaw('contracts.id', $this->myContractIds());
        }

        return $query;
    }

    /**
     * The status-tab filter, in SQL. Mirrors the PHP compare the old row loop ran:
     * contractStatusKey() lowercases and the table collation compares case-insensitively,
     * so plain where() calls match the same rows.
     */
    public function applyStatus($query, string $status): void
    {
        if ($status === 'all') {
            return;
        }

        if (str_contains($status, 'executed_')) {
            $query->where('contracts.contract_status', 'executed')
                ->where('contracts.substatus', explode('_', $status)[1]);

            return;
        }

        if (str_contains($status, 'draft_initial')) {
            $query->where('contracts.contract_status', 'Draft')
                ->where('contracts.substatus', 'Initial Draft');

            return;
        }

        if (str_contains($status, 'draft_under_revision')) {
            $query->where('contracts.contract_status', 'Draft')
                ->where('contracts.substatus', 'Under Revision');

            return;
        }

        if ($status === 'review') {
            // contractStatusKey() groups the internal 'Pre-Approval' status under 'review',
            // so the Review tab also returns pre-approval contracts.
            $query->whereIn('contracts.contract_status', ['Review', 'Pre-Approval', 'PreApproval', 'Pre Approval']);

            return;
        }

        $query->where('contracts.contract_status', $status);
    }

    /**
     * The search box, over the same columns the table renders. Plain columns match in SQL.
     * contract_name is encrypted by PHP (random IV per value), so SQL cannot see it: the one
     * column is decrypted over the current tab's id list and the matching ids join the WHERE.
     * Not searched any more (they were only reachable through the old client-side search):
     * the formatted currency value - matching it would decrypt every contract's value on
     * every keystroke - and the S.No column, which is a row number.
     */
    public function applySearch($query, string $term): void
    {
        $nameIds = [];
        foreach ((clone $query)->select('contracts.id', 'contracts.contract_name')->get() as $row) {
            if (stripos((string) decryptString($row->contract_name, 'contract_name'), $term) !== false) {
                $nameIds[] = (int) $row->id;
            }
        }

        // Branch names decrypt in SQL (legacy key), but through BranchUser so BranchScope
        // still limits the rows. A short list of branch ids, bounded by the branch table.
        $branchIds = BranchUser::where(decrypt_datas('BranchName', 'branch'), 'like', '%' . $term . '%')
            ->pluck('id')
            ->all();

        $like = '%' . $term . '%';

        $query->where(function ($w) use ($like, $nameIds, $branchIds) {
            // Inline integers, zero bound values - the 1,000-binding bug does not apply.
            $w->whereIntegerInRaw('contracts.id', $nameIds)
                ->orWhere('contracts.substatus', 'like', $like)
                ->orWhere('contracts.contract_status', 'like', $like)
                ->orWhere('contracts.contract_priority', 'like', $like)
                ->orWhere('contracts.contract_attachment_filename', 'like', $like)
                // The table renders dates as d-m-Y; DATE_FORMAT is the only way to match
                // what the user sees, and Eloquent has no date-format operator.
                ->orWhereRaw("DATE_FORMAT(contracts.fixed_date, '%d-%m-%Y') LIKE ?", [$like])
                ->orWhereRaw("DATE_FORMAT(contracts.contract_end_date, '%d-%m-%Y') LIKE ?", [$like])
                ->orWhereIn('contracts.contract_type', ContractType::select('contract_type_id')->where('contract_type', 'like', $like))
                ->orWhereIn('contracts.catgoery_id', ContractCategories::select('id')->where('name', 'like', $like));

            if (!empty($branchIds)) {
                $w->orWhereExists(function ($sub) use ($branchIds) {
                    $sub->select(DB::raw(1))
                        ->from('contract_party_data')
                        ->whereColumn('contract_party_data.custom_field_group_id', 'contracts.id')
                        ->where('contract_party_data.contract_party_type', 'Internal')
                        ->whereIn('contract_party_data.contract_party_location_id', array_map('strval', $branchIds));
                });
            }
        });
    }

    /**
     * Parse a comma-separated id list ("1,2") from a URL parameter or POST
     * field into an array of positive ints. Junk tokens (contype=abc,
     * contype=1,,x) are dropped, never thrown on. Absent, empty and '0' all
     * mean no filter and return []. Dev call 2026-08-28: no JSON in the URL.
     */
    public static function parseIdList($raw): array
    {
        if (!is_string($raw) && !is_numeric($raw)) {
            return [];
        }
        $ids = [];
        foreach (explode(',', (string) $raw) as $token) {
            $token = trim($token);
            if ($token !== '' && ctype_digit($token) && (int) $token > 0) {
                $ids[] = (int) $token;
            }
        }
        return $ids;
    }

    /**
     * Ids of the live contracts waiting on this user's approval.
     *
     * Both whereIn calls take a query, never a list of ids. A list with 1,000 or more
     * bound ids silently returns zero rows on this stack
     * (.scratch/wherein-1000-bug/spec.md) - the pre-ticket-05 code bound ~2,508 ids here
     * and "My contracts" came back empty.
     *
     * approval_status is plain text now, so the unique_id subquery keeps only the
     * groups that hold a pending row. Every row of those groups is fetched so the
     * group's leading row (highest id) still names the contract, as the old walk did.
     *
     * @return array<int>
     */
    private function myContractIds(): array
    {
        $approvalsArr = ApprovalContracts::select('id', 'unique_id', 'contract_id', 'username', 'approval_status')
            ->whereIn('unique_id', ApprovalContracts::select('unique_id')->where('approval_status', 'pending'))
            ->whereIn('contract_id', Contract::withoutGlobalScope('accessLevelSelect')->select('id')->where('status', 1))
            ->orderBy('id', 'DESC')
            ->get()
            ->groupBy('unique_id');

        $contractIds = [];
        foreach ($approvalsArr as $appr) {
            foreach ($appr as $appr_) {
                if ($appr_->approval_status != 'pending') {
                    continue;
                }
                // username stays encrypted (dev call 2026-08-21). Decrypt it only for
                // pending rows; nothing reads the other four encrypted columns here.
                $email = json_decode(decryptString($appr_->username, 'username'))->email ?? '';
                if (Helpers::accessInfo($email, false)) {
                    $contractIds[] = $appr[0]->contract_id;
                }
            }
        }

        return array_values(array_unique(array_map('intval', $contractIds)));
    }
}
