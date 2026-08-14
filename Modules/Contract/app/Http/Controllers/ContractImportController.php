<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Contract\Http\Controllers\ContractController;
use Modules\Contract\Http\Controllers\GoogleDriveController;
use Modules\Contract\Http\Controllers\LocalDriveController;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use DateTime;

use App\Models\AddUsers;
use App\Models\ApprovalContracts;
use App\Models\Branch;
use App\Models\Category;
use App\Models\ContractParties;
use App\Models\ContractType;
use App\Models\CustomFields;
use App\Models\CustomFieldsHistory;
use App\Models\CustomFieldsTimeline;
use App\Models\Contract;
use App\Models\CustomFieldsData;
use App\Models\ContractPartyData;
use App\Models\ContractCategories;
use App\Models\EntityBusiness;
use App\Models\EntityMain;
use App\Models\Country;
use App\Models\ContractPartiesLabel;
use App\Models\State;
use App\Models\ContractHistory;
use App\Models\FinancialLimit;
use App\Models\BranchUser;
use App\Helpers\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ContractImportController extends Controller
{

    public function __construct()
    {
          if(Controller::checkCurrentAuth("Contracts") != 1){
              return abort('404');
          }
    }


    public function storeFile(Request $request)
    {
        
        
        $controller = fileStorageTypeController(); 
        $errorMsg = "";
        $files = null;
        if ($request->hasFile('files')) {
            $files = $request->file('files');
        }
    
        $value = session('datafull');
        
        $error_rows = 0;
         
        try {
            foreach ($value as $key => $val) {
                $contract = [];
                $contract_type_data = ContractType::where('short_name', $key)->select('contract_type','contract_type_id')->first();
                $contract_type_id = $contract_type_data->contract_type_id;
                $cname = $contract_type_data->contract_type;
                $roid = [];
    
                foreach ($val as $ckey => $chaildval) {
                    
                    if ($ckey > 1) {
                      
                        $value[$key][$ckey]['error'] = false;
                        $value[$key][$ckey]['errormessage'] = "";

                        if (!isset($chaildval[1]) && !isset($chaildval[2]) && !isset($chaildval[3])) {
                            continue;
                        }
                        
                        if(isset($chaildval[24]) && !is_numeric($chaildval[24])){
                            $value[$key][$ckey]['error'] = true;
                            $value[$key][$ckey]['errormessage'] .= 'Invalid Contract Value <br/> <hr>';                            
                        }
                        
                        $partyCheckArray = ['Internal'=>[], 'Intergroup'=>[],'External'=>[], ''];
                        
                        if (isset($chaildval[1])) {
                            
                            $selno = $chaildval[0];
                            $department_id =  EntityBusiness::where('name', $chaildval[2])->pluck('id')->first();
    
                            $catgoery_id =  ContractCategories::where('name', $chaildval[3])->pluck('id')->first();
    
                            $exclusivity = isset($chaildval[4]) ? $chaildval[4] : null;
    
                            $contract_description = isset($chaildval[5]) ? $chaildval[5] : null;
                            
                            $previous_contract_exits =  $chaildval[6];
                            $previous_contract_no =  $chaildval[7]; 
    
                            $party1_type = isset($chaildval[8]) ? $chaildval[8] : null;
    
                            $party1_external_partyname = '';
    
                            if (isset($chaildval[8])) {
                                
                               
                                if ($party1_type == "Internal" || $party1_type == "Intergroup") {
                                    
                                    $party1_partyname = EntityMain::where(decrypt_datas('Nameoftheentity', 'entity'), [$chaildval[9]])
                                    ->value('id');
                                    $party1_internalBranch =  DB::table('branch')
                                    ->where(decrypt_datas('LegalName', 'branch'), [$chaildval[10]])
                                    ->value('id');
                                    
                                    $partyCheckArray[$party1_type][] = $party1_partyname;
                                    
                                }else{
                                    $party1_external_partynames = ContractParties::pluck('company_name', 'id');
    
                                    $exneone = explode(":", $chaildval[11]);
        
                                    if(isset($exneone[0])){
        
                                        $chaildval[11] = $exneone[0];
        
                                    }
        
                                    foreach ($party1_external_partynames as $key1 => $party1_external_partynam) {
                                        if (trim(decryptString($party1_external_partynam, 'company_name')) == trim($chaildval[11])) {
                                            $party1_external_partyname =  $key1;
                                            $partyCheckArray['external'][] = $party1_external_partyname;
                                        }
                                        
                                       
                                    }
                                    
                                    if(!isset($party1_external_partyname) || $party1_external_partyname == null || $party1_external_partyname == ''){
                                        $error_rows++;
                                        $value[$key][$ckey]['error'] = true;
                                        $value[$key][$ckey]['errormessage'] .= 'Invalid External Party1 Name <br/> <hr>';
                                    }                                    
                                    
                                }
       
                            } 
    
    
                            $party2_type = isset($chaildval[12]) ? $chaildval[12] : null;
                            $party2_external_partyname = '';

                            if ($party2_type == "Internal" || $party2_type == "Intergroup") {
                                $party2_partyname = EntityMain::where(decrypt_datas('Nameoftheentity', 'entity'), [$chaildval[13]])
                                    ->value('id');
                                    
                                $party2_internalBranch =  DB::table('branch')
                                    ->where(decrypt_datas('LegalName', 'branch'), [$chaildval[14]])
                                    ->value('id');
                                $partyCheckArray[$party2_type][] = $party2_partyname;
                            }else{
                                $party2_external_partynames =  ContractParties::pluck('company_name', 'id');
                                $exne = explode(":", $chaildval[15]);
                                if(isset($exne[0])){
                                    $chaildval[15] = $exne[0];
                                }

                                foreach ($party2_external_partynames as $key2 => $party1_external_partynam) {
                                    if (trim(decryptString($party1_external_partynam, 'company_name')) == trim($chaildval[15])) {
                                        $party2_external_partyname =  $key2;
                                        $partyCheckArray['external'][] = $party2_external_partyname;
                                    }
                                }
                                if(!isset($party2_external_partyname) || $party2_external_partyname == null || $party2_external_partyname == ''){
                                    $error_rows++;
                                    $value[$key][$ckey]['error'] = true;
                                    $value[$key][$ckey]['errormessage'] .= 'Invalid External Party2 Name <br/> <hr>';
                                }
                              
                            }
                            
                            
                            if (isset($chaildval[49])) {
                                
                                $party3_type = isset($chaildval[49]) ? $chaildval[49] : null;
        
                                $party3_external_partyname = '';
                               
                                if ($party3_type == "Internal" || $party3_type == "Intergroup") {
                                    
                                    $party3_partyname = EntityMain::where(decrypt_datas('Nameoftheentity', 'entity'), [$chaildval[50]])
                                    ->value('id');
                                    $party3_internalBranch =  DB::table('branch')
                                    ->where(decrypt_datas('LegalName', 'branch'), [$chaildval[51]])
                                    ->value('id');
                                    $partyCheckArray[$party3_type][] = $party3_partyname;
                                }else{
                                    $party3_external_partynames = ContractParties::pluck('company_name', 'id');
    
                                    $exneone = explode(":", $chaildval[52]);
        
                                    if(isset($exneone[0])){
        
                                        $chaildval[52] = $exneone[0];
        
                                    }
        
                                    foreach ($party3_external_partynames as $key3 => $party3_external_partynam) {
                                        if (trim(decryptString($party3_external_partynam, 'company_name')) == trim($chaildval[52])) {
                                            $party3_external_partyname =  $key3;
                                        }
                                        
                                       
                                    }
                                    
                                    if(!isset($party3_external_partyname) || $party3_external_partyname == null || $party3_external_partyname == ''){
                                        $error_rows++;
                                        $value[$key][$ckey]['error'] = true;
                                        $value[$key][$ckey]['errormessage'] .= 'Invalid External Party3 Name <br/> <hr>';
                                        //continue;
                                    }                                        
                                    
                                }
                            } 

                            if (isset($chaildval[53])) {
                            
                                $party4_type = isset($chaildval[53]) ? $chaildval[53] : null;
        
                                $party4_external_partyname = '';

                                if ($party4_type == "Internal" || $party4_type == "Intergroup") {
                                    $party4_partyname = EntityMain::where(decrypt_datas('Nameoftheentity', 'entity'), [$chaildval[54]])
                                        ->value('id');
                                        
                                    $party4_internalBranch =  DB::table('branch')
                                        ->where(decrypt_datas('LegalName', 'branch'), [$chaildval[55]])
                                        ->value('id');
                                    $partyCheckArray[$party4_type][] = $party4_partyname;
                                }else{
                                    $party4_external_partynames =  ContractParties::pluck('company_name', 'id');
                                    $exne = explode(":", $chaildval[56]);
                                    if(isset($exne[0])){
                                        $chaildval[56] = $exne[0];
                                    }
    
                                    foreach ($party4_external_partynames as $key4 => $party4_external_partynam) {
                                        if (trim(decryptString($party4_external_partynam, 'company_name')) == trim($chaildval[56])) {
                                            $party4_external_partyname =  $key4;
                                        }
                                    }
                                    if(!isset($party4_external_partyname) || $party4_external_partyname == null || $party4_external_partyname == ''){
                                        $error_rows++;
                                        $value[$key][$ckey]['error'] = true;
                                        $value[$key][$ckey]['errormessage'] .= 'Invalid External Party4 Name <br/> <hr>';
                                    }
                                  
                                }
                            
                            }
                            
                            if (isset($chaildval[57])) {
                                $party5_type = isset($chaildval[57]) ? $chaildval[57] : null;
        
                                $party5_external_partyname = '';

                                if ($party5_type == "Internal" || $party5_type == "Intergroup") {
                                    $party5_partyname = EntityMain::where(decrypt_datas('Nameoftheentity', 'entity'), [$chaildval[58]])
                                        ->value('id');
                                        
                                    $party5_internalBranch =  DB::table('branch')
                                        ->where(decrypt_datas('LegalName', 'branch'), [$chaildval[59]])
                                        ->value('id');
                                    $partyCheckArray[$party5_type][] = $party5_partyname;
                                }else{
                                    $party5_external_partynames =  ContractParties::pluck('company_name', 'id');
                                    $exne = explode(":", $chaildval[60]);
                                    if(isset($exne[0])){
                                        $chaildval[60] = $exne[0];
                                    }
    
                                    foreach ($party5_external_partynames as $key5 => $party5_external_partynam) {
                                        if (trim(decryptString($party5_external_partynam, 'company_name')) == trim($chaildval[60])) {
                                            $party5_external_partyname =  $key5;
                                        }
                                    }
                                    if(!isset($party5_external_partyname) || $party5_external_partyname == null || $party5_external_partyname == ''){
                                        $error_rows++;
                                        $value[$key][$ckey]['error'] = true;
                                        $value[$key][$ckey]['errormessage'] .= 'Invalid External Party5 Name <br/> <hr>';
                                    }
                                  
                                }
                                
                            }                            
                                    
                            $owner = AddUsers::where(decrypt_datas('Email', 'AddUsers'), [$chaildval[16]])
                                ->pluck('id')
                                ->first();
    
                            $signatory = AddUsers::where(decrypt_datas('Email', 'AddUsers'), [$chaildval[17]])
                                ->pluck('id')
                                ->first();
    
                            $commencement_type = isset($chaildval[19]) ? $chaildval[19] : null;
                            $commencement_date = isset($chaildval[20]) ? dateImport($chaildval[20]) : null;
                            $contract_end_type = isset($chaildval[21]) ? $chaildval[21] : null;
                            $end_date_of_contract = isset($chaildval[22]) ? dateImport($chaildval[22]) : null;
                            
                            if ($chaildval[2] == 'New') {
                                $signing_date = null;
                            } else {
                                $signing_date = isset($chaildval[18]) ? dateImport($chaildval[18]) : $commencement_date;
                            }                        
                            
    
                            
                            // ============= Status Check ==================== //

                            $cur_date = date('Y-m-d');
                            if(isset($chaildval[1]) && $chaildval[1] === 'Legacy Contracts' && $end_date_of_contract != ''){
                                $contract_status = 'executed';
                                
                                if(strtotime($end_date_of_contract) > strtotime($cur_date)){
                                    $contract_sub_status = 'active';
                                }elseif(strtotime($cur_date) > strtotime($end_date_of_contract)){
                                    $contract_sub_status = 'expired';
                                }
                                
                            }elseif(isset($chaildval[1]) && $chaildval[1] === 'New'){
                                $contract_status = 'draft';
                                $contract_sub_status = 'draft';
                            }else{
                                $value[$key][$ckey]['error'] = true;
                                $value[$key][$ckey]['errormessage'] .= "Start and End Date Mandatory<hr>"; 
                            }
                            
                            if($contract_end_type == 'evergreen' && $chaildval[1] != 'New'){
                                $contract_status = 'executed';
                                $contract_sub_status = 'active';
                            }
                            
                            
                            if($contract_end_type == 'onetimeContract' && $chaildval[1] != 'New'){
                                $contract_status = 'executed';
                                if($end_date_of_contract != ''){
                                    if(strtotime($end_date_of_contract) > strtotime($cur_date)){
                                        $contract_sub_status = 'active';
                                    }elseif(strtotime($cur_date) > strtotime($end_date_of_contract)){
                                        $contract_sub_status = 'completed';
                                    }
                                }else{
                                    $contract_sub_status = 'active';
                                }
                                
                            }
                            
                            if($contract_end_type == 'fixedTerm' && $chaildval[1] != 'New'){
                                if($end_date_of_contract == ''){
                                    $value[$key][$ckey]['error'] = true;
                                    $value[$key][$ckey]['errormessage'] .= "End Date Missed For <b> Fixed Term</b><hr>";                                    
                                }
                                
                            }
                            
                            $checkContractExist = Contract::select('id')
                            ->where('contract_type', $contract_type_id)
                            ->where('department_id',$department_id)
                            ->get();
    
    
                            $filenamelistOriginalName = '';
                            $filenamelist = '';
                             
                            if (isset($files)) {
                                $fileNameError = 0;
                                foreach ($files as $file) {
      
                                    
                                     $filenamelistOriginalName = $file->getClientOriginalName();
                                     
                                    if (strtolower($file->getClientOriginalName()) !== strtolower($chaildval['25'])) {
                                        $fileNameError++;
                                    }
                                }
                                
                                if(count($files) == $fileNameError){
                                    $error_rows++;
                                    $value[$key][$ckey]['error'] = true;
                                    $value[$key][$ckey]['errormessage'] .= "<b>" .$chaildval['25']."</b> File Name Mismatched With Excel and Attachment <br/>  <hr>";                                    
                                }
                            }
    
                            $cudo = 0;
                            if($value[$key][$ckey]['errormessage'] != ""){
                                $value[$key][$ckey]['errormessage'] = "<hr>".$value[$key][$ckey]['errormessage'];
                            }

                        }
    
                    }
                }
            }
            
        
            
        }catch (\Exception $e) {
            $errorMsg =  "Transaction failed: " . $e->getMessage();
            $error_rows++;
        }        
        
       if($error_rows == 0){

            DB::beginTransaction();
             
            try {
                $storedContracts = [];
                foreach ($value as $key => $val) {
                    $contract_type_data = ContractType::where('short_name', $key)->select('contract_type','contract_type_id')->first();
                    $contract_type_id = $contract_type_data->contract_type_id;
                    $cname = $contract_type_data->contract_type;
                    $roid = [];
                    $parentMissing = [];
        
                    foreach ($val as $ckey => $chaildval) {
                        if ($ckey > 1) {
                            
                            if (!isset($chaildval[1]) && !isset($chaildval[2]) && !isset($chaildval[3])) {
                                continue;
                            }
        
                            if (isset($chaildval[1])) {
                                
                                $selno = $chaildval[0];
                                $department_id =  EntityBusiness::where('name', $chaildval[2])->pluck('id')->first();
        
                                $catgoery_id =  ContractCategories::where('name', $chaildval[3])->pluck('id')->first();
        
                                $exclusivity = isset($chaildval[4]) ? $chaildval[4] : null;
        
                                $contract_description = isset($chaildval[5]) ? $chaildval[5] : null;
                                
                                $previous_contract_exits =  $chaildval[6];
                                $previous_contract_no =  $chaildval[7]; 
                                
                                $entityid = null;
                                $branchId = [];

                                $party1_type = isset($chaildval[8]) ? $chaildval[8] : null;
        
                                $party1_external_partyname = '';
        
                                if (isset($chaildval[8])) {
                                    
                                    if ($chaildval[8] == "Internal" || $party1_type == "Intergroup") {
                                        
                                        $party1_partyname = EntityMain::where(decrypt_datas('Nameoftheentity', 'entity'), [$chaildval[9]])
                                        ->value('id');
                                        $party1_internalBranch =  DB::table('branch')
                                        ->where(decrypt_datas('LegalName', 'branch'), [$chaildval[10]])
                                        ->value('id');
                                        
                                        $branchId[] = $party1_internalBranch;
                                        
                                    }else{
                                        $party1_external_partynames = ContractParties::pluck('company_name', 'id');
        
                                        $exneone = explode(":", $chaildval[11]);
            
                                        if(isset($exneone[0])){
            
                                            $chaildval[11] = $exneone[0];
            
                                        }
            
                                        foreach ($party1_external_partynames as $key1 => $party1_external_partynam) {
                                            if (trim(decryptString($party1_external_partynam, 'company_name')) == trim($chaildval[11])) {
                                                $party1_external_partyname =  $key1;
                                            }
                                            
                                           
                                        }
                                        
                                    }
           
                                } 

                                $party2_type = isset($chaildval[12]) ? $chaildval[12] : null;
        
                                $party2_external_partyname = '';

                                if ($chaildval[12] == "Internal" || $party2_type == "Intergroup") {
                                    $party2_partyname = EntityMain::where(decrypt_datas('Nameoftheentity', 'entity'), [$chaildval[13]])
                                        ->value('id');
                                        
                                    $party2_internalBranch =  DB::table('branch')
                                        ->where(decrypt_datas('LegalName', 'branch'), [$chaildval[14]])
                                        ->value('id');
                                        
                                    $branchId[] = $party2_internalBranch;
                                }else{
                                    $party2_external_partynames =  ContractParties::pluck('company_name', 'id');
                                    $exne = explode(":", $chaildval[15]);
                                    if(isset($exne[0])){
                                        $chaildval[15] = $exne[0];
                                    }
    
                                    foreach ($party2_external_partynames as $key2 => $party2_external_partynam) {
                                        if (trim(decryptString($party2_external_partynam, 'company_name')) == trim($chaildval[15])) {
                                            $party2_external_partyname =  $key2;
                                        }
                                    }
                                    if(!isset($party2_external_partyname) || $party2_external_partyname == null || $party2_external_partyname == ''){
                                        //continue;
                                    }
                                  
                                }

                            }
        
                    $owner = AddUsers::where(decrypt_datas('Email', 'AddUsers'), [$chaildval[16]])
                        ->pluck('id')
                        ->first();

                    $signatory = AddUsers::where(decrypt_datas('Email', 'AddUsers'), [$chaildval[17]])
                        ->pluck('id')
                        ->first();

                    $commencement_type = isset($chaildval[19]) ? $chaildval[19] : null;
                    $commencement_date = isset($chaildval[20]) ? dateImport($chaildval[20]) : null;
                    $contract_end_type = isset($chaildval[21]) ? $chaildval[21] : null;
                    $end_date_of_contract = isset($chaildval[22]) ? dateImport($chaildval[22]) : null;
                    
                    if ($chaildval[2] == 'New') {
                        $signing_date = null;
                    } else {
                        $signing_date = isset($chaildval[18]) ? dateImport($chaildval[18]) : $commencement_date;
                    }                        
                    

                    $currency = isset($chaildval[23]) ? $chaildval[23] : null;

                    $currency_value = isset($chaildval[24]) ? $chaildval[24] : null;

                    $payment_schedule =  isset($chaildval[39]) ? $chaildval[39] : null;

                    $type_of_renewal= isset($chaildval[26]) ? $chaildval[26] : null;

                    $payment_terms =  isset($chaildval[40]) ? $chaildval[40] : null;
                    $billing_frequency = isset($chaildval[41]) ? $chaildval[41] : null;

                    $taxes = isset($chaildval[42]) ? $chaildval[42] : null;
                    $escalation_clauses = isset($chaildval[43]) ? $chaildval[43] : null;
                    $discounts = isset($chaildval[44]) ? $chaildval[44] : null;
                    $retention = isset($chaildval[45]) ? $chaildval[45] : null;
                    $payment_escrow = isset($chaildval[46]) ? $chaildval[46] : null;
                    $financial_guarantees = isset($chaildval[47]) ?  $chaildval[47] : null;

                    $currency_conversion = isset($chaildval[48]) ?  $chaildval[48] : null;
                    
                    

					
                    if (isset($chaildval[49])) {

                        $party3_type = isset($chaildval[49]) ? $chaildval[49] : null;

                        $party3_external_partyname = '';
                       
                        if ($party3_type == "Internal" || $party3_type == "Intergroup") {
                            
                            $party3_partyname = EntityMain::where(decrypt_datas('Nameoftheentity', 'entity'), [$chaildval[50]])
                            ->value('id');
                            $party3_internalBranch =  DB::table('branch')
                            ->where(decrypt_datas('BranchName', 'branch'), [$chaildval[51]])
                            ->value('id');
                            
                            $branchId[] = $party3_internalBranch;
                            
                        }else{
                            $party3_external_partynames = ContractParties::pluck('company_name', 'id');

                            $exneone = explode(":", $chaildval[52]);

                            if(isset($exneone[0])){

                                $chaildval[52] = $exneone[0];

                            }

                            foreach ($party3_external_partynames as $key => $party3_external_partynam) {
                                if (trim(decryptString($party3_external_partynam, 'company_name')) == trim($chaildval[52])) {
                                    $party3_external_partyname =  $key;
                                }
                            }

                        }
                    } 

                    if (isset($chaildval[53])) {
                    
                        $party4_type = isset($chaildval[53]) ? $chaildval[53] : null;

                        $party4_external_partyname = '';

                        if ($party4_type == "Internal" || $party4_type == "Intergroup") {
                            $party4_partyname = EntityMain::where(decrypt_datas('Nameoftheentity', 'entity'), [$chaildval[54]])
                                ->value('id');
                                
                            $party4_internalBranch =  DB::table('branch')
                                ->where(decrypt_datas('BranchName', 'branch'), [$chaildval[55]])
                                ->value('id');
                                
                            $branchId[] = $party4_internalBranch;
                        }else{
                            $party4_external_partynames =  ContractParties::pluck('company_name', 'id');
                            $exne = explode(":", $chaildval[56]);
                            if(isset($exne[0])){
                                $chaildval[56] = $exne[0];
                            }

                            foreach ($party4_external_partynames as $key => $party4_external_partynam) {
                                if (trim(decryptString($party4_external_partynam, 'company_name')) == trim($chaildval[56])) {
                                    $party4_external_partyname =  $key;
                                }
                            }
                          
                        }
                    
                    }
                    
                    if (isset($chaildval[57])) {
                        $party5_type = isset($chaildval[57]) ? $chaildval[57] : null;

                        $party5_external_partyname = '';

                        if ($party4_type == "Internal" || $party5_type == "Intergroup") {
                            $party5_partyname = EntityMain::where(decrypt_datas('Nameoftheentity', 'entity'), [$chaildval[58]])
                                ->value('id');
                                
                            $party5_internalBranch =  DB::table('branch')
                                ->where(decrypt_datas('BranchName', 'branch'), [$chaildval[59]])
                                ->value('id');

                            $branchId[] = $party5_internalBranch;
                        }else{
                            $party5_external_partynames =  ContractParties::pluck('company_name', 'id');
                            $exne = explode(":", $chaildval[60]);
                            if(isset($exne[0])){
                                $chaildval[60] = $exne[0];
                            }

                            foreach ($party5_external_partynames as $key => $party5_external_partynam) {
                                if (trim(decryptString($party5_external_partynam, 'company_name')) == trim($chaildval[60])) {
                                    $party5_external_partyname =  $key;
                                }
                            }
                          
                        }
                                    
                    }                    


                    // return $chaildval;
                    $contractController = new ContractController();

                    if (count($branchId) > 0 && !empty($branchId[0])) {
                
                        $approval_user_column = "approval_required_users";
                        $approvalTypeGlobal = "0";
                        if (isset($chaildval[1]) && $chaildval[1] != 'New') {
                            $approval_user_column = "approval_required_users_legacy";
                            $approvalTypeGlobal = "legacy";
                        }   

                        $financialLimit = $contractController->financialLimit($branchId[0], $department_id, $catgoery_id, $contract_type_id, $currency_value, $approval_user_column);
                        
                        $financialLimitDecoded = json_decode($financialLimit)[0];
                
                        $signatory_data_decoded = (array)json_decode($financialLimitDecoded->signatory);
                        $app_type_data_decoded = (array)json_decode($financialLimitDecoded->approval_type);
                        $app_status_data_decoded = (array)json_decode($financialLimitDecoded->approval_status);
                
                        $signatory_array = (array)($signatory_data_decoded['sign']);
                        $owner_array = (array)($signatory_data_decoded['owner']);
                        $notifier_array = ((array)($signatory_data_decoded['notify'] ?? [])) ?? [];
                        $utf_array = ((array)($signatory_data_decoded['signutform'] ?? null)) ?? null;
                        $signatory_data_decoded = [];
                        $signatory_data_decoded['sign'] = $signatory_array[$approvalTypeGlobal];
                        $signatory_data_decoded['owner'] = $owner_array[$approvalTypeGlobal];
                        $signatory_data_decoded['notify'] = $notifier_array[$approvalTypeGlobal] ?? [];
                        $signatory_data_decoded['signutform'] = $utf_array[$approvalTypeGlobal] ?? [];
                        $financialLimitDecoded->signatory = json_encode($signatory_data_decoded);
                        $financialLimitDecoded->approval_type = $app_type_data_decoded[$approvalTypeGlobal];
                        $financialLimitDecoded->approval_status = $app_status_data_decoded[$approvalTypeGlobal]; 
                        
                        if($signatory){
                            $signatory_data_decoded['sign'] = $signatory;
                        }
                        
                        if($owner){
                            $signatory_data_decoded['owner'] = $owner;
                        }
                        
                        $financialLimitDecoded->signatory = json_encode($signatory_data_decoded);
                        
                        $all_approvers = json_decode($financialLimitDecoded->approver);
                
                
                
                
                        $branchHeads = BranchUser::select(
                            'id',
                            decrypt_data('branchheadname', 'branch'),
                            'Branchhead',
                            decrypt_data('departments', 'branch'),
                            decrypt_data('LegalName', 'branch')
                        )->where('id', $branchId[0])->first();
                
                
                        $branchHeadsError = [];
                        foreach ($all_approvers as $ap_data) {
                            if ($ap_data->type == 'designation') {
                                if ($ap_data->name == 'branch_head') {
                                    $branchHeadId = $branchHeads->Branchhead;
                                    if ($branchHeadId == null) {
                                        $branchHeadsError[] = "Branch Head Not Added in your selected Branch Please Update In Goal Portal";
                                    }
                                    $ap_data->id = $branchHeadId;
                                }
                                if ($ap_data->name == 'branch_dep_head') {
                                    $branchDeptData = unserialize($branchHeads->departments);
                                    if (!isset($branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')])) {
                                        $branchHeadsError[] = "Branch Department Head Not Added in your selected Branch Please Update In Goal Portal";
                                    } else {
                                        $ap_data->id = $branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')];
                                    }
                                }
                                if ($ap_data->name == 'overall_dept_head') {
                                    $entityDeptHead = EntityBusiness::select('overall_dept_head')->where('id', $request->input('BasicContract.DepartmentType'))->first();
                                    if (!$entityDeptHead || !$entityDeptHead->overall_dept_head) {
                                        $branchHeadsError[] = "Department Over All Head Not Added in your Entity Business Please Update In Goal Portal";
                                    } else {
                                        $ap_data->id = $entityDeptHead->overall_dept_head;
                                    }
                                }
                            }
                        }
                
                        $financialLimitDecoded->approver = json_encode($all_approvers);
                        
                        if (count($branchHeadsError) > 0) {
                            $error_rows++;
                            $errorMsg =  implode(',',$branchHeadsError);
                        }                        
                
                        $financialLimit = json_encode([$financialLimitDecoded]);                        
                    } else {
                        $financialLimit = '';
                    }

                    
                    // ============= Status Check ==================== //

                    
                    
                    $cur_date = date('Y-m-d');
                    if(isset($chaildval[1]) && $chaildval[1] === 'Legacy Contracts' && $end_date_of_contract != ''){
                        $contract_status = 'executed';
                        
                        if(strtotime($end_date_of_contract) > strtotime($cur_date)){
                            $contract_sub_status = 'active';
                        }elseif(strtotime($cur_date) > strtotime($end_date_of_contract)){
                            $contract_sub_status = 'expired';
                        }
                        
                    }elseif(isset($chaildval[1]) && $chaildval[1] === 'New'){
                        $contract_status = 'draft';
                        $contract_sub_status = 'draft';
                    }else{
                        $contract_status = 'draft';
                        $contract_sub_status = 'draft';
                    }
                    
                    if($contract_end_type == 'evergreen'){
                        $contract_status = 'executed';
                        $contract_sub_status = 'active';
                    }
                    
                    
                    if($contract_end_type == 'onetimeContract'){
                        $contract_status = 'executed';
                        if($end_date_of_contract != ''){
                            if(strtotime($end_date_of_contract) > strtotime($cur_date)){
                                $contract_sub_status = 'active';
                            }elseif(strtotime($cur_date) > strtotime($end_date_of_contract)){
                                $contract_sub_status = 'completed';
                            }
                        }else{
                            $contract_sub_status = 'active';
                        }
                        
                    }

                    // ============= Status Check End ==================== //
                    
                    
                    //Update Billing Value
                    $billingFreqArr = [ 'Weekly'=>52,'Monthly'=>12, 'Quarterly'=>4, 'Annually'=>1, 'Onetime'=>1 ]; 
                    $billFrequ = $billing_frequency; 
                    $billValue = ""; 
                    $totValue = "";
                    $contValue = $currency_value; 
                    if($billFrequ != "" && $billValue == "" && $contValue != ""){ 
                        $billFrequ_val = $billingFreqArr[$billFrequ]; 
                        $billValue = $contValue/$billFrequ_val;
                        $fixedTerm = $contract_end_type; 
                        if ($fixedTerm != 'evergreen' && !empty($commencement_date) && !empty($end_date_of_contract)) { 
                            $dateSt = new DateTime($commencement_date); 
                            $dateEd = new DateTime($end_date_of_contract); 
                            $daysBt = $dateSt->diff($dateEd)->days; 
                            $yearCal = round($daysBt/365); 
                            $totValue = $contValue*$yearCal;
                            
                        }
                    }

                    //Owner/Initiator Validation
                    $owner_initiator = session()->get('contractSessionUser');
                
                    $initiatior_exists = AddUsers::select('id',  decrypt_data('AccessScope', 'AddUsers'))
                                ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
                                ->first();        
                    if(!$initiatior_exists)
                    {
                        $invalid_owner_error = array('Owner Not Available Please Contact Administrator');
                        return redirect('contracts/builk-import')->withErrors(array_merge($invalid_owner_error))->withInput();
                    }   
                        
                    $owner_initiator_id = $initiatior_exists->id;
                    
                    $contract = Contract::create([
                        'contract_mode' => isset($chaildval[1]) && $chaildval[1] === 'New' ? encryptString('new', 'contract_mode') : encryptString('old', 'contract_mode'),
                        'contract_name' => '',
                        'contract_type' => $contract_type_id,
                        'contract_tags' => json_encode([$contract_type_id]),
                        'contract_description' => encryptString($contract_description, 'contract_description'),

                        'department_id' => $department_id,
                        'catgoery_id' => $catgoery_id,

                        'signatory' => $signatory,
                        'owner' => $owner,

                        // Contract Duration
                        'signing_date' => $signing_date,

                        'commencement_type'=>$commencement_type,
                        'end_contract_type'=>encryptString($contract_end_type, 'end_contract_type'),
                        'commencement_date'=>$commencement_date,


                        'fixed_date'=>$commencement_date,

                        'contract_end_date' => $end_date_of_contract,
                        
                        'contract_status'=>$contract_status,
                        'substatus'=>$contract_sub_status,
                        'renewal_type'=>encryptString('manualRenewal', 'renewal_type'),
                        'reminder_first_alert' => encryptString('Contract End Date', 'reminder_first_alert'),
                        'reminder_first_alertMeOn' => encryptString('1 days prior', 'reminder_first_alertMeOn'),
                        'reminder_first_alert_repeats' => encryptString('Daily', 'reminder_first_alert_repeats'),

                        'reminder_second_alert' =>  encryptString('Contract End Date', 'reminder_second_alert'),
                        'reminder_second_alertMeOn' => encryptString('1 days prior', 'reminder_second_alertMeOn'),
                        'reminder_second_alert_repeats' => encryptString('Daily', 'reminder_second_alert_repeats'),

                        'reminder_escalation_alert' =>  encryptString('Contract End Date', 'reminder_escalation_alert'),
                        'reminder_escalation_alertMeOn' => encryptString('1 days prior', 'reminder_escalation_alertMeOn'),
                        'reminder_escalation_alert_repeats' => encryptString('Daily', 'reminder_escalation_alert_repeats'),
                        
                        'reminder_escalation_alert_after' =>  encryptString('Contract End Date', 'reminder_escalation_alert_after'),
                        'reminder_escalation_alertMeOn_after' => encryptString('1 days after', 'reminder_escalation_alertMeOn_after'),
                        'reminder_escalation_alert_repeats_after' => encryptString('Daily', 'reminder_escalation_alert_repeats_after'),


                        // Contract Value
                        'currency' => encryptString($currency, 'currency'),
                        'billing_value' => encryptString($billValue, 'billing_value'),
                        'currency_value' => encryptString($currency_value, 'currency_value'),
                        'total_value' => encryptString($totValue, 'total_value'),                                    
                        'payment_schedule' => encryptString($payment_schedule, 'payment_schedule'),
                        'currency_contract' => encryptString($currency, 'currency_contract'),
                        'payment_terms' => $payment_terms,
                        'billing_frequency' => encryptString($billing_frequency, 'billing_frequency'),
                        'taxes' => encryptString($taxes, 'taxes'),
                        'escalation_clauses' => encryptString($escalation_clauses, 'escalation_clauses'),
                        'discounts' => encryptString($discounts, 'discounts'),
                        'retention' => encryptString($retention, 'retention'),
                        'payment_escrow' => encryptString($payment_escrow, 'payment_escrow'),
                        'financial_guarantees' => encryptString($financial_guarantees, 'financial_guarantees'),
                        'rules_id' => $financialLimit,
                        'exclusivity' => encryptString($exclusivity, 'exclusivity'),
                        'contract_attachment' => 0,
                        'created_by' => $owner_initiator_id
                    ]);
                    
                    //History Creation
                    $contracthis = Contract::select('*')->where('id', $contract->id)->first();
                    // $contractHistory = ContractHistory::create([
                    //             'contract_name' => $contracthis->contract_name,
                    //             'id' => $contract->id,
                    //             'contract_mode' => $contracthis->contract_mode,
                    //             'contract_type' => $contracthis->contract_type,
                    //             // 'contract_name' => $contract->/ 'contract_name,
                    //             'contract_description' => $contracthis->contract_description,
                    //             'contract_priority' => $contracthis->contract_priority,
                                
                    //             'department_id' => $contracthis->department_id,
                    //             'catgoery_id' => $contracthis->catgoery_id,
                    
                    //             'signatory' => $contracthis->signatory,
                    //             'owner' => $contracthis->owner,
                    
                    
                    //             'confidentialityagreement' => $contracthis->confidentialityagreement,
                    //             'exclusivity' => $contract->exclusivity,
                    
                    //             // Contract Duration
                    //             'signing_date' => $contracthis->signing_date,
                    //             'commencement_type' => $contracthis->commencement_type,
                    //             'fixed_date' => $contracthis->fixed_date,
                    //             'event_name' => $contracthis->event_name,
                    //             'end_contract_type' => $contracthis->end_contract_type,
                    //             'contract_end_date' => $contracthis->contract_end_date,
                    //             'renewal_type' => $contracthis->renewal_type,
                    //             'period_auto_renewal' => $contracthis->period_auto_renewal,
                    //             'period_auto_renewal_unit' => $contracthis->period_auto_renewal_unit,
                    //             'auto_renewal_date' => $contracthis->auto_renewal_date,
                    //             'manual_renewal_date' => $contracthis->manual_renewal_date,
                    //             'evergreen_condition' => $contracthis->evergreen_condition,
                    //             'termination_date' => $contracthis->termination_date,
                    //             'termination_reason' => $contracthis->termination_reason,
                    
                    
                    //             // Contract Value
                    //             'currency' => $contracthis->currency,
                    //             'billing_value' => $contracthis->billing_value,
                    //             'currency_value' => $contracthis->currency_value,
                    //             'total_value' => $contracthis->total_value,            
                    //             'payment_schedule' => $contracthis->payment_schedule,
                    //             'currency_contract' => $contracthis->currency_contract,
                    //             'payment_terms' => $contracthis->payment_terms,
                    //             'billing_frequency' => $contracthis->billing_frequency,
                    //             'taxes' => $contracthis->taxes,
                    //             'escalation_clauses' => $contracthis->escalation_clauses,
                    //             'discounts' => $contracthis->discounts,
                    //             'retention' => $contracthis->retention,
                    //             'payment_escrow' => $contracthis->payment_escrow,
                    //             'financial_guarantees' => $contracthis->financial_guarantees,
                    //             'currency_conversion' => $contracthis->currency_conversion,
                    
                    //             // Reminder Value
                    //             'reminder_first_alert' => $contracthis->reminder_first_alert,
                    //             'reminder_first_alertMeOn' => $contracthis->reminder_first_alertMeOn,
                    //             'reminder_first_alert_repeats' => $contracthis->reminder_first_alert_repeats,
                    //             'reminder_second_alert' => $contracthis->reminder_second_alert,
                    //             'reminder_second_alertMeOn' => $contracthis->reminder_second_alertMeOn,
                    //             'reminder_second_alert_repeats' => $contracthis->reminder_second_alert_repeats,
                    //             'reminder_escalation_alert' => $contracthis->reminder_escalation_alert,
                    //             'reminder_escalation_alertMeOn' => $contracthis->reminder_escalation_alertMeOn,
                    //             'reminder_escalation_alert_repeats' => $contracthis->reminder_escalation_alert_repeats,
                    //             'reminder_escalation_alert_after' => $contracthis->reminder_escalation_alert_after,
                    //             'reminder_escalation_alertMeOn_after' => $contracthis->reminder_escalation_alertMeOn_after,
                    //             'reminder_escalation_alert_repeats_after' => $contracthis->reminder_escalation_alert_repeats_after,
                    //             // 'rules_id' => $contracthis->rules_id,
                    //             'rules_id' => $contracthis->rules_id,
                    //             'contract_status'=>$contracthis->contract_status,
                    //             'substatus'=>$contracthis->substatus,                                
                    //             'custom_fields_data' => $contracthis->custom_fields_data,
                    //             'contract_attachment' => $contracthis->contract_attachment,
                    //             'contract_attachment_filename' => $contracthis->contract_attachment_filename,
                    //             'created_by' => $contracthis->created_by
                    //         ]);
                            
                    if ($party1_type == 'Internal' || $party1_type == "Intergroup") {
                        ContractPartyData::create([
                            'custom_field_group_id' => $contract->id,
                            'contract_party_type' => $party1_type,
                            'contract_party_id' => $party1_partyname,
                            'contract_party_exe_id' => null,
                            'contract_party_location_id' => $party1_internalBranch,
                        ]);
                        
                        $entityid = $party1_partyname;
                        
                    
                    } else {
                        ContractPartyData::create([
                            'custom_field_group_id' => $contract->id,
                            'contract_party_type' => $party1_type,
                            'contract_party_id' => null,
                            'contract_party_exe_id' => $party1_external_partyname,
                            'contract_party_location_id' => null,
                        ]);
                        
                        $entityid = $party1_external_partyname;
                    //$branchId = 0;
                    }
                    
                    if ($party2_type == 'Internal' || $party2_type == "Intergroup") {
                        ContractPartyData::create([
                            'custom_field_group_id' => $contract->id,
                            'contract_party_type' => $party2_type,
                            'contract_party_id' => $party2_partyname,
                            'contract_party_exe_id' => null,
                            'contract_party_location_id' => $party2_internalBranch,
                        ]);
                    } else {
                        ContractPartyData::create([
                            'custom_field_group_id' => $contract->id,
                            'contract_party_type' => $party2_type,
                            'contract_party_id' => null,
                            'contract_party_exe_id' => $party2_external_partyname,
                            'contract_party_location_id' => null,
                        ]);
                    }
                    
                    if(isset($party3_type)){
                        if ($party3_type == 'Internal' || $party3_type == "Intergroup") {
                            ContractPartyData::create([
                                'custom_field_group_id' => $contract->id,
                                'contract_party_type' => $party3_type,
                                'contract_party_id' => $party3_partyname,
                                'contract_party_exe_id' => null,
                                'contract_party_location_id' => $party3_internalBranch,
                            ]);
                        } else {										
                            ContractPartyData::create([
                                'custom_field_group_id' => $contract->id,
                                'contract_party_type' => $party3_type,
                                'contract_party_id' => null,
                                'contract_party_exe_id' => $party3_external_partyname,
                                'contract_party_location_id' => null,
                            ]);
                        } 
                    }
                    
                    if(isset($party4_type)){
                        if ($party4_type == 'Internal' || $party4_type == "Intergroup") {
                            ContractPartyData::create([
                                'custom_field_group_id' => $contract->id,
                                'contract_party_type' => $party4_type,
                                'contract_party_id' => $party4_partyname,
                                'contract_party_exe_id' => null,
                                'contract_party_location_id' => $party4_internalBranch,
                            ]);
                        } else {
                            ContractPartyData::create([
                                'custom_field_group_id' => $contract->id,
                                'contract_party_type' => $party4_type,
                                'contract_party_id' => null,
                                'contract_party_exe_id' => $party4_external_partyname,
                                'contract_party_location_id' => null,
                            ]);
                        }
                    }
                    
                    if(isset($party5_type)){
                        if ($party5_type == 'Internal' || $party5_type == "Intergroup") {
                            ContractPartyData::create([
                                'custom_field_group_id' => $contract->id,
                                'contract_party_type' => $party5_type,
                                'contract_party_id' => $party5_partyname,
                                'contract_party_exe_id' => null,
                                'contract_party_location_id' => $party5_internalBranch,
                            ]);
                        } else {
                            ContractPartyData::create([
                                'custom_field_group_id' => $contract->id,
                                'contract_party_type' => $party5_type,
                                'contract_party_id' => null,
                                'contract_party_exe_id' => $party5_external_partyname,
                                'contract_party_location_id' => null,
                            ]);
                        }
                    }
                    
                    if (isset($party1_partyname) &&  $party1_partyname != "") {
                        $partyUnique = $party1_partyname;
                    }else{
                        $partyUnique = $party2_external_partyname;
                    }
                    
                    $con_code = sprintf('%04d',$contract->id);
                    $unique_code = "CON".$entityid.$department_id.$catgoery_id.$partyUnique.$con_code;
                    
                    Contract::where('id', $contract->id)->update(['contract_unique_id' => $unique_code]);

                    $nextAprroverEmail = "";
                    
                    if(isset($chaildval[1])){
                        $contract_status = 'draft';
                        $contract_sub_status = 'draft';
                        
                        //=========== Approval Insert Process ==============//
                        $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $owner)->get();
                        $appArr = json_decode(trim($financialLimit));
                        $randNo = rand(0, 99999);
                        

                        if(is_array($appArr) && count($appArr) > 0 && isset($users[0])){
                            $approval_type = $appArr[0]->approval_type;
                            $approval_status = $appArr[0]->approval_status;
                            
                            
                            if ($approval_status == 'required') {
                                $unique_id = $contract->id . $randNo;
                                $statusPreApprvr = 'Draft';
                                $statusApprvr = 'Draft';
                                $subStatusApprvr = 'Initial Draft';
                                if ($chaildval[1] != 'New') {
                                    $statusPreApprvr = 'Negotiation';
                                    $statusApprvr = 'Approval';
                                    $subStatusApprvr = 'Pending Approval';
                                    $approvalArr = json_decode($appArr[0]->approver);
                                    foreach ($approvalArr as $key => $appVal) {
                                        $approver_id = $appVal->id;
                                        $users = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();
                                        ApprovalContracts::create([
                                            'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                            'previous_status' => encryptString($statusPreApprvr, 'previous_status'),
                                            'status' => encryptString($statusApprvr, 'status'),
                                            'contract_id' => $contract->id,
                                            'orderval' => $key,
                                            'unique_id' => $unique_id,
                                            'flag' => 1,
                                            'approval_status' => encryptString('pending', 'approval_status'),
                                        ]);
                                        if ($approval_type == 'sequential'){
                                            $nextAprroverEmail = $users[0]->Email;
                                            break;
                                        }else{
                                            if($nextAprroverEmail == ""){
                                                $nextAprroverEmail = [];
                                            }
                                            $multipleNextApprovers = true;
                                            $nextAprroverEmail[] = $users[0]->Email;
                                        }
                                    }
                                }else{
                                    ApprovalContracts::create([
                                        'username' => encryptString(json_encode(['email' => $users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                        'previous_status' => encryptString('Draft', 'previous_status'),
                                        'status' => encryptString('Draft', 'status'),
                                        'contract_id' => $contract->id,
                                        'orderval' => 0,
                                        'unique_id' => $unique_id,
                                        'flag' => 1,
                                        'approval_status' => encryptString('pending', 'approval_status'),
                                    ]);
                                    
                                    $nextAprroverEmail = $users[0]->Email;
                                }
                            } else {
                                $statusPreApprvr = 'Approval';
                                $statusApprvr = 'Signing';
                                $subStatusApprvr = 'Approved';
            
                                $users = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'), decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $signatory)->get();
                                
                                if ($chaildval[1] != 'New') {
                                    $statusApprvr = 'executed';
                                    $subStatusApprvr = 'active';  
                                    
                                    $cur_date = date('Y-m-d');
                                    
                                    //$end_date_of_contract = $end_date_of_contract;
                                    //$contract_end_type = $request->input('Duration.effectiveDate');
                                    
                                    if (strtotime($cur_date) > strtotime($end_date_of_contract) && $subStatusApprvr == 'active') {
                                        if( $subStatusApprvr == 'active'){
                                            if($contract_end_type == 'onetimeContract'){
                                                $subStatusApprvr = 'completed';
                                            }
                                            if($contract_end_type == 'fixedTerm'){
                                                $subStatusApprvr = 'expired';
                                            }
                                        }
                                    }                         
                                }                                
                            }
                            
                            
                            Contract::where('id', $contract->id)->update(['contract_status' => $statusApprvr, 'substatus' => $subStatusApprvr]);

                        }
                        //=========== Approval Insert Process End ==============// 
                    }

                    $namePartygroup = $cname . '-';

                    if ($party1_type == 'Internal' || $party1_type == "Intergroup") {
                        $namePartygroup .= $chaildval[9] . ',';
                    } else {
                        $namePartygroup .= $chaildval[11] . ',';
                    }
                    if ($party2_type == 'Internal' || $party2_type == "Intergroup") {
                        $namePartygroup .= $chaildval[13] . ',';
                    } else {
                        $namePartygroup .= $chaildval[15] . ',';
                    }
                    if (isset($party3_type)){
                        if ($party3_type == 'Internal' || $party3_type == "Intergroup") {
                            $namePartygroup .= $chaildval[50] . ',';
                        } else {
                            $namePartygroup .= $chaildval[52] . ',';
                        }
                    }
                    if (isset($party4_type)){
                        if ($party4_type == 'Internal' || $party4_type == "Intergroup") {
                            $namePartygroup .= $chaildval[54] . ',';
                        } else {
                            $namePartygroup .= $chaildval[56] . ',';
                        }
                    }
                    if (isset($party5_type)){
                        if ($party5_type == 'Internal' || $party5_type == "Intergroup") {
                            $namePartygroup .= $chaildval[58] . ',';
                        } else {
                            $namePartygroup .= $chaildval[60] . ',';
                        }
                    }



                    $namePartygroup = rtrim($namePartygroup, ',');

                    $namePartygroup .= '-' . date("Y");

                    $namePartygroup = encryptString($namePartygroup, 'contract_name');
                    
                    
                    $roid[$chaildval[0]] = $contract->id;
                    
                    
                    if($previous_contract_exits != 'No' && $previous_contract_exits != '' ){
                        $previous_contract_exits;
                        $parid = null;
                        if($previous_contract_exits == 'Yes-In Software'){
                             $parid = Contract::where('contract_unique_id', $previous_contract_no)->pluck('id')->first(); 
                        }
                        if($previous_contract_exits == 'Yes-In This file'){
                             if(isset($roid[$previous_contract_no])){
                                $parid = $roid[$previous_contract_no];
                             }else{
                                 $parentMissing[$contract->id] = $previous_contract_no;
                                 $parid = 0;
                             }
                        }
                        // $previous_contract_no; 
                        
                        //Only Upload If Parent Contract Exist
                        if($parid > 0){
                            Contract::where('id', $parid)->update(['substatus'=> 'renewed']);
                            Contract::where('id', $contract->id)->update(['parentcontract'=>$parid]);
                        }
                    }
                    Contract::where('id', $contract->id)->update(['contract_name' => $namePartygroup ]);

                    $customFields = CustomFields::where('status', 1)->where('contract_type', $contract_type_id)->orderBy('order_id')->pluck('custom_field_id');


                    $filenamelistOriginalName = '';
                    $filenamelist = '';
                     
                    if (isset($files)) {
                        foreach ($files as $file) {

                            
                             $filenamelistOriginalName .= $file->getClientOriginalName();
                             
                            if ($file->getClientOriginalName() == $chaildval['25']) {
                                
                                $filenamelist .= $chaildval['25'];
                                
                                $ss = $controller->storeFile($file, $namePartygroup . '-' . $contract->id, $contract->id);

                                $filename = file_name($file);
                                
                                if(env('send_email_on_bulk_upload')){
                                
                                    $finalNotifiers = "";
                                    
                                    //For Getting Notifiers List
                                    if(isset($signatory_data_decoded['notify']) && count($signatory_data_decoded['notify']) > 0){
                                        $finalNotifiers = $signatory_data_decoded['notify'];
                                        $finalNotifiers = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->whereIn('id', $finalNotifiers)->pluck('Email')->toArray();
                                    }
                                    
                                    if(isset($all_approvers) && count($all_approvers) > 0){
                        
                                        $approversArr = [];
                                        foreach($all_approvers as $app_data){
                                            $approversArr[] = $app_data->id;
                                        }
                                        
                                        $approversArr = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->whereIn('id', $approversArr)->pluck('Email')->toArray();
                                        
                                        if($finalNotifiers == ""){
                                            $finalNotifiers = [];
                                        } 
                                        
                                        $finalNotifiers = array_unique(array_merge($finalNotifiers, $approversArr));
                                    }
                        
                                    if ($nextAprroverEmail != "") {
                                        $controller->changePermission($ss, $finalNotifiers, $nextAprroverEmail);
                                        $emailTrigger = new ContractNotificationController();
                                        $MailSent = $emailTrigger->sendEmail($contract->id, '', '', $nextAprroverEmail, 'Legacy Contract Upload Alert', $filename,  $ss, 'newContract');
                                    }  
                                }
                                Contract::where('id', $contract->id)->update(['contract_attachment' => $ss, 'contract_attachment_filename'=>$filename]);
                            }
                        }
                    }

                    $cudo = 0;

                    foreach ($chaildval as $cckey => $customField) {

                        if ($cckey > 60 && is_numeric($cckey)) {
                            if (isset($customField)) {
                                CustomFieldsData::create([
                                    'custom_field_id' => $customFields[$cudo],
                                    'custom_field_group' => 'contracts',
                                    'custom_field_value' =>  $customField,
                                    'custom_field_group_id' => $contract->id
                                ]);
                            }
                            $cudo++;
                        }
                    }
                    $storedContract = $contract->id ?? 0;
                    
                    if($storedContract > 0){
                        $storedContracts[] = $storedContract;
                    }
                            DB::commit();
                            }
                        }
                    }
                    
                    foreach($parentMissing as $ky => $mPc){
                        //Update Parent Contract
                        if($roid[$mPc] > 0 && $ky > 0){
                            Contract::where('id', $roid[$mPc])->update(['substatus'=> 'renewed']);
                            Contract::where('id', $ky)->update(['parentcontract'=>$roid[$mPc]]);
                        }                        
                    }
                
            
                
            }catch (\Exception $e) {
            // If something goes wrong, roll back the transaction
                DB::rollBack();
                $error_rows++;
                $errorMsg =  "Transaction failed: " . $e->getMessage(). "<-->".$e->getLine();
            }
            
            if($error_rows == 0){
                $dataStored = [];
                if(count($storedContracts) > 0){
                    $allContractsStored = Contract::select('*')->whereIn('id', $storedContracts)->get();
                    $dataStored = $this->availableContracts($allContractsStored, true);
                }
                return redirect('/contracts/reports/imported')->with('success', 'Files uploaded successfully.')->with('dataStored', $dataStored);
            }else{
                DB::rollBack();
                return redirect('/contracts/reports/imported')->with('error', 'Files Not Uploaded. Issue On '. $errorMsg.json_encode($chaildval));
            }
       }else{
           $this->setSessionForSupportData();
           session(['errorPresent' => $error_rows]);
           return redirect('/contracts/builk-import')->with('error', 'Files Not Uploaded. '.$errorMsg)->with('data', $value)->with('errorPresent', $error_rows);
       }
    }

    public function uploadFile(Request $request)
    {

        // Define your arrays
        $checkType = ['New', 'Legacy Contracts'];
        
        $checkDepartment = EntityBusiness::pluck('name')->toArray();
        
        $checkCategory = ContractCategories::pluck('name')->toArray();
         
        $checkExclusivity = ['Non Exclusive', 'Mutually Exclusive', 'Exclusive to Contracting Party', 'Exclusivity to Company'];
        
        $checkPartyType = ['Internal', 'External', 'Intergroup'];
   
         
        $checkInternalPartyNameTemp = EntityMain::select(decrypt_data('Nameoftheentity', 'entity'))->get();
        $checkInternalPartyName = [];
        foreach($checkInternalPartyNameTemp as $checkInternalPartyNam){
            $checkInternalPartyName[] = $checkInternalPartyNam->Nameoftheentity;
        }
    
        
         
        $checkPartyInternalLocationTemp = DB::table('branch')->select(decrypt_data('LegalName', 'branch'))->get(); 
        $checkPartyInternalLocation = [];
        foreach($checkPartyInternalLocationTemp as $checkPartyInternalLocatio){
            $checkPartyInternalLocation[] = $checkPartyInternalLocatio->LegalName;
        } 
        
        
         
        
        $checkPartyExternalPartyNameTemp =  ContractParties::select('company_name', 'state', 'gst')->get();
        $checkPartyExternalPartyName = [];
        foreach ($checkPartyExternalPartyNameTemp as $contractPartie) {
            if (decryptString($contractPartie, 'company_name') != null) {
                
                $cname  = decryptString($contractPartie->company_name, 'company_name');
                if (isset($contractPartie->state) && $contractPartie->state > 0) {

                    $state = State::select("name", "id")

                        ->where('id', $contractPartie->state)

                        ->pluck('name')->first();

                    $cname .= ':'.$state;

                }

                if (isset($contractPartie->gst)) {

                    $cname .= ':'.decryptString($contractPartie->gst, 'gst');

                }
             $checkPartyExternalPartyName[] = $cname;
 
            }
              
            
        }  
           
        $checkCoordinator = AddUsers::pluck(decrypt_data('Email', 'AddUsers'))->toArray();


        $file = $request->file('file');
        $filePath = $file->getPathname();
        
        $testAgainstFormats = [
            IOFactory::READER_XLS,
            IOFactory::READER_HTML,
        ];        

        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($filePath);
            // //$reader = IoFactory::createReader('Xlsx');
            // // $spreadsheet = $spreadsheetReaser->load($filePath);
            // //$reader->setFlags(IReader::IGNORE_EMPTY_CELLS | IReader::LOAD_WITH_CHARTS);
            // //$reader->setLoadAllSheets();
            // //$reader->load($filePath); 
            // $spreadsheet->setReadDataOnly(true);
            
            // $reader = new Xlesx();
            // $reader->setReadDataOnly(true); // much lower memory use
            // $spreadsheet = $reader->load($filePath);            
            

            $data = [];

            // Loop through each sheet in the spreadsheet
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                    if ($sheet->getSheetState() === \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VISIBLE) {

                $sheetData = [];
                $sheetName = $sheet->getTitle();

                // Loop through each row of the worksheet
                foreach ($sheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false); // Loop through all cells, even if not set

                    $rowData = [];
                    foreach ($cellIterator as $cell) {
                        $cellValue = $cell->getValue();
                        if($cell->isFormula()){
                            $cellValue = $cell->getCalculatedValue();
                        }

                        if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {

                            // Get the formatted value as date

                            $cellValue = $cell->getFormattedValue();

                        }
                        $rowData[] = $cellValue;

                        // For numeric values, you might prefer to use getValue() if formatted value is not suitable
                    }
                    
                    
                    
                    $sheetData[] = $rowData;
                }

                // Add the sheet data to the main data array with the sheet name as the key
                $data[$sheetName] = $sheetData;
            }
            }

        //return redirect()->back()->with('success', 'Files uploaded successfully.');
            // Pass the data to the view or redirect with the data
            session(['datafull' => $data]);
            
            $this->setSessionForSupportData();

            return redirect()->back()->with('data', $data)->with('checkType', $checkType)
            ->with('checkDepartment', $checkDepartment)
            ->with('checkCategory', $checkCategory)
            ->with('checkExclusivity', $checkExclusivity)
            ->with('checkPartyType', $checkPartyType)
            ->with('checkInternalPartyName', $checkInternalPartyName)
            ->with('checkPartyInternalLocation', $checkPartyInternalLocation)
            ->with('checkPartyExternalPartyName', $checkPartyExternalPartyName)
            ->with('checkCoordinator', $checkCoordinator);
        } catch (\Exception $e) {
            echo $e->getMessage();
            die;
            // Handle exception
            return redirect()->back()->with('error', 'Error loading spreadsheet: Invalid File Format - '.$e->getMessage());
        }
    }

    public function setSessionForSupportData($skipOthers=false){

            $checkType = ['New', 'Legacy Contracts'];
            
            $checkDepartment = EntityBusiness::pluck('name')->toArray();
            
            $checkCategory = ContractCategories::pluck('name')->toArray();
             
            $checkExclusivity = ['Non Exclusive', 'Mutually Exclusive', 'Exclusive to Contracting Party', 'Exclusivity to Company'];
            
            $checkPartyType = ['Internal', 'External', 'Intergroup'];
       
             
            $checkInternalPartyNameTemp = EntityMain::select(decrypt_data('Nameoftheentity', 'entity'))->get();
            $checkInternalPartyName = [];
            foreach($checkInternalPartyNameTemp as $checkInternalPartyNam){
                $checkInternalPartyName[] = $checkInternalPartyNam->Nameoftheentity;
            }
        
            
             
            $checkPartyInternalLocationTemp = DB::table('branch')->select(decrypt_data('LegalName', 'branch'))->get(); 
            $checkPartyInternalLocation = [];
            foreach($checkPartyInternalLocationTemp as $checkPartyInternalLocatio){
                $checkPartyInternalLocation[] = $checkPartyInternalLocatio->LegalName;
            } 
            
            
             
            
            $checkPartyExternalPartyNameTemp =  ContractParties::select('company_name', 'state', 'gst')->get();
            $checkPartyExternalPartyName = [];
            foreach ($checkPartyExternalPartyNameTemp as $contractPartie) {
                if (decryptString($contractPartie, 'company_name') != null) {
                    
                    $cname  = decryptString($contractPartie->company_name, 'company_name');
                    if (isset($contractPartie->state) && $contractPartie->state > 0) {
    
                        $state = State::select("name", "id")
    
                            ->where('id', $contractPartie->state)
    
                            ->pluck('name')->first();
    
                        $cname .= ':'.$state;
    
                    }
    
                    if (isset($contractPartie->gst)) {
    
                        $cname .= ':'.decryptString($contractPartie->gst, 'gst');
    
                    }
                 $checkPartyExternalPartyName[] = $cname;
     
                }
                  
                
            }  
               
            $checkCoordinator = AddUsers::pluck(decrypt_data('Email', 'AddUsers'))->toArray();
        
            session(['checkType' => $checkType]);
            session(['checkDepartment' => $checkDepartment]);
            session(['checkCategory' => $checkCategory]);
            session(['checkExclusivity' => $checkExclusivity]);
            session(['checkPartyType' => $checkPartyType]);
            session(['checkInternalPartyName' => $checkInternalPartyName]);
            session(['checkPartyInternalLocation' => $checkPartyInternalLocation]);
            session(['checkPartyExternalPartyName' => $checkPartyExternalPartyName]);
            session(['checkCoordinator' => $checkCoordinator]);
    }

    public function contractBuilkImport(Request $request)
    {

        $contractTypes = ContractType::get();
        return view('contract::contractimport.contractBuilkImport')->with('contractTypes', $contractTypes);
    }

    public function templateDownload(Request $request)
    {


        $rules = [
            "ContractType" =>'required'
        ];

        $validator =  Validator::make($request->all(), $rules);
        
       
        if($validator->fails()) {
            $errors = $validator->errors();
            return redirect('/contracts/builk-import')->withErrors($validator)->withInput();
        }
        
        $spreadsheet = new Spreadsheet();
        $writer = new Xlsx($spreadsheet);

        $sheet = $spreadsheet->getActiveSheet();

        $contractTypes = ContractType::get();


        $categorys = Category::where('category_group', 'contract')->get();
        $contractTypes = ContractType::get();


        $entities = EntityMain::select('id', decrypt_data('Nameoftheentity', 'entity'))
            ->get();
        //$contractParties =  ContractParties::select('*')->get();

        $users = AddUsers::select('id',  decrypt_data('FirstName', 'AddUsers'))->get();

 
         $contractParties =  ContractParties::select('company_name', 'state', 'gst')->get();
        $contractPartiesData = [];
        foreach ($contractParties as $contractPartie) {

                $cname  = decryptString($contractPartie->company_name, 'company_name');

                if($cname != ""){ 
                    if (isset($contractPartie->state) && $contractPartie->state > 0) {
    
                        $state = State::select("name", "id")
    
                            ->where('id', $contractPartie->state)
    
                            ->pluck('name')->first();
    
                        $cname .= ':'.$state;
    
                    }
    
                    if (isset($contractPartie->gst)) {
    
                        $cname .= ':'.decryptString($contractPartie->gst, 'gst');
    
                    }

                    $contractPartiesData[] = $cname;
                }
        }

        $contractPartiesDataArr = $contractPartiesData;
        $contractPartiesDataArrCount = count($contractPartiesDataArr);







        $ent =  '"' . EntityBusiness::pluck('name')->implode(', ') . '"';
        $entArr =  EntityBusiness::pluck('name');
        
        // echo $ent;die;

        // $catego =  '"' . ContractCategories::pluck('name')->implode(', ') . '"';


        $branchs = '"' . Branch::select(decrypt_data('LegalName', 'branch'))->pluck('LegalName')->implode(', ') . '"';
        $branchArr = Branch::select(decrypt_data('LegalName', 'branch'))->pluck('LegalName');


        $catego =  '"' . ContractCategories::pluck('name')->implode(', ') . '"';
        $categoArr =   ContractCategories::pluck('name');
        
        $categoArrCount = count($categoArr);

        $entities = '"' . EntityMain::select(decrypt_data('Nameoftheentity', 'entity'))
            ->pluck('Nameoftheentity')->implode(', ') . '"';


        $entitiesArr = EntityMain::select(decrypt_data('Nameoftheentity', 'entity'))
            ->pluck('Nameoftheentity');
            
        $entitiesArrCount = count($entitiesArr);


        $users = '"' . AddUsers::select(decrypt_data('Email', 'AddUsers'))->pluck('Email')->implode(', ') . '"';
        $usersArr = AddUsers::select(decrypt_data('Email', 'AddUsers'))->pluck('Email');
        
        $usersArrCount = count($usersArr);
        if (isset($request->ContractType)) {
            $key = array_search(0, $request->ContractType);
            // Get contract types from your model
            if ($key !== false) {
                $ContractTypes = ContractType::pluck('short_name', 'contract_type_id')->toArray();
            } else {
                $ContractTypes = ContractType::whereNotIn('contract_type_id', [0])
                    ->whereIn('contract_type_id', $request->ContractType)
                    ->pluck('short_name', 'contract_type_id')
                    ->toArray();
            }
        } else {
            $ContractTypes = ContractType::pluck('short_name', 'contract_type_id')->toArray();
        }

        $maxrows = 50;


        foreach ($ContractTypes as $key => $ContractType) {
            // Create a new sheet for each contract type
            $newSheet = clone $sheet;
            $newSheet->setTitle($ContractType);





            $newSheet->setCellValue('A1', 'Basic Contract Information');

            $newSheet->mergeCells('A1:H1');
            $contractTypes = "New,Legacy Contracts";
            $newSheet->setCellValue('A2', 'Sl.No');
            $newSheet->setCellValue('B2', 'Contract');
            
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'A' . $row;
                $newSheet->setCellValue($cell,($row - 2));
            }
            
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'B' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"'.$contractTypes.'"');
                // $validation->setFormula1('"' . implode(',', ['New', 'Legacy Contracts']) . '"');
            }

            $entArrCount = count($entArr);
            // print_r($entArr);die;

            $newSheet->setCellValue('C2', 'Department');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'C' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                // $validation->setFormula1('"' .$ent. '"');

                $validation->setFormula1('DepartmentCode!$A$1:$A$'.$entArrCount.'');	// Make sure to put the list items between " and "  !!!
                // $validation->setFormula1('DepartmentCode!$A$1:$A$'.$ent.'');

                // $validation->setFormula1($ent);
            }




            $newSheet->setCellValue('D2', 'Category');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'D' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('Category!$A$1:$A$'.$categoArrCount.'');
                // $validation->setFormula1($catego);
            }


            $newSheet->setCellValue('E2', 'Exclusivity');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'E' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"Exclusivity to Company, Exclusive to Contracting Party, Mutually Exclusive, Non Exclusive"');
                // $validation->setFormula1('"' . implode(',', ['Exclusivity to Company', 'Exclusive to Contracting Party', 'Mutually Exclusive', 'Non Exclusive']) . '"');
            }

            $newSheet->setCellValue('F2', 'Contract Description');
            
            $newSheet->setCellValue('G2', 'Previous Contract exits');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'G' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"No,Yes-In Software,Yes-In This file"');
                // $validation->setFormula1('"' . implode(',', ['Internal,External']) . '"');
            }
            $newSheet->setCellValue('H2', 'Previous Contract No');
            

            $newSheet->setCellValue('I1', 'Party Details');
            $newSheet->mergeCells('I1:P1');

            $newSheet->setCellValue('I2', 'Party 1 Type');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'I' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"Internal,External,Intergroup"');
                // $validation->setFormula1('"' . implode(',', ['Internal,External']) . '"');
            }

            $newSheet->setCellValue('J2', 'Party 1 Internal Party Name');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'J' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('Entities!$A$1:$A$'.$entitiesArrCount.'');
                // $validation->setFormula1($entities);
            }
            
            $branchArrCount = count($branchArr);
            $newSheet->setCellValue('K2', 'Party 1 Internal Location (Branch Address)');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'K' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('Branch!$A$1:$A$'.$branchArrCount.'');
                // $validation->setFormula1($branchs);
            }
            $newSheet->setCellValue('L2', 'Party 1 External Party Name');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'L' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('ExternalParties!$A$1:$A$'.$contractPartiesDataArrCount.'');
                // $validation->setFormula1($contractPartiesData);
            }
            $newSheet->setCellValue('M2', 'Party 2 Type');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'M' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"Internal,External,Intergroup"');
                // $validation->setFormula1('"' . implode(',', ['Internal,External']) . '"');
            }

            $newSheet->setCellValue('N2', 'Party 2 Internal Party Name');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'N' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('Entities!$A$1:$A$'.$entitiesArrCount.'');
                // $validation->setFormula1($entities);
            }

            $newSheet->setCellValue('O2', 'Party 2 Internal Location (Branch Address)');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'O' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('Branch!$A$1:$A$'.$branchArrCount.'');
                // $validation->setFormula1($branchs);
            }

            $newSheet->setCellValue('P2', 'Party 2 External Party Name');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'P' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('ExternalParties!$A$1:$A$'.$contractPartiesDataArrCount.'');
                // $validation->setFormula1($contractPartiesData);
            }


            $newSheet->setCellValue('Q1', 'Ownership');
            $newSheet->mergeCells('Q1:S1');
            $newSheet->setCellValue('Q2', 'Co-ordinator');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'Q' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('Users!$A$1:$A$'.$usersArrCount.'');
                // $validation->setFormula1($users);
            }

            $newSheet->setCellValue('R2', 'Signatory');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'R' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('Users!$A$1:$A$'.$usersArrCount.'');
                // $validation->setFormula1($users);
            }
            $newSheet->setCellValue('S2', 'Signing Date (01-06-2024)');


            $newSheet->setCellValue('T1', 'Contract Duration');
            $newSheet->mergeCells('T1:AN1');

            $newSheet->setCellValue('T2', 'Commencement Type');

            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'T' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"FixedDate,Eventbased"');
                // $validation->setFormula1('"' . implode(',', ['FixedDate, Eventbased']) . '"');
                $newSheet->setCellValue($cell, 'FixedDate');
            }

            $newSheet->setCellValue('U2', 'Commencement Date (01-06-2024)');



            $newSheet->setCellValue('V2', 'Contract End Type');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'V' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"onetimeContract,fixedTerm,evergreen,terminated"');
                // $validation->setFormula1('"' . implode(',', ['onetimeContract, fixedTerm, evergreen, terminated']) . '"');
                $newSheet->setCellValue($cell, 'fixedTerm');
            }

            $newSheet->setCellValue('W2', 'End date of contract (01-06-2024)');


            $newSheet->setCellValue('X2', 'Contract Currency');

            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'X' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"INR"');
                // $validation->setFormula1('"' . implode(',', ['INR', 'USD']) . '"');
                $newSheet->setCellValue($cell, 'INR');
            }

            $newSheet->setCellValue('Y2', 'Contract Value');

            $newSheet->setCellValue('Z2', 'Contract Attachments');


            $newSheet->setCellValue('AA2', 'Type of Renewal');

            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AA' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"automaticrenewal,manualRenewal"');
                // $validation->setFormula1('"' . implode(',', ['Automatic renewal with notice', 'Manual Renewal with notice']) . '"');
                $newSheet->setCellValue($cell, 'manualRenewal');
            }




            $newSheet->setCellValue('AB2', 'Period of auto renewal');

            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AB' . $row;
                $newSheet->setCellValue($cell, '1 Year');
            }

            $newSheet->setCellValue('AC2', 'Condition for end of contract');

            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AC' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"When mutually agreed to end, When terminated Clause is triggered, When good are delivered/ project is completed/ milestone is achieved,others [specify]"');
                // $validation->setFormula1('"' . implode(',', ['When mutually agreed to end', 'When terminated Clause is triggered', 'When good are delivered/ project is completed/ milestone is achieved', 'others [specify]']) . '"');
            }

            $newSheet->setCellValue('AD2', 'Enable Reminder');

            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AD' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"YES,NO"');
                // $newSheet->setCellValue($cell, 'NO');
            }

            $newSheet->setCellValue('AE2', 'First level Alert Me about');

            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AE' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"Contract End Date, Renewal Date"');
                // $validation->setFormula1('"' . implode(',', ['Contract End Date', 'Renewal Date']) . '"');
            }

            $newSheet->setCellValue('AF2', 'First level Alert Me on');
            $newSheet->setCellValue('AG2', 'First level Repeats');

            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AG' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                
                $validation->setFormula1('"Daily, Every 3 days, Weekly, Fortnightly, Monthly,Never"');
                
                //$validation->setFormula1('"' . implode(',', ['Daily', 'Every 3 days', 'Weekly', 'Fortnightly', 'Monthly', 'Never']) . '"');
            }

            $newSheet->setCellValue('AH2', 'Second level Alert Me about');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AH' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"Contract End Date,Renewal Date"');
                 
               // $validation->setFormula1('"' . implode(',', ['Contract End Date', 'Renewal Date']) . '"');
            }

            $newSheet->setCellValue('AI2', 'Second level Alert Me on');

            $newSheet->setCellValue('AJ2', 'Second level Repeats');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AJ' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                 $validation->setFormula1('"Daily, Every 3 days, Weekly, Fortnightly, Monthly,Never"');
               // $validation->setFormula1('"' . implode(',', ['Daily', 'Every 3 days', 'Weekly', 'Fortnightly', 'Monthly', 'Never']) . '"');
            }

            $newSheet->setCellValue('AK2', 'Escalation level Alert Me about');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AK' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"Contract End Date, Renewal Date"');
                //$validation->setFormula1('"' . implode(',', ['Contract End Date', 'Renewal Date']) . '"');
            }

            $newSheet->setCellValue('AL2', 'Escalation level Alert Me on');
            $newSheet->setCellValue('AM2', 'Escalation level Repeats');

            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AM' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"Daily, Every 3 days, Weekly, Fortnightly, Monthly, Never"');
             //   $validation->setFormula1('"' . implode(',', ['Daily', 'Every 3 days', 'Weekly', 'Fortnightly', 'Monthly', 'Never']) . '"');
            }


            $newSheet->setCellValue('AN1', 'Contract Value');

            $newSheet->mergeCells('AN1:AX1');

            $newSheet->setCellValue('AN2', 'Payment Schedule');
            $newSheet->setCellValue('AO2', 'Payment Terms');
            $newSheet->setCellValue('AP2', 'Billing Frequency');

            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AP' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"Weekly, Quarterly, Monthly, Annually, Onetime"');
              //  $validation->setFormula1('"' . implode(',', ['Monthly', 'Quarterly', 'Annually']) . '"');
            }

            $newSheet->setCellValue('AQ2', 'Taxes and Fees');
            $newSheet->setCellValue('AR2', 'Escalation Clauses');
            $newSheet->setCellValue('AS2', 'Discounts or Rebates');
            $newSheet->setCellValue('AT2', 'Retention or Holdbacks');
            $newSheet->setCellValue('AU2', 'Payment Escrow');
            $newSheet->setCellValue('AV2', 'Financial Guarantees or Bonds');
            $newSheet->setCellValue('AW2', 'Currency Conversion');
            
            $newSheet->setCellValue('AX2', 'Party 3 Type');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AX' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"Internal,External,Intergroup"');
            }

            $newSheet->setCellValue('AY2', 'Party 3 Internal Party Name');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AY' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('Entities!$A$1:$A$'.$entitiesArrCount.'');
                // $validation->setFormula1($entities);
            }
            
            $branchArrCount = count($branchArr);
            $newSheet->setCellValue('AZ2', 'Party 3 Internal Location (Branch Address)');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'AZ' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('Branch!$A$1:$A$'.$branchArrCount.'');
                // $validation->setFormula1($branchs);
            }
            $newSheet->setCellValue('BA2', 'Party 3 External Party Name');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'BA' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('ExternalParties!$A$1:$A$'.$contractPartiesDataArrCount.'');
                // $validation->setFormula1($contractPartiesData);
            }
            
            $newSheet->setCellValue('BB2', 'Party 4 Type');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'BB' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"Internal,External,Intergroup"');
            }

            $newSheet->setCellValue('BC2', 'Party 4 Internal Party Name');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'BC' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('Entities!$A$1:$A$'.$entitiesArrCount.'');
                // $validation->setFormula1($entities);
            }
            
            $branchArrCount = count($branchArr);
            $newSheet->setCellValue('BD2', 'Party 4 Internal Location (Branch Address)');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'BD' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('Branch!$A$1:$A$'.$branchArrCount.'');
                // $validation->setFormula1($branchs);
            }
            $newSheet->setCellValue('BE2', 'Party 4 External Party Name');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'BE' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('ExternalParties!$A$1:$A$'.$contractPartiesDataArrCount.'');
                // $validation->setFormula1($contractPartiesData);
            }

            $newSheet->setCellValue('BF2', 'Party 5 Type');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'BF' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('"Internal,External,Intergroup"');
            }

            $newSheet->setCellValue('BG2', 'Party 5 Internal Party Name');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'BG' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('Entities!$A$1:$A$'.$entitiesArrCount.'');
                // $validation->setFormula1($entities);
            }
            
            $branchArrCount = count($branchArr);
            $newSheet->setCellValue('BH2', 'Party 5 Internal Location (Branch Address)');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'BH' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('Branch!$A$1:$A$'.$branchArrCount.'');
                // $validation->setFormula1($branchs);
            }
            $newSheet->setCellValue('BI2', 'Party 5 External Party Name');
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = 'BI' . $row;
                $validation = $newSheet->getCell($cell)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Value is not in list.');
                $validation->setPromptTitle('Pick from list');
                $validation->setPrompt('Please pick a value from the dropdown list.');
                $validation->setFormula1('ExternalParties!$A$1:$A$'.$contractPartiesDataArrCount.'');
                // $validation->setFormula1($contractPartiesData);
            }





            //$categorys = Category::where('category_group', 'contract')->get();
 


            $startingColumn = 'BJ'; // Column immediately after 'AW'
            $rows = 2; // Assuming you want to add these to row 1



            //foreach ($categorys as $category) {
                $customFields = CustomFields::where('status', 1)->where('contract_type', $key)->orderBy('order_id')->get();
                //$customFields = CustomFields::where('status', 1)->where('contract_type', $key)->where('category', $category->category_id)->orderBy('order_id')->get();
                //$k = 0;
                foreach ($customFields as $customField) {

                    if ($customField->field_type == 'date') {
                        $newSheet->setCellValue($startingColumn . $rows,  $customField->field_name . '(01-06-2023)');
                    } else {
                        $newSheet->setCellValue($startingColumn . $rows,  $customField->field_name);
                    }

                    for ($row = 3; $row <= $maxrows; $row++) {
                        $cell = $startingColumn . $row;
                        $validation = $newSheet->getCell($cell)->getDataValidation();

                        if ($customField->field_type == 'date') {
                        }
                        if ($customField->field_type == 'currency') {
                            $validation->setType(DataValidation::TYPE_LIST);
                            $validation->setErrorStyle(DataValidation::STYLE_STOP);
                            $validation->setShowDropDown(true);
                            $validation->setShowInputMessage(true);
                            $validation->setShowErrorMessage(true);
                            $validation->setErrorTitle('Input error');
                            $validation->setError('Value is not in list.');
                            $validation->setPromptTitle('Pick from list');
                            $validation->setPrompt('Please pick a value from the dropdown list.');
                            //$validation->setFormula1('"' . implode(',', ['INR', 'USD']) . '"');
                        }
                        if ($customField->field_type == 'number') {
                            $validation->setType(DataValidation::TYPE_DECIMAL);
                            $validation->setErrorStyle(DataValidation::STYLE_STOP);
                            $validation->setShowDropDown(true);
                            $validation->setShowInputMessage(true);
                            $validation->setShowErrorMessage(true);
                            $validation->setErrorTitle('Input error');
                            $validation->setError('Value is not in list.');
                            $validation->setPromptTitle('Pick from list');
                            $validation->setPrompt('Please pick a value from the dropdown list.');
                           // $validation->setFormula1($users);
                        }

                        if ($customField->field_type == 'text') {
                        }


                        if ($customField->field_type == 'select') {
                            $validation->setType(DataValidation::TYPE_LIST);
                            $validation->setErrorStyle(DataValidation::STYLE_STOP);
                            $validation->setShowDropDown(true);
                            $validation->setShowInputMessage(true);
                            $validation->setShowErrorMessage(true);
                            $validation->setErrorTitle('Input error');
                            $validation->setError('Value is not in list.');
                            $validation->setPromptTitle('Pick from list');
                            $validation->setPrompt('Please pick a value from the dropdown list.');

                            $array = explode(',', $customField->field_default_value);

                          //  $validation->setFormula1('"' . implode(',', $array) . '"');
                        }
                    }

                    //$k++;
                    $startingColumn++;
                }
            //}


            $highestColumn = $newSheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $newSheet->getColumnDimension($columnLetter)->setAutoSize(true);
                $newSheet->freezePane('A3');
            }

            
            
            

            $spreadsheet->addSheet($newSheet);
        }
        
        
        $sheetCount = count($ContractTypes)+1;
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex($sheetCount);
        $k =1;
        
        foreach($entArr as $entVal)
        {
            $spreadsheet->getActiveSheet()->setCellValue("A$k",$entVal);
            $k++;
        }
        
        $spreadsheet->getActiveSheet()->setTitle('DepartmentCode');
        $spreadsheet->getSheetByName('DepartmentCode')->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
        
        
        $sheetNoB = $sheetCount+1;
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex($sheetNoB);
        $l =1;
        
        foreach($branchArr as $branchVal)
        {
            $spreadsheet->getActiveSheet()->setCellValue("A$l",$branchVal);
            $l++;
        }
        
        $spreadsheet->getActiveSheet()->setTitle('Branch');
        $spreadsheet->getSheetByName('Branch')->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
        
        
        $sheetNoU = $sheetNoB+1;
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex($sheetNoU);
        $m =1;
        
        foreach($usersArr as $usersVal)
        {
            $spreadsheet->getActiveSheet()->setCellValue("A$m",$usersVal);
            $m++;
        }
        
        $spreadsheet->getActiveSheet()->setTitle('Users');
        $spreadsheet->getSheetByName('Users')->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);

        
        $sheetNoC = $sheetNoU+1;
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex($sheetNoC);
        $n =1;
        
        foreach($categoArr as $catVal)
        {
            $spreadsheet->getActiveSheet()->setCellValue("A$n",$catVal);
            $n++;
        }
        
        $spreadsheet->getActiveSheet()->setTitle('Category');
        $spreadsheet->getSheetByName('Category')->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
        
        
        $sheetNoE = $sheetNoC+1;
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex($sheetNoE);
        $p =1;
        
        foreach($entitiesArr as $entyVal)
        {
            $spreadsheet->getActiveSheet()->setCellValue("A$p",$entyVal);
            $p++;
        }
        
        $spreadsheet->getActiveSheet()->setTitle('Entities');
        $spreadsheet->getSheetByName('Entities')->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
        
        
        $sheetNoExp = $sheetNoE+1;
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex($sheetNoExp);
        $q =1;
        
        foreach($contractPartiesDataArr as $contractPartiesDataVal)
        {
            $spreadsheet->getActiveSheet()->setCellValue("A$q",$contractPartiesDataVal);
            $q++;
        }
        
        $spreadsheet->getActiveSheet()->setTitle('ExternalParties');
        $spreadsheet->getSheetByName('ExternalParties')->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);


        $spreadsheet->removeSheetByIndex(0);
    

        
        // Stream the file to the browser
        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );        
        
        $timestampFile = strtotime(date('Y-m-d H:i:s'));
        $response->headers->set('Content-Type','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set("Content-Disposition","attachment; filename=contracts$timestampFile.xlsx");
        $response->headers->set('Cache-Control','max-age=0');

        setcookie('preload', false, 0, "/");
        return $response;
        exit;
    }
    
    public function checkContractDuplicates($sheetName, $sheetData){
        
        //$contractData = $request->sessionData;
        //$sheetData = json_decode($contractData, true);
        
        $contract_type_data = ContractType::pluck('contract_type_id', 'short_name');
        
        $department_data =  EntityBusiness::pluck('id', 'name');
        
        $catgoery_data =  ContractCategories::pluck('id', 'name');
        
        $checkInternalCols = [8,12,49,53,57];
        
        $errorCols = [];
        
        $duplicatesExist = 0;

        $cont_type_id = $contract_type_data[$sheetName] ?? false;
        
        if(!$cont_type_id){
            return false;
        }
        
        // print_r($sheetData);
        // die;
        $chaildval = $sheetData;
        $chaildval['contractType'] = $cont_type_id;
        $chaildval['DepartmentType'] = $department_data[$chaildval['DepartmentType']];
        $chaildval['catgoeryType'] = $catgoery_data[$chaildval['catgoeryType']];
        $contractController = new ContractController();
        
        $partysArr = $chaildval['partys'];
        $keysPartys = array_keys($partysArr);
        $valuesPartys = array_values($partysArr);
        $newKeysPartys = array_map(function($key) use ($checkInternalCols) {
                            return array_search($key, $checkInternalCols);
                        }, $keysPartys);
        $newArrayPartys = array_combine($newKeysPartys, $valuesPartys); 
        
        $duplicateContract = $contractController->checkDuplicateContracts($newArrayPartys, $chaildval, $chaildval['endContractDate'], true);
        
        if($duplicateContract){
            $duplicatesExist++;
            return $duplicateContract;
        }
        
        return false;

    }
    
    
    public function checkImportDuplicates(Request $request){
        
        $contractData = $request->sessionData;
        $sheetData = json_decode($contractData, true);
        
        $contract_type_data = ContractType::pluck('contract_type_id', 'short_name');
        
        $department_data =  EntityBusiness::pluck('id', 'name');
        
        $catgoery_data =  ContractCategories::pluck('id', 'name');
        
        $checkInternalCols = [8,12,49,53,57];
        
        $errorCols = [];
        
        $duplicatesExist = 0;
        
        foreach ($sheetData as $key => $val) {

                $cont_type_id = $contract_type_data[$key];
                
                foreach($val as $vkey => $chaildval){
                    // = $chaildval;
    
                    //print_
                    $chaildval['contractType'] = $cont_type_id;
                    $chaildval['DepartmentType'] = $department_data[$chaildval['DepartmentType']];
                    $chaildval['catgoeryType'] = $catgoery_data[$chaildval['catgoeryType']];
                    $contractController = new ContractController();
                    
                    $partysArr = $chaildval['partys'];
                    $keysPartys = array_keys($partysArr);
                    $valuesPartys = array_values($partysArr);
                    $newKeysPartys = array_map(function($key) use ($checkInternalCols) {
                                        return array_search($key, $checkInternalCols);
                                    }, $keysPartys);
                    $newArrayPartys = array_combine($newKeysPartys, $valuesPartys); 
                    
                    $duplicateContract = $contractController->checkDuplicateContracts($newArrayPartys, $chaildval, $chaildval['endContractDate'], true);
                    
                    if($duplicateContract){
                        $duplicatesExist++;
                        $errorCols[] = $key."-".$vkey;
                    }
                }
        }
        
        return response()->json(['message' => $duplicatesExist ? 'Duplicate Contracts Exist In This Sheet' : 'success', 'data' => $errorCols], 200);

    }    

}
