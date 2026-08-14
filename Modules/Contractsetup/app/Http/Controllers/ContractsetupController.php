<?php

namespace Modules\Contractsetup\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
use App\Models\CustomVarDocs;
use App\Models\EntityBusiness;
use App\Models\ContractCategories;
use App\Models\FileStorage;
use App\Helpers\Helpers;
use App\Models\ClausesCategory;
use App\Models\ClauseList;
use App\Models\AgreementTemplate;
use App\Models\ContractGroup;

use Exception;
use Rap2hpoutre\FastExcel\FastExcel;

class ContractsetupController extends Controller
{
    
    public function __construct()
    {
      if(Controller::checkCurrentAuth("Contracts") != 1){
          return abort('404');
      }
    }
    
    public function fileConfig()
    {
        $file = FileStorage::where('id', 1)->value('type'); 
        return view('contract-setup::fileconfig.viewConfig')->with('file',$file);
    }

    public function fileConfigStore(Request $request)
    {
        if(Helpers::userInfo()->email == 'admin@legalitysimplified.com'){
          FileStorage::where('id', 1)->update(['type' => $request->fileStorage]);
          return redirect()->back()->with('response', 'success')->with('message', 'updated successfully');            
        }else{
            return redirect()->back()->with('response', 'danger')->with('message', 'Sorry! Invalid Operation Contact Admin'); 
        }

    }
    
    public function index()
    {
        $customFields = CustomFields::where('status', 1)->where('contract_type', 1)->get();
        $categorys = Category::where('category_group', 'contract')->get();
        $contractTypes = ContractType::get();

        return view('contract-setup::contract.createfield')->with('contractTypes', $contractTypes)->with('categorys', $categorys)->with('lists', $customFields);
    }



    public function indexParty()
    {
        $customFields = CustomFields::where('status', 1)->where('contract_type', 1)->get();
        $categorys = Category::where('category_group', 'party')->get();
        $contractTypes = ContractType::get();

        return view('contract-setup::contract.partyCustomField')->with('contractTypes', $contractTypes)->with('categorys', $categorys)->with('lists', $customFields);
    }
    
    public function indexPartyIndividual()
    {
        $customFields = CustomFields::where('status', 1)->where('contract_type', 1)->get();
        $categorys = Category::where('category_group', 'iparty')->get();
        $contractTypes = ContractType::get();

        return view('contract-setup::contract.partyIndividualCustomField')->with('contractTypes', $contractTypes)->with('categorys', $categorys)->with('lists', $customFields);
    }



    public function list(Request $request)
    {
        $categorys = Category::where('category_group', 'contract')->get();
        $contractTypes = ContractType::get();
        $contracttype = isset($request->contracttype) ? $request->contracttype : '1';
        // Generic fields apply to every contract type, so they are listed alongside the
        // fields belonging to the selected type and stay editable from any of them.
        $customFields = CustomFields::where('status', 1)
            ->where(function ($query) use ($contracttype) {
                $query->where('contract_type', $contracttype)
                    ->orWhere(function ($generic) {
                        $generic->where('is_generic', 1)->where('sub_type', 'contract');
                    });
            })
            ->orderBy('order_id')->get();
        $currentcontractType = ContractType::where('contract_type_id', $contracttype)->first();


        return view('contract-setup::contract.createfieldlist')->with('currentcontractType', $currentcontractType)->with('contractTypes', $contractTypes)->with('lists', $customFields)->with('categorys', $categorys);
    }

    public function indexPartyList(Request $request)
    {


        $categorys = Category::where('category_group', 'party')->get();
        $contractTypes = ContractType::get();
        $contracttype = 0;
        $customFields = CustomFields::where('status', 1)->where('contract_type', $contracttype)->where('sub_type', 'party')->orderBy('order_id')->get();
        $currentcontractType = ContractType::where('contract_type_id', $contracttype)->first(); 

        return view('contract-setup::contract.partyCustomFieldlist')->with('currentcontractType', $currentcontractType)->with('contractTypes', $contractTypes)->with('lists', $customFields)->with('categorys', $categorys);

    }
    
