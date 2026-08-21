<?php

namespace Modules\Contract\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Helpers;
use App\Models\Contract;
use App\Models\BranchUser;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Contract\app\Exports\LocationStatusSummaryExport;
use Modules\Contract\Services\ContractVisibilityQuery;
use Illuminate\Support\Facades\Log;

class ContractDashboardController extends Controller
{

    public function __construct()
    {
          if(Controller::checkCurrentAuth("Contracts") != 1){
            return abort('404');
          }
    }


    public function index()
    {
        return view('contract::dashboard.viewDashboard');
    }


    

    // -----------------------------------------------------------------------
    // The dashboard.
    //
    // Spec: .scratch/contracts-dashboard-perf/spec.md sections 3, 4 and 10.
    // Names: .scratch/contracts-dashboard-perf/names.md.
    //
    // This replaced dashDetails() on 2026-08-21 (spec section 10 step 11) and now serves the
    // live URLs, GET '' and POST 'filterDash'. dashDetails() is deleted; report.md rows 2, 2a,
    // 2b, 2c and 3 hold the old numbers, and `dashboard:compare-counters` had already reported
    // all 15 stage counters and all 4 task counters identical.
    //
    // What changes, on purpose:
    //  - no contract row is ever loaded into PHP; the counters are one GROUP BY
    //  - no array of contract ids exists, so the MariaDB 1,000-parameter bug cannot happen
    //  - the filterByLocationReport cookie is not read (spec section 9)
    //  - "My Actionable Items" numbers change, because they are silently zero today
    // -----------------------------------------------------------------------

    public function dashboardSummary(Request $request)
    {
        $visibility = new ContractVisibilityQuery();

        // The two dropdown lists are NOT built here any more. The view asks the shared
        // option-lists endpoint for them (spec.md section 8), which takes both queries and
        // 136 <option> tags off this page's critical path. The branch query alone decrypted
        // eleven columns to print one.

        $counts = $this->foldStatusCounters($this->statusCountRows($request, $visibility));

        $stusMyTask = $this->myTaskCounts($request, $visibility);

        // Reads a plain-text approval_status, so `php artisan contract:convert-approval-status
        // --apply` must have run on whatever database this points at. Against a table still
        // holding ciphertext it counts nothing and every number reads zero.
        //
        // The old decrypt-everything counter and its ?oldApprovalStatus=1 escape hatch were
        // deleted 2026-08-21 on the dev's call, so there is no longer a way back in code. If a
        // deployment runs this before the conversion, run the conversion - see DEPLOYMENT.md
        // section 1, and note the narrow migration throws rather than letting the order slip
        // silently.
        //
        // One local-only escape hatch remains, the cheapest way to measure what this counter
        // costs:
        //   ?withoutActionableItems=1   the counter off, report.md step 3 against step 5
        if ($request->boolean('withoutActionableItems')) {
            $stusMy = $this->emptyActionableItemCounts();
        } else {
            $stusMy = $this->actionableItemCounts($request, $visibility);
        }

        return view('contract::dashboard.viewDashboardSummary', compact(
            'stusMy',
            'stusMyTask'
        ))
        ->with('sellocal', $request->contractlocs ?? [])
        ->with('selcontype', $request->contracttype ?? [])
        ->with('counts', $counts);
    }

    /**
     * One GROUP BY over the visible contracts. Returns about 20 rows - one per
     * contract_status / substatus pair - and never a contract row.
     *
     * Grouped on HEX() of the two columns, not on the columns themselves, so 'Terminated' and
     * 'terminated' stay apart. The table collation is case-insensitive and would silently
     * merge them before PHP ever saw them, while the PHP fold below is case-sensitive on
     * 'Terminated'. HEX also sidesteps the server's ONLY_FULL_GROUP_BY, which does not accept
     * a COLLATE expression in the select as matching the same expression in the GROUP BY.
     * The rows are turned back into text in foldStatusCounters().
     */
    private function statusCountRows(Request $request, ContractVisibilityQuery $visibility)
    {
        $query = $visibility->visibleContracts();

        if ($request->contractlocs) {
            $visibility->applyPartyLocationFilter($query, 'contracts', $request->contractlocs);
        }

        if ($request->contracttype) {
            $query->whereIn('contracts.contract_type', $request->contracttype);
        }

        return $query
            ->selectRaw(
                'HEX(contract_status) AS contract_status_hex,'
                . ' HEX(substatus) AS substatus_hex,'
                . ' COUNT(*) AS total'
            )
            ->groupBy(DB::raw('HEX(contract_status)'), DB::raw('HEX(substatus)'))
            ->get();
    }

