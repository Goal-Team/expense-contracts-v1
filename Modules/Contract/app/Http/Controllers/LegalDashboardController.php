<?php

namespace Modules\Contract\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Helpers;
use App\Models\Contract;
use App\Models\ContractType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LegalDashboardController extends Controller
{

    public function __construct()
    {
        if(Controller::checkCurrentAuth("Contracts") != 1){
            return abort('404');
        }
    }

    /**
     * Display legal advisor dashboard with contracts assigned to them
     */
    public function index()
    {
        $currentUserEmail = session()->get('contractSessionUser');
        
        // Get all contracts assigned to current legal advisor
        $contracts = Contract::select(
            'id',
            'contract_name',
            'currency',
            'currency_value',
            'end_contract_type',
            'contract_status',
            'substatus',
            'fixed_date',
            'onetime_end_date',
            'contract_type',
            'legal_advisor_email',
            'created_at',
            'updated_at'
        )
        ->where('legal_advisor_email', $currentUserEmail)
        ->where('status', 1)
        ->orderBy('id', 'desc')
        ->get();

        // Calculate status counts
        $statusCounts = $this->calculateStatusCounts($contracts);
        
        // Get contract types for filter
        $contractTypes = ContractType::get();

        return view('contract::dashboard.legalDashboard', compact(
            'contracts',
            'statusCounts',
            'contractTypes',
            'currentUserEmail'
        ));
    }

    /**
     * Calculate counts per contract status
     */
    private function calculateStatusCounts($contracts)
    {
        $counts = [
            'all' => 0,
            'draft' => 0,
            'review' => 0,
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
            'executed_completed' => 0
        ];

        foreach ($contracts as $contract) {
            $counts['all']++;
            
            $status = strtolower($contract->contract_status);
            
            switch ($status) {
                case 'executed':
                    $counts['executed']++;
                    $substatus = strtolower($contract->substatus ?? '');
                    if ($substatus === 'active') {
                        $counts['executed_active']++;
                    } elseif ($substatus === 'expired') {
                        $counts['executed_expired']++;
                    } elseif ($substatus === 'pending') {
                        $counts['executed_pending']++;
                    } elseif ($substatus === 'renewed') {
                        $counts['executed_renewed']++;
                    } elseif ($substatus === 'terminated') {
                        $counts['executed_terminated']++;
                    } elseif ($substatus === 'completed') {
                        $counts['executed_completed']++;
                    }
                    break;
                case 'draft':
                    $counts['draft']++;
                    break;
                case 'review':
                    $counts['review']++;
                    break;
                case 'negotiation':
                    $counts['negotiation']++;
                    break;
                case 'approval':
                    $counts['approval']++;
                    break;
                case 'approved':
                    $counts['approved']++;
                    break;
                case 'signing':
                    $counts['signing']++;
                    break;
            }
        }

        return $counts;
    }

    /**
     * Get filtered contracts based on request parameters
     */
    public function getFilteredContracts(Request $request)
    {
        //$currentUserEmail = auth()->user()->email;
        
        $query = Contract::select(
            'id',
            'contract_name',
            'currency',
            'currency_value',
            'contract_status',
            'substatus',
            'contract_type',
            'legal_advisor_email',
            'created_at'
        )
        ->where('legal_advisor_email', session()->get('contractSessionUser'))
        ->where('status', 1)
        ->orderBy('id', 'desc');

        // Filter by contract type
        if($request->contracttype && count($request->contracttype) > 0){
            $query->whereIn('contract_type', $request->contracttype);
        }

        // Filter by status
        if($request->contractstatus && $request->contractstatus !== 'all'){
            if($request->contractstatus === 'executed'){
                $query->where('contract_status', 'Executed');
            } else {
                $query->whereRaw('LOWER(contract_status) = ?', [strtolower($request->contractstatus)]);
            }
        }

        $contracts = $query->get();

        return response()->json([
            'success' => true,
            'contracts' => $contracts,
            'count' => count($contracts)
        ]);
    }

    /**
     * View contract details - redirect to contract view
     */
    public function viewContract($contractId)
    {
        $currentUserEmail = auth()->user()->email;
        
        $contract = Contract::where('id', $contractId)
            ->where('legal_advisor_email', $currentUserEmail)
            ->where('status', 1)
            ->firstOrFail();

        return redirect()->route('contractView', ['id' => $contractId]);
    }
}
