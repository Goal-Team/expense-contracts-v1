<?php

namespace Modules\ContractParties\Http\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use App\Models\User;
use App\Models\ContractParties;
use App\Models\ContractPartiesRepresentative;
use App\Models\ContractPartiesLabel;
use App\Models\Country;
use App\Models\State;
use App\Models\Branch;

use App\Models\ContractCategories;
use App\Models\AddUsers;
use App\Models\ContractType;
use App\Models\CustomFields;
use App\Models\CustomFieldsData;
use App\Models\EntityBusiness;
use App\Models\FinancialLimit;
use App\Models\BranchUser;
use App\Models\PartyApprovalRules;
use App\Models\ApprovalParties;
use App\Models\ContractPartyData;
use App\Models\ContractPartyEntityType;

use App\Helpers\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;
use DB;
use Illuminate\Support\Facades\Storage;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ContractPartiesController extends Controller
{
    
    public function __construct()
    {
        if (Controller::checkCurrentAuth("Contracts") != 1) {
            return abort('404');
        }
    }
    
    /**
     * Store a party-related file using the project's file storage abstraction.
     * Returns a storage reference (path or remote id) on success, or false on failure.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $parties_id
     * @param string|null $fileName
     * @return string|false
     */
    protected function storePartyFile($file, $parties_id = 0, $fileName = null)
    {
        try {
            $fileName = $fileName ?: file_name($file);

            $controller = fileStorageTypeController();

            // Local storage: place under contracts/others/parties/{id}
            if (fileStorageType() == 'Local') {
                $folderPath = 'contracts/others/parties/' . $parties_id;
                if (!Storage::exists($folderPath)) {
                    Storage::makeDirectory($folderPath);
                }
                $stored = $file->storeAs($folderPath, $fileName, 'local');
                return $stored;
            }

            // For other storage types (Google, Microsoft) rely on their storeFile implementation.
            $stored = $controller->storeFile($file, 0, 0, $fileName);

            if (is_string($stored) && str_contains(strtolower($stored), 'error')) {
                return false;
            }

            return $stored;
        } catch (\Exception $e) {
            // optional: Log errors
            return false;
        }
    }    

    public function home(Request $request)
    {
        return view('parties::contract_parties.home');
    }

    public function dashboard()
    {

        $related_party = $external = $vendors = $customer = $supplier = 0;
        $internal = DB::table('entity')->select('id', decrypt_data('Nameoftheentity', 'entity'))->count();
        
        $branchsAvailable = BranchUser::pluck('id')->toArray();
            
        $contractPartiesAll =  ContractParties::select('*')->where('contract_parties.party_sub_type', '<>', 'individual')->get();
        
        $contractParties = [];

        foreach($contractPartiesAll  as $contractPartie){
            $contractPartie->available = true;

            //Only For Branch
            if($contractPartie->engagement_level == 1){
                if($contractPartie->engagement_branch && !in_array($contractPartie->engagement_branch,$branchsAvailable)){
                    $contractPartie->available = false;
                }
            }else{
                if($contractPartie->engagement_access_level && !in_array($contractPartie->engagement_access_level,$branchsAvailable)){
                    $contractPartie->available = false;
                }                
            }

            if($contractPartie->available){
                $external++;
                if($contractPartie->is_related_party == '1'){
                    $related_party++;
                }
                
                switch ($contractPartie->party_type) {
                    case 'vendor':
                        $vendors++;
                        break;
                    case 'customer':
                        $customer++;
                        break;
                    case 'supplier':
                        $supplier++;
                        break;
                }                
            }

        } 

        return view('parties::contract_parties.index', compact('internal', 'external', 'related_party', 'vendors', 'customer', 'supplier'));
    }
    
    public function dashboard_individual()
    {

        $related_party = $external = $vendors = $customer = $supplier = 0;
        $internal = DB::table('entity')->select('id', decrypt_data('Nameoftheentity', 'entity'))->count();
        
        $branchsAvailable = BranchUser::pluck('id')->toArray();
            
        $contractPartiesAll =  ContractParties::select('*')->where('party_sub_type', 'individual')->get();
        
        $contractParties = [];

        foreach($contractPartiesAll  as $contractPartie){
            $contractPartie->available = true;

            //Only For Branch
            if($contractPartie->engagement_level == 1){
                if($contractPartie->engagement_branch && !in_array($contractPartie->engagement_branch,$branchsAvailable)){
                    $contractPartie->available = false;
                }
            }else{
                if($contractPartie->engagement_access_level && !in_array($contractPartie->engagement_access_level,$branchsAvailable)){
                    $contractPartie->available = false;
                }                
            }

            if($contractPartie->available){
                $external++;
                if($contractPartie->is_related_party == '1'){
                    $related_party++;
                }
                
                switch ($contractPartie->party_type) {
                    case 'vendor':
                        $vendors++;
                        break;
                    case 'customer':
                        $customer++;
                        break;
                    case 'supplier':
                        $supplier++;
                        break;
                }                
            }

        } 

        return view('parties::contract_parties.indexindividual', compact('internal', 'external', 'related_party', 'vendors', 'customer', 'supplier'));
    }
    
    public function parties_json(Request $request)
    {
        $q = $request->get('q', '');
        
        $branchsAvailable = BranchUser::pluck('id')->toArray();
            
        $contractPartiesQuery =  ContractParties::select('id','company_name as name', 'entity_scope as scope', 'entity_type as entityTypeId', 'payment_type as payer_type');

        $contractPartiesQuery->where('contract_parties.party_sub_type', '<>', 'individual');
        
        
        $contractPartiesAll = $contractPartiesQuery->get();
        
        $contractParties = [];
        


        foreach($contractPartiesAll  as $contractPartie){
            $contractPartie->available = true;
            //Only For Branch
            if($contractPartie->engagement_level == 1){
                if($contractPartie->engagement_branch && !in_array($contractPartie->engagement_branch,$branchsAvailable)){
                    $contractPartie->available = false;
                }
            }else{
                if($contractPartie->engagement_access_level && !in_array($contractPartie->engagement_access_level,$branchsAvailable)){
                    $contractPartie->available = false;
                }                
            }

            if($contractPartie->available){
                $contractPartie->name = decryptString($contractPartie->name, 'company_name');
                if(!empty($q)){
                    if(strpos(strtolower($contractPartie->name), strtolower($q)) !== false){
                        $contractParties[] = $contractPartie;
                    }
                }else{
                    $contractParties[] = $contractPartie;
                }
            }

        }            

        return response()->json($contractParties);
    }
    
    public function get_party_entity_types(Request $request)
    {
        $scope = $request->get('scope');
        $query = ContractPartyEntityType::query();
        if ($scope) $query->where('scope', $scope);
        $items = $query->get(['id', 'name', 'scope']);
        return response()->json($items);
    }    

    /**
     * Resolve all parent geography ids for a given engagement access level.
     * This avoids dynamic SQL like "@pv:=" when access level is empty.
     */
    protected function getGeoHierarchyParentIds($accessLevel): array
    {
        if (!is_numeric($accessLevel) || (int) $accessLevel <= 0) {
            return [];
        }

        $currentIds = [(int) $accessLevel];
        $allParents = [];
        $safety = 0;

        while (!empty($currentIds) && $safety < 25) {
            $parentValues = DB::table('GeographicalHierarchy')
                ->whereIn('id', $currentIds)
                ->pluck('parent')
                ->filter()
                ->toArray();

            $nextIds = [];
            foreach ($parentValues as $parentCsv) {
                foreach (explode(',', (string) $parentCsv) as $id) {
                    $id = (int) trim($id);
                    if ($id > 0 && !in_array($id, $allParents, true)) {
                        $allParents[] = $id;
                        $nextIds[] = $id;
                    }
                }
            }

            $currentIds = $nextIds;
            $safety++;
        }

        return $allParents;
    }

    public function contract_parties(Request $request)
    {
        try {

        $related_party = $external = $vendors = $customer = $supplier = 0;
        $internal = DB::table('entity')->select('id', decrypt_data('Nameoftheentity', 'entity'))->count();
        
        $branchsAvailable = BranchUser::pluck('id')->toArray();
            
        $contractPartiesQuery =  ContractParties::select('contract_parties.id', 'contract_parties.company_name', 'contract_parties.party_type', 'contract_parties.city', 'contract_parties.company_contact', 'contract_parties.company_email', 'legal_entity', 'role_in_contract', 'engagement_level', 'contract_parties.status', 'contract_parties.created_by','approvers', 'entity_type', 'contract_parties.vendor_code', 'contract_parties.active_vendor_code');
        
        if(isset($request->partysub)){
            $contractPartiesQuery->where('contract_parties.party_sub_type', 'individual');
        }else{
            $contractPartiesQuery->where('contract_parties.party_sub_type', '<>', 'individual');
        }
        
        if (isset($_GET['status'])){
            if($_GET['status'] == 'related_party') {
                $contractPartiesQuery->where('contract_parties.is_related_party', 1);
            }else{
                switch ($_GET['status']) {
                    case 'vendors':
                        $contractPartiesQuery->where('contract_parties.party_type', 'vendor');
                        break;
                    case 'customer':
                        $contractPartiesQuery->where('contract_parties.party_type', 'customer');
                        break;
                    case 'supplier':
                        $contractPartiesQuery->where('contract_parties.party_type', 'supplier');
                        break;
                }                
            }
        }
        
        $contractPartiesAll = $contractPartiesQuery->get();
        
        $contractParties = [];

        foreach($contractPartiesAll  as $contractPartie){
            $contractPartie->available = true;
            // if(in_array(Helpers::userInfo()->email, explode(',', $contractPartie->approvers))){
            //     $contractPartie->available = true;
            // }else if($contractPartie->status == 1){
            //     $contractPartie->available = true;
            // }
            //Only For Branch
            if($contractPartie->engagement_level == 1){
                if($contractPartie->engagement_branch && !in_array($contractPartie->engagement_branch,$branchsAvailable)){
                    $contractPartie->available = false;
                }
            }else{
                if($contractPartie->engagement_access_level && !in_array($contractPartie->engagement_access_level,$branchsAvailable)){
                    $contractPartie->available = false;
                }                
            }

            if($contractPartie->available){
                $external++;
                if($contractPartie->is_related_party == '1'){
                    $related_party++;
                }
                
                switch ($contractPartie->party_type) {
                    case 'vendor':
                        $vendors++;
                        break;
                    case 'customer':
                        $customer++;
                        break;
                    case 'supplier':
                        $supplier++;
                        break;
                }
                
                $contractPartie->company_name = decryptString($contractPartie->company_name, 'company_name');
                $contractParties[] = $contractPartie;
            }

        } 

        return response()->json([
            'data' => $contractParties,
            'draw' => $request->input('draw') ?? 1,
            'recordsTotal' => count($contractParties),
            'recordsFiltered' => count($contractParties),
        ]);

        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }

    /**
     * @date:: 17 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: Contract Parties List function
     **/
    public function contract_parties_add_org(Request $request)
    {

        //Owner/Initiator Validation
        $owner_initiator = session()->get('contractSessionUser');

        $initiatior_exists = AddUsers::select('id',  decrypt_data('AccessScope', 'AddUsers'))
            ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
            ->first();
        if (!$initiatior_exists) {
            $invalid_owner_error = array('Owner Not Available Please Contact Administrator');
            return redirect('parties')->withErrors(array_merge($fileError, $invalid_owner_error))->withInput();
        }
        
        $owner_initiator_id = $initiatior_exists->id;
        
        try {
            if ($request->isMethod('post')) {

                $parties_label = array();
                $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','nullable') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
                    ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
                    ->where('contract_parties_label.status', 1)->get();
                foreach ($label as $label_data) {
                    $parties_label[$label_data->name] = [
                        'name' => $label_data->name,
                        'label_name' => $label_data->label_name,
                        'is_required' => $label_data->is_required,
                        'error_text' => $label_data->error_text,
                        'is_regex' => $label_data->is_regex,
                        'regex_id' => $label_data->regex_id,
                        'regex_name' => $label_data->regex_name,
                        'regex_pattern' => $label_data->pattern
                    ];
                }

                $validator =  Validator::make($request->all(), [
                    'company_name' => 'required',
                    'company_contact' => $parties_label['company_contact']['is_required'],
                    'company_email' => $parties_label['company_email']['is_required'].'|unique:contract_parties,company_email',
                    'gst' => $request->customer_type == 'international' ? 'nullable' : $parties_label['gst']['is_required'],
                    'pan' => $request->customer_type == 'international' ? 'nullable' : $parties_label['pan']['is_required'],
                    'corporate_registration_number' => $request->customer_type == 'international' ? 'required' : 'nullable',
                    'tax_residency_certificate' => $request->customer_type == 'international' ? 'required|file|mimes:pdf,jpg,jpeg,png' : 'nullable|file|mimes:pdf,jpg,jpeg,png',
                    'no_permanent_establishment' => $request->customer_type == 'international' ? 'required|file|mimes:pdf,jpg,jpeg,png' : 'nullable|file|mimes:pdf,jpg,jpeg,png',
                    'representative.*.passport_number' => $request->customer_type == 'international' ? 'required' : 'nullable',                    
                    // 'building_no' => 'required',
                    // 'area_name' => 'required',
                    // 'city' => 'required',
                    // 'state' => 'required',
                    // 'country' => 'required',
                    // 'legal_entity' => 'required',
                    // 'engagement_level' => 'required'
                ]);
                if ($validator->fails()) {
                    $errors = $validator->errors();
                    
                    $Countryid = $request->Countryid;
                    $states = State::select("name", "id")
                        ->where('Countryid', $Countryid)
                        ->get();
                    return redirect('parties/contract-parties-org-add')->withErrors($validator)->withInput()->with('states', $states);
                }

                // Additional check for organization_type
                if (!empty($request->organization_type) && !in_array($request->organization_type, ['firm','society','trust'])) {
                    $Countryid = $request->Countryid;
                    $states = State::select("name", "id")->where('Countryid', $Countryid)->get();
                    return redirect('parties/contract-parties-org-add')->withErrors(['organization_type' => 'Invalid organization type'])->withInput()->with('states', $states);
                }
                $engagement_access_level = $engagement_branch = NULL;
                $parties = new ContractParties();
                $parties->company_name = encryptString($request->company_name, 'company_name');
                $parties->party_type = $request->contract_type;
                $parties->party_sub_type = $request->contract_type;
                $parties->entity_scope = $request->customer_type;
                $parties->entity_type = $request->entity_type;
                $parties->payment_type = $request->payer_type;
                $parties->company_contact = $request->company_contact;
                $parties->company_email = $request->company_email;
                $parties->building_no = $request->building_no;
                $parties->area_name = $request->area_name;
                $parties->landmark = $request->landmark;

                $parties->city = $request->city;
                $parties->state = $request->state;
                $parties->country = $request->country;
                $parties->pincode = $request->pincode;
                $parties->website = $request->website;
                $parties->gst = encryptString($request->gst, 'gst');
                $parties->pan = encryptString($request->pan, 'pan');
                $parties->vendor_code = $request->vendor_code;
                $parties->active_vendor_code = $request->active_vendor_code;
                if(!empty($request->corporate_registration_number)){
                    $parties->corporate_registration_number = encryptString($request->corporate_registration_number, 'corporate_registration_number');
                }
                $parties->legal_entity = $request->legal_entity;
                $parties->organization_type = $request->organization_type;
                $parties->role_in_contract = $request->role_in_contract;
                
                //Add Approvers For New Vendors
                $getApprovers = PartyApprovalRules::select('approval_required_users');

                if ($request->engagement_level == "branch") {
                    $parties->engagement_level = 1;
                    $parties->engagement_branch = $request->engagement_branch;
                    $getApprovers->whereRaw("FIND_IN_SET($request->engagement_branch, branch)");
                } else {
                    $parties->engagement_level = 0;
                    $parties->engagement_access_level = $request->engagement_access_level;
                    $branches = $this->getGeoHierarchyParentIds($request->engagement_access_level);
                   
                    $getApprovers->whereRaw("FIND_IN_SET($request->engagement_access_level, accesslevel)");
                    if (!empty($branches)) {
                        $getApprovers->whereIn('accessLevel', $branches , "OR");
                    }
                }

                $finalApprovers = $getApprovers->first();
                $approvers = [];
                if($finalApprovers){
                   $approvers =  json_decode($finalApprovers->approval_required_users);
                }
                $parties->approvers = json_encode($approvers);
                $parties->is_related_party = $request->is_related_party;
                if(env('enable_party_approvals')){
                    $parties->status = 0;
                }else{
                    $parties->status = 1;
                }
                $parties->created_by = $owner_initiator_id;
                $parties->updated_by = $owner_initiator_id;
                $parties->save();
                $parties_id = $parties->id;

                // Handle GST file
                if ($request->hasFile('gst_file')) {
                    $gstStored = $this->storePartyFile($request->file('gst_file'), $parties_id);
                    if ($gstStored) {
                        $parties->gst_file = $gstStored;
                        $parties->save();
                    }
                }

                // Handle PAN file
                if ($request->hasFile('pan_file')) {
                    $panStored = $this->storePartyFile($request->file('pan_file'), $parties_id);
                    if ($panStored) {
                        $parties->pan_file = $panStored;
                        $parties->save();
                    }
                } 
                
                if ($request->hasFile('tax_residency_certificate')) {
                    $taxStored = $this->storePartyFile($request->file('tax_residency_certificate'), $parties_id, 'tax_residency_certificate');
                    if ($taxStored) {
                        $parties->tax_residency_certificate = $taxStored;
                        $parties->save();
                    }
                }

                if ($request->hasFile('no_permanent_establishment')) {
                    $nopeStored = $this->storePartyFile($request->file('no_permanent_establishment'), $parties_id, 'no_permanent_establishment');
                    if ($nopeStored) {
                        $parties->no_permanent_establishment = $nopeStored;
                        $parties->save();
                    }
                }
                

                // Save escalation matrix (array of {name, designation}) if provided
                if ($request->has('escalation')) {
                    $matrix = array_values($request->input('escalation'));
                    $parties->escalation_matrix = json_encode($matrix);
                    $parties->save();
                }
                                
                
                if(env('enable_party_approvals')){
                    foreach ($approvers as $key => $appVal) {
                    $randNo = rand(0, 99999);
                    $unique_id = $parties_id . $randNo;
                    $approver_id = $appVal->id;
                    $users = AddUsers::select('id', decrypt_data('Email','AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();                            
                        ApprovalParties::create([
                            'username' => encryptString(json_encode(['email'=>$users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                            'previous_status' => encryptString('Idle', 'previous_status'),
                            'status' => encryptString('inprogress', 'status'),
                            'parties_id' => $parties_id,
                            'orderval' => $key,
                            'unique_id' => $unique_id,
                            'flag' => 1,
                            'approval_status' => encryptString('pending', 'approval_status'),
                        ]);
                    break;
                    
                } 
                }

                foreach ($request->representative as $idx => $value) {
                    $representative = new ContractPartiesRepresentative;
                    $representative->parties_id =  $parties_id;
                    // echo $value['representative_nationality'];
                    // echo "<br>";
                    $representative->representative_name = $value['representative_name'];
                    $representative->representative_email = $value['representative_email'];
                    $representative->representative_designation = $value['representative_designation'];
                    $representative->representative_contact = $value['representative_contact'];
                    $representative->representative_nationality = $value['representative_nationality'];
                    $representative->passport_number = $value['passport_number'] ?? null;                    

                    $representative->status = 1;
                    $representative->created_by = $owner_initiator_id;
                    $representative->updated_by = $owner_initiator_id;
                    
                    $fileInput = $request->file("representative.$idx.representative_brs");
                    
                    if ($fileInput) {
                        $stored = $this->storePartyFile($fileInput, $parties_id);
                        if ($stored) {
                            $representative->representative_brs = $stored;
                            $representative->save();
                        }
                    }                    
                    
                    $representative->save();
                }

                if ($request->has('customFields')) {
                    foreach ($request->input('customFields') as $customField) {
                        if (isset($customField)) {

                            if (isset($customField['id']) && isset($customField['value']) && isset($parties_id)) {
                                CustomFieldsData::create([
                                    'custom_field_id' => $customField['id'],
                                    'custom_field_group' => 'parties',
                                    'custom_field_value' => $customField['value'],
                                    'custom_field_group_id' => $parties_id
                                ]);
                            }
                        }
                    }
                }

                if (isset($_GET['by'])) {
                    return response()->json(['message' => 'Parties has been Created Successfully.', 'id' => $parties->id, 'company_name' => decryptString($parties->company_name, 'company_name')]);
                } else {
                    return redirect('/parties')->with('success', 'Parties has been Created Successfully.');
                }
            } else {
                $customFields = CustomFields::where('status', 1)->where('contract_type', 0)->where('sub_type', 'party')->orderBy('order_id')->get();

                $parties_label = array();
                $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','unrequired') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
                    ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
                    ->where('contract_parties_label.status', 1)->get();
                foreach ($label as $label_data) {
                    $parties_label[$label_data->name] = [
                        'name' => $label_data->name,
                        'label_name' => $label_data->label_name,
                        'is_required' => $label_data->is_required,
                        'error_text' => $label_data->error_text,
                        'is_regex' => $label_data->is_regex,
                        'regex_id' => $label_data->regex_id,
                        'regex_name' => $label_data->regex_name,
                        'regex_pattern' => $label_data->pattern
                    ];
                }
                //print_r($parties_label['gst']['regex_pattern']);exit;
                $country = Country::select('id', 'name')->get();
                $geo_graph = $this->getGeoGraphDropdowns();

                $branch = Branch::select("id", decrypt_data('LegalName', 'branch'))->get();
                $viewName = 'create';
                
                if (isset($_GET['by'])) {
                   $viewName = 'form'; 
                }
                
                //Hide Unwanted Fields for Expense Contract
                $hideExpenseFields = false;
                if(in_array(session()->get('contractSessionUserRole'), ['User','Marketing Manager'])){
                    $hideExpenseFields = true;
                }              
                
                return view("parties::contract_parties.$viewName", compact('parties_label', 'country', 'geo_graph', 'branch', 'customFields', 'hideExpenseFields'));
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }
    

    public function contract_parties_add_ind(Request $request)
    {

        try {
            if ($request->isMethod('post')) {

                $validator =  Validator::make($request->all(), [
                    'company_name' => 'required',
                    //'company_contact' => 'required',
                    //'company_email' => 'required|unique:contract_parties,company_email',
                    // 'pan' => 'required',
                    'building_no' => 'required',
                    'area_name' => 'required',
                    'city' => 'required',
                    'state' => 'required',
                    'pincode' => 'required',
                    // 'country' => 'required',
                    // 'legal_entity' => 'required',
                    // 'engagement_level' => 'required'
                ]);
                if ($validator->fails()) {
                    $Countryid = $request->Countryid;
                    $states = State::select("name", "id")
                        ->where('Countryid', $Countryid)
                        ->get();
                    if (isset($_GET['by'])){
                        return response()->json(collect($validator->errors()->all()));
                    }else{
                        return redirect('parties/contract-parties-ind-add')->withErrors($validator)->withInput()->with('states', $states);
                    }
                }
                $engagement_access_level = $engagement_branch = NULL;
                $parties = new ContractParties();
                $parties->company_name = encryptString($request->company_name, 'company_name');
                // $parties->party_type = $request->contract_type;
                $parties->party_type = 'vendor';
                $parties->party_sub_type = 'individual';
                $parties->company_contact = $request->company_contact;
                $parties->company_email = $request->company_email;
                $parties->building_no = $request->building_no;
                $parties->area_name = $request->area_name;
                $parties->landmark = $request->landmark;

                $parties->city = $request->city;
                $parties->state = $request->state;
                $parties->country = $request->country;
                $parties->pincode = $request->pincode;
                $parties->website = $request->website;
                $parties->gst = encryptString($request->gst, 'gst');
                $parties->pan = encryptString($request->pan, 'pan');
                $parties->vendor_code = $request->vendor_code;
                $parties->active_vendor_code = $request->active_vendor_code;

                $parties->legal_entity = $request->legal_entity;
                $parties->role_in_contract = $request->role_in_contract;
                //Add Approvers For New Vendors
                $getApprovers = PartyApprovalRules::select('approval_required_users');
                // print_r($request->all());exit;
                if ($request->engagement_level == "branch") {
                    $parties->engagement_level = 1;
                    $parties->engagement_branch = $request->engagement_branch;
                    $getApprovers->whereRaw("FIND_IN_SET($request->engagement_branch, branch)");
                } else {
                    $parties->engagement_level = 0;
                    $parties->engagement_access_level = $request->engagement_access_level;
                    $branches = $this->getGeoHierarchyParentIds($request->engagement_access_level);
                   
                    $getApprovers->whereRaw("FIND_IN_SET($request->engagement_access_level, accesslevel)");
                    if (!empty($branches)) {
                        $getApprovers->whereIn('accessLevel', $branches , "OR");
                    }
                }

                $finalApprovers = $getApprovers->first();
                $approvers = [];
                if($finalApprovers){
                   $approvers =  json_decode($finalApprovers->approval_required_users);
                }
                $parties->approvers = json_encode($approvers);
                $parties->is_related_party = $request->is_related_party;
                
                //Enable Party Approvals
                if(env('enable_party_approvals')){
                    $parties->status = 0;
                }else{
                    $parties->status = 1;
                }
                $parties->created_by = 1;
                $parties->updated_by = 1;
                $parties->save();
                $parties_id = $parties->id;
                
                if(env('enable_party_approvals')){
                    foreach ($approvers as $key => $appVal) {
                    $randNo = rand(0, 99999);
                    $unique_id = $parties_id . $randNo;
                    $approver_id = $appVal->id;
                    $users = AddUsers::select('id', decrypt_data('Email','AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();                            
                        ApprovalParties::create([
                            'username' => encryptString(json_encode(['email'=>$users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                            'previous_status' => encryptString('Idle', 'previous_status'),
                            'status' => encryptString('inprogress', 'status'),
                            'parties_id' => $parties_id,
                            'orderval' => $key,
                            'unique_id' => $unique_id,
                            'flag' => 1,
                            'approval_status' => encryptString('pending', 'approval_status'),
                        ]);
                    break;
                    
                } 
                }

                foreach ($request->representative ?? [] as $value) {
                    $representative = new ContractPartiesRepresentative;
                    $representative->parties_id =  $parties_id;
                    // echo $value['representative_nationality'];
                    // echo "<br>";
                    $representative->representative_name = $value['representative_name'];
                    $representative->representative_email = $value['representative_email'];
                    $representative->representative_designation = $value['representative_designation'];
                    $representative->representative_contact = $value['representative_contact'];
                    $representative->representative_nationality = $value['representative_nationality'];

                    $representative->status = 1;
                    $representative->created_by = 1;
                    $representative->updated_by = 1;
                    $representative->save();
                }

                if ($request->has('customFields')) {
                    foreach ($request->input('customFields') as $customField) {
                        if (isset($customField)) {

                            if (isset($customField['id']) && isset($customField['value']) && isset($parties_id)) {
                                CustomFieldsData::create([
                                    'custom_field_id' => $customField['id'],
                                    'custom_field_group' => 'parties',
                                    'custom_field_value' => $customField['value'],
                                    'custom_field_group_id' => $parties_id
                                ]);
                            }
                        }
                    }
                }

                if (isset($_GET['by'])) {
                    return response()->json(['message' => 'Parties has been Created Successfully.', 'id' => $parties->id, 'company_name' => decryptString($parties->company_name, 'company_name')]);
                } else {
                    return redirect('/parties/individual')->with('success', 'Parties has been Created Successfully.');
                }
            } else {
                $customFields = CustomFields::where('status', 1)->where('contract_type', 0)->where('sub_type', 'iparty')->orderBy('order_id')->get();

                $parties_label = array();
                $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','unrequired') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
                    ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
                    ->where('contract_parties_label.status', 1)->get();

                foreach ($label as $label_data) {
                    $parties_label[$label_data->name] = [
                        'name' => $label_data->name,
                        'label_name' => $label_data->label_name,
                        'is_required' => $label_data->is_required,
                        'error_text' => $label_data->error_text,
                        'is_regex' => $label_data->is_regex,
                        'regex_id' => $label_data->regex_id,
                        'regex_name' => $label_data->regex_name,
                        'regex_pattern' => $label_data->pattern
                    ];
                }

                $country = Country::select('id', 'name')->get();
                $geo_graph = $this->getGeoGraphDropdowns();

                $branch = Branch::select("id", decrypt_data('LegalName', 'branch'))->get();
                
                $viewName = 'createindividual';
                
                if (isset($_GET['by'])) {
                   $viewName = 'formindividual'; 
                }
                return view("parties::contract_parties.$viewName", compact('parties_label', 'country', 'geo_graph', 'branch', 'customFields'));
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }
    
    
    public function party_approval_flow(Request $request)
    {
        
        $party_id =  $request->input('id');
        $nextAppStatus =  $request->input('nextAppStatus');
        $curAppStatus =  $request->input('curAppStatus');
        $userInputVal =  $request->input('userInputVal');
        $ReviewDescription =  $request->input('shortDescrip');
        
        $appRowId =  $request->input('appRowId');

        
        $currentApproval = ApprovalParties::find($appRowId);
        
        $approvalArr = ContractParties::select('approvers')->where('id', $party_id)->get();
        

        $appArr = json_decode(trim($approvalArr[0]['approvers']));
        
        $randNo = rand(0, 99999);

        ApprovalParties::where(['parties_id' => $party_id])->update([
            'flag' => 0,
            'approval_status' => encryptString($userInputVal, 'approval_status')
        ]);
        
        ApprovalParties::where(['id' => $appRowId])->update([
            'next_action_item' => encryptString($ReviewDescription, 'next_action_item'),
            'next_action_description' => encryptString($ReviewDescription, 'next_action_description'),
            'next_status' => encryptString($nextAppStatus, 'next_status')
        ]);
        
        
        if ($userInputVal == 'approved'){
            ContractParties::where(['id' => $party_id])->update([
                'status' => 1,
                'approval_status' => 1
            ]);            
        }

        return response()->json(['message' => 'successful!'], 200);
    }
    /**
     * @date:: 22 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: Parties delete function
     **/
    public function contract_parties_delete(Request $request)
    {
        try {
            $parties = ContractParties::find($request->id);
            //print_r($request->id);exit;
            $allParties = ContractPartyData::where('contract_party_exe_id', $request->id)->get();
            if(count($allParties) > 0){
                return response()->json(['success'=>false, 'message' => "Party Can't Deleted"], 200);
            }else{
                $parties->delete();
                return response()->json(['success'=>true, 'message' => 'Party Deleted'], 200);
            }
            //return redirect('/parties')->with('success', 'Parties deleted Successfully.');
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }
    /**
     * @date:: 22 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: Parties edit function
     **/
    public function contract_parties_edit_org(Request $request)
    {
        try {
            if ($request->isMethod('post')) {
                
                
                $parties_label = array();
                $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','nullable') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
                    ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
                    ->where('contract_parties_label.status', 1)->get();
                foreach ($label as $label_data) {
                    $parties_label[$label_data->name] = [
                        'name' => $label_data->name,
                        'label_name' => $label_data->label_name,
                        'is_required' => $label_data->is_required,
                        'error_text' => $label_data->error_text,
                        'is_regex' => $label_data->is_regex,
                        'regex_id' => $label_data->regex_id,
                        'regex_name' => $label_data->regex_name,
                        'regex_pattern' => $label_data->pattern
                    ];
                }

                $validator =  Validator::make($request->all(), [
                    'company_name' => 'required',
                    'company_contact' => $parties_label['company_contact']['is_required'],
                    'company_email' => $parties_label['company_email']['is_required'].'|unique:contract_parties,company_email,'.$request->parties_id,
                    'gst' => $request->customer_type == 'international' ? 'nullable' : $parties_label['gst']['is_required'],
                    'pan' => $request->customer_type == 'international' ? 'nullable' : $parties_label['pan']['is_required'],
                    'corporate_registration_number' => $request->customer_type == 'international' ? 'required' : 'nullable',
                    'tax_residency_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
                    'no_permanent_establishment' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
                    'representative.*.passport_number' => $request->customer_type == 'international' ? 'required' : 'nullable',                    
                    // 'building_no' => 'required',
                    // 'area_name' => 'required',
                    // 'city' => 'required',
                    // 'state' => 'required',
                    // 'country' => 'required',
                    // 'legal_entity' => 'required',
                    // 'engagement_level' => 'required'
                ]);
                if ($validator->fails()) {
                    $errors = $validator->errors();
                    return redirect('parties/contract-parties-org-edit/'.$request->parties_id)->withErrors($validator)->withInput();
                }

                // Additional check for organization_type
                if (!empty($request->organization_type) && !in_array($request->organization_type, ['firm','society','trust'])) {
                    return redirect('parties/contract-parties-org-edit/'.$request->parties_id)->withErrors(['organization_type' => 'Invalid organization type'])->withInput();
                }
                $engagement_access_level = $engagement_branch = NULL;

                if ($request->engagement_level == "branch") {
                    $engagement_level = 1;
                    $engagement_branch = $request->engagement_branch;
                } else {
                    $engagement_level = 0;
                    $engagement_access_level = $request->engagement_access_level;
                }

                $parties = ContractParties::find($request->parties_id);
                //Add Approvers For Edited Vendors
                $getApprovers = PartyApprovalRules::select('approval_required_users');

                if ($request->engagement_level == "branch") {
                    $getApprovers->whereRaw("FIND_IN_SET($request->engagement_branch, branch)");
                } else {
                    $branches = $this->getGeoHierarchyParentIds($request->engagement_access_level);
                   
                    $getApprovers->whereRaw("FIND_IN_SET($request->engagement_access_level, accesslevel)");
                    if (!empty($branches)) {
                        $getApprovers->whereIn('accessLevel', $branches , "OR");
                    }
                }

                $finalApprovers = $getApprovers->first();
                
                $approvers = [];
                if($finalApprovers){
                   $approvers =  json_decode($finalApprovers->approval_required_users);
                }
                
                $parties->update([
                    'company_name' => encryptString($request->company_name, 'company_name'),
                    'party_type' => $request->contract_type,
                    'company_contact' => $request->company_contact,
                    'company_email' => $request->company_email,
                    'entity_scope' => $request->customer_type,
                    'entity_type' => $request->entity_type,
                    'payment_type' => $request->payer_type,                  
                    'building_no' => $request->building_no,
                    'area_name' => $request->area_name,
                    'landmark' => $request->landmark,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                    'pincode' => $request->pincode,
                    'website' => $request->website,
                    'gst' => encryptString($request->gst, 'gst'),
                    'pan' => encryptString($request->pan, 'pan'),
                    'vendor_code' => $request->vendor_code,
                    'active_vendor_code' => $request->active_vendor_code,
                    'legal_entity' => $request->legal_entity,
                    'organization_type' => $request->organization_type,
                    'role_in_contract' => $request->role_in_contract,
                    'engagement_level' => $engagement_level,
                    'engagement_branch' => $engagement_branch,
                    'approvers' => json_encode($approvers),
                    'engagement_access_level' => $engagement_access_level,
                    'is_related_party' => $request->is_related_party ?? 0,
                    'corporate_registration_number' => !empty($request->corporate_registration_number) ? encryptString($request->corporate_registration_number, 'corporate_registration_number') : null,                    
                    'escalation_matrix' => !empty($request->escalation) ? json_encode(array_values($request->escalation)) : null,                    
                    'updated_by' => 1
                ]);
                $parties_id = $request->parties_id;
                
                // enforce international required assets only if switching/setting to international and they are missing
                if ($request->customer_type == 'international') {
                    if (empty($parties->corporate_registration_number) && empty($request->corporate_registration_number)) {
                        return redirect('parties/contract-parties-org-edit/'.$request->parties_id)->withErrors(['corporate_registration_number' => 'Corporate Registration Number is required for International Customers.'])->withInput();
                    }
                    // tax residency and no_permanent_establishment: require either existing DB value or uploaded file
                    if (empty($parties->tax_residency_certificate) && !$request->hasFile('tax_residency_certificate')) {
                        return redirect('parties/contract-parties-org-edit/'.$request->parties_id)->withErrors(['tax_residency_certificate' => 'Tax Residency Certificate is required for International Customers.'])->withInput();
                    }
                    if (empty($parties->no_permanent_establishment) && !$request->hasFile('no_permanent_establishment')) {
                        return redirect('parties/contract-parties-org-edit/'.$request->parties_id)->withErrors(['no_permanent_establishment' => 'No Permanent Establishment document is required for International Customers.'])->withInput();
                    }
                }                
                
                // handle GST and PAN file uploads on edit
                if ($request->hasFile('gst_file')) {
                    $gstStored = $this->storePartyFile($request->file('gst_file'), $parties_id);
                    if ($gstStored) {
                        $parties->gst_file = $gstStored;
                        $parties->save();
                    }
                }

                if ($request->hasFile('pan_file')) {
                    $panStored = $this->storePartyFile($request->file('pan_file'), $parties_id);
                    if ($panStored) {
                        $parties->pan_file = $panStored;
                        $parties->save();
                    }
                } 
                
                

                if ($request->hasFile('tax_residency_certificate')) {
                    $taxStored = $this->storePartyFile($request->file('tax_residency_certificate'), $parties_id, 'tax_residency_certificate');
                    if ($taxStored) {
                        $parties->tax_residency_certificate = $taxStored;
                        $parties->save();
                    }
                }

                if ($request->hasFile('no_permanent_establishment')) {
                    $nopeStored = $this->storePartyFile($request->file('no_permanent_establishment'), $parties_id, 'no_permanent_establishment');
                    if ($nopeStored) {
                        $parties->no_permanent_establishment = $nopeStored;
                        $parties->save();
                    }
                }                
                
                $deletePrevReps = ContractPartiesRepresentative::where('parties_id', $parties_id)->delete();
                
                if($parties->approval_status == 0 && $parties->status == 0 && env('enable_party_approvals')){
                    $approvalExist = ApprovalParties::where('parties_id', $parties_id)->get();
                    if(count($approvalExist) == 0){
                        foreach ($approvers as $key => $appVal) {
                            $randNo = rand(0, 99999);
                            $unique_id = $parties_id . $randNo;
                            $approver_id = $appVal->id;
                            $users = AddUsers::select('id', decrypt_data('Email','AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();                            
                                ApprovalParties::create([
                                    'username' => encryptString(json_encode(['email'=>$users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                    'previous_status' => encryptString('Idle', 'previous_status'),
                                    'status' => encryptString('inprogress', 'status'),
                                    'parties_id' => $parties_id,
                                    'orderval' => $key,
                                    'unique_id' => $unique_id,
                                    'flag' => 1,
                                    'approval_status' => encryptString('pending', 'approval_status'),
                                ]);
                            break;
                            
                        }
                    }
                }

                foreach ($request->representative ?? [] as $idx => $value) {
                    //if (isset($value['representative_id'])) {
                        $representative = new ContractPartiesRepresentative;
                        $representative->parties_id =  $parties_id;
                        $representative->representative_name = $value['representative_name'];
                        $representative->representative_email = $value['representative_email'];
                        $representative->representative_designation = $value['representative_designation'];
                        $representative->representative_contact = $value['representative_contact'];
                        $representative->representative_nationality = $value['representative_nationality'];
                        $representative->passport_number = $value['passport_number'] ?? null;                        

                        $representative->status = 1;
                        $representative->created_by = 1;
                        $representative->updated_by = 1;
                        $representative->save();
                        
                        // handle representative BRS file if uploaded in edit
                        $fileInput = $request->file("representative.$idx.representative_brs");
                        if ($fileInput) {
                            $stored = $this->storePartyFile($fileInput, $parties_id);
                            if ($stored) {
                                $representative->representative_brs = $stored;
                                $representative->save();
                            }
                        }                        
                    //}
                }
                
                $deletePrevCusFields = CustomFieldsData::where('custom_field_group_id', $parties_id)->delete();
                if ($request->has('customFields')) {
                    foreach ($request->input('customFields') as $customField) {
                        if (isset($customField)) {

                            if (isset($customField['id']) && isset($customField['value']) && isset($parties_id)) {
                                CustomFieldsData::create([
                                    'custom_field_id' => $customField['id'],
                                    'custom_field_group' => 'parties',
                                    'custom_field_value' => $customField['value'],
                                    'custom_field_group_id' => $parties_id
                                ]);
                            }
                        }
                    }
                }
                
                return redirect('/parties')->with('success', 'Parties has been Updated Successfully.');
            } else {
                $parties_label = array();
                $parties = ContractParties::find($request->id);

                $representative = ContractPartiesRepresentative::select('contract_parties_representative.*')
                    ->where('contract_parties_representative.parties_id', $request->id)
                    ->get();

                $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','unrequired') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
                    ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
                    ->where('contract_parties_label.status', 1)->get();

                foreach ($label as $label_data) {
                    $parties_label[$label_data->name] = [
                        'name' => $label_data->name,
                        'label_name' => $label_data->label_name,
                        'is_required' => $label_data->is_required,
                        'error_text' => $label_data->error_text,
                        'is_regex' => $label_data->is_regex,
                        'regex_id' => $label_data->regex_id,
                        'regex_name' => $label_data->regex_name,
                        'regex_pattern' => $label_data->pattern
                    ];
                }
                $country = Country::select('id', 'name')->get();
                $branch = Branch::select("id", decrypt_data('LegalName', 'branch'))->get();

                $customFields = CustomFields::where('status', 1)->where('contract_type', 0)->where('sub_type', 'party')->orderBy('order_id')->get();

                $geo_graph = $this->getGeoGraphDropdowns();
                
                $approvalsArr = ApprovalParties::select('*')->where('parties_id', $request->id)->orderBy('id', 'DESC')
                ->get()
                ->map(function ($task) { 
                          $task->username = decryptString($task->username, 'username');
                          $task->status = decryptString($task->status, 'status');
                          $task->previous_status = decryptString($task->previous_status, 'previous_status');
                          $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                          $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                          $task->approval_status = decryptString($task->approval_status, 'approval_status');
                          $task->next_status = decryptString($task->next_status, 'next_status');
                          return $task;
                      })
                ->groupBy('unique_id')
                ->reverse();  
                
                //Hide Unwanted Fields for Expense Contract
                $hideExpenseFields = false;
                if(in_array(session()->get('contractSessionUserRole'), ['User','Marketing Manager'])){
                    $hideExpenseFields = true;
                }

                return view('parties::contract_parties.edit', compact('parties', 'representative', 'parties_label', 'country', 'branch', 'geo_graph', 'customFields','approvalsArr', 'hideExpenseFields'));
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }
    
    
    public function contract_parties_edit_ind(Request $request)
    {
        try {
            if ($request->isMethod('post')) {
                $parties_label = array();
                $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','nullable') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
                    ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
                    ->where('contract_parties_label.status', 1)->get();
                foreach ($label as $label_data) {
                    $parties_label[$label_data->name] = [
                        'name' => $label_data->name,
                        'label_name' => $label_data->label_name,
                        'is_required' => $label_data->is_required,
                        'error_text' => $label_data->error_text,
                        'is_regex' => $label_data->is_regex,
                        'regex_id' => $label_data->regex_id,
                        'regex_name' => $label_data->regex_name,
                        'regex_pattern' => $label_data->pattern
                    ];
                }
                $validator =  Validator::make($request->all(), [
                    'company_name' => 'required',
                    'company_contact' => 'required',
                    'company_email' => 'required|unique:contract_parties,company_email,'.$request->parties_id,
                    'pan' => $parties_label['pan']['is_required'],
                    'building_no' => 'required',
                    'area_name' => 'required',
                    'city' => 'required',
                    'state' => 'required',
                    'country' => 'required',
                    'legal_entity' => 'required',
                    'engagement_level' => 'required'
                ]);
                if ($validator->fails()) {
                    $errors = $validator->errors();
                    return redirect('parties/contract-parties-ind-edit/'.$request->parties_id)->withErrors($validator)->withInput();
                }
                $engagement_access_level = $engagement_branch = NULL;

                $parties = ContractParties::find($request->parties_id);
                //Add Approvers For Edited Vendors
                $getApprovers = PartyApprovalRules::select('approval_required_users');
                
                
                if ($request->engagement_level == "branch") {
                    $engagement_level = 1;
                    $engagement_branch = $request->engagement_branch;
                } else {
                    $engagement_level = 0;
                    $engagement_access_level = $request->engagement_access_level;
                }                

                if ($request->engagement_level == "branch") {
                    $getApprovers->whereRaw("FIND_IN_SET($request->engagement_branch, branch)");
                } else {
                    $branches = $this->getGeoHierarchyParentIds($request->engagement_access_level);
                   
                    $getApprovers->whereRaw("FIND_IN_SET($request->engagement_access_level, accesslevel)");
                    if (!empty($branches)) {
                        $getApprovers->whereIn('accessLevel', $branches , "OR");
                    }
                }

                $finalApprovers = $getApprovers->first();

                $approvers = [];
                if($finalApprovers){
                   $approvers =  json_decode($finalApprovers->approval_required_users);
                }


                $parties->update([
                    'company_name' => encryptString($request->company_name, 'company_name'),
                    'party_type' => $request->contract_type,
                    'company_contact' => $request->company_contact,
                    'company_email' => $request->company_email,
                    'building_no' => $request->building_no,
                    'area_name' => $request->area_name,
                    'landmark' => $request->landmark,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                    'pincode' => $request->pincode,
                    'website' => $request->website,
                    'gst' => encryptString($request->gst, 'gst'),
                    'pan' => encryptString($request->pan, 'pan'),
                    'vendor_code' => $request->vendor_code,
                    'active_vendor_code' => $request->active_vendor_code,
                    'legal_entity' => $request->legal_entity,
                    'role_in_contract' => $request->role_in_contract,
                    'approvers' => json_encode($approvers),
                    'engagement_level' => $engagement_level,
                    'engagement_branch' => $engagement_branch,
                    'engagement_access_level' => $engagement_access_level,
                    'is_related_party' => $request->is_related_party ?? 0,
                    'updated_by' => 1
                ]);
                $parties_id = $request->parties_id;
                
                $deletePrevReps = ContractPartiesRepresentative::where('parties_id', $parties_id)->delete();
                
                if($parties->approval_status == 0 && $parties->status == 0 && env('enable_party_approvals')){
                    $approvalExist = ApprovalParties::where('parties_id', $parties_id)->get();
                    if(count($approvalExist) == 0){
                        foreach ($approvers as $key => $appVal) {
                            $randNo = rand(0, 99999);
                            $unique_id = $parties_id . $randNo;
                            $approver_id = $appVal->id;
                            $users = AddUsers::select('id', decrypt_data('Email','AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->where('id', $approver_id)->get();                            
                                ApprovalParties::create([
                                    'username' => encryptString(json_encode(['email'=>$users[0]->Email, 'name' => $users[0]->FirstName]), 'username'),
                                    'previous_status' => encryptString('Idle', 'previous_status'),
                                    'status' => encryptString('inprogress', 'status'),
                                    'parties_id' => $parties_id,
                                    'orderval' => $key,
                                    'unique_id' => $unique_id,
                                    'flag' => 1,
                                    'approval_status' => encryptString('pending', 'approval_status'),
                                ]);
                            break;
                            
                        }
                    }
                }                

                foreach ($request->representative ?? [] as $value) {
                    //if (isset($value['representative_id'])) {
                        $representative = new ContractPartiesRepresentative;
                        $representative->parties_id =  $parties_id;
                        $representative->representative_name = $value['representative_name'];
                        $representative->representative_email = $value['representative_email'];
                        $representative->representative_designation = $value['representative_designation'];
                        $representative->representative_contact = $value['representative_contact'];
                        $representative->representative_nationality = $value['representative_nationality'];

                        $representative->status = 1;
                        $representative->created_by = 1;
                        $representative->updated_by = 1;
                        $representative->save();
                    //}
                }
                
                $deletePrevCusFields = CustomFieldsData::where('custom_field_group_id', $parties_id)->delete();
                
                if ($request->has('customFields')) {
                    foreach ($request->input('customFields') as $customField) {
                        if (isset($customField)) {

                            if (isset($customField['id']) && isset($customField['value']) && isset($parties_id)) {
                                CustomFieldsData::create([
                                    'custom_field_id' => $customField['id'],
                                    'custom_field_group' => 'parties',
                                    'custom_field_value' => $customField['value'],
                                    'custom_field_group_id' => $parties_id
                                ]);
                            }
                        }
                    }
                }
                
                return redirect('/parties/individual')->with('success', 'Parties has been Updated Successfully.');
            } else {
                $parties_label = array();
                $parties = ContractParties::find($request->id);

                $representative = ContractPartiesRepresentative::select('contract_parties_representative.*')
                    ->where('contract_parties_representative.parties_id', $request->id)
                    ->get();

                $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','unrequired') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
                    ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
                    ->where('contract_parties_label.status', 1)->get();

                foreach ($label as $label_data) {
                    $parties_label[$label_data->name] = [
                        'name' => $label_data->name,
                        'label_name' => $label_data->label_name,
                        'is_required' => $label_data->is_required,
                        'error_text' => $label_data->error_text,
                        'is_regex' => $label_data->is_regex,
                        'regex_id' => $label_data->regex_id,
                        'regex_name' => $label_data->regex_name,
                        'regex_pattern' => $label_data->pattern
                    ];
                }
                $country = Country::select('id', 'name')->get();
                $branch = Branch::select("id", decrypt_data('LegalName', 'branch'))->get();

                $customFields = CustomFields::where('status', 1)->where('contract_type', 0)->where('sub_type', 'iparty')->orderBy('order_id')->get();

                $geo_graph = $this->getGeoGraphDropdowns();
                
                $approvalsArr = ApprovalParties::select('*')->where('parties_id', $request->id)->orderBy('id', 'DESC')
                ->get()
                ->map(function ($task) { 
                          $task->username = decryptString($task->username, 'username');
                          $task->status = decryptString($task->status, 'status');
                          $task->previous_status = decryptString($task->previous_status, 'previous_status');
                          $task->next_action_item = decryptString($task->next_action_item, 'next_action_item');
                          $task->next_action_description = decryptString($task->next_action_description, 'next_action_description');
                          $task->approval_status = decryptString($task->approval_status, 'approval_status');
                          $task->next_status = decryptString($task->next_status, 'next_status');
                          return $task;
                      })
                ->groupBy('unique_id')
                ->reverse();                 

                return view('parties::contract_parties.editindividual', compact('parties', 'representative', 'parties_label', 'country', 'branch', 'geo_graph', 'customFields','approvalsArr'));
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }

    public function contract_parties_view_org(Request $request)
    {
        try {

            $parties_label = array();
            $parties = ContractParties::find($request->id);

            $representative = ContractPartiesRepresentative::select('contract_parties_representative.*')
                ->where('contract_parties_representative.parties_id', $request->id)
                ->get();

            $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','unrequired') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
                ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
                ->where('contract_parties_label.status', 1)->get();

            foreach ($label as $label_data) {
                $parties_label[$label_data->name] = [
                    'name' => $label_data->name,
                    'label_name' => $label_data->label_name,
                    'is_required' => $label_data->is_required,
                    'error_text' => $label_data->error_text,
                    'is_regex' => $label_data->is_regex,
                    'regex_id' => $label_data->regex_id,
                    'regex_name' => $label_data->regex_name,
                    'regex_pattern' => $label_data->pattern
                ];
            }
            $country = Country::select('id', 'name')->get();
            $branch = Branch::select("id", decrypt_data('LegalName', 'branch'))->get();

            $customFields = CustomFields::where('status', 1)->where('contract_type', 0)->orderBy('order_id')->get();

            $geo_graph = $this->getGeoGraphDropdowns();

            return view('parties::contract_parties.view', compact('parties', 'representative', 'parties_label', 'country', 'branch', 'geo_graph', 'customFields'));
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }
    
    public function contract_parties_view_ind(Request $request)
    {
        try {

                $parties_label = array();
                $parties = ContractParties::find($request->id);

                $representative = ContractPartiesRepresentative::select('contract_parties_representative.*')
                    ->where('contract_parties_representative.parties_id', $request->id)
                    ->get();

                $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','unrequired') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
                    ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
                    ->where('contract_parties_label.status', 1)->get();
  
                foreach ($label as $label_data) {
                    $parties_label[$label_data->name] = [
                        'name' => $label_data->name,
                        'label_name' => $label_data->label_name,
                        'is_required' => $label_data->is_required,
                        'error_text' => $label_data->error_text,
                        'is_regex' => $label_data->is_regex,
                        'regex_id' => $label_data->regex_id,
                        'regex_name' => $label_data->regex_name,
                        'regex_pattern' => $label_data->pattern
                    ];
                }
                $country = Country::select('id', 'name')->get();
                $branch = Branch::select("id", decrypt_data('LegalName', 'branch'))->get();

                $customFields = CustomFields::where('status', 1)->where('contract_type', 0)->orderBy('order_id')->get();

                $geo_graph = $this->getGeoGraphDropdowns();

                return view('parties::contract_parties.viewindividual', compact('parties', 'representative', 'parties_label', 'country', 'branch', 'geo_graph', 'customFields'));
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }

    /*** Work By Jeeva ***/
    public function contract_parties_bulk_import_org(Request $request)
    {

        //$contractTypes = ContractType::get();
        return view('parties::contract_parties_bulk_import.import');
    }
    
    public function contract_parties_bulk_import_ind(Request $request)
    {

        //$contractTypes = ContractType::get();
        return view('parties::contract_parties_bulk_import.importindividual');
    }

    public function contract_parties_bulk_check_view(Request $request)
    {
        
        $branchsUser = Branch::select(decrypt_data('LegalName', 'branch'), 'id')->get();
        //$contractTypes = ContractType::get();
        return view('parties::contract_parties_bulk_import.import_test', compact('branchsUser'));
    }


    public function contract_parties_template_download_org(Request $request)
    {

        $spreadsheet = new Spreadsheet();
        $writer = new Xlsx($spreadsheet);

        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setTitle('Organization');

        $maxrows = 10;

        $sheet->setCellValue('A1', 'Party Type');
        for ($row = 2; $row <= $maxrows; $row++) {
            $cell = 'A' . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the dropdown list.');
            $validation->setFormula1('"' . implode(',', ['Customer', 'Vendor', 'Supplier', 'Partner']) . '"');
        }

        $sheet->setCellValue('B1', 'Legal Entity');
        for ($row = 2; $row <= $maxrows; $row++) {
            $cell = 'B' . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the dropdown list.');
            $validation->setFormula1('"' . implode(',', ['Corporation', 'Partnership', 'Individual']) . '"');
        }

        $sheet->setCellValue('C1', 'Company Name');
        $sheet->setCellValue('D1', 'Company Email ID');
        $sheet->setCellValue('E1', 'GST');
        $sheet->setCellValue('F1', 'PAN');
        $sheet->setCellValue('G1', 'Company Contact Number');
        $sheet->setCellValue('H1', 'Building No');
        $sheet->setCellValue('I1', 'Area Name');
        $sheet->setCellValue('J1', 'Landmark');
        $sheet->setCellValue('K1', 'City');
        $sheet->setCellValue('L1', 'PinCode');
        $sheet->setCellValue('M1', 'Country');
        $sheet->setCellValue('N1', 'State');
        $sheet->setCellValue('O1', 'Website');
        $sheet->setCellValue('P1', 'Representative Name');
        $sheet->setCellValue('Q1', 'Representative Email ID');
        $sheet->setCellValue('R1', 'Representative Designation');
        $sheet->setCellValue('S1', 'Representative Contact Number');
        $sheet->setCellValue('T1', 'Representative Nationality');

        $sheet->setCellValue('U1', 'Access Level');
        for ($row = 2; $row <= $maxrows; $row++) {
            $cell = 'U' . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the dropdown list.');
            $validation->setFormula1('"' . implode(',', ['One', 'Two', 'Three']) . '"');
        }

        $branches_list = Branch::select(decrypt_data('LegalName', 'branch'))->pluck('LegalName');
        $branchs = collect($branches_list)
            ->transform(function ($item, $key) {
                return $item;
            })
            ->implode(",");

        $sheet->setCellValue('V1', 'Engagement Branch');
        for ($row = 2; $row <= $maxrows; $row++) {
            $cell = 'V' . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the dropdown list.');
            $validation->setFormula1('Branch!$A$1:$A$'.count($branches_list).'');
        }

        $sheet->setCellValue('W1', 'Role In Contract');
        for ($row = 2; $row <= $maxrows; $row++) {
            $cell = 'W' . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the dropdown list.');
            $validation->setFormula1('"' . implode(',', ['Buyer', 'Seller', 'Service Provider', 'Other']) . '"');
        }

        $sheet->setCellValue('X1', 'Is Related Party');
        for ($row = 2; $row <= $maxrows; $row++) {
            $cell = 'X' . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the dropdown list.');
            $validation->setFormula1('"' . implode(',', ['Yes', 'No']) . '"');
        }
        
        
        //For Custom Fields
        $sheet->setCellValue('Y1', 'Vendor Code');
        $startingColumn = "Z";
        $rows = 1;
        
        $customFields = CustomFields::where('status', 1)->where('contract_type', 0)->where('sub_type', '<>', null)->where('sub_type', '<>', 'iparty')->orderBy('order_id')->get();
        $k = 0;
        foreach ($customFields as $customField) {
        
            if ($customField->field_type == 'date') {
                $sheet->setCellValue($startingColumn . $rows,  $customField->field_name);
            } else {
                $sheet->setCellValue($startingColumn . $rows,  $customField->field_name);
            }
        
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = $startingColumn . $row;
                $validation = $sheet->getCell($cell)->getDataValidation();
        
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
        
                    $validation->setFormula1('"' . implode(',', $array) . '"');
                }
            }
        
            $k++;
            $startingColumn++;
        }
        
        $sheetNoB = 1;
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex($sheetNoB);
        $l =1;
        
        foreach($branches_list as $branchVal)
        {
            $spreadsheet->getActiveSheet()->setCellValue("A$l",$branchVal);
            $l++;
        }
        
        $spreadsheet->getActiveSheet()->setTitle('Branch');
        $spreadsheet->getSheetByName('Branch')->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);        


        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="contract_parties_organization_'.strtotime(date('y-m-d h:i:s')).'.xlsx"');
        header('Cache-Control: max-age=0');
    
        // Stream the file to the browser
        setcookie('preload', false, 0, "/");
        $writer->save('php://output');
        exit;

    }
    
    //For Individual
    public function contract_parties_template_download_ind(Request $request)
    {

        $spreadsheet = new Spreadsheet();
        $writer = new Xlsx($spreadsheet);

        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setTitle('individual');

        $maxrows = 10;

        $sheet->setCellValue('A1', 'Party Type');
        for ($row = 2; $row <= $maxrows; $row++) {
            $cell = 'A' . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the dropdown list.');
            $validation->setFormula1('"' . implode(',', ['Customer', 'Vendor', 'Supplier', 'Partner']) . '"');
        }

        $sheet->setCellValue('B1', 'Legal Entity');
        for ($row = 2; $row <= $maxrows; $row++) {
            $cell = 'B' . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the dropdown list.');
            $validation->setFormula1('"' . implode(',', ['Corporation', 'Partnership', 'Individual']) . '"');
        }
        
        $sheet->setCellValue('C1', 'Sub Party Type');
        for ($row = 2; $row <= $maxrows; $row++) {
            $cell = 'C' . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the dropdown list.');
            $validation->setFormula1('"' . implode(',', ['Individual', 'Organization']) . '"');
        }

        $sheet->setCellValue('D1', 'Name');
        $sheet->setCellValue('E1', 'Email ID');
        $sheet->setCellValue('F1', 'GST');
        $sheet->setCellValue('G1', 'PAN');
        $sheet->setCellValue('H1', 'Contact Number');
        $sheet->setCellValue('I1', 'Building No');
        $sheet->setCellValue('J1', 'Area Name');
        $sheet->setCellValue('K1', 'Landmark');
        $sheet->setCellValue('L1', 'City');
        $sheet->setCellValue('M1', 'PinCode');
        $sheet->setCellValue('N1', 'Country');
        $sheet->setCellValue('O1', 'State');
        $sheet->setCellValue('P1', 'Website');
        $sheet->setCellValue('Q1', 'Representative Name');
        $sheet->setCellValue('R1', 'Representative Email ID');
        $sheet->setCellValue('S1', 'Representative Designation');
        $sheet->setCellValue('T1', 'Representative Contact Number');
        $sheet->setCellValue('U1', 'Representative Nationality');

        $sheet->setCellValue('V1', 'Access Level');
        for ($row = 2; $row <= $maxrows; $row++) {
            $cell = 'V' . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the dropdown list.');
            $validation->setFormula1('"' . implode(',', ['One', 'Two', 'Three']) . '"');
        }

        $branches_list = Branch::select(decrypt_data('LegalName', 'branch'))->pluck('LegalName');
        $branchs = collect($branches_list)
            ->transform(function ($item, $key) {
                return $item;
            })
            ->implode(",");

        $sheet->setCellValue('W1', 'Engagement Branch');
        for ($row = 2; $row <= $maxrows; $row++) {
            $cell = 'W' . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the dropdown list.');
            $validation->setFormula1('Branch!$A$1:$A$'.count($branches_list).'');
        }

        $sheet->setCellValue('X1', 'Role In Contract');
        for ($row = 2; $row <= $maxrows; $row++) {
            $cell = 'X' . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the dropdown list.');
            $validation->setFormula1('"' . implode(',', ['Buyer', 'Seller', 'Service Provider', 'Other']) . '"');
        }

        $sheet->setCellValue('Y1', 'Is Related Party');
        for ($row = 2; $row <= $maxrows; $row++) {
            $cell = 'Y' . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the dropdown list.');
            $validation->setFormula1('"' . implode(',', ['Yes', 'No']) . '"');
        }
        
        //For Custom Fields
        $startingColumn = "Z";
        $rows = 1;
        
        $customFields = CustomFields::where('status', 1)->where('contract_type', 0)->where('sub_type', '<>', null)->where('sub_type', 'iparty')->orderBy('order_id')->get();
        $k = 0;
        foreach ($customFields as $customField) {
        
            if ($customField->field_type == 'date') {
                $sheet->setCellValue($startingColumn . $rows,  $customField->field_name);
            } else {
                $sheet->setCellValue($startingColumn . $rows,  $customField->field_name);
            }
        
            for ($row = 3; $row <= $maxrows; $row++) {
                $cell = $startingColumn . $row;
                $validation = $sheet->getCell($cell)->getDataValidation();
        
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
                    $validation->setFormula1('"' . implode(',', ['INR', 'USD']) . '"');
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
        
                   $validation->setFormula1('"' . implode(',', $array) . '"');
                }
            }
        
            $k++;
            $startingColumn++;
        }        

        $sheetNoB = 1;
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex($sheetNoB);
        $l =1;
        
        foreach($branches_list as $branchVal)
        {
            $spreadsheet->getActiveSheet()->setCellValue("A$l",$branchVal);
            $l++;
        }
        
        $spreadsheet->getActiveSheet()->setTitle('Branch');
        $spreadsheet->getSheetByName('Branch')->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);        


        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="contract_parties_individual'.strtotime(date('y-m-d h:i:s')).'.xlsx"');
        header('Cache-Control: max-age=0');
    
        // Stream the file to the browser
        setcookie('preload', false, 0, "/");
        $writer->save('php://output');
        exit;
    }

    public function contract_parties_upload_file_org(Request $request)
    {

        $file = $request->file('file');
        $filePath = $file->getPathname();

        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($filePath);

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
                            $rowData[] = $cell->getValue();
                        }
                        $sheetData[] = $rowData;
                    }
    
                    // Add the sheet data to the main data array with the sheet name as the key
                    $data[$sheetName] = $sheetData;
                }
            }
            
            $parties_label = array();
            $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','nullable') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
                ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
                ->where('contract_parties_label.status', 1)->get();
            foreach ($label as $label_data) {
                $parties_label[$label_data->name] = [
                    'name' => $label_data->name,
                    'label_name' => $label_data->label_name,
                    'is_required' => $label_data->is_required,
                    'error_text' => $label_data->error_text,
                    'is_regex' => $label_data->is_regex,
                    'regex_id' => $label_data->regex_id,
                    'regex_name' => $label_data->regex_name,
                    'regex_pattern' => $label_data->pattern
                ];
            }            
            // Pass the data to the view or redirect with the data
            session(['datafull' => $data, 'parties_label' => $parties_label]);
            
            
            return redirect()->back()->with('data', $data)->with('parties_label', $parties_label);
        } catch (\Exception $e) {
            // Handle exception
            return redirect()->back()->with('error', 'Error loading spreadsheet: ' . $e->getMessage());
        }
    }
    
    //For Individual
    public function contract_parties_upload_file_ind(Request $request)
    {

        $file = $request->file('file');
        $filePath = $file->getPathname();

        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($filePath);

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
                            $rowData[] = $cell->getValue();
                        }
                        $sheetData[] = $rowData;
                    }
    
                    // Add the sheet data to the main data array with the sheet name as the key
                    $data[$sheetName] = $sheetData;
                }
            }

            $parties_label = array();
            $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','nullable') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
                ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
                ->where('contract_parties_label.status', 1)->get();
            foreach ($label as $label_data) {
                $parties_label[$label_data->name] = [
                    'name' => $label_data->name,
                    'label_name' => $label_data->label_name,
                    'is_required' => $label_data->is_required,
                    'error_text' => $label_data->error_text,
                    'is_regex' => $label_data->is_regex,
                    'regex_id' => $label_data->regex_id,
                    'regex_name' => $label_data->regex_name,
                    'regex_pattern' => $label_data->pattern
                ];
            }            
            // Pass the data to the view or redirect with the data
            session(['datafull' => $data, 'parties_label' => $parties_label]);

            return redirect()->back()->with('data', $data);
        } catch (\Exception $e) {
            // Handle exception
            return redirect()->back()->with('error', 'Error loading spreadsheet: ' . $e->getMessage());
        }
    }

    //For Bulk Check
    public function contract_parties_bulk_check(Request $request)
    {

        $file = $request->file('file');
        $filePath = $file->getPathname();

        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($filePath);

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
                            $rowData[] = $cell->getValue();
                        }
                        $sheetData[] = $rowData;
                    }
    
                    // Add the sheet data to the main data array with the sheet name as the key
                    $data[$sheetName] = $sheetData;
                }
            }

            $parties_label = array();
            $label = ContractPartiesLabel::selectRaw("contract_parties_label.id,contract_parties_label.name,contract_parties_label.label_name,if(is_required = 1,'required','nullable') as is_required,error_text,is_regex,regex_id,regex.name as regex_name,regex.pattern")
                ->leftJoin('regex', 'regex.id', '=', 'contract_parties_label.regex_id')
                ->where('contract_parties_label.status', 1)->get();
            foreach ($label as $label_data) {
                $parties_label[$label_data->name] = [
                    'name' => $label_data->name,
                    'label_name' => $label_data->label_name,
                    'is_required' => $label_data->is_required,
                    'error_text' => $label_data->error_text,
                    'is_regex' => $label_data->is_regex,
                    'regex_id' => $label_data->regex_id,
                    'regex_name' => $label_data->regex_name,
                    'regex_pattern' => $label_data->pattern
                ];
            }            
            // Pass the data to the view or redirect with the data
            session(['datafull' => $data, 'parties_label' => $parties_label]);

            return redirect()->back()->with('data', $data);
        } catch (\Exception $e) {
            // Handle exception
            return redirect()->back()->with('error', 'Error loading spreadsheet: ' . $e->getMessage());
        }
    }
    
    
    public function contract_parties_store_file_org(Request $request)
    {

        $party_type_data = array('Customer' => 'customer', 'Vendor' => 'vendor', 'Supplier' => 'Supplier', 'Partner' => 'partner');
        $legal_entity_data = array('Company' => 'Company', 'Corporation' => 'corporation', 'Partnership' => 'partnership', 'Individual' => 'individual', 'LLP' => 'llp', 'Trust' => 'trust', 'aop' => 'AOP');

        $value = session('datafull');

        $dataSaved = 0;
        foreach ($value as $shName => $sheetss) {
            
            if(strtolower($shName) == 'organization'){
                foreach ($sheetss as $key => $val) {



                if ($key > 0) {


                    // if (!isset($val['4']) && !isset($val['5'])) {
                    //     continue;
                    // }

                    if (!isset($val['1'])) {
                        break;
                    }
                    $engagement_access_level = $engagement_branch = NULL;

                    $company_name = $val['2'];
                    $company_contact = $val['6'];
                    $company_email = $val['3'];

                    $building_no = $val['7'];
                    $area_name = $val['8'];
                    $landmark = $val['9'];
                    $city = $val['10'];

                    $state = $val['13'];


                    $state = State::select("name", "id")
                        ->where('name', $state)
                        ->pluck('id')->first();

                    $country = 1;


                    $pincode = $val['11'];
                    $website = $val['14'];
                    $gst = $val['4'];
                    $pan = $val['5'];
                    $legal_entity = $legal_entity_data[$val['1']];
                    $party_type = $party_type_data[$val['0']];

                    $role_in_contract = $val['22'];
                    $engagement_level = $val['20'];
                    $engagement_branch = $val['21'];
                    $engagement_access_level = null;
                    $is_related_party = ($val['23'] == 'Yes') ? 1 : 0;

                    $representative_name = $val['15'];
                    $representative_email = $val['16'];
                    $representative_designation = $val['17'];
                    $representative_contact = $val['18'];
                    $representative_nationality = $val['19'];





                    $parties = new ContractParties();
                    $parties->company_name = encryptString($company_name, 'company_name');

                    $parties->party_type = $party_type;

                    $parties->company_contact = $company_contact;

                    $parties->company_email = $company_email;

                    $parties->building_no = $building_no;

                    $parties->area_name = $area_name;

                    $parties->landmark = $landmark;

                    $parties->city = $city;
                    $parties->state = $state;
                    $parties->country = $country;
                    $parties->pincode = $pincode;
                    $parties->website = $website;
                    $parties->gst = encryptString($gst, 'gst');
                    $parties->pan = encryptString($pan, 'pan');


                    $parties->legal_entity = $legal_entity;

                    $parties->role_in_contract = $role_in_contract;
                    if ($engagement_level == "branch") {
                        $parties->engagement_level = 1;
                        $parties->engagement_branch = $engagement_branch;
                    } else {
                        $parties->engagement_level = 0;
                        $parties->engagement_access_level = $engagement_access_level;
                    }
                    $parties->is_related_party = $is_related_party;
                    $parties->vendor_code = isset($val['24']) ? $val['24'] : null;
                    
                    $parties->approvers = json_encode([]);
                    $parties->status = 1;
                    $parties->created_by = 1;
                    $parties->updated_by = 1;
                    $parties->save();
                    $parties_id = $parties->id;

                    $representative = new ContractPartiesRepresentative;
                    $representative->parties_id =  $parties_id;
                    $representative->representative_name = $representative_name;
                    $representative->representative_email = $representative_email;
                    $representative->representative_designation = $representative_designation;
                    $representative->representative_contact = $representative_contact;
                    $representative->representative_nationality = $representative_nationality;

                    $representative->status = 1;
                    $representative->created_by = 1;
                    $representative->updated_by = 1;
                    $representative->save();

                    
                    $customFields = CustomFields::where('status', 1)->where('sub_type', 'party')->orderBy('order_id')->pluck('custom_field_id');


                    $cudo = 0;

                    foreach ($val as $cckey => $customField) {

                        if ($cckey > 24) {

                            if (isset($customField)) {
                                CustomFieldsData::create([
                                    'custom_field_id' => $customFields[$cudo],
                                    'custom_field_group' => 'parties',
                                    'custom_field_value' =>  $customField,
                                    'custom_field_group_id' => $parties_id
                                ]);
                            }
                            $cudo++;
                        }
                    }
                    
                    $dataSaved++;
                }
            }
            }else{
                return redirect()->back()->with('error', "Invalid Sheet Name ($shName) Please use sheet name as <b>Organization</b>");
            }
        }
        
        if($dataSaved > 0){
            return redirect()->back()->with('success', 'Data uploaded successfully.');
        }else{
           return redirect()->back()->with('error', 'Data not uploaded/Invalid Data.'); 
        }
    }

    //For Individual
    public function contract_parties_store_file_ind(Request $request)
    {

        $party_type_data = array('Customer' => 'customer', 'Vendor' => 'vendor', 'Supplier' => 'Supplier', 'Partner' => 'partner');
        $party_sub_type_data = array('Individual' => 'individual', 'Organization' => 'organization');
        $legal_entity_data = array('Corporation' => 'corporation', 'Partnership' => 'partnership', 'Individual' => 'individual', 'LLP' => 'llp', 'Trust' => 'trust', 'aop' => 'AOP');

        $value = session('datafull');


        foreach ($value as $shName => $sheetss) {
            if(strtolower($shName) == 'individual'){
                foreach ($sheetss as $key => $val) {



                if ($key > 0) {


                    // if (!isset($val['4']) && !isset($val['5'])) {
                    //     continue;
                    // }

                    if (!isset($val['1'])) {
                        break;
                    }
                    $engagement_access_level = $engagement_branch = NULL;

                    $company_name = $val['3'];
                    $company_contact = $val['7'];
                    $company_email = $val['4'];

                    $building_no = $val['8'];
                    $area_name = $val['9'];
                    $landmark = $val['10'];
                    $city = $val['10'];

                    $state = $val['14'];


                    $state = State::select("name", "id")
                        ->where('name', $state)
                        ->pluck('id')->first();

                    $country = 1;


                    $pincode = $val['12'];
                    $website = $val['15'];
                    $gst = $val['5'];
                    $pan = $val['6'];
                    $legal_entity = $legal_entity_data[$val['1']];
                    $party_type = $party_type_data[$val['0']];
                    $party_sub_type = $party_sub_type_data[$val['2']];

                    $role_in_contract = $val['23'];
                    $engagement_level = $val['21'];
                    $engagement_branch = $val['22'];
                    $engagement_access_level = null;
                    $is_related_party = ($val['24'] == 'Yes') ? 1 : 0;

                    $representative_name = $val['16'];
                    $representative_email = $val['17'];
                    $representative_designation = $val['18'];
                    $representative_contact = $val['19'];
                    $representative_nationality = $val['20'];





                    $parties = new ContractParties();
                    $parties->company_name = encryptString($company_name, 'company_name');

                    $parties->party_type = $party_type;
                    
                    $parties->party_sub_type = ($party_sub_type == 'individual') ? $party_sub_type : $party_type;

                    $parties->company_contact = $company_contact;

                    $parties->company_email = $company_email;

                    $parties->building_no = $building_no;

                    $parties->area_name = $area_name;

                    $parties->landmark = $landmark;

                    $parties->city = $city;
                    $parties->state = $state;
                    $parties->country = $country;
                    $parties->pincode = $pincode;
                    $parties->website = $website;
                    $parties->gst = encryptString($gst, 'gst');
                    $parties->pan = encryptString($pan, 'pan');


                    $parties->legal_entity = $legal_entity;

                    $parties->role_in_contract = $role_in_contract;
                    if ($engagement_level == "branch") {
                        $parties->engagement_level = 1;
                        $parties->engagement_branch = $engagement_branch;
                    } else {
                        $parties->engagement_level = 0;
                        $parties->engagement_access_level = $engagement_access_level;
                    }
                    $parties->is_related_party = $is_related_party;
                    
                    $parties->approvers = json_encode([]);
                    $parties->status = 1;
                    $parties->created_by = 1;
                    $parties->updated_by = 1;

                    $parties->save();
                  
                    $parties_id = $parties->id;
                    
                    // var_dump( $parties_id )."<br/>";
                    // // if($parties_id){
                    // //     echo $company_name;
                    // //     die;
                    // // }

                    $representative = new ContractPartiesRepresentative;
                    $representative->parties_id =  $parties_id;
                    $representative->representative_name = $representative_name;
                    $representative->representative_email = $representative_email;
                    $representative->representative_designation = $representative_designation;
                    $representative->representative_contact = $representative_contact;
                    $representative->representative_nationality = $representative_nationality;

                    $representative->status = 1;
                    $representative->created_by = 1;
                    $representative->updated_by = 1;
                    $representative->save();


                    $customFields = CustomFields::where('status', 1)->where('sub_type', 'iparty')->orderBy('order_id')->pluck('custom_field_id');


                    $cudo = 0;

                    foreach ($val as $cckey => $customField) {

                        if ($cckey > 24) {

                            if (isset($customField)) {
                                CustomFieldsData::create([
                                    'custom_field_id' => $customFields[$cudo],
                                    'custom_field_group' => 'parties',
                                    'custom_field_value' =>  $customField,
                                    'custom_field_group_id' => $parties_id
                                ]);
                            }
                            $cudo++;
                        }
                    }
                }
            }
            }else{
                return redirect()->back()->with('error', "Invalid Sheet Name ($shName) Please use sheet name as <b>Individual</b>");
            }
        }

        return redirect()->back()->with('success', 'Data uploaded successfully.');
    }

    /*** Work By Jeeva ***/
    
    /**
     * @date:: 24 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: get states function
     **/
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
    public function financial_data(Request $request)
    {
        try {
            $financial = FinancialLimit::selectRaw('financial_limit.id,IFNULL(decrypt_data(BranchName,"branch"), "Any / All") as BranchName,IFNULL(entitybusiness.name, "Any / All") as department,IFNULL(contract_categories.name, "Any / All") as contract_categories_name,IFNULL(contract_type.contract_type, "Any / All") as contract_type,financial_limit.lower_limit,financial_limit.upper_limit,financial_limit.approval_type,financial_limit.approval_required_users')
                // $financial = FinancialLimit::selectRaw('financial_limit.id,IFNULL(aes_decrypt(BranchName,"dummy.branch"), "Any / All") as BranchName,IFNULL(entitybusiness.name, "Any / All") as department,IFNULL(contract_categories.name, "Any / All") as contract_categories_name,IFNULL(contract_type.contract_type, "Any / All") as contract_type,financial_limit.lower_limit,financial_limit.upper_limit,financial_limit.approval_type,financial_limit.approval_required_users')
                ->leftJoin('branch', 'branch.id', '=', 'financial_limit.location')
                ->leftJoin('entitybusiness', 'entitybusiness.id', '=', 'financial_limit.department')
                ->leftJoin('contract_categories', 'contract_categories.id', '=', 'financial_limit.category')
                ->leftJoin('contract_type', 'contract_type.contract_type_id', '=', 'financial_limit.contract_type')
                ->orderby('financial_limit.location', 'asc')
                ->orderby('financial_limit.department', 'asc')
                ->orderby('financial_limit.category', 'asc')
                ->get()->toArray();


            return response()->json([
                'data' => $financial,
                'draw' => $request->input('draw') ?? 1,
                'recordsTotal' => count($financial),
                'recordsFiltered' => count($financial),
            ]);
            return response()->json($financial, 200);
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
    public function financial_old(Request $request)
    {
        $total_count =  FinancialLimit::select('id')->get()->count();
        $financial = FinancialLimit::select('financial_limit.id', 'financial_limit.lower_limit', 'financial_limit.upper_limit', 'financial_limit.approval_type', decrypt_data('BranchName', 'branch'), 'entitybusiness.name as department', 'contract_categories.name as contract_categories_name', 'contract_type.contract_type', 'financial_limit.approval_required_users')
            ->leftJoin('branch', 'branch.id', '=', 'financial_limit.location')
            ->leftJoin('entitybusiness', 'entitybusiness.id', '=', 'financial_limit.department')
            ->leftJoin('contract_categories', 'contract_categories.id', '=', 'financial_limit.category')
            ->leftJoin('contract_type', 'contract_type.contract_type_id', '=', 'financial_limit.contract_type')
            ->orderby('financial_limit.location', 'asc')
            ->orderby('financial_limit.department', 'asc')
            ->orderby('financial_limit.category', 'asc')
            ->paginate(10);
        return view('parties::contract_parties.financial.index_old', compact('total_count', 'financial'))
            ->with('i', (request()->input('page', 1) - 1) * 10);
    }
    /**
     * @date:: 28 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: financial Add function
     **/
    public function financial_add(Request $request)
    {
        try {
            if ($request->isMethod('post')) {
                //return $request->post();
                $validator =  Validator::make($request->all(), [
                    'location' => 'required',
                    'department' => 'required',
                    'category' => 'required',
                    'contract_type' => 'required'
                ]);
                if ($validator->fails()) {
                    $errors = $validator->errors();
                    return redirect('financial-add')->withErrors($validator)->withInput();
                }
                $check_financial = FinancialLimit::select('id')
                    ->where('location', $request->location)
                    ->where('department', $request->department)
                    ->where('category', $request->category)
                    ->where('contract_type', $request->contract_type)
                    ->get();
                if (count($check_financial) > 0) {
                    $exist_error = array('This location,department and category,contract_type field value already exist. Try other value');
                    return redirect('financial-add')->withErrors($exist_error)->withInput();
                }

                $approval_users = [];
                if ($request->approval_status == 'required') {
                    foreach ($request->approval_required_users as $value) {
                        $parts = explode(':', $value);
                        $id = $parts[0];
                        $name = $parts[1];
                        $approval_users[] = [
                            'id' => $id,
                            'name' => $name
                        ];
                    }
                }
                $financial = new FinancialLimit();
                $financial->location = $request->location;
                $financial->department = $request->department;
                $financial->category = $request->category;
                $financial->contract_type = $request->contract_type;
                $financial->lower_limit = $request->lower_limit;
                $financial->upper_limit = $request->upper_limit;
                $financial->approver = $request->approver;
                $financial->approval_type = $request->approval_type;
                $financial->approval_status = $request->approval_status;
                $financial->approval_required_users = json_encode($approval_users);

                $financial->status = 1;
                $financial->save();
                return redirect()->route('parties::financial')->with('success', 'Financial has been Created Successfully.');
            } else {
                $contract_type = ContractType::select('contract_type_id', 'contract_type')->get();
                $contract_categories = ContractCategories::select('id', 'name')->get();
                $branch = Branch::select("id", decrypt_data('BranchName', 'branch'))->get();
                $entity_business = EntityBusiness::select('id', 'name')->where('entityid', 1)->get();
                $add_users = AddUsers::select("id", decrypt_data('FirstName', 'AddUsers'))->get();
                //print_r($add_users);exit;
                return view('parties::contract_parties.financial.create', compact('contract_type', 'branch', 'contract_categories', 'add_users', 'entity_business'));
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }
    /**
     * @date:: 30 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: check limit function
     **/
    public function check_limit(Request $request)
    {
        try {
            if ($request->isMethod('post')) {
                //return $request->post();
                $validator =  Validator::make($request->all(), [
                    'location' => 'required',
                    'department' => 'required',
                    'category' => 'required',
                    'contract_type' => 'required',
                    'lower_limit' => 'required',
                    'upper_limit' => 'required'
                ]);
                if ($validator->fails()) {
                    $errors = $validator->errors();
                    return redirect('financial-add')->withErrors($validator)->withInput();
                }
                $lower_limit = (int)$request->lower_limit;
                $upper_limit = (int)$request->upper_limit;
                if (isset($request->financial_id)) {
                    $check_limit = FinancialLimit::select('id')
                        ->where('location', $request->location)
                        ->where('department', $request->department)
                        ->where('category', $request->category)
                        ->where('contract_type', $request->contract_type)
                        //->where([['lower_limit','<=',$lower_limit],['upper_limit','>=',$upper_limit]])
                        //->whereRaw('(lower_limit <= '.$lower_limit.' and upper_limit >= '.$upper_limit.') or (lower_limit >= '.$lower_limit.' or upper_limit >= '.$upper_limit.' )')
                        ->where('upper_limit', '>=', $lower_limit)
                        ->where('id', '!=', $request->financial_id)
                        ->get();
                } else {
                    $check_limit = FinancialLimit::select('id')
                        ->where('location', $request->location)
                        ->where('department', $request->department)
                        ->where('category', $request->category)
                        ->where('contract_type', $request->contract_type)
                        //->where([['lower_limit','<=',$lower_limit],['upper_limit','>=',$upper_limit]])
                        //->orwhere('lower_limit','>=',$lower_limit)
                        ->where('upper_limit', '>=', $lower_limit)
                        ->get();
                }
                //print_r(count($check_limit));exit;
                if (count($check_limit) > 0) {
                    return response()->json(['status' => false, 'message' => 'lower_limit and upper_limit value is inbetween there'], 200);
                } else {
                    return response()->json(['status' => true, 'message' => 'value not there'], 200);
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return response()->json(['status' => false, 'message' => $message], 500);
        }
    }
    /**
     * @date:: 30 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: Financial delete function
     **/
    public function financial_delete(Request $request)
    {
        try {
            $financial = FinancialLimit::find($request->id);
            //print_r($request->id);exit;
            $financial->delete();
            return redirect()->route('parties::financial')->with('success', 'Financial deleted Successfully.');
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }
    /**
     * @date:: 30 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: Financial edit function
     **/
    public function financial_edit(Request $request)
    {
        try {
            if ($request->isMethod('post')) {
                //return $request->post();
                $validator =  Validator::make($request->all(), [
                    'location' => 'required',
                    'department' => 'required',
                    'category' => 'required',
                    'contract_type' => 'required'
                ]);
                if ($validator->fails()) {
                    $errors = $validator->errors();
                    return redirect('financial-edit/' . $request->financial_id)->withErrors($validator)->withInput();
                }
                $check_financial = FinancialLimit::select('id')
                    ->where('location', $request->location)
                    ->where('department', $request->department)
                    ->where('category', $request->category)
                    ->where('contract_type', $request->contract_type)
                    ->where('id', '!=', $request->financial_id)
                    ->get();
                if (count($check_financial) > 0) {
                    $exist_error = array('This location,department and category,contract_type field value already exist. Try other value');
                    return redirect('financial-edit/' . $request->financial_id)->withErrors($exist_error)->withInput();
                }
                $approval_users = [];
                if ($request->approval_status == 'required') {
                    foreach ($request->approval_required_users as $value) {
                        $parts = explode(':', $value);
                        $id = $parts[0];
                        $name = $parts[1];
                        $approval_users[] = [
                            'id' => $id,
                            'name' => $name
                        ];
                    }
                }
                $financial = FinancialLimit::find($request->financial_id);
                $financial->update([
                    'location' => $request->location,
                    'department' => $request->department,
                    'category' => $request->category,
                    'contract_type' => $request->contract_type,
                    'lower_limit' => $request->lower_limit,
                    'upper_limit' => $request->upper_limit,
                    'approver' => $request->approver,
                    'approval_type' => $request->approval_type,
                    'approval_status' => $request->approval_status,
                    'approval_required_users' => json_encode($approval_users)
                ]);
                return redirect()->route('parties::financial')->with('success', 'Financial has been Updated Successfully.');
            } else {
                $financial = FinancialLimit::find($request->id);

                $contract_type = ContractType::select('contract_type_id', 'contract_type')->get();
                $contract_categories = ContractCategories::select('id', 'name')->get();
                $branch = Branch::select("id", decrypt_data('BranchName', 'branch'))->get();
                $entity_business = EntityBusiness::select('id', 'name')->where('entityid', 1)->get();
                $add_users = AddUsers::select("id", decrypt_data('FirstName', 'AddUsers'))->get();
                //print_r($add_users);exit;
                return view('parties::contract_parties.financial.edit', compact('financial', 'contract_type', 'branch', 'contract_categories', 'add_users', 'entity_business'));
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }
    /**
     * @date:: 24 May 2024,  
     * @author :: Mangaleswari, 
     * @desc:: get users function
     **/
    public function getUsers(Request $request)
    {
        try {
            $add_users = AddUsers::select("id", decrypt_data('FirstName', 'AddUsers'))->get();
            return $add_users;
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return $message;
        }
    }

    /**
     * @date:: 06 Jun 2024,  
     * @author :: Mangaleswari, 
     * @desc:: get Approvers function
     **/
    public function getApprovers(Request $request)
    {
        try {
            $validator =  Validator::make($request->all(), [
                'location' => 'required',
                'department' => 'required',
                'category' => 'required',
                'contract_type' => 'required',
                'contract_value' => 'required'
            ]);
            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json(['status' => false, 'message' => $errors, 'data' => []], 422);
            }
            $location = $request->location;
            $department = $request->department;
            $category = $request->category;
            $contract_type = $request->contract_type;
            $contract_value = $request->contract_value;
            $financial_limit = [];
            $financial_limit = FinancialLimit::select("id", "approval_type", "approval_status", "approval_required_users as approver")
                ->where([['location', '=', $location], ['department', '=', $department], ['category', '=', $category], ['contract_type', '=', $contract_type], ['status', '=', 1]])
                ->whereRaw('(' . $contract_value . ' BETWEEN lower_limit AND upper_limit)')
                ->get();

            $where_clause = array(
                0 => 'location = ' . $location . ' AND  department = ' . $department . ' AND  category= ' . $category . ' AND  contract_type = ' . $contract_type,
                1 => 'location = ' . $location . ' AND  department = ' . $department . ' AND category= ' . $category . ' AND ( contract_type = ' . $contract_type . ' OR contract_type = 0)',
                2 => 'location = ' . $location . ' AND  department = ' . $department . ' AND  ( category= ' . $category . ' OR category = 0 ) AND ( contract_type = ' . $contract_type . ' OR contract_type = 0)',
                3 => 'location = ' . $location . ' AND ( department = ' . $department . ' OR department = 0) AND  ( category= ' . $category . ' OR category = 0 ) AND ( contract_type = ' . $contract_type . ' OR contract_type = 0)',
                4 => '( location = ' . $location . '  OR location = 0) AND  ( department = ' . $department . ' OR department = 0) AND  ( category= ' . $category . ' OR category = 0 ) AND ( contract_type = ' . $contract_type . ' OR contract_type = 0)'
            );

            $contract_where_clause = array(
                0 => '(' . $contract_value . ' BETWEEN lower_limit AND upper_limit OR lower_limit is null AND upper_limit is null)',
                1 => '(' . $contract_value . ' BETWEEN lower_limit AND upper_limit OR lower_limit is null AND upper_limit is null)',
                2 => '(' . $contract_value . ' BETWEEN lower_limit AND upper_limit OR lower_limit is null AND upper_limit is null)',
                3 => '(' . $contract_value . ' BETWEEN lower_limit AND upper_limit OR lower_limit is null AND upper_limit is null)',
                4 => '(' . $contract_value . ' BETWEEN lower_limit AND upper_limit OR lower_limit is null AND upper_limit is null)',
            );
            $i = 0;
            do {
                if (count($financial_limit) > 0) {
                    return response()->json(['status' => true, 'message' => '', 'data' => $financial_limit], 200);
                    break;
                }
                $financial_limit = FinancialLimit::select("id", "approval_type", "approval_status", "approval_required_users as approver")
                    ->whereRaw($where_clause[$i])
                    ->where('status', 1)
                    ->whereRaw($contract_where_clause[$i])
                    ->get();
                $i++;
                if (($i == 5) && (count($financial_limit) == 0)) {
                    return response()->json(['status' => false, 'message' => 'No records found', 'data' => []], 404);
                }
            } while ($i < 6);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
            return response()->json(['status' => false, 'message' => $message, 'data' => []], 500);
        }
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $data = [];
        //print_r($request->filled('q'));exit;
        if ($request->filled('q')) {
            $data = User::select("name", "id")
                ->where('name', 'LIKE', '%' . $request->get('q') . '%')
                ->get();
        } else {
            $data = User::select("name", "id")
                ->get();
        }
        return response()->json($data);
    }

    /**
     * Display the vendor import upload form.
     */
    public function vendor_import_view(Request $request)
    {
        $batchId = $request->query('batch');
        $preview = false;
        $matched = [];
        $unmatched = [];

        if ($batchId) {
            $basePath = storage_path('app/vendor_imports/' . basename($batchId));
            $resultsFile = $basePath . '/results.json';
            if (file_exists($resultsFile)) {
                $results = json_decode(file_get_contents($resultsFile), true);
                $matched = $results['matched'] ?? [];
                $unmatched = $results['unmatched'] ?? [];

                // Always hide rows that are already validated.
                $matched = array_values(array_filter($matched, function ($row) {
                    return (int) ($row['already_valid'] ?? 0) !== 1;
                }));

                // Re-check current DB state to avoid showing stale cache items.
                $partyIds = collect($matched)
                    ->pluck('party_id')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->filter(function ($id) {
                        return $id > 0;
                    })
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($partyIds)) {
                    $validMap = ContractParties::withoutGlobalScopes()
                        ->whereIn('id', $partyIds)
                        ->pluck('valid', 'id')
                        ->map(function ($value) {
                            return (int) $value;
                        })
                        ->toArray();

                    $matched = array_values(array_filter($matched, function ($row) use ($validMap) {
                        $partyId = (int) ($row['party_id'] ?? 0);
                        return $partyId > 0 && (int) ($validMap[$partyId] ?? 0) !== 1;
                    }));
                }

                $preview = true;
            }
        }

        return view('parties::vendor_import.index', compact('preview', 'matched', 'unmatched', 'batchId'));
    }

    /**
     * Step 1: Upload Excel file, parse rows to temp JSON, decrypt all parties to temp JSON.
     * Returns JSON with batch_id and total_rows for the frontend to start chunk processing.
     */
    public function vendor_import_upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $expectsJson = $request->expectsJson()
            || $request->ajax()
            || str_contains(strtolower((string) $request->header('Accept')), 'application/json')
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        try {
            $batchId = uniqid('vi_', true);
            $basePath = storage_path('app/vendor_imports/' . $batchId);
            if (!is_dir($basePath)) {
                mkdir($basePath, 0755, true);
            }

            // Parse Excel with read-data-only for speed
            $reader = IOFactory::createReaderForFile($request->file('file')->getPathname());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($request->file('file')->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            // Parse rows, skip header
            $excelRows = [];
            foreach (array_slice($rows, 1) as $row) {
                if (!array_filter($row)) continue;
                $excelRows[] = [
                    'vendor_code'        => trim($row[0] ?? ''),
                    'active_vendor_code' => trim($row[1] ?? ''),
                    'vendor_name'        => trim($row[2] ?? ''),
                    'pan'                => trim($row[3] ?? ''),
                    's_no'               => trim($row[4] ?? ''),
                ];
            }
            unset($rows);

            if (empty($excelRows)) {
                return response()->json(['status' => false, 'message' => 'No data found in the uploaded file.'], 422);
            }

            // Save parsed rows to temp file
            file_put_contents($basePath . '/rows.json', json_encode($excelRows));

            // Pre-decrypt all contract parties and build indexes (only vendors pending validation)
            $allParties = ContractParties::select('id', 'company_name', 'pan', 'vendor_code', 'active_vendor_code', 'valid', 'status')
                ->where(function ($query) {
                    $query->whereNull('valid')->orWhere('valid', 0);
                })
                ->get();

            $panIndex = [];
            $nameIndex = [];
            $partyMap = [];

            foreach ($allParties as $party) {
                $decryptedPan = decryptString($party->pan, 'pan');
                $decryptedName = decryptString($party->company_name, 'company_name');

                $normalizedPan = strtoupper(trim($decryptedPan ?? ''));
                $normalizedName = $this->normalizeVendorName($decryptedName ?? '');

                if ($normalizedPan !== '') {
                    $panIndex[$normalizedPan][] = $party->id;
                }
                if ($normalizedName !== '') {
                    $nameIndex[$normalizedName][] = $party->id;
                }

                $partyMap[$party->id] = [
                    'id'                  => $party->id,
                    'name'                => $decryptedName,
                    'normalized_name'     => $normalizedName,
                    'pan'                 => $decryptedPan,
                    'vendor_code'         => $party->vendor_code,
                    'active_vendor_code'  => $party->active_vendor_code,
                    'valid'               => $party->valid,
                ];
            }
            unset($allParties);

            // Count contracts per party in one query instead of N+1
            $contractCounts = ContractPartyData::select('contract_party_exe_id', DB::raw('COUNT(*) as cnt'))
                ->whereIn('contract_party_exe_id', array_keys($partyMap))
                ->groupBy('contract_party_exe_id')
                ->pluck('cnt', 'contract_party_exe_id')
                ->toArray();

            foreach ($partyMap as $pid => &$pdata) {
                $pdata['contracts_count'] = $contractCounts[$pid] ?? 0;
                $pdata['locations'] = [];
                $pdata['location_text'] = '';
            }
            unset($contractCounts);

            // Collect party location names for preview filters
            $partyLocationRows = ContractPartyData::select('contract_party_exe_id', 'contract_party_location_id')
                ->whereIn('contract_party_exe_id', array_keys($partyMap))
                ->whereNotNull('contract_party_location_id')
                ->get();

            if ($partyLocationRows->isNotEmpty()) {
                $locationIds = $partyLocationRows->pluck('contract_party_location_id')->unique()->values()->all();
                $branchMap = Branch::select('id', decrypt_data('BranchName', 'branch'))
                    ->whereIn('id', $locationIds)
                    ->get()
                    ->keyBy('id');

                foreach ($partyLocationRows->groupBy('contract_party_exe_id') as $partyId => $rowsForParty) {
                    if (!isset($partyMap[$partyId])) {
                        continue;
                    }

                    $names = [];
                    foreach ($rowsForParty as $partyLocRow) {
                        $name = trim((string) data_get($branchMap->get($partyLocRow->contract_party_location_id), 'BranchName', ''));
                        if ($name !== '') {
                            $names[] = $name;
                        }
                    }

                    $names = array_values(array_unique($names));
                    $partyMap[$partyId]['locations'] = $names;
                    $partyMap[$partyId]['location_text'] = implode(', ', $names);
                }
            }

            // Save party index to temp file
            file_put_contents($basePath . '/party_index.json', json_encode([
                'panIndex'  => $panIndex,
                'nameIndex' => $nameIndex,
                'partyMap'  => $partyMap,
            ]));

            // Initialize empty results file
            file_put_contents($basePath . '/results.json', json_encode([
                'matched'   => [],
                'unmatched' => [],
            ]));

            if ($expectsJson) {
                return response()->json([
                    'status'     => true,
                    'batch_id'   => $batchId,
                    'total_rows' => count($excelRows),
                ]);
            }

            return redirect()->route('parties.vendor_import_view')->with(
                'success',
                'File uploaded successfully. Processing starts via JavaScript on this page. If you are seeing this repeatedly, please enable JavaScript and retry.'
            );

        } catch (\Exception $e) {
            if ($expectsJson) {
                return response()->json(['status' => false, 'message' => 'Error processing file: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }

    /**
     * Step 2: Process a chunk of rows (AJAX). Called repeatedly by frontend.
     * Expects: batch_id, offset, limit (default 500)
     */
    public function vendor_import_process_chunk(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|string|max:100',
            'offset'   => 'required|integer|min:0',
            'limit'    => 'nullable|integer|min:1|max:2000',
        ]);

        $batchId  = basename($request->batch_id);
        $offset   = (int) $request->offset;
        $limit    = (int) ($request->limit ?? 500);
        $basePath = storage_path('app/vendor_imports/' . $batchId);

        if (!is_dir($basePath)) {
            return response()->json(['status' => false, 'message' => 'Invalid batch.'], 404);
        }

        try {
            // Load Excel rows
            $allRows = json_decode(file_get_contents($basePath . '/rows.json'), true);
            $totalRows = count($allRows);
            $chunk = array_slice($allRows, $offset, $limit);
            unset($allRows);

            if (empty($chunk)) {
                return response()->json([
                    'status'    => true,
                    'processed' => $totalRows,
                    'total'     => $totalRows,
                    'done'      => true,
                ]);
            }

            // Load party index
            $index = json_decode(file_get_contents($basePath . '/party_index.json'), true);
            $panIndex  = $index['panIndex'];
            $nameIndex = $index['nameIndex'];
            $partyMap  = $index['partyMap'];
            unset($index);

            // Load existing results
            $results = json_decode(file_get_contents($basePath . '/results.json'), true);
            $matched   = $results['matched'];
            $unmatched = $results['unmatched'];

            // Process chunk
            foreach ($chunk as $row) {
                $uploadPan  = strtoupper(trim($row['pan']));
                $uploadName = $this->normalizeVendorName($row['vendor_name'] ?? '');

                $matchedPartyId = null;
                $matchType = 'unmatched';

                // Priority 1: PAN exact match
                if ($uploadPan !== '' && isset($panIndex[$uploadPan])) {
                    foreach ($panIndex[$uploadPan] as $pid) {
                        $existingName = (string) ($partyMap[$pid]['normalized_name'] ?? '');
                        if ($existingName === $uploadName) {
                            $matchedPartyId = $pid;
                            $matchType = 'exact';
                            break;
                        }
                    }
                    if (!$matchedPartyId) {
                        $matchedPartyId = $panIndex[$uploadPan][0];
                        $matchType = 'pan_only';
                    }
                }

                // Priority 2: Name exact match
                if (!$matchedPartyId && $uploadName !== '' && isset($nameIndex[$uploadName])) {
                    $matchedPartyId = $nameIndex[$uploadName][0];
                    $matchType = 'name_only';
                }

                // Priority 3: Fuzzy name match (stricter than similar_text to avoid false positives)
                if (!$matchedPartyId && $uploadName !== '') {
                    foreach ($partyMap as $pid => $pdata) {
                        $existingName = (string) ($pdata['normalized_name'] ?? '');
                        if ($existingName === '') {
                            continue;
                        }

                        if ($this->isLikelyFuzzyNameMatch($uploadName, $existingName)) {
                            $matchedPartyId = $pid;
                            $matchType = 'fuzzy_name';
                            break;
                        }
                    }
                }

                if ($matchedPartyId) {
                    $matched[] = [
                        'excel_row'     => $row,
                        'party_id'      => $matchedPartyId,
                        'party_name'    => $partyMap[$matchedPartyId]['name'],
                        'party_pan'     => $partyMap[$matchedPartyId]['pan'],
                        'match_type'    => $matchType,
                        'contracts'     => $partyMap[$matchedPartyId]['contracts_count'],
                        'locations'     => $partyMap[$matchedPartyId]['locations'] ?? [],
                        'location_text' => $partyMap[$matchedPartyId]['location_text'] ?? '',
                        'already_valid' => $partyMap[$matchedPartyId]['valid'],
                    ];
                } else {
                    $unmatched[] = [
                        'excel_row' => $row,
                    ];
                }
            }

            // Save updated results
            file_put_contents($basePath . '/results.json', json_encode([
                'matched'   => $matched,
                'unmatched' => $unmatched,
            ]));

            $processed = min($offset + $limit, $totalRows);

            return response()->json([
                'status'    => true,
                'processed' => $processed,
                'total'     => $totalRows,
                'done'      => $processed >= $totalRows,
                'matched_count'   => count($matched),
                'unmatched_count' => count($unmatched),
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Chunk processing error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Validate selected matched vendor rows — sets valid=1, updates vendor_code & active_vendor_code.
     */
    public function vendor_import_validate(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.party_id'            => 'required|integer|exists:contract_parties,id',
            'items.*.vendor_code'         => 'nullable|string|max:100',
            'items.*.active_vendor_code'  => 'nullable|string|max:100',
            'items.*.pan'                 => 'nullable|string|max:100',
        ]);

        $partyIds = collect($request->items)
            ->pluck('party_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter(function ($id) {
                return $id > 0;
            })
            ->unique()
            ->values()
            ->all();

        $partiesById = ContractParties::withoutGlobalScopes()
            ->whereIn('id', $partyIds)
            ->get()
            ->keyBy('id');

        $successCount = 0;
        foreach ($request->items as $item) {
            $party = $partiesById->get((int) ($item['party_id'] ?? 0));
            if ($party) {
                $vendorCode = trim((string) ($item['vendor_code'] ?? ''));
                $activeVendorCode = trim((string) ($item['active_vendor_code'] ?? ''));
                $excelPan = strtoupper(trim((string) ($item['pan'] ?? '')));

                $party->valid = 1;

                if ($vendorCode !== '') {
                    $party->vendor_code = $vendorCode;
                }

                if ($activeVendorCode !== '') {
                    $party->active_vendor_code = $activeVendorCode;
                }

                if ($excelPan !== '') {
                    $party->pan = encryptString($excelPan, 'pan');
                }

                $party->save();
                $successCount++;
            }
        }

        return response()->json(['status' => true, 'message' => $successCount . ' vendor(s) validated successfully.']);
    }

    /**
     * Export unmatched vendor rows as an Excel file in the org template format.
     */
    public function vendor_import_export_unmatched(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|string|max:100',
        ]);

        $batchId = basename($request->batch_id);
        $basePath = storage_path('app/vendor_imports/' . $batchId);
        $resultsFile = $basePath . '/results.json';

        if (!file_exists($resultsFile)) {
            return redirect()->back()->with('error', 'No import results found.');
        }

        $results = json_decode(file_get_contents($resultsFile), true);
        $unmatched = $results['unmatched'] ?? [];

        if (empty($unmatched)) {
            return redirect()->back()->with('error', 'No unmatched vendors to export.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Organization');

        // Header row matching contract_parties_template_download_org format
        $headers = [
            'A1' => 'Party Type',
            'B1' => 'Legal Entity',
            'C1' => 'Company Name',
            'D1' => 'Company Email ID',
            'E1' => 'GST',
            'F1' => 'PAN',
            'G1' => 'Company Contact Number',
            'H1' => 'Building No',
            'I1' => 'Area Name',
            'J1' => 'Landmark',
            'K1' => 'City',
            'L1' => 'PinCode',
            'M1' => 'Country',
            'N1' => 'State',
            'O1' => 'Website',
            'P1' => 'Representative Name',
            'Q1' => 'Representative Email ID',
            'R1' => 'Representative Designation',
            'S1' => 'Representative Contact Number',
            'T1' => 'Representative Nationality',
            'U1' => 'Access Level',
            'V1' => 'Engagement Branch',
            'W1' => 'Role In Contract',
            'X1' => 'Is Related Party',
            'Y1' => 'Vendor Code',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $rowNum = 2;
        foreach ($unmatched as $item) {
            $row = $item['excel_row'];
            $sheet->setCellValue('A' . $rowNum, 'Vendor');
            $sheet->setCellValue('C' . $rowNum, $row['vendor_name']);
            $sheet->setCellValue('F' . $rowNum, $row['pan']);
            $sheet->setCellValue('Y' . $rowNum, $row['vendor_code']);
            $rowNum++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'unmatched_vendors_' . date('Y_m_d_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function normalizeVendorName($value): string
    {
        $name = strtolower(trim((string) $value));
        if ($name === '') {
            return '';
        }

        $name = preg_replace('/[^a-z0-9\s]+/i', ' ', $name);
        $name = preg_replace('/\s+/', ' ', (string) $name);

        return trim((string) $name);
    }

    private function compactVendorName($value): string
    {
        return preg_replace('/[^a-z0-9]+/i', '', $this->normalizeVendorName($value));
    }

    private function isLikelyFuzzyNameMatch(string $uploadName, string $existingName): bool
    {
        if ($uploadName === '' || $existingName === '') {
            return false;
        }

        if ($uploadName === $existingName) {
            return true;
        }

        $compactUpload = $this->compactVendorName($uploadName);
        $compactExisting = $this->compactVendorName($existingName);
        if ($compactUpload === '' || $compactExisting === '') {
            return false;
        }

        $maxLen = max(strlen($compactUpload), strlen($compactExisting));
        $distance = levenshtein($compactUpload, $compactExisting);
        $compactSimilarity = $maxLen > 0 ? (1 - ($distance / $maxLen)) : 0;

        if ($compactSimilarity >= 0.88) {
            return true;
        }

        $genericTokens = [
            'enterprise', 'enterprises', 'trading', 'company', 'co', 'corp', 'corporation',
            'pvt', 'private', 'ltd', 'limited', 'llp', 'industries', 'industry', 'solutions',
            'services', 'service', 'global', 'international', 'group', 'india'
        ];

        $uploadTokens = array_values(array_filter(explode(' ', $uploadName), function ($token) use ($genericTokens) {
            return $token !== '' && !in_array($token, $genericTokens, true);
        }));
        $existingTokens = array_values(array_filter(explode(' ', $existingName), function ($token) use ($genericTokens) {
            return $token !== '' && !in_array($token, $genericTokens, true);
        }));

        if (empty($uploadTokens) || empty($existingTokens)) {
            return false;
        }

        $bestTokenSimilarity = 0;
        foreach ($uploadTokens as $uToken) {
            foreach ($existingTokens as $eToken) {
                $tokenMax = max(strlen($uToken), strlen($eToken));
                if ($tokenMax < 4) {
                    continue;
                }
                $tokenDistance = levenshtein($uToken, $eToken);
                $tokenSimilarity = 1 - ($tokenDistance / $tokenMax);
                if ($tokenSimilarity > $bestTokenSimilarity) {
                    $bestTokenSimilarity = $tokenSimilarity;
                }
            }
        }

        if ($bestTokenSimilarity >= 0.84 && $compactSimilarity >= 0.72) {
            return true;
        }

        return false;
    }

    /**
     * Show the upload form for updating contract parties via Excel
     */
    public function parties_update_import_view()
    {
        return view('parties::parties_update_import.index');
    }

    /**
     * Parse uploaded Excel file and show column mapping page
     */
    public function parties_update_import_upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240'
            ]);

            $file = $request->file('file');
            $batchId = 'update_import_' . time() . '_' . uniqid();

            // Store file temporarily
            $tempPath = storage_path('app/temp/' . $batchId . '.xlsx');
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            $file->move(storage_path('app/temp'), $batchId . '.xlsx');

            // Parse Excel
            $spreadsheet = IOFactory::load($tempPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = [];
            foreach ($worksheet->getRowIterator() as $row) {
                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $rows[] = $rowData;
            }

            if (count($rows) < 2) {
                return redirect()->back()->with('error', 'Excel file must have at least a header row and one data row.');
            }

            // First row is headers
            $columns = array_map(function($val) {
                return $val ?? 'Column';
            }, $rows[0]);

            // Sample data (next 5 rows)
            $sampleData = array_slice($rows, 1, 5);
            $totalRows = count($rows) - 1;

            // Store in session
            session([
                'update_import_batch_id' => $batchId,
                'update_import_file_path' => $tempPath,
                'update_import_columns' => $columns,
                'update_import_sample_data' => $sampleData,
                'update_import_total_rows' => $totalRows,
                'update_import_all_data' => array_slice($rows, 1)
            ]);

            // Available fields for mapping
            $availableFields = [
                'vendor_code' => 'Vendor Code',
                'active_vendor_code' => 'Active Vendor Code',
                'party_type' => 'Party Type',
                'entity_scope' => 'Customer Type (Domestic/International)',
                'entity_type' => 'Entity Type',
                'payment_type' => 'Payer Type',
                'company_contact' => 'Company Contact',
                'company_email' => 'Company Email',
                'building_no' => 'Building No',
                'area_name' => 'Area Name',
                'landmark' => 'Landmark',
                'city' => 'City',
                'state' => 'State',
                'pincode' => 'Pincode',
                'country' => 'Country',
                'website' => 'Website',
                'gst' => 'GST',
                'pan' => 'PAN',
                'legal_entity' => 'Legal Entity',
                'organization_type' => 'Organization Type',
                'role_in_contract' => 'Role In Contract',
                'corporate_registration_number' => 'Corporate Registration Number',
                'is_related_party' => 'Is Related Party'
            ];

            return view('parties::parties_update_import.mapping', compact(
                'batchId', 'columns', 'sampleData', 'totalRows', 'availableFields'
            ));

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }

    /**
     * Show confirmation page with preview of changes
     */
    public function parties_update_import_preview(Request $request)
    {
        try {
            $batchId = session('update_import_batch_id');
            $allData = session('update_import_all_data');
            $columns = session('update_import_columns');

            if (!$batchId || !$allData) {
                return redirect()->route('parties.parties_update_import_view')
                    ->with('error', 'Session expired. Please upload the file again.');
            }

            $mapping = $request->input('mapping', []);

            // Filter out skipped columns (empty values) and company_name (never update party name)
            $activeMapping = array_filter($mapping, function($val) {
                return !empty($val) && $val !== 'company_name';
            });

            if (empty($activeMapping)) {
                return redirect()->back()->with('error', 'Please map at least one column to a party field.');
            }

            // Store mapping in session
            session(['update_import_mapping' => $activeMapping]);

            // Field labels for display
            $fieldLabels = [
                'vendor_code' => 'Vendor Code',
                'active_vendor_code' => 'Active Vendor Code',
                'party_type' => 'Party Type',
                'entity_scope' => 'Customer Type',
                'entity_type' => 'Entity Type',
                'payment_type' => 'Payer Type',
                'company_contact' => 'Company Contact',
                'company_email' => 'Company Email',
                'building_no' => 'Building No',
                'area_name' => 'Area Name',
                'landmark' => 'Landmark',
                'city' => 'City',
                'state' => 'State',
                'pincode' => 'Pincode',
                'country' => 'Country',
                'website' => 'Website',
                'gst' => 'GST',
                'pan' => 'PAN',
                'legal_entity' => 'Legal Entity',
                'organization_type' => 'Organization Type',
                'role_in_contract' => 'Role In Contract',
                'corporate_registration_number' => 'Corporate Registration Number',
                'is_related_party' => 'Is Related Party'
            ];

            // Encrypted fields
            $encryptedFields = ['gst', 'pan', 'corporate_registration_number'];

            $matchedParties = [];
            $unmatchedParties = [];

            foreach ($allData as $rowIndex => $row) {
                $partyName = $row[0] ?? null;

                if (empty($partyName)) {
                    continue;
                }

                // If party name contains ':', extract the first part as the actual party name
                if (strpos($partyName, ':') !== false) {
                    $partyName = trim(explode(':', $partyName)[0]);
                }

                // Find party by name (decrypt and compare)
                $party = ContractParties::where('party_sub_type', '<>', 'individual')
                    ->get()
                    ->first(function($p) use ($partyName) {
                        $decryptedName = decryptString($p->company_name, 'company_name');
                        return strtolower(trim($decryptedName)) === strtolower(trim($partyName));
                    });

                if (!$party) {
                    $unmatchedParties[] = [
                        'row_number' => $rowIndex + 2,
                        'party_name' => $partyName
                    ];
                    continue;
                }

                // Build changes preview
                $changes = [];
                foreach ($activeMapping as $colIndex => $fieldName) {
                    $newValue = $row[$colIndex] ?? null;

                    if ($newValue === null || $newValue === '') {
                        continue;
                    }

                    // Get old value
                    $oldValue = $party->$fieldName;

                    // Decrypt if needed for display
                    if (in_array($fieldName, $encryptedFields) && $oldValue) {
                        $oldValue = decryptString($oldValue, $fieldName);
                    }

                    $changes[] = [
                        'field' => $fieldName,
                        'field_label' => $fieldLabels[$fieldName] ?? $fieldName,
                        'old_value' => $oldValue,
                        'new_value' => $newValue
                    ];
                }

                if (!empty($changes)) {
                    $matchedParties[] = [
                        'party_id' => $party->id,
                        'party_name' => decryptString($party->company_name, 'company_name'),
                        'changes' => $changes
                    ];
                }
            }

            // Store for execution
            session([
                'update_import_matched' => $matchedParties,
                'update_import_unmatched' => $unmatchedParties
            ]);

            $totalRows = session('update_import_total_rows');

            return view('parties::parties_update_import.confirm', compact(
                'batchId', 'matchedParties', 'unmatchedParties', 'totalRows'
            ));

        } catch (Exception $e) {
            return redirect()->route('parties.parties_update_import_view')
                ->with('error', 'Error processing preview: ' . $e->getMessage());
        }
    }

    /**
     * Execute the actual updates
     */
    public function parties_update_import_execute(Request $request)
    {
        try {
            $matchedParties = session('update_import_matched');
            $mapping = session('update_import_mapping');

            if (empty($matchedParties)) {
                return redirect()->route('parties.parties_update_import_view')
                    ->with('error', 'No parties to update.');
            }

            // Encrypted fields
            $encryptedFields = ['gst', 'pan', 'corporate_registration_number'];

            $updatedCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($matchedParties as $partyData) {
                $party = ContractParties::find($partyData['party_id']);

                if (!$party) {
                    $errors[] = "Party ID {$partyData['party_id']} not found.";
                    continue;
                }

                $updateData = [];

                foreach ($partyData['changes'] as $change) {
                    $fieldName = $change['field'];
                    $newValue = $change['new_value'];

                    // Never update company_name
                    if ($fieldName === 'company_name') {
                        continue;
                    }

                    // Encrypt if needed
                    if (in_array($fieldName, $encryptedFields)) {
                        $newValue = encryptString($newValue, $fieldName);
                    }

                    $updateData[$fieldName] = $newValue;
                }

                if (!empty($updateData)) {
                    $party->update($updateData);
                    $updatedCount++;
                }
            }

            DB::commit();

            // Clean up session and temp file
            $tempPath = session('update_import_file_path');
            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }
            session()->forget([
                'update_import_batch_id',
                'update_import_file_path',
                'update_import_columns',
                'update_import_sample_data',
                'update_import_total_rows',
                'update_import_all_data',
                'update_import_mapping',
                'update_import_matched',
                'update_import_unmatched'
            ]);

            $message = "Successfully updated {$updatedCount} parties.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }

            return redirect()->route('parties.parties')
                ->with('success', $message);

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('parties.parties_update_import_view')
                ->with('error', 'Error executing updates: ' . $e->getMessage());
        }
    }
}