    public function indexPartyIndividualList(Request $request)
    {


        $categorys = Category::where('category_group', 'iparty')->get();
        $contractTypes = ContractType::get();
        $contracttype = 0;
        $customFields = CustomFields::where('status', 1)->where('contract_type', $contracttype)->where('sub_type', 'iparty')->orderBy('order_id')->get();
        $currentcontractType = ContractType::where('contract_type_id', $contracttype)->first(); 

        return view('contract-setup::contract.partyCustomFieldlist')->with('currentcontractType', $currentcontractType)->with('contractTypes', $contractTypes)->with('lists', $customFields)->with('categorys', $categorys);

    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required',
            'type' => 'required',
            'category' => 'required',
            'contracttype' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            $customField = new CustomFields();
            $customField->category = $request->category;
            $customField->field_name = $request->label;
            $customField->field_type = $request->type;
            $customField->contract_type = isset($request->contracttype) ? $request->contracttype : 1;
            $customField->required = isset($request->required) ? $request->required : 0;
            $customField->sub_type = $request->subtype;
            $customField->is_generic = $request->has('is_generic') ? 1 : 0;
            
            $customField->field_default_value = $request->type === 'tablename'
                ? ($request->tablename ?? null)
                : ($request->val ?? null);

            $customField->save();

            $customTimeline = new CustomFieldsTimeline();
            $customTimeline->custom_field_id = $customField->id;
            $customTimeline->updated_by = 1;
            $customTimeline->action = 'created';
            $customTimeline->data = json_encode($customField);
            $customTimeline->save();


            return response()->json(['message' => 'Form submitted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }



    public function update(Request $request)
    {
 
        foreach ($request->custom_fields as $field) {
                
            CustomFields::where('custom_field_id', $field['id'])->update([
                'field_name' => $field['name'],
                'category' => $field['group'] ? $field['group'] : 1,
                'field_type' => $field['type'],
                'field_default_value' => $field['value'],
                'required' => isset($field['required']) ? 1 : 0,
                'is_generic' => isset($field['is_generic']) ? 1 : 0,
                'order_id' => $field['order'],
                'status' => $field['status'],
            ]);
            if ($field['status'] == 0) {
                $customField = new CustomFieldsTimeline();
                $customField->custom_field_id = $field['id'];
                $customField->updated_by = 1;
                $customField->action = 'deleted';
                $customField->data = json_encode($field);
                $customField->save();
            } else {
                $customField = new CustomFieldsTimeline();
                $customField->custom_field_id = $field['id'];
                $customField->updated_by = 1;
                $customField->action = 'updated';
                $customField->data = json_encode($field);
                $customField->save();
            }
        }
        return response()->json(['message' => 'Form submitted successfully'], 200);
    }
    
    
    public function contractTypeList()
    {
        return view('contract-setup::contractType.list');
    }    
    
    public function contractTypeListData(Request $request)
    {
        try
        {

            $data = DB::table('contract_type')
            //->join('contract_categories', 'contract_type.categoryId', '=', 'contract_categories.id')
            ->leftjoin("contract_categories",\DB::raw("FIND_IN_SET(contract_categories.id,contract_type.categoryId)"),">",\DB::raw("'0'"))
            ->join('entitybusiness', 'contract_type.departmentId', '=', 'entitybusiness.id')
            ->where('contract_type.status',1) 
            ->select('contract_type.*',\DB::raw("GROUP_CONCAT(contract_categories.name) as category_name"), 'entitybusiness.name as business_name')
            ->groupBy("contract_type.contract_type_id")
            ->get()->toArray();
            
            return response()->json([
                'data' => $data,
                'draw' => $request->input('draw') ?? 1,
                'recordsTotal' => count($data),
                'recordsFiltered' => count($data),
            ]);
            
        }catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();      
            return $message;
         }
        
        

        // return view('contract-setup::contract.contractTypeList', compact('data'));
    }

    public function contractTypeCreate()
    {
        $catego =  ContractCategories::select('*')->get();
        $ent = EntityBusiness::select('*')->get();
        $catGroup = ContractGroup::select('*')->get();
        return view('contract-setup::contractType.create', compact('catego','ent', 'catGroup'));
    }
    
    public function contractTypeEdit(Request $request , $id)
    {
        $contractTypeExist = ContractType::where('contract_type_id', $request->id)->first();
        
        if(!$contractTypeExist){
            return redirect()->back()->with('message', 'Invalid Contract Type')->with('alert-class', "alert-danger");
        }
        $catego =  ContractCategories::select('*')->get();
        $ent = EntityBusiness::select('*')->get();
        $catGroup = ContractGroup::select('*')->get();
        return view('contract-setup::contractType.edit', compact('catego','ent', 'catGroup', 'contractTypeExist'));
    }
    
    
    public function contractTypeStore(Request $request){

        try {
            $maxNumber = ContractType::where('group_id', $request->GroupName)->count('contract_type');
            $catPrefix = ContractGroup::where('id', $request->GroupName)->select('categoryId')->first();
            $catSuffix = sprintf('%02d', $maxNumber+1);
            if (isset($request->id)) {
                
                $validator = Validator::make($request->all(), [
                    'contractTypeName' => 'required',
                    'contractShortName' => 'required',
                    'DepartmentType' => 'required',
                    'catgoeryType.*' => 'required'
                ]);
                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator)->withInput();
                }                
                ContractType::where('contract_type_id', $request->id)->update([
                    'contract_type' => $request->contractTypeName,
                    'short_name' => $request->contractShortName,
                    'departmentId' => $request->DepartmentType,
                    'categoryId' => implode(',', $request->catgoeryType ?? []),
                    'group_id' => $request->GroupName,
                    'type_unique_id' => $catPrefix->categoryId.$catSuffix
                ]);
                return redirect()->back()->with('success', 'Updated successfully');
            } else {
                
                $validator = Validator::make($request->all(), [
                    'contractTypeName' => 'required|unique:contract_type,contract_type',
                    'contractShortName' => 'required|unique:contract_type,short_name',
                    'DepartmentType' => 'required',
                    'catgoeryType.*' => 'required'
                ]);
                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator)->withInput();
                }                
                $contractTypeName = new ContractType();
                $contractTypeName->contract_type = $request->contractTypeName;
                $contractTypeName->short_name = $request->contractShortName; 
                $contractTypeName->departmentId = $request->DepartmentType;
                $contractTypeName->categoryId = implode(',', $request->catgoeryType ?? []); 
                $contractTypeName->group_id = $request->GroupName; 
                $contractTypeName->type_unique_id = $catPrefix->categoryId.$catSuffix; 
                $contractTypeName->applicable = '1'; 
                $contractTypeName->save();
                return redirect()->back()->with('success', 'Created successfully');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function contractImport()
    {
        return view('contract-setup::contract.contractCreateType');
    }
    public function contractImportStore(Request $request){
        
        
       $file = $request->file('file');
 
        $collection = (new FastExcel)->import($file);
        
        
        try {
            foreach($collection as $collectio){
                $contractTypeName = new ContractType();
                $contractTypeName->contract_type = $collectio['Contract Type Name'];
                $contractTypeName->departmentId = EntityBusiness::where('name', $collectio['Department'])->first()->id;
                $contractTypeName->categoryId = ContractCategories::where('name', $collectio['Category'])->first()->id;
                $contractTypeName->save();
            }
            return redirect()->back()->with('successupload', 'Data imported successfully.');
        } catch (\Exception $e) {

            return redirect()->back()->with('error', $e->getMessage());
        }
        
    }
    
    
   
    public function contractTypeDelete(Request $request, $id)
    {
        // return $request->all();
        $contractExist = Contract::where('contract_type', $id)->count();
        if($contractExist == 0){
            $contractType = ContractType::where('contract_type_id', $id)->update([
                'status'=>'0'
            ]);
            $message = 'Deleted Successfully';
            $messageClass = 'success';
        }else{
            $message = 'Cannot Delete this contract type data exist for this type.';
            $messageClass = 'danger';            
        }
        return redirect()->back()->with('message', $message)->with('alert-class', "alert-$messageClass");

    }


    //Clauses Setup Methods

    public function clauseConfigSetup()
    {
        $customFields = ClauseList::where('status', 1)->get();
        $categorys = ClausesCategory::where('category_group', 'title', 'required')->get();
        $contractTypes = ContractType::get();

        return view('contract-setup::contract.clauses')->with('contractTypes', $contractTypes)->with('categorys', $categorys)->with('lists', $customFields);
    }
    
    public function clauseConfigTitleList(Request $request)
    {
        $search = $request->input('term'); // Get the search term from Select2
    
        $query = ClausesCategory::selectRaw('category_name as text, category_id as id, required as optional')
            ->where('category_group', 'title');
    
        if (!empty($search)) {
            $query->where('category_name', 'like', '%' . $search . '%');
        }
    
        $categories = $query->get(); // Limit to 20 results
    
        return response()->json([
            'results' => $categories
        ]);
    }
    
    public function clauseConfigList(Request $request)
    {

        $categorys = ClausesCategory::where('category_group', 'title')->get();
        $contractTypes = ContractType::get();
        $contracttype = $request->contracttype ?? 0;
        $customFields = ClauseList::where('status', 1)->where('contract_type', $contracttype)->orderBy('order_id')->get();
        $currentcontractType = ContractType::where('contract_type_id', $contracttype)->first(); 

        return view('contract-setup::contract.clauseList')->with('currentcontractType', $currentcontractType)->with('contractTypes', $contractTypes)->with('lists', $customFields)->with('categorys', $categorys);

    }
    
    public function clauseConfigStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'val' => 'required',
            'type' => 'required',
            'category' => 'required',
            'contracttype' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            $customField = new ClauseList();
            $customField->category = $request->category;
            $customField->field_name = isset($request->label) ? $request->label : $request->category."_label_".strtotime('dmyhis');
            $customField->field_type = $request->type;
            $customField->contract_type = isset($request->contracttype) ? $request->contracttype : 1;
            $customField->required = isset($request->required) ? $request->required : 0;
            $customField->field_default_value = isset($request->val) ? $request->val : null;
            $customField->save();

            $customTimeline = new CustomFieldsTimeline();
            $customTimeline->custom_field_id = $customField->id;
            $customTimeline->updated_by = 1;
            $customTimeline->action = 'created';
            $customTimeline->data = json_encode($customField);
            $customTimeline->save();

            return response()->json(['message' => 'Form submitted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }
    
    public function clauseConfigUpdate(Request $request)
    {
 
        foreach ($request->custom_fields as $field) {


            ClauseList::where('custom_field_id', $field['id'])->update([
                'field_name' => $field['name'],
                'category' => $field['group'] ? $field['group'] : 1,
                'field_type' => $field['type'],
                'field_default_value' => $field['value'],
                'required' => isset($field['required']) ? 1 : 0,
                'order_id' => $field['order'],
                'status' => $field['status'],
            ]);
        }
        return response()->json(['message' => 'Form submitted successfully'], 200);
    }    
    
    public function clauseConfigTitleStore(Request $request)
    {
        $rules = [
            'required_title' => 'nullable|in:0,1', // Optional validation
        ];
    
        if (isset($request->category_id) && $request->category_id > 0) {
            // Update: skip unique validation for current category_id
            $rules['clauseTitle'] = [
                'required',
                Rule::unique('clauses_category', 'category_name')->ignore($request->category_id, 'category_id')
            ];
        } else {
            // Create: apply full unique check
            $rules['clauseTitle'] = 'required|unique:clauses_category,category_name';
        }
    
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 200);
        }
    
        try {
            $required = $request->has('required_title') ? $request->required_title : 0;
    
            if (isset($request->category_id) && $request->category_id > 0) {
                // Update existing category
                ClausesCategory::where('category_id', $request->category_id)->update([
                    'category_name' => $request->clauseTitle,
                    'required' => $required
                ]);
                return response()->json(['success' => true, 'message' => 'Title Updated Successfully'], 200);
            } else {
                // Create new category
                $contractTypeName = new ClausesCategory();
                $contractTypeName->category_name = $request->clauseTitle;
                $contractTypeName->category_group = 'title';
                $contractTypeName->required = $required;
                $contractTypeName->save();
    
                return response()->json(['success' => true, 'message' => 'Title Created Successfully'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 200);
        }
    }

    
    //Custom Variable Setup For Contract Templates

    public function customVariableConfigSetup()
    {
        $customVars = CustomVarDocs::where('status', 1)->get();

        return view('contract-setup::admin.customVarSetup')->with('customVars', $customVars);
    }
    
    //Store Custom Variable
    public function customVariableStore(Request $request){

        if (isset($request->varId)) {
            $validator = Validator::make($request->all(), [
                'varTitle' => ['required', Rule::unique('custom_var_docs', 'var_field')->ignore($request->varId, 'var_id')],
                'VarText' => ['required', Rule::unique('custom_var_docs', 'var_var')->ignore($request->varId, 'var_id')],
            ]);
        }else{
            $validator = Validator::make($request->all(), [
                'varTitle' => 'required|unique:custom_var_docs,var_field',
                'VarText' => 'required|unique:custom_var_docs,var_var'
            ]);
        }
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 200);
        }
        try {
            if (isset($request->varId)) {
                CustomVarDocs::where('var_id', $request->varId)->update([
                    'var_field' => $request->varTitle,
                    'var_var' => '${'.str_replace(' ', '_', $request->VarText).'}',
                    'var_disp_var' => $request->VarText,
                    'var_table' => $request->varTables
                ]);
                return response()->json(['success' => true, 'message' => 'Custom Variable Updated Successfully'], 200);
            } else {
                $customVarAdd = new CustomVarDocs();
                $customVarAdd->var_field = $request->varTitle;
                $customVarAdd->var_table = $request->varTables;
                $customVarAdd->var_disp_var = $request->VarText;
                $customVarAdd->var_var = '${'.str_replace(' ', '_', $request->VarText).'}';
                $customVarAdd->save();
                return response()->json(['success' => true, 'message' => 'Custom Variable Created Successfully'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 200);
        }
    }
    
    //Edit Custom Variable
    public function customVariableEdit(Request $request)
    {
        $customVars = CustomVarDocs::where('status', 1)->where('var_id', $request->var_token)->get();
        if(count($customVars) == 1){
            return response()->json(['success' => true, 'message' => 'Retrieved Vars Successfully', 'data'=>$customVars[0]], 200);
        }else{
            return response()->json(['success' => false, 'message' => 'Vars Not Available'], 200);
        }
    } 
    
    public function customVariableListObject()
    {
        $customVars = CustomVarDocs::where('status', 1)->pluck('var_disp_var', 'var_var');
        
        return response()->json(['success' => true, 'message' => 'Retrieved Vars Successfully', 'data'=>$customVars], 200);
    }    
    
    //Showing Clause List in File Template
    public function clauseConfigBaseTemplate(Request $request, $type="all")
    {
        $categorys = ClausesCategory::where('category_group', 'title')->get();
        $contractTypes = ContractType::get();
        $customFields = ClauseList::where('status', 1)->where('contract_type', 0)->orderBy('order_id')->get();
        // Look for a template with the best match of (contract_type, payment_type, entity_type_id)
        // Request may include: contracttype, payment_type (cash/credit), entity_type_id
        $contractType = $request->contracttype ?? 0;
        $paymentType = $request->payment_type ?? null; // expected 'Cash' or 'Credit' or null
        $entityTypeId = $request->entity_type_id ?? null;

        // Base query
        $baseQ = AgreementTemplate::where('status', 'published')->where('contract_type', $contractType);

        // Try most specific: both payment & entity
        $templateExist = null;
        if ($paymentType && $entityTypeId) {
            $templateExist = (clone $baseQ)->where('payment_type', $paymentType)->where('entity_type_id', $entityTypeId)->first();
        }
        // Next: payment only
        if (!$templateExist && $paymentType) {
            $templateExist = (clone $baseQ)->where('payment_type', $paymentType)->whereNull('entity_type_id')->first();
        }
        // Next: entity only
        if (!$templateExist && $entityTypeId) {
            $templateExist = (clone $baseQ)->whereNull('payment_type')->where('entity_type_id', $entityTypeId)->first();
        }
        // Fallback: contract_type only
        if (!$templateExist) {
            $templateExist = (clone $baseQ)->whereNull('payment_type')->whereNull('entity_type_id')->first();
        }

        if($type == 'all'){
            return view('contract-setup::contract.clauseTemplateSelection')->with('contractTypes', $contractTypes)->with('lists', $customFields)->with('categorys', $categorys);
        }else{
            if($templateExist){
                return $templateExist->template_html ?? $templateExist->template_content ?? '';
            }
            return view('contract-setup::contract.clauseTemplateContent')->with('contractTypes', $contractTypes)->with('lists', $customFields)->with('categorys', $categorys);
        }
    
    }
    
    //Build Clause Template
    public function clauseConfigTemplateAdd(Request $request, $type="all")
    {

        $contractTypes = ContractType::get();
        return view('contract-setup::contract.clauseTemplate', compact('contractTypes'));

    }
    
    //Build Clause Template and Store
    public function clauseConfigTemplateStore(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'templatebuilded' => 'required'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 200);
        }
        // Use combination (contract_type + payment_type + entity_type_id) to find existing record
        $contractType = $request->contracttype ?? 0;
        $paymentType = $request->payment_type ?? null;
        $entityTypeId = $request->entity_type_id ?? null;

        // Try to find exact match first
        $query = AgreementTemplate::where('status', 'published')->where('contract_type', $contractType);
        if ($paymentType) $query = $query->where('payment_type', $paymentType); else $query = $query->whereNull('payment_type');
        if ($entityTypeId) $query = $query->where('entity_type_id', $entityTypeId); else $query = $query->whereNull('entity_type_id');

        $templateExist = $query->first();
        try {
            if ($templateExist) {
                // Update the exact record
                $templateExist->update(['template_html' => rawurldecode($request->templatebuilded)]);
                return response()->json(['success' => true, 'operation'=>'update', 'message' => 'Template Updated Successfully'], 200);
            } else {
                // Create new entry with the specified attributes
                $newTemplate = new AgreementTemplate();
                $newTemplate->template_html = rawurldecode($request->templatebuilded);
                $newTemplate->contract_type = $contractType;
                $newTemplate->payment_type = $paymentType;
                $newTemplate->entity_type_id = $entityTypeId;
                $newTemplate->status = 'published';
                $newTemplate->version_no = 1;
                $newTemplate->created_by = auth()->id();
                $newTemplate->updated_by = auth()->id();
                $newTemplate->save();
                return response()->json(['success' => true, 'operation'=>'create', 'message' => 'Template Created Successfully'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 200);
        }

    }
    
    
    //Category Add
    public function categoryConfigTitleStore(Request $request){
    
        $validator = Validator::make($request->all(), [
            'categoryTitle' => 'required|unique:contract_categories,name'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 200);
        }
        try {
            if (isset($request->id)) {
                ContractCategories::where('contract_type_id', $request->id)->update([
                    'name' => $request->categoryTitle
                ]);
                return response()->json(['success' => true, 'message' => 'Title Updated Successfully'], 200);
            } else {
                $contractTypeName = new ContractCategories();
                $contractTypeName->name = $request->categoryTitle;
                $contractTypeName->save();
                return response()->json(['success' => true, 'message' => 'Category Created Successfully', 'data'=>['id'=>$contractTypeName->id, 'name'=>$request->categoryTitle]], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 200);
        }
    } 
    
    public function customFieldsGet(Request $request){
            
        $query = CustomFields::query();

        // allow optional filters (useful if you want to limit custom fields by contract_type etc.)
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('sub_type')) {
            $query->where('sub_type', $request->query('sub_type'));
        }

        if (!$request->has('contract_type')) {
            $query->where('contract_type', '>', 0);
        }else{
            $query->where('contract_type', $request->query('contract_type'));
        }

        // Select columns the frontend needs
        $fields = $query->select([
            'custom_field_id',
            'field_name',
            'field_type',
            'category',
            'contract_type',
        ])->get();

        return response()->json($fields);        
    }
}