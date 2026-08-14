<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\AddUsers;
use App\Models\Contract;
use App\Models\BranchUser;
use App\Models\ContractCategories;
use App\Models\EntityMain;
use App\Models\EntityBusiness;
use App\Models\GeographicalHierarchy;
use App\Models\UserCredentials;
use App\Models\UserActionLog;
use App\Models\CustomVarDocs;
use App\Models\FinancialLimit;
use App\Models\ConfigStorageConfig;
use DB;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Route;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    
    public static function checkCurrentAuth($access)
    {
        return true;
    }

    public function getGeoGraphDropdowns(){
        
        $type     = "headoffice";
        
        $entityid = session()->get('contractSessionEntity') ?? env('default_entity_id');
        
        if(!env('default_entity_id')){
            $geo_graphs = GeographicalHierarchy::where('type', $type)->get();
        }else{
           $geo_graphs = GeographicalHierarchy::where('entityid', $entityid)->where('type', $type)->get(); 
        }
        
        
        if (count($geo_graphs) > 0) {
            
            foreach ($geo_graphs as $row) {
                $parent = $row["id"];
                $type   = $row["type"];
                
                $responseRegion = array();
                $geo_graphs_1 = GeographicalHierarchy::where('entityid', $entityid)->where('parent', $parent)->get();
                
                
                if (count($geo_graphs_1) > 0) {
                    
                    foreach ($geo_graphs_1 as $rowRegion) {
                        
                        $parent     = $rowRegion["id"];
                        $typeRegion = $rowRegion["type"];
                        
                        $responseState = array();
                        $geo_graphs_2 = GeographicalHierarchy::where('entityid', $entityid)->where('parent', $parent)->get();
                        
                        if (count($geo_graphs_2) > 0) {
                    
                        foreach ($geo_graphs_2 as $rowState) {
                                
                                
                                $parent    = $rowState["id"];
                                $typeState = $rowState["type"];
                                
                                $responseZone = array();
                                $geo_graphs_3 = GeographicalHierarchy::where('entityid', $entityid)->where('parent', $parent)->get();
                        
                                if (count($geo_graphs_3) > 0) {
                            
                                foreach ($geo_graphs_3 as $rowZone) {
                                        
                                        $parent   = $rowZone["id"];
                                        $typeZone = $rowZone["type"];
                                        
                                        $responseDistrict = array();
                                        $geo_graphs_4 = GeographicalHierarchy::where('entityid', $entityid)->where('parent', $parent)->get();
                        
                                        if (count($geo_graphs_4) > 0) {
                                    
                                        foreach ($geo_graphs_4 as $rowDistrict) {
                                                
                                        $parent   = $rowDistrict["id"];
                                        $typeDistrict = $rowDistrict["type"];
                                        
                                        $responseCity = array();
                                        $geo_graphs_5 = GeographicalHierarchy::where('entityid', $entityid)->where('parent', $parent)->get();
                        
                                        if (count($geo_graphs_5) > 0) {
                                    
                                        foreach ($geo_graphs_5 as $rowCity) {
                                                
                                        $parent   = $rowCity["id"];
                                        $typeCity = $rowCity["type"];
                                        
                                        $responseCluster = array();
                                        $geo_graphs_6 = GeographicalHierarchy::where('entityid', $entityid)->where('parent', $parent)->get();
                        
                                        if (count($geo_graphs_6) > 0) {
                                    
                                        foreach ($geo_graphs_6 as $rowCluster) {
                                                
                                                $rowCluster["tname"]="&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;".$rowCluster["name"];
                                                $rowDistrict["ticon"] = 'check';
                                                $responseGeo[] = $rowCluster;
                                            }}
                                                
                                                
                                                $rowCity["tname"]="&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;".$rowCity["name"];
                                                $rowDistrict["ticon"] = 'check';
                                                $responseGeo[] = $rowCity;
                                            }}
                                                
                                                $rowDistrict["tname"]="&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;".$rowDistrict["name"];
                                                $rowDistrict["ticon"] = 'check';
                                                $responseGeo[] = $rowDistrict;
                                            }
                                        }
                                        
                                        
                                        $rowZone["tname"]="&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;".$rowZone["name"];
                                        $rowDistrict["ticon"] = 'check';
                                    $responseGeo[] = $rowZone;
                                    }
                                }
                                
                                
                                $rowState["tname"]="&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;".$rowState["name"];
                                $responseGeo[] = $rowState;
                            }
                        }
                        
                        $rowRegion["tname"]="&#160;&#160;&#160;&#160;".$rowRegion["name"];
                                $responseGeo[] = $rowRegion;
                        
                    }
    
                }
                $row["tname"]=$row["name"];
                $responseGeo[] = $row;
            }
        
        }

        return array_reverse($responseGeo);
    }
    
    public function availableContracts($contracts=null, $listArray=false, $countArrKey="", $partyData="contracts", $locationCheck=array()){
        
        $action = Route::currentRouteAction();
    
        if ($action) {
            [$controllerFull, $method] = explode('@', $action);
    
            // Get only the controller class name (without namespace)
            $controller = class_basename($controllerFull);
    
            if($controller != 'ContractReportsController'){
                setcookie('filterByLocationReport', null, 0, "/");
            }
        }
        

        if(!$contracts){
            $contracts = Contract::select(
                'contract_name', 'id', 'currency', 
                'currency_value','end_contract_type', 
                'contract_status','substatus',
                'fixed_date','onetime_end_date','contract_end_date','contract_type','department_id'
            )
            ->orderBy('id', 'desc')
            ->where('status', 1)
            ->get();
        }
        
        //For AccessLevel
        $available_branches = BranchUser::pluck(decrypt_data('BranchName', 'branch'), 'id')->toArray();
        $available_departms = EntityBusiness::pluck('id')->toArray();

        $entity_list = EntityMain::pluck(decrypt_data('Nameoftheentity', 'entity'), 'id')->toArray();

        $availableContracts = [];

        foreach ($contracts as $contract) {
           
            $contract->contract_name = decryptString($contract->contract_name, 'contract_name');
            $contract->currency = decryptString($contract->currency, 'currency');
            $contract->currency_value = decryptString($contract->currency_value, 'currency_value');
            $contract->end_contract_type = decryptString($contract->end_contract_type, 'end_contract_type');                   
            
            if($contract->fixed_date == ''){
                $contract->fixed_date = '-';
            }else{
                $fixedDate = strtotime($contract->fixed_date); 
                $contract->fixed_date = date('d-m-Y', $fixedDate);
                
            }
            
            if($contract->contract_end_date == ''){
                $contract->contract_end_date = '-';
            }else{
                $endDate = strtotime($contract->contract_end_date); 
                $contract->contract_end_date = date('d-m-Y', $endDate);
            }
            
            $partysName = '';
            
            $contract->location_branch = '-';
            
            $contractParty = $contract->contractPartyList->all();
            
            $contract->applicable = false;
            
            $contractParty = $contract->contractPartyList->all();

            $contract->contractParty = $contractParty;

            if (isset($contract->catgoery_id)) {
                $category = \App\Models\ContractCategories::find($contract->catgoery_id);
            
                if ($category) {
                    $contract->catgoery_identity = $contract->catgoery_id;
                    $contract->catgoery_id = $category->name;
                } else {
                    // Optional: handle missing category
                    $contract->catgoery_identity = $contract->catgoery_id;
                    $contract->catgoery_id = null; // or 'Unknown'
                }
            }
            
            $contract->currency_value_converted = "-";
            if($contract->currency_value > 0){
                $contract->currency_value_converted = currency_formatter(env('default_currency'),$contract->currency_value);
            }            

            $i = 0;
            $locIndx = 0;
            foreach ($contractParty as $contractPart) {
                
                //Check Branches Accessible for the User
                if ($contractPart->contract_party_location_id == !null && $contractPart->contract_party_type == 'Internal') {
                    
                    //For Setting Internal Party Name
                    if(isset($entity_list[$contractPart->contract_party_id])){
                        $entityName = $entity_list[$contractPart->contract_party_id];
                        $contractPart->Nameoftheentity = $entityName;
                        if($i == 0){
                            $partysName .= ','.$entityName;
                        }else{
                            $partysName .= ','.$entityName;
                        }
                        $i++;
                    }


                    //For Check Branch Level Access for the User
                    if(!array_key_exists($contractPart->contract_party_location_id, $available_branches) && !$contract->applicable){
                        $contract->applicable = false;
                    }else{
                        if($locIndx == 0 && $partyData == 'partyData'){

                            if(!isset($availableContracts[$contractPart->contract_party_location_id])){
                                $availableContracts[$contractPart->contract_party_location_id] = 1;
                            }else{
                                $availableContracts[$contractPart->contract_party_location_id]++;
                            }

                        }
                        $contract->location_branch = $available_branches[$contractPart->contract_party_location_id] ?? '-';
                        
                        $locationCheckCookie = $_COOKIE['filterByLocationReport'] ?? '[]'; 
                        
                        $locationCheck = json_decode($locationCheckCookie);
                        
                        if(!empty($locationCheck) && !in_array($contractPart->contract_party_location_id,$locationCheck)){
                            $contract->applicable = false;
                        }else{
                            $contract->applicable = true;
                        }
                        $locIndx++;
                    }                 
                }

                if ($contractPart->contract_party_exe_id == !null) {

                    if(isset($contractParties->company_name )){
                        $partysName .=  ','.decryptString($contractPart->partyDetailsEx->company_name, 'company_name') . ',';
                    }else{
                        $partysName .= '';
                    }
                }                
            }

            //For Check Departments Leval Access for the User
            if(!in_array($contract->department_id,$available_departms)){
                $contract->applicable = false;
            }
            
            if($contract->applicable){

                if($partyData == 'contracts'){
                    if($countArrKey != ""){
                        if(!isset($availableContracts[$contract[$countArrKey]])){
                            $availableContracts[$contract[$countArrKey]] = 1;
                        }else{
                            $availableContracts[$contract[$countArrKey]]++;
                        }
                    }else if($listArray){
    
                        $contract->contract_type_id = $contract->contract_type ?? 0;
                        
                        $contract->contract_type =  $contract->contractTypeData->contract_type ?? '';

                    
                        $partysName = preg_replace('/^,|,$/', '', $partysName);
                        
                        $contract->contractPartyNames = $partysName;
    
                        $availableContracts[] = $contract;
                    }
                }
            }
        }
        return $availableContracts;
    }
    
    public function crudUserActionLog($group_id, $actionType, $actionName, $actionId, $actionStatus = 0, $actionerId, $update = false, $actionerName='', $locDetails=''){
        $actionLogData = [];
        
        if(!$update){
            //Owner/Initiator
            $owner_initiator = session()->get('contractSessionUser');
    
            $initiatior_exists = AddUsers::select('id',  decrypt_data('AccessScope', 'AddUsers'))
                ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
                ->first();
            if (!$initiatior_exists) {
                $owner_initiator_id = 0;
            }
    
            $owner_initiator_id = $initiatior_exists->id ?? 0;
                
            $actionLogData['user_id'] = $owner_initiator_id;
            $actionLogData['group_id'] = $group_id;
            $actionLogData['action_type'] = $actionType;
            $actionLogData['action_name'] = $actionName;
            $actionLogData['action_id'] = $actionId;
            $actionLogData['actioner_id'] = $actionerId;
            $actionLogData['actioner_name'] = $actionerName;
            $actionLogData['status'] = $actionStatus;
            $actionLogData['log_details'] = json_encode(['ip' => $_SERVER['REMOTE_ADDR'], 'logDate' => date('d-m-Y H:i:s'), 'storageType' => fileStorageType(), 'coords'=>$locDetails]);        
            $createActionLog = UserActionLog::create($actionLogData);
        }else{
            //$whereLogData['user_id'] = $owner_initiator_id;
            //$whereLogData['action_id'] = $actionId;
            
            $whereLogData = [];
            $whereLogData['group_id'] = $group_id;
            $whereLogData['action_type'] = $actionType;
            $whereLogData['action_name'] = $actionName;
            $whereLogData['actioner_id'] = $actionerId;
            $whereLogData['status'] = 0;            
            
            $updateLogData = [];
            $updateLogData['status'] = $actionStatus;            
            UserActionLog::where($whereLogData)
            ->update($updateLogData);
        }
    }
    
    public function checkTablesConfiguration(){
        $tables = DB::select('SHOW TABLES');
        
        $requiredTable = [];

    
        $requiredTableText = array_map(function($value){ return ucwords(str_replace("_", " ", $value));}, $requiredTable);
        $missedTables = 0;
        $allTables = [];
        
        //Get All Tables
        foreach ($tables as $table) {
            foreach ($table as $name) {
                if(in_array($name, $requiredTable)){
                    $allTables[] = $name;
                }
            }
        }
        
        //Check Tables Available
        $missedTablesArr = [];
        foreach ($requiredTable as $key => $reqTbl) {
            if(!in_array($reqTbl, $allTables)){
                $missedTables++;
                $missedTablesArr[] = "Table <b>$requiredTableText[$key]</b> Was Missing Please Create One"; 
            }else{
                if($reqTbl == 'custom_var_docs'){
                    $checkCustomVarDocs = CustomVarDocs::where('status', 1)->count();
                    if($checkCustomVarDocs == 0){
                        $missedTablesArr[] = "Default Custom Variables was missing...";
                        $missedTables++;
                    }
                }
                if($reqTbl == 'financial_limit'){
                    $checkFinancialLimit = FinancialLimit::where('status', 1)->count();
                    if($checkFinancialLimit == 0){
                        $missedTablesArr[] = "Default DOA/Approval Rules Missing...";
                        $missedTables++;
                    }                    
                }
            }
        }
        
        return $missedTables == 0 ? true : $missedTablesArr;
    }
    
    public function storageAvailableCheck($checkDays = 88, $reminder = false){
        
        $storageAvailable = false;
        //For AccessLevel
        if(fileStorageType() != "Local"){
           $configExist = ConfigStorageConfig::where('storage_type', fileStorageType())->orderBy('id', 'DESC')->first();
           
           if($configExist){
            
                $createdDt = $configExist->created_at;
                // Convert to DateTime if it's a string
                if (is_string($createdDt)) {
                    $createdDt = new DateTime($createdDt);
                }
            
                $now = new DateTime();
                $diff = $now->diff($createdDt)->days;
            
                if ($createdDt < $now && $diff < $checkDays) {
                    $storageAvailable = true;
                }else{
                    $storageAvailable = $reminder ? ($diff - $checkDays) : false;
                }
           }
        }else{
           $storageAvailable = true; 
        }
        return $storageAvailable;
    }
    
}