    /**
     * Fold those ~20 rows into the 15 counters the view prints.
     *
     * The fold stays in PHP because contractStatusKey() and the case-sensitive 'Terminated'
     * check are correct today, and moving them into SQL CASE arms would change the answer.
     * Kept arm for arm identical to dashDetails() - including having no default arm, so an
     * unknown status is counted nowhere, exactly as today.
     */
    private function foldStatusCounters($rows): array
    {
        $counts = [
            'all' => 0,
            'draft' => 0,
            'review' => 0,
            'finalization' => 0,
            'negotiation' => 0,
            'approval' => 0,
            'approved' => 0,
            'signing' => 0,
            'executed' => 0,
            'executed_active' => 0,
            'executed_expired' => 0,
            'executed_pending' => 0,
            'executed_renewed' => 0,
            'executed_terminated' => 0,
            'executed_completed' => 0,
        ];

        foreach ($rows as $row) {
            $total = (int) $row->total;

            // Back from HEX() to the exact bytes stored in the row - see statusCountRows().
            $contractStatus = $row->contract_status_hex === null ? null : hex2bin($row->contract_status_hex);
            $substatus = $row->substatus_hex === null ? null : hex2bin($row->substatus_hex);

            switch (contractStatusKey($contractStatus)) {
                case 'executed':
                    $counts['executed'] += $total;
                    $counts['all'] += $total;
                    switch ($substatus) {
                        case 'active':
                            $counts['executed_active'] += $total;
                            break;
                        case 'expired':
                            $counts['executed_expired'] += $total;
                            break;
                        case 'pending':
                            $counts['executed_pending'] += $total;
                            break;
                        case 'renewed':
                            $counts['executed_renewed'] += $total;
                            break;
                        case 'Terminated':
                            $counts['executed_terminated'] += $total;
                            break;
                        case 'completed':
                            $counts['executed_completed'] += $total;
                            break;
                    }
                    break;
                case 'draft':
                    $counts['draft'] += $total;
                    $counts['all'] += $total;
                    break;
                // Also covers the internal 'Pre-Approval' status via contractStatusKey().
                case 'review':
                    $counts['review'] += $total;
                    $counts['all'] += $total;
                    break;
                case 'finalization':
                    $counts['finalization'] += $total;
                    $counts['all'] += $total;
                    break;
                case 'negotiation':
                    $counts['negotiation'] += $total;
                    $counts['all'] += $total;
                    break;
                case 'approval':
                    $counts['approval'] += $total;
                    $counts['all'] += $total;
                    break;
                case 'approved':
                    $counts['approved'] += $total;
                    $counts['all'] += $total;
                    break;
                case 'signing':
                    $counts['signing'] += $total;
                    $counts['all'] += $total;
                    break;
            }
        }

        return $counts;
    }

    /**
     * The four task numbers, as conditional counts instead of loading every task row.
     *
     * Same rule as today: 'all' counts every task on a visible contract whoever owns it,
     * while the three status numbers count only tasks owned by the logged-in user.
     */
    private function myTaskCounts(Request $request, ContractVisibilityQuery $visibility): array
    {
        $userId = Helpers::userInfo()->id;

        $query = DB::table('contract_tasks')
            ->join('contracts', 'contracts.id', '=', 'contract_tasks.contract_id');

        $visibility->applyTo($query, 'contracts');

        if ($request->contractlocs) {
            $visibility->applyPartyLocationFilter($query, 'contracts', $request->contractlocs);
        }

        $row = $query->selectRaw(
            'COUNT(*) AS all_total,'
            . ' SUM(CASE WHEN contract_tasks.task_owner = ? AND contract_tasks.status = ? THEN 1 ELSE 0 END) AS pending_total,'
            . ' SUM(CASE WHEN contract_tasks.task_owner = ? AND contract_tasks.status = ? THEN 1 ELSE 0 END) AS inprogress_total,'
            . ' SUM(CASE WHEN contract_tasks.task_owner = ? AND contract_tasks.status = ? THEN 1 ELSE 0 END) AS completed_total',
            [$userId, 'pending', $userId, 'inprogress', $userId, 'completed']
        )->first();

        return [
            'all' => (int) ($row->all_total ?? 0),
            'pending' => (int) ($row->pending_total ?? 0),
            'inprogress' => (int) ($row->inprogress_total ?? 0),
            'completed' => (int) ($row->completed_total ?? 0),
        ];
    }

