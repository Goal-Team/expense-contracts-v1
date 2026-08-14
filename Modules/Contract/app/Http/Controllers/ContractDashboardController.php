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