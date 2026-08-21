<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Contract\Http\Controllers\EmailTemplateController;

use App\Models\Contract;
use App\Models\ContractPartyData;
use App\Models\ContractDiscount;
use App\Models\ContractHealthCheck;
use App\Models\ContractLocation;
use App\Models\ContractType;
use App\Models\AddUsers;
use App\Models\ApprovalContracts;
use App\Models\ContractHistory;
use App\Models\ContractLocationHistory;
use App\Models\ContractDiscountHistory;
use App\Models\ContractHealthCheckHistory;
use App\Models\FinancialLimit;
use App\Models\ContractTemplates;
use App\Models\ContractPartyEntityType;
use App\Models\Branch;
use App\Models\BranchUser;
use App\Models\GeographicalHierarchy;
use App\Models\LocationMaster;
use App\Models\ConsultationMaster;
use App\Models\TestMaster;
use App\Models\ContractParties;
use App\Models\EntityBusiness;

use App\Helpers\Helpers; // uses Helpers::userInfo()

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpWord\IOFactory as PhpWordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use stdClass;

class ContractCustomController extends Controller
{
    
    public function __construct()
    {
        // Load data from database (ConsultationMaster, LocationMaster, TestMaster) or fallback to JSON
        $this->consultations = $this->loadConsultationsFromDB();
        $this->tests = $this->loadTestsFromDB();
        $this->locations = $this->loadLocationsFromDB();
    }

    /**
     * Load consultations from database
     */
    private function loadConsultationsFromDB()
    {
        try {
            $consultations = ConsultationMaster::select('id', 'name', 'price')
                ->where('status', 1)
                ->orderBy('name')
                ->get()
                ->toArray();
            return !empty($consultations) ? $consultations : $this->loadJsonData('consultations.json');
        } catch (\Exception $e) {
            return $this->loadJsonData('consultations.json');
        }
    }

    /**
     * Load tests from database
     */
    private function loadTestsFromDB()
    {
        try {
            $tests = TestMaster::select('id', 'name', 'price')
                ->where('status', 1)
                ->orderBy('name')
                ->get()
                ->toArray();
            return !empty($tests) ? $tests : $this->loadJsonData('tests.json');
        } catch (\Exception $e) {
            return $this->loadJsonData('tests.json');
        }
    }

    /**
     * Load locations from database
     */
    private function loadLocationsFromDB()
    {
        try {
            $locations = LocationMaster::select('id', 'location_name as name', 'region')
                ->where('status', 1)
                ->orderBy('location_name')
                ->get()
                ->toArray();
            return !empty($locations) ? $locations : $this->loadJsonData('locations.json');
        } catch (\Exception $e) {
            return $this->loadJsonData('locations.json');
        }
    }

    /**
     * Load JSON data from storage (fallback)
     */
    private function loadJsonData($filename)
    {
        $path = storage_path('app/data/' . $filename);
        if (! file_exists($path)) {
            return [];
        }
        return json_decode(file_get_contents($path), true);
    }
    