    /**
     * The SQL half of "My Actionable Items" once approval_status is plain text: the pending
     * filter moves into the query, so PHP never sees a row that is not pending.
     *
     * approval_status is not selected - there is nothing left to test it against in PHP. Needs
     * `php artisan contract:convert-approval-status --apply` to have run; against a table that
     * still holds ciphertext this matches nothing and every count reads zero.
     *
     * The comparison is a plain one, so the table collation decides case. The deleted PHP
     * version compared === 'pending' and would not have matched 'Pending'. Every one of the 61
     * write sites passes a lowercase word and all 127 real rows decrypted to lowercase (checked
     * 2026-08-21), so the two agreed on this data - but on a client database holding a
     * capitalised value this one counts it and the old one did not. This one is right.
     *
     * See .scratch/contracts-dashboard-perf/issues/17-plain-columns-experiment.md
     */
    private function actionableApprovalRows(Request $request, ContractVisibilityQuery $visibility)
    {
        $query = DB::table('approval_contracts')
            ->join('contracts', 'contracts.id', '=', 'approval_contracts.contract_id');

        $visibility->applyTo($query, 'contracts');

        if ($request->contractlocs) {
            $visibility->applyPartyLocationFilter($query, 'contracts', $request->contractlocs);
        }

        return $query
            ->where('approval_contracts.row_status', 1)
            ->where('approval_contracts.superseded', 0)
            ->where('approval_contracts.approval_status', 'pending')
            ->select(
                'approval_contracts.id',
                'approval_contracts.unique_id',
                'approval_contracts.username'
            )
            ->orderBy('approval_contracts.id', 'desc');
    }

    /**
     * The leading row's contract_status for each of the given unique_ids - the highest id in
     * the group, over the same visibility-filtered set actionableApprovalRows() walks.
     *
     * This is here because actionableApprovalRows() only returns pending rows, and a group's
     * leader is often not one of them. 6 unique_ids in this database span more than one
     * contract, so the leader genuinely decides the answer and cannot be shortcut to "any row
     * of this group".
     *
     * Chunked at 500 ids: a whereIn carrying 1,000 or more bound parameters silently returns
     * zero rows on this MariaDB build (CONTEXT.md). The list is the unique_ids that survived
     * the email match, which is a handful, so this normally runs once.
     */
    private function leadingStatusByGroup(
        Request $request,
        ContractVisibilityQuery $visibility,
        array $uniqueIds
    ): array {
        $leading = [];

        foreach (array_chunk($uniqueIds, 500) as $slice) {
            $query = DB::table('approval_contracts')
                ->join('contracts', 'contracts.id', '=', 'approval_contracts.contract_id');

            $visibility->applyTo($query, 'contracts');

            if ($request->contractlocs) {
                $visibility->applyPartyLocationFilter($query, 'contracts', $request->contractlocs);
            }

            $rows = $query
                ->where('approval_contracts.row_status', 1)
                ->where('approval_contracts.superseded', 0)
                ->whereIn('approval_contracts.unique_id', $slice)
                ->select(
                    'approval_contracts.unique_id',
                    'contracts.contract_status'
                )
                ->orderBy('approval_contracts.id', 'desc')
                ->get();

            // Same rule as the old walk: id DESC, first row seen for a unique_id is its leader.
            foreach ($rows as $row) {
                if (!array_key_exists($row->unique_id, $leading)) {
                    $leading[$row->unique_id] = contractStatusKey($row->contract_status);
                }
            }
        }

        return $leading;
    }

