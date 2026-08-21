<?php

namespace Modules\Contract\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Helpers;
use App\Models\Contract;
use App\Models\ApprovalContracts;
use App\Models\Tasks;
use App\Models\BranchUser;
use App\Models\ContractType;
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


    public function dashDetails(Request $request)
    {

        $branchs_query = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        );
        
        $branchs = $branchs_query->get();
        
        $contractTypes = ContractType::get();
        
        $contracts_query = Contract::select('contract_name', 'id', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date','onetime_end_date','contract_type')
        ->orderBy('id', 'desc')
        ->where('status', 1);
        
        if($request->contracttype){
            $contracts_query->whereIn('contract_type', $request->contracttype);
        }
        
        $contracts = $contracts_query->get();

        $contracts = $this->availableContracts($contracts, true);
        
        $contract_all_total                = 0;
        $contract_draft_total              = 0;
        $contract_review_total             = 0;
        $contract_finalization_total       = 0;
        $contract_negotiation_total        = 0;
        $contract_approval_total           = 0;
        $contract_approved_total           = 0;
        $contract_signing_total            = 0;
        $contract_executable_total         = 0;
        $contract_executable_active_total  = 0;
        $contract_executable_expired_total = 0;
        $contract_executable_pending_total = 0;
        $contract_executable_renewed_total = 0;
        $contract_executable_termina_total = 0;
        $contract_executable_comp_total    = 0;
        
        $contractIds = [];
        $contractStatus = [];
        foreach ($contracts as $contract) {
            
            $applicable = true;
            if($request->contractlocs){
            
              $applicable = false;
              $contractParty = $contract->contractParty;
              foreach ($contractParty as $contractPart) {
        
                //Check Branches Accessible for the User
                if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Internal' && in_array($contractPart->contract_party_location_id,$request->contractlocs)) {                        
                    $applicable = true;            
                }
              }
            }            
            
            if($applicable){
                $contractIds[] = $contract->id;
                
                // User-facing status key ('Pre-Approval' -> 'review').
                $contractStatus[$contract->id] = contractStatusKey($contract->contract_status);

                switch (contractStatusKey($contract->contract_status)) {
                    case 'executed':
                        $contract_executable_total++;
                        $contract_all_total++;
                        switch ($contract->substatus) {
                            case 'active':
                                $contract_executable_active_total++;
                                break;
                            case 'expired':
                                $contract_executable_expired_total++;
                                break;
                            case 'pending':
                                $contract_executable_pending_total++;
                                break;
                            case 'renewed':
                                $contract_executable_renewed_total++;
                                break;
                            case 'Terminated':
                                $contract_executable_termina_total++;
                                break;
                            case 'completed':
                                $contract_executable_comp_total++;
                                break;
                        }
                        break;
                        case 'draft':
                            $contract_draft_total++;
                            $contract_all_total++;
                            break;        
                        // Also covers the internal 'Pre-Approval' status via contractStatusKey().
                        case 'review':
                            $contract_review_total++;
                            $contract_all_total++;
                            break;
                        // Pre-approval flow stage (grouped flow): previously uncounted, so
                        // these contracts were invisible in the dashboard totals.
                        case 'finalization':
                            $contract_finalization_total++;
                            $contract_all_total++;
                            break;
                        // Was 'Negotiation' (never matched, since the switch value is
                        // strtolower'd) and fell through into 'approval', double-counting.
                        case 'negotiation':
                            $contract_negotiation_total++;
                            $contract_all_total++;
                            break;
                        case 'approval':
                            $contract_approval_total++;
                            $contract_all_total++;
                            break;
                        case 'approved':
                            $contract_approved_total++;
                            $contract_all_total++;
                            break;        
                        case 'signing':
                            $contract_signing_total++;
                            $contract_all_total++;
                            break;
                }
            }
        }
        
        $approvalsArr = ApprovalContracts::select('*')
        ->whereIn('contract_id', $contractIds)
        ->orderBy('id', 'DESC')
        ->get()            
        ->map(function ($task) {
            $task->username = decryptString($task->username, 'username');
            $task->status = decryptString($task->status, 'status');
            $task->previous_status = decryptString($task->previous_status, 'previous_status');
            $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
            $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
            $task->approval_status = decryptString($task->approval_status, 'approval_status');
            return $task;
        })
        ->groupBy('unique_id')
        ->reverse();
        
        $tasks = Tasks::select('id', 'task_owner', 'status')
        ->whereIn('contract_id', $contractIds)
        ->orderBy('id', 'desc')
        ->get()->toArray();
        
        $stusMyTask = array(
            'all' => 0,
            'pending' => 0,
            'inprogress' => 0,
            'completed' => 0
        );
        
        $userId = Helpers::userInfo()->id;
        
        foreach($tasks as $tk){
            $stusMyTask['all']++;
            if($tk['task_owner'] == $userId){
                //echo $tk['status'];
                $stusMyTask[$tk['status']]++;
            }
        }

        $stus = array(
            'all' => $contract_all_total,
            'draft' => $contract_draft_total,
            'review' => $contract_review_total,
            'finalization' => $contract_finalization_total,
            'negotiation' => $contract_negotiation_total,
            'approval' => $contract_approval_total,
            'approved' => $contract_approved_total,
            'signing' => $contract_signing_total,
            'executed' => $contract_executable_total,
            'executed_active' => $contract_executable_active_total,
            'executed_expired' => $contract_executable_expired_total,
            'executed_pending' => $contract_executable_pending_total,
            'executed_renewed' => $contract_executable_renewed_total,
            'executed_terminated' => $contract_executable_termina_total,
            'executed_completed' => $contract_executable_comp_total
        );  
        
        $stusMy = array(
            'all' => 0,
            'draft' => 0,
            'review' => 0,
            'negotiation' => 0,
            'approval' => 0,
            'approved' => 0,
            'signing' => 0,
            'executed' => 0,
            'finalization' => 0
        );

        return view('contract::dashboard.viewDashboard1', compact('approvalsArr', 'contractStatus', 'stusMy', 'stusMyTask', 'branchs', 'contractTypes'))
        ->with('contracts', (count($contracts) == 0) ? [] : $contracts)
        ->with('sellocal', $request->contractlocs ?? [])
        ->with('selcontype', $request->contracttype ?? [])
        ->with('counts', $stus);
    }
    

    // -----------------------------------------------------------------------
    // Dashboard summary - the new path, beside dashDetails()
    //
    // Spec: .scratch/contracts-dashboard-perf/spec.md sections 3, 4 and 10.
    // Names: .scratch/contracts-dashboard-perf/names.md.
    //
    // dashDetails() above is untouched and still serves the live URL. This method is
    // reachable only at its own routes (contractDashboardSummary /
    // contractDashboardSummary.filter), so the old page cannot start serving new
    // behaviour by accident. dashDetails() is deleted only once report.md shows old and
    // new side by side and `php artisan dashboard:compare-counters` reports no
    // unexpected difference.
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

        // Local-only escape hatch for step 3 vs step 5 of the spec's order of work: the same
        // page measured with the counter off and on, in one session. It only exists on this
        // route, and the live URL never reaches this method at all.
        $stusMy = $request->boolean('withoutActionableItems')
            ? $this->emptyActionableItemCounts()
            : $this->actionableItemCounts($request, $visibility);

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
     * The SQL half of "My Actionable Items": the narrowest row set that can still produce the
     * six numbers. Five columns plus unique_id, read in chunks, nothing decrypted here.
     *
     * unique_id is carried because the count is attributed to the status of the group's
     * leading row - the highest id sharing that unique_id - exactly as the old blade loop did
     * with $appr[0].
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
            ->select(
                'approval_contracts.id',
                'approval_contracts.contract_id',
                'approval_contracts.unique_id',
                'approval_contracts.username',
                'approval_contracts.approval_status',
                'contracts.contract_status'
            )
            ->orderBy('approval_contracts.id', 'desc');
    }

    /**
     * The PHP half: decrypt username and approval_status per surviving row and fold the six
     * numbers. This is the ~0.5 s locally / ~2 s expected at 60,000 rows that spec.md
     * section 4 accepts knowingly; ticket 17 is what removes it.
     *
     * It cannot be done in SQL: both columns are AES-128-CBC with a random IV, so the same
     * value encrypts differently every time and is not matchable, filterable or indexable.
     */
    private function actionableItemCounts(Request $request, ContractVisibilityQuery $visibility): array
    {
        $stusMy = $this->emptyActionableItemCounts();

        $groupStatusKey = [];
        $decrypted = 0;

        $this->actionableApprovalRows($request, $visibility)
            ->chunk(2000, function ($rows) use (&$stusMy, &$groupStatusKey, &$decrypted) {
                foreach ($rows as $row) {
                    // Walking id DESC means the first row seen for a unique_id is its leader,
                    // so its status is the one the whole group counts against.
                    if (!array_key_exists($row->unique_id, $groupStatusKey)) {
                        $groupStatusKey[$row->unique_id] = contractStatusKey($row->contract_status);
                    }

                    $approvalStatus = decryptString($row->approval_status, 'approval_status');
                    $decrypted++;

                    if ($approvalStatus !== 'pending') {
                        continue;
                    }

                    $username = decryptString($row->username, 'username');
                    $decrypted++;

                    $email = json_decode((string) $username)->email ?? '';

                    if (!Helpers::accessInfo($email, false)) {
                        continue;
                    }

                    $key = $groupStatusKey[$row->unique_id];

                    if (array_key_exists($key, $stusMy)) {
                        $stusMy[$key]++;
                    }

                    $stusMy['all']++;
                }
            });

        Log::debug('dashboardSummary actionable items', [
            'groups' => count($groupStatusKey),
            'values_decrypted' => $decrypted,
            'actionable_total' => $stusMy['all'],
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