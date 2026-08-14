<?php

namespace Modules\ApprovalRules\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\User;
use App\Models\ContractParties;
use App\Models\ContractPartiesRepresentative;
use App\Models\ContractPartiesLabel;
use App\Models\Country;
use App\Models\State;
use App\Models\Branch;
use App\Models\BranchUser;
use App\Models\ContractCategories;
use App\Models\AddUsers;
use App\Models\ContractType;
use App\Models\CustomFields;
use App\Models\CustomFieldsData;
use App\Models\EntityBusiness;
use App\Models\FinancialLimit;
use App\Models\PartyApprovalRules;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;


class ApprovalRulesController extends Controller
{


    public function __construct()
    {
      if(Controller::checkCurrentAuth("Contracts") != 1){
        return abort('404');
      }
    }

    public function getState(Request $request)
    {
        try {
            $states = [];
            if ($request->Countryid) {
                $Countryid = $request->Countryid;
                $states = State::select("name", "id")
                    ->where('Countryid', $Countryid)
                    ->get();
                return $states;
            } else {
                return false;
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }
    /**
     * @date:: 28 May 2024,
     * @author :: Mangaleswari,
     * @desc:: financial List function
     **/
    public function financial(Request $request)
    {
        try {
            $total_count =  ContractParties::select('id')->get()->count();
            $contract_parties = ContractParties::select('contract_parties.id', 'contract_parties.company_name', 'contract_parties.party_type', 'contract_parties.city', 'contract_parties.company_contact', 'contract_parties.company_email', 'legal_entity', 'role_in_contract', 'engagement_level', 'contract_parties.status', 'contract_parties.created_by', 'users.name as user_name')
                ->leftJoin('users', 'users.id', '=', 'contract_parties.created_by')
                ->orderby('contract_parties.id', 'desc')->paginate(10);
            return view('contract-setup::financial.index', compact('total_count', 'contract_parties'))
                ->with('i', (request()->input('page', 1) - 1) * 10);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }

    /**
     * @date:: 28 May 2024,
     * @author :: Mangaleswari,
     * @desc:: financial Add function
     **/

    
    public function financial_data(Request $request)
    {
        
        try
        {
            // 
            $exactEncryptObj = decrypt_datas('BranchName',"branch");
            
            $a=json_encode((array)$exactEncryptObj);
            $b=(array)json_decode(str_replace('\u0000*\u0000','',$a));
            $exactEncrypt = $b['value'];
            
            $financial = FinancialLimit::selectRaw('financial_limit.approval_name as approvalName, financial_limit.id,IFNULL('.$exactEncrypt.', "Any / All") as BranchName,IFNULL(entitybusiness.name, "Any / All") as department,IFNULL(contract_categories.name, "Any / All") as contract_categories_name,IFNULL(contract_type.contract_type, "Any / All") as contract_type,financial_limit.lower_limit,financial_limit.upper_limit,financial_limit.approval_type,financial_limit.approval_required_users')
            ->leftJoin('branch', 'branch.id', '=', 'financial_limit.location')
            ->leftJoin('entitybusiness', 'entitybusiness.id', '=', 'financial_limit.department')
            ->leftJoin('contract_categories', 'contract_categories.id', '=', 'financial_limit.category')
            ->leftJoin('contract_type', 'contract_type.contract_type_id', '=', 'financial_limit.contract_type')
            ->orderby('financial_limit.id','desc')
            ->get()->toArray();
            
            return response()->json([
                'data' => $financial,
                'draw' => $request->input('draw') ?? 1,
                'recordsTotal' => count($financial),
                'recordsFiltered' => count($financial),
            ]);
            return response()->json($financial, 200);
        }catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();      
            return $message;
         }
    }
    
    
    public function party_approval_data(Request $request)
    {
        
        try
        {
            // 
            $exactEncryptObj = decrypt_datas('BranchName',"branch");
            
            $a=json_encode((array)$exactEncryptObj);
            $b=(array)json_decode(str_replace('\u0000*\u0000','',$a));
            $exactEncrypt = $b['value'];
            
            $financial = PartyApprovalRules::selectRaw('party_approval_rules.id,IFNULL('.$exactEncrypt.', "Any / All") as BranchName,GeographicalHierarchy.name as geoname, party_approval_rules.approval_required_users')
            ->leftJoin('branch', 'branch.id', '=', 'party_approval_rules.branch')
            ->leftJoin('GeographicalHierarchy', 'GeographicalHierarchy.id', '=', 'party_approval_rules.accesslevel')
            ->orderby('party_approval_rules.id','desc')
            ->get()->toArray();
            
            return response()->json([
                'data' => $financial,
                'draw' => $request->input('draw') ?? 1,
                'recordsTotal' => count($financial),
                'recordsFiltered' => count($financial),
            ]);
            return response()->json($financial, 200);
        }catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();      
            return $message;
         }
    }
    /**
     * @date:: 28 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: financial List function
    **/
    public function financial_old(Request $request)
    {
        $total_count =  FinancialLimit::select('id')->get()->count();
            $financial = FinancialLimit::select('financial_limit.id','financial_limit.lower_limit','financial_limit.upper_limit','financial_limit.approval_type',decrypt_data('BranchName','branch'),'entitybusiness.name as department','contract_categories.name as contract_categories_name','contract_type.contract_type','financial_limit.approval_required_users')
            ->leftJoin('branch', 'branch.id', '=', 'financial_limit.location')
            ->leftJoin('entitybusiness', 'entitybusiness.id', '=', 'financial_limit.department')
            ->leftJoin('contract_categories', 'contract_categories.id', '=', 'financial_limit.category')
            ->leftJoin('contract_type', 'contract_type.contract_type_id', '=', 'financial_limit.contract_type')
            ->orderby('financial_limit.location','asc')
            ->orderby('financial_limit.department','asc')
            ->orderby('financial_limit.category','asc')
            ->paginate(10);
            return view('contract-setup::content.financial.index_old',compact('total_count','financial'))
                    ->with('i', (request()->input('page', 1) - 1) * 10);
    }
    /**
     * @date:: 28 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: financial Add function
    **/
    public function financial_add(Request $request)
    {
        
        try
        {
            if ($request->isMethod('post')) {
               //return $request->post();
                $messages = [
                    "required" => 'Please Fill Mandatory Fields :attribute',
                    "upper_limit.gt" => 'Upper Limit must be greater than Lower Limit',
                    "lower_limit.lt" => 'Lower Limit must be lower than Upper Limit'
                ];
               $validator =  Validator::make($request->all(),[
                    'location.*' => 'required',
                    'department.*' => 'required',
                    'category.*' => 'required',
                    'contract_type.*' => 'required',
                    'approval_name' => 'required',
                    'upper_limit' => 'nullable|gt:lower_limit',
                    'lower_limit' => 'nullable|lt:upper_limit',
                ], $messages);
                
                if($validator->fails()) {
                    
                    $errors = $validator->errors();
                    return redirect('/contract-setup/financial-add')->withErrors($validator)->withInput();
                }
                
                
                //$queryLocConcat = "";

                // $firstLocation = array_key_first($request->location);
                // $lastLocation = array_key_last($request->location);                
                // foreach($request->location as $key => $location){
                //     if($firstLocation == $key){
                //         $queryLocConcat .= "(";
                //     }                
                //     $queryLocConcat .= "FIND_IN_SET('$location', location)";
                //     if($lastLocation > $key && count($request->location) > 1 && ($key+1) < count($request->location)){
                //         $queryLocConcat .= " OR ";
                //     }
                    
                //     if($lastLocation == $key){
                //         $queryLocConcat .= ")";
                //     }                    
                    
                // }
                
                
                // $queryDepConcat = "";
                // $firstDepartment = array_key_first($request->department);
                // $lastDepartment = array_key_last($request->department);                
                // foreach($request->department as $key => $department){

                //     if($firstDepartment == $key){
                //         $queryDepConcat .= "(";
                //     }                
                //     $queryDepConcat .= "FIND_IN_SET('$department', department)";
                //     if($lastDepartment > $key && count($request->department) > 1 && ($key+1) < count($request->department)){
                //         $queryDepConcat .= " OR ";
                //     }
                    
                //     if($lastDepartment == $key){
                //         $queryDepConcat .= ")";
                //     }                     
                // }
                
                // if($queryLocConcat != "" && $queryDepConcat != ""){
                //     $queryLocConcat .= " and ";
                // }

                // $queryCatConcat = "";
                // $firstCategory = array_key_first($request->category);
                // $lastCategory = array_key_last($request->category);                 
                // foreach($request->category as $key => $category){
                    
                //     if($firstCategory == $key){
                //         $queryCatConcat .= "(";
                //     } 
                    
                //     $queryCatConcat .= "FIND_IN_SET('$category', category)";
                //     if($lastCategory > $key && count($request->category) > 1 && ($key+1) < count($request->category)){
                //         $queryCatConcat .= " OR ";
                //     }
                    
                    
                //     if($lastCategory == $key){
                //         $queryCatConcat .= ")";
                //     }                     
                                                                                                                                                                                                    
                // }
                
                // if($queryDepConcat != "" && $queryCatConcat != ""){
                //     $queryDepConcat .= " and ";
                // }                
                
                // $queryConConcat = "";
                // $firstCtype = array_key_first($request->contract_type);
                // $lastCtype = array_key_last($request->contract_type);                  
                // foreach($request->contract_type as $key => $contract_type){
                    
                //     if($firstCtype == $key){
                //         $queryConConcat .= "(";
                //     }                
                //     $queryConConcat .= "FIND_IN_SET('$contract_type', contract_type)";
                //     if($lastLocation > $key && count($request->contract_type) > 1 && ($key+1) < count($request->contract_type)){
                //         $queryConConcat .= " OR ";
                //     }
                    
                //     if($lastCtype == $key){
                //         $queryConConcat .= ")";
                //     }                     
                    
                // }
                
                // if($queryCatConcat != "" && $queryConConcat != ""){
                //     $queryCatConcat .= " and ";
                // }
                
                
                // echo $queryLocConcat.$queryDepConcat.$queryCatConcat.$queryConConcat;
                // die;

                // handle rule_builder_data duplicates and storage
                $canonical = $this->canonicalizeRuleData($request->rule_builder_data);
                
                if(!empty($request->rule_builder_data)){
                    // check duplicates
                    $existing = FinancialLimit::whereNotNull('rule_builder_data')->get();
                    foreach($existing as $ex){
                        $exCanon = $this->canonicalizeRuleData($ex->rule_builder_data);
                        if($exCanon == $canonical){
                            $exist_error = array('A rule with identical conditions already exists. Modify the rule or choose a different set.');
                            return redirect('/contract-setup/financial-add')->withErrors($exist_error)->withInput();
                        }
                    }
                }                

                // $check_financial = FinancialLimit::select('id')
                //             //->whereRaw("$queryLocConcat$queryDepConcat$queryCatConcat$queryConConcat")
                //             ->where('upper_limit',$request->upper_limit)
                //             ->where('lower_limit',$request->lower_limit)
                //             ->get();
                            
                // if(count($check_financial) > 0)
                // {
                //     $exist_error = array('This Location,Department,Category,Contract Type and Financial Limits Combination already exist. Try Other/Modify the values');
                //     return redirect('/contract-setup/financial-add')->withErrors($exist_error)->withInput();
                // }
                
                // if($request->lower_limit > 0){
                //     $check_limit = FinancialLimit::select('id')
                //                 ->whereRaw("$queryLocConcat$queryDepConcat$queryCatConcat$queryConConcat")
                //                 ->where('upper_limit','>=',$request->lower_limit)
                //                 ->get();    
                //     if(count($check_limit) > 0)
                //     {
                //         $exist_error = array('Lower and Upper Limit Already Taken Try Other/Modify the values');
                //         return redirect('/contract-setup/financial-add')->withErrors($exist_error)->withInput();                    
                //     }
                // }
                
                
                //echo "<pre>";
                $financial = new FinancialLimit();
                $financial->approval_name = $request->approval_name;
                $financial->location = implode(',',$request->location ?? []);
                $financial->department = implode(',',$request->department ?? []);
                $financial->category = implode(',',$request->category ?? []);
                $financial->contract_type = implode(',',$request->contract_type ?? []);
                $financial->lower_limit = $request->lower_limit;
                $financial->upper_limit = $request->upper_limit;
                $financial->approver = $request->approver;
                $financial->sameAsAll = $request->sameAsNewApproval ? 1 : 0;
                $financial->rule_builder_data = $canonical;

                $approval_status = [];
                $approval_type = [];
                $approval_signatory = [];
                $approval_signatoryUtForm = [];
                $approval_owner = [];
                $approval_userNoti = [];
                
                //File Storage
                $storageController = fileStorageTypeController();
                
                $config_approval_types = config('app.APPROVAL_TYPES', []);
                
                // Ensure we always have at least the default approval type
                if (empty($config_approval_types)) {
                    $config_approval_types = [''];
                }
                
                foreach($config_approval_types as $con_app_type){
                    $appTypeArr = $con_app_type != '' ? $con_app_type : 0;
                    $con_app_type = $con_app_type != '' ? '_'.$con_app_type : $con_app_type;
                    $approval_users = [];
                    $app_type = "approval_required_user_type".$con_app_type;
                    $app_type_users = "approval_required_users".$con_app_type;
                    $app_type_desg = "approval_required_desg".$con_app_type;
                    
                    // $request->$app_type = $request->approval_required_user_type;
                    // $request->$app_type_users = $request->approval_required_users;
                    // $request->$app_type_desg = $request->approval_required_desg;
                    // Config may define more approval types than the form renders
                    // (e.g. a newly added 'ext2'); skip types absent from the submission.
                    if (!isset($request->approval_status[$appTypeArr])) {
                        continue;
                    }
                    $apprvl_status = $request->approval_status[$appTypeArr];
                    $apprvl_type = $request->approval_type[$appTypeArr] ?? '';
                    $apprvl_signat = $request->signatory_user[$appTypeArr] ?? '';
                    $filePath = null;
                    if($request->file('signatory_user_utaking.'.$appTypeArr)){
                        $utfFile = $request->file('signatory_user_utaking.'.$appTypeArr);
                        $utfFileName = file_name($utfFile);
                        $filePath = $storageController->storeFile($utfFile, 0, 0, $utfFileName);
                        //echo $filePath;
                    }
                    //die;
                    $apprvl_signat_utform = $filePath;
                    $apprvl_owner = $request->owner_user[$appTypeArr] ?? '';
                    $apprvl_userNoti = $request->user_noti[$appTypeArr] ?? [];
                    
                    $approval_status[$appTypeArr] = $apprvl_status;
                    $approval_type[$appTypeArr] = $apprvl_type;
                    $approval_signatory[$appTypeArr] = $apprvl_signat;
                    $approval_signatoryUtForm[$appTypeArr] = $apprvl_signat_utform;
                    $approval_owner[$appTypeArr] = $apprvl_owner;
                    $approval_userNoti[$appTypeArr] = $apprvl_userNoti;
                    $all_groups = [];
                    if($apprvl_status == 'required')
                    {                    
                        $app_type_role = "approval_required_role".$con_app_type;
                        // foreach ($request->$app_type as $ky => $value)
                        // {
                        //         $type_approver = $value;
                        //         $role = $request->$app_type_role[$ky] ?? 'Approver';
                        //         $name = "";
                        //         if($value == 'name' && isset($request->$app_type_users[$ky])){
                        //             $parts = explode(':', $request->$app_type_users[$ky]);
                        //             $id = $parts[0];
                        //             $name = $parts[1];
                        //             $email = $parts[2];
                        //         }else{
                        //             if(isset($request->$app_type_users[$ky])){
                        //                 $id = 0;
                        //                 $name = $request->$app_type_desg[$ky];
                        //                 $email = 0;
                        //             }
                        //         }
                        //         if($name != ""){
                        //             $approval_users[] = [
                        //                 'id' => $id,
                        //                 'type' => $type_approver,
                        //                 'name' => $name,
                        //                 'email' => $email,
                        //                 'role' => $role
                        //             ];
                        //         }
                        // }
                        
                        if(count($approval_users) == 0){
                            $approval_users[] = [
                                'id' => 0,
                                'type' => 'name',
                                'name' => '',
                                'email' => '',
                                'role' => 'Approver'
                            ];                                    
                        }
                        // collect approval_groups field if provided for this app type
                        $app_groups_field = 'approval_groups'.$con_app_type;
                        
                        if(isset($request->$app_groups_field) && !empty($request->$app_groups_field)){
                            $all_groups[$appTypeArr] = $this->normalizeApprovalGroups($request->$app_groups_field, $apprvl_type);
                        } else {
                            // if groups not provided, build a default single group from approval_users
                            $all_groups[$appTypeArr] = $this->normalizeApprovalGroups([[
                                'role' => 'Approver',
                                'approval_type' => $apprvl_type,
                                'dynamic_approver_enabled' => 0,
                                'approvers' => $approval_users
                            ]], $apprvl_type);
                        }
                        $approval_users = $all_groups[$appTypeArr];
                    }
                    $financial->$app_type_users = json_encode($approval_users);
                }
                $financial->approval_type = json_encode($approval_type);
                $financial->approval_status = json_encode($approval_status);
                
                $financial->approval_signatory_owner = json_encode(['sign' => $approval_signatory, 'signutform' => $approval_signatoryUtForm, 'owner'=> $approval_owner, 'notify'=>$approval_userNoti]);

                // Set default values for approval columns that may not be covered by the config loop
                $allApprovalColumns = [
                    'approval_required_users_renewed',
                    'approval_required_users_addendum',
                    'approval_required_users_legacy',
                    'approval_required_users_edit',
                    'approval_required_users_legacy_edit',
                    'approval_required_users_terminate',
                ];
                
                foreach ($allApprovalColumns as $col) {
                    if (!isset($financial->$col) || $financial->$col === null) {
                        $financial->$col = json_encode([]);
                    }
                }

                $financial->status = 1;
                $financial->save();
                
                foreach($config_approval_types as $con_app_type){
                    $appTypeArr = $con_app_type != '' ? $con_app_type : 0;
                    $con_app_type_key = $con_app_type != '' ? '_'.$con_app_type : $con_app_type;
                    $app_groups_field = 'approval_groups'.$con_app_type_key;
                    
                    if(isset($request->$app_groups_field) && !empty($request->$app_groups_field)){
                        $groupsData = $this->normalizeApprovalGroups($request->$app_groups_field, $request->approval_type[$appTypeArr] ?? 'sequential');
                        $this->saveApprovalGroupsToTable($financial->id, $con_app_type, $groupsData);
                        
                        if ($con_app_type == '' && $financial->approval_group_set_id == null) {
                            $defaultSet = \App\Models\ApprovalGroupSet::where('financial_limit_id', $financial->id)
                                ->where('approval_type', '')
                                ->first();
                            if ($defaultSet) {
                                $financial->approval_group_set_id = $defaultSet->id;
                                $financial->save();
                            }
                        }
                    }
                }
                
                return redirect('/contract-setup/approval-rules')->with('success','Financial has been Created Successfully.');
            }else
            {
             $contract_type = ContractType::select('contract_type_id','contract_type')->get();
             $contract_categories = ContractCategories::select('id','name')->get();
             $branch = Branch::select("id",decrypt_data('BranchName','branch'))->get();
             $entity_business = EntityBusiness::select('id','name')->where('entityid',1)->get();
             $customFields = CustomFields::where('status', 1)->where('contract_type', '>' , 0)->orderBy('order_id')->get();
             $add_users = AddUsers::select("id",
            decrypt_data('Salutation', 'AddUsers'), 
            decrypt_data('FirstName', 'AddUsers') , 
            decrypt_data('LastName', 'AddUsers'),
            decrypt_data('Designation', 'AddUsers'),
            decrypt_data('Email', 'AddUsers')
            )->get();
             //print_r($add_users);exit;
             return view('contract-setup::financial.create',compact('contract_type','branch','contract_categories','add_users','entity_business','customFields'));
            }
        }catch (Exception $e) {
            $message = $e->getMessage().$e->getLine();
            $code = $e->getCode();      
            echo $message;
            die;
            return redirect('/contract-setup/financial-add')->withErrors([$message, $code])->withInput(); 
        }
    }
    /**
     * @date:: 30 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: check limit function
    **/
    public function check_limit(Request $request)
    {
        try
        {
            if ($request->isMethod('post')) {
               //return $request->post();
               $validator =  Validator::make($request->all(),[
                    'location' => 'required',
                    'department' => 'required',
                    'category' => 'required',
                    'contract_type' => 'required',
                    'lower_limit' => 'required',
                    'upper_limit' => 'required'
                ]);
                if($validator->fails()) {
                    $errors = $validator->errors();
                    return redirect('contract-setup::financial-add')->withErrors($validator)->withInput();
                }
                $lower_limit =(int)$request->lower_limit;
                $upper_limit =(int)$request->upper_limit;
                if(isset($request->financial_id))
                {
                    $check_limit = FinancialLimit::select('id')
                                ->where('location',$request->location)
                                ->where('department',$request->department)
                                ->where('category',$request->category)
                                ->where('contract_type',$request->contract_type)
                                //->where([['lower_limit','<=',$lower_limit],['upper_limit','>=',$upper_limit]])
                                //->whereRaw('(lower_limit <= '.$lower_limit.' and upper_limit >= '.$upper_limit.') or (lower_limit >= '.$lower_limit.' or upper_limit >= '.$upper_limit.' )')
                                ->where('upper_limit','>=',$lower_limit)
                                ->where('id','!=',$request->financial_id)
                                ->get();
                }else
                {
                    $check_limit = FinancialLimit::select('id')
                                    ->where('location',$request->location)
                                    ->where('department',$request->department)
                                    ->where('category',$request->category)
                                    ->where('contract_type',$request->contract_type)
                                    // ->where([['lower_limit','<=',$lower_limit],['upper_limit','>=',$upper_limit]])
                                    // ->orwhere('lower_limit','>=',$lower_limit)
                                    ->where('upper_limit','>=',$lower_limit)
                                    ->get();
                }
                    //print_r(count($check_limit));exit;
                if(count($check_limit) > 0)
                {
                     return response()->json(['status' => false,'message' => 'lower_limit and upper_limit value is inbetween there'], 200);
                }else
                {
                    return response()->json(['status' => true,'message' => 'value not there'], 200);
                }
            }
        }catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();      
             return response()->json(['status' => false,'message' => $message], 500);
         }
    }
    /**
     * @date:: 30 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: Financial delete function
    **/
    public function financial_delete(Request $request)
    {
        if($request->id != 1){
            try
            {
                $financial = FinancialLimit::find($request->id);
                //print_r($request->id);exit;
                $financial->delete();
                return redirect('/contract-setup/approval-rules')->with('success','Financial deleted Successfully.');
            }catch (Exception $e) {
                $message = $e->getMessage();
                $code = $e->getCode();      
                return $message;
            }
        }else{
            
        }
    }
    /**
     * @date:: 30 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: Financial edit function
    **/
    public function financial_edit(Request $request)
    {
        
        //print_r($request->rule_builder_data);
        //die;
        try
        {
            if ($request->isMethod('post')) {
                //return $request->post();
               $validator =  Validator::make($request->all(),[
                    // 'location' => 'required',
                    // 'department' => 'required',
                    // 'category' => 'required',
                    // 'contract_type' => 'required'
                ]);
                if($validator->fails()) {
                    $errors = $validator->errors();
                    return redirect('/contract-setup/financial-edit/'.$request->financial_id)->withErrors($validator)->withInput();
                }
                
                // $queryLocConcat = "";

                // $firstLocation = array_key_first($request->location);
                // $lastLocation = array_key_last($request->location);                
                // foreach($request->location as $key => $location){
                //     if($firstLocation == $key){
                //         $queryLocConcat .= "(";
                //     }                
                //     $queryLocConcat .= "FIND_IN_SET('$location', location)";
                //     if($lastLocation > $key && count($request->location) > 1 && ($key+1) < count($request->location)){
                //         $queryLocConcat .= " OR ";
                //     }
                    
                //     if($lastLocation == $key){
                //         $queryLocConcat .= ")";
                //     }                    
                    
                // }
                
                
                // $queryDepConcat = "";
                // $firstDepartment = array_key_first($request->department);
                // $lastDepartment = array_key_last($request->department);                
                // foreach($request->department as $key => $department){

                //     if($firstDepartment == $key){
                //         $queryDepConcat .= "(";
                //     }                
                //     $queryDepConcat .= "FIND_IN_SET('$department', department)";
                //     if($lastDepartment > $key && count($request->department) > 1 && ($key+1) < count($request->department)){
                //         $queryDepConcat .= " OR ";
                //     }
                    
                //     if($lastDepartment == $key){
                //         $queryDepConcat .= ")";
                //     }                     
                // }
                
                // if($queryLocConcat != "" && $queryDepConcat != ""){
                //     $queryLocConcat .= " and ";
                // }

                // $queryCatConcat = "";
                // $firstCategory = array_key_first($request->category);
                // $lastCategory = array_key_last($request->category);                 
                // foreach($request->category as $key => $category){
                    
                //     if($firstCategory == $key){
                //         $queryCatConcat .= "(";
                //     } 
                    
                //     $queryCatConcat .= "FIND_IN_SET('$category', category)";
                //     if($lastCategory > $key && count($request->category) > 1 && ($key+1) < count($request->category)){
                //         $queryCatConcat .= " OR ";
                //     }
                    
                    
                //     if($lastCategory == $key){
                //         $queryCatConcat .= ")";
                //     }                     
                                                                                                                                                                                                    
                // }
                
                // if($queryDepConcat != "" && $queryCatConcat != ""){
                //     $queryDepConcat .= " and ";
                // }                
                
                // $queryConConcat = "";
                // $firstCtype = array_key_first($request->contract_type);
                // $lastCtype = array_key_last($request->contract_type);                  
                // foreach($request->contract_type as $key => $contract_type){
                    
                //     if($firstCtype == $key){
                //         $queryConConcat .= "(";
                //     }                
                //     $queryConConcat .= "FIND_IN_SET('$contract_type', contract_type)";
                //     if($lastCtype > $key && count($request->contract_type) > 1 && ($key+1) < count($request->contract_type)){
                //         $queryConConcat .= " OR ";
                //     }
                    
                //     if($lastCtype == $key){
                //         $queryConConcat .= ")";
                //     }                     
                    
                // }
                
                // if($queryCatConcat != "" && $queryConConcat != ""){
                //     $queryCatConcat .= " and ";
                // }

                // $check_financial = FinancialLimit::select('id')
                //             ->whereRaw("$queryLocConcat$queryDepConcat$queryCatConcat$queryConConcat")
                //             ->where('upper_limit',$request->upper_limit)
                //             ->where('lower_limit',$request->lower_limit)                            
                //             ->where('id','!=',$request->financial_id)
                //             ->get();
                

                // if(count($check_financial) > 0)
                // {
                //     $exist_error = array('This location,department and category,contract type field value already exist. Try other value');
                //     return redirect('/contract-setup/financial-edit/'.$request->financial_id)->withErrors($exist_error)->withInput();
                // }
                
                // if($request->lower_limit > 0){
                //     $check_limit = FinancialLimit::select('id')
                //                 ->whereRaw("$queryLocConcat$queryDepConcat$queryCatConcat$queryConConcat")
                //                 ->where('upper_limit','>=',$request->lower_limit)
                //                 ->where('id','!=',$request->financial_id)                            
                //                 ->get();
    
                //     if(count($check_limit) > 0)
                //     {
                //         $exist_error = array('Lower and Upper Limit Already Taken Try Other/Modify the values');
                //         return redirect('/contract-setup/financial-edit/'.$request->financial_id)->withErrors($exist_error)->withInput();                    
                //     }
                // }

                $approval_status = [];
                $approval_type = [];
                $approval_signatory = [];
                $approval_signatoryUtForm = [];
                $approval_owner = [];
                $approval_userNoti = [];
                
                $update_request = [];
                
                //File Storage
                $storageController = fileStorageTypeController();                
                
                $config_approval_types = config('app.APPROVAL_TYPES', []);
                
                // Ensure we always have at least the default approval type
                if (empty($config_approval_types)) {
                    $config_approval_types = [''];
                }
                
                foreach($config_approval_types as $con_app_type){
                    $appTypeArr = $con_app_type != '' ? $con_app_type : 0;
                    $con_app_type = $con_app_type != '' ? '_'.$con_app_type : $con_app_type;
                    $approval_users = [];
                    $app_type = "approval_required_user_type".$con_app_type;
                    $app_type_users = "approval_required_users".$con_app_type;
                    $app_type_desg = "approval_required_desg".$con_app_type;
                    
                    // $request->$app_type = $request->approval_required_user_type;
                    // $request->$app_type_users = $request->approval_required_users;
                    // $request->$app_type_desg = $request->approval_required_desg;
                    // Config may define more approval types than the form renders
                    // (e.g. a newly added 'ext2'); skip types absent from the submission.
                    if (!isset($request->approval_status[$appTypeArr])) {
                        continue;
                    }
                    $apprvl_status = $request->approval_status[$appTypeArr];
                    $apprvl_type = $request->approval_type[$appTypeArr] ?? '';
                    $apprvl_signat = $request->signatory_user[$appTypeArr] ?? '';
                    $filePath = null;
                    if($request->file('signatory_user_utaking.'.$appTypeArr)){
                        $utfFile = $request->file('signatory_user_utaking.'.$appTypeArr);
                        $utfFileName = file_name($utfFile);
                        $filePath = $storageController->storeFile($utfFile, 0, 0, $utfFileName);
                        //echo $filePath;
                    }else{
                        $filePath = $request->signatory_user_utaking_old[$appTypeArr] ?? '';
                    }
                    //die;
                    $apprvl_signat_utform = $filePath;
                    $apprvl_owner = $request->owner_user[$appTypeArr] ?? '';
                    $apprvl_userNoti = $request->user_noti[$appTypeArr] ?? [];
                    
                    $approval_status[$appTypeArr] = $apprvl_status;
                    $approval_type[$appTypeArr] = $apprvl_type;
                    $approval_signatory[$appTypeArr] = $apprvl_signat;
                    $approval_signatoryUtForm[$appTypeArr] = $apprvl_signat_utform;
                    $approval_owner[$appTypeArr] = $apprvl_owner;
                    $approval_userNoti[$appTypeArr] = $apprvl_userNoti;
                    $checkAllActionsAppr = 0;
                    if($apprvl_status == 'required')
                    {                    
                        // foreach ($request->$app_type as $ky => $value)
                        // {
                        //         $type_approver = $value;
                        //         $name = "";
                        //         if($value == 'name' && isset($request->$app_type_users[$ky])){
                        //             $parts = explode(':', $request->$app_type_users[$ky]);
                        //             $id = $parts[0];
                        //             $name = $parts[1];
                        //             $email = $parts[2];
                        //         }else{
                        //             if(isset($request->$app_type_users[$ky])){
                        //                 $id = 0;
                        //                 $name = $request->$app_type_desg[$ky];
                        //                 $email = 0;
                        //             }
                        //         }
                        //         if($name != ""){
                        //             $approval_users[] = [
                        //                 'id' => $id,
                        //                 'type' => $type_approver,
                        //                 'name' => $name,
                        //                 'email' => $email
                        //             ];
                        //         }else{
                        //             $checkAllActionsAppr++;
                        //         }
                        // }
                        
                        // if(count($approval_users) == 0){
                        //     $exist_error = array('Please Add Single Approvers For Each Action');
                        //     return redirect('/contract-setup/financial-edit/'.$request->financial_id)->withErrors($exist_error)->withInput();                                   
                        // } 
                        
                        // collect approval_groups field if provided for this app type
                        $app_groups_field = 'approval_groups'.$con_app_type;
                        if(isset($request->$app_groups_field) && !empty($request->$app_groups_field)){
                            $all_groups[$appTypeArr] = $this->normalizeApprovalGroups($request->$app_groups_field, $apprvl_type);
                        } else {
                            // if groups not provided, build a default single group from approval_users
                            $all_groups[$appTypeArr] = $this->normalizeApprovalGroups([[
                                'role' => 'Approver',
                                'approval_type' => $apprvl_type,
                                'dynamic_approver_enabled' => 0,
                                'approvers' => $approval_users
                            ]], $apprvl_type);
                        }
                        
                        $approval_users = $all_groups[$appTypeArr];
                    }
                    $update_request[$app_type_users] = json_encode($approval_users);

                }

                $financial = FinancialLimit::find($request->financial_id);
                
                $update_request['approver'] = $request->approver;
                $update_request['sameAsAll'] =  $request->sameAsNewApproval ? 1 : 0;
                $update_request['approval_type'] =  json_encode($approval_type);
                $update_request['approval_status'] =  json_encode($approval_status);
                $update_request['approval_signatory_owner'] =  json_encode(['sign' => $approval_signatory, 'signutform' => $approval_signatoryUtForm, 'owner'=> $approval_owner, 'notify'=>$approval_userNoti]);
                
                // Set default values for approval columns that may not be covered by the config loop
                $allApprovalColumns = [
                    'approval_required_users_renewed',
                    'approval_required_users_addendum',
                    'approval_required_users_legacy',
                    'approval_required_users_edit',
                    'approval_required_users_legacy_edit',
                    'approval_required_users_terminate',
                ];
                
                foreach ($allApprovalColumns as $col) {
                    if (!isset($update_request[$col])) {
                        $update_request[$col] = json_encode([]);
                    }
                }
                
                if($request->financial_id != 1){
                    $update_request['approval_name'] = $request->approval_name;
                    $update_request['location'] = implode(',',$request->location ?? []);
                    $update_request['department'] = implode(',',$request->department ?? []);
                    $update_request['category'] = implode(',',$request->category ?? []);
                    $update_request['contract_type'] = implode(',',$request->contract_type ?? []);
                    $update_request['lower_limit'] = $request->lower_limit;
                    $update_request['upper_limit'] = $request->upper_limit;
                }
                
                // handle rule_builder_data duplicates and storage on update
                if(isset($request->rule_builder_data) && $request->rule_builder_data !== ''){
                    $canonical = $this->canonicalizeRuleData($request->rule_builder_data);
                    $existing = FinancialLimit::whereNotNull('rule_builder_data')->where('id','!=',$request->financial_id)->get();
                    foreach($existing as $ex){
                        $exCanon = $this->canonicalizeRuleData($ex->rule_builder_data);
                        if($exCanon == $canonical){
                            $exist_error = array('A rule with identical conditions already exists. Modify the rule or choose a different set.');
                            return redirect('/contract-setup/financial-edit/'.$request->financial_id)->withErrors($exist_error)->withInput();
                        }
                    }
                    $update_request['rule_builder_data'] = $canonical;
                }

                $financial->update($update_request);
                
                foreach($config_approval_types as $con_app_type){
                    $appTypeArr = $con_app_type != '' ? $con_app_type : 0;
                    $con_app_type_key = $con_app_type != '' ? '_'.$con_app_type : $con_app_type;
                    $app_groups_field = 'approval_groups'.$con_app_type_key;
                    
                    if(isset($request->$app_groups_field) && !empty($request->$app_groups_field)){
                        $groupsData = $this->normalizeApprovalGroups($request->$app_groups_field, $request->approval_type[$appTypeArr] ?? 'sequential');
                        $this->saveApprovalGroupsToTable($financial->id, $con_app_type, $groupsData);
                        
                        if ($con_app_type == '' && $financial->approval_group_set_id == null) {
                            $defaultSet = \App\Models\ApprovalGroupSet::where('financial_limit_id', $financial->id)
                                ->where('approval_type', '')
                                ->first();
                            if ($defaultSet) {
                                $financial->approval_group_set_id = $defaultSet->id;
                                $financial->save();
                            }
                        }
                    }
                }
                
                return redirect('/contract-setup/approval-rules')->with('success','Financial has been Updated Successfully.');
            }else
            {
                $financial = FinancialLimit::find($request->id);
  
                $contract_type = ContractType::select('contract_type_id','contract_type')->get();
                $contract_categories = ContractCategories::select('id','name')->get();
                $branch = Branch::select("id",decrypt_data('BranchName','branch'))->get();
                $entity_business = EntityBusiness::select('id','name')->where('entityid',1)->get();
                $add_users = AddUsers::select("id",
            decrypt_data('Salutation', 'AddUsers'), 
            decrypt_data('FirstName', 'AddUsers') , 
            decrypt_data('LastName', 'AddUsers'),
            decrypt_data('Designation', 'AddUsers'),
            decrypt_data('Email', 'AddUsers'))->get();

             return view('contract-setup::financial.edit',compact('financial','contract_type','branch','contract_categories','add_users','entity_business'));
            }
        }catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode(); 
            
            return $message."on line ".$e->getLine();
        }
    }
    /**
     * @date:: 24 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: get users function
    **/
    public function getUsers(Request $request)
    {
        try
        {
           $add_users = AddUsers::select("id",
            decrypt_data('Salutation', 'AddUsers'), 
            decrypt_data('FirstName', 'AddUsers') , 
            decrypt_data('LastName', 'AddUsers'),
            decrypt_data('Designation', 'AddUsers'),
            decrypt_data('Email', 'AddUsers'))
            ->get()
            ->map(function ($user) {
                return $this->sanitizeUtf8Data($user->toArray());
            })
            ->values();

           $jsonFlags = JSON_UNESCAPED_UNICODE;
           if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
               $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
           }

           return response()->json($add_users, 200, [], $jsonFlags);
        }catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();      
            return response()->json(['status' => false, 'message' => $message], 500);
        }
    }

    /**
     * Ensure returned payload is valid UTF-8 before JSON encoding.
     */
    private function sanitizeUtf8Data($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->sanitizeUtf8Data($item);
            }
            return $value;
        }

        if (is_string($value) && !preg_match('//u', $value)) {
            $clean = function_exists('iconv') ? @iconv('UTF-8', 'UTF-8//IGNORE', $value) : false;
            if ($clean !== false) {
                return $clean;
            }

            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
        }

        return $value;
    }

    /**
     * @date:: 06 Jun 2024,  
     * @author :: Mangaleswari, 
     * @desc:: get Approvers function
    **/
    public function getApprovers(Request $request)
    {
        try
        {
            $validator =  Validator::make($request->all(),[
                    'location' => 'required',
                    'department' => 'required',
                    'category' => 'required',
                    'contract_type' => 'required',
                    'contract_value' => 'required'
            ]);
            if($validator->fails()) {
                $errors = $validator->errors();
                return response()->json(['status' => false,'message' => $errors,'data' => []], 422);
            }
           $location = $request->location;
           $department = $request->department;
           $category = $request->category;
           $contract_type = $request->contract_type;
           $contract_value = $request->contract_value;
           $financial_limit = [];
           $financial_limit = FinancialLimit::select("id","approval_type","approval_status","approval_required_users as approver")
                              ->where([['location','=',$location],['department','=',$department],['category','=',$category],['contract_type','=',$contract_type],['status','=',1]])
                              ->whereRaw('('.$contract_value.' BETWEEN lower_limit AND upper_limit)')
                              ->get();

            $where_clause =array(
                    0 => 'location = '.$location.' AND  department = '.$department.' AND  category= '.$category.' AND  contract_type = '.$contract_type ,
                    1 => 'location = '.$location.' AND  department = '.$department.' AND category= '.$category.' AND ( contract_type = '.$contract_type.' OR contract_type = 0)' ,
                    2 => 'location = '.$location.' AND  department = '.$department.' AND  ( category= '.$category.' OR category = 0 ) AND ( contract_type = '.$contract_type.' OR contract_type = 0)' ,
                    3 => 'location = '.$location.' AND ( department = '.$department.' OR department = 0) AND  ( category= '.$category.' OR category = 0 ) AND ( contract_type = '.$contract_type.' OR contract_type = 0)',
                    4 => '( location = '.$location.'  OR location = 0) AND  ( department = '.$department.' OR department = 0) AND  ( category= '.$category.' OR category = 0 ) AND ( contract_type = '.$contract_type.' OR contract_type = 0)'
                    );

            $contract_where_clause =array(
                    0 => '('.$contract_value.' BETWEEN lower_limit AND upper_limit OR lower_limit is null AND upper_limit is null)',
                    1 => '('.$contract_value.' BETWEEN lower_limit AND upper_limit OR lower_limit is null AND upper_limit is null)',
                    2 => '('.$contract_value.' BETWEEN lower_limit AND upper_limit OR lower_limit is null AND upper_limit is null)',
                    3 => '('.$contract_value.' BETWEEN lower_limit AND upper_limit OR lower_limit is null AND upper_limit is null)',
                    4 => '('.$contract_value.' BETWEEN lower_limit AND upper_limit OR lower_limit is null AND upper_limit is null)',
                );
            $i = 0;
            do {
              if (count($financial_limit) > 0) 
                {
                    return response()->json(['status' => true,'message' => '','data' => $financial_limit], 200);
                    break;
                }
              $financial_limit = FinancialLimit::select("id","approval_type","approval_status","approval_required_users as approver")
                              ->whereRaw($where_clause[$i])
                              ->where('status',1)
                              ->whereRaw($contract_where_clause[$i])
                              ->get();
              $i++;
              if(($i == 5) && (count($financial_limit) == 0))
              {
                return response()->json(['status' => false,'message' => 'No records found','data' => []], 404);
              }
            } while ($i < 6);
            
        }catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();   
            return response()->json(['status' => false,'message' => $message,'data' => []], 500);
        }
    }

    /**
     * Normalize rule_builder_data JSON to a canonical string for comparison
     */
    private function canonicalizeRuleData($json){
        if(empty($json)) return '';
        $data = json_decode($json, true);
        if($data === null) return trim($json);

        // recursive ksort for associative arrays
        $recursive_ksort = function (&$arr) use (&$recursive_ksort) {
            if(!is_array($arr)) return;
            // if associative array: sort by keys
            $isAssoc = array_values($arr) !== $arr;
            if($isAssoc){
                ksort($arr);
            }
            foreach($arr as &$v){
                if(is_array($v)) $recursive_ksort($v);
            }
        };
        $recursive_ksort($data);
        return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    /**
     * Normalize approval groups payload for stable persisted structure.
     * Now supports parent-grouped structure: {review: [...], approval: [...], signatory: [...], _parent_routing: {...}}
     */
    private function normalizeApprovalGroups($groupsInput, $defaultApprovalType = 'sequential')
    {
        $groups = $groupsInput;
        if (is_string($groupsInput)) {
            $decoded = json_decode($groupsInput, true);
            $groups = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($groups)) {
            return ['review' => [], 'negotiation' => [], 'finalization' => [], 'approval' => [], 'signatory' => [], '_parent_routing' => []];
        }

        // Preserve parent-level routing data
        $parentRouting = $groups['_parent_routing'] ?? [];

        // Check if input is already in parent-grouped format
        $isParentGrouped = isset($groups['review']) || isset($groups['negotiation']) || isset($groups['finalization']) || isset($groups['approval']) || isset($groups['signatory']);
        
        if ($isParentGrouped) {
            // Process parent-grouped structure
            $normalized = [
                'review' => [],
                'negotiation' => [],
                'finalization' => [],
                'approval' => [],
                'signatory' => [],
                '_parent_routing' => $parentRouting
            ];
            
            foreach (['review', 'negotiation', 'finalization', 'approval', 'signatory'] as $parentType) {
                if (!isset($groups[$parentType]) || !is_array($groups[$parentType])) {
                    continue;
                }
                
                foreach ($groups[$parentType] as $group) {
                    if (is_object($group)) {
                        $group = (array) $group;
                    }
                    if (!is_array($group)) {
                        continue;
                    }

                    $role = $group['role'] ?? 'Approver';
                    $approvalType = $group['approval_type'] ?? $defaultApprovalType;
                    $filePermission = $this->whitelistFilePermission($group['file_permission'] ?? 'editor');
                    $autoNextEnabled = (int)($group['auto_next_enabled'] ?? 0) === 1 ? 1 : 0;
                    $dynamicApproverEnabled = (int)($group['dynamic_approver_enabled'] ?? 0) === 1 ? 1 : 0;
                    $approversInput = $group['approvers'] ?? [];

                    if (is_object($approversInput)) {
                        $approversInput = (array) $approversInput;
                    }
                    if (!is_array($approversInput)) {
                        $approversInput = [];
                    }

                    $approvers = [];
                    foreach ($approversInput as $approver) {
                        if (is_object($approver)) {
                            $approver = (array) $approver;
                        }
                        if (!is_array($approver)) {
                            continue;
                        }

                        $approvers[] = [
                            'id' => (int)($approver['id'] ?? 0),
                            'type' => ($approver['type'] ?? 'name') === 'designation' ? 'designation' : 'name',
                            'name' => trim((string)($approver['name'] ?? '')),
                            'email' => trim((string)($approver['email'] ?? '')),
                        ];
                    }

                    $normalized[$parentType][] = [
                        'role' => $role,
                        'file_permission' => $filePermission,
                        'approval_type' => $approvalType,
                        'auto_next_enabled' => $autoNextEnabled,
                        'dynamic_approver_enabled' => $dynamicApproverEnabled,
                        'approvers' => $approvers,
                    ];
                }
            }
            
            return $normalized;
        }

        // Legacy flat array - migrate to parent-grouped structure
        $normalized = [
            'review' => [],
            'negotiation' => [],
            'finalization' => [],
            'approval' => [],
            'signatory' => [],
            '_parent_routing' => $parentRouting
        ];

        foreach ($groups as $group) {
            if (is_object($group)) {
                $group = (array) $group;
            }
            if (!is_array($group)) {
                continue;
            }

            $role = $group['role'] ?? 'Approver';
            $approvalType = $group['approval_type'] ?? $defaultApprovalType;
            $autoNextEnabled = (int)($group['auto_next_enabled'] ?? 0) === 1 ? 1 : 0;
            $dynamicApproverEnabled = (int)($group['dynamic_approver_enabled'] ?? 0) === 1 ? 1 : 0;
            $approversInput = $group['approvers'] ?? [];

            if (is_object($approversInput)) {
                $approversInput = (array) $approversInput;
            }
            if (!is_array($approversInput)) {
                $approversInput = [];
            }

            $approvers = [];
            foreach ($approversInput as $approver) {
                if (is_object($approver)) {
                    $approver = (array) $approver;
                }
                if (!is_array($approver)) {
                    continue;
                }

                $approvers[] = [
                    'id' => (int)($approver['id'] ?? 0),
                    'type' => ($approver['type'] ?? 'name') === 'designation' ? 'designation' : 'name',
                    'name' => trim((string)($approver['name'] ?? '')),
                    'email' => trim((string)($approver['email'] ?? '')),
                ];
            }

            $normalizedGroup = [
                'role' => $role,
                'file_permission' => $this->whitelistFilePermission($group['file_permission'] ?? 'editor'),
                'approval_type' => $approvalType,
                'auto_next_enabled' => $autoNextEnabled,
                'dynamic_approver_enabled' => $dynamicApproverEnabled,
                'approvers' => $approvers,
            ];

            // Map role to parent type
            if ($role === 'Signatory') {
                $normalized['signatory'][] = $normalizedGroup;
            } elseif ($role === 'Verifier' || $role === 'Preapprover') {
                $normalized['review'][] = $normalizedGroup;
            } elseif ($role === 'Negotiator') {
                $normalized['negotiation'][] = $normalizedGroup;
            } elseif ($role === 'Finalizer') {
                $normalized['finalization'][] = $normalizedGroup;
            } else {
                $normalized['approval'][] = $normalizedGroup;
            }
        }

        return $normalized;
    }

    /**
     * Whitelist a cloud-file permission level to one of the supported values.
     * Falls back to 'editor' (which preserves the historical write-access behaviour).
     */
    private function whitelistFilePermission($level)
    {
        $level = strtolower(trim((string) $level));
        return in_array($level, ['readonly', 'editor', 'commentator'], true) ? $level : 'editor';
    }

    private function saveApprovalGroupsToTable($financialLimitId, $approvalType, $groupsData)
    {
        try {
            \DB::beginTransaction();

            $existingSet = \App\Models\ApprovalGroupSet::where('financial_limit_id', $financialLimitId)
                ->where('approval_type', $approvalType)
                ->first();

            if ($existingSet) {
                $existingSet->groups()->delete();
                $existingSet->delete();
            }

            if (empty($groupsData)) {
                \DB::commit();
                return null;
            }

            $groupSet = \App\Models\ApprovalGroupSet::create([
                'financial_limit_id' => $financialLimitId,
                'approval_type' => $approvalType,
            ]);

            foreach (['review', 'negotiation', 'finalization', 'approval', 'signatory'] as $parentType) {
                if (!isset($groupsData[$parentType]) || !is_array($groupsData[$parentType])) {
                    continue;
                }

                foreach ($groupsData[$parentType] as $index => $group) {
                    if (!is_array($group)) {
                        continue;
                    }

                    $approvalGroup = \App\Models\ApprovalGroup::create([
                        'approval_group_set_id' => $groupSet->id,
                        'parent_type' => $parentType,
                        'role' => $group['role'] ?? 'Approver',
                        'approval_type' => $group['approval_type'] ?? 'sequential',
                        'auto_next_enabled' => $group['auto_next_enabled'] ?? 0,
                        'dynamic_approver_enabled' => $group['dynamic_approver_enabled'] ?? 0,
                        'order_index' => $index,
                    ]);

                    if (isset($group['approvers']) && is_array($group['approvers'])) {
                        foreach ($group['approvers'] as $approverIndex => $approver) {
                            if (!is_array($approver)) {
                                continue;
                            }

                            \App\Models\ApprovalGroupApprover::create([
                                'approval_group_id' => $approvalGroup->id,
                                'approver_id' => $approver['id'] ?? 0,
                                'approver_type' => $approver['type'] ?? 'name',
                                'approver_name' => $approver['name'] ?? '',
                                'approver_email' => $approver['email'] ?? '',
                                'order_index' => $approverIndex,
                            ]);
                        }
                    }
                }
            }

            \DB::commit();
            return $groupSet->id;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('saveApprovalGroupsToTable error: ' . $e->getMessage());
            return null;
        }
    }

    private function getApprovalGroupsFromTable($financialLimitId, $approvalType)
    {
        try {
            $groupSet = \App\Models\ApprovalGroupSet::where('financial_limit_id', $financialLimitId)
                ->where('approval_type', $approvalType)
                ->first();

            if (!$groupSet) {
                return null;
            }

            $groups = [
                'review' => [],
                'negotiation' => [],
                'finalization' => [],
                'approval' => [],
                'signatory' => [],
            ];

            $dbGroups = $groupSet->groups()->get();

            foreach ($dbGroups as $group) {
                $approvers = [];
                foreach ($group->approvers as $approver) {
                    $approvers[] = [
                        'id' => $approver->approver_id,
                        'type' => $approver->approver_type,
                        'name' => $approver->approver_name,
                        'email' => $approver->approver_email,
                    ];
                }

                $groupData = [
                    'role' => $group->role,
                    'approval_type' => $group->approval_type,
                    'auto_next_enabled' => $group->auto_next_enabled,
                    'dynamic_approver_enabled' => $group->dynamic_approver_enabled,
                    'approvers' => $approvers,
                ];

                if (isset($groups[$group->parent_type])) {
                    $groups[$group->parent_type][] = $groupData;
                }
            }

            return $groups;
        } catch (\Exception $e) {
            \Log::error('getApprovalGroupsFromTable error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * API: Return list of branches (locations) for rule builder
     */
    public function apiBranches(Request $request)
    {
        try {
            $branches = BranchUser::select('id', decrypt_data('BranchName','branch'), decrypt_data('LegalName','branch'))->get()->map(function($b){
                return [
                    'id' => $b->id,
                    'name' => $b->BranchName ?? $b->LegalName ?? ''
                ];
            });
            return response()->json(['status' => true, 'data' => $branches], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => []], 500);
        }
    }

    /**
     * API: Return list of departments (entity business)
     */
    public function apiDepartments(Request $request)
    {
        try {
            $departments = EntityBusiness::select('id', 'name')->get()->map(function($d){
                return ['id' => $d->id, 'name' => $d->name];
            });
            return response()->json(['status' => true, 'data' => $departments], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => []], 500);
        }
    }

    /**
     * API: Return list of categories (contract categories)
     */
    public function apiCategories(Request $request)
    {
        try {
            $categories = ContractCategories::select('id', 'name')->get()->map(function($c){
                return ['id' => $c->id, 'name' => $c->name];
            });
            return response()->json(['status' => true, 'data' => $categories], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => []], 500);
        }
    }

    /**
     * API: Return list of contract types
     */
    public function apiContractTypes(Request $request)
    {
        try {
            $types = ContractType::select('contract_type_id as id', 'contract_type as name')->get()->map(function($t){
                return ['id' => $t->id, 'name' => $t->name];
            });
            return response()->json(['status' => true, 'data' => $types], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => []], 500);
        }
    }
    
    /**
     * API: Return list of locations_master entries for rule builder
     */
    public function apiLocations(Request $request)
    {
        try {
            $locations = \App\Models\LocationMaster::select('id', 'location_name', 'region')->get()->map(function($l){
                return ['id' => $l->id, 'name' => $l->location_name, 'region' => $l->region];
            });
            return response()->json(['status' => true, 'data' => $locations], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => []], 500);
        }
    }    

    /**
     * API: Return active external party representatives for pre-approver selection.
     */
    public function apiExternalRepresentatives(Request $request)
    {
        try {
            $rows = ContractPartiesRepresentative::query()
                ->select(
                    'contract_parties_representative.id as representative_id',
                    'contract_parties_representative.representative_name',
                    'contract_parties_representative.representative_email',
                    'contract_parties_representative.representative_designation',
                    'contract_parties_representative.parties_id as party_id',
                    'contract_parties.company_name as party_name'
                )
                ->join('contract_parties', 'contract_parties.id', '=', 'contract_parties_representative.parties_id')
                ->where('contract_parties.status', 1)
                ->where('contract_parties_representative.status', 1)
                ->whereRaw('LOWER(contract_parties.party_type) = ?', ['external'])
                ->orderBy('contract_parties.company_name', 'asc')
                ->orderBy('contract_parties_representative.representative_name', 'asc')
                ->get()
                ->map(function ($row) {
                    $partyName = trim((string)($row->party_name ?? ''));
                    $repName = trim((string)($row->representative_name ?? ''));
                    $repEmail = trim((string)($row->representative_email ?? ''));
                    $designation = trim((string)($row->representative_designation ?? ''));

                    return [
                        'party_id' => (int)($row->party_id ?? 0),
                        'party_name' => $partyName,
                        'representative_id' => (int)($row->representative_id ?? 0),
                        'representative_name' => $repName,
                        'representative_email' => $repEmail,
                        'designation' => $designation,
                        'label' => $partyName . ' - ' . $repName . ' - ' . $repEmail . ' - ' . $designation,
                    ];
                })
                ->values();

            return response()->json(['status' => true, 'data' => $rows], 200);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => []], 500);
        }
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $data = [];
        //print_r($request->filled('q'));exit;
        if($request->filled('q')){
            $data = User::select("name", "id")
                        ->where('name', 'LIKE', '%'. $request->get('q'). '%')
                        ->get();
        }else
        {
            $data = User::select("name", "id")
                        ->get();
        }
        return response()->json($data);
    }
    
    public function financial_add_users(Request $request)
    {
        try
        {
            $appType = $request->appType;
            $defVal = $request->defVal;
            $index = $request->index;
            $add_users = AddUsers::select("id",
            decrypt_data('Salutation', 'AddUsers'), 
            decrypt_data('FirstName', 'AddUsers') , 
            decrypt_data('LastName', 'AddUsers'),
            decrypt_data('Designation', 'AddUsers'),
            decrypt_data('Email', 'AddUsers')
            )->get();
            return view('contract-setup::financial.add-users',compact('add_users','index', 'appType','defVal'));
        }catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();   
            return response()->json(['status' => false,'message' => $message,'data' => []], 500);
        }
    }
    
    public function financial_party_add_users(Request $request)
    {
        try
        {
            $appType = $request->appType;
            $defVal = $request->defVal;
            $index = $request->index;
            $add_users = AddUsers::select("id",
            decrypt_data('Salutation', 'AddUsers'), 
            decrypt_data('FirstName', 'AddUsers') , 
            decrypt_data('LastName', 'AddUsers'),
            decrypt_data('Designation', 'AddUsers'),
            decrypt_data('Email', 'AddUsers')
            )->get();
            return view('contract-setup::party.add-users',compact('add_users','index', 'appType','defVal'));
        }catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();   
            return response()->json(['status' => false,'message' => $message,'data' => []], 500);
        }
    }

    public function party_approval_add(Request $request)
    {
        
        try
        {
            if ($request->isMethod('post')) {
               //return $request->post();

               $validator =  Validator::make($request->all(),[
                    'location' => 'required',
                    'access_level' => 'required'
                ]);
                
                if($validator->fails()) {
                    
                    $errors = $validator->errors();
                    return redirect('/contract-setup/party-approval-add')->withErrors($validator)->withInput();
                }
                
                
                $check_financial = PartyApprovalRules::select('id')
                                    ->where('branch',$request->location)
                                    ->where('accesslevel',$request->access_level)
                                    ->get();
                            
                if(count($check_financial) > 0)
                {
                    $exist_error = array('This Location,Department,Category,Contract Type and Financial Limits Combination already exist. Try Other/Modify the values');
                    return redirect('/contract-setup/party-approval-add')->withErrors($exist_error)->withInput();
                }
                
                if($request->lower_limit > 0){
                    $check_limit = PartyApprovalRules::select('id')
                                    ->where('branch',$request->location)
                                    ->where('accesslevel',$request->access_level)
                                    ->get();

                    if(count($check_limit) > 0)
                    {
                        $exist_error = array('Lower and Upper Limit Already Taken Try Other/Modify the values');
                        return redirect('/contract-setup/party-approval-add')->withErrors($exist_error)->withInput();                    
                    }
                }
                $approval_users = [];
                if($request->approval_status == 'required')
                {
                    foreach ($request->approval_required_user_type as $ky => $value)
                    {
                        $type_approver = $value;
                        $role = $request->approval_required_role[$ky] ?? 'Approver';
                        if($value == 'name'){
                            $parts = explode(':', $request->approval_required_users[$ky]);
                            $id = $parts[0];
                            $name = $parts[1];
                            $email = $parts[2];
                        }else{
                            $id = 0;
                            $name = $request->approval_required_desg[$ky];
                            $email = 0;                            
                        }
                        $approval_users[] = [
                            'id' => $id,
                            'type' => $type_approver,
                            'name' => $name,
                            'email' => $email,
                            'role' => $role
                        ];
                    }
                    
                }
                    if(count($approval_users) == 0){
                        $approval_users[] = [
                            'id' => 0,
                            'type' => 'name',
                            'name' => '',
                            'email' => '',
                            'role' => 'Approver'
                        ];                                    
                    }
                    $financial = new PartyApprovalRules();
                    $financial->branch = $request->location;
                $financial->accesslevel = $request->access_level;

                $financial->approval_status = $request->approval_status;
                $financial->approval_required_users = json_encode($approval_users);

                $financial->status = 1;
                $financial->save();
                return redirect('/contract-setup/party-approval-rules')->with('success','Party approval rules has been Created Successfully.');
            }else
            {
             $geo_graph = $this->getGeoGraphDropdowns();
             $branch = Branch::select("id",decrypt_data('BranchName','branch'))->get();
             $add_users = AddUsers::select("id",
            decrypt_data('Salutation', 'AddUsers'), 
            decrypt_data('FirstName', 'AddUsers') , 
            decrypt_data('LastName', 'AddUsers'),
            decrypt_data('Designation', 'AddUsers'),
            decrypt_data('Email', 'AddUsers')
            )->get();
             return view('contract-setup::party.create',compact('geo_graph','branch','add_users'));
            }
        }catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();      
            return redirect('/contract-setup/party-approval-add')->withErrors([$message, $code])->withInput(); 
        }
    } 
    
    public function party_approval_edit(Request $request)
    {
        try
        {
            if ($request->isMethod('post')) {

               $validator =  Validator::make($request->all(),[
                    'location' => 'required',
                    'access_level' => 'required'
                ]);

                if($validator->fails()) {
                    $errors = $validator->errors();
                    return redirect('/contract-setup/party-approval-edit/'.$request->financial_id)->withErrors($validator)->withInput();
                }

                $approval_users = [];
                if($request->approval_status == 'required')
                {
                    foreach ($request->approval_required_user_type as $ky => $value)
                    {
                        $type_approver = $value;
                        if($value == 'name'){
                            $parts = explode(':', $request->approval_required_users[$ky]);
                            $id = $parts[0];
                            $name = $parts[1];
                            $email = $parts[2];
                        }else{
                            $id = 0;
                            $name = $request->approval_required_desg[$ky];
                            $email = 0;                            
                        }
                        $approval_users[] = [
                            'id' => $id,
                            'type' => $type_approver,
                            'name' => $name,
                            'email' => $email
                        ];
                    }
                }
                $financial = PartyApprovalRules::find($request->financial_id);
                
                $update_request = [
                    'approval_status' => $request->approval_status,
                    'approval_required_users' => json_encode($approval_users)
                ];                
                
                if($request->financial_id != 1){
                    $update_request['branch'] = $request->location;
                    $update_request['accesslevel'] = $request->access_level;
                }

                $financial->update($update_request);
                
                return redirect('/contract-setup/party-approval-rules')->with('success','Approval Rule has been Updated Successfully.');
            }else
            {
                $financial = PartyApprovalRules::find($request->id);
  
                $geo_graph = $this->getGeoGraphDropdowns();
                $branch = Branch::select("id",decrypt_data('BranchName','branch'))->get();
                $add_users = AddUsers::select("id",
                            decrypt_data('Salutation', 'AddUsers'), 
                            decrypt_data('FirstName', 'AddUsers') , 
                            decrypt_data('LastName', 'AddUsers'),
                            decrypt_data('Designation', 'AddUsers'),
                            decrypt_data('Email', 'AddUsers'))->get();
             return view('contract-setup::party.edit',compact('financial','geo_graph','branch','add_users'));
            }
        }catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();      
            return $message;
        }
    }    
    
}