    /**
     * "My Actionable Items" with approval_status plain: the same six numbers, without
     * decrypting a status column 13,861 times to throw 11,732 of them away.
     *
     * What is left to decrypt is username, which stays encrypted by the dev's call on
     * 2026-08-21 - it holds JSON {email,name} whose name is printed in 13 blade files, so
     * converting it is a much larger job for a few milliseconds. So this decrypts one value per
     * pending row instead of one status for every row plus one username per pending row:
     * 15,988 values became 2,129 on the seeded 3,018-contract set.
     *
     * This replaced the decrypt-everything version, which ran beside it while both were measured
     * on the same page and the same data (CLAUDE.md) and was deleted 2026-08-21 once proven.
     * report.md rows 3 and 12 hold the two sets of numbers.
     */
    private function actionableItemCounts(Request $request, ContractVisibilityQuery $visibility): array
    {
        $stusMy = $this->emptyActionableItemCounts();

        $mine      = [];
        $decrypted = 0;

        $this->actionableApprovalRows($request, $visibility)
            ->chunk(2000, function ($rows) use (&$mine, &$decrypted) {
                foreach ($rows as $row) {
                    $username = decryptString($row->username, 'username');
                    $decrypted++;

                    $email = json_decode((string) $username)->email ?? '';

                    if (!Helpers::accessInfo($email, false)) {
                        continue;
                    }

                    $mine[] = $row->unique_id;
                }
            });

        if ($mine === []) {
            Log::debug('dashboardSummary actionable items (plain approval_status)', [
                'pending_rows_seen' => $decrypted,
                'values_decrypted'  => $decrypted,
                'mine'              => 0,
                'actionable_total'  => 0,
            ]);

            return $stusMy;
        }

        $leading = $this->leadingStatusByGroup($request, $visibility, array_values(array_unique($mine)));

        // One increment per surviving row, not per group - the old walk counted a group once for
        // every pending row in it that belonged to the user, and that is preserved here.
        foreach ($mine as $uniqueId) {
            $key = $leading[$uniqueId] ?? null;

            if ($key === null) {
                continue;
            }

            if (array_key_exists($key, $stusMy)) {
                $stusMy[$key]++;
            }

            $stusMy['all']++;
        }

        Log::debug('dashboardSummary actionable items (plain approval_status)', [
            'pending_rows_seen' => $decrypted,
            'values_decrypted'  => $decrypted,
            'mine'              => count($mine),
            'groups'            => count($leading),
            'actionable_total'  => $stusMy['all'],
        ]);

        return $stusMy;
    }

    /**
     * The six numbers at zero. Same keys the old blade loop expected, so the view renders the
     * same whether the counter ran or was skipped for a measurement.
     */
    private function emptyActionableItemCounts(): array
    {
        return [
            'all' => 0,
            'draft' => 0,
            'review' => 0,
            'negotiation' => 0,
            'approval' => 0,
            'approved' => 0,
            'signing' => 0,
            'executed' => 0,
            'finalization' => 0,
        ];
    }

    
    // -----------------------------------------------------------------------
    // Location Status Report
    // Contracts grouped by Active / Expired / Going-to-Expire per internal
    // first-party location.
    // -----------------------------------------------------------------------