    public function createCustom()
    {
        return view('contract::contract-custom.create');
    }
    /**
     * Store a new contract with all related data
     */
    public function store(Request $request)
    {
        $incoming = $request->has('payload') ? json_decode($request->input('payload'), true) : $request->all();
        
        if ($request->has('payload')) {
            $request->merge($incoming);
        }
        
        $validator = Validator::make($incoming, [
            'renew' => 'required|boolean',
            'new_contract' => 'required|array',
            'new_contract.agreement_name' => 'required|string|max:255',
            'new_contract.customer_id' => 'required|integer',
            'new_contract.scope' => 'nullable|string',
            'new_contract.entity_type_id' => 'nullable|integer',
            'new_contract.scope_of_services' => 'nullable|array',
            'new_contract.discounts' => 'nullable|array',
            'new_contract.health_check_rows' => 'nullable|array',
            'new_contract.locations' => 'nullable|array',
            'new_contract.start_date' => 'required|date',
            'new_contract.end_date' => 'required|date|after_or_equal:new_contract.start_date',
            'new_contract.editor_text' => 'nullable|string',
            'new_contract.employees_dependants' => 'nullable|string|in:employees,dependants,both'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        

        DB::beginTransaction();
        
        //try {
            $isRenewal = $request->input('renew', false);
            $newContractData = $request->input('new_contract');
            $oldContractData = $request->input('old_contract') ?? false;
            $oldContractId = $request->input('old_contract_id') ?? 0;
            
            $fileStorageController =  fileStorageTypeController();
            
            $contractsCreated = [];
            
            //If renewal, update old contract status
            
            if ($isRenewal) {
                if($oldContractId == 0){

                    $oldContract = $this->saveContractHelper($oldContractData, false , null);
                    
                    $oldContractId = $oldContract->id;

                    if ($request->hasFile('old_legacy_file')) {
                        
                        $file = $request->file('old_legacy_file');
                        
                        // Store file
                        $filename = file_name($file);
                        
                        $filePath = $fileStorageController->storeFile($file, '', $oldContractId, $filename);
                        
                        $cur_date = date('Y-m-d');
                        
                        $end_date_of_contract = $oldContractData['end_date'];
                        
                        $mainStatus = "executed";
                        $subStatusApprvr = "active";

                        if (strtotime($cur_date) > strtotime($end_date_of_contract)) {
                            $subStatusApprvr = 'expired';
                        }                        
                        
                        Contract::where('id', $oldContractId)->update([
                            'contract_attachment' => $filePath, 
                            'contract_attachment_filename' => $filename,
                            'contract_status' => $mainStatus,
                            'substatus' => $subStatusApprvr,                            
                            'mm_code' => '-',                           
                            'oracle_code' => '-'                            
                            ]);
                    }
                }
                $contractsCreated[] = ['type' => 'old', 'id' => $oldContractId];
            }
            
            //Create Old/New Contracts
            
            $newContract = $this->saveContractHelper($newContractData, $isRenewal , $oldContractId);
            
            $contract = Contract::findOrFail($newContract->id);

            // Store additional confidentiality agreement fields on create
            $confArr = [];
            if (!empty($contract->confidentialityagreement)) {
                $confArr = @json_decode($contract->confidentialityagreement, true) ?: [];
            }
            // Ensure default keys exist so we always have a consistent structure
            $defaults = [
                'prevailing_hospital_tariff' => false,
                'communication_protocol' => '',
                'employees_dependants' => null,
                'employees' => '',
                'dependants' => '',
                'sponsors' => []
            ];
            foreach ($defaults as $k => $v) {
                if (!array_key_exists($k, $confArr)) $confArr[$k] = $v;
            }
            // Override defaults with incoming values when provided
            if (array_key_exists('prevailing_hospital_tariff', $newContractData)) {
                $confArr['prevailing_hospital_tariff'] = (bool)$newContractData['prevailing_hospital_tariff'];
                
                if($confArr['prevailing_hospital_tariff']){
                    // Prevailing hospital tariff upload during contract creation
                    if ($request->hasFile('prevailing_file')) {
                        $pfValidator = Validator::make($request->all(), ['prevailing_file' => 'file|mimes:doc,docx|max:20480']);
                        if ($pfValidator->fails()) {
                            DB::rollBack();
                            return response()->json(['success' => false, 'errors' => $pfValidator->errors()], 422);
                        }
                        $uploadedPrev = $request->file('prevailing_file');
                        $prevFilename = file_name($uploadedPrev);
                        $prevPath = $fileStorageController->storeFile($uploadedPrev, '', $contract->id, $prevFilename);
                        $confArr['prevailing_hospital_tariff'] = true;
                        $confArr['prevailing_file'] = $prevPath;
                        $confArr['prevailing_file_name'] = $prevFilename;
                        Contract::where('id', $contract->id)->update(['confidentialityagreement' => json_encode($confArr)]);
                    }                    
                }
            }
            if (array_key_exists('communication_protocol', $newContractData)) {
                $confArr['communication_protocol'] = $newContractData['communication_protocol'];
            }
            if (array_key_exists('employees_dependants', $newContractData)) {
                $confArr['employees_dependants'] = $newContractData['employees_dependants'];
            }
            if (array_key_exists('employees', $newContractData)) {
                $confArr['employees'] = $newContractData['employees'];
            }
            if (array_key_exists('dependants', $newContractData)) {
                $confArr['dependants'] = $newContractData['dependants'];
            }
            if (array_key_exists('sponsors', $newContractData)) {
                $confArr['sponsors'] = is_array($newContractData['sponsors']) ? $newContractData['sponsors'] : [];
            }
            if (!empty($confArr)) {
                Contract::where('id', $contract->id)->update(['confidentialityagreement' => json_encode($confArr)]);
            }
            
            $isDraftSave = $request->input('save_as_draft', false) || (isset($incoming['save_as_draft']) && $incoming['save_as_draft']);

            // Disallow saving as draft when a custom template file is attached
            if ($isDraftSave && $request->hasFile('docxFile')) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Cannot save as draft while uploading a custom template. Uncheck the upload option or remove the file to save as draft.'], 422);
            }

            if(!$isDraftSave){
                // If client uploaded a custom .docx template, validate & store it and skip auto-generation
                if ($request->hasFile('docxFile')) {
                    $fileValidator = Validator::make($request->all(), ['docxFile' => 'file|mimes:doc,docx|max:20480']);
                    if ($fileValidator->fails()) {
                        DB::rollBack();
                        return response()->json(['success' => false, 'errors' => $fileValidator->errors()], 422);
                    }
                    $uploaded = $request->file('docxFile');
                    $filename = file_name($uploaded);
                    $filePath = $fileStorageController->storeFile($uploaded, '', $contract->id, $filename);
                    Contract::where('id', $contract->id)->update(['contract_attachment' => $filePath, 'contract_attachment_filename' => $filename]);
                } else {
                    $this->updateOrCreateTemplate('', $contract);
                }
            }
            
            $contract->refresh();
            
            $this->createApprovalFlow($contract, $isDraftSave, $request->hasFile('docxFile'));
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Contract created successfully',
                'data' => [
                    'contracts' => $contractsCreated,
                    'new_contract' => $newContract->load([
                        'contractPartyList',
                        'contractDiscounts',
                        'contractHealthChecks',
                        'contractLocations'
                    ])
                ]
            ], 201);
    }
    
    
    public function saveContractHelper($newContractData, $isRenewal, $oldContractId){
        
        
            $approval_user_column = "approval_required_users";
            $approvalTypeGlobal = "0";
            if ($isRenewal) {
                $approval_user_column = "approval_required_users_renewed";
                $approvalTypeGlobal = "renew";
            }  
            
            $contractParties = ContractParties::select('*')->where('id', $newContractData['customer_id'])->first();
            
            $newContractData['payment_type'] = $contractParties->payment_type ?? null;
            
            $finalApprovers = $this->evaluateDiscountApproval($newContractData, $approval_user_column, $approvalTypeGlobal);
            
            $newContractData['approvers'] = $finalApprovers;
    
            $newContract = $this->createContract($newContractData, $isRenewal ?  $oldContractId : null);
            
            // Store customer as contract party
            $this->createContractParty($newContract->id, $newContractData['customer_id']);
            
            // Store discounts
            if (! empty($newContractData['discounts'])) {
                $this->createDiscounts($newContract->id, $newContractData['discounts']);
            }
            
            // Store health check packages
            if (!empty($newContractData['health_check_rows'])) {
                $this->createHealthChecks($newContract->id, $newContractData['health_check_rows']);
            }
            
            // Store locations
            if (!empty($newContractData['locations'])) {
                $this->createLocations($newContract->id, $newContractData['locations']);
            }
            
            return $newContract;
    }
    
    /**
     * Create contract record
     */
    private function createContract(array $data, $parentContractId = null)
    {

        //Owner/Initiator Validation
        $owner_initiator = session()->get('contractSessionUser');

        $initiatior_exists = AddUsers::select('id',  decrypt_data('AccessScope', 'AddUsers'))
            ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
            ->first();
        if (!$initiatior_exists) {
            $invalid_owner_error = array('Owner Not Available Please Contact Administrator');
            return redirect('contracts/list/contract-custom/')->withErrors(array_merge($fileError, $invalid_owner_error))->withInput();
        }
        
        $owner_initiator_id = $initiatior_exists->id;
        
        $getDepartment = ContractType::find(admin_setting('custom_contracts_type_id'));
        
        $contractData = [
            'contract_name' => encryptString($data['agreement_name'] ,'contract_name'),
            'fixed_date' => $data['start_date'],
            'contract_end_date' => $data['end_date'],
            'contract_description' => $data['contract_notes'],
            'contract_type' => admin_setting('custom_contracts_type_id'),
            'department_id' => $getDepartment->departmentId,
            'catgoery_id' => $data['entity_type_id'], 
            'rules_id' => json_encode($data['approvers']),
            'contract_status' => 'Draft',
            'substatus' => 'Initial Draft',
            'status' => '1',
            'signatory' => 0,
            'owner' => $owner_initiator_id,            
            'created_by' => $owner_initiator_id,
        ];
        
        // If renewal, set parent contract
        if ($parentContractId) {
            $contractData['parentcontract'] = $parentContractId;
            $contractData['renewal_type'] = 'manual';
        }
        
        // Add additional fields if present
        if (isset($data['scope'])) {
            $contractData['custom_fields_data'] = $data['scope'];
        }
        
        if (isset($data['scope_of_services'])) {
            $contractData['contract_tags'] = json_encode($data['scope_of_services']);
        }
        
        $newContract = Contract::create($contractData);

        // Generate contract_unique_id using admin prefix + 8-digit zero-padded numeric suffix
        try {
            $prefix = admin_setting('contract_prefix_id') ?? 'CLM';
            // Use a small transaction and row locking to safely compute next sequence for this prefix
            \DB::transaction(function () use ($prefix, $newContract) {
                // Find current maximum contract_unique_id for this prefix (exclude the newly created row)
                $maxUnique = \DB::table('contracts')
                    ->where('contract_unique_id', 'like', $prefix . '%')
                    ->where('id', '<>', $newContract->id)
                    ->lockForUpdate()
                    ->orderBy('contract_unique_id', 'desc')
                    ->value('contract_unique_id');

                $next = 1;
                if ($maxUnique) {
                    $numeric = intval(preg_replace('/[^0-9]/', '', $maxUnique));
                    $next = $numeric + 1;
                }

                $unique_code = $prefix . str_pad($next, 8, '0', STR_PAD_LEFT);

                // Safety loop in case of race / collision
                while (\DB::table('contracts')->where('contract_unique_id', $unique_code)->exists()) {
                    $next++;
                    $unique_code = $prefix . str_pad($next, 8, '0', STR_PAD_LEFT);
                }

                // Persist the generated unique code
                Contract::where('id', $newContract->id)->update(['contract_unique_id' => $unique_code]);
            }, 5);
        } catch (\Throwable $e) {
            \Log::error('Failed to generate contract_unique_id for contract ' . ($newContract->id ?? '') . ': ' . $e->getMessage());
        }

        return $newContract;
    }
    
    /**
     * Create contract party data
     */
    private function createContractParty($contractId, $customerId)
    {
        
        $partyArray = [
            [
                'custom_field_group_id'      => $contractId,
                'contract_party_type'        => 'Internal',
                'contract_party_location_id'=> 1,
                'contract_party_id'          => 1,
                'party_sub_type' => 'Internal',
                'contract_party_exe_id' => null,
            ],
            [
                'custom_field_group_id' => $contractId,
                'contract_party_type'   => 'External',
                'contract_party_location_id'=> null,
                'contract_party_id'          => null,                
                'party_sub_type'        => 'Organization',
                'contract_party_exe_id' => $customerId,
            ],
        ];
        
        return ContractPartyData::insert($partyArray);

    }
    
    /**
     * Create discount records
     */
    private function createDiscounts($contractId, array $discounts)
    {
        $discountRecords = [];
        
        foreach ($discounts as $discount) {
            $discountRecords[] = [
                'contract_id' => $contractId,
                'category' => $discount['category'],
                'subcategory' => $discount['subcategory'],
                'discount_percent' => $discount['discount_percent'] ?? 0,
                'room_charges' => json_encode($discount['room_charges'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        ContractDiscount::insert($discountRecords);
    }
    
    /**
     * Create health check records
     */
    private function createHealthChecks($contractId, array $healthChecks)
    {
        $healthCheckRecords = [];
        
        
        foreach ($healthChecks as $healthCheck) {
            if(!empty($healthCheck['row_name'])){
                
                if(isset($healthCheck['selected_others']) && count($healthCheck['selected_others']) > 0){
                    $otherId = $healthCheck['selected_others'][0]['description'];
                    $healthCheck['selected_consultation_ids'][] = $otherId;
                    $healthCheck['prices'][$otherId] = $healthCheck['selected_others'][0]['price'];
                }

                $prices = $healthCheck['prices'] ?? [];
                // include override allocation if present
                if (isset($healthCheck['override_allocation'])) {
                    $prices['override_allocation'] = $healthCheck['override_allocation'];
                }

                $healthCheckRecords[] = [
                    'contract_id' => $contractId,
                    'row_name' => $healthCheck['row_name'],
                    'selected_test_ids' => json_encode($healthCheck['selected_test_ids'] ?? []),
                    'package_price' => $healthCheck['package_price'] ?? 0,
                    'selected_consultation_ids' => json_encode($healthCheck['selected_consultation_ids'] ?? []),
                    'consultation_prices' => json_encode($prices),
                    'overhead_allocation' => isset($healthCheck['overhead_allocation']) ? $healthCheck['overhead_allocation'] : 0,
                    'approved_cost' => isset($healthCheck['approved_cost']) ? $healthCheck['approved_cost'] : null,                    
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        

        if(count($healthCheckRecords) >0){
            ContractHealthCheck::insert($healthCheckRecords);
        }
    }
    
    /**
     * Create location records
     */
    private function createLocations($contractId, array $locations)
    {
        $locationRecords = [];
        
        foreach ($locations as $locationId) {
            $locationRecords[] = [
                'contract_id' => $contractId,
                'location_id' => $locationId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        ContractLocation::insert($locationRecords);
    }
    

    /**
     * Agreement form submit - Update contract details (AJAX)
     * Accepts payload similar to store(): payload may be JSON or FormData with 'payload' key
     */
    public function agreementFormUpdate(Request $request, $id)
    {
        // Accept payload inside FormData as 'payload' or direct JSON body
        $incoming = $request->has('payload') ? json_decode($request->input('payload'), true) : $request->all();
        $data = $incoming['new_contract'] ?? $incoming;

        $validator = Validator::make($data, [
            'agreement_name' => 'required|string|max:255',
            'customer_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'scope_of_services' => 'nullable|array',
            'discounts' => 'nullable|array',
            'health_check_rows' => 'nullable|array',
            'health_check_rows.*.override_allocation' => 'nullable|numeric|min:0',
            'locations' => 'nullable|array',
            'editor_text' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'coc_ip' => 'nullable|numeric|min:0',
            'coc_op' => 'nullable|numeric|min:0',
            'employees_dependants' => 'nullable|string|in:employees,dependants,both'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        


        DB::beginTransaction();
        //try {
            $contract = Contract::findOrFail($id);

            // Prevent updates if contract is in a final/published state
            $finalStates = ['active','expired','completed','terminated'];
            if (in_array(strtolower($contract->contract_status ?? ''), $finalStates, true)) {
                return response()->json(['success' => false, 'message' => 'Contract cannot be modified in its current state ('.$contract->contract_status.').'], 403);
            }

            $updateData = [];
            if (isset($data['agreement_name'])) {
                $updateData['contract_name'] = function_exists('encryptString') ? encryptString($data['agreement_name'], 'contract_name') : $data['agreement_name'];
            }
            if (isset($data['start_date'])) $updateData['fixed_date'] = $data['start_date'];
            if (isset($data['end_date'])) $updateData['contract_end_date'] = $data['end_date'];
            if (isset($data['entity_type_id'])) $updateData['catgoery_id'] = $data['entity_type_id'];
            if (isset($data['scope'])) $updateData['custom_fields_data'] = $data['scope'];
            if (isset($data['scope_of_services'])) $updateData['contract_tags'] = json_encode($data['scope_of_services']);
            if (isset($data['contract_notes'])) $updateData['contract_description'] = $data['contract_notes'];

            // Update/insert customer party
            if (!empty($data['customer_id'])) {
                $external = ContractPartyData::where('custom_field_group_id', $contract->id)->where('contract_party_type', 'External')->first();
                if ($external) {
                    $external->update(['contract_party_exe_id' => $data['customer_id']]);
                } else {
                    ContractPartyData::insert([
                        'custom_field_group_id' => $contract->id,
                        'contract_party_type' => 'External',
                        'contract_party_location_id' => null,
                        'contract_party_id' => null,
                        'party_sub_type' => 'Organization',
                        'contract_party_exe_id' => $data['customer_id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Update discounts
            if (isset($data['discounts'])) {
                ContractDiscount::where('contract_id', $contract->id)->delete();
                $this->createDiscounts($contract->id, $data['discounts']);
            }

            // Update health checks
            if (isset($data['health_check_rows'])) {
                ContractHealthCheck::where('contract_id', $contract->id)->delete();
                $this->createHealthChecks($contract->id, $data['health_check_rows']);
            }

            // Update locations
            if (isset($data['locations'])) {
                ContractLocation::where('contract_id', $contract->id)->delete();
                $this->createLocations($contract->id, $data['locations']);
            }
            
            
            if (isset($data['credit_limit']) || isset($data['credit_days']) || isset($data['prevailing_hospital_tariff']) || isset($data['communication_protocol']) || isset($data['employees_dependants']) || isset($data['employees']) || isset($data['dependants']) || isset($data['sponsors']) || isset($data['coc_ip']) || isset($data['coc_op'])) {
                $existing = [];
                if (!empty($contract->confidentialityagreement)) {
                    $existing = @json_decode($contract->confidentialityagreement, true) ?: [];
                }
                // Ensure baseline keys exist
                $defaults = [
                    'prevailing_hospital_tariff' => false,
                    'communication_protocol' => '',
                    'employees_dependants' => null,
                    'employees' => '',
                    'dependants' => '',
                    'sponsors' => []
                ];
                foreach ($defaults as $k => $v) {
                    if (!array_key_exists($k, $existing)) $existing[$k] = $v;
                }

                if (isset($data['credit_limit'])) $existing['credit_limit'] = $data['credit_limit'];
                if (isset($data['credit_days'])) $existing['credit_days'] = $data['credit_days'];

                // COC split for IP / OP (applicable only for international scope)
                if (isset($data['coc_ip'])) $existing['coc_ip'] = $data['coc_ip'];
                if (isset($data['coc_op'])) $existing['coc_op'] = $data['coc_op'];

                if (isset($data['bank_guarantee'])) $existing['bank_guarantee'] = $data['bank_guarantee'];

                // Prevailing hospital tariff flag & file handled separately (file stored below)
                if (isset($data['prevailing_hospital_tariff'])) $existing['prevailing_hospital_tariff'] = (bool)$data['prevailing_hospital_tariff'];

                // Free-text protocol fields
                if (isset($data['communication_protocol'])) $existing['communication_protocol'] = $data['communication_protocol'];
                if (isset($data['employees_dependants'])) $existing['employees_dependants'] = $data['employees_dependants'];
                if (isset($data['employees'])) $existing['employees'] = $data['employees'];
                if (isset($data['dependants'])) $existing['dependants'] = $data['dependants'];

                // Sponsors array
                if (isset($data['sponsors'])) $existing['sponsors'] = is_array($data['sponsors']) ? $data['sponsors'] : [];

                // If a Prevailing Hospital Tariff Word doc was uploaded, validate & store it
                if ($request->hasFile('prevailing_file')) {
                    // Ensure the checkbox is checked when uploading a file
                    if (empty($data['prevailing_hospital_tariff'])) {
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => 'Prevailing Hospital Tariff must be checked to upload tariff file.'], 422);
                    }
                    $fileValidator = Validator::make($request->all(), ['prevailing_file' => 'file|mimes:doc,docx|max:20480']);
                    if ($fileValidator->fails()) {
                        DB::rollBack();
                        return response()->json(['success' => false, 'errors' => $fileValidator->errors()], 422);
                    }
                    $fileStorageController = fileStorageTypeController();
                    $uploaded = $request->file('prevailing_file');
                    $filename = file_name($uploaded);
                    $filePath = $fileStorageController->storeFile($uploaded, '', $contract->id, $filename);
                    $existing['prevailing_file'] = $filePath;
                    $existing['prevailing_file_name'] = $filename;
                }

                // Compare with existing confidentialityagreement and update only if changes are present
                $oldConf = [];
                if (!empty($contract->confidentialityagreement)) {
                    $oldConf = @json_decode($contract->confidentialityagreement, true) ?: [];
                }

                // If there is any difference between old and new, persist and snapshot
                if ($oldConf !== $existing) {
                    $contract->update(['confidentialityagreement' => json_encode($existing)]);
                    // snapshot: confidentialityagreement updated via agreement form
                }
                
                $this->createContractSnapshot($contract, 'data updated');
            }            

            DB::commit();

            $contract->refresh();

            // Re-evaluate approvers based on updated contract data
            // If approval flow no longer matches, add missing approvers to the workflow
            $approversChanged = false;
            $added = [];
            $removed = [];
            
            try {
                // Only re-evaluate if contract is not in Draft status (Draft only has owner approval)
                // Re-evaluation happens after owner approves and full workflow is generated
                if (strtolower($contract->contract_status ?? '') !== 'draft') {
                    $contractData = [
                        'discounts' => $data['discounts'] ?? [],
                        'health_check_rows' => $data['health_check_rows'] ?? [],
                        'locations' => $data['locations'] ?? [],
                        'scope' => $contract->custom_fields_data,
                        'contract_type' => $contract->contract_type,
                        'payment_type' => $data['payment_type'] ?? null,
                    ];
                    
                    $reEvalResult = $this->reEvaluateAndAddMissingApprovers($contract, $contractData);
                    
                    $approversChanged = $reEvalResult['changed'] ?? false;
                    $added = $reEvalResult['added'] ?? [];
                    $removed = $reEvalResult['removed'] ?? [];
                    
                    if ($approversChanged) {
                        // Flash flag for show blade and include diffs
                        session()->flash('approvers_changed', ['added' => $added, 'removed' => $removed]);
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('Failed to re-evaluate approvers after update: ' . $e->getMessage());
            }

            $isDraftSave = $request->input('save_as_draft', false) || (isset($incoming['save_as_draft']) && $incoming['save_as_draft']);
            
            if(!$isDraftSave){
                $this->updateOrCreateTemplate('', $contract);
                $successMsg = 'Contract '.(!empty($contract->contract_attachment) ? 'Updated' : 'Created');
                if ($approversChanged && !empty($added)) {
                    $successMsg .= '. New approvers added: ' . implode(', ', $added);
                }
                if ($approversChanged && !empty($removed)) {
                    $successMsg .= '. Approvers removed: ' . implode(', ', $removed);
                }
                session()->put('success', $successMsg);            
            }else{
                session()->put('success', 'Contract Draft Updated...');            
            }
            
            
            return response()->json([
                'success' => true, 
                'data' => $contract, 
                'approvers_changed' => $approversChanged, 
                'approver_diff' => ['added' => $added, 'removed' => $removed],
                'added_approvers' => $added,
                'removed_approvers' => $removed
            ], 200);

        // } catch (Exception $e) {
        //     DB::rollBack();
        //     return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        // }
    }
    
    
    /**
     * Delete contract
     */
    public function destroy($id)
    {
        try {
            $contract = Contract::findOrFail($id);
            $contract->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Contract deleted successfully'
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contract',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Display a listing of contracts
     */
    public function index(Request $request)
    {

            $perPage = $request->input('per_page', 15);
            $query = Contract::query();
            
            $query->where('contract_type', admin_setting('custom_contracts_type_id'));
            
            // Load relationships
            $query->with([
                'contractPartyList.partyDetailsEx',
                'contractPartyList.partyDetailsIn',
                'contractDiscounts',
                'contractHealthChecks',
                'contractLocations.location'
            ]);
            
            // Search filter
            if ($request->has('search') && ! empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('contract_name', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%")
                      ->orWhere('contract_unique_id', 'like', "%{$search}%");
                });
            }
            
            // Status filter
            if ($request->has('status') && !empty($request->status)) {
                $query->where('contract_status', $request->status);
            }
            
            // Date range filter
            if ($request->has('start_date') && ! empty($request->start_date)) {
                $query->where('signing_date', '>=', $request->start_date);
            }
            
            if ($request->has('end_date') && !empty($request->end_date)) {
                $query->where('contract_end_date', '<=', $request->end_date);
            }
            
            $contracts = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return view('contract::contract-custom.index', compact('contracts'));
    }

    // Dashboard moved to ContractDashboardController::dashDetails
    // Legacy per-controller dashboard removed to centralize dashboard logic

    
    /**
     * Display the specified contract
     */
    public function show($id)
    {

        // $contract = Contract::findOrFail($id);
        
        // $this->createApprovalFlow($contract);
        // $this->updateOrCreateTemplate('', $contract);
        // echo $this->getApprovalTemplate($id);
        // die;
        
        try {
            $contract = Contract::with([
                'contractPartyList.partyDetailsEx',
                'contractPartyList.partyDetailsIn',
                'contractPartyList.branchDetails',
                'contractDiscounts',
                'contractHealthChecks',
                'contractLocations.location',
                'contractParent',
                'contractTypeData',
                'contractClauseLink'
            ])->findOrFail($id);

            // Decrypt contract name for display if encrypted
            if (!empty($contract->contract_name) && function_exists('decryptString')) {
                try {
                    $contract->contract_name_decrypted = decryptString($contract->contract_name, 'contract_name');
                } catch (\Throwable $e) {
                    $contract->contract_name_decrypted = $contract->contract_name;
                }
            } else {
                $contract->contract_name_decrypted = $contract->contract_name;
            }

            // Overview summary calculation
            $overviewSummary = [
                'packages_count' => 0,
                'total_tests_amount' => 0.0,
                'total_consultation_amount' => 0.0,
                'net_total' => 0.0,
            ];

            if ($contract->contractHealthChecks && $contract->contractHealthChecks->count()) {
                foreach ($contract->contractHealthChecks as $hc) {
                    $overviewSummary['packages_count']++;
                    $testsCollection = method_exists($hc, 'tests') ? $hc->tests()->get() : collect();
                    $testSubtotal = 0.0;
                    foreach ($testsCollection as $t) $testSubtotal += floatval($t->price ?? 0);

                    $consultationSubtotal = 0.0;
                    $consultIds = is_string($hc->selected_consultation_ids) ? @json_decode($hc->selected_consultation_ids, true) : $hc->selected_consultation_ids;
                    $prices = is_string($hc->consultation_prices) ? @json_decode($hc->consultation_prices, true) : $hc->consultation_prices;
                    if (!empty($consultIds) && is_array($prices)) {
                        foreach ((array)$consultIds as $cid) {
                            if (isset($prices[$cid])) $consultationSubtotal += floatval($prices[$cid]);
                        }
                    }

                    $overviewSummary['total_tests_amount'] += $testSubtotal;
                    $overviewSummary['total_consultation_amount'] += $consultationSubtotal;
                    $overviewSummary['net_total'] += ($testSubtotal + $consultationSubtotal + floatval($hc->package_price ?? 0));
                }
            }

            // Credit cell inputs only shown for renewed contracts (parentcontract not null)
            $showCreditCellInputs = !empty($contract->parentcontract);

            // Parse existing credit cell data if exists
            $creditCellData = null;
            if (!empty($contract->confidentialityagreement)) {
                $creditCellData = json_decode($contract->confidentialityagreement, true);
            }
            
            $query = ContractPartyEntityType::query();
            $query->where('scope', $contract->custom_fields_data);
            $entityTypesList = $query->get(['id', 'name', 'scope']);            

            // Load approval entries
            $approvalEntries = ApprovalContracts::where('contract_id', $contract->id)
                ->orderBy('orderval', 'asc')
                ->get()
                ->map(function ($entry) {
                    $display = $entry->toArray();
                    // Decode username JSON payload if available (supports legacy plain string)
                    if (!empty($entry->username)) {
                        try {
                            $dec = function_exists('decryptString') ? @decryptString($entry->username, 'username') : $entry->username;
                        } catch (\Throwable $e) { $dec = $entry->username; }
                        $tmp = @json_decode($dec, true);
                        if (is_array($tmp)) {
                            $display['username_decrypted_email'] = $tmp['email'] ?? null;
                            $display['username_decrypted_name'] = $tmp['name'] ?? null;
                            $display['username_decrypted'] = $tmp['name'] ?? $tmp['email'] ?? $dec;
                        } else {
                            $display['username_decrypted'] = $dec;
                            $display['username_decrypted_email'] = null;
                            $display['username_decrypted_name'] = null;
                        }
                    } else {
                        $display['username_decrypted'] = $entry->username;
                        $display['username_decrypted_email'] = null;
                        $display['username_decrypted_name'] = null;
                    }

                    if (function_exists('decryptString') && !empty($entry->status)) {
                        try { $display['status_decrypted'] = decryptString($entry->status, 'username'); } catch (\Throwable $e) { $display['status_decrypted'] = $entry->status; }
                    } else {
                        $display['status_decrypted'] = $entry->status;
                    }

                    if (function_exists('decryptString') && !empty($entry->approval_status)) {
                        try { $display['approval_status_decrypted'] = decryptString($entry->approval_status, 'username'); } catch (\Throwable $e) { $display['approval_status_decrypted'] = $entry->approval_status; }
                    } else {
                        $display['approval_status_decrypted'] = $entry->approval_status;
                    }

                    if (function_exists('decryptString') && !empty($entry->next_status)) {
                        try { $display['next_status_decrypted'] = decryptString($entry->next_status, 'username'); } catch (\Throwable $e) { $display['next_status_decrypted'] = $entry->next_status; }
                    } else {
                        $display['next_status_decrypted'] = $entry->next_status;
                    }

                    return (object) array_merge($entry->toArray(), $display);
                });

            // Identify current user using Helpers::userInfo()
            $userInfo = Helpers::userInfo();
            $currentEntry = null;
            $approvalCycleCompleted = true;
            if ($userInfo && $approvalEntries->count()) {
                $currentIdentifier = strtolower($userInfo->email ?? $userInfo->FirstName ?? '');
                foreach ($approvalEntries as $entry) {
                    $entryIdent = strtolower($entry->username_decrypted_email ?? $entry->username ?? '');
                    if($entry->flag === 1){
                        $approvalCycleCompleted = false;
                    }
                    if (!$entryIdent) continue;
                    if (strpos($entryIdent, '@') !== false) {
                        if ($currentIdentifier === $entryIdent) { $currentEntry = $entry; break; }
                    } else {
                        if ($currentIdentifier === strtolower($entryIdent)) { $currentEntry = $entry; break; }
                    }
                }
            }

            // Determine approver level flags
            $isApproverLevel2Active = false;
            $isApproverLevel3Active = false;

            // Check signatory role via approvers_master email
            $isSignatory = false;
            if ($userInfo) {
                try {
                    $uemail = strtolower($userInfo->email ?? '');
                    if (!empty($uemail) && \Schema::hasTable('approvers_master')) {
                        $sig = \DB::table('approvers_master')->where('is_signatory',1)->whereRaw('LOWER(email)=?', [$uemail])->first();
                        if ($sig) $isSignatory = true;
                    }
                } catch (\Throwable $e) { $isSignatory = false; }
            }

            $consultations = $this->consultations;
            $tests = $this->tests;
            //$locations = $this->locations;
            
            
            $locations = LocationMaster::select('location_name as name','id')->get()->toArray();
            $locations = array_column($locations, 'name', 'id');
            
            
            // Related contracts: previous and subsequent based on parentcontract chain
            $contractsparentList = collect();
            $contractsSubseqList = collect();
            try {
                $getParentContracts = "SELECT parentcontract FROM
                (SELECT id,parentcontract,
                       CASE WHEN id in ('" . $id . "') THEN @idlist := CONCAT(IFNULL(@idlist,''),',',parentcontract)
                            WHEN FIND_IN_SET(id,@idlist) THEN @idlist := CONCAT(@idlist,',',parentcontract)
                            END as checkId
                FROM contracts
                ORDER BY id DESC)T
                WHERE checkId IS NOT NULL";

                $contractsparentListQuery = DB::select($getParentContracts);
                $parentContractArr = [];
                foreach ($contractsparentListQuery as $conpar) {
                    $parentContractArr[] = $conpar->parentcontract;
                }

                if (!empty($parentContractArr)) {
                    $contractsparentList = Contract::select('*')->whereIn('id', $parentContractArr)->get();
                }

                // Subsequent contracts (children of current contract and its parent chain)
                $childsList = '';
                $finalListChild = [];
                
                // Always include current contract ID to find its direct children
                $contractsToFindChildrenFor = [$id];
                
                // Also include parent contracts to find siblings/cousins
                foreach ($parentContractArr as $parCon) {
                    if ($parCon > 0) {
                        $contractsToFindChildrenFor[] = $parCon;
                    }
                }
                
                // Find all children recursively for each contract
                foreach ($contractsToFindChildrenFor as $contractIdToCheck) {
                    // Reset session variable for each iteration
                    DB::statement("SET @pv = ?", [$contractIdToCheck]);
                    
                    $getSubSequesntContracts = "SELECT GROUP_CONCAT(lv SEPARATOR ',') as childList FROM (
                                       SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',') FROM contracts 
                                       WHERE FIND_IN_SET(parentcontract, @pv)) AS lv FROM contracts 
                                       JOIN
                                       (SELECT @pv=" . intval($contractIdToCheck) . ") tmp
                                       ) a WHERE lv IS NOT NULL";

                    $contractsSubSeqList = DB::select($getSubSequesntContracts);

                    foreach ($contractsSubSeqList as $conSubSeq) {
                        if (!empty($conSubSeq->childList)) {
                            $childsList .= ($childsList ? ',' : '') . $conSubSeq->childList;
                        }
                    }
                }
                
                // Also do a simple direct query for immediate children of current contract
                $directChildren = Contract::where('parentcontract', $id)->where('status', 1)->pluck('id')->toArray();
                if (!empty($directChildren)) {
                    $childsList .= ($childsList ? ',' : '') . implode(',', $directChildren);
                }
                
                // Parse and deduplicate
                if (!empty($childsList)) {
                    $finalListChild = array_unique(array_filter(explode(',', $childsList), function($v) {
                        return $v !== '' && $v !== null && is_numeric($v);
                    }));
                }
                

                if (!empty($finalListChild)) {
                    $contractsSubseqList = Contract::whereIn('id', $finalListChild)->where('id', '<>', $id)->where('status', 1)->get();
                }

            } catch (\Throwable $e) {
                // ignore related contract fail; leave collections empty
                echo $e->getMessage();
                die;
            }            

            // Map controller flags to variables expected by the Blade view
            $isSecondApprover = $isApproverLevel2Active;
            $isApprover3 = $isApproverLevel3Active;
            $isCreditCell = $showCreditCellInputs;
            
            // Detect current approver type flags (approver or verifier)
            $currentApproverType = strtolower($currentEntry->approver_type_row ?? '');
            $isCurrentApproverIsApprover = ($currentApproverType === 'approver');
            $isCurrentApproverIsVerifier = ($currentApproverType === 'verifier');
            $isCreditUser = ($currentApproverType === 'preapprover');
            $isCurrentApproverIsApproverOrVerifier = in_array($currentApproverType, ['approver', 'verifier']);  
            
            // Determine active approver/verifier status (editable form only when approver/verifier and active flag==1)
            $isCurrentApproverActive = false;
            if ($currentEntry && $isCurrentApproverIsApproverOrVerifier) {
                $isCurrentApproverActive = ((int)$currentEntry->flag === 1);
            }

            // Determine owner: compare authenticated user's id to contract->created_by when possible
            $isOwner = false;

            if (!empty($contract->created_by)) {
                $owner = AddUsers::select('id',  decrypt_data('Email', 'AddUsers'))
                        ->where('id', $contract->created_by)
                        ->first();
                if ($owner) {
                    $ownerEmail = $owner->Email ?? null;
                    $userInfo = Helpers::userInfo();
                    $currentIdentifier = strtolower($userInfo->email);
                    if ($ownerEmail && strtolower($ownerEmail) === $currentIdentifier) {
                        $isOwner = true;
                    }
                }
            }
            
            
            
            //Is Credit Cell
            // $isCreditUser = false;
            // if (!empty($contract->owner)) {
            //     $owner = AddUsers::select('id',  decrypt_data('Email', 'AddUsers'))
            //             ->where('id', $contract->owner)
            //             ->first();
            //     if ($owner) {
            //         $ownerEmail = $owner->Email ?? null;
            //         $userInfo = Helpers::userInfo();
            //         $currentIdentifier = strtolower($userInfo->email);
            //         if ($ownerEmail && strtolower($ownerEmail) === $currentIdentifier) {
            //             $isCreditUser = true;
            //         }
            //     }
            // }
            
            $isSignatory = false;
            
            if (!empty($contract->signatory)) {
                $signatory = AddUsers::select('id',  decrypt_data('Email', 'AddUsers'))
                        ->where('id', $contract->signatory)
                        ->first();
                if ($signatory) {
                    $ownerEmail = $signatory->Email ?? null;
                    $userInfo = Helpers::userInfo();
                    $currentIdentifier = strtolower($userInfo->email);
                    if ($ownerEmail && strtolower($ownerEmail) === $currentIdentifier) {
                        $isSignatory = true;
                    }
                }
            }

            // Access rules for the form
            // - canViewForm: owners and any approver/verifier (even if inactive) may see the form
            // - canEditForm: only active approver/verifier may edit
            $canViewForm = ($isOwner || $isCurrentApproverIsApproverOrVerifier);
            $canEditForm = $isCurrentApproverActive && !$isOwner; // owner is always readonly
            
            if (in_array(strtolower($contract->contract_status), ['executed', 'signing'], true)) {
                $canViewForm = !$isOwner ? "" : $canViewForm;
                $isCurrentApproverIsApproverOrVerifier = false;
            }

            // // final readonly flag expected by the Blade partial (true => inputs read-only)
            $readonlyForm = !$canEditForm;            
            
            
            $readonlyForm = false;

            if ($currentEntry) {
                $ord = (int)$currentEntry->orderval;
                if ($ord === 2 && (int)$currentEntry->flag === 1) $isApproverLevel2Active = true;
                if ($ord === 3 && (int)$currentEntry->flag === 1) $isApproverLevel3Active = true;

                if ($ord === 1 && (int)$currentEntry->flag === 0) $readonlyForm = true;
                if ($ord === 2 && (int)$currentEntry->flag === 0) $readonlyForm = true;
                if ($ord === 3 && (int)$currentEntry->flag === 0) $readonlyForm = true;
            }            
            
            // ------------------------------------------------------------------
            // If contract is in Signing / Progress, look up the stored
            // eSign compose response, call getEasySignLinks to check status.
            // If all signed → download the signed PDF, update the contract
            // attachment, and move to Executed / Signed.
            // ------------------------------------------------------------------
            $easySignInfo = [];
            if (
                strtolower($contract->contract_status ?? '') === 'signing'
                && strtolower($contract->substatus ?? '') === 'progress'
            ) {
                try {
                    // Retrieve stored compose response
                    $esignRecord = \App\Models\EsignResposnse::where('contract_id', $contract->id)
                        ->where('status', 1)
                        ->latest()
                        ->first();

                    if ($esignRecord) {
                        $composeBody = json_decode($esignRecord->esignresponse, true);
                        $epakId      = $composeBody['data'] ?? null;
                        $metadata    = $composeBody['metadata'] ?? [];

                        if ($epakId) {
                            $esignCtrl = new \Modules\Contract\Http\Controllers\EsignApiController();

                            // Obtain a fresh token
                            $tokenRes  = $esignCtrl->getToken(new \Illuminate\Http\Request());
                            $tokenBody = json_decode($tokenRes->getContent(), true);
                            $msbToken  = $tokenBody['msb_token'] ?? ($tokenBody['access_token'] ?? null);
                            $tenantId  = 'APOLLO_HOSPITALS';

                            if ($msbToken) {
                                // Build a request with the required headers
                                $linksRequest = new \Illuminate\Http\Request();
                                $linksRequest->headers->set('access_token', $msbToken);
                                $linksRequest->headers->set('tenant_id', $tenantId);

                                $linksResponse = $esignCtrl->getEasySignLinks($linksRequest, $epakId);
                                $linksData     = json_decode($linksResponse->getContent(), true);

                                if (
                                    isset($linksData['data'][0]['easySignInfo'])
                                    && is_array($linksData['data'][0]['easySignInfo'])
                                ) {
                                    $easySignInfo = $linksData['data'][0]['easySignInfo'];

                                    // Check whether every signer has status "Signed"
                                    $allSigned = count($easySignInfo) > 0 && collect($easySignInfo)->every(function ($info) {
                                        return strtolower($info['status'] ?? '') === 'signed';
                                    });

                                    if ($allSigned) {
                                        // Download signed PDF using metadata (filename => docId)
                                        $downloadedPath     = null;
                                        $downloadedFilename = null;

                                        foreach ($metadata as $filename => $docId) {
                                            $dlRequest = new \Illuminate\Http\Request();
                                            $dlRequest->headers->set('access_token', $msbToken);
                                            $dlRequest->headers->set('tenant_id', $tenantId);
                                            $dlRequest->merge([
                                                'docId'    => $docId,
                                                'filename' => $filename,
                                                'msb_token' => $msbToken,
                                                'tenant_id' => $tenantId,
                                            ]);

                                            $dlResponse = $esignCtrl->downloadDocument($dlRequest, $contract->id);
                                            $dlData     = json_decode($dlResponse->getContent(), true);

                                            if (!empty($dlData['success'])) {
                                                $downloadedPath     = $dlData['path'];
                                                $downloadedFilename = $dlData['filename'] ?? $filename;
                                            }
                                        }
                                        


                                        // Update contract attachment with the signed document
                                        $updateData = [
                                            'contract_status' => 'executed',
                                            'substatus'       => 'active',
                                        ];
                                        

                                        if ($downloadedPath) {
                                            $updateData['contract_attachment']          = $downloadedPath;
                                            $updateData['contract_attachment_filename'] = $downloadedFilename;
                                        }
                                        
                                       
                                        //contract->update($updateData)->except(['contract_name_decrypted']);
                                        Contract::where('id', $contract->id)->update($updateData);                                        

                                        // Mark esign record as completed
                                        $esignRecord->update(['status' => 0]);

                                        //$contract->refresh();
                                    }
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // Silently continue – the page should still render
                    //echo ;
                    \Log::error("Failed to Download Esigned File" . $e->getMessage()."--".$e->getLine());
                    //die;
                }
            }

            return view('contract::contract-custom.show', compact(
                'contract',
                'entityTypesList',
                'showCreditCellInputs',
                'creditCellData',
                'isCreditUser',
                'consultations',
                'tests',
                'locations',
                'approvalEntries',
                'overviewSummary',
                'currentEntry',
                'isApproverLevel2Active',
                'isApproverLevel3Active',
                'isSignatory',
                'isSecondApprover',
                'isApprover3',
                'isCreditCell',
                'readonlyForm',
                'isCurrentApproverIsApprover',
                'isCurrentApproverIsVerifier',
                'isCurrentApproverIsApproverOrVerifier',
                'isCurrentApproverActive',
                'isSignatory',
                'isOwner',
                'canViewForm',
                'canEditForm',
                'approvalCycleCompleted',
                'contractsparentList',
                'contractsSubseqList',
                'easySignInfo'
            ));
            
        } catch (Exception $e) {
            return redirect(url('/contracts/list/contract-custom/'))->with('message', 'Oops! Invalid Contract/Access Restricted')->with('alert-class', 'alert-danger');
        }
    }
    
    /**
     * Show the form for creating a new contract
     */
    
    /**
     * Approve contract - Save credit cell inputs and send approval emails
     */
    public function approve(Request $request, $id)
    {
       
        $validator = Validator::make($request->all(), [
            'current_outstanding' => 'required|numeric|min:0',
            'recommended_credit_limit' => 'required|numeric|min:0',
            'recommendation_comments' => 'required|string|max:1000',
            'current_approval' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        
        try {
        
            $contract = Contract::findOrFail($id);
            
            //Owner/Initiator Validation
            $owner_initiator = session()->get('contractSessionUser');
    
            $initiatior_exists = AddUsers::select('id',  decrypt_data('AccessScope', 'AddUsers'))
                ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
                ->first();
                
            if (!$initiatior_exists) {
                $invalid_owner_error = array('Owner Not Available Please Contact Administrator');
                return redirect('contracts/list/contract-custom')->withErrors(array_merge($fileError, $invalid_owner_error))->withInput();
            }
            
            $owner_initiator_id = $initiatior_exists->id;            
        
            // Prepare credit cell data
            $creditCellData = [
                'current_outstanding' => $request->input('current_outstanding'),
                'recommended_credit_limit' => $request->input('recommended_credit_limit'),
                'recommendation_comments' => $request->input('recommendation_comments'),
                'submitted_at' => now()->toDateTimeString(),
                'submitted_by' => $owner_initiator_id
            ];
            
            $existing = [];
            if (!empty($contract->confidentialityagreement)) {
                $existing = @json_decode($contract->confidentialityagreement, true) ?: [];
            }            
            
            $contract->update(['confidentialityagreement' => json_encode($creditCellData)]);
            // snapshot: credit cell submitted
            $this->createContractSnapshot($contract, 'Credit cell submitted');
            
            // if(count($existing) == 0){
            //     $this->createApprovalFlow($contract);
            // }
            
            DB::commit();
            
            $request->merge(['action' => 'approve', 'comments' => $request->input('recommendation_comments')]);
            
            $retrun = $this->approvalRespond($request, $id, $request->current_approval);

            // After successful save, generate approval flow and notify approvers
            // createApprovalFlow will update contract status to Review/In Review and create approval entries
            
            return redirect("/contracts/show/contract-custom/$id")->with('success', 'Contract approved successfully.  Approval emails have been sent to all approvers.');
            
        } catch (Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to approve contract: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    
    
    
    /**
     * Reject contract - Update status and substatus
     */
    public function reject(Request $request, $id)
    {
        // Accept optional reason for rejection
        $validator = Validator::make($request->all(), [
            'comments' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $contract = Contract::findOrFail($id);

            // Update contract status
            $contract->update([
                'contract_status' => 'Draft',
                'substatus' => 'Initial Draft',
            ]);
            // record history for rejection
            $this->createContractSnapshot($contract, 'Rejected by '.(Helpers::userInfo()->email ?? Helpers::userInfo()->FirstName ?? 'Unknown'));

            // Determine current user (rejector)
            $userInfo = Helpers::userInfo();
            $currentIdentifier = strtolower($userInfo->email ?? $userInfo->FirstName ?? '');
            $rejectorName = $userInfo->email ?? $userInfo->FirstName ?? 'A user';

            // Find approval entries and match rejecting approver entry if possible
            $approvalEntries = ApprovalContracts::where('contract_id', $contract->id)->orderBy('orderval', 'asc')->get();
            $rejectEntry = null;
            foreach ($approvalEntries as $entry) {
                $entryIdent = $entry->username ?? '';
                if (!empty($entry->username) && function_exists('decryptString')) {
                    try { $dec = decryptString($entry->username, 'username'); } catch (\Throwable $e) { $dec = $entry->username; }
                    $tmp = @json_decode($dec, true);
                    if (is_array($tmp)) {
                        $entryIdent = strtolower($tmp['email'] ?? $tmp['name'] ?? $dec);
                    } else {
                        $entryIdent = strtolower($dec);
                    }
                } else {
                    $entryIdent = strtolower($entryIdent);
                }
                if ($entryIdent) {
                    if (strpos($entryIdent, '@') !== false) {
                        if ($currentIdentifier === $entryIdent) { $rejectEntry = $entry; break; }
                    } else {
                        if ($currentIdentifier === strtolower($entryIdent)) { $rejectEntry = $entry; break; }
                    }
                }
            }

            // Collect recipients: those with orderval greater than rejecting approver, or all approvers if no match
            if ($rejectEntry) {
                $nextApprovals = ApprovalContracts::where('contract_id', $contract->id)
                    ->where('orderval', '>', $rejectEntry->orderval)
                    ->get();
            } else {
                $nextApprovals = ApprovalContracts::where('contract_id', $contract->id)->get();
            }

            $reason = $request->input('comments', null);

            // Prepare email content
            $subject = "Contract #{$contract->id} was rejected by {$rejectorName}";
            $contractTitle = '';
            if (!empty($contract->contract_name) && function_exists('decryptString')) {
                try { $contractTitle = decryptString($contract->contract_name, 'contract_name'); } catch (\Throwable $e) { $contractTitle = $contract->contract_name; }
            } else {
                $contractTitle = $contract->contract_name ?? '';
            }

            $body = "<p>Contract #{$contract->id} ('" . e($contractTitle) . "') was rejected by <strong>" . e($rejectorName) . "</strong>.</p>";
            if ($reason) {
                $body .= "<p><strong>Reason:</strong> " . e($reason) . "</p>";
            }
            $body .= "<p>View contract: <a href='" . url('/contracts/show/contract-custom/' . $contract->id) . "'>Open Contract</a></p>";

            // Send emails to all next approvers
            foreach ($nextApprovals as $n) {
                $to = $n->username ?? null;
                if (!empty($n->username) && function_exists('decryptString')) {
                    try { $dec = decryptString($n->username, 'username'); } catch (\Throwable $e) { $dec = $n->username; }
                    $tmp = @json_decode($dec, true);
                    $to = $tmp['email'] ?? $dec;
                }
                if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    try {
                        Mail::send([], [], function ($message) use ($to, $subject, $body) {
                            $message->to($to)->subject($subject);
                            $message->html($body);
                        });
                    } catch (Exception $e) {
                        \Log::error("reject: failed to notify approver {$to} for contract {$contract->id}: " . $e->getMessage());
                    }
                }
            }

            // Set all other approver flags to 1 (retain) similar to approvalRespond's behavior
            ApprovalContracts::where('contract_id', $contract->id)->where('id', '!=', $rejectEntry->id ?? 0)->update(['flag' => 1]);

            // Notify owner as well
            try { $this->notifyOwner(new Request(), $contract->id); } catch (Exception $e) { \Log::error('reject: notifyOwner failed: ' . $e->getMessage()); }

            return redirect()->back()
                ->with('warning', 'Contract has been rejected and moved to Initial Draft status.');

        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to reject contract: ' . $e->getMessage());
        }
    }
    
    // ========== PRIVATE HELPER METHODS ==========    
    
    /**
     * Create approval flow entries for a contract
     * - Tries to fetch approvers from approvers_master (or approvers) table if exists.
     * - If none found, fallback to contract owner as single approver.
     * - Encrypts specified fields using encryptString(value, username) if function exists.
     * - Sets flags and sends notification to first approver.
     * - For draft contracts ($isDraftOnly=true), only creates owner approval entry.
     * - Full workflow is generated after owner approves the draft.
     */
    protected function createApprovalFlow(Contract $contract, $isDraftOnly=false, $customTemplate = false)
    {

            $data = json_decode($contract->rules_id, true);
            
            // Main approval info
            $allApprovers = [
                'approval_status' => $data['approval_status'],
                'approval_type'   => $data['approval_type'],
                'approvers'       => []
            ];
            
            // For draft contracts, only add the owner/creator as approver
            // The full workflow will be generated after owner approves
            if($isDraftOnly){
                //User/Creater Approver - Draft only flow
                $owner_initiator = session()->get('contractSessionUser');
        
                $initiatior_exists = AddUsers::select('id',  decrypt_data('UserName', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))
                    ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
                    ->first();
                if ($initiatior_exists) {
                    $approverUser = [];
                    $approverUser['email'] = $initiatior_exists->UserName;                        
                    $approverUser['id'] = $initiatior_exists->id;
                    $approverUser['name'] = $initiatior_exists->FirstName;
                    $approverUser['type'] = 'name';
                    $allApprovers['approvers'][] = [
                        'role'          => 'Owner',
                        'approval_type' => 'sequential',
                        'approver'      => $approverUser
                    ];                
                }
                
                // For draft, skip the rest of the approval flow generation
                // and just create the owner entry
                return $this->createOwnerOnlyApprovalEntry($contract, $allApprovers);
            }

            // If a custom template/.docx was uploaded for this contract, add Corporate Legal approver and signatory from admin settings
            try {
                if ($customTemplate && preg_match('/\.(doc|docx)$/i', $contract->contract_attachment_filename)) {
                    // Helper to parse admin_setting values which may be JSON or plain strings (single quotes tolerated)
                    $parseAdminUser = function ($raw) {
                        if (empty($raw)) return null;
                        if (is_array($raw)) return $raw;
                        $json = trim((string)$raw);
                        $data = @json_decode($json, true);
                        if (!is_array($data)) {
                            // try converting single quotes to double quotes
                            $alt = str_replace("'", '"', $json);
                            $data = @json_decode($alt, true);
                        }
                        if (is_array($data)) return $data;
                        // last resort: if it contains @ treat as email, else treat as name
                        if (strpos($json, '@') !== false) return ['email' => $json];
                        return ['name' => $json];
                    };

                    $corpApproverRaw = admin_setting('corp_approver') ?? admin_setting('default_approver') ?? null;

                    $corpApprover = $parseAdminUser($corpApproverRaw);


                    // Normalize to approver structure
                    $approverUser = [
                        'email' => $corpApprover['email'] ?? null,
                        'id' => $corpApprover['id'] ?? null,
                        'name' => $corpApprover['name'] ?? ($corpApprover['email'] ?? 'Corporate Approver'),
                        'type' => 'name'
                    ];

                    $allApprovers['approvers'][] = [
                        'role' => 'Verifier',
                        'approval_type' => 'sequential',
                        'approver' => $approverUser
                    ];

                }
            } catch (\Throwable $e) {
                // swallow errors - non-critical
                \Log::error('Failed to add corporate approver/signatory from admin settings: ' . $e->getMessage());
            }
            
            // Loop approval_required_users
            foreach ($data['approval_required_users'] as $group) {
                foreach ($group['approvers'] as $approver) {
                    $allApprovers['approvers'][] = [
                        'role'          => $group['role'],
                        'approval_type' => $group['approval_type'],
                        'approver'      => $approver
                    ];
                }
            }
            
            
            $approvers = $allApprovers['approvers'];

            if (empty($approvers)) {
                // No approvers found - nothing to create
                return false;
            }
            

            // We'll create ApprovalContracts entries in serial order using approver list
            $ord = 1;
            $createdEntries = [];
            $branchHeads = BranchUser::select(
                'id',
                decrypt_data('branchheadname', 'branch'),
                'Branchhead',
                decrypt_data('departments', 'branch'),
                decrypt_data('LegalName', 'branch')
            )->where('id', 1)->first();
            
            foreach ($approvers as $apprData) {
                
                $approver = (object) $apprData['approver'];
        
                $branchHeadsError = [];
                
                if ($approver->type == 'designation') {
                    if ($approver->name == 'unit_head') {
                        //User/Creater Approver
                        $owner_initiator = session()->get('contractSessionUser');
                
                        $initiatior_exists = AddUsers::select('id',  decrypt_data('UserName', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('Manager', 'AddUsers'))
                            ->where(decrypt_datas('UserName', 'AddUsers'), $owner_initiator)
                            ->first();
                        if ($initiatior_exists) {
                            $initiatior_manager_exists = AddUsers::select('id',  decrypt_data('UserName', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('Manager', 'AddUsers'))
                                ->where(decrypt_datas('UserName', 'AddUsers'), $initiatior_exists->Manager)
                                ->first();
                                
                            if ($initiatior_manager_exists) {
                                $approver->id = $initiatior_manager_exists->id;
                                $approver->email = $initiatior_manager_exists->UserName;
                            }else{
                                $branchHeadsError[] = "Unit Head Not Present For User";
                            }
                        }else{
                            $branchHeadsError[] = "User Not Exist";
                        }
                    }
                    if ($approver->name == 'branch_head') {
                        $branchHeadId = $branchHeads->Branchhead;
                        if ($branchHeadId == null) {
                            $branchHeadsError[] = "Branch Head Not Added in your selected Branch Please Update In Goal Portal";
                        }
                        $approver->id = $branchHeadId;
                    }
                    if ($approver->name == 'branch_dep_head') {
                        $branchDeptData = unserialize($branchHeads->departments);
                        //print_r($branchDeptData);
                        if (!isset($branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')])) {
                            $branchHeadsError[] = "Branch Department Head Not Added in your selected Branch Please Update In Goal Portal";
                        } else {
                            $approver->id = $branchDeptData["departmentheadid"][$request->input('BasicContract.DepartmentType')];
                        }
                    }
                    if ($approver->name == 'overall_dept_head') {
                        $entityDeptHead = EntityBusiness::select('overall_dept_head')->where('id', $request->input('BasicContract.DepartmentType'))->first();
                        if (!$entityDeptHead || !$entityDeptHead->overall_dept_head) {
                            $branchHeadsError[] = "Department Over All Head Not Added in your Entity Business Please Update In Goal Portal";
                        } else {
                            $approver->id = $entityDeptHead->overall_dept_head;
                        }
                    }
                    $usersFetch = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'))->where('id', $approver->id)->first();
                    $usernameValue = $usersFetch->Email ?? null;
                    $approver->name = $usersFetch->FirstName ?? null;
                }else{
                    $usernameValue = $approver->email;
                }
                
                
                $encryptKey = $usernameValue;

                // Build username payload (email + name) and encrypt as JSON when helper exists
                $usernamePayload = ['email' => $approver->email ?? $usernameValue, 'name' => $approver->name ?? ($usersFetch->FirstName ?? null)];
                $usernameJson = json_encode($usernamePayload);
                $usernameEncrypted = function_exists('encryptString') ? @encryptString($usernameJson, 'username') : $usernameJson;
                $statusEncrypted = function_exists('encryptString') ? @encryptString('Pending', 'status') : 'Pending';
                $previousStatusEncrypted = function_exists('encryptString') ? @encryptString($contract->contract_status ?? '', 'previous_status') : ($contract->contract_status ?? '');
                $approvalStatusEncrypted = function_exists('encryptStringx') ? @encryptStringx('pending', 'approval_contracts.approval_status') : 'pending';
                $nextStatusEncrypted = function_exists('encryptString') ? @encryptString('Review', 'next_status') : 'Review';
                $randNo = rand(0, 99999);
                $unique_id_loop = $contract->id . $randNo;

                $entry = ApprovalContracts::create([
                    'username' => $usernameEncrypted,
                    'approval_type_main' => $allApprovers['approval_type'],
                    'approval_type_row' => $apprData['approval_type'],
                    'approver_type_row' => $apprData['role'],
                    'unique_id' => $unique_id_loop,
                    'status' => $statusEncrypted,
                    'orderval' => $appr->orderval ?? $ord,
                    'previous_status' => $previousStatusEncrypted,
                    'contract_id' => $contract->id,
                    'next_action_item' => null,
                    'next_action_description' => null,
                    'button_text' => 'Open',
                    'attachments' => $contract->contract_attachment ?? null,
                    'attachments_filename' => $contract->contract_attachment_filename ?? null,
                    'approval_status' => $approvalStatusEncrypted,
                    'flag' => 1,
                    'next_status' => $nextStatusEncrypted,
                    'fileType' => $contract->fileType ?? fileStorageType(),
                    'created_by' => $contract->created_by
                ]);

                $createdEntries[] = $entry;
                $ord++;
            }


            // Determine initial active approvers based on main approval type and row approval type
            $entries = ApprovalContracts::where('contract_id', $contract->id)->orderBy('orderval', 'asc')->get();
            $groupsOrder = $entries->pluck('approver_type_row')->unique();
            $mainType = strtolower($allApprovers['approval_type'] ?? 'sequential');

            if ($mainType === 'sequential') {
                // Only the first group is active initially
                $firstGroupName = $entries->first()->approver_type_row ?? null;
                foreach ($groupsOrder as $groupName) {
                    $groupEntries = $entries->where('approver_type_row', $groupName);
                    $rowType = strtolower($groupEntries->first()->approval_type_row ?? 'sequential');
                    if ($groupName === $firstGroupName) {
                        if ($rowType === 'parallel') {
                            ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $groupName)->update(['flag' => 1]);
                        } else {
                            $firstInGroup = $groupEntries->sortBy('orderval')->first();
                            if ($firstInGroup) {
                                ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $groupName)->update(['flag' => 0]);
                                ApprovalContracts::where('id', $firstInGroup->id)->update(['flag' => 1]);
                            }
                        }
                    } else {
                        ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $groupName)->update(['flag' => 0]);
                    }
                }
            } else {
                // Main parallel: activate each group according to its row type
                foreach ($groupsOrder as $groupName) {
                    $groupEntries = $entries->where('approver_type_row', $groupName);
                    $rowType = strtolower($groupEntries->first()->approval_type_row ?? 'sequential');
                    if ($rowType === 'parallel') {
                        ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $groupName)->update(['flag' => 1]);
                    } else {
                        $firstInGroup = $groupEntries->sortBy('orderval')->first();
                        if ($firstInGroup) {
                            ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $groupName)->update(['flag' => 0]);
                            ApprovalContracts::where('id', $firstInGroup->id)->update(['flag' => 1]);
                        }
                    }
                }
            }
            
            if(!$isDraftOnly){
                // Update contract status to Review / In Review
                $contract->update([
                    'contract_status' => 'Review',
                    'substatus' => 'In Review'
                ]);
                // snapshot after status change
                $this->createContractSnapshot($contract, 'Approval flow created — moved to Review');
            }

            // Notify active approvers (skip Owner role notifications as before)
            $activeEntries = ApprovalContracts::where('contract_id', $contract->id)->where('flag', 1)->get();
            foreach ($activeEntries as $act) {
                $to = null;
                try { $dec = function_exists('decryptString') ? @decryptString($act->username, 'username') : $act->username; } catch (\Throwable $e) { $dec = $act->username; }
                $tmp = @json_decode($dec, true);
                $to = $tmp['email'] ?? $dec;
                if (!$to) continue;
                // skip notifications for Owner role
                if (strtolower($act->approver_type_row ?? '') === 'owner') continue;

                $subject = "Contract #{$contract->id} requires your review";
                $emailRecipients = ['to' => $to];
                $htmlTemplate = $this->getApprovalTemplate($contract->id, $act->approver_type_row == 'Approver' ? true : false);
                if ($htmlTemplate) {
                    try {
                        Mail::send([], [], function ($message) use ($emailRecipients, $subject, $htmlTemplate) {
                            if (!empty($emailRecipients['to'])) {
                                $message->to($emailRecipients['to']);
                            }
                            if (!empty($emailRecipients['cc'])) {
                                $message->cc($emailRecipients['cc']);
                            }
                            if (!empty($emailRecipients['bcc'])) {
                                $message->bcc($emailRecipients['bcc']);
                            }
                            $message->subject($subject);
                            $message->html($htmlTemplate);
                        });
                    } catch (Exception $e) {
                        \Log::error("Failed to notify approver {$to}: " . $e->getMessage());
                    }
                }
            }

            return true;

    }

    /**
     * Create owner-only approval entry for draft contracts
     * This is called when contract is saved as draft - only owner approval is created
     */
    protected function createOwnerOnlyApprovalEntry(Contract $contract, array $allApprovers)
    {
        $approvers = $allApprovers['approvers'];
        
        if (empty($approvers)) {
            return false;
        }
        
        $branchHeads = BranchUser::select(
            'id',
            decrypt_data('branchheadname', 'branch'),
            'Branchhead',
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->where('id', 1)->first();
        
        $apprData = $approvers[0]; // Owner is the first and only approver for draft
        $approver = (object) $apprData['approver'];
        
        $usernameValue = $approver->email ?? $approver->name ?? null;
        $encryptKey = $usernameValue;
        
        $usernamePayload = ['email' => $approver->email ?? $usernameValue, 'name' => $approver->name ?? null];
        $usernameJson = json_encode($usernamePayload);
        $usernameEncrypted = function_exists('encryptString') ? @encryptString($usernameJson, 'username') : $usernameJson;
        $statusEncrypted = function_exists('encryptString') ? @encryptString('Pending', 'status') : 'Pending';
        $previousStatusEncrypted = function_exists('encryptString') ? @encryptString($contract->contract_status ?? '', 'previous_status') : ($contract->contract_status ?? '');
        $approvalStatusEncrypted = function_exists('encryptStringx') ? @encryptStringx('pending', 'approval_contracts.approval_status') : 'pending';
        $nextStatusEncrypted = function_exists('encryptString') ? @encryptString('Review', 'next_status') : 'Review';
        $randNo = rand(0, 99999);
        $unique_id_loop = $contract->id . $randNo;
        
        $entry = ApprovalContracts::create([
            'contract_id' => $contract->id,
            'unique_id' => $unique_id_loop,
            'username' => $usernameEncrypted,
            'status' => $statusEncrypted,
            'previous_status' => $previousStatusEncrypted,
            'approval_status' => $approvalStatusEncrypted,
            'next_status' => $nextStatusEncrypted,
            'button_text' => 'Approve Draft',
            'approver_type_row' => 'Owner',
            'approval_type_row' => 'sequential',
            'approval_type_main' => $allApprovers['approval_type'] ?? 'sequential',
            'orderval' => 1,
            'flag' => 1, // Active immediately
            'created_by' => $contract->created_by
        ]);
        
        // Contract stays in Draft status - no status change for draft-only flow
        $this->createContractSnapshot($contract, 'Draft approval flow created — awaiting owner confirmation');
        
        return true;
    }
    
    /**
     * Generate full approval workflow after owner approves draft
     * This removes the owner entry and creates the complete approval flow
     */
    protected function generateFullApprovalWorkflow(Contract $contract, $customTemplate = false)
    {
        $data = json_decode($contract->rules_id, true);
        
        // Main approval info
        $allApprovers = [
            'approval_status' => $data['approval_status'],
            'approval_type'   => $data['approval_type'],
            'approvers'       => []
        ];
        
        // If a custom template/.docx was uploaded, add Corporate Legal approver
        try {
            if ($customTemplate && preg_match('/\.(doc|docx)$/i', $contract->contract_attachment_filename)) {
                $parseAdminUser = function ($raw) {
                    if (empty($raw)) return null;
                    if (is_array($raw)) return $raw;
                    $json = trim((string)$raw);
                    $data = @json_decode($json, true);
                    if (!is_array($data)) {
                        $alt = str_replace("'", '"', $json);
                        $data = @json_decode($alt, true);
                    }
                    if (is_array($data)) return $data;
                    if (strpos($json, '@') !== false) return ['email' => $json];
                    return ['name' => $json];
                };

                $corpApproverRaw = admin_setting('corp_approver') ?? admin_setting('default_approver') ?? null;
                $corpApprover = $parseAdminUser($corpApproverRaw);

                $approverUser = [
                    'email' => $corpApprover['email'] ?? null,
                    'id' => $corpApprover['id'] ?? null,
                    'name' => $corpApprover['name'] ?? ($corpApprover['email'] ?? 'Corporate Approver'),
                    'type' => 'name'
                ];

                $allApprovers['approvers'][] = [
                    'role' => 'Verifier',
                    'approval_type' => 'sequential',
                    'approver' => $approverUser
                ];
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to add corporate approver: ' . $e->getMessage());
        }
        
        // Loop approval_required_users to build full workflow
        foreach ($data['approval_required_users'] as $group) {
            foreach ($group['approvers'] as $approver) {
                $allApprovers['approvers'][] = [
                    'role'          => $group['role'],
                    'approval_type' => $group['approval_type'],
                    'approver'      => $approver
                ];
            }
        }
        
        $approvers = $allApprovers['approvers'];
        
        if (empty($approvers)) {
            return false;
        }
        
        // Create approval entries
        $ord = 1;
        $createdEntries = [];
        $branchHeads = BranchUser::select(
            'id',
            decrypt_data('branchheadname', 'branch'),
            'Branchhead',
            decrypt_data('departments', 'branch'),
            decrypt_data('LegalName', 'branch')
        )->where('id', 1)->first();
        
        foreach ($approvers as $apprData) {
            $approver = (object) $apprData['approver'];
            
            if ($approver->type == 'designation') {
                $usersFetch = AddUsers::select('id', decrypt_data('FirstName', 'AddUsers'), decrypt_data('UserName', 'AddUsers'))
                    ->where('designation', $approver->id)
                    ->first();
                if (!$usersFetch) continue;
                $usernameValue = $usersFetch->UserName ?? null;
                $approver->email = $usernameValue;
                $approver->name = $usersFetch->FirstName ?? null;
            } else {
                $usernameValue = $approver->email ?? $approver->name ?? null;
            }
            
            $encryptKey = $usernameValue;
            $usernamePayload = ['email' => $approver->email ?? $usernameValue, 'name' => $approver->name ?? null];
            $usernameJson = json_encode($usernamePayload);
            $usernameEncrypted = function_exists('encryptString') ? @encryptString($usernameJson, 'username') : $usernameJson;
            $statusEncrypted = function_exists('encryptString') ? @encryptString('Pending', 'status') : 'Pending';
            $previousStatusEncrypted = function_exists('encryptString') ? @encryptString($contract->contract_status ?? '', 'previous_status') : ($contract->contract_status ?? '');
            $approvalStatusEncrypted = function_exists('encryptStringx') ? @encryptStringx('pending', 'approval_contracts.approval_status') : 'pending';
            $nextStatusEncrypted = function_exists('encryptString') ? @encryptString('Review', 'next_status') : 'Review';
            $randNo = rand(0, 99999);
            $unique_id_loop = $contract->id . $randNo;
            
            $entry = ApprovalContracts::create([
                'contract_id' => $contract->id,
                'unique_id' => $unique_id_loop,
                'username' => $usernameEncrypted,
                'status' => $statusEncrypted,
                'previous_status' => $previousStatusEncrypted,
                'approval_status' => $approvalStatusEncrypted,
                'next_status' => $nextStatusEncrypted,
                'button_text' => 'Approve',
                'approver_type_row' => $apprData['role'],
                'approval_type_row' => $apprData['approval_type'],
                'approval_type_main' => $allApprovers['approval_type'] ?? 'sequential',
                'orderval' => $ord,
                'flag' => 0,
                'created_by' => $contract->created_by
            ]);
            
            $createdEntries[] = $entry;
            $ord++;
        }
        
        // Activate first approvers based on approval type
        $entries = ApprovalContracts::where('contract_id', $contract->id)
            ->where('approver_type_row', '!=', 'Owner')
            ->orderBy('orderval', 'asc')
            ->get();
        
        if ($entries->count() > 0) {
            $groupsOrder = $entries->pluck('approver_type_row')->unique();
            $mainType = strtolower($allApprovers['approval_type'] ?? 'sequential');
            
            if ($mainType === 'sequential') {
                $firstGroupName = $entries->first()->approver_type_row ?? null;
                foreach ($groupsOrder as $groupName) {
                    $groupEntries = $entries->where('approver_type_row', $groupName);
                    $rowType = strtolower($groupEntries->first()->approval_type_row ?? 'sequential');
                    if ($groupName === $firstGroupName) {
                        if ($rowType === 'parallel') {
                            ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $groupName)->update(['flag' => 1]);
                        } else {
                            $firstInGroup = $groupEntries->sortBy('orderval')->first();
                            if ($firstInGroup) {
                                ApprovalContracts::where('id', $firstInGroup->id)->update(['flag' => 1]);
                            }
                        }
                    }
                }
            } else {
                foreach ($groupsOrder as $groupName) {
                    $groupEntries = $entries->where('approver_type_row', $groupName);
                    $rowType = strtolower($groupEntries->first()->approval_type_row ?? 'sequential');
                    if ($rowType === 'parallel') {
                        ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $groupName)->update(['flag' => 1]);
                    } else {
                        $firstInGroup = $groupEntries->sortBy('orderval')->first();
                        if ($firstInGroup) {
                            ApprovalContracts::where('id', $firstInGroup->id)->update(['flag' => 1]);
                        }
                    }
                }
            }
        }
        
        // Update contract status to Review
        $contract->update([
            'contract_status' => 'Review',
            'substatus' => 'In Review'
        ]);
        
        $this->createContractSnapshot($contract, 'Full approval workflow generated after owner approval');
        
        // Notify active approvers
        $activeEntries = ApprovalContracts::where('contract_id', $contract->id)
            ->where('flag', 1)
            ->where('approver_type_row', '!=', 'Owner')
            ->get();
        
        foreach ($activeEntries as $act) {
            $to = null;
            try { $dec = function_exists('decryptString') ? @decryptString($act->username, 'username') : $act->username; } catch (\Throwable $e) { $dec = $act->username; }
            $tmp = @json_decode($dec, true);
            $to = $tmp['email'] ?? $dec;
            if (!$to) continue;
            
            $subject = "Contract #{$contract->id} requires your review";
            $emailRecipients = ['to' => $to];
            $htmlTemplate = $this->getApprovalTemplate($contract->id, $act->approver_type_row == 'Approver' ? true : false);
            if ($htmlTemplate) {
                try {
                    Mail::send([], [], function ($message) use ($emailRecipients, $subject, $htmlTemplate) {
                        if (!empty($emailRecipients['to'])) $message->to($emailRecipients['to']);
                        $message->subject($subject);
                        $message->html($htmlTemplate);
                    });
                } catch (Exception $e) {
                    \Log::error("Failed to notify approver {$to}: " . $e->getMessage());
                }
            }
        }
        
        return true;
    }
    
    /**
     * Re-evaluate and add missing approvers to workflow after contract data is modified
     * Compares current approval flow with new evaluation and adds any missing approvers
     * Missing approvers are inserted based on their approver_type_row (Verifier, Approver, Signatory)
     * Also removes approvers that are no longer needed (only if not already approved)
     */
    protected function reEvaluateAndAddMissingApprovers(Contract $contract, array $contractData)
    {
        try {
            // Get new approval requirements based on updated data
            $newRules = $this->evaluateDiscountApproval($contractData, 'approval_required_users', 0);
            
            if (!is_array($newRules) || empty($newRules['approval_required_users'])) {
                return ['changed' => false, 'added' => [], 'removed' => [], 'message' => 'No new approvers required'];
            }
            
            // Define the role order for proper insertion (lower = earlier in workflow)
            $roleOrder = [
                'owner' => 1,
                'preapprover' => 2,
                'verifier' => 3,
                'approver' => 4,
                'signatory' => 5
            ];
            
            // Extract emails with their roles from new rules
            $newRequiredApprovers = [];
            foreach ($newRules['approval_required_users'] as $group) {
                foreach ($group['approvers'] as $approver) {
                    $email = strtolower(trim($approver['email'] ?? ''));
                    if ($email) {
                        $newRequiredApprovers[$email] = [
                            'email' => $approver['email'] ?? null,
                            'name' => $approver['name'] ?? null,
                            'role' => $group['role'],
                            'approval_type' => $group['approval_type']
                        ];
                    }
                }
            }
            
            // Get existing approvers in the workflow (excluding Owner)
            $existingApprovals = ApprovalContracts::where('contract_id', $contract->id)
                ->where('approver_type_row', '!=', 'Owner')
                ->orderBy('orderval', 'asc')
                ->get();
            
            $existingEmails = [];
            $existingByRole = [];
            $existingApprovalMap = []; // Map email to approval record for removal check
            
            foreach ($existingApprovals as $approval) {
                try {
                    $dec = function_exists('decryptString') ? @decryptString($approval->username, 'username') : $approval->username;
                } catch (\Throwable $e) {
                    $dec = $approval->username;
                }
                $tmp = @json_decode($dec, true);
                $email = strtolower(trim($tmp['email'] ?? $dec ?? ''));
                if ($email) {
                    $existingEmails[] = $email;
                    $existingApprovalMap[$email] = $approval;
                    $role = strtolower($approval->approver_type_row ?? 'approver');
                    if (!isset($existingByRole[$role])) {
                        $existingByRole[$role] = [];
                    }
                    $existingByRole[$role][] = [
                        'email' => $email,
                        'orderval' => $approval->orderval
                    ];
                }
            }
            
            // Find missing approvers (need to add)
            $missingEmails = array_diff(array_keys($newRequiredApprovers), $existingEmails);
            
            // Find extra approvers (need to remove if dynamically added and not approved)
            $extraEmails = array_diff($existingEmails, array_keys($newRequiredApprovers));
            
            // Track removed approvers
            $removedApprovers = [];
            
            // Remove extra approvers that are:
            // 1. Dynamically added (marked with 'dynamically_added' in next_action_description)
            // 2. NOT already approved
            foreach ($extraEmails as $extraEmail) {
                if (!isset($existingApprovalMap[$extraEmail])) {
                    continue;
                }
                
                $approvalRecord = $existingApprovalMap[$extraEmail];
                
                // Only remove dynamically added approvers (not initial approvers)
                // Dynamically added approvers have 'dynamically_added' marker
                $isDynamicallyAdded = ($approvalRecord->next_action_description === 'dynamically_added');
                
                if (!$isDynamicallyAdded) {
                    // Skip - this is an initial approver, do not remove
                    continue;
                }
                
                // Check if the approver has already approved
                $approvalStatus = $approvalRecord->approval_status ?? '';
                try {
                    $decryptedStatus = function_exists('decryptString') 
                        ? @decryptString($approvalStatus, 'approval_status') 
                        : $approvalStatus;
                } catch (\Throwable $e) {
                    $decryptedStatus = $approvalStatus;
                }
                
                // Only remove if NOT approved
                if (strtolower($decryptedStatus) !== 'approved') {
                    // Delete the approval record
                    ApprovalContracts::where('id', $approvalRecord->id)->delete();
                    $removedApprovers[] = $extraEmail;
                    
                    // Remove from existingByRole
                    $role = strtolower($approvalRecord->approver_type_row ?? 'approver');
                    if (isset($existingByRole[$role])) {
                        $existingByRole[$role] = array_filter($existingByRole[$role], function($item) use ($extraEmail) {
                            return $item['email'] !== $extraEmail;
                        });
                    }
                }
            }
            
            // Re-order remaining approvers after removal to close gaps
            if (!empty($removedApprovers)) {
                $remainingApprovals = ApprovalContracts::where('contract_id', $contract->id)
                    ->orderBy('orderval', 'asc')
                    ->get();
                
                $newOrder = 1;
                foreach ($remainingApprovals as $remaining) {
                    if ($remaining->orderval != $newOrder) {
                        $remaining->update(['orderval' => $newOrder]);
                    }
                    $newOrder++;
                }
                
                // Refresh existingByRole with updated ordervals
                $existingByRole = [];
                foreach ($remainingApprovals as $approval) {
                    if (strtolower($approval->approver_type_row ?? '') === 'owner') continue;
                    try {
                        $dec = function_exists('decryptString') ? @decryptString($approval->username, 'username') : $approval->username;
                    } catch (\Throwable $e) {
                        $dec = $approval->username;
                    }
                    $tmp = @json_decode($dec, true);
                    $email = strtolower(trim($tmp['email'] ?? $dec ?? ''));
                    if ($email) {
                        $role = strtolower($approval->approver_type_row ?? 'approver');
                        if (!isset($existingByRole[$role])) {
                            $existingByRole[$role] = [];
                        }
                        $existingByRole[$role][] = [
                            'email' => $email,
                            'orderval' => $approval->orderval
                        ];
                    }
                }
            }
            
            // If no missing approvers and no removals, return early
            if (empty($missingEmails) && empty($removedApprovers)) {
                return ['changed' => false, 'added' => [], 'removed' => [], 'message' => 'Approval flow still matches - no changes needed'];
            }
            
            // Track added approvers
            $addedApprovers = [];
            
            // If there are missing approvers, add them
            if (!empty($missingEmails)) {
                // Group missing approvers by their role
                $missingByRole = [];
                foreach ($missingEmails as $email) {
                    $approverInfo = $newRequiredApprovers[$email];
                    $role = strtolower($approverInfo['role']);
                    if (!isset($missingByRole[$role])) {
                        $missingByRole[$role] = [];
                    }
                    $missingByRole[$role][] = $approverInfo;
                }
            
                // Sort missing roles by role order
                uksort($missingByRole, function($a, $b) use ($roleOrder) {
                    $orderA = $roleOrder[$a] ?? 99;
                    $orderB = $roleOrder[$b] ?? 99;
                    return $orderA - $orderB;
                });
            
                // Process each role group and insert at appropriate position
                foreach ($missingByRole as $role => $approvers) {
                    // Determine the insertion orderval based on role
                    // Find the maximum orderval of approvers with lower or same role order
                    $insertAfterOrder = 0;
                    
                    foreach ($roleOrder as $existingRole => $order) {
                        if ($order <= ($roleOrder[$role] ?? 99)) {
                            if (isset($existingByRole[$existingRole])) {
                                foreach ($existingByRole[$existingRole] as $existing) {
                                    if ($existing['orderval'] > $insertAfterOrder) {
                                        $insertAfterOrder = $existing['orderval'];
                                    }
                                }
                            }
                        }
                    }
                    
                    // If no existing approvers found with lower/same role, find the max orderval of Owner
                    if ($insertAfterOrder == 0) {
                        $ownerMax = ApprovalContracts::where('contract_id', $contract->id)
                            ->where('approver_type_row', 'Owner')
                            ->max('orderval');
                        $insertAfterOrder = $ownerMax ?? 0;
                    }
                    
                    // Shift existing approvers with higher orderval to make room
                    $approversToShift = ApprovalContracts::where('contract_id', $contract->id)
                        ->where('orderval', '>', $insertAfterOrder)
                        ->orderBy('orderval', 'desc')
                        ->get();
                    
                    $shiftAmount = count($approvers);
                    foreach ($approversToShift as $toShift) {
                        $toShift->update(['orderval' => $toShift->orderval + $shiftAmount]);
                    }
                    
                    // Insert the missing approvers (marked as dynamically added)
                    $currentOrder = $insertAfterOrder + 1;
                    foreach ($approvers as $approver) {
                        $usernamePayload = ['email' => $approver['email'] ?? null, 'name' => $approver['name'] ?? null];
                        $usernameJson = json_encode($usernamePayload);
                        $usernameEncrypted = function_exists('encryptString') ? @encryptString($usernameJson, 'username') : $usernameJson;
                        $statusEncrypted = function_exists('encryptString') ? @encryptString('Pending', 'status') : 'Pending';
                        $previousStatusEncrypted = function_exists('encryptString') ? @encryptString($contract->contract_status ?? '', 'previous_status') : ($contract->contract_status ?? '');
                        $approvalStatusEncrypted = function_exists('encryptStringx') ? @encryptStringx('pending', 'approval_contracts.approval_status') : 'pending';
                        $nextStatusEncrypted = function_exists('encryptString') ? @encryptString('Review', 'next_status') : 'Review';
                        $randNo = rand(0, 99999);
                        $unique_id_loop = $contract->id . $randNo;
                        
                        ApprovalContracts::create([
                            'contract_id' => $contract->id,
                            'unique_id' => $unique_id_loop,
                            'username' => $usernameEncrypted,
                            'status' => $statusEncrypted,
                            'previous_status' => $previousStatusEncrypted,
                            'approval_status' => $approvalStatusEncrypted,
                            'next_status' => $nextStatusEncrypted,
                            'button_text' => 'Approve',
                            'approver_type_row' => ucfirst($role), // Verifier, Approver, Signatory
                            'approval_type_row' => $approver['approval_type'] ?? 'sequential',
                            'approval_type_main' => $newRules['approval_type'] ?? 'sequential',
                            'orderval' => $currentOrder,
                            'flag' => 0, // Not active yet - will be activated in sequence
                            'next_action_description' => 'dynamically_added', // Mark as dynamically added for removal tracking
                            'created_by' => $contract->created_by
                        ]);
                        
                        $addedApprovers[] = strtolower(trim($approver['email'] ?? ''));
                        
                        // Update existingByRole for subsequent iterations
                        if (!isset($existingByRole[$role])) {
                            $existingByRole[$role] = [];
                        }
                        $existingByRole[$role][] = [
                            'email' => strtolower(trim($approver['email'] ?? '')),
                            'orderval' => $currentOrder
                        ];
                        
                        $currentOrder++;
                    }
                }
            } // End of if (!empty($missingEmails))
            
            if (!empty($addedApprovers) || !empty($removedApprovers)) {
                // Update rules_id with new rules
                $contract->update(['rules_id' => json_encode($newRules)]);
                $snapshotMsg = [];
                if (!empty($addedApprovers)) {
                    $snapshotMsg[] = 'Added approvers: ' . implode(', ', $addedApprovers);
                }
                if (!empty($removedApprovers)) {
                    $snapshotMsg[] = 'Removed approvers: ' . implode(', ', $removedApprovers);
                }
                $this->createContractSnapshot($contract, 'Approval flow modified after data change - ' . implode('; ', $snapshotMsg));
            }
            
            return [
                'changed' => !empty($addedApprovers) || !empty($removedApprovers), 
                'added' => $addedApprovers, 
                'removed' => $removedApprovers,
                'message' => 'Added ' . count($addedApprovers) . ' approvers, removed ' . count($removedApprovers) . ' approvers'
            ];
            
        } catch (\Throwable $e) {
            \Log::error('Failed to re-evaluate approvers: ' . $e->getMessage());
            return ['changed' => false, 'added' => [], 'removed' => [], 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Notify owner API (called when Edit button is clicked in approvals UI)
     * This will send a simple email notification to contract owner / configured recipients
     */
    public function notifyOwner(Request $request, $id)
    {
        try {
            $contract = Contract::findOrFail($id);

            // Determine owner email
            $ownerEmail = null;
            if (is_numeric($contract->created_by)) {
                $owner = AddUsers::select('id',  decrypt_data('Email', 'AddUsers'))->find($contract->created_by);
                $ownerEmail = $owner->Email ?? null;
            }

            if (!$ownerEmail) {
                return redirect(url('/contracts/show/contract-custom/'.$id))->with('error', 'Owner email not available.');
            }

            $subject = "Contract #{$contract->id} - Edit requested";
            $body = "Dear Owner,\n\nAn edit was requested for Contract (ID: {$contract->id}).\nPlease review: " . url('/contracts/show/contract-custom/'.$contract->id) . "\n\nRegards,\nContracts System";

            Mail::raw($body, function ($message) use ($ownerEmail, $subject) {
                $message->to($ownerEmail)->subject($subject);
            });

            return redirect(url('/contracts/show/contract-custom/'.$id))->with('success', 'Owner notified about edit request.');
        } catch (Exception $e) {
            \Log::error('notifyOwner error: ' . $e->getMessage());
            return redirect(url('/contracts/show/contract-custom/'.$id))->with('error', 'Failed to notify owner.');
        }
    }
    
    /**
     * Show a single approval step view (iframe preview + comments + action form).
     * Route: GET /contracts/{contract}/approval/{approval}/view
     */
    public function approvalView($contractId, $approvalId)
    {
        $contract = Contract::with(['contractHealthChecks'])->findOrFail($contractId);
        $approval = ApprovalContracts::findOrFail($approvalId);

        // Decode stored username payload
        $username = $approval->username;
        if (!empty($approval->username) && function_exists('decryptString')) {
            try { $dec = decryptString($approval->username, 'username'); } catch (\Throwable $e) { $dec = $approval->username; }
            $tmp = @json_decode($dec, true);
            if (is_array($tmp)) {
                $usernameEmail = strtolower($tmp['email'] ?? '');
                $usernameName = strtolower($tmp['name'] ?? '');
                $username = $tmp['name'] ?? $tmp['email'] ?? $approval->username;
            } else {
                $usernameEmail = (stripos($dec, '@') !== false) ? strtolower($dec) : '';
                $usernameName = strtolower($dec);
                $username = $dec;
            }
        } else {
            $usernameEmail = '';
            $usernameName = '';
            $username = $approval->username;
        }

        $approval_status = $approval->approval_status;
        if (function_exists('decryptString') && !empty($approval->approval_status)) {
            try { $approval_status = decryptString($approval->approval_status, 'username'); } catch (\Throwable $e) { /* ignore */ }
        }

        $isCurrentApprover = false;
        $userInfo = Helpers::userInfo();
        if ($userInfo) {
            $userEmail = strtolower($userInfo->email ?? '');
            $userName = strtolower($userInfo->FirstName ?? '');
            if (!empty($usernameEmail)) {
                $isCurrentApprover = $userEmail === $usernameEmail;
            } else {
                $isCurrentApprover = (!empty($usernameName) && $userName === $usernameName) || $userEmail === strtolower($approval->username ?? '');
            }
        }

        $attachmentUrl = null;
        if (!empty($approval->attachments)) $attachmentUrl = asset('storage/' . $approval->attachments);
        elseif (!empty($contract->contract_attachment)) $attachmentUrl = asset('storage/' . $contract->contract_attachment);

        // overview summary (same as in show)
        $overviewSummary = [
            'packages_count' => 0,
            'total_tests_amount' => 0.0,
            'total_consultation_amount' => 0.0,
            'net_total' => 0.0,
        ];
        if ($contract->contractHealthChecks && $contract->contractHealthChecks->count()) {
            foreach ($contract->contractHealthChecks as $hc) {
                $overviewSummary['packages_count']++;
                $testsCollection = method_exists($hc, 'tests') ? $hc->tests()->get() : collect();
                $testSubtotal = 0.0;
                foreach ($testsCollection as $t) $testSubtotal += floatval($t->price ?? 0);

                $consultationSubtotal = 0.0;
                $consultIds = is_string($hc->selected_consultation_ids) ? @json_decode($hc->selected_consultation_ids, true) : $hc->selected_consultation_ids;
                $prices = is_string($hc->consultation_prices) ? @json_decode($hc->consultation_prices, true) : $hc->consultation_prices;
                if (!empty($consultIds) && is_array($prices)) {
                    foreach ((array)$consultIds as $cid) {
                        if (isset($prices[$cid])) $consultationSubtotal += floatval($prices[$cid]);
                    }
                }

                $overviewSummary['total_tests_amount'] += $testSubtotal;
                $overviewSummary['total_consultation_amount'] += $consultationSubtotal;
                $overviewSummary['net_total'] += ($testSubtotal + $consultationSubtotal + floatval($hc->package_price ?? 0));
            }
        }

        $isApproverLevel2 = ((int)$approval->orderval === 2 && (int)$approval->flag === 1);

        // determine if current user is signatory
        $isSignatory = false;
        if ($userInfo) {
            try {
                $uemail = strtolower($userInfo->email ?? '');
                if (!empty($uemail) && \Schema::hasTable('approvers_master')) {
                    $sig = \DB::table('approvers_master')->where('is_signatory',1)->whereRaw('LOWER(email)=?', [$uemail])->first();
                    if ($sig) $isSignatory = true;
                }
            } catch (\Throwable $e) { $isSignatory = false; }
        }
    }
    
    /**
     * Respond to an approval step (approve/reject).
     * Route: POST /contracts/{contract}/approval/{approval}/respond
     * Payload: action=approve|reject, comments=string
     */
    public function approvalRespond(Request $request, $contractId, $approvalId)
    {

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:2000',
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        DB::beginTransaction();
        try {
            $contract = Contract::findOrFail($contractId);
            $approval = ApprovalContracts::findOrFail($approvalId);

            // Prevent acting on approvals if contract is in a final/published state
            $finalStates = ['active','expired','completed','terminated'];
            if (in_array(strtolower($contract->contract_status ?? ''), $finalStates, true)) {
                DB::rollBack();
                return redirect(url('/contracts/show/contract-custom/'.$contractId).'?tab=approvals')->with('error', 'Contract cannot be modified in its current state ('.$contract->contract_status.').');
            }
            
            if(empty($contract->contract_attachment)){
                return redirect(url('/contracts/show/contract-custom/'.$contractId))->with('error', 'Sorry! Template Not Yet Created');
            }
    
            // Only allow current active approver to act (flag == 1)
            if ((int)$approval->flag !== 1) {
                return redirect(url('/contracts/show/contract-custom/'.$contractId).'?tab=approvals')->with('error', 'This approval step is not active for you.');
            }
    
            $action = $request->input('action');
            $comments = $request->input('comments');
    
// Derive a usable key/email from stored username payload
            $usernameKey = null;
            $usernameEmailForKey = null;
            try {
                $dec = function_exists('decryptString') ? @decryptString($approval->username, 'username') : $approval->username;
            } catch (\Throwable $e) {
                $dec = $approval->username;
            }
            $tmp = @json_decode($dec, true);
            if (is_array($tmp)) {
                $usernameEmailForKey = $tmp['email'] ?? null;
                $usernameKey = $usernameEmailForKey ?: $tmp['name'] ?? null;
            } else {
                $usernameKey = $dec;
            }
            $usernameKey = $usernameKey ?: ('approver_' . $approval->id);

            // Prepare updated_by payload
            $updatedUser = ['email' => Helpers::userInfo()->email ?? 'External', 'name' => Helpers::userInfo()->FirstName ?? 'User'];
            $updatedByPayload = function_exists('encryptString') ? @encryptString(json_encode($updatedUser), 'updated_by') : json_encode($updatedUser);

            if ($action === 'reject') {
                
                $this->reject($request, $contractId);
                
                // set this approver as completed (flag -> 0) and approval_status -> rejected
                $approval->approval_status = function_exists('encryptStringx') ? @encryptStringx('rejected', 'approval_contracts.approval_status') : 'rejected';
                $approval->status = function_exists('encryptString') ? @encryptString('Rejected', $usernameKey) : 'Rejected';
                $approval->next_action_description = $comments;
                $approval->flag = 0;
                $approval->updated_by = $updatedByPayload;
                $approval->button_text = 'Rejected on ' . now()->format('Y-m-d H:i');
                $approval->save();
    
                // When rejected, per requirements contract returns to owner (Draft / Initial Draft)
                $contract->update([
                    'contract_status' => 'Draft',
                    'substatus' => 'Initial Draft'
                ]);
    
                // set all other approver flags to 1 (retain) or as needed - requirement said all others retain flag=1
                ApprovalContracts::where('contract_id', $contract->id)->where('id', '!=', $approval->id)->update(['flag' => 1]);
    
                // notify owner
                $this->notifyOwner(new Request(), $contract->id);
    
                DB::commit();
                return redirect(url('/contracts/show/contract-custom/'.$contractId).'?tab=approvals')->with('success', 'You have rejected the contract. It has been returned to the owner.');
            }
    
            // APPROVE path
            // mark this approver as completed
            $approval->approval_status = function_exists('encryptStringx') ? @encryptStringx('approved', 'approval_contracts.approval_status') : 'approved';
            $approval->status = function_exists('encryptString') ? @encryptString('Approved', $usernameKey) : 'Approved';
            $approval->next_action_description = $comments;
            $approval->flag = 0;
            // Record who updated this approval and show readable button text
            $approval->updated_by = $updatedByPayload;
            $approval->button_text = 'Approved on ' . now()->format('Y-m-d H:i');
            $approval->save();
            
            // Check if this is an Owner approval of a Draft contract
            // If so, generate the full approval workflow
            $isOwnerApproval = (strtolower($approval->approver_type_row ?? '') === 'owner');
            $isDraftContract = (strtolower($contract->contract_status ?? '') === 'draft');
            
            if ($isOwnerApproval && $isDraftContract) {
                // Owner approved the draft - now generate the full approval workflow
                $hasCustomTemplate = !empty($contract->contract_attachment_filename) && preg_match('/\.(doc|docx)$/i', $contract->contract_attachment_filename);
                $this->generateFullApprovalWorkflow($contract, $hasCustomTemplate);
                
                DB::commit();
                return redirect(url('/contracts/show/contract-custom/'.$contractId).'?tab=approvals')->with('success', 'Draft approved. Full approval workflow has been initiated.');
            }
    
            // find next approver (orderval greater than current and flag == 1 or not yet completed)
            // Determine advancement based on main approval type and row approval type
            $mainType = strtolower($approval->approval_type_main ?? 'sequential');
            $currentGroup = $approval->approver_type_row;
            $currentRowType = strtolower($approval->approval_type_row ?? 'sequential');

            // helper to check decrypted approval status
            $isApproved = function ($entry) {
                try {
                    return (function_exists('decryptString') ? @decryptString($entry->approval_status, 'approval_status') : $entry->approval_status) === 'approved';
                } catch (\Throwable $e) {
                    return ($entry->approval_status === 'approved');
                }
            };

            // default: no next activation yet
            $movedToNext = false;
    
// Advance workflow for main sequential or parallel behavior
            if (isset($mainType) && $mainType === 'sequential') {
                if ($currentRowType === 'sequential') {
                    // try to activate next member within same group
                    $nextInGroup = ApprovalContracts::where('contract_id', $contract->id)
                        ->where('approver_type_row', $currentGroup)
                        ->where('orderval', '>', $approval->orderval)
                        ->orderBy('orderval', 'asc')
                        ->first();

                    if ($nextInGroup) {
                        $nextInGroup->flag = 1;
                        $nextInGroup->save();

                        $contract->update(['contract_status' => 'Review', 'substatus' => 'In Review']);
                        // snapshot: moved to review (next approver activated)
                        $this->createContractSnapshot($contract, 'Workflow moved — next approver activated (In Review)');

                        // notify nextInGroup - decode username payload to extract email
                        $nextEmail = null;
                        try { $dec = function_exists('decryptString') ? @decryptString($nextInGroup->username, 'username') : $nextInGroup->username; } catch (\Throwable $e) { $dec = $nextInGroup->username; }
                        $tmp = @json_decode($dec, true);
                        $nextEmail = $tmp['email'] ?? $dec;
                        if ($nextEmail && filter_var($nextEmail, FILTER_VALIDATE_EMAIL)) {
                            $subject = "Contract #{$contract->id} awaiting your approval Next";
                            //$nextEmail = "jeevanantham@legalitysimplified.com";
                            \Log::error("notify approver {$nextEmail}: ");
                            $emailRecipients['to'] = $nextEmail;
                            if($nextInGroup->approver_type_row != 'Preapprover'){
                                $htmlTemplate = $this->getApprovalTemplate($contract->id, $nextInGroup->approver_type_row == 'Approver' ? true : false);
                                if($htmlTemplate){
                                    try {
                                        Mail::send([], [], function ($message) use ($emailRecipients, $subject, $htmlTemplate) {
                                            if (!empty($emailRecipients['to'])) $message->to($emailRecipients['to']);
                                            if (!empty($emailRecipients['cc'])) $message->cc($emailRecipients['cc']);
                                            if (!empty($emailRecipients['bcc'])) $message->bcc($emailRecipients['bcc']);
                                            $message->subject($subject);
                                            $message->html($htmlTemplate);
                                        });
                                    } catch (Exception $e) { \Log::error("Failed to notify approver {$nextEmail}: " . $e->getMessage()); }
                                }
                            } else {
                                $this->getApprovalTemplate($contract->id, false, true);
                            }
                        }

                        DB::commit();
                        return redirect(url('/contracts/show/contract-custom/'.$contractId).'?tab=approvals')->with('success', 'You have approved this step. The workflow moved to the next approver.');
                    }

                    // no next in group -> activate next group (if exists)
                    $groupMaxOrd = ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $currentGroup)->max('orderval');
                    $nextGroupEntry = ApprovalContracts::where('contract_id', $contract->id)->where('orderval', '>', $groupMaxOrd)->orderBy('orderval', 'asc')->first();
                    if ($nextGroupEntry) {
                        $nextGroup = $nextGroupEntry->approver_type_row;
                        $nextGroupEntries = ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $nextGroup)->orderBy('orderval', 'asc')->get();
                        $rowTypeNext = strtolower($nextGroupEntries->first()->approval_type_row ?? 'sequential');

                        if ($rowTypeNext === 'parallel') {
                            ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $nextGroup)->update(['flag' => 1]);
                            $notifyEntries = ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $nextGroup)->where('flag', 1)->get();
                        } else {
                            $firstNext = $nextGroupEntries->first();
                            if ($firstNext) {
                                ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $nextGroup)->update(['flag' => 0]);
                                ApprovalContracts::where('id', $firstNext->id)->update(['flag' => 1]);
                                $notifyEntries = collect([$firstNext]);
                            } else {
                                $notifyEntries = collect();
                            }
                        }

                        $contract->update(['contract_status' => 'Review', 'substatus' => 'In Review']);
                        // snapshot: moved to review (next group activated)
                        $this->createContractSnapshot($contract, 'Workflow moved — next group activated (In Review)');

                        // notify all newly activated approvers in next group
                        foreach ($notifyEntries as $ne) {
                            $to = null;
                            try { $to = function_exists('decryptString') ? @decryptString($ne->username, 'username') : $ne->username; } catch (\Throwable $e) { $to = $ne->username; }
                            if (!$to) continue;
                            if (strtolower($ne->approver_type_row ?? '') === 'owner') continue;
                            $subject = "Contract #{$contract->id} awaiting your approval noti";
                            //$to = "jeevanantham@legalitysimplified.com";
                        $tmp = @json_decode($to, true);
                        $to = $tmp['email'] ?? $to;
                            \Log::error("Successfully Notified {$to}: ");
                            $emailRecipients['to'] = $to;
                            if($ne->approver_type_row != 'Preapprover'){                            
                                $htmlTemplate = $this->getApprovalTemplate($contract->id, $ne->approver_type_row == 'Approver' ? true : false);
                                if ($htmlTemplate) {
                                    try {
                                        Mail::send([], [], function ($message) use ($emailRecipients, $subject, $htmlTemplate) {
                                            if (!empty($emailRecipients['to'])) $message->to($emailRecipients['to']);
                                            if (!empty($emailRecipients['cc'])) $message->cc($emailRecipients['cc']);
                                            if (!empty($emailRecipients['bcc'])) $message->bcc($emailRecipients['bcc']);
                                            $message->subject($subject);
                                            $message->html($htmlTemplate);
                                        });
                                    } catch (Exception $e) { \Log::error("Failed to notify approver {$to}: " . $e->getMessage()); }
                                }
                            } else {
                                    $this->getApprovalTemplate($contract->id, false, true);
                            }                            
                        }

                        DB::commit();
                        return redirect(url('/contracts/show/contract-custom/'.$contractId).'?tab=approvals')->with('success', 'You have approved this step. The workflow moved to the next group.');
                    }

                    // else fall through to final approval handling
                } else {
                    // current group is parallel - wait for other members
                    $groupEntries = ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $currentGroup)->get();
                    $allApproved = true;
                    foreach ($groupEntries as $g) {
                        if (! $isApproved($g)) { $allApproved = false; break; }
                    }

                    if (! $allApproved) {
                        $contract->update(['contract_status' => 'Review', 'substatus' => 'In Review']);
                        DB::commit();
                        return redirect(url('/contracts/show/contract-custom/'.$contractId).'?tab=approvals')->with('success', 'You have approved this step. Waiting for other group members to approve.');
                    }

                    // group complete -> activate next group (same as above)
                    $groupMaxOrd = ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $currentGroup)->max('orderval');
                    $nextGroupEntry = ApprovalContracts::where('contract_id', $contract->id)->where('orderval', '>', $groupMaxOrd)->orderBy('orderval', 'asc')->first();
                    if ($nextGroupEntry) {
                        $nextGroup = $nextGroupEntry->approver_type_row;
                        $nextGroupEntries = ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $nextGroup)->orderBy('orderval', 'asc')->get();
                        $rowTypeNext = strtolower($nextGroupEntries->first()->approval_type_row ?? 'sequential');

                        if ($rowTypeNext === 'parallel') {
                            ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $nextGroup)->update(['flag' => 1]);
                            $notifyEntries = ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $nextGroup)->where('flag', 1)->get();
                        } else {
                            $firstNext = $nextGroupEntries->first();
                            if ($firstNext) {
                                ApprovalContracts::where('contract_id', $contract->id)->where('approver_type_row', $nextGroup)->update(['flag' => 0]);
                                ApprovalContracts::where('id', $firstNext->id)->update(['flag' => 1]);
                                $notifyEntries = collect([$firstNext]);
                            } else {
                                $notifyEntries = collect();
                            }
                        }

                        $contract->update(['contract_status' => 'Review', 'substatus' => 'In Review']);

                        foreach ($notifyEntries as $ne) {
                            $to = null;
                            try { $dec = function_exists('decryptString') ? @decryptString($ne->username, 'username') : $ne->username; } catch (\Throwable $e) { $dec = $ne->username; }
                            $tmp = @json_decode($dec, true);
                            $to = $tmp['email'] ?? $dec;
                            if (!$to) continue;
                            if (strtolower($ne->approver_type_row ?? '') === 'owner') continue;
                            $subject = "Contract #{$contract->id} awaiting your approval owner";
                            $emailRecipients['to'] = $to;
                            $htmlTemplate = $this->getApprovalTemplate($contract->id, $ne->approver_type_row == 'Approver' ? true : false);
                            if ($htmlTemplate) {
                                try {
                                    Mail::send([], [], function ($message) use ($emailRecipients, $subject, $htmlTemplate) {
                                        if (!empty($emailRecipients['to'])) $message->to($emailRecipients['to']);
                                        if (!empty($emailRecipients['cc'])) $message->cc($emailRecipients['cc']);
                                        if (!empty($emailRecipients['bcc'])) $message->bcc($emailRecipients['bcc']);
                                        $message->subject($subject);
                                        $message->html($htmlTemplate);
                                    });
                                } catch (Exception $e) { \Log::error("Failed to notify approver {$to}: " . $e->getMessage()); }
                            }
                        }

                        DB::commit();
                        return redirect(url('/contracts/show/contract-custom/'.$contractId).'?tab=approvals')->with('success', 'You have approved this step. The workflow moved to the next group.');
                    }

                    // else fall through to final approval handling
                }
            }
    
            // No next approver -> final approval step
            // set contract to Approval / Pending Approval and trigger signatory flow
            $contract->update([
                'contract_status' => 'Signing',
                'substatus' => 'Approved'
            ]);
            // snapshot for final approval
            $this->createContractSnapshot($contract, 'Final approval — sent for signing');

            // Notify contract creator that agreement is approved and ready for signature
            try {
                $ownerEmail = null;
                if (!empty($contract->created_by)) {
                    if (is_numeric($contract->created_by)) {
                        $owner = AddUsers::select('id',  decrypt_data('Email', 'AddUsers'))->find($contract->created_by);
                        $ownerEmail = $owner->Email ?? null;
                    } else {
                        $cb = @json_decode($contract->created_by, true);
                        $ownerEmail = $cb['email'] ?? $contract->created_by;
                    }
                }

                if (!empty($ownerEmail)) {
                    $subjectOwner = "Contract #{$contract->id} approved for signing — please upload signed agreement";
                    
                    $htmlTemplate = $this->getApprovalTemplate($contract->id);
                    \Log::error("Email Owner " . $ownerEmail);
                    //$ownerEmail = "jeevanantham@legalitysimplified.com";
                    //$body = "Your contract #{$contract->id} has completed approvals and is approved for signing. Please upload the signed agreement via the portal; once uploaded it will be attached to the contract record and marked executed.";
                    Mail::send([], [], function ($message) use ($ownerEmail, $subjectOwner, $htmlTemplate) {
                        $message->to($ownerEmail);
                        $message->subject($subjectOwner);
                        $message->html($htmlTemplate);  
                    });
                }
            } catch (Exception $e) {
                \Log::error("Failed to notify contract creator for contract {$contract->id}: " . $e->getMessage());
            }

            // Attempt to build final PDF document with appended annexures (locations, discounts, health packages)
            try {
                $fileStorageController = fileStorageTypeController();

                // Get base HTML for contract (use template renderer)
                $htmlDoc = $this->updateOrCreateTemplate('', $contract, true);

                // Build Annexures HTML
                $annexHtml = $this->getAnnexures($contract);

                // Render full HTML and generate PDF (always include footer on every page)
                if ($htmlDoc) {
                    // include annexHtml when available
                    $fullHtml = $htmlDoc . ($annexHtml ?? '');

                    // default verifier info (fallback to current user or app name)
                    $approverName = Helpers::userInfo()->FirstName ?? config('app.name', 'Verified');
                    $approvedAt = date('d M Y H:i');

                    try {
                        // Attempt to find most recent approved approver (approver_row_type == 'approver')
                        $approvals = \App\Models\ApprovalContracts::where('contract_id', $contract->id)
                            ->where('approver_type_row', 'approver')
                            ->get();

                        $lastApproved = null;
                        foreach ($approvals as $ap) {
                            try {
                                $status = function_exists('decryptString') && !empty($ap->approval_status) ? @decryptString($ap->approval_status, 'approval_status') : $ap->approval_status;
                            } catch (\Throwable $e) {
                                $status = $ap->approval_status;
                            }
                            if (is_string($status) && strtolower($status) === 'approved') {
                                if (is_null($lastApproved)) $lastApproved = $ap;
                                else {
                                    // prefer most recent updated_at
                                    if (!empty($ap->updated_at) && !empty($lastApproved->updated_at)) {
                                        if ($ap->updated_at > $lastApproved->updated_at) $lastApproved = $ap;
                                    } else if (!empty($ap->orderval) && !empty($lastApproved->orderval) && $ap->orderval > $lastApproved->orderval) {
                                        $lastApproved = $ap;
                                    }
                                }
                            }
                        }

                        if ($lastApproved) {
                            try {
                                // Decrypt username payload if possible (may be JSON payload {"email":"...","name":"..."})
                                $rawApprover = function_exists('decryptString') && !empty($lastApproved->username) ? @decryptString($lastApproved->username, 'username') : $lastApproved->username;
                            } catch (\Throwable $e) { $rawApprover = $lastApproved->username; }

                            // Prefer the "name" field if username payload is JSON, otherwise use email or raw string
                            $decoded = @json_decode($rawApprover, true);
                            if (is_array($decoded)) {
                                $approverName = $decoded['name'] ?? $decoded['email'] ?? $approverName;
                            } else {
                                $approverName = $rawApprover ?: $approverName;
                            }

                            // format date+time
                            $approvedAt = !empty($lastApproved->updated_at) ? $lastApproved->updated_at->format('d M Y H:i') : $approvedAt;
                        }
                    } catch (\Throwable $e) {
                        // proceed with fallback verifier info
                        \Log::error('Failed to compute last approved approver for footer: ' . $e->getMessage());
                    }

                    // Build footer HTML and CSS (position:fixed repeats on each page in DomPDF)
                    $footerHtml = "<div class=\"contract-footer\"><div class=\"verified-by\">Verified by: " . e($approverName) . " on " . e($approvedAt) . "</div></div>";
                    $style = "<style>body{margin-bottom:70px;} .contract-footer{position:fixed;bottom:10px;left:0;right:0;text-align:center;font-size:10px;color:#666;} .contract-footer .verified-by{display:inline-block;padding-top:6px;border-top:1px solid #eaeaea;}</style>";

                    $fullHtml = $style . $fullHtml . $footerHtml;

                    $pdf = \PDF::loadHTML($fullHtml)->setPaper('a4');
                    
                    $generatedDocumentName = 'final_contract_' . ($contract->contract_unique_id ?? $contract->id) . '_' . time() . '.pdf';
                    
                    $storagePath = '/storage/app/';
        
                    $generateDocPath = $fileStorageController->get_file_path($contract->id);                    

                    if (fileStorageType() == "Local") {
                        $finalPath = base_path() . $storagePath . $generateDocPath . '/' . $generatedDocumentName;
                        $pdf->save($finalPath);
                        $finalFilePathName = $generateDocPath . '/' . $generatedDocumentName;
                    } else {
                        $finalPath = base_path() . '/storage/app/contracts/tempDocs/' . $generatedDocumentName;
                        $pdf->save($finalPath);
                        $finalFilePathName = $fileStorageController->storeContent($finalPath, $generateDocPath, $generatedDocumentName);
                        unlink($finalPath);
                    }

                    // Update contract attachment to point to generated PDF
                    Contract::where('id', $contract->id)->update([
                        'contract_attachment' => $finalFilePathName,
                        'contract_attachment_filename' => $generatedDocumentName
                    ]);
                }

            } catch (Exception $e) {
                //echo 'Failed to create final PDF for contract ' . ($contract->id ?? '') . ': ' . $e->getMessage();
                \Log::error('Failed to create final PDF for contract ' . ($contract->id ?? '') . ': ' . $e->getMessage());
            }

            DB::commit();
            return redirect(url('/contracts/show/contract-custom/'.$contractId).'?tab=approvals')->with('success', 'You have approved the final step. Contract has been sent to signatory if configured.');
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('approvalRespond error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to process approval response: ' . $e->getMessage());
        }
    }
    
    
    /**
     * approverUpdate
     *
     * Endpoint used by the first active approver to edit the whole contract form.
     * URL: POST /contracts/{contract}/approver-edit
     *
     * Behavior:
     * - Verifies that the current authenticated user matches the first approval entry and that flag==1.
     * - Validates inputs (same rules as update).
     * - Saves contract base fields and nested arrays (discounts, health_check_rows, locations).
     * - Returns JSON (AJAX) or redirects back with flash when non-AJAX.
     */
    public function approverUpdate(Request $request, $id)
    {
        // Check permission: user must be authenticated
        if (!auth()->check()) {
            return $request->ajax()
                ? response()->json(['success' => false, 'message' => 'Unauthorized'], 401)
                : redirect()->back()->with('error', 'Unauthorized');
        }

        // Fetch first approval entry
        $first = ApprovalContracts::where('contract_id', $id)->orderBy('orderval', 'asc')->first();

        // Prevent approver edits if contract is in a final/published state
        $contractCheck = Contract::find($id);
        $finalStates = ['active','expired','completed','terminated'];
        if ($contractCheck && in_array(strtolower($contractCheck->contract_status ?? ''), $finalStates, true)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Contract cannot be modified in its current state ('.$contractCheck->contract_status.').'], 403);
            }
            return redirect()->back()->with('error', 'Contract cannot be modified in its current state ('.$contractCheck->contract_status.').');
        }
        if (! $first) {
            return $request->ajax()
                ? response()->json(['success' => false, 'message' => 'No approval workflow configured'], 422)
                : redirect()->back()->with('error', 'No approval workflow configured');
        }

        // Determine first approver identifier (decrypt if needed)
        $firstIdentifier = $first->username;
        if (function_exists('decryptString') && !empty($first->username)) {
            try { $firstIdentifier = decryptString($first->username, 'username'); } catch (\Throwable $e) { /* ignore */ }
        }

        $current = strtolower(auth()->user()->email ?? auth()->user()->name ?? '');

        $isMatch = false;
        if (!empty($firstIdentifier)) {
            $fi = strtolower($firstIdentifier);
            if (strpos($fi, '@') !== false) {
                $isMatch = ($current === $fi);
            } else {
                $isMatch = ($current === $fi);
            }
        }

        // Ensure this is active first approver
        if (! $isMatch || (int)$first->flag !== 1) {
            return $request->ajax()
                ? response()->json(['success' => false, 'message' => 'Not allowed to edit'], 403)
                : redirect()->back()->with('error', 'You are not authorized to perform this edit.');
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'agreement_name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'editor_text' => 'nullable|string',
            'customer_id' => 'nullable|integer',
            'discounts' => 'nullable|array',
            'discounts.*.category' => 'nullable|string|max:255',
            'discounts.*.subcategory' => 'nullable|string|max:255',
            'discounts.*.discount_percent' => 'nullable|numeric|min:0',
            'health_check_rows' => 'nullable|array',
            'health_check_rows.*.row_name' => 'required_with:health_check_rows|string|max:255',
            'health_check_rows.*.package_price' => 'nullable|numeric|min:0',
            'health_check_rows.*.selected_test_ids' => 'nullable|array',
            'health_check_rows.*.selected_test_ids.*' => 'integer',
            'health_check_rows.*.selected_consultation_ids' => 'nullable|array',
            'health_check_rows.*.selected_consultation_ids.*' => 'integer',
            'health_check_rows.*.prices' => 'nullable',
            'locations' => 'nullable|array',
            'locations.*' => 'integer'         
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $contract = Contract::findOrFail($id);

            // Update contract_name (encrypt if helper exists)
            $updateData = [];
            if ($request->filled('agreement_name')) {
                $newName = $request->input('agreement_name');
                if (function_exists('encryptString')) {
                    try {
                        $updateData['contract_name'] = encryptString($newName, 'contract_name');
                    } catch (\Throwable $e) {
                        $updateData['contract_name'] = $newName;
                    }
                } else {
                    $updateData['contract_name'] = $newName;
                }
            }

            if ($request->filled('start_date')) $updateData['signing_date'] = $request->input('start_date');
            if ($request->filled('end_date')) $updateData['contract_end_date'] = $request->input('end_date');
            if ($request->filled('editor_text')) $updateData['contract_description'] = $request->input('editor_text');

            if (!empty($updateData)) {
                $contract->update($updateData);
                // snapshot after approver edit
                $this->createContractSnapshot($contract, 'Approver updated contract via approver edit');
            }

            // Update customer party
            if ($request->filled('customer_id')) {
                ContractPartyData::where('custom_field_group_id', $id)
                    ->where('contract_party_type', 'External')
                    ->update(['contract_party_exe_id' => $request->input('customer_id')]);
            }

            // Discounts: delete + recreate
            if ($request->has('discounts')) {
                ContractDiscount::where('contract_id', $id)->delete();
                $discounts = $request->input('discounts', []);
                $discountRecords = [];
                foreach ($discounts as $d) {
                    $room_charges = [];
                    if (isset($d['room_charges'])) {
                        if (is_array($d['room_charges'])) $room_charges = $d['room_charges'];
                        else $room_charges = array_filter(array_map('trim', explode(',', $d['room_charges'])));
                    }
                    $discountRecords[] = [
                        'contract_id' => $id,
                        'category' => $d['category'] ?? null,
                        'subcategory' => $d['subcategory'] ?? null,
                        'discount_percent' => $d['discount_percent'] ?? 0,
                        'room_charges' => json_encode($room_charges),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                if (!empty($discountRecords)) ContractDiscount::insert($discountRecords);
            }

            // Health checks: delete + recreate
            if ($request->has('health_check_rows')) {
                ContractHealthCheck::where('contract_id', $id)->delete();
                $healthChecks = $request->input('health_check_rows', []);
                $hcRecords = [];
                foreach ($healthChecks as $hc) {
                    $selected_tests = [];
                    if (!empty($hc['selected_test_ids'])) {
                        if (is_string($hc['selected_test_ids'])) {
                            $selected_tests = array_filter(array_map('intval', array_map('trim', explode(',', $hc['selected_test_ids']))));
                        } else {
                            $selected_tests = array_map('intval', $hc['selected_test_ids']);
                        }
                    }
                    $selected_consults = [];
                    if (!empty($hc['selected_consultation_ids'])) {
                        if (is_string($hc['selected_consultation_ids'])) {
                            $selected_consults = array_filter(array_map('intval', array_map('trim', explode(',', $hc['selected_consultation_ids']))));
                        } else {
                            $selected_consults = array_map('intval', $hc['selected_consultation_ids']);
                        }
                    }
                    $prices = [];
                    if (!empty($hc['prices'])) {
                        if (is_string($hc['prices'])) {
                            $maybe = @json_decode($hc['prices'], true);
                            if (is_array($maybe)) $prices = $maybe;
                        } elseif (is_array($hc['prices'])) {
                            $prices = $hc['prices'];
                        }
                    }

                    $hcRecords[] = [
                        'contract_id' => $id,
                        'row_name' => $hc['row_name'] ?? null,
                        'selected_test_ids' => json_encode($selected_tests),
                        'package_price' => $hc['package_price'] ?? 0,
                        'selected_consultation_ids' => json_encode($selected_consults),
                        'consultation_prices' => json_encode($prices),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                if (!empty($hcRecords)) ContractHealthCheck::insert($hcRecords);
            }

            // Locations: delete + recreate
            if ($request->has('locations')) {
                ContractLocation::where('contract_id', $id)->delete();
                $locations = $request->input('locations', []);
                $locRecords = [];
                foreach ($locations as $loc) {
                    $locRecords[] = ['contract_id' => $id, 'location_id' => intval($loc), 'created_at' => now(), 'updated_at' => now()];
                }
                if (!empty($locRecords)) ContractLocation::insert($locRecords);
            }

            DB::commit();
            
            // Re-evaluate approval flow after approver modification
            // If approval flow no longer matches, add missing approvers
            try {
                $contractData = [
                    'discounts' => $request->input('discounts', []),
                    'health_check_rows' => $request->input('health_check_rows', []),
                    'locations' => $request->input('locations', []),
                    'scope' => $contract->custom_fields_data,
                    'contract_type' => $contract->contract_type,
                ];
                
                $reEvalResult = $this->reEvaluateAndAddMissingApprovers($contract, $contractData);
                
                $successMessage = 'Contract updated by approver';
                if ($reEvalResult['changed'] && !empty($reEvalResult['added'])) {
                    $successMessage .= '. New approvers added: ' . implode(', ', $reEvalResult['added']);
                }
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => true, 
                        'message' => $successMessage,
                        'approvers_changed' => $reEvalResult['changed'],
                        'added_approvers' => $reEvalResult['added']
                    ]);
                }
                
                return redirect(url('/contracts/'.$id.'?tab=approvals'))->with('success', $successMessage);
                
            } catch (\Throwable $e) {
                \Log::error('Failed to re-evaluate approvers after approver update: ' . $e->getMessage());
                
                if ($request->ajax()) {
                    return response()->json(['success' => true, 'message' => 'Contract updated by approver']);
                }
                return redirect(url('/contracts/'.$id.'?tab=approvals'))->with('success', 'Contract updated successfully.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('approverUpdate failed: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to update: '.$e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Failed to update contract: '.$e->getMessage());
        }
    }
    
    
    /**
     * Evaluate discount percentages from incoming contract json against rule_builder_data ranges
     * Returns approval_type, approval_status and approval_required_users based on violations
     */
public function evaluateDiscountApproval($request, $approval_mode = 'approval_required_users', $approvalTypeGlobal = 0)
{
    try {
        $discounts = data_get($request, 'discounts', []);
        $contractType = data_get($request, 'contract_type', data_get($request, 'contract_type_id', admin_setting('custom_contracts_type_id')));
        $scope = strtolower(data_get($request, 'scope', ''));
        $scope_pay_type = strtolower(data_get($request, 'payment-type', ''));

        $limits = FinancialLimit::where('status', 1)
            ->where('approval_flow_type', 'custom')
            ->whereNotNull('rule_builder_data')
            ->get();

        // Helper to build the return structure from a FinancialLimit row
        $buildApproval = function ($limit) use ($approval_mode, $approvalTypeGlobal, $request, $scope) {
            $types = json_decode($limit->approval_type, true) ?: [];
            $approvalType = $types[$approvalTypeGlobal] ?? reset($types) ?? 'sequential';
            
            $approvalTypeMap = [
                'approval_required_users' => '',
                'approval_required_users_edit' => 'edit',
                'approval_required_users_renewed' => 'renew',
                'approval_required_users_addendum' => 'addendum',
                'approval_required_users_legacy' => 'legacy',
                'approval_required_users_legacy_edit' => 'legacy_edit',
                'approval_required_users_terminate' => 'terminate',
            ];
            
            $dbApprovalType = $approvalTypeMap[$approval_mode] ?? '';
            
            $users = null;
            $groupSet = \App\Models\ApprovalGroupSet::where('financial_limit_id', $limit->id)
                ->where('approval_type', $dbApprovalType)
                ->first();
            
            if ($groupSet) {
                $users = [];
                foreach (['review', 'negotiation', 'finalization', 'approval', 'signatory'] as $parentType) {
                    $groups = $groupSet->groups()->where('parent_type', $parentType)->orderBy('order_index')->get();
                    foreach ($groups as $group) {
                        $approvers = [];
                        foreach ($group->approvers as $approver) {
                            $approvers[] = [
                                'id' => $approver->approver_id,
                                'type' => $approver->approver_type,
                                'name' => $approver->approver_name,
                                'email' => $approver->approver_email,
                            ];
                        }
                        
                        $users[] = [
                            'role' => $group->role,
                            'approval_type' => $group->approval_type,
                            'auto_next_enabled' => $group->auto_next_enabled,
                            'dynamic_approver_enabled' => $group->dynamic_approver_enabled,
                            'approvers' => $approvers,
                        ];
                    }
                }
            }
            
            if ($users === null || empty($users)) {
                $users = json_decode($limit->{$approval_mode} ?? '[]', true) ?: [];
            }
            
            // Parse corporate verifier/approver/signatory from admin_setting
            // Expected format: "{'name': 'Corporate Verifier' , 'email' : 'corpverifier@example.com'}"
            $corpVerifierName = 'Corporate Verifier';
            $corpVerifierEmail = 'corpverifier1@legalitysimplified.com';
            $corpApproverName = 'Corporate Approver';
            $corpApproverEmail = 'dummyapprover@legalitysimplified.com';
            $corpSignatoryName = 'Corporate Signatory';
            $corpSignatoryEmail = 'dummysignatory@legalitysimplified.com';
            
            // Load corporate verifier from admin_setting
            $corpVerifierSetting = admin_setting('corp_verifier');
            if (!empty($corpVerifierSetting)) {
                // Convert single quotes to double quotes for valid JSON
                $corpVerifierJson = str_replace("'", '"', $corpVerifierSetting);
                $corpVerifierData = @json_decode($corpVerifierJson, true);
                if (is_array($corpVerifierData)) {
                    $corpVerifierName = $corpVerifierData['name'] ?? $corpVerifierName;
                    $corpVerifierEmail = $corpVerifierData['email'] ?? $corpVerifierEmail;
                }
            }
            
            // Load corporate approver from admin_setting (fallback to default_approver if not found)
            $corpApproverSetting = admin_setting('corp_approver');
            if (!empty($corpApproverSetting)) {
                $corpApproverJson = str_replace("'", '"', $corpApproverSetting);
                $corpApproverData = @json_decode($corpApproverJson, true);
                if (is_array($corpApproverData)) {
                    $corpApproverName = $corpApproverData['name'] ?? $corpApproverName;
                    $corpApproverEmail = $corpApproverData['email'] ?? $corpApproverEmail;
                }
            } else {
                // Fallback to default_approver if corp_approver not found
                $defaultApproverSetting = admin_setting('default_approver');
                if (!empty($defaultApproverSetting)) {
                    $defaultApproverJson = str_replace("'", '"', $defaultApproverSetting);
                    $defaultApproverData = @json_decode($defaultApproverJson, true);
                    if (is_array($defaultApproverData)) {
                        $corpApproverName = $defaultApproverData['name'] ?? $corpApproverName;
                        $corpApproverEmail = $defaultApproverData['email'] ?? $corpApproverEmail;
                    }
                }
            }
            
            // Load corporate signatory from admin_setting (fallback to default_signatory if not found)
            $corpSignatorySetting = admin_setting('corp_signatory');
            if (!empty($corpSignatorySetting)) {
                $corpSignatoryJson = str_replace("'", '"', $corpSignatorySetting);
                $corpSignatoryData = @json_decode($corpSignatoryJson, true);
                if (is_array($corpSignatoryData)) {
                    $corpSignatoryName = $corpSignatoryData['name'] ?? $corpSignatoryName;
                    $corpSignatoryEmail = $corpSignatoryData['email'] ?? $corpSignatoryEmail;
                }
            } else {
                // Fallback to default_signatory if corp_signatory not found
                $defaultSignatorySetting = admin_setting('default_signatory');
                if (!empty($defaultSignatorySetting)) {
                    $defaultSignatoryJson = str_replace("'", '"', $defaultSignatorySetting);
                    $defaultSignatoryData = @json_decode($defaultSignatoryJson, true);
                    if (is_array($defaultSignatoryData)) {
                        $corpSignatoryName = $defaultSignatoryData['name'] ?? $corpSignatoryName;
                        $corpSignatoryEmail = $defaultSignatoryData['email'] ?? $corpSignatoryEmail;
                    }
                }
            }
            
            $locations = [];
            
            //$cities = Branch::pluck('City', 'id');           
            
            //print_r($contract->contractLocations->location());
            
            // foreach(data_get($request,'locations', []) as $locId){
            //     $locations[] = ['id' => $locId, 'city' => $cities[$locId], 'Branch' => $cities[$locId]];
            // }
            
            //$result = $this->analyze($locations);
            
            //$regionCount = $result['overall']['region']['count'] ?? 0;

            $locations = data_get($request,'locations', []);           
            
            $result = $this->getRegionAndOverallCounts($locations);
            
            $regionCount =  $result['overall']->total_regions ?? 0;
            $locationCount =  $result['overall']->total_locations ?? 0;
            
            // Fetch regional approvers from the first location in LocationMaster
            $regionalApprovers = null;
            if (!empty($locations) && is_array($locations)) {
                $firstLocationId = is_array($locations[0]) ? ($locations[0]['id'] ?? $locations[0]) : $locations[0];
                $regionalApprovers = \App\Models\LocationMaster::find($firstLocationId);
            }
            
            if($scope != 'international'){
    
                if ($regionCount > 1) {
                
                    $newUsers = [];
                    $verifierAdded = false;
                    $approverAdded = false;
                    $signatoryAdded = false;
                
                    
                    foreach ($users as $user) {
                
                            // Handle Verifier (keep only one)
                            if ($user['role'] === 'Verifier' && !$verifierAdded) {
                                $user['approvers'][0]['type'] = 'name';
                                $user['approvers'][0]['name'] = $corpVerifierName;
                                $user['approvers'][0]['email'] = $corpVerifierEmail;
                                $newUsers[] = $user;
                                $verifierAdded = true;
                            }
                            
                            // Handle Approver (keep only one)
                            elseif ($user['role'] === 'Approver' && !$approverAdded) {
                                $user['approvers'][0]['type'] = 'name';
                                $user['approvers'][0]['name'] = $corpApproverName;                        
                                $user['approvers'][0]['email'] = $corpApproverEmail;
                                $newUsers[] = $user;
                                $approverAdded = true;
                            }
        
                            // Handle Signatory (keep only one)
                            elseif ($user['role'] === 'Signatory' && !$signatoryAdded) {
                                $user['approvers'][0]['type'] = 'name';
                                $user['approvers'][0]['name'] = $corpSignatoryName;                        
                                $user['approvers'][0]['email'] = $corpSignatoryEmail;
                                $newUsers[] = $user;
                                $signatoryAdded = true;
                            }
                
                        // Ignore extra verifiers / approvers automatically
                    }
                    $users = $newUsers;
                    
                }
    
                if ($regionCount == 1 &&  $locationCount > 1) {
                
                    $newUsers = [];
                    $verifierAdded = false;
                    $approverAdded = false;
                    $signatoryAdded = false;
                
                    foreach ($users as $user) {
                        
                        
                        // Handle Verifier (keep only one)
                        // if ($user['role'] === 'Verifier' && !$verifierAdded) {
                        //     // Use regional verifier from LocationMaster if available
                        //     if ($regionalApprovers && $regionalApprovers->regional_verifier_email) {
                        //         $user['approvers'][0]['type'] = 'name';
                        //         $user['approvers'][0]['name'] = $regionalApprovers->regional_verifier_name ?: 'Regional Verifier';
                        //         $user['approvers'][0]['email'] = $regionalApprovers->regional_verifier_email;
                        //     }
                        //     $newUsers[] = $user;
                        //     $verifierAdded = true;
                        // }                    
                
                        // Handle Approver (keep only one)
                        if ($user['role'] === 'Approver' && !$approverAdded) {
                            $user['approvers'][0]['type'] = 'name';
                            // Use regional approver from LocationMaster if available
                            if ($regionalApprovers && $regionalApprovers->regional_approver_email) {
                                $user['approvers'][0]['name'] = $regionalApprovers->regional_approver_name ?: 'Regional Approver';
                                $user['approvers'][0]['email'] = $regionalApprovers->regional_approver_email;
                            } else {
                                $user['approvers'][0]['name'] = 'Regional Approver';                        
                                $user['approvers'][0]['email'] = 'regionalapprover2@legalitysimplified.com';
                            }
                            $newUsers[] = $user;
                            $approverAdded = true;
                        }
    
                        // Handle Signatory (keep only one)
                        elseif ($user['role'] === 'Signatory' && !$signatoryAdded) {
                            $user['approvers'][0]['type'] = 'name';
                            // Use regional signatory from LocationMaster if available
                            if ($regionalApprovers && $regionalApprovers->regional_signatory_email) {
                                $user['approvers'][0]['name'] = $regionalApprovers->regional_signatory_name ?: 'Regional Signatory';
                                $user['approvers'][0]['email'] = $regionalApprovers->regional_signatory_email;
                            } else {
                                $user['approvers'][0]['name'] = 'Regional Signatory';                        
                                $user['approvers'][0]['email'] = 'regionalsignatory2@legalitysimplified.com';
                            }
                            $newUsers[] = $user;
                            $signatoryAdded = true;
                        }
                
                        // Ignore extra verifiers / approvers automatically
                    }
                    
                    $users = $newUsers;
                }
            }

            return [
                'status' => true,
                'id' => $limit->id,
                'approval_status' => 'required',
                'approval_type' => $approvalType,
                'approval_required_users' => array_values($users),
            ];
        };

        // Find matching groups for the requested contract type
        $matchingGroups = [];
        foreach ($limits as $limit) {
            $rb = json_decode($limit->rule_builder_data, true);
            if (empty($rb['gcondition']) || !is_array($rb['gcondition'])) {
                continue;
            }

            foreach ($rb['gcondition'] as $group) {
                $groupContractTypes = $group['contractType'] ?? $group['contract_type'] ?? null;
                if (is_null($groupContractTypes)) {
                    continue;
                }

                $groupContractTypes = is_array($groupContractTypes) ? $groupContractTypes : [$groupContractTypes];
                $normalized = array_map(function ($v) {
                    return is_numeric($v) ? (int)$v : (string)$v;
                }, $groupContractTypes);

                $isAny = in_array(0, $normalized, true) || in_array('0', $groupContractTypes, true);
                $contractMatch = $isAny
                    || in_array((int)$contractType, $normalized, true)
                    || in_array((string)$contractType, $groupContractTypes, true);
                    
                    
                // If contract type matches, also check payment type when the group specifies one (and it's not "Not Applicable").
                // Accept legacy key `payment_type` as well. If the group has a non-empty, specific payment type, require the request to match it (case-insensitive).
                $groupPaymentType = $group['paymentType'] ?? ($group['payment_type'] ?? null);
                $reqPaymentType = data_get($request, 'paymentType', data_get($request, 'payment_type', null));
                
                
                
                //echo ;

                $paymentMatch = true;
                //echo $limit->id."--- form ".$reqPaymentType."-- doa".$groupPaymentType;
                if (!is_null($groupPaymentType) && trim($groupPaymentType) !== '' && strtolower(trim($groupPaymentType)) !== 'not applicable') {
                    if (is_null($reqPaymentType) || strtolower(trim($groupPaymentType)) !== strtolower(trim((string)$reqPaymentType))) {
                        $paymentMatch = false;
                    }
                }

                if ($contractMatch && $paymentMatch) {
                    $matchingGroups[] = ['limit' => $limit, 'group' => $group];
                }
            }
        }
        
        
        // If no groups matched the contract type, return default (id = 1 or first limit)
        if (empty($matchingGroups)) {
            $defaultLimit = FinancialLimit::find(1) ?: $limits->first();
            if ($defaultLimit) {
                return $buildApproval($defaultLimit);
            }
        }

        // Scope handling: International scope bypasses discount checks and uses any matching group with customerType = International
        if ($scope === 'international') {
            foreach ($matchingGroups as $m) {
                $groupScope = strtolower($m['group']['customerType'] ?? '');
                if ($groupScope === 'international') {
                    return $buildApproval($m['limit']);
                }
            }
            // No matching international group -> fallthrough to default below
        } else {
            // Evaluate discount rules against matching groups
            $matchedLimits = [];
            foreach ($discounts as $disc) {
                $cat = $disc['category'] ?? null;
                $sub = $disc['subcategory'] ?? null;
                $perc = isset($disc['discount_percent']) ? floatval($disc['discount_percent']) : null;

                foreach ($matchingGroups as $m) {
                    $limit = $m['limit'];
                    $group = $m['group'];

                    $conditions = $group['discountRules']['condition'] ?? null;
                    if (empty($conditions) || !is_array($conditions)) {
                        continue;
                    }

                    foreach ($conditions as $cond) {
                        $opt = $cond['discountoption'] ?? null;
                        $subcat = $cond['subcategory'] ?? null;

                        if ($opt !== $cat || $subcat !== $sub) {
                            continue;
                        }

                        $min = ($cond['mindiscount'] !== null && $cond['mindiscount'] !== '') ? floatval($cond['mindiscount']) : null;
                        $max = ($cond['maxdiscount'] !== null && $cond['maxdiscount'] !== '') ? floatval($cond['maxdiscount']) : null;

                        $inRange = ($min === null || $perc >= $min) && ($max === null || $perc <= $max);

                        if ($inRange) {
                            // Collect matching limit (dedupe by id)
                            if (!empty($limit) && isset($limit->id)) {
                                $matchedLimits[$limit->id] = $limit;
                            }
                        }
                    }
                }
            }

            if (!empty($matchedLimits)) {
                // Choose the most restrictive approval: prefer the limit yielding the highest total approvers
                $bestLimit = null;
                $bestScore = -1;

                foreach ($matchedLimits as $ml) {
                    $approval = $buildApproval($ml);
                    $users = $approval['approval_required_users'] ?? [];
                    $approverCount = 0;
                    foreach ($users as $user) {
                        $approverCount += is_array($user['approvers'] ?? null) ? count($user['approvers']) : 0;
                    }

                    if ($approverCount > $bestScore || ($approverCount === $bestScore && ($bestLimit === null || $ml->id > $bestLimit->id))) {
                        $bestLimit = $ml;
                        $bestScore = $approverCount;
                    }
                }

                return $buildApproval($bestLimit);
            }
        }

        // No matching rule triggered approval — return default
        $defaultLimit = FinancialLimit::find(1) ?: $limits->first();
        if ($defaultLimit) {
            return $buildApproval($defaultLimit);
        }

        // If nothing found at all
        return ['status' => false, 'message' => 'No applicable financial limit found'];
    } catch (Exception $e) {
        return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
    }
}
    
    
    /** 
        Prepare Next Approver Email
    **/
    
    public function getApprovalTemplate($contractId, $isapprover=false, $creditCellTemplate=false){

            $contract = Contract::with([
                'contractPartyList.partyDetailsEx',
                'contractPartyList.partyDetailsIn',
                'contractPartyList.branchDetails',
                'contractDiscounts',
                'contractHealthChecks',
                'contractLocations.location',
                'contractParent',
                'contractTypeData',
                'contractClauseLink'
            ])->findOrFail($contractId);        
        
            // Generate approval email HTML for this contract (use stored contractData if available)
            $approvalHtml = null;
            try {
                $emailController = new EmailTemplateController();
                
                
                  $party = optional($contract->contractPartyList->get(1));
                  $partyEx = $party ? optional($party->partyDetailsEx) : null;
                  $customerName = $partyEx && ($partyEx->company_name ?? false)
                                  ? (function_exists('decryptString') ? @decryptString($partyEx->company_name, 'company_name') : $partyEx->company_name)
                                  : ($partyIn && ($partyIn->name ?? false) ? $partyIn->name : '');                

                    // Fallback: reconstruct minimal structured data from contract rows
                    $discounts = [];
                    foreach ($contract->contractDiscounts as $d) {
                        $discounts[] = [
                            'category' => $d->category,
                            'subcategory' => $d->subcategory,
                            'discount_percent' => (float) $d->discount_percent,
                            'is_high_discount' => (float) $d->discount_percent > 15,
                            'room_charges' => is_array($d->room_charges) ? $d->room_charges : (is_string($d->room_charges) ? @json_decode($d->room_charges, true) : [])
                        ];
                    }
                    
                    $healthChecks = [];
                    $healthCheckRows = $contract->contractHealthChecks;
                    
                    foreach ($healthCheckRows as $index => $row) {
                        $tests = [];
                        $testsTotal = 0;
                        
                        foreach ($row['selected_test_ids'] as $testId) {
                            $testDetails = $this->getTestDetails($testId);
                            $tests[] = [
                                'id' => $testDetails['id'],
                                'name' => $testDetails['name'],
                                'price' => $isapprover ? $testDetails['price'] : '-'
                            ];
                            $testsTotal += $testDetails['price'];
                        }
            
                        $consultations = [];
                        $consultationsTotal = 0;
                        
                        foreach ($row['selected_consultation_ids'] as $consultId) {
                            
                            if(is_numeric($consultId)){
                                $consultName = $this->getConsultationName($consultId);
                            }else{
                               $consultName = $consultId; 
                            }
                            $price = isset($row['prices'][$consultId]) ? $row['prices'][$consultId] : 0;
                            $consultations[] = [
                                'id' => $consultId,
                                'name' => $consultName,
                                'price' => $isapprover ? $price : '-'
                            ];
                            $consultationsTotal += $price;
                        }
            
                        $healthChecks[] = [
                            'package_number' => $index + 1,
                            'package_name' => $row['row_name'],
                            'package_price' => $row['package_price'],
                            'tests' => $tests,
                            'tests_total' => $testsTotal,
                            'consultations' => $consultations,
                            'consultations_total' => $consultationsTotal
                        ];
                    }
                    

                    $locationDetails = [];
                    $locIds = [];
                    foreach ($contract->contractLocations as $cl) {
                        $loc = $cl->location; // relation returns BranchUser select
                        if ($loc) {
                            $locationDetails[] = [
                                'id' => $loc->id,
                                'name' => $loc->location_name ?? "",
                                'region' => $loc->region ?? '',
                                'unit_head' => $loc->location_head ?? '',
                                'region_head' => $loc->region_head ?? ''
                            ];
                        }
                        $locIds[] = $cl->location_id;
                    }

                    $maxDiscount = 0;
                    $hasHigh = false;
                    foreach ($discounts as $dd) {
                        $p = (float) ($dd['discount_percent'] ?? 0);
                        if ($p > $maxDiscount) $maxDiscount = $p;
                        if ($p > 15) $hasHigh = true;
                    }

                    $structuredData = [
                        'contract_info' => [
                            'agreement_name' => function_exists('decryptString') && !empty($contract->contract_name) ? @decryptString($contract->contract_name, 'contract_name') : ($contract->contract_name ?? ''),
                            'contract_id' => $contract->id,
                            'customer_id' => '',
                            'customer_name' => $customerName,
                            'contract_type' => ContractType::find(admin_setting('custom_contracts_type_id'))->contract_type,
                            'scope' => $contract->custom_fields_data ?? '',
                            'entity_type_id' => $contract->catgoery_id ?? null,
                            'scope_of_services' => is_string($contract->contract_tags) ? @json_decode($contract->contract_tags, true) : ($contract->contract_tags ?? []),
                            'start_date' => $contract->fixed_date ?? $contract->signing_date ?? '',
                            'end_date' => $contract->contract_end_date ?? '',
                            'duration_confirmed' => true,
                            'editor_text' => $contract->contract_description ?? ''
                        ],
                        'locations' => [
                            'count' => count($locIds),
                            'region_count' => 0,
                            'details' => $locationDetails
                        ],
                        'discounts' => $discounts,
                        'health_check_packages' => $healthChecks,
                        'approvers' => [
                            'list' => [],
                            'logic' => [
                                'total_locations' => count($locIds),
                                'total_regions' => 0,
                                'has_high_discount' => $hasHigh,
                                'max_discount' => $maxDiscount,
                                'approval_level' => '',
                                'explanation' => ''
                            ]
                        ],
                        'generated_at' => now()->toDateTimeString()
                    ];
                    
                
                if(!$creditCellTemplate){
                    // Generate HTML template (no email send)
                    $approvalHtml = $emailController->generateHTMLTemplate($structuredData);
                }else{
                   $approvalHtml = $emailController->sendCorporateFinanceEmail($structuredData); 
                }
                
                //echo $approvalHtml;
            } catch (\Exception $e) {
                \Log::error ('Failed to generate approval HTML for contract ' . ($contract->id ?? 'unknown') . ': ' . $e->getMessage());
                $approvalHtml = false;
            } 
            
            return $approvalHtml;
    }

    /**
     * Create a historical snapshot of current contract data
     * Also snapshots related tables: locations, discounts, health_checks
     */
    private function createContractSnapshot(Contract $contract, $note = null)
    {
        try {
            $updatedUser = ['email' => Helpers::userInfo()->email ?? 'External', 'name' => Helpers::userInfo()->FirstName ?? 'User'];
            $payload = [
                'contract_name' => $contract->contract_name,
                'id' => $contract->id,
                'contract_type' => $contract->contract_type,
                'contract_description' => $contract->contract_description,
                'department_id' => $contract->department_id ?? null,
                'catgoery_id' => $contract->catgoery_id ?? null,
                'signatory' => $contract->signatory ?? null,
                'owner' => $contract->owner ?? null,
                'confidentialityagreement' => $contract->confidentialityagreement ?? null,
                'signing_date' => $contract->signing_date ?? null,
                'fixed_date' => $contract->fixed_date ?? null,
                'contract_end_date' => $contract->contract_end_date ?? null,
                'renewal_type' => $contract->renewal_type ?? null,
                'currency' => $contract->currency ?? null,
                'billing_value' => $contract->billing_value ?? null,
                'currency_value' => $contract->currency_value ?? null,
                'total_value' => $contract->total_value ?? null,
                'rules_id' => $contract->rules_id ?? null,
                'contract_attachment' => $contract->contract_attachment ?? null,
                'contract_attachment_filename' => $contract->contract_attachment_filename ?? null,
                'contract_status' => $contract->contract_status ?? null,
                'substatus' => $contract->substatus ?? null,
                'custom_fields_data' => $contract->custom_fields_data ?? null,
                'created_by' => $contract->created_by ?? null
            ];
            if ($note) $payload['reasonforterminate'] = $note;
            $historyRecord = ContractHistory::create($payload);
            $snapshotId = $historyRecord->history_id ?? $historyRecord->id;

            // Snapshot related tables for audit trail
            $this->snapshotRelatedTables($contract, $snapshotId);
        } catch (\Throwable $e) {
            \Log::error('createContractSnapshot failed for contract ' . ($contract->id ?? '') . ': ' . $e->getMessage());
        }
    }

    /**
     * Snapshot related tables (locations, discounts, health_checks) for audit trail
     * Only creates snapshot records when there are actual changes from the previous snapshot
     */
    private function snapshotRelatedTables(Contract $contract, $snapshotId)
    {
        try {
            $userId = Helpers::userInfo()->id ?? 0;
            $contractId = $contract->id;

            // Get previous snapshot ID for comparison
            $prevSnapshot = ContractHistory::where('id', $contractId)
                ->where('history_id', '<', $snapshotId)
                ->orderBy('history_id', 'desc')
                ->first();
            $prevSnapshotId = $prevSnapshot ? $prevSnapshot->history_id : null;

            // Current data
            $currentLocations = ContractLocation::where('contract_id', $contractId)->get();
            $currentDiscounts = ContractDiscount::where('contract_id', $contractId)->get();
            $currentHealthChecks = ContractHealthCheck::where('contract_id', $contractId)->get();

            // Previous snapshot data
            $prevLocations = $prevSnapshotId 
                ? ContractLocationHistory::where('snapshot_id', $prevSnapshotId)->get() 
                : collect();
            $prevDiscounts = $prevSnapshotId 
                ? ContractDiscountHistory::where('snapshot_id', $prevSnapshotId)->get() 
                : collect();
            $prevHealthChecks = $prevSnapshotId 
                ? ContractHealthCheckHistory::where('snapshot_id', $prevSnapshotId)->get() 
                : collect();

            // ========== LOCATIONS ==========
            $currentLocIds = $currentLocations->pluck('location_id')->filter()->unique()->toArray();
            $prevLocIds = $prevLocations->pluck('location_id')->filter()->unique()->toArray();
            
            $locationsChanged = (count(array_diff($currentLocIds, $prevLocIds)) > 0) || 
                                (count(array_diff($prevLocIds, $currentLocIds)) > 0);

            if ($locationsChanged || !$prevSnapshotId) {
                // Determine action for each location
                foreach ($currentLocations as $loc) {
                    $locationName = null;
                    if ($loc->location_id) {
                        $locationMaster = LocationMaster::find($loc->location_id);
                        $locationName = $locationMaster->location_name ?? null;
                    }
                    
                    $action = 'unchanged';
                    if (!in_array($loc->location_id, $prevLocIds)) {
                        $action = 'added';
                    }
                    
                    ContractLocationHistory::create([
                        'contract_id' => $contractId,
                        'snapshot_id' => $snapshotId,
                        'location_id' => $loc->location_id,
                        'location_name' => $locationName,
                        'action' => $action,
                        'created_by' => $userId,
                    ]);
                }
                
                // Record removed locations
                foreach ($prevLocIds as $prevLocId) {
                    if (!in_array($prevLocId, $currentLocIds)) {
                        $prevLoc = $prevLocations->where('location_id', $prevLocId)->first();
                        ContractLocationHistory::create([
                            'contract_id' => $contractId,
                            'snapshot_id' => $snapshotId,
                            'location_id' => $prevLocId,
                            'location_name' => $prevLoc->location_name ?? null,
                            'action' => 'removed',
                            'created_by' => $userId,
                        ]);
                    }
                }
            }

            // ========== DISCOUNTS ==========
            $currentDiscMap = [];
            foreach ($currentDiscounts as $d) {
                $key = $d->category . '|' . $d->subcategory;
                $currentDiscMap[$key] = $d;
            }
            $prevDiscMap = [];
            foreach ($prevDiscounts as $d) {
                $key = $d->category . '|' . $d->subcategory;
                $prevDiscMap[$key] = $d;
            }
            
            $discountsChanged = false;
            // Check for additions, modifications
            foreach ($currentDiscMap as $key => $disc) {
                if (!isset($prevDiscMap[$key])) {
                    $discountsChanged = true;
                    break;
                }
                $prevDisc = $prevDiscMap[$key];
                if ($disc->discount_percent != $prevDisc->discount_percent || 
                    json_encode($disc->room_charges) !== json_encode($prevDisc->room_charges)) {
                    $discountsChanged = true;
                    break;
                }
            }
            // Check for removals
            if (!$discountsChanged) {
                foreach ($prevDiscMap as $key => $disc) {
                    if (!isset($currentDiscMap[$key])) {
                        $discountsChanged = true;
                        break;
                    }
                }
            }

            if ($discountsChanged || !$prevSnapshotId) {
                // Snapshot current discounts with action
                foreach ($currentDiscounts as $disc) {
                    $key = $disc->category . '|' . $disc->subcategory;
                    $action = 'unchanged';
                    
                    if (!isset($prevDiscMap[$key])) {
                        $action = 'added';
                    } else {
                        $prevDisc = $prevDiscMap[$key];
                        if ($disc->discount_percent != $prevDisc->discount_percent || 
                            json_encode($disc->room_charges) !== json_encode($prevDisc->room_charges)) {
                            $action = 'modified';
                        }
                    }
                    
                    ContractDiscountHistory::create([
                        'contract_id' => $contractId,
                        'snapshot_id' => $snapshotId,
                        'original_id' => $disc->id,
                        'category' => $disc->category,
                        'subcategory' => $disc->subcategory,
                        'discount_percent' => $disc->discount_percent,
                        'room_charges' => $disc->room_charges,
                        'action' => $action,
                        'created_by' => $userId,
                    ]);
                }
                
                // Record removed discounts
                foreach ($prevDiscMap as $key => $prevDisc) {
                    if (!isset($currentDiscMap[$key])) {
                        ContractDiscountHistory::create([
                            'contract_id' => $contractId,
                            'snapshot_id' => $snapshotId,
                            'original_id' => $prevDisc->original_id ?? null,
                            'category' => $prevDisc->category,
                            'subcategory' => $prevDisc->subcategory,
                            'discount_percent' => $prevDisc->discount_percent,
                            'room_charges' => $prevDisc->room_charges,
                            'action' => 'removed',
                            'created_by' => $userId,
                        ]);
                    }
                }
            }

            // ========== HEALTH CHECKS ==========
            $currentHcMap = [];
            foreach ($currentHealthChecks as $hc) {
                $currentHcMap[$hc->row_name] = $hc;
            }
            $prevHcMap = [];
            foreach ($prevHealthChecks as $hc) {
                $prevHcMap[$hc->row_name] = $hc;
            }
            
            $healthChecksChanged = false;
            // Check for additions, modifications
            foreach ($currentHcMap as $rowName => $hc) {
                if (!isset($prevHcMap[$rowName])) {
                    $healthChecksChanged = true;
                    break;
                }
                $prevHc = $prevHcMap[$rowName];
                if ($hc->package_price != $prevHc->package_price || 
                    $hc->approved_cost != $prevHc->approved_cost ||
                    $hc->overhead_allocation != $prevHc->overhead_allocation ||
                    json_encode($hc->selected_test_ids) !== json_encode($prevHc->selected_test_ids) ||
                    json_encode($hc->selected_consultation_ids) !== json_encode($prevHc->selected_consultation_ids)) {
                    $healthChecksChanged = true;
                    break;
                }
            }
            // Check for removals
            if (!$healthChecksChanged) {
                foreach ($prevHcMap as $rowName => $hc) {
                    if (!isset($currentHcMap[$rowName])) {
                        $healthChecksChanged = true;
                        break;
                    }
                }
            }

            if ($healthChecksChanged || !$prevSnapshotId) {
                // Snapshot current health checks with action
                foreach ($currentHealthChecks as $hc) {
                    $action = 'unchanged';
                    
                    if (!isset($prevHcMap[$hc->row_name])) {
                        $action = 'added';
                    } else {
                        $prevHc = $prevHcMap[$hc->row_name];
                        if ($hc->package_price != $prevHc->package_price || 
                            $hc->approved_cost != $prevHc->approved_cost ||
                            $hc->overhead_allocation != $prevHc->overhead_allocation ||
                            json_encode($hc->selected_test_ids) !== json_encode($prevHc->selected_test_ids) ||
                            json_encode($hc->selected_consultation_ids) !== json_encode($prevHc->selected_consultation_ids)) {
                            $action = 'modified';
                        }
                    }
                    
                    ContractHealthCheckHistory::create([
                        'contract_id' => $contractId,
                        'snapshot_id' => $snapshotId,
                        'original_id' => $hc->id,
                        'row_name' => $hc->row_name,
                        'selected_test_ids' => $hc->selected_test_ids,
                        'package_price' => $hc->package_price,
                        'selected_consultation_ids' => $hc->selected_consultation_ids,
                        'consultation_prices' => $hc->consultation_prices,
                        'overhead_allocation' => $hc->overhead_allocation,
                        'approved_cost' => $hc->approved_cost,
                        'action' => $action,
                        'created_by' => $userId,
                    ]);
                }
                
                // Record removed health checks
                foreach ($prevHcMap as $rowName => $prevHc) {
                    if (!isset($currentHcMap[$rowName])) {
                        ContractHealthCheckHistory::create([
                            'contract_id' => $contractId,
                            'snapshot_id' => $snapshotId,
                            'original_id' => $prevHc->original_id ?? null,
                            'row_name' => $rowName,
                            'selected_test_ids' => $prevHc->selected_test_ids,
                            'package_price' => $prevHc->package_price,
                            'selected_consultation_ids' => $prevHc->selected_consultation_ids,
                            'consultation_prices' => $prevHc->consultation_prices,
                            'overhead_allocation' => $prevHc->overhead_allocation,
                            'approved_cost' => $prevHc->approved_cost,
                            'action' => 'removed',
                            'created_by' => $userId,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error('snapshotRelatedTables failed for contract ' . ($contract->id ?? '') . ': ' . $e->getMessage());
        }
    }
    public function getContractCodes($id)
    {
        try {
            $contract = Contract::findOrFail($id);
            $isActive = (strtolower($contract->contract_status) === 'executed' || strtolower($contract->status) === 'active');
            if (! $isActive) {
                return response()->json(['success' => false, 'message' => 'Contract is not active'], 403);
            }
            return response()->json(['success' => true, 'data' => [
                'mm_code' => $contract->mm_code ?? null,
                'oracle_code' => $contract->oracle_code ?? null
            ]], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch codes', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Save MM and Oracle codes for a contract (Active contracts only)
     */
    public function saveContractCodes(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'mm_code' => 'nullable|string|max:255',
            'oracle_code' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            $contract = Contract::findOrFail($id);
            $isActive = (strtolower($contract->contract_status) === 'executed' || strtolower($contract->status) === 'active');
            if (! $isActive) {
                return response()->json(['success' => false, 'message' => 'Contract is not active'], 403);
            }

            $contract->mm_code = $request->input('mm_code');
            $contract->oracle_code = $request->input('oracle_code');
            $contract->save();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Codes saved successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to save codes', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the complete change history for a contract
     * Returns all history snapshots with user info, change timestamp, and related tables changes
     */
    public function getChangeHistory($id)
    {
        try {
            $contract = Contract::findOrFail($id);
            
            // Get all history records for this contract
            $historyRecords = ContractHistory::where('id', $id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Get user info for created_by
            $userIds = $historyRecords->pluck('created_by')->unique()->filter()->toArray();
            $users = [];
            if (!empty($userIds)) {
                $usersQuery = AddUsers::select('id', decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'), decrypt_data('Email', 'AddUsers'))
                    ->whereIn('id', $userIds)
                    ->get();
                foreach ($usersQuery as $u) {
                    $users[$u->id] = [
                        'name' => trim(($u->FirstName ?? '') . ' ' . ($u->LastName ?? '')),
                        'email' => $u->Email ?? ''
                    ];
                }
            }

            // Fields to track for changes
            $trackFields = [
                'contract_name' => 'Contract Name',
                'contract_status' => 'Status',
                'substatus' => 'Sub-Status',
                'fixed_date' => 'Start Date',
                'contract_end_date' => 'End Date',
                'signing_date' => 'Signing Date',
                'currency_value' => 'Contract Value',
                'currency' => 'Currency',
                'owner' => 'Owner',
                'signatory' => 'Signatory',
                'catgoery_id' => 'Category',
                'department_id' => 'Department',
                'contract_attachment_filename' => 'Attachment',
                'confidentialityagreement' => 'Agreement Details'
            ];

            // Confidentiality agreement sub-fields to track
            $confAgreementFields = [
                'credit_limit' => 'Credit Limit',
                'credit_days' => 'Credit Days',
                'coc_ip' => 'COC IP',
                'coc_op' => 'COC OP',
                'prevailing_hospital_tariff' => 'Prevailing Hospital Tariff',
                'communication_protocol' => 'Communication Protocol',
                'employees_dependants' => 'Employees & Dependants',
                'employees' => 'Employees Count',
                'dependants' => 'Dependants Count',
                'sponsors' => 'Sponsors'
            ];

            $historyData = [];
            $prevRecord = null;

            foreach ($historyRecords->reverse() as $record) {
                $changes = [];
                $snapshotId = $record->history_id ?? null;
                
                // Decrypt and format values
                foreach ($trackFields as $field => $label) {
                    // Skip confidentialityagreement - we'll handle it separately
                    if ($field === 'confidentialityagreement') continue;
                    
                    $currentVal = $record->$field ?? null;
                    $prevVal = $prevRecord ? ($prevRecord->$field ?? null) : null;
                    
                    // Decrypt if needed
                    if (function_exists('decryptString') && is_string($currentVal) && !empty($currentVal)) {
                        try { $currentVal = @decryptString($currentVal, $field); } catch (\Throwable $e) {}
                    }
                    if (function_exists('decryptString') && is_string($prevVal) && !empty($prevVal)) {
                        try { $prevVal = @decryptString($prevVal, $field); } catch (\Throwable $e) {}
                    }

                    // Format dates
                    if (in_array($field, ['fixed_date', 'contract_end_date', 'signing_date']) && !empty($currentVal)) {
                        try { $currentVal = \Carbon\Carbon::parse($currentVal)->format('d M Y'); } catch (\Throwable $e) {}
                    }
                    if (in_array($field, ['fixed_date', 'contract_end_date', 'signing_date']) && !empty($prevVal)) {
                        try { $prevVal = \Carbon\Carbon::parse($prevVal)->format('d M Y'); } catch (\Throwable $e) {}
                    }

                    // Check if value changed
                    if ($prevRecord && $currentVal != $prevVal) {
                        $changes[] = [
                            'field' => $field,
                            'label' => $label,
                            'old_value' => $prevVal,
                            'new_value' => $currentVal
                        ];
                    }
                }

                // Track confidentialityagreement sub-field changes
                $currentConf = @json_decode($record->confidentialityagreement ?? '{}', true) ?: [];
                $prevConf = $prevRecord ? (@json_decode($prevRecord->confidentialityagreement ?? '{}', true) ?: []) : [];
                
                foreach ($confAgreementFields as $confField => $confLabel) {
                    $currConfVal = $currentConf[$confField] ?? null;
                    $prevConfVal = $prevConf[$confField] ?? null;
                    
                    if ($prevRecord && $currConfVal != $prevConfVal) {
                        $changes[] = [
                            'field' => 'confidentialityagreement.' . $confField,
                            'label' => $confLabel,
                            'old_value' => $prevConfVal,
                            'new_value' => $currConfVal
                        ];
                    }
                }

                // Track related table changes (locations, discounts, health_checks)
                $relatedChanges = $this->getRelatedTableChanges($id, $snapshotId, $prevRecord ? ($prevRecord->history_id ?? null) : null);

                // Get updated_by info
                $updatedByInfo = null;
                if (!empty($record->updated_by)) {
                    $updatedByDecoded = @json_decode($record->updated_by, true);
                    if (is_array($updatedByDecoded)) {
                        $updatedByInfo = $updatedByDecoded;
                    }
                }

                // Get created_by user info
                $createdByUser = null;
                $createdById = $record->created_by ?? null;
                if ($createdById && isset($users[$createdById])) {
                    $createdByUser = $users[$createdById];
                } elseif ($createdById == -1) {
                    $createdByUser = ['name' => 'External User', 'email' => ''];
                } elseif ($createdById == 0) {
                    $createdByUser = ['name' => 'System', 'email' => ''];
                }

                $historyData[] = [
                    'history_id' => $record->history_id ?? $record->id,
                    'created_at' => $record->created_at ? $record->created_at->format('d M Y H:i:s') : null,
                    'updated_at' => $record->updated_at ? $record->updated_at->format('d M Y H:i:s') : null,
                    'status' => $record->contract_status ?? null,
                    'substatus' => $record->substatus ?? null,
                    'updated_by' => $updatedByInfo,
                    'created_by' => $createdByUser,
                    'changes' => $changes,
                    'related_changes' => $relatedChanges,
                    'is_first' => ($prevRecord === null)
                ];

                $prevRecord = $record;
            }

            // Reverse to show newest first
            $historyData = array_reverse($historyData);

            return response()->json([
                'success' => true,
                'data' => $historyData,
                'current' => [
                    'status' => $contract->contract_status,
                    'substatus' => $contract->substatus
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('getChangeHistory error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch history', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get changes in related tables (locations, discounts, health_checks) between two snapshots
     */
    private function getRelatedTableChanges($contractId, $currentSnapshotId, $prevSnapshotId)
    {
        $changes = [
            'locations' => [],
            'discounts' => [],
            'health_checks' => []
        ];

        try {
            // Get current snapshot data
            $currentLocations = $currentSnapshotId 
                ? ContractLocationHistory::where('snapshot_id', $currentSnapshotId)->get()
                : collect();
            $currentDiscounts = $currentSnapshotId 
                ? ContractDiscountHistory::where('snapshot_id', $currentSnapshotId)->get()
                : collect();
            $currentHealthChecks = $currentSnapshotId 
                ? ContractHealthCheckHistory::where('snapshot_id', $currentSnapshotId)->get()
                : collect();

            // Get previous snapshot data
            $prevLocations = $prevSnapshotId 
                ? ContractLocationHistory::where('snapshot_id', $prevSnapshotId)->get()
                : collect();
            $prevDiscounts = $prevSnapshotId 
                ? ContractDiscountHistory::where('snapshot_id', $prevSnapshotId)->get()
                : collect();
            $prevHealthChecks = $prevSnapshotId 
                ? ContractHealthCheckHistory::where('snapshot_id', $prevSnapshotId)->get()
                : collect();

            // Compare locations
            $currentLocIds = $currentLocations->pluck('location_id')->toArray();
            $prevLocIds = $prevLocations->pluck('location_id')->toArray();
            
            $addedLocs = array_diff($currentLocIds, $prevLocIds);
            $removedLocs = array_diff($prevLocIds, $currentLocIds);

            foreach ($addedLocs as $locId) {
                $loc = $currentLocations->where('location_id', $locId)->first();
                $changes['locations'][] = [
                    'action' => 'added',
                    'location_id' => $locId,
                    'location_name' => $loc->location_name ?? 'Unknown'
                ];
            }
            foreach ($removedLocs as $locId) {
                $loc = $prevLocations->where('location_id', $locId)->first();
                $changes['locations'][] = [
                    'action' => 'removed',
                    'location_id' => $locId,
                    'location_name' => $loc->location_name ?? 'Unknown'
                ];
            }

            // Compare discounts
            $currentDiscMap = $currentDiscounts->keyBy(function ($d) {
                return $d->category . '|' . $d->subcategory;
            });
            $prevDiscMap = $prevDiscounts->keyBy(function ($d) {
                return $d->category . '|' . $d->subcategory;
            });

            foreach ($currentDiscMap as $key => $disc) {
                if (!$prevDiscMap->has($key)) {
                    $changes['discounts'][] = [
                        'action' => 'added',
                        'category' => $disc->category,
                        'subcategory' => $disc->subcategory,
                        'discount_percent' => $disc->discount_percent
                    ];
                } else {
                    $prevDisc = $prevDiscMap->get($key);
                    if ($disc->discount_percent != $prevDisc->discount_percent) {
                        $changes['discounts'][] = [
                            'action' => 'modified',
                            'category' => $disc->category,
                            'subcategory' => $disc->subcategory,
                            'old_value' => $prevDisc->discount_percent,
                            'new_value' => $disc->discount_percent
                        ];
                    }
                }
            }
            foreach ($prevDiscMap as $key => $disc) {
                if (!$currentDiscMap->has($key)) {
                    $changes['discounts'][] = [
                        'action' => 'removed',
                        'category' => $disc->category,
                        'subcategory' => $disc->subcategory,
                        'discount_percent' => $disc->discount_percent
                    ];
                }
            }

            // Compare health checks
            $currentHcMap = $currentHealthChecks->keyBy('row_name');
            $prevHcMap = $prevHealthChecks->keyBy('row_name');

            foreach ($currentHcMap as $rowName => $hc) {
                if (!$prevHcMap->has($rowName)) {
                    $changes['health_checks'][] = [
                        'action' => 'added',
                        'row_name' => $rowName,
                        'package_price' => $hc->package_price,
                        'approved_cost' => $hc->approved_cost
                    ];
                } else {
                    $prevHc = $prevHcMap->get($rowName);
                    $modified = [];
                    if ($hc->package_price != $prevHc->package_price) {
                        $modified['package_price'] = ['old' => $prevHc->package_price, 'new' => $hc->package_price];
                    }
                    if ($hc->approved_cost != $prevHc->approved_cost) {
                        $modified['approved_cost'] = ['old' => $prevHc->approved_cost, 'new' => $hc->approved_cost];
                    }
                    if ($hc->overhead_allocation != $prevHc->overhead_allocation) {
                        $modified['overhead_allocation'] = ['old' => $prevHc->overhead_allocation, 'new' => $hc->overhead_allocation];
                    }
                    if (!empty($modified)) {
                        $changes['health_checks'][] = [
                            'action' => 'modified',
                            'row_name' => $rowName,
                            'changes' => $modified
                        ];
                    }
                }
            }
            foreach ($prevHcMap as $rowName => $hc) {
                if (!$currentHcMap->has($rowName)) {
                    $changes['health_checks'][] = [
                        'action' => 'removed',
                        'row_name' => $rowName,
                        'package_price' => $hc->package_price,
                        'approved_cost' => $hc->approved_cost
                    ];
                }
            }
        } catch (\Throwable $e) {
            \Log::error('getRelatedTableChanges error: ' . $e->getMessage());
        }

        return $changes;
    }

    /**
     * Compare a specific history snapshot with current contract data
     * Returns detailed field-by-field comparison including confidentialityagreement and related tables
     */
    public function compareHistoryDetail($id, $historyId)
    {
        try {
            $contract = Contract::with(['contractLocations.location', 'contractDiscounts', 'contractHealthChecks'])->findOrFail($id);
            $history = ContractHistory::where('history_id', $historyId)->orWhere('id', $historyId)->first();
            
            if (!$history) {
                return response()->json(['success' => false, 'message' => 'History record not found'], 404);
            }

            $snapshotId = $history->history_id ?? $history->id;

            // Pre-fetch lookup data for ID resolution
            $userIds = array_filter([
                $history->owner ?? null, 
                $contract->owner ?? null,
                $history->signatory ?? null,
                $contract->signatory ?? null
            ]);
            $users = [];
            if (!empty($userIds)) {
                $usersQuery = AddUsers::select('id', decrypt_data('FirstName', 'AddUsers'), decrypt_data('LastName', 'AddUsers'), decrypt_data('Email', 'AddUsers'))
                    ->whereIn('id', $userIds)
                    ->get();
                foreach ($usersQuery as $u) {
                    $users[$u->id] = trim(($u->FirstName ?? '') . ' ' . ($u->LastName ?? '')) ?: ($u->Email ?? 'User #' . $u->id);
                }
            }

            // Fetch categories and departments
            $categoryIds = array_filter([$history->catgoery_id ?? null, $contract->catgoery_id ?? null]);
            $categories = [];
            if (!empty($categoryIds)) {
                $catQuery = ContractType::whereIn('contract_type_id', $categoryIds)->get();
                foreach ($catQuery as $cat) {
                    $categories[$cat->id] = $cat->type_name ?? 'Category #' . $cat->id;
                }
            }

            $departmentIds = array_filter([$history->department_id ?? null, $contract->department_id ?? null]);
            $departments = [];
            if (!empty($departmentIds)) {
                $deptQuery = EntityBusiness::whereIn('id', $departmentIds)->get();
                foreach ($deptQuery as $dept) {
                    $departments[$dept->id] = $dept->name ?? 'Department #' . $dept->id;
                }
            }

            // Helper to resolve ID to name
            $resolveValue = function($field, $value) use ($users, $categories, $departments) {
                if (empty($value)) return null;
                
                switch ($field) {
                    case 'owner':
                    case 'signatory':
                        return $users[$value] ?? 'User #' . $value;
                    case 'catgoery_id':
                        return $categories[$value] ?? 'Category #' . $value;
                    case 'department_id':
                        return $departments[$value] ?? 'Department #' . $value;
                    default:
                        return $value;
                }
            };

            // Fields to compare with friendly labels
            $compareFields = [
                'contract_name' => 'Contract Name',
                'contract_status' => 'Status',
                'substatus' => 'Sub-Status',
                'fixed_date' => 'Start Date',
                'contract_end_date' => 'End Date',
                'signing_date' => 'Signing Date',
                'currency' => 'Currency',
                'currency_value' => 'Contract Value',
                'billing_value' => 'Billing Value',
                'total_value' => 'Total Value',
                'owner' => 'Owner',
                'signatory' => 'Signatory',
                'catgoery_id' => 'Category',
                'department_id' => 'Department',
                'contract_attachment_filename' => 'Attachment',
                'contract_description' => 'Description',
                'renewal_type' => 'Renewal Type',
                'termination_reason' => 'Termination Reason'
            ];

            $comparison = [];
            $changesCount = 0;

            foreach ($compareFields as $field => $label) {
                $historyVal = $history->$field ?? null;
                $currentVal = $contract->$field ?? null;

                // Decrypt values
                if (function_exists('decryptString')) {
                    if (is_string($historyVal) && !empty($historyVal)) {
                        try { $historyVal = @decryptString($historyVal, $field); } catch (\Throwable $e) {}
                    }
                    if (is_string($currentVal) && !empty($currentVal)) {
                        try { $currentVal = @decryptString($currentVal, $field); } catch (\Throwable $e) {}
                    }
                }

                // Format dates
                $dateFields = ['fixed_date', 'contract_end_date', 'signing_date'];
                if (in_array($field, $dateFields)) {
                    if (!empty($historyVal)) {
                        try { $historyVal = \Carbon\Carbon::parse($historyVal)->format('d M Y'); } catch (\Throwable $e) {}
                    }
                    if (!empty($currentVal)) {
                        try { $currentVal = \Carbon\Carbon::parse($currentVal)->format('d M Y'); } catch (\Throwable $e) {}
                    }
                }

                // Resolve IDs to names for specific fields
                $idFields = ['owner', 'signatory', 'catgoery_id', 'department_id'];
                $historyDisplay = $historyVal;
                $currentDisplay = $currentVal;
                if (in_array($field, $idFields)) {
                    $historyDisplay = $resolveValue($field, $historyVal);
                    $currentDisplay = $resolveValue($field, $currentVal);
                }

                $isChanged = ($historyVal != $currentVal);
                if ($isChanged) $changesCount++;

                $comparison[] = [
                    'field' => $field,
                    'label' => $label,
                    'history_value' => $historyDisplay,
                    'current_value' => $currentDisplay,
                    'is_changed' => $isChanged
                ];
            }

            // Compare confidentialityagreement sub-fields
            $confAgreementFields = [
                'credit_limit' => 'Credit Limit',
                'credit_days' => 'Credit Days',
                'coc_ip' => 'COC IP',
                'coc_op' => 'COC OP',
                'prevailing_hospital_tariff' => 'Prevailing Hospital Tariff',
                'communication_protocol' => 'Communication Protocol',
                'employees_dependants' => 'Employees & Dependants',
                'employees' => 'Employees Count',
                'dependants' => 'Dependants Count',
                'sponsors' => 'Sponsors'
            ];

            $historyConf = @json_decode($history->confidentialityagreement ?? '{}', true) ?: [];
            $currentConf = @json_decode($contract->confidentialityagreement ?? '{}', true) ?: [];

            $confComparison = [];
            foreach ($confAgreementFields as $confField => $confLabel) {
                $historyConfVal = $historyConf[$confField] ?? null;
                $currentConfVal = $currentConf[$confField] ?? null;
                
                $isChanged = ($historyConfVal != $currentConfVal);
                if ($isChanged) $changesCount++;

                $confComparison[] = [
                    'field' => $confField,
                    'label' => $confLabel,
                    'history_value' => $historyConfVal,
                    'current_value' => $currentConfVal,
                    'is_changed' => $isChanged
                ];
            }

            // Compare related tables (locations, discounts, health_checks)
            $locationsComparison = $this->compareLocationsHistory($contract, $snapshotId);
            $discountsComparison = $this->compareDiscountsHistory($contract, $snapshotId);
            $healthChecksComparison = $this->compareHealthChecksHistory($contract, $snapshotId);

            // Count changes in related tables
            $changesCount += count(array_filter($locationsComparison, fn($l) => $l['action'] !== 'unchanged'));
            $changesCount += count(array_filter($discountsComparison, fn($d) => $d['action'] !== 'unchanged'));
            $changesCount += count(array_filter($healthChecksComparison, fn($h) => $h['action'] !== 'unchanged'));

            // Get updated_by info from history
            $updatedByInfo = null;
            if (!empty($history->updated_by)) {
                $updatedByDecoded = @json_decode($history->updated_by, true);
                if (is_array($updatedByDecoded)) {
                    $updatedByInfo = $updatedByDecoded;
                }
            }

            return response()->json([
                'success' => true,
                'history_date' => $history->created_at ? $history->created_at->format('d M Y H:i:s') : null,
                'updated_by' => $updatedByInfo,
                'changes_count' => $changesCount,
                'comparison' => $comparison,
                'confidentiality_agreement' => $confComparison,
                'locations' => $locationsComparison,
                'discounts' => $discountsComparison,
                'health_checks' => $healthChecksComparison
            ]);

        } catch (\Exception $e) {
            \Log::error('compareHistoryDetail error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to compare history', 'error' => $e->getMessage()."on line ".$e->getLine()], 500);
        }
    }

    /**
     * Compare locations between snapshot and current contract
     */
    private function compareLocationsHistory($contract, $snapshotId)
    {
        $comparison = [];
        
        try {
            // Get snapshot locations
            $historyLocs = ContractLocationHistory::where('snapshot_id', $snapshotId)->get();
            $historyLocIds = $historyLocs->pluck('location_id')->toArray();
            
            // Get current locations
            $currentLocs = $contract->contractLocations ?? collect();
            $currentLocIds = $currentLocs->pluck('location_id')->toArray();

            // Find added, removed, unchanged
            $addedIds = array_diff($currentLocIds, $historyLocIds);
            $removedIds = array_diff($historyLocIds, $currentLocIds);
            $unchangedIds = array_intersect($currentLocIds, $historyLocIds);

            foreach ($unchangedIds as $locId) {
                $loc = $currentLocs->where('location_id', $locId)->first();
                $locName = $loc && $loc->location ? $loc->location->location_name : 'Unknown';
                $comparison[] = [
                    'location_id' => $locId,
                    'location_name' => $locName,
                    'action' => 'unchanged'
                ];
            }

            foreach ($addedIds as $locId) {
                $loc = $currentLocs->where('location_id', $locId)->first();
                $locName = $loc && $loc->location ? $loc->location->location_name : 'Unknown';
                $comparison[] = [
                    'location_id' => $locId,
                    'location_name' => $locName,
                    'action' => 'added'
                ];
            }

            foreach ($removedIds as $locId) {
                $histLoc = $historyLocs->where('location_id', $locId)->first();
                $comparison[] = [
                    'location_id' => $locId,
                    'location_name' => $histLoc->location_name ?? 'Unknown',
                    'action' => 'removed'
                ];
            }
        } catch (\Throwable $e) {
            \Log::error('compareLocationsHistory error: ' . $e->getMessage());
        }

        return $comparison;
    }

    /**
     * Compare discounts between snapshot and current contract
     */
    private function compareDiscountsHistory($contract, $snapshotId)
    {
        $comparison = [];
        
        try {
            // Get snapshot discounts
            $historyDiscs = ContractDiscountHistory::where('snapshot_id', $snapshotId)->get();
            $historyDiscMap = $historyDiscs->keyBy(fn($d) => $d->category . '|' . $d->subcategory);
            
            // Get current discounts
            $currentDiscs = $contract->contractDiscounts ?? collect();
            $currentDiscMap = $currentDiscs->keyBy(fn($d) => $d->category . '|' . $d->subcategory);

            // Compare
            foreach ($currentDiscMap as $key => $disc) {
                if ($historyDiscMap->has($key)) {
                    $histDisc = $historyDiscMap->get($key);
                    $isModified = ($disc->discount_percent != $histDisc->discount_percent);
                    $comparison[] = [
                        'category' => $disc->category,
                        'subcategory' => $disc->subcategory,
                        'history_value' => $histDisc->discount_percent,
                        'current_value' => $disc->discount_percent,
                        'action' => $isModified ? 'modified' : 'unchanged'
                    ];
                } else {
                    $comparison[] = [
                        'category' => $disc->category,
                        'subcategory' => $disc->subcategory,
                        'history_value' => null,
                        'current_value' => $disc->discount_percent,
                        'action' => 'added'
                    ];
                }
            }

            // Check for removed discounts
            foreach ($historyDiscMap as $key => $histDisc) {
                if (!$currentDiscMap->has($key)) {
                    $comparison[] = [
                        'category' => $histDisc->category,
                        'subcategory' => $histDisc->subcategory,
                        'history_value' => $histDisc->discount_percent,
                        'current_value' => null,
                        'action' => 'removed'
                    ];
                }
            }
        } catch (\Throwable $e) {
            \Log::error('compareDiscountsHistory error: ' . $e->getMessage());
        }

        return $comparison;
    }

    /**
     * Compare health checks between snapshot and current contract
     */
    private function compareHealthChecksHistory($contract, $snapshotId)
    {
        $comparison = [];
        
        try {
            // Get snapshot health checks
            $historyHcs = ContractHealthCheckHistory::where('snapshot_id', $snapshotId)->get();
            $historyHcMap = $historyHcs->keyBy('row_name');
            
            // Get current health checks
            $currentHcs = $contract->contractHealthChecks ?? collect();
            $currentHcMap = $currentHcs->keyBy('row_name');

            // Compare
            foreach ($currentHcMap as $rowName => $hc) {
                if ($historyHcMap->has($rowName)) {
                    $histHc = $historyHcMap->get($rowName);
                    $changes = [];
                    
                    if ($hc->package_price != $histHc->package_price) {
                        $changes['package_price'] = ['history' => $histHc->package_price, 'current' => $hc->package_price];
                    }
                    if ($hc->approved_cost != $histHc->approved_cost) {
                        $changes['approved_cost'] = ['history' => $histHc->approved_cost, 'current' => $hc->approved_cost];
                    }
                    if ($hc->overhead_allocation != $histHc->overhead_allocation) {
                        $changes['overhead_allocation'] = ['history' => $histHc->overhead_allocation, 'current' => $hc->overhead_allocation];
                    }

                    $comparison[] = [
                        'row_name' => $rowName,
                        'history_price' => $histHc->package_price,
                        'current_price' => $hc->package_price,
                        'history_approved_cost' => $histHc->approved_cost,
                        'current_approved_cost' => $hc->approved_cost,
                        'changes' => $changes,
                        'action' => empty($changes) ? 'unchanged' : 'modified'
                    ];
                } else {
                    $comparison[] = [
                        'row_name' => $rowName,
                        'history_price' => null,
                        'current_price' => $hc->package_price,
                        'history_approved_cost' => null,
                        'current_approved_cost' => $hc->approved_cost,
                        'changes' => [],
                        'action' => 'added'
                    ];
                }
            }

            // Check for removed health checks
            foreach ($historyHcMap as $rowName => $histHc) {
                if (!$currentHcMap->has($rowName)) {
                    $comparison[] = [
                        'row_name' => $rowName,
                        'history_price' => $histHc->package_price,
                        'current_price' => null,
                        'history_approved_cost' => $histHc->approved_cost,
                        'current_approved_cost' => null,
                        'changes' => [],
                        'action' => 'removed'
                    ];
                }
            }
        } catch (\Throwable $e) {
            \Log::error('compareHealthChecksHistory error: ' . $e->getMessage());
        }

        return $comparison;
    }
    
    
    public function previewTemplate(Request $request, $id){
        
        $contract = Contract::find($id);
        
        if (!$contract) {
            return response()->json(['success' => false, 'message' => 'Invalid Contract'], 404);
        }else{
            return response()->json(['success' => false, 'message' => $this->updateOrCreateTemplate('', $contract, true)], 200);
        }     
    }
    
    

    /**
     * Extend the given contract by days or set a new end date (only for Executed contracts in allowed substatuses)
     */
    public function extendContract(Request $request, $id)
    {
        try {
            $contract = Contract::findOrFail($id);
            $status = strtolower($contract->contract_status ?? '');
            $substatus = strtolower($contract->substatus ?? '');
            $allowed = ['expired','completed','active'];
            if ($status !== 'executed' || ! in_array($substatus, $allowed)) {
                return response()->json(['status' => false, 'message' => 'Contract is not eligible for extension'], 403);
            }

            $days = $request->input('days');
            $end_date = $request->input('end_date');

            if (empty($days) && empty($end_date)) {
                return response()->json(['status' => false, 'message' => 'Provide number of days or end date'], 422);
            }

            $currentEnd = $contract->contract_end_date ? \Carbon\Carbon::parse($contract->contract_end_date) : null;
            if ($end_date) {
                $newEnd = \Carbon\Carbon::parse($end_date);
                if ($currentEnd && $newEnd->lte($currentEnd)) {
                    return response()->json(['status' => false, 'message' => 'End date must be after current end date'], 422);
                }
            } else {
                if (! $currentEnd) {
                    return response()->json(['status' => false, 'message' => 'No current end date to extend from'], 422);
                }
                if (! is_numeric($days) || intval($days) <= 0) {
                    return response()->json(['status' => false, 'message' => 'Days must be a positive integer'], 422);
                }
                $newEnd = $currentEnd->copy()->addDays(intval($days));
            }

            // Instead of updating the existing contract, duplicate it and create a new contract record
            DB::beginTransaction();

            // Replicate basic contract row (will not copy primary key)
            $newContract = $contract->replicate();

            // Preserve attachments and filename explicitly
            $newContract->contract_attachment = $contract->contract_attachment;
            $newContract->contract_attachment_filename = $contract->contract_attachment_filename;

            // Copy mm_code and oracle_code from parent contract
            $newContract->mm_code = $contract->mm_code;
            $newContract->oracle_code = $contract->oracle_code;

            // Store comments in contract_description
            $comments = $request->input('comments');
            if (!empty($comments)) {
                $newContract->contract_description = $comments;
            }

            // Mark as renewal/duplicate of original
            $newContract->parentcontract = $contract->id;
            $newContract->renewal_type = 'manual';

            // Set lifecycle fields for the new contract
            // Set fixed_date to day after current contract end (if available) or today
            if ($contract->contract_end_date) {
                $newContract->fixed_date = \Carbon\Carbon::parse($contract->contract_end_date)->addDay()->format('Y-m-d');
            } else {
                $newContract->fixed_date = now()->format('Y-m-d');
            }
            $newContract->contract_end_date = $newEnd->format('Y-m-d');

            // Reset workflow fields so approval flow can be created
            $newContract->contract_status = 'Draft';
            $newContract->substatus = 'Initial Draft';

            // Keep the same creator/owner
            $newContract->created_by = $contract->created_by;

            // Timestamps should reflect new record
            $newContract->created_at = now();
            $newContract->updated_at = now();

            $newContract->save();

            $newId = $newContract->id;

            // Copy related simple records: discounts
            if ($contract->contractDiscounts && $contract->contractDiscounts->count()) {
                $discounts = [];
                foreach ($contract->contractDiscounts as $d) {
                    $discounts[] = [
                        'contract_id' => $newId,
                        'category' => $d->category,
                        'subcategory' => $d->subcategory,
                        'discount_percent' => $d->discount_percent,
                        'room_charges' => is_array($d->room_charges) ? json_encode($d->room_charges) : ($d->room_charges ?? '[]'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                if (!empty($discounts)) ContractDiscount::insert($discounts);
            }

            // Copy health checks
            if ($contract->contractHealthChecks && $contract->contractHealthChecks->count()) {
                $hcRecords = [];
                foreach ($contract->contractHealthChecks as $hc) {
                    $hcRecords[] = [
                        'contract_id' => $newId,
                        'row_name' => $hc->row_name,
                        'selected_test_ids' => $hc->selected_test_ids,
                        'package_price' => $hc->package_price,
                        'selected_consultation_ids' => $hc->selected_consultation_ids,
                        'consultation_prices' => $hc->consultation_prices,
                        'overhead_allocation' => $hc->overhead_allocation,
                        'approved_cost' => $hc->approved_cost,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                if (!empty($hcRecords)) ContractHealthCheck::insert($hcRecords);
            }

            // Copy locations
            if ($contract->contractLocations && $contract->contractLocations->count()) {
                $locRecords = [];
                foreach ($contract->contractLocations as $l) {
                    $locRecords[] = ['contract_id' => $newId, 'location_id' => $l->location_id, 'created_at' => now(), 'updated_at' => now()];
                }
                if (!empty($locRecords)) ContractLocation::insert($locRecords);
            }

            // Copy parties (ContractPartyData) and maintain external party links
            $partyRows = \App\Models\ContractPartyData::where('custom_field_group_id', $contract->id)->get();
            $partyInsert = [];
            foreach ($partyRows as $p) {
                $row = $p->toArray();
                unset($row['id']);
                $row['custom_field_group_id'] = $newId;
                $partyInsert[] = $row;
            }
            if (!empty($partyInsert)) \App\Models\ContractPartyData::insert($partyInsert);

            // Create approval flow for the new contract
            $this->createApprovalFlow($newContract);

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Contract duplicated and new contract created for extension', 'new_contract_id' => $newId], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to duplicate/extend contract', 'error' => $e->getMessage().$e->getLine()], 500);
        }
    }     
    
    public function updateOrCreateTemplate($newContractData, $contract, $returnTemplate=false){
        // Prevent template updates for finalized contracts
        $finalStates = ['active','expired','completed','terminated'];
        if ($contract && in_array(strtolower($contract->contract_status ?? ''), $finalStates, true)) {
            return null;
        }
            
        if(empty($newContractData)){
            // Determine customer payment and entity type from contract parties if available
            $paymentType = null;
            $entityTypeId = null;
            try {
                $party = optional($contract->contractPartyList->get(1));
                $partyEx = $party ? optional($party->partyDetailsEx) : null;
                $paymentType = $partyEx->payment_type ?? null;
                // Common field names: entity_type or entity_type_id
                $entityTypeId = $partyEx->entity_type ?? $partyEx->entity_type_id ?? null;
            } catch (\Throwable $e) {
                $paymentType = null; $entityTypeId = null;
            }

            // Build base query and prefer most-specific match
            $baseQ = ContractTemplates::where('status', 1)->where('contract_type', $contract->contract_type);
            $templateExist = null;
            if ($paymentType && $entityTypeId) {
                $templateExist = (clone $baseQ)->where('payment_type', $paymentType)->where('entity_type_id', $entityTypeId)->first();
            }
            if (!$templateExist && $paymentType) {
                $templateExist = (clone $baseQ)->where('payment_type', $paymentType)->whereNull('entity_type_id')->first();
            }
            if (!$templateExist && $entityTypeId) {
                $templateExist = (clone $baseQ)->whereNull('payment_type')->where('entity_type_id', $entityTypeId)->first();
            }
            if (!$templateExist) {
                $templateExist = (clone $baseQ)->whereNull('payment_type')->whereNull('entity_type_id')->first();
            }
            if($templateExist){
                $newContractData = $templateExist->template_content;
            }
        }
        
        if(!empty($newContractData)){
            //Store Contract Template
            
            $contractController = new ContractController();
            
            $fileStorageController =  fileStorageTypeController();
            
            $storagePath = '/storage/app/';

            $generateDocPath = $fileStorageController->get_file_path($contract->id);

            $html = $newContractData;
            $html = html_entity_decode($html);

            $phpWord = new PhpWord();
            //$phpWord->getSettings()->setOutputEscapingEnabled(true);
            $section = $phpWord->addSection();
            //echo ($html);
            $html = trim($html, '"');
            $html = str_replace('&amp;', 'and', $html);
            $html = str_replace('<br>', '<br/>', $html);

            $html = $contractController->replaceCharacterAndStylesWord($html);

            //Clause Title Storage
            $pattern = '/clause_title_(.+?)_op/';

            $clauseTitles_ = [];

            if (preg_match_all($pattern, $html, $matches)) {

                if (isset($matches[1])) {
                    foreach ($matches[1] as $ttles) {
                        $clauseTitles_[] = [
                            'clause_category' => $ttles,
                            'contract_id' => $contract->id
                        ];
                    }
                }
            }


            if (count($clauseTitles_) > 0) {
                ClausesContractsLink::insert($clauseTitles_);
            }

            //Replace Custom Vars
            $ContractsFinal = Contract::select('*')->where('id', $contract->id)->get();

            $html = $contractController->replaceWordText('', $html, $ContractsFinal[0], false);
            
            
            // Build Annexures HTML
            $annexHtml = $this->getAnnexures($contract);
            
            
            if(!empty($annexHtml)){
                $html .= $annexHtml;
            }
            
            
            
            if(!$returnTemplate){

                // Add the HTML to the Word document
                Html::addHtml($section, $html, false, true);
    
                $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    
                $generatedDocumentName = 'drafted_contract_' . strtotime(date('d-m-y h:i:s')) . '.docx';
    
                $senattment['filename'][] = $generatedDocumentName;
                
                if (fileStorageType() == "Local") {
                    $finalPath = base_path() . $storagePath . $generateDocPath . '/' . $generatedDocumentName;
                    $writer->save($finalPath);
                    $finalFilePathName = $generateDocPath . '/' . $generatedDocumentName;
                } else {
                    $finalPath = base_path() . '/storage/app/contracts/tempDocs/' . $generatedDocumentName;
                    $writer->save($finalPath);
                    $finalFilePathName = $fileStorageController->storeContent($finalPath, $generateDocPath, $generatedDocumentName);
                    unlink($finalPath);
                }
                
                Contract::where('id', $contract->id)->update(['contract_attachment' => $finalFilePathName, 'contract_attachment_filename' => $generatedDocumentName]);
                // create a history snapshot for updated/generate template PDF
                $contract->refresh();
                $this->createContractSnapshot($contract, 'Contract generated/updated with annexures');
            }else{
                return $html;
            }
        }
        
    }
    

    public function completeSignUpload(Request $request, $id)
    {
        $contract = Contract::find($id);
        
        if (!$contract) {
            return response()->json(['success' => false, 'message' => 'Invalid Contract'], 404);
        }

        if (!$request->hasFile('signed_file') && !$request->filled('signed_file_base64')) {
            return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
        }
        
        $isSignatory = false;
        $userInfo = Helpers::userInfo();
            
        if (!empty($contract->signatory)) {
            $signatory = AddUsers::select('id',  decrypt_data('Email', 'AddUsers'))
                    ->where('id', $contract->signatory)
                    ->first();
            if ($signatory) {
                $ownerEmail = $signatory->Email ?? null;
                
                $currentIdentifier = strtolower($userInfo->email);
                if ($ownerEmail && strtolower($ownerEmail) === $currentIdentifier) {
                    $isSignatory = true;
                }
            }
        }        
        
        $isCreator = false;
        // Check if the current user is the contract creator (supports numeric id or stored JSON/email)
        if (!empty($contract->created_by)) {
            if (intval($contract->created_by) === intval($userInfo->id ?? 0)) {
                $isCreator = true;
            }
        }

        // Allow if signatory or the contract creator (creator allowed only when contract is in Signing/Approved state)
        if (!($isSignatory || $isCreator)) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'.json_encode($userInfo)], 401);
        }

        $validator = \Validator::make($request->all(), [
            'signed_file' => 'sometimes|file|mimes:pdf|max:20480',
            'signed_file_base64' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $storageController = fileStorageTypeController();

        try {
            if ($request->hasFile('signed_file')) {
                $file = $request->file('signed_file');
                $generatedName = 'signed_' . ($contract->contract_unique_id ?? $contract->id) . '.' . $file->getClientOriginalExtension();

                if (fileStorageType() != 'Local') {
                    $filePath = $storageController->storeFile($file, '', $contract->id, $generatedName);
                } else {
                    $generatedPdfDocumentFinal = $generatePdfPath . '/' . $generatedName;
                    Storage::put($generatedPdfDocumentFinal, file_get_contents($file));
                    $filePath = $generatedPdfDocumentFinal;
                }
            }

            //Contract::where('id', $id)->update(['contract_attachment' => $filePath, 'contract_attachment_filename' => $generatedName]);
            
            $cur_date = date('Y-m-d');
            
            $end_date_of_contract = $contract->contract_end_date;
            
            $mainStatus = "executed";
            $subStatusApprvr = "active";

            if (strtotime($cur_date) > strtotime($end_date_of_contract)) {
                $subStatusApprvr = 'expired';
            }                        
                        
            Contract::where('id', $id)->update([
                'contract_attachment' => $filePath, 
                'contract_attachment_filename' => $generatedName,
                'contract_status' => $mainStatus,
                'substatus' => $subStatusApprvr                            
            ]);            

            // create snapshot for signed upload
            $contract->refresh();
            $this->createContractSnapshot($contract, 'Signed file uploaded — contract executed');

            return response()->json(['success' => true, 'message' => 'Signed file saved', 'file' => $filePath], 200);
        } catch (\Exception $e) {
            \Log::error('completeSignUpload error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to save signed file'], 500);
        }
    } 
    
    
    /**
     * Get consultation name by ID
     */
    private function getConsultationName($id)
    {
        
        // Return ID itself if not numeric
        if (!is_numeric($id)) {
            return $id;
        }        
        foreach ($this->consultations as $consult) {
            if ($consult['id'] == $id) {
                return $consult['name'];
            }
        }
        return 'Unknown Consultation';
    }

    /**
     * Get test details by ID
     */
    private function getTestDetails($id)
    {
        foreach ($this->tests as $test) {
            if ($test['id'] == $id) {
                return $test;
            }
        }
        return ['id' => $id, 'name' => 'Unknown Test', 'price' => 0];
    }  
    
    
    public function getRegionAndOverallCounts($locationIds = [])
    {
        /*
         |----------------------------------------
         | Per-region counts
         |----------------------------------------
         */
        $perRegion = DB::table('locations_master as lm')
            ->select(
                'lm.region',
                DB::raw('COUNT(lm.id) as total_locations')
            )
            ->groupBy('lm.region')
            ->whereIn('id', $locationIds)
            ->get();
    
        /*
         |----------------------------------------
         | Overall counts
         |----------------------------------------
         */
        $overall = DB::table('locations_master')
            ->select(
                DB::raw('COUNT(DISTINCT region) as total_regions'),
                DB::raw('COUNT(id) as total_locations')
            )
            ->whereIn('id', $locationIds)
            ->first();
    
        /*
         |----------------------------------------
         | Final Response
         |----------------------------------------
         */
        return [
            'overall' => $overall,
            'per_region' => $perRegion
        ];
    }
  
    
    
    
    //Region Wise
    
    public function analyze(array $locations): array
    {
        $perCity = [];
        $uniqueByType = []; // type => [id => name]

        foreach ($locations as $loc) {
            $cityId = (int) ($loc['city'] ?? 0);
            if ($cityId <= 0) {
                // skip invalid
                continue;
            }

            $hier = $this->buildHierarchy($cityId);

            // make a readable city label
            $cityLabel = $hier['citytown']['name'] ?? $this->getNode($cityId)?->name ?? null;

            $perCity[] = [
                'input' => $loc,
                'hierarchy' => $hier,
                'city_label' => $cityLabel,
            ];

            // collect unique ids for levels we care about
            foreach (['citytown', 'district', 'state', 'region', 'country'] as $type) {
                if (isset($hier[$type])) {
                    $uniqueByType[$type][ (int)$hier[$type]['id'] ] = $hier[$type]['name'];
                }
            }
        }

        // analyze per level if single/multiple/none
        $analysis = [];
        foreach (['citytown', 'district', 'state', 'region' , 'country'] as $type) {
            if (!isset($uniqueByType[$type])) {
                $analysis[$type] = [
                    'count' => 0,
                    'mode' => 'none',
                    'examples' => []
                ];
            } else {
                $count = count($uniqueByType[$type]);
                $analysis[$type] = [
                    'count' => $count,
                    'mode' => ($count === 1) ? 'single' : 'multiple',
                    'examples' => array_values($uniqueByType[$type])
                ];
            }
        }

        return [
            'per_city' => $perCity,
            'analysis' => $analysis
        ];
    }

    /**
     * Build full ancestor chain for a starting node id.
     * Returns associative array keyed by type:
     * e.g. ['citytown' => ['id'=>..,'name'=>..,'type'=>..], 'district' => ..., ... ]
     *
     * @param int $startId
     * @return array
     */
    public function buildHierarchy(int $startId): array
    {
        $chain = [];
        $visitedIds = [];

        $currentId = $startId;

        while ($currentId !== null && $currentId !== '' && $currentId !== 0) {
            // break if loop detected
            if (in_array($currentId, $visitedIds, true)) {
                break;
            }
            $visitedIds[] = $currentId;

            $node = $this->getNode($currentId);
            if (! $node) {
                break;
            }

            // put in chain by type
            $chain[$node->type] = [
                'id' => (int)$node->id,
                'name' => $node->name,
                'type' => $node->type
            ];

            $parent = $node->parent;

            if ($parent === null || $parent === '') {
                break;
            }

            if (is_numeric($parent)) {
                $nextId = (int)$parent;
                // avoid infinite loop if something points to itself
                if ($nextId === (int)$node->id) {
                    break;
                }
                $currentId = $nextId;
                continue;
            }

            // parent is non-numeric: try to resolve by name (fallback)
            $parentNode = $this->getNodeByName($parent);
            if ($parentNode) {
                $currentId = (int)$parentNode->id;
                continue;
            }

            // nothing else to do
            break;
        }

        return $chain;
    }

    /**
     * Fetch node by id and cache it.
     *
     * @param int $id
     * @return GeographicalHierarchy|null
     */
    protected function getNode(int $id): ?GeographicalHierarchy
    {
        if (isset($this->cacheById[$id])) {
            return $this->cacheById[$id];
        }

        $node = GeographicalHierarchy::find($id);
        if ($node) {
            $this->cacheById[$id] = $node;
            // also populate name cache
            $this->cacheByName[$node->name] = $node;
        } else {
            $this->cacheById[$id] = null;
        }
        return $this->cacheById[$id];
    }

    /**
     * Fetch node by name (first match) and cache it.
     *
     * @param string $name
     * @return GeographicalHierarchy|null
     */
    protected function getNodeByName(string $name): ?GeographicalHierarchy
    {
        if (isset($this->cacheByName[$name])) {
            return $this->cacheByName[$name];
        }

        $node = GeographicalHierarchy::where('name', $name)->first();
        if ($node) {
            $this->cacheByName[$name] = $node;
            $this->cacheById[$node->id] = $node;
        } else {
            $this->cacheByName[$name] = null;
        }
        return $this->cacheByName[$name];
    }   
    

    /**
     * API: Return list of locations from LocationMaster (used by contract JS)
     */
    public function apiLocations(Request $request)
    {
        try {
            $locations = LocationMaster::select('id', 'location_name', 'region')
                ->orderBy('location_name')
                ->get()
                ->map(function($l){
                    return ['id' => $l->id, 'name' => $l->location_name, 'region' => $l->region];
                });

            return response()->json($locations, 200);
        } catch (Exception $e) {
            return response()->json([], 500);
        }
    }  
    
    
    public function getAnnexures($contract, $annexHtml=''){
        

        // Locations table (with serial numbers)
        $locRows = '';
        $locSn = 1;
        foreach ($contract->contractLocations ?? [] as $cl) {
            $lm = optional($cl->location);
            $name = $lm->location_name ?? ('Location #' . ($cl->location_id ?? ''));
            $parts = array_filter([ $lm->address ?? '', $lm->city ?? '', $lm->state ?? '', $lm->country ?? '', $lm->pincode ?? '' ]);
            $addr = implode(', ', $parts);
            $locRows .= "<tr><td style='padding:6px;border:1px solid #ddd;text-align:center'>{$locSn}</td><td style='padding:6px;border:1px solid #ddd'>{$name}</td><td style='padding:6px;border:1px solid #ddd'>{$addr}</td></tr>";
            $locSn++;
        }
        if ($locRows !== '') {
            $annexHtml .= "<div style='page-break-before: always;'></div><h3>Annexure A — Locations</h3>";
            $annexHtml .= "<table style='border-collapse:collapse;width:100%'><thead><tr><th style='padding:6px;border:1px solid #ddd;width:48px'>#</th><th style='padding:6px;border:1px solid #ddd'>Name</th><th style='padding:6px;border:1px solid #ddd'>Address</th></tr></thead><tbody>{$locRows}</tbody></table>";
        }

        // Discount details (with serial numbers)
        $discRows = '';
        $hideRoomCharges = true;
        $discSn = 1;
        foreach ($contract->contractDiscounts ?? [] as $d) {
            $roomCharges = is_string($d->room_charges) ? $d->room_charges : (is_array($d->room_charges) ? json_encode($d->room_charges) : '');
            //return $roomCharges;
            
            $roomChargeDetails = '';
            if($roomCharges != '[]'){
                $items = json_decode($roomCharges, true);
                //return $roomCharges;
                foreach ($items as $item){
                    $roomChargeDetails .= "<p><strong>{$item['name']}</strong> - ";
                    $roomChargeDetails .= "{$item['price']}</p>";
                }               
            }

            $roomCharges = ($roomCharges == '[]' ? '' : $roomCharges);
            if($hideRoomCharges && !empty($roomCharges)){
                $hideRoomCharges = false;
            }
            
            $pct = number_format(floatval($d->discount_percent ?? 0), 2);
            $discRows .= "<tr><td style='padding:6px;border:1px solid #ddd;text-align:center'>{$discSn}</td><td style='padding:6px;border:1px solid #ddd'>{$d->category}</td><td style='padding:6px;border:1px solid #ddd'>{$d->subcategory}</td><td style='padding:6px;border:1px solid #ddd'>".($pct > 0 ? $pct : 'NA') ."</td><td style='padding:6px;border:1px solid #ddd; display: ".($hideRoomCharges ? 'none' : '')."'>{$roomChargeDetails}</td></tr>";
            $discSn++;
        }
        if ($discRows !== '') {
            $annexHtml .= "<div style='page-break-before: always;'></div><h3>Annexure B — Discounts</h3>";
            $annexHtml .= "<table style='border-collapse:collapse;width:100%'><thead><tr><th style='padding:6px;border:1px solid #ddd;width:48px'>#</th><th style='padding:6px;border:1px solid #ddd'>Category</th><th style='padding:6px;border:1px solid #ddd'>Subcategory</th><th style='padding:6px;border:1px solid #ddd'>Discount %</th><th style='padding:6px;border:1px solid #ddd; display: ".($hideRoomCharges ? 'none' : '')."'>Room Charges</th></tr></thead><tbody>{$discRows}</tbody></table>";
        }

        // Health package details
        $hcRows = '';
        foreach ($contract->contractHealthChecks ?? [] as $hc) {
            $testsList = [];
            $testIds = is_string($hc->selected_test_ids) ? @json_decode($hc->selected_test_ids, true) : $hc->selected_test_ids;
            if (!empty($testIds) && is_array($testIds)) {
                foreach ($testIds as $tid) {
                    $td = $this->getTestDetails($tid);
                    $testsList[] = $td['name'] ?? ($td['id'] ?? $tid);
                }
            }
            $consultList = [];
            $consIds = is_string($hc->selected_consultation_ids) ? @json_decode($hc->selected_consultation_ids, true) : $hc->selected_consultation_ids;
            if (!empty($consIds) && is_array($consIds)) {
                foreach ($consIds as $cid) {
                    $consultList[] = $this->getConsultationName($cid);
                }
            }
            $testsText = htmlspecialchars(implode(', ', $testsList));
            $consText = htmlspecialchars(implode(', ', $consultList));
            $pkgPrice = number_format(floatval($hc->package_price ?? 0), 2);
            $hcRows .= "<tr><td style='padding:6px;border:1px solid #ddd'>{$hc->row_name}</td><td style='padding:6px;border:1px solid #ddd'>₹{$pkgPrice}</td><td style='padding:6px;border:1px solid #ddd'>{$testsText}</td><td style='padding:6px;border:1px solid #ddd'>{$consText}</td></tr>";
        }
        if ($hcRows !== '') {
            $annexHtml .= "<div style='page-break-before: always;'></div><h3>Annexure C — Health Packages</h3>";
            $annexHtml .= "<table style='border-collapse:collapse;width:100%'><thead><tr><th style='padding:6px;border:1px solid #ddd'>Package</th><th style='padding:6px;border:1px solid #ddd'>Price</th><th style='padding:6px;border:1px solid #ddd'>Tests</th><th style='padding:6px;border:1px solid #ddd'>Consultations</th></tr></thead><tbody>{$hcRows}</tbody></table>";
        }
        
        // If Prevailing Hospital Tariff file exists, attempt to include it as Annexure D
        try {
            $conf = !empty($contract->confidentialityagreement) ? @json_decode($contract->confidentialityagreement, true) : [];
            if (!empty($conf['prevailing_file'])) {
                $prevPath = $conf['prevailing_file'];
                $prevName = $conf['prevailing_file_name'] ?? basename($prevPath);
                // Only include if doc/docx present; convert to HTML and append
                if (preg_match('/\.(doc|docx)$/i', $prevName)) {
                    // Use ContractController helper to convert Word to HTML
                    $cc = new \Modules\Contract\Http\Controllers\ContractController();
                    $storedFile = null;
                    $unlinkTemp = false;

                    // Local storage: use direct path
                    if (fileStorageType() == 'Local') {
                        $localPath = storage_path('app/' . $prevPath);
                        if (file_exists($localPath)) {
                            $storedFile = $localPath;
                        }
                    } else {
                        // Remote storage: attempt to download a temporary local copy
                        try {
                            $file_name = 'prev_' . strtotime(date('y-m-d h:i:s')) . '_' . basename($prevPath);
                            $file_path = 'contracts/tempDocs/';
                            $content = fileStorageTypeController()->downloadUrl($prevPath, $file_name);

                            if ($content) {
                                Storage::disk('local')->put($file_path . $file_name, $content);
                                $storedFile = base_path() . '/storage/app/' . $file_path . $file_name;
                                $unlinkTemp = $file_path . $file_name;
                            }
                        } catch (\Throwable $e) {
                            \Log::error('Failed to download prevailing file: ' . $e->getMessage());
                            $storedFile = null;
                        }
                    }

                    if ($storedFile && file_exists($storedFile)) {
                        try {
                            $buf = $cc->convertWordToHtmlBuffer($storedFile);
                            if ($buf) {
                                $annexHtml .= "<div style='page-break-before: always;'></div><h3>Annexure D — Prevailing Hospital Tariff</h3>";
                                $annexHtml .= "<div class='prevailing-doc'>" . $buf . "</div>";
                            } else {
                                // fallback: provide link to file
                                $annexHtml .= "<div style='page-break-before: always;'></div><h3>Annexure D — Prevailing Hospital Tariff</h3>";
                                $annexHtml .= "<p><a href='" . asset('storage/' . $prevPath) . "' target='_blank'>Download tariff document: " . e($prevName) . "</a></p>";
                            }
                        } catch (\Throwable $e) {
                            \Log::error('Failed to convert prevailing doc: ' . $e->getMessage());
                            $annexHtml .= "<div style='page-break-before: always;'></div><h3>Annexure D — Prevailing Hospital Tariff</h3>";
                            $annexHtml .= "<p><a href='" . asset('storage/' . $prevPath) . "' target='_blank'>Download tariff document: " . e($prevName) . "</a></p>";
                        }

                        // Clean up temporary file if we downloaded it
                        if ($unlinkTemp) {
                            try {
                                Storage::disk('local')->delete($unlinkTemp);
                            } catch (\Throwable $e) {
                                // ignore cleanup errors
                            }
                        }
                    } else {
                        // Link fallback if we couldn't access or convert the file
                        $annexHtml .= "<div style='page-break-before: always;'></div><h3>Annexure D — Prevailing Hospital Tariff</h3>";
                        $annexHtml .= "<p><a href='" . asset('storage/' . $prevPath) . "' target='_blank'>Download tariff document: " . e($prevName) . "</a></p>";
                    }
                } else {
                    // Link fallback
                    $annexHtml .= "<div style='page-break-before: always;'></div><h3>Annexure D — Prevailing Hospital Tariff</h3>";
                    $annexHtml .= "<p><a href='" . asset('storage/' . $prevPath) . "' target='_blank'>Download tariff document: " . e($prevName) . "</a></p>";
                }
            }
        } catch (\Throwable $e) {
            \Log::error('getAnnexures prevailing include failed: ' . $e->getMessage());
        }        
            
        return $annexHtml;
    }
    
}