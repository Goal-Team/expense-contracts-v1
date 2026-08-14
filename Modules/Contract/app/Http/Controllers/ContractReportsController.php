<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;

use App;

use DB;

use Illuminate\Http\Request;

use App\Models\BranchUser;

use App\Models\ContractType;

use App\Models\Contract;

use App\Models\EntityBusiness;

use App\Models\ClausesCategory;

use PDF;

use Maatwebsite\Excel\Facades\Excel;

use Modules\Contract\app\Exports\LocationTypeCountsExport;
use Modules\Contract\app\Exports\ContractTypeSubstatusExport;

class ContractReportsController extends Controller
{

    public function __construct()
    {
        if(Controller::checkCurrentAuth("Contracts") != 1){
            return abort('404');
        }
        
        $branchs =  BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('LegalName', 'branch')
            )->get();

        // Share variable with all views automatically
        view()->share('branchs', $branchs);        
    }
    
    
    public function statusReports(Request $request, $executed = "")
    {

            
        $contracts_all = Contract::select('contract_name', 'id', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date','contract_end_date','onetime_end_date','contract_type')->orderBy('id', 'desc')->where('status', 1)->get();
        
        $locationFilter = $_COOKIE['filterByLocationReport'] ?? '[]';
       
        $contracts = $this->availableContracts($contracts_all, true);
        
        $contract_all_nexe_total           = 0;
        $contract_all_exe_total            = 0;
        $contract_draft_total              = 0;
        $contract_review_total             = 0;
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
        
        foreach ($contracts as $contract) {

            switch (strtolower($contract->contract_status)) {
                case 'executed':
                    $contract_executable_total++;
                    $contract_all_exe_total++;
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
                        $contract_all_nexe_total++;
                        break;        
                    case 'review':
                        $contract_review_total++;
                        $contract_all_nexe_total++;
                        break;        
                    case 'negotiation':
                        $contract_negotiation_total++;
                        $contract_all_nexe_total++;
                        break;
                    case 'approval':
                        $contract_approval_total++;
                        $contract_all_nexe_total++;
                        break;        
                    case 'approved':
                        $contract_approved_total++;
                        $contract_all_nexe_total++;
                        break;        
                    case 'signing':
                        $contract_signing_total++;
                        $contract_all_nexe_total++;
                        break;
            }           
            
        }
        
        
        $statusLabels = array(
            'draft' => 'Draft',
            'review' => 'Review',
            'negotiation' => 'Negotiation',
            'approval' => 'Pending Approval',
            'approved' => 'Approved',
            'signing' => 'Signing',
            'executed' => 'Executed',
            'executed_active' => 'Active',
            'executed_expired' => 'Expired',
            'executed_pending' => 'Pending',
            'executed_renewed' => 'Renewed',
            'executed_terminated' => 'Terminated',
            'executed_completed' => 'Completed'            
        );
        
        $pageTitle = "Non Executed";


        $viewFile = 'contract::reports.viewReports';

        if($executed == ""){
    
            $status = array(
                'draft' => $contract_draft_total,
                'review' => $contract_review_total,
                'negotiation' => $contract_negotiation_total,
                'approval' => $contract_approval_total,
                'approved' => $contract_approved_total,
                'signing' => $contract_signing_total
            );
            
            $statusColorClass = array(
                'draft' => 'primary',
                'review' => 'warning',
                'negotiation' => 'success',
                'approval' => 'info',
                'approved' => 'success',
                'signing' => 'info',
                'executed' => 'info',
            );
            
            $statusColor = array(
                'draft' => '#7367f0',
                'review' => '#ff9f43',
                'negotiation' => '#28c76f',
                'approval' => '#00cfe8',
                'approved' => '#28c76f',
                'signing' => '#00cfe8',
                'executed' => '#00cfe8',
            ); 
            
            $allcount = $contract_all_nexe_total;
            
        }else if($executed == 'executed'){
            $pageTitle = "Executed";
            
            $status = array(
                'executed_active' => $contract_executable_active_total,
                'executed_expired' => $contract_executable_expired_total,
                'executed_pending' => $contract_executable_pending_total,
                'executed_renewed' => $contract_executable_renewed_total,
                'executed_terminated' => $contract_executable_termina_total,
                'executed_completed' => $contract_executable_comp_total
            );
            
            $statusColorClass = array(
                'executed_active' => 'success',
                'executed_expired' => 'danger',
                'executed_pending' => 'warning',
                'executed_renewed' => 'info',
                'executed_terminated' => 'danger',
                'executed_completed' => 'success'
            );
            
            $statusColor = array(
                'executed_active' => '#28c76f',
                'executed_expired' => '#ea5455',
                'executed_pending' => '#ff9f43',
                'executed_renewed' => '#00cfe8',
                'executed_terminated' => '#a8aaae',
                'executed_completed' => '#7367f0'
            ); 
            
            $allcount = $contract_all_exe_total;

        }
        
        
        return view($viewFile)
        ->with('executed', $executed)
        ->with('pageTitle', $pageTitle)
        ->with('status', $status)
        ->with('allContracts', $allcount)
        ->with('statusLabels', $statusLabels)
        ->with('statusClass', $statusColorClass)
        ->with('statusColor', $statusColor);
    }

    public function uploadedReports(Request $request)
    {
        $dataStored = $request->session()->get('dataStored');
        return view('contract::reports.uploadedReports', compact('dataStored'));
    }
    
    public function statusReportsData(Request $request, $onlyArray=false)
    {
        
        if (isset($request->status) && $request->status !== 'all') {
            $contracts_query = Contract::select('contract_name', 'id', 'currency', 'currency_value', 'end_contract_type', 
                                'contract_status','substatus','fixed_date','contract_end_date','onetime_end_date','contract_type','catgoery_id');
            if (str_contains($request->status, 'executed_')) {
                $contracts_query->where('contract_status', 'executed');
                $contracts_query->where('substatus', explode('_', $request->status)[1]);
            } else {
                $contracts_query->where('contract_status', $request->status);
            }
            $contracts_query->where('status', 1);
            $contracts_query->orderBy('id', 'desc');
            $contracts = $contracts_query->get();
        } else if(isset($request->status) && $request->status === 'all') {
            $contracts = Contract::select('contract_name', 'id', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date','onetime_end_date','contract_type','catgoery_id')
            ->orderBy('id', 'desc')
            ->where('status',1)
            ->get();
        }else{
            $contracts = [];
        }

        $ContractsFinal = $this->availableContracts($contracts, true);
        
        // $final_contract_array = [];
        
        // foreach($ContractsFinal as $keyCon => $contract){
        //     $contract->currency_value_converted = "-";
        //     if($contract->currency_value > 0){
        //         $contract->currency_value_converted = currency_formatter(env('default_currency'),$contract->currency_value);
        //         $applicable = false;
        //         if(isset($request->locId)){
        //               $contractParty = $contract->contractParty;
        //               foreach ($contractParty as $contractPart) {
                    
                
        //                 //Check Branches Accessible for the User
        //                 if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Internal' && $contractPart->contract_party_location_id == $request->locId) {                        
        //                     $applicable = true;
        //                 }
        //               }                        
        //         }else{
        //           $applicable = true; 
        //         }

        //         if($applicable){
        //             $final_contract_array[] = $contract;
        //         }
        //     }
        // }
        
        // $currency_value_finder_arr = array_column($final_contract_array, 'currency_value');

        // array_multisort($currency_value_finder_arr, SORT_DESC, $final_contract_array);
        
        // $ContractsFinal = array_slice($final_contract_array, 0, 5);

        
        if($onlyArray){
            return $ContractsFinal;
        }

        return response()->json([
            'data' => $ContractsFinal,
            'draw' => $request->input('draw') ?? 1,
            'recordsTotal' => count($ContractsFinal),
            'recordsFiltered' => count($ContractsFinal)
        ]);
    }      

    public function statusReportsExpired(Request $request, $onlyArray=false)
    {

        $onlyData = false;
        $contracts_all_query = Contract::select('contract_name', 'id', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date','contract_end_date','onetime_end_date','fixedterm_end_date','contract_type')
        ->orderBy('id', 'desc')
        //->where('end_contract_type', "fixedTerm")
        ->where('contract_status', "executed")
        ->where('status', 1);

        if(!isset($request->filterStatus)){
            if(!isset($_COOKIE['filterByYear'])){
                $yearFilter = date('Y');
            }else{
                $yearFilter = $_COOKIE['filterByYear'];
            }

            $contracts_all_query->whereYear('contract_end_date', $yearFilter);
        }

        if(isset($request->yearFilter)){
            $contracts_all_query->whereYear('contract_end_date', $request->yearFilter);
            $onlyData = true;
        }

        // if(isset($request->filterStatus)){
        //     $contracts_all_query->where('substatus', $request->filterStatus);
        //     $onlyData = true;
        // }

        $contracts_all = $contracts_all_query->get();

        $contracts = $this->availableContracts($contracts_all, true);
        
        $months_array = [
            '01'=>'January',
            '02'=>'February',
            '03'=>'March',
            '04'=>'April',
            '05'=>'May',
            '06'=>'June',
            '07'=>'July',
            '08'=>'August',
            '09'=>'September',
            '10'=>'October',
            '11'=>'November',
            '12'=>'December'
        ];

        $configYears = config('app.YEAR_GAPS');
        $future_years = date("Y", strtotime("+$configYears year"));
        $past_years = date("Y", strtotime("-$configYears year"));
        
        $contract_all_expired              = 0;
        $contract_all_expirin              = 0;

        $expired_contracts_arry = array_fill_keys(array_keys($months_array), 0);
        $expired_cont_bar_color = array_fill_keys(array_keys($months_array), '#ea5455');
        $expirin_contracts_arry = array_fill_keys(array_keys($months_array), 0);
        $expirin_cont_bar_color = array_fill_keys(array_keys($months_array), '#ff9f43');
        
        $today_date = date('d-m-Y h:i:s');
        
        $contracts_final = [];
        foreach ($contracts as $contract) {

            $end_con_type = decryptString($contract->end_contract_type, 'end_contract_type');

            $end_date = $contract->contract_end_date;
            
            if($end_con_type == 'fixedTerm'){
                switch (strtolower($contract->contract_status)) {
                case 'executed':
                    $month_from_date = date('m',strtotime($end_date));
                    if(strtotime($today_date) > strtotime($end_date)){
                        $contract_all_expired++;
                        $expired_contracts_arry[$month_from_date]++;
                        if($request->filterStatus == 'expired'){
                            $contracts_final[] = $contract;
                        }
                    }else{
                        $contract_all_expirin++;
                        $expirin_contracts_arry[$month_from_date]++;
                        if($request->filterStatus == 'active'){
                            $contracts_final[] = $contract;
                        }
                    }
                    // switch ($contract->substatus) {
                    //     case 'expired':
                    //         $contract_all_expired++;
                    //         $expired_contracts_arry[$month_from_date]++;
                    //         $contracts_final[] = $contract;
                    //         break;
                    //     case 'active':
                    //         break;
                    // }
                    break;
            } 
            }

        }

        //Expired Contract Highlight Color
        $highlight_expired_bar = "#ea5455";
        $value_max_expired_arr = max($expired_contracts_arry);
        $value_max_expired_key = array_search($value_max_expired_arr, $expired_contracts_arry);
        //$expired_cont_bar_color[$value_max_expired_key] = $highlight_expired_bar;

        //Expiring Contract Highlight Color
        $highlight_expiring_bar = "#ff9f43";
        $value_max_expiring_arr = max($expirin_contracts_arry);
        $value_max_expiring_key = array_search($value_max_expiring_arr, $expirin_contracts_arry);
        //$expirin_cont_bar_color[$value_max_expiring_key] = $highlight_expiring_bar;

        
        $page_title = "Aging";
        
        
        if($onlyArray){
            return $contracts_final;
        }        
        
        if($onlyData){
            return response()->json([
                'data' => $contracts_final,
                'draw' => $request->input('draw') ?? 1,
                'recordsTotal' => count($contracts_final),
                'recordsFiltered' => count($contracts_final)
            ]);
        }

        return view('contract::reports.viewReportsExpire')
        ->with('pageTitle', $page_title)
        ->with('monthsXais', $months_array)
        ->with('fromYear', $past_years)
        ->with('toYear', $future_years)
        ->with('expiredContractsDat', $expired_contracts_arry)
        ->with('expiredContBarColor', $expired_cont_bar_color)
        ->with('expirinContractsDat', $expirin_contracts_arry)
        ->with('expirinContBarColor', $expirin_cont_bar_color)
        ->with('allContractsExpired', $contract_all_expired)
        ->with('allContractsExpirin', $contract_all_expirin);
    }
    
    public function contractTypeReports(Request $request)
    {

        $contractTypes = ContractType::select('contract_type_id','contract_type')->get();

        $contracts = Contract::select('contract_name', 'id', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date','contract_end_date','onetime_end_date','contract_type')
        ->orderBy('id', 'desc')
        ->where('status', 1)
        ->get();

        $contract_type_array = $this->availableContracts($contracts, true);



        $branch_locations = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch')
            )->get(); 
        $contract_type_array_final = [];

        foreach($contract_type_array as $cont){
                if($request->locationId && $request->locationId > 0){
                    $contractParty = $cont->contractParty;
                                foreach ($contractParty as $contractPart) {
                        
                                    //Check Branches Accessible for the User
                                    if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Internal' && $request->locationId == $contractPart->contract_party_location_id) {                        
                                        if(!isset($contract_type_array_final[$cont->contract_type_id])){
                                            $contract_type_array_final[$cont->contract_type_id] = 1;
                                        }else{
                                            $contract_type_array_final[$cont->contract_type_id]++;
                                        }
                                    }
                                }
                }else{
                                    if(!isset($contract_type_array_final[$cont->contract_type_id])){
                                        $contract_type_array_final[$cont->contract_type_id] = 1;
                                    }else{
                                        $contract_type_array_final[$cont->contract_type_id]++;
                                    }	
                }
        }
        $allcount = array_sum($contract_type_array_final);	 
            
        return view('contract::reports.viewReportsContTypes')
        ->with('contractTypes', $contractTypes)
        ->with('contTypeCountArr', $contract_type_array_final)
        ->with('branchLocation', $branch_locations)
        ->with('allContracts', $allcount);
    } 

    public function contractTagsReports()
    {

        $contractTypes = ContractType::select('contract_type_id','contract_type')->get();
        
        $contracts = Contract::select('contract_name', 'id', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date','contract_end_date','onetime_end_date','contract_type','contract_tags')
        ->orderBy('id', 'desc')
        ->where('status', 1)
        ->get();
        
        $contract_type_array = $this->availableContracts($contracts, false, "contract_tags");
        
        $tagWiseData = [];

        
        foreach($contract_type_array as $keyCon => $con_tags){
            $allKeys = json_decode($keyCon);
            if(is_array($allKeys)){
                //print_r($allKeys);
                
                foreach($allKeys as $ky){
                    if(!isset($tagWiseData[$ky])){
                        
                        $tagWiseData[$ky] = $con_tags;
                    }else{
                        $tagWiseData[$ky] = $con_tags + $tagWiseData[$ky];
                    }
                }
            }
        }
        
        $allcount = array_sum($tagWiseData);
        
        $branch_locations = BranchUser::select(
            'id',
            decrypt_data('LegalName', 'branch')
            )->get();  
            
        return view('contract::reports.viewReportsContTags')
        ->with('contractTypes', $contractTypes)
        ->with('contTypeCountArr', $tagWiseData)
        ->with('branchLocation', $branch_locations)
        ->with('allContracts', $allcount);
    } 

    public function contractValueReportsnew(Request $request)
    {

        $contractTypes = ContractType::select('contract_type_id','contract_type','departmentId')->get();
        
        $contractDeptsQuery = EntityBusiness::select('id','name');
        
        if($request->deptId){
            $contractDeptsQuery->where('id', $request->deptId);
        }
        $contractDepts = $contractDeptsQuery->get();
        
        $contracts = Contract::select('contract_name', 'id', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date','contract_end_date','onetime_end_date','contract_type', 'department_id')
        ->orderBy('id', 'desc')
        ->where('status', 1)
        ->get();
        
        $contract_data = $this->availableContracts($contracts, true);
        
        $typeWiseData = [];
        $sumTotal = 0;
        foreach($contract_data as $keyCon => $contract){
            $currencyVal = is_numeric($contract->currency_value) ? $contract->currency_value : 0;
            if(!isset($typeWiseData[$contract->department_id])){
                $typeWiseData[$contract->department_id]['value'] = $currencyVal;
                $typeWiseData[$contract->department_id]['contracts'] = [$contract->id];
            }else{
                $typeWiseData[$contract->department_id]['value'] = $typeWiseData[$contract->department_id]['value'] + $currencyVal;
                $typeWiseData[$contract->department_id]['contracts'][] = $contract->id;
            }

            if(!isset($typeWiseData[$contract->department_id][$contract->contract_type_id])){
                $typeWiseData[$contract->department_id][$contract->contract_type_id]['value'] = $currencyVal;
                $typeWiseData[$contract->department_id]['contracts'] = [$contract->id];
            }else{
                $typeWiseData[$contract->department_id][$contract->contract_type_id]['value'] = $typeWiseData[$contract->department_id][$contract->contract_type_id]['value'] + $currencyVal;
                $typeWiseData[$contract->department_id]['contracts'][] = $contract->id;
            }
            
            $contractParty = $contract->contractParty;
            $firstInternal = 0;
            foreach ($contractParty as $contractPart) {
                if($firstInternal > 1){
                    continue;
                }
                //Check Branches Accessible for the User
                if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Internal') {  
                    $firstInternal++;
                    if($firstInternal == 1){
                        if(!isset($typeWiseData[$contract->department_id][$contract->contract_type_id]['loc'][$contractPart->contract_party_location_id])){
                            $typeWiseData[$contract->department_id][$contract->contract_type_id]['loc'][$contractPart->contract_party_location_id] = $currencyVal;
                            $typeWiseData[$contract->department_id][$contract->contract_type_id]['locCount'][$contractPart->contract_party_location_id] = 1;
                        }else{
                            $typeWiseData[$contract->department_id][$contract->contract_type_id]['loc'][$contractPart->contract_party_location_id] = $typeWiseData[$contract->department_id][$contract->contract_type_id]['loc'][$contractPart->contract_party_location_id] + $currencyVal;
                            $typeWiseData[$contract->department_id][$contract->contract_type_id]['locCount'][$contractPart->contract_party_location_id]++;
                        }
                    }
                }
            }
            
            $sumTotal += $currencyVal;
        }
        
        
        $typesValue = array_column($typeWiseData, 'value');
        
        $allcount = $sumTotal;
        
        $branch_locations = BranchUser::select(
            'id',
            decrypt_data('LegalName', 'branch')
            )->get();  
		
		if(isset($request->loadTree)){
			$finalHtml = "";
			$scrollToData = "";
			$contTypeNameArr = [];
			$contTypeValuArr = [];
			$contTypeCountArr = $typeWiseData;
			foreach($contractDepts as $condept){
				$contDepValue = 0;
				$contDepCount = 0;
				$contDepCountNv = 0;
				$parentText = "";
				$conDepObj = new \stdClass();
				$conDepObj->deptId = $condept->id;
				$finalObj = json_encode((array)$conDepObj);
				$contTypeNameArr[$condept->id] = '"'.$condept->name.'"';
				$parentText .= $condept->name;
				if(!$request->deptId){
    				$scrollToData = '<span class="ms-2 showData position-absolute" title="Show Data"><i class="ti ti-xs ti-table-down"></i></span>';
    				$finalHtml .= '<li class="py-2 position-relative [finalDepClass] '.$contract->currency_value.'" data-par-dropdown="Overall vs '.$condept->name.','.$condept->name.'" data-par-text="Overall ,'.$condept->name.'" data-par-val="'.$allcount.',[finalDepValueOrg]" data-fo-id="dept_'.$condept->id.'" data-cdept=\''.$finalObj.'\' data-jstree=\'{"icon" : "ti ti-building-bank text-primary"}\'>'.$condept->name.'[finalDepValue][finalDepCount]'.$scrollToData.'<ul>';
    				
                    if(isset($contTypeCountArr[$condept->id])){
                      
                      $conDeptConValue = $contTypeCountArr[$condept->id]['value'];
                      
                      $contDepValue = $contDepValue + $conDeptConValue;
                      if( $conDeptConValue > 0){
                          $contDepCount++;
                      }else{
                          $contDepCountNv++;
                      }
                    }
                    
                    if($contDepCount > 0){
                        $finalHtml .='<li class="py-2 position-relative" data-jstree=\'{"icon" : "ti ti-building-bank text-primary"}\'>Loading...</li>';
                    }
				}
				
			    if($request->deptId){
				    foreach($contractTypes as $contype){

				  $prog_width = 0;
				  $contypeCount = 0;
				  if(isset($contTypeCountArr[$condept->id][$contype->contract_type_id])){
					  
					  $contypeCount = $contTypeCountArr[$condept->id][$contype->contract_type_id]['value'];
					  
					  $contDepValue = $contDepValue + $contypeCount;
				  }
				  
				  $valueCon = '';
				  $dotted_border_main = 'all-count no-count d-none';
				  $contTypeDepCount = 0;
					$valueCon .= '<span class="btn btn-sm btn-warning bg-glow ms-2 toggle-spans toggle_count">[finalDepTypeCount]</span>';
					$valueCon .= '<span class="btn btn-sm btn-warning bg-glow position-absolute end-0 me-1 toggle-spans toggle_val">'.currency_formatter(env('default_currency'),$contypeCount).'</span>';
				  if($contypeCount > 0){
					$dotted_border_main = 'all-count yes-count dotted-border';
				  }
					$conDepObj = new \stdClass();
					$conDepObj->deptId = $condept->id;
					$conDepObj->conTyp = $contype->contract_type_id;
					$finalObj = json_encode((array)$conDepObj);
				    $finalHtml .= '<li data-fo-id="deptConType_'.$contype->contract_type_id.'" data-par-dropdown="Overall vs '.$contype->contract_type.','.$condept->name.' vs '.$contype->contract_type.','.$contype->contract_type.'" data-par-text="Overall ,'.$condept->name.','.$contype->contract_type.'" data-par-val="'.$allcount.',[finalDepValueOrg],'.$contypeCount.'" class="py-2 position-relative [finalConTypeClass]" data-cdept=\''.$finalObj.'\' data-jstree=\'{"icon" : "ti ti-building-bank text-warning"}\'>'.$contype->contract_type.$valueCon.$scrollToData;
				  //if($contypeCount > 0){
					  $finalHtml .= '<ul class="pt-4">';
					  foreach($branch_locations as $loc){
					  
						  $location_name = $loc->LegalName;
						  $location_name_text = $loc->LegalName;
							
						  $locValueCon = '';
						  $dotted_border = '';
						  if(isset($contTypeCountArr[$condept->id][$contype->contract_type_id]['loc'][$loc->id])){
							$location_name = '<span class="dotted-af-text d-inline-flex">'.$location_name.'</span>';
							$locValCount = $contTypeCountArr[$condept->id][$contype->contract_type_id]['locCount'][$loc->id] ?? 0;
							if($locValCount > 0 ){
								$locValueCon .= '<span class="btn btn-sm btn-success bg-glow ms-2 toggle-spans toggle_count">'.$locValCount.'</span>'.$scrollToData;
							}
							$locValueCon .= '<span class="btn btn-sm btn-success bg-glow position-absolute end-0 me-1 toggle-spans toggle_val">'.currency_formatter(env('default_currency'),$contTypeCountArr[$condept->id][$contype->contract_type_id]['loc'][$loc->id]).'</span>';
							$dotted_border = 'dotted-border';
							$contTypeDepCount += $locValCount;
							if($contTypeCountArr[$condept->id][$contype->contract_type_id]['loc'][$loc->id] > 0){
							    $contDepCount += $locValCount;
							}else{
							    $contDepCountNv += $locValCount;
							}
						  }
							$conDepObj = new \stdClass();
							$conDepObj->deptId = $condept->id;
							$conDepObj->conTyp = $contype->contract_type_id;
							$conDepObj->locId = $loc->id;
							$finalObj = json_encode((array)$conDepObj);                              
						  $finalHtml .= '<li class="py-2 position-relative '.$dotted_border.'" data-par-dropdown="Overall vs '.$location_name_text.','.$condept->name." vs ".$location_name_text.','.$contype->contract_type." vs ".$location_name_text.','.$loc->LegalName.'" data-par-text="Overall ,'.$condept->name.','.$contype->contract_type.','.$loc->LegalName.'" data-par-val="'.$allcount.',[finalDepValueOrg],'.$contypeCount.','.($contTypeCountArr[$condept->id][$contype->contract_type_id]['loc'][$loc->id] ?? 0).'" data-fo-id="deptConLoc_'.$loc->id.'" data-cdept=\''.$finalObj.'\' data-jstree=\'{"icon" : "ti ti-device-mobile-pin text-success"}\'>'.$location_name.$locValueCon.'</li>';
					  }
					  $finalHtml .= '</ul>';
				    //}
				  $finalHtml .= '</li>';
				  $finalHtml = str_replace('[finalDepTypeCount]',$contTypeDepCount,$finalHtml);
				  $finalHtml = str_replace('[finalConTypeClass]',$contTypeDepCount > 0 ? 'all-count yes-count dotted-border' : 'all-count no-count',$finalHtml);
                    
				}
			    }
                
                if(!$request->deptId){
    				$contDepValue1 = '<span class="btn btn-sm btn-primary bg-glow position-absolute end-0 me-1 toggle-spans toggle_val">'.currency_formatter(env('default_currency'),$contDepValue).'</span>';
    				$contDepCount1 = '<span class="ms-2 toggle-spans toggle_count"><span class="badge bg-info">T '.$contDepCount+$contDepCountNv.'</span>=<span class="badge bg-success">V '.$contDepCount.'</span>+<span class="badge bg-danger">NV '.$contDepCountNv.'</span></span>';
    				$finalHtml = str_replace('[finalDepValue]',($contDepValue || $contDepCountNv) > 0 ? $contDepValue1 : '',$finalHtml);
    				$finalHtml = str_replace('[finalDepValueOrg]',($contDepValue || $contDepCountNv) > 0 ? $contDepValue : '',$finalHtml);
    				$finalHtml = str_replace('[finalDepCount]',$contDepCount > 0 ? $contDepCount1 : '',$finalHtml);
    				$finalHtml = str_replace('[finalDepClass]',$contDepCount > 0 ? 'all-count yes-count' : 'all-count no-count',$finalHtml);
    				$finalHtml .= '</ul>';
    				$finalHtml .= '</li>';
                }
				$contTypeValuArr[$condept->id] = $contDepValue;
			}
			
			return response()->json([
                'treedata' => $finalHtml,
				'chartdata' => [implode(',',array_values($contTypeNameArr)),implode(',',array_values($contTypeValuArr))]
			]);
				
	    } 
        return view('contract::reports.viewReportsContTypesValues')
        ->with('contractDepts', $contractDepts)
        ->with('contractTypes', $contractTypes)
        ->with('contTypeCountArr', $typeWiseData)
        ->with('branchLocation', $branch_locations)
        ->with('allContracts', $allcount);
    }

    public function contractValueReports(Request $request)
    {

        $contractTypes = ContractType::select('contract_type_id','contract_type','departmentId')->get();
        
        $contractDepts = EntityBusiness::select('id','name')->get();
        
        $contracts = Contract::select('contract_name', 'id', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date','contract_end_date','onetime_end_date','contract_type', 'department_id')
        ->orderBy('id', 'desc')
        ->where('status', 1)
        ->get();
        
        $contract_data = $this->availableContracts($contracts, true);
        
        $typeWiseData = [];
        $sumTotal = 0;
        foreach($contract_data as $keyCon => $contract){
            $currencyVal = is_numeric($contract->currency_value) ? $contract->currency_value : 0;
            if(!isset($typeWiseData[$contract->department_id][$contract->contract_type_id])){
                $typeWiseData[$contract->department_id][$contract->contract_type_id]['value'] = $currencyVal;
                $typeWiseData[$contract->department_id]['contracts'] = [$contract->id];
            }else{
                $typeWiseData[$contract->department_id][$contract->contract_type_id]['value'] = $typeWiseData[$contract->department_id][$contract->contract_type_id]['value'] + $currencyVal;
                $typeWiseData[$contract->department_id]['contracts'][] = $contract->id;
            }
            
            $contractParty = $contract->contractParty;
            $firstInternal = 0;
            foreach ($contractParty as $contractPart) {
                if($firstInternal > 1){
                    continue;
                }
                //Check Branches Accessible for the User
                if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Internal') {  
                    $firstInternal++;
                    if($firstInternal == 1){
                        if(!isset($typeWiseData[$contract->department_id][$contract->contract_type_id]['loc'][$contractPart->contract_party_location_id])){
                            $typeWiseData[$contract->department_id][$contract->contract_type_id]['loc'][$contractPart->contract_party_location_id] = $currencyVal;
                            $typeWiseData[$contract->department_id][$contract->contract_type_id]['locCount'][$contractPart->contract_party_location_id] = 1;
                        }else{
                            $typeWiseData[$contract->department_id][$contract->contract_type_id]['loc'][$contractPart->contract_party_location_id] = $typeWiseData[$contract->department_id][$contract->contract_type_id]['loc'][$contractPart->contract_party_location_id] + $currencyVal;
                            $typeWiseData[$contract->department_id][$contract->contract_type_id]['locCount'][$contractPart->contract_party_location_id]++;
                        }
                    }
                }
            }
            
            $sumTotal += $currencyVal;
        }
        
        
        $typesValue = array_column($typeWiseData, 'value');
        
        $allcount = $sumTotal;
        
        $branch_locations = BranchUser::select(
            'id',
            decrypt_data('LegalName', 'branch')
            )->get();  
		
		if(isset($request->loadTree)){
			$finalHtml = "";
			$contTypeNameArr = [];
			$contTypeValuArr = [];
			$contTypeCountArr = $typeWiseData;
			foreach($contractDepts as $condept){
				$contDepValue = 0;
				$contDepCount = 0;
				$contDepCountNv = 0;
				$parentText = "";
				$conDepObj = new \stdClass();
				$conDepObj->deptId = $condept->id;
				$finalObj = json_encode((array)$conDepObj);
				$contTypeNameArr[$condept->id] = '"'.$condept->name.'"';
				$parentText .= $condept->name;
				$scrollToData = '<span class="ms-2 showData position-absolute" title="Show Data"><i class="ti ti-xs ti-table-down"></i></span>';
				$finalHtml .= '<li class="py-2 position-relative [finalDepClass]" data-par-dropdown="Overall vs '.$condept->name.','.$condept->name.'" data-par-text="Overall ,'.$condept->name.'" data-par-val="'.$allcount.',[finalDepValueOrg]" data-fo-id="dept_'.$condept->id.'" data-cdept=\''.$finalObj.'\' data-jstree=\'{"icon" : "ti ti-building-bank text-primary"}\'>'.$condept->name.'[finalDepValue][finalDepCount]'.$scrollToData.'<ul>';
				foreach($contractTypes as $contype){

				  $prog_width = 0;
				  $contypeCount = 0;
				  if(isset($contTypeCountArr[$condept->id][$contype->contract_type_id])){
					  
					  $contypeCount = $contTypeCountArr[$condept->id][$contype->contract_type_id]['value'];
					  
					  $contDepValue = $contDepValue + $contypeCount;
				  }
				  
				  $valueCon = '';
				  $dotted_border_main = 'all-count no-count d-none';
				  $contTypeDepCount = 0;
					$valueCon .= '<span class="btn btn-sm btn-warning bg-glow ms-2 toggle-spans toggle_count">[finalDepTypeCount]</span>';
					$valueCon .= '<span class="btn btn-sm btn-warning bg-glow position-absolute end-0 me-1 toggle-spans toggle_val">'.currency_formatter(env('default_currency'),$contypeCount).'</span>';
				  if($contypeCount > 0){
					$dotted_border_main = 'all-count yes-count dotted-border';
				  }
					$conDepObj = new \stdClass();
					$conDepObj->deptId = $condept->id;
					$conDepObj->conTyp = $contype->contract_type_id;
					$finalObj = json_encode((array)$conDepObj);
				    $finalHtml .= '<li data-fo-id="deptConType_'.$contype->contract_type_id.'" data-par-dropdown="Overall vs '.$contype->contract_type.','.$condept->name.' vs '.$contype->contract_type.','.$contype->contract_type.'" data-par-text="Overall ,'.$condept->name.','.$contype->contract_type.'" data-par-val="'.$allcount.',[finalDepValueOrg],'.$contypeCount.'" class="py-2 position-relative [finalConTypeClass]" data-cdept=\''.$finalObj.'\' data-jstree=\'{"icon" : "ti ti-building-bank text-warning"}\'>'.$contype->contract_type.$valueCon.$scrollToData;
				  //if($contypeCount > 0){
					  $finalHtml .= '<ul class="pt-4">';
					  foreach($branch_locations as $loc){
					  
						  $location_name = $loc->LegalName;
						  $location_name_text = $loc->LegalName;
							
						  $locValueCon = '';
						  $dotted_border = '';
						  if(isset($contTypeCountArr[$condept->id][$contype->contract_type_id]['loc'][$loc->id])){
							$location_name = '<span class="dotted-af-text d-inline-flex">'.$location_name.'</span>';
							$locValCount = $contTypeCountArr[$condept->id][$contype->contract_type_id]['locCount'][$loc->id] ?? 0;
							if($locValCount > 0 ){
								$locValueCon .= '<span class="btn btn-sm btn-success bg-glow ms-2 toggle-spans toggle_count">'.$locValCount.'</span>'.$scrollToData;
							}
							$locValueCon .= '<span class="btn btn-sm btn-success bg-glow position-absolute end-0 me-1 toggle-spans toggle_val">'.currency_formatter(env('default_currency'),$contTypeCountArr[$condept->id][$contype->contract_type_id]['loc'][$loc->id]).'</span>';
							$dotted_border = 'dotted-border';
							$contTypeDepCount += $locValCount;
							if($contTypeCountArr[$condept->id][$contype->contract_type_id]['loc'][$loc->id] > 0){
							    $contDepCount += $locValCount;
							}else{
							    $contDepCountNv += $locValCount;
							}
						  }
							$conDepObj = new \stdClass();
							$conDepObj->deptId = $condept->id;
							$conDepObj->conTyp = $contype->contract_type_id;
							$conDepObj->locId = $loc->id;
							$finalObj = json_encode((array)$conDepObj);                              
						  $finalHtml .= '<li class="py-2 position-relative '.$dotted_border.'" data-par-dropdown="Overall vs '.$location_name_text.','.$condept->name." vs ".$location_name_text.','.$contype->contract_type." vs ".$location_name_text.','.$loc->LegalName.'" data-par-text="Overall ,'.$condept->name.','.$contype->contract_type.','.$loc->LegalName.'" data-par-val="'.$allcount.',[finalDepValueOrg],'.$contypeCount.','.($contTypeCountArr[$condept->id][$contype->contract_type_id]['loc'][$loc->id] ?? 0).'" data-fo-id="deptConLoc_'.$loc->id.'" data-cdept=\''.$finalObj.'\' data-jstree=\'{"icon" : "ti ti-device-mobile-pin text-success"}\'>'.$location_name.$locValueCon.'</li>';
					  }
					  $finalHtml .= '</ul>';
				    //}
				  $finalHtml .= '</li>';
				  $finalHtml = str_replace('[finalDepTypeCount]',$contTypeDepCount,$finalHtml);
				  $finalHtml = str_replace('[finalConTypeClass]',$contTypeDepCount > 0 ? 'all-count yes-count dotted-border' : 'all-count no-count d-none',$finalHtml);
                    
				}

				$contDepValue1 = '<span class="btn btn-sm btn-primary bg-glow position-absolute end-0 me-1 toggle-spans toggle_val">'.currency_formatter(env('default_currency'),$contDepValue).'</span>';
				$contDepCount1 = '<span class="ms-2 toggle-spans toggle_count"><span class="badge bg-info">T '.$contDepCount+$contDepCountNv.'</span>=<span class="badge bg-success">V '.$contDepCount.'</span>+<span class="badge bg-danger">NV '.$contDepCountNv.'</span></span>';
				$finalHtml = str_replace('[finalDepValue]',($contDepValue || $contDepCountNv) > 0 ? $contDepValue1 : '',$finalHtml);
				$finalHtml = str_replace('[finalDepValueOrg]',($contDepValue || $contDepCountNv) > 0 ? $contDepValue : '',$finalHtml);
				$finalHtml = str_replace('[finalDepCount]',$contDepCount > 0 ? $contDepCount1 : '',$finalHtml);
				$finalHtml = str_replace('[finalDepClass]',$contDepCount > 0 ? 'all-count yes-count' : 'all-count no-count d-none',$finalHtml);
				$finalHtml .= '</ul>';
				$finalHtml .= '</li>';
				$contTypeValuArr[$condept->id] = $contDepValue;
			}
			
			return response()->json([
                'treedata' => $finalHtml,
				'chartdata' => [implode(',',array_values($contTypeNameArr)),implode(',',array_values($contTypeValuArr))]
			]);
				
	    } 
        return view('contract::reports.viewReportsContTypesValues')
        ->with('contractDepts', $contractDepts)
        ->with('contractTypes', $contractTypes)
        ->with('contTypeCountArr', $typeWiseData)
        ->with('branchLocation', $branch_locations)
        ->with('allContracts', $allcount);
    }
    
    public function contractValueReportsTree(Request $request){
        
        $nodeId = $request->nodeid;
        $showGtZero = $request->get('show_gt_zero', 0); 

        // ROOT LEVEL: Departments
        if ($nodeId == '#' || $nodeId == null) {

            // Only load contracts ids and encrypted values
            $departments = EntityBusiness::select('id','name')->get();

            return $departments->map(function ($dept) use ($showGtZero) {
                $contracts = Contract::where('department_id', $dept->id)
                    ->select('currency_value')
                    ->where('status', 1)
                    ->get();
                
                $contracts = $this->availableContracts($contracts, true);
                
                $count = count($contracts);
                $total = 0;
                $countWithValue = 0;
                $countZero = 0;                
                foreach ($contracts as $c) {
                    $decrypted = decryptString($c->currency_value, 'currency_value');
                    //$total += floatval($decrypted);
                    
                    if ($decrypted > 0) {
                        $countWithValue++;
                        $total += $decrypted;
                    } else {
                        $countZero++;
                    }                    
                }
                
                if ($showGtZero && $count == 0) return null;

                return [
                    'id' => 'dept_' . $dept->id,
                    'text' => $this->jstreeHtmlText($dept->name, $countWithValue, $countZero, $total, 'primary', $count),
                    'children' => true,
                    'type' => 'department',
                    'data' => [
                        'total' => $decrypted,
                        'with_value' => $countWithValue,
                        'no_value' => $countZero,
                        'total_amount' => $total,
                        'textCustom' => $dept->name
                    ]                    
                ];
            });

        }

        // DEPARTMENT LEVEL: Contract Types
        if (strpos($nodeId, 'dept_') === 0) {
            
            $departmentId = str_replace('dept_', '', $nodeId);

            $types = ContractType::select('contract_type_id','contract_type','departmentId')->get();

            return $types->map(function ($type) use ($departmentId, $showGtZero) {
                
                $contracts = Contract::where('department_id', $departmentId)
                    ->where('contract_type', $type->contract_type_id)
                    ->select('currency_value')
                    ->where('status', 1)
                    ->get();
                
                $contracts = $this->availableContracts($contracts, true);
                
                
                $count = count($contracts);
                
                if ($showGtZero && $count == 0) return null;
                
                $total = 0;
                $countWithValue = 0;
                $countZero = 0;
                $decrypted = 0;
                foreach ($contracts as $c) {
                    $decrypted = decryptString($c->currency_value, 'currency_value');
                    //$total += floatval($decrypted);
                    
                    if ($decrypted > 0) {
                        $countWithValue++;
                        $total += $decrypted;
                    } else {
                        $countZero++;
                    }                    
                }
                

                return [
                    'id' => "type_{$departmentId}_{$type->contract_type_id}",
                    'text' => $this->jstreeHtmlText($type->contract_type, $countWithValue, $countZero, $total, 'success', $count),
                    'children' => true,
                    'type' => 'type',
                    'data' => [
                        'total' => $decrypted,
                        'with_value' => $countWithValue,
                        'no_value' => $countZero,
                        'total_amount' => $total,
                        'textCustom' => $type->contract_type,
                    ]                     
                ];
            });
        }

        // CONTRACT TYPE LEVEL: Locations
        if (strpos($nodeId, 'type_') === 0) {
            $parts = explode('_', $nodeId);
            $departmentId = $parts[1];
            $typeId = $parts[2];
            
            $allBranches = BranchUser::select('id', decrypt_data('LegalName', 'branch'))->get();

            // Get only necessary fields from contract_party_data + contracts
            $partyData = \App\Models\ContractPartyData::with('contract')
                ->whereHas('contract', function ($q) use ($departmentId, $typeId) {
                    $q->where('department_id', $departmentId)
                      ->where('status', 1)
                      ->where('contract_type', $typeId);
                })
            ->whereNotNull('contract_party_location_id')
            ->where('contract_party_type', 'internal')
            ->get();

            // Group by location
            $byLocation = $partyData->groupBy('contract_party_location_id');

            // Map over all branches
            return $allBranches->map(function ($branch) use ($byLocation,$departmentId, $typeId) {
                $contracts = $byLocation->has($branch->id) 
                    ? $byLocation[$branch->id]->pluck('contract')->unique('id')->values()
                    : collect();
                
                $contracts = $this->availableContracts($contracts, true);
                
                
                $count = count($contracts);            
                
                $countWithValue = 0;
                $countZero = 0;
                $total = 0;
                $decrypted = "";
            
                foreach ($contracts as $c) {
                    $decrypted = floatval(decryptString($c->currency_value, 'currency_value'));
                    if ($decrypted > 0) {
                        $countWithValue++;
                        $total += $decrypted;
                    } else {
                        $countZero++;
                    }
                }
            
                return [
                    'id' => "loc_{$departmentId}_{$typeId}_{$branch->id}",
                    'text' => $this->jstreeHtmlText($branch->LegalName, $countWithValue, $countZero, $total, 'warning', $count),
                    'children' => false,
                    'type' => 'location',
                    'data' => [
                        'total' => $decrypted,
                        'with_value' => $countWithValue,
                        'no_value' => $countZero,
                        'total_amount' => $total,
                        'textCustom' => $branch->LegalName,
                    ]                    
                ];
            })->values();

        }

        return [];
    }

    public function contractLocationReportData(Request $request)
    {

        if(isset($request->contractType)){

            $branch_locations_query = BranchUser::select(
                'id',
                decrypt_data('LegalName', 'branch'),
		        decrypt_data('BranchName', 'branch')
                ); 

    	    if(isset($request->locationId) && $request->locationId > 0){
    		    $branch_locations_query->where('id', $request->locationId);
            }
		
            $branch_locations = $branch_locations_query->get();
            
            $contracts_query = Contract::select('contract_name', 'id', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date','onetime_end_date','contract_type')
            ->orderBy('id', 'desc')
            ->where('status', 1);
            
            if($request->contractType > 0){
                $contracts_query->where('contract_type', $request->contractType);
            }

            $contracts = $contracts_query->get();

            $contract_type_array = $this->availableContracts($contracts, false, "contract_party_location_id", 'partyData');

            $final_data = [];
            foreach($branch_locations as $brloc){	
                $final_data[] = [
                    'locName' => $brloc->BranchName,
                    'locCount' => $contract_type_array[$brloc->id] ?? 0
                ];
            }   
    
            return response()->json([
                'data' => $final_data,
                'draw' => $request->input('draw') ?? 1,
                'recordsTotal' => count($contract_type_array),
                'recordsFiltered' => count($contract_type_array)
            ]);
        }
    }
    
    
    
    /**
     * Export location-wise contract type counts.
     * Accepts POST with optional 'locationId' filter.
     */
    public function exportLocationTypeCounts(Request $request)
    {
        $locationId = intval($request->input('locationId', 0));

        $contractTypesMap = ContractType::select('contract_type_id', 'contract_type')
            ->pluck('contract_type', 'contract_type_id')
            ->toArray();

        $contracts = Contract::select('contract_name', 'id', 'currency', 'currency_value', 'end_contract_type', 'contract_status', 'substatus', 'fixed_date', 'contract_end_date', 'onetime_end_date', 'contract_type')
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->get();

        $contracts = $this->availableContracts($contracts, true);

        $branchQuery = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch')
        );

        if ($locationId > 0) {
            $branchQuery->where('id', $locationId);
        }

        $branches = $branchQuery->get();

        $sheets = [];

        foreach ($branches as $branch) {
            $typeCounts = [];

            foreach ($contracts as $contract) {
                $isInBranch = false;

                foreach (($contract->contractParty ?? []) as $contractPart) {
                    if (
                        intval($contractPart->contract_party_location_id) === intval($branch->id)
                        && strtolower((string) $contractPart->contract_party_type) === 'internal'
                    ) {
                        $isInBranch = true;
                        break;
                    }
                }

                if (!$isInBranch) {
                    continue;
                }

                $typeId = $contract->contract_type_id ?? ($contract->contract_type ?? 0);
                $typeName = $contractTypesMap[$typeId] ?? ($contract->contract_type ?: 'Unspecified');

                if (!isset($typeCounts[$typeName])) {
                    $typeCounts[$typeName] = 0;
                }

                $typeCounts[$typeName]++;
            }

            $rows = [];
            $total = 0;
            foreach ($typeCounts as $typeName => $count) {
                $rows[] = ['type' => $typeName, 'count' => $count];
                $total += $count;
            }

            $sheets[] = [
                'title' => $branch->BranchName,
                'rows' => $rows,
                'count' => $total,
            ];
        }

        if ($locationId <= 0) {
            $allTypeCounts = [];

            foreach ($contracts as $contract) {
                $typeId = $contract->contract_type_id ?? ($contract->contract_type ?? 0);
                $typeName = $contractTypesMap[$typeId] ?? ($contract->contract_type ?: 'Unspecified');

                if (!isset($allTypeCounts[$typeName])) {
                    $allTypeCounts[$typeName] = 0;
                }

                $allTypeCounts[$typeName]++;
            }

            $allRows = [];
            $allTotal = 0;
            foreach ($allTypeCounts as $typeName => $count) {
                $allRows[] = ['type' => $typeName, 'count' => $count];
                $allTotal += $count;
            }

            array_unshift($sheets, [
                'title' => 'All Locations',
                'rows' => $allRows,
                'count' => $allTotal,
            ]);
        }

        $filename = 'location_contract_type_counts_' . ($locationId > 0 ? $locationId : 'all') . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new LocationTypeCountsExport($sheets), $filename);
    }

    /**
     * Export contract substatus counts per contract type.
     * Accepts POST with optional 'locationId' filter from reports-contract-types page.
     */
    public function exportContractTypeSubstatusCounts(Request $request)
    {
        $locationId = intval($request->input('locationId', 0));

        $contractTypesMap = ContractType::select('contract_type_id', 'contract_type')
            ->pluck('contract_type', 'contract_type_id')
            ->toArray();

        $contracts = Contract::select('id', 'contract_type', 'contract_status', 'substatus')
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->get();

        $contracts = $this->availableContracts($contracts, true);

        $statusLabelMap = [
            'active' => 'Active',
            'expired' => 'Expired',
            'pending' => 'Pending',
            'renewed' => 'Renewed',
            'terminated' => 'Terminated',
            'completed' => 'Completed',
            'not_set' => 'Not Set',
        ];

        $matrix = [];
        $seenSubstatuses = [];

        foreach ($contracts as $contract) {
            if ($locationId > 0) {
                $matchedLocation = false;
                foreach (($contract->contractParty ?? []) as $contractPart) {
                    if (
                        intval($contractPart->contract_party_location_id) === $locationId
                        && strtolower((string) $contractPart->contract_party_type) === 'internal'
                    ) {
                        $matchedLocation = true;
                        break;
                    }
                }
                if (!$matchedLocation) {
                    continue;
                }
            }

            $typeId = $contract->contract_type_id ?? ($contract->contract_type ?? 0);
            $typeName = $contractTypesMap[$typeId] ?? ($contract->contract_type ?: 'Unspecified');

            $rawSubstatus = strtolower(trim((string) ($contract->substatus ?? '')));
            $normalizedSubstatus = $rawSubstatus !== '' ? $rawSubstatus : 'not_set';
            $substatusLabel = $statusLabelMap[$normalizedSubstatus] ?? ucwords(str_replace('_', ' ', $normalizedSubstatus));

            if (!isset($matrix[$typeName])) {
                $matrix[$typeName] = [];
            }
            if (!isset($matrix[$typeName][$substatusLabel])) {
                $matrix[$typeName][$substatusLabel] = 0;
            }

            $matrix[$typeName][$substatusLabel]++;
            $seenSubstatuses[$substatusLabel] = true;
        }

        $preferredSubstatusOrder = ['Active', 'Expired', 'Pending', 'Renewed', 'Terminated', 'Completed', 'Not Set'];
        $orderedSubstatuses = [];
        foreach ($preferredSubstatusOrder as $label) {
            if (isset($seenSubstatuses[$label])) {
                $orderedSubstatuses[] = $label;
            }
        }

        $remainingSubstatuses = array_diff(array_keys($seenSubstatuses), $orderedSubstatuses);
        sort($remainingSubstatuses);
        $orderedSubstatuses = array_merge($orderedSubstatuses, $remainingSubstatuses);

        $orderedRows = [];
        foreach ($contractTypesMap as $typeName) {
            if (isset($matrix[$typeName])) {
                $orderedRows[$typeName] = $matrix[$typeName];
            }
        }
        foreach ($matrix as $typeName => $counts) {
            if (!isset($orderedRows[$typeName])) {
                $orderedRows[$typeName] = $counts;
            }
        }

        $filename = 'contract_type_substatus_counts_' . ($locationId > 0 ? $locationId : 'all') . '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new ContractTypeSubstatusExport($orderedRows, $orderedSubstatuses), $filename);
    }
    
    public function contractLocationReportTagsData(Request $request)
    {

        if(isset($request->contractType)){

            $branch_locations = BranchUser::select(
                'id',
                decrypt_data('LegalName', 'branch')
                )->get(); 
            
            $contracts_query = Contract::select('contract_name', 'id', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date','onetime_end_date','contract_type','contract_tags')
            ->orderBy('id', 'desc')
            ->where('status', 1);

            $contracts = $contracts_query->get();

            $contract_type_array = $this->availableContracts($contracts, true);

            $final_data = [];
            
            $tagWiseData = [];
            
            $locationWiseCount = [];

            foreach($contract_type_array as $con_tags){

                $allKeys = json_decode($con_tags->contract_tags);
                    if(is_array($allKeys)){                
                        if($request->contractType > 0 && in_array($request->contractType, $allKeys)){
                            $allKeys = [$request->contractType];
                        }elseif($request->contractType > 0 && !in_array($request->contractType, $allKeys)){
                            $allKeys = [];
                        }
    
                        foreach($allKeys as $ky){
                          $contractParty = $con_tags->contractParty;
                          foreach ($contractParty as $contractPart) {
                    
                            //Check Branches Accessible for the User
                            if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Internal') {                        
                                if(!isset($locationWiseCount[$contractPart->contract_party_location_id])){
                                    $locationWiseCount[$contractPart->contract_party_location_id] = 1;
                                }else{
                                    $locationWiseCount[$contractPart->contract_party_location_id] = $locationWiseCount[$contractPart->contract_party_location_id] + 1;
                                }
                            }
                          }
                    }
                    }
            }
            

            foreach($branch_locations as $brloc){
                
                $final_data[] = [
                    'locName' => $brloc->LegalName,
                    'locCount' => $locationWiseCount[$brloc->id] ?? 0
                ];
            }
            
 
    
            return response()->json([
                'data' => $final_data,
                'draw' => $request->input('draw') ?? 1,
                'recordsTotal' => count($contract_type_array),
                'recordsFiltered' => count($contract_type_array)
            ]);
        }
    } 

    public function contractLocationReportValueData(Request $request)
    {

        if(isset($request->contractType)){

            $branch_locations = BranchUser::select(
                'id',
                decrypt_data('LegalName', 'branch')
                )->get(); 
            
            $contracts_query = Contract::select('contract_name', 'id', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date','onetime_end_date','contract_type')
            ->orderBy('id', 'desc')
            ->where('status', 1);
            
            if($request->contractType > 0){
                $contracts_query->where('contract_type', $request->contractType);
            }

            $contracts = $contracts_query->get();

            $contract_type_array = $this->availableContracts($contracts, true);

            $final_data = [];
            
            $tagWiseData = [];
            
            $locationWiseCount = [];

            foreach($contract_type_array as $con_tags){
 
                  $contractParty = $con_tags->contractParty;
                  foreach ($contractParty as $contractPart) {
            
                    //Check Branches Accessible for the User
                    if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Internal') {                        
                        if(!isset($locationWiseCount[$contractPart->contract_party_location_id])){
                            $locationWiseCount[$contractPart->contract_party_location_id] = $con_tags->currency_value;
                        }else{
                            $locationWiseCount[$contractPart->contract_party_location_id] = $locationWiseCount[$contractPart->contract_party_location_id] + $con_tags->currency_value;
                        }
                    }
                  }
            }
            

            foreach($branch_locations as $brloc){
                
                $final_data[] = [
                    'locName' => $brloc->LegalName,
                    'locCount' => currency_formatter(env('default_currency'),($locationWiseCount[$brloc->id] ?? 0))
                ];
            }
            
 
    
            return response()->json([
                'data' => $final_data,
                'draw' => $request->input('draw') ?? 1,
                'recordsTotal' => count($contract_type_array),
                'recordsFiltered' => count($contract_type_array)
            ]);
        }
    }
    
    public function contractDeptReports(Request $request, $onlyArray=false)
    {

        $contractDepts = EntityBusiness::select('id','name')->get();
        
        $contracts_query = Contract::select('id','department_id')
        ->orderBy('id', 'desc')
        ->where('status', 1);

        if(isset($request->deptId)){
            $contracts_query->addSelect('contract_name', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date', 'contract_end_date','onetime_end_date','contract_type');
            $contracts_query->where('department_id', $request->deptId);
        }
        
        $contracts = $contracts_query->get();
        

        if(isset($request->deptId)){
            $contract_dept_array = $this->availableContracts($contracts, true);
            
        if($onlyArray){
            return $contract_dept_array;
        }            
            return response()->json([
                'data' => $contract_dept_array,
                'draw' => $request->input('draw') ?? 1,
                'recordsTotal' => count($contract_dept_array),
                'recordsFiltered' => count($contract_dept_array)
            ]);
        }else{
            $contract_dept_array = $this->availableContracts($contracts, true);
			$deptWiseCount = [];
			foreach ($contract_dept_array as $contract) {
				
				$currencyVal = is_numeric($contract->currency_value) ? $contract->currency_value : 0;
				if(!isset($deptWiseCount[$contract->department_id])){
					$deptWiseCount[$contract->department_id]['value'] = $currencyVal;
					$deptWiseCount[$contract->department_id]['count'] = 1;
				}else{
					$deptWiseCount[$contract->department_id]['value'] = $deptWiseCount[$contract->department_id]['value'] + $currencyVal;
					$deptWiseCount[$contract->department_id]['count']++;
				}
			}
			
        }
        
		$currency_value_finder_arr = array_column($deptWiseCount, 'value');
		
		$currency_count_finder_arr = array_column($deptWiseCount, 'count');
		
        $allcount = array_sum($currency_count_finder_arr);
		
		$allValues = array_sum($currency_value_finder_arr);

        return view('contract::reports.viewReportsContDept')
        ->with('contractDepts', $contractDepts)
        ->with('contDeptCountArr', $deptWiseCount)
		->with('allValues', $allValues)
        ->with('allContracts', $allcount);
    }    

    public function contractDetailReports(Request $request, $onlyArray=false)
    {

        $contractDepts = EntityBusiness::select('id','name')->get();
        
        $contracts_query = Contract::select('id','department_id')
        ->orderBy('id', 'desc')
        //->where('contract_status','executed')
        //->whereIn('substatus',['active','expired'])        
        ->where('status', 1);
        
        if($request->nodeid){
            
            $nodeId = $request->nodeid;
            
            if (strpos($nodeId, 'dept_') === 0) {
                $departmentId = str_replace('dept_', '', $nodeId);
                $request->merge(['deptId' => $departmentId]);
            }
            
            if (strpos($nodeId, 'type_') === 0) {
                $parts = explode('_', $nodeId);
                $departmentId = $parts[1];
                $typeId = $parts[2];                
                $request->merge([
                    'deptId' => $departmentId,
                    'conTyp' => $typeId
                ]);
            }

            if (strpos($nodeId, 'loc_') === 0) {
                $parts = explode('_', $nodeId);
                $departmentId = $parts[1];
                $typeId = $parts[2];                
                $location = $parts[3];                
                $request->merge([
                    'deptId' => $departmentId,
                    'conTyp' => $typeId,
                    'locId' => $location
                ]);
            }

        }

        if(isset($request->deptId)){
            $contracts_query->addSelect('contract_name', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date', 'contract_end_date','onetime_end_date','contract_type');
            $contracts_query->where('department_id', $request->deptId);
            if(isset($request->conTyp)){
                $contracts_query->where('contract_type', $request->conTyp);
            }
        }
        
        $contracts = $contracts_query->get();
        

        if(isset($request->deptId)){
            $final_contract_array = [];
            $contract_dept_array = $this->availableContracts($contracts, true);

            foreach($contract_dept_array as $keyCon => $contract){
                $contract->currency_value_converted = "-";
                if($contract->currency_value > 0){
                    $contract->currency_value_converted = currency_formatter(env('default_currency'),$contract->currency_value);
                    $applicable = false;
                    if(isset($request->locId)){
                          $contractParty = $contract->contractParty;
                          foreach ($contractParty as $contractPart) {
                        
                    
                            //Check Branches Accessible for the User
                            if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Internal' && $contractPart->contract_party_location_id == $request->locId) {                        
                                $applicable = true;
                            }
                          }                        
                    }else{
                       $applicable = true; 
                    }
                    if($applicable){
                        $final_contract_array[] = $contract;
                    }
                }
            }
            $currency_value_finder_arr = array_column($final_contract_array, 'currency_value');

            array_multisort($currency_value_finder_arr, SORT_DESC, $final_contract_array);
            
            $final_contract_array = array_slice($final_contract_array, 0, 5);

            if($onlyArray){
                return $final_contract_array;
            }
            
            return response()->json([
                'data' => $final_contract_array,
                'draw' => $request->input('draw') ?? 1,
                'recordsTotal' => count($final_contract_array),
                'recordsFiltered' => count($final_contract_array)
            ]);
        }else{
            $contract_dept_array = $this->availableContracts($contracts, false, "department_id");
        }
        
        $allcount = array_sum($contract_dept_array);

        return view('contract::reports.viewReportsContDept')
        ->with('contractDepts', $contractDepts)
        ->with('contDeptCountArr', $contract_dept_array)
        ->with('allContracts', $allcount);
    }    
    
    public function listContractException(Request $request, $onlyArray=false)
    {
        
        $contract_gap_count              = 0;
        $contract_sub_count              = 0;
        $contract_delay_count            = 0;
        $contract_all_total              = 0;
        
        $contracts_query = Contract::select('contract_name', 'id', 'contract_unique_id','currency', 'currency_value', 'end_contract_type', 
                            'contract_status','substatus','signing_date','fixed_date','contract_end_date','onetime_end_date','fixedterm_end_date','contract_type','catgoery_id','parentcontract');
        $contracts_query->whereNotNull('signing_date');
        $contracts_query->orderBy('id', 'desc');
        $contracts_query->where('status', 1);
        $contracts = $contracts_query->get();

        $ExceptionReport['contract_gap'] = [];
        $ExceptionReport['subseq_sign_delay'] = [];
        $ExceptionReport['sign_delay'] = [];

        $contracts = $this->availableContracts($contracts, true); 
        
        foreach ($contracts as $contract) {
            
            //Current Contract Data
            $currContStDate = strtotime($contract->fixed_date); 
            $currContSgDate = strtotime($contract->signing_date); 
            $parentContract = ['id'=>false];
            $checkParent = true;
            if(isset($request->exceptType) && $request->exceptType == 'del_count'){
                $checkParent = false;
            }
            if($contract->parentcontract > 0 && $checkParent){
                $parentContract = Contract::select('contract_name', 'id', 'contract_unique_id','currency', 'currency_value', 'end_contract_type', 
                                    'contract_status','substatus','signing_date','fixed_date','contract_end_date','onetime_end_date','fixedterm_end_date','contract_type','catgoery_id','parentcontract')
                                    ->whereNotNull('signing_date')
                                    ->where('id', $contract->parentcontract)
                                    ->where('status', 1)
                                    ->orderBy('id', 'desc')->first();
                if($parentContract){
                    


                    //Prev Contract Data
                    $prevConEndDate = strtotime($parentContract->contract_end_date); 
                    
                    $dayDiffConGap = round(($currContStDate - $prevConEndDate) / (60 * 60 * 24));
                    
                    $dayDiffConSubDelay = round(($currContSgDate - $prevConEndDate) / (60 * 60 * 24));
                    
                    //$dayDiffConSgDelay = round(($currContSgDate - $currContStDate) / (60 * 60 * 24));
                    
                    if($dayDiffConGap > 1){
                        $ExceptionReport['contract_gap'][] = ["curconid"=> $contract,"oldconid"=>$parentContract, 
                        "exceptdetails" => "Contract Gap ".$dayDiffConGap." Days"];
                        $contract_gap_count++;
                        $contract_all_total++;
                    }

                    if($dayDiffConSubDelay > 1){
                        $ExceptionReport['subseq_sign_delay'][] = ["curconid"=> $contract,"oldconid"=>$parentContract, 
                        "exceptdetails" => "Contract Subsequent Sign Delayed ".$dayDiffConSubDelay." Days"];                            
                        $contract_sub_count++;
                        $contract_all_total++;
                    }

                    // if($dayDiffConSgDelay > 1){
                    //     $ExceptionReport['sign_delay'][] = ["curconid"=> $contract,"oldconid"=>$parentContract, 
                    //     "exceptdetails" => "Contract Sign Delayed ".$dayDiffConSgDelay." Days"];
                    //     $contract_delay_count++;
                    //     $contract_all_total++;
                    // }
                            
                }
            }
            
            $dayDiffConSgDelay = round(($currContSgDate - $currContStDate) / (60 * 60 * 24));
            if($dayDiffConSgDelay > 1){
                $ExceptionReport['sign_delay'][] = ["curconid"=> $contract,"oldconid"=>$parentContract, 
                "exceptdetails" => "Contract Sign Delayed ".$dayDiffConSgDelay." Days"];
                $contract_delay_count++;
                $contract_all_total++;
            }            
        }

        $allcount = $contract_all_total;
        
        $exception_count = array(
            'gap_count' => $contract_gap_count,
            'sub_count' => $contract_sub_count,
            'del_count' => $contract_delay_count,
        );
        
        $exception_count_label = array(
            'gap_count' => 'Contracts Gap',
            'sub_count' => 'Subsequent Signed Delay',
            'del_count' => 'Signed Delay',
        ); 
        
        $exception_count_color = array(
            'gap_count' => 'primary',
            'sub_count' => 'warning',
            'del_count' => 'success'
        );     
        
        $contracts = [];
        if(isset($request->exceptType)){
            $exception_key = array(
                'gap_count' => 'contract_gap',
                'sub_count' => 'subseq_sign_delay',
                'del_count' => 'sign_delay',
            );             
            $contracts = $ExceptionReport[$exception_key[$request->exceptType]];
            if($onlyArray){
                return $contracts;
            }
            return response()->json([
                'data' => $contracts,
                'draw' => $request->input('draw') ?? 1,
                'recordsTotal' => count($contracts),
                'recordsFiltered' => count($contracts)
            ]);
        }

        return view('contract::reports.viewExceptionReport')
        ->with('exception_count', $exception_count)
        ->with('exception_count_label', $exception_count_label)
        ->with('exception_count_color', $exception_count_color)
        ->with('allContracts', $allcount);
    }

    public function contractClauseReports()
    {
        //$contractTypes = ContractType::select('contract_type_id','contract_type')->get();
        $contractClauses = ClausesCategory::where('category_group', 'title')->get();
        //$contractClauseLink = ClausesContractsLink::where('link_type', 'contracts')->get();

        $contracts = Contract::select('contract_name', 'id', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','fixed_date','contract_end_date','onetime_end_date','contract_type','contract_tags')
        ->orderBy('id', 'desc')
        ->where('status', 1)
        ->get();

        $contract_type_array = $this->availableContracts($contracts, true);
        
        
        foreach($contract_type_array as $keyCon => $con_s){
            
            $allLinks = $con_s->contractClauseLink->pluck('clause_category')->toArray();

            $con_s->linkedClauses = $allLinks;
        }

        $allcount = 0;
            
        return view('contract::reports.viewReportsClause')
        ->with('contractClauses', $contractClauses)
        ->with('contDatas', $contract_type_array)
        ->with('allContracts', $allcount);
    } 

    public function contractPartyTypeReports(Request $request)
    {

        $contractDepts = EntityBusiness::select('id','name')->get();
        
        $contracts_query = Contract::select('id','department_id')
        ->orderBy('id', 'desc')
        ->where('status', 1);

        if(isset($request->deptId)){
            $contracts_query->addSelect('contract_name', 'currency', 'currency_value','end_contract_type', 'contract_status','substatus','contract_end_date','fixed_date','onetime_end_date','contract_type');
            $contracts_query->where('department_id', $request->deptId);
        }
        
        $contracts = $contracts_query->get();
        

        if(isset($request->deptId)){
            $contract_dept_array = $this->availableContracts($contracts, true);
            
            return response()->json([
                'data' => $contract_dept_array,
                'draw' => $request->input('draw') ?? 1,
                'recordsTotal' => count($contract_dept_array),
                'recordsFiltered' => count($contract_dept_array)
            ]);
        }else{
            $contract_dept_array = $this->availableContracts($contracts, false, "department_id");
        }
        
        $allcount = array_sum($contract_dept_array);

        return view('contract::reports.viewReportsPartyType')
        ->with('contractDepts', $contractDepts)
        ->with('contDeptCountArr', $contract_dept_array)
        ->with('allContracts', $allcount);
    }
    
    public function exportData(Request $request){
        
        $request_ = new Request();
        $deptParamas = json_decode($request->exportParams, true) ?? [];
        $resp = [];
        $request_->replace($deptParamas);
        $viewFile = "pdfData";
        switch($request->exportUrl){
            case 'reports-contract-detail-data':
                $resp = $this->contractDetailReports($request_, true);
                break;
            case 'reports-status-data':
                $resp = $this->statusReportsData($request_, true);
                break;
            case 'reports-expired-data':
                $resp = $this->statusReportsExpired($request_, true);
                break;
            case 'reports-contract-depts-data':
                $resp = $this->contractDeptReports($request_, true);
                break;
            case 'reports-exceptions-data':
                $viewFile = "pdfExceptionData";
                $resp = $this->listContractException($request_, true);
                break;
        }
        // echo  'print_oprtaions<pre>';
        // print_r($resp);
        // die; 
        $data = ['tableData' => $resp, 'imagesData' => json_decode($request->imgs)];
        //return view('contract::reports.pdfData')->with('tableData',$resp)->with('imagesData',json_decode($request->imgs));
        $pdf = PDF::loadView("contract::reports.$viewFile", $data);
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('document_'.strtotime(date('y-m-d h:i:s')).'.pdf');      
        die;
    
    }
    
    private function jstreeHtmlText($name, $countWithValue, $countZero, $totalValue, $dispClass="secondary", $count=0){
        $finalHtml = "<span class='badge bg-danger'>{$count}</span> <span class='badge bg-info'>{$countWithValue}</span> <span class='badge bg-warning'>{$countZero}</span> <span class='btn btn-sm btn-$dispClass bg-glow position-absolute end-0 me-1 toggle-spans toggle_val'>" . currency_formatter(env('default_currency'), $totalValue) . "</span>";
        if($count === 0 && $totalValue == 0){
            $finalHtml = '';
        }
        
        $initialText = "{$name} $finalHtml";
        return $initialText;
    }
}