    /**
     * Resolve the effective end-date of a contract for expiry calculations.
     * Returns a Carbon instance or null when no relevant date is available.
     */
    private function resolveContractEndDate($contract): ?Carbon
    {
        $endType = strtolower(
            decryptString($contract->end_contract_type ?? '', 'end_contract_type') ?? ''
        );

        $raw = null;
        if (in_array($endType, ['fixedterm', 'fixed_term'])) {
            $raw = $contract->contract_end_date ?? null;
        } elseif ($endType === 'onetime') {
            $raw = $contract->onetime_end_date ?? null;
        } else {
            // 'fixed' or any other type: prefer fixed_date, then contract_end_date
            $raw = $contract->fixed_date
                ?? $contract->contract_end_date
                ?? $contract->onetime_end_date
                ?? null;
        }

        if (empty($raw) || $raw === '-') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Build the location-wise status summary data array.
     * Each element keyed by location_id:
     *   [ 'location_name', 'active', 'expired', 'expiring_soon', 'total' ]
     *
     * Active        – substatus = 'active' and end date > 90 days away (or no end date)
     * Expired       – substatus = 'expired'
     * Expiring Soon – substatus = 'active' and end date within next 90 days
     */
    private function buildLocationStatusData(Request $request): array
    {
        $query = Contract::select(
            'id', 'contract_name', 'end_contract_type',
            'contract_status', 'substatus',
            'fixed_date', 'contract_end_date', 'onetime_end_date'
        )
        ->orderBy('id', 'desc')
        ->where('status', 1)
        ->where('contract_status', 'Executed');

        $contracts = $query->get();
        $contracts = $this->availableContracts($contracts, true);

        $today = Carbon::today();
        $in90  = Carbon::today()->addDays(90);

        // Build location name map from BranchUser
        $branchNames = BranchUser::select('id', decrypt_data('BranchName', 'branch'))
            ->get()
            ->keyBy('id');

        // Optional location filter from request
        $filterLocs = $request->contractlocs
            ? array_map('intval', (array) $request->contractlocs)
            : [];

        $locationData = [];

        foreach ($contracts as $contract) {
            $partyList = $contract->contractParty ?? [];

            // Use only the *first* internal first-party location to avoid
            // double-counting when a contract has multiple internal parties.
            $locationId = null;
            foreach ($partyList as $party) {
                if (
                    $party->contract_party_type === 'Internal'
                    && !empty($party->contract_party_location_id)
                ) {
                    $locationId = (int) $party->contract_party_location_id;
                    break;
                }
            }

            if ($locationId === null) {
                continue; // no internal first-party location – skip
            }

            if (!empty($filterLocs) && !in_array($locationId, $filterLocs)) {
                continue; // filtered out by request
            }

            // Initialise bucket
            if (!isset($locationData[$locationId])) {
                $branchRecord = $branchNames->get($locationId);
                $locationData[$locationId] = [
                    'location_name' => $branchRecord
                        ? ($branchRecord->BranchName ?? 'Location #' . $locationId)
                        : 'Unknown',
                    'active'        => 0,
                    'expired'       => 0,
                    'expiring_soon' => 0,
                    'total'         => 0,
                ];
            }

            $substatus = strtolower($contract->substatus ?? '');
            $locationData[$locationId]['total']++;

            if ($substatus === 'expired') {
                $locationData[$locationId]['expired']++;
            } elseif ($substatus === 'active') {
                $endDate = $this->resolveContractEndDate($contract);
                if (
                    $endDate
                    && $endDate->greaterThanOrEqualTo($today)
                    && $endDate->lessThanOrEqualTo($in90)
                ) {
                    $locationData[$locationId]['expiring_soon']++;
                } else {
                    $locationData[$locationId]['active']++;
                }
            }
            // Other substatuses (pending, renewed, Terminated, completed) go
            // to 'total' only and are not surfaced in the three primary buckets.
        }

        // Sort alphabetically by location name
        uasort(
            $locationData,
            fn($a, $b) => strcmp($a['location_name'], $b['location_name'])
        );

        return $locationData;
    }

    /**
     * GET /contracts/dashboard/location-status
     * Render the location-wise contracts-status dashboard view.
     */
    public function locationStatusReport(Request $request)
    {
        $locationData = $this->buildLocationStatusData($request);

        $branchs = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->get();

        $totals = [
            'active'        => array_sum(array_column($locationData, 'active')),
            'expired'       => array_sum(array_column($locationData, 'expired')),
            'expiring_soon' => array_sum(array_column($locationData, 'expiring_soon')),
            'total'         => array_sum(array_column($locationData, 'total')),
        ];

        return view('contract::dashboard.locationStatusReport', compact(
            'locationData', 'branchs', 'totals'
        ))->with('sellocal', $request->contractlocs ?? []);
    }

    /**
     * POST /contracts/dashboard/location-status/export
     * Download an Excel (.xlsx) file of the location-wise status summary.
     */
    public function exportLocationStatusReport(Request $request)
    {
        $locationData = $this->buildLocationStatusData($request);
        $rows         = array_values($locationData);
        $filename     = 'location-status-summary-' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new LocationStatusSummaryExport($rows), $filename);
    }

    public function logoutContract(){
        session()->invalidate();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        	session_unset();
    		session_destroy();
        }        
        return redirect()->away(env('authMainUrl'));
    }
}