@extends('layouts/layoutMaster')
@section('title', ' New Contract Form')
{{-- Contract Create V3. Cloned from contractCreate.blade.php; adds tenure, price revision
     terms, per-party vendor/address/contact fields and the annexure upload block. --}}
<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/quill/typography.scss',
'resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/quill/editor.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/dropzone/dropzone.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/tagify/tagify.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/quill/katex.js',
'resources/assets/vendor/libs/quill/quill.js',
'resources/assets/vendor/libs/cleavejs/cleave.js',
'resources/assets/vendor/libs/tagify/tagify.js',
'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
'resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/dropzone/dropzone.js',
'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
'resources/assets/vendor/libs/jquery-sticky/jquery-sticky.js'
])

<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')

@vite(['resources/assets/js/forms-file-upload.js'])
@vite(['resources/assets/js/form-layouts.js'])

{{-- Serve V3 party rows (with vendor code / address / contact) from "Add more parties". --}}
<script>
   window.PARTY_ROW_URL = '/contracts/create/parties-v3';
   window.ANNEXURE_LIST_URL = '{{ route('contract-setup.annexures.getList') }}';
   window.ANNEXURE_SAMPLE_BASE_URL = '{{ url('/contract-setup/annexures') }}';
</script>
<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/blob.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/filesaver.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/htmdocx.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contract.js"></script>
<script type="module" src="{{url('/')}}/Modules/ContractParties/resources/assets/js/scriptparty.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contractV3.js"></script>

@endsection
@section('content')

<style>
  .accordion-item.has-error {
      border: 1px solid red !important;
   }

   .files input {
      outline: 2px dashed #dbdade;
      outline-offset: -10px;
      -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
      transition: outline-offset .15s ease-in-out, background-color .15s linear;
      padding: 120px 0px 85px 35%;
      text-align: center !important;
      margin: 0;
      width: 100% !important;
   }

   .files input:focus {
      outline: 2px dashed #dbdade;
      outline-offset: -10px;
      -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
      transition: outline-offset .15s ease-in-out, background-color .15s linear;
   }

   .files {
      position: relative
   }

   .files:after {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-upload' width='24' height='24' viewBox='0 0 24 24' stroke-width='2' stroke='%235d596c' fill='none' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath stroke='none' d='M0 0h24v24H0z' fill='none'/%3E%3Cpath d='M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2' /%3E%3Cpolyline points='7 9 12 4 17 9' /%3E%3Cline x1='12' y1='4' x2='12' y2='16' /%3E%3C/svg%3E") !important;
      background: #4b465c14;
      content: "";
      border-radius: 8px;
      position: absolute;
      top: 3rem;
      left: calc(50% - 23px);
      display: inline-block;
      height: 48px;
      width: 48px;
      background-repeat: no-repeat !important;
      background-position: center !important;
   }

   .color input {
      background: #fff;
   }

   .files:before {
      position: absolute;
      bottom: 10px;
      left: 0;
      pointer-events: none;
      width: 100%;
      right: 0;
      height: 57px;
      content: "Drop files here or click to upload";
      display: block;
      margin: 0 auto;
      font-weight: 600;
      text-transform: capitalize;
      text-align: center;
   }
   
   .unRequiredFields{
       display: none;
   }
   
   #showAllFields{
       transform: scale(1.5);
   }
</style>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
   <div class="d-flex flex-column justify-content-center">
      <h4 class="mb-1 mt-3">Add New Contract</h4>
   </div>
   <div class="d-flex align-content-center flex-wrap gap-3">
      <div class="d-flex gap-3">
         <a href="{{url('/contracts/list')}}" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
      </div>
   </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger sticky-element">
        <h5 class="alert-heading mb-2">Errors Details</h5>
        <ul class="list-unstyled mb-0">
            @foreach ($errors->keys() as $field)
            
                <li class="text-dark {{$field}}"><i class="ti ti-exclamation-circle text-danger"></i> {!! ucwords($errors->first($field)) !!}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="row my-4">
   <div class="col">

      <form class="row createcontractnew" id="createcontract" action="{{ route('storeContractV3') }}" method="POST" enctype="multipart/form-data">
         @csrf
         <div class="col-md mb-4 mb-md-2">
            <div class="accordion mt-3" id="accordionWithIcon">
               <div class="card accordion-item active">
                  <div class="card-header">
                     {{-- Not a <label>: this block wraps the Show All Fields checkbox, and a
                          label toggles its first labelable descendant, so clicking the header
                          text or the legal-sharing note used to flip that switch (showing and
                          hiding every .unRequiredFields field). --}}
                     <div class="form-label w-100">Contract

                        {{-- The Show All Fields switch is hidden on V3; the fields tagged
                             .unRequiredFields stay collapsed. The switch itself is kept in the
                             DOM so the contract.js handler bound to #showAllFields is a no-op
                             rather than a missing element. --}}
                        <div class="form-check form-switch show-error-switch float-end d-none">
                          <input class="form-check-input" type="checkbox" role="switch" id="showAllFields">
                          <label class="form-check-label ms-2 fs-5 fw-bold" for="showAllFields">Show All Fields</label>
                        </div>
                        <div class="mt-2">
                           <label class="form-label d-block mb-1">Legal Information Sharing</label>
                           <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#contactLegalCreateModal">Contact Group Legal Advisor</button>
                           <small class="d-block text-muted mt-1">Optional. If provided, legal request is sent immediately after contract creation.</small>
                        </div>
                     </div>
                     <div class="col mt-2">
                        @if(admin_setting('enable_new_contracts'))
                        <div class="form-check form-check-inline">
                           <label class="form-check-label">
                              <input type="radio" class="contractmode form-check-input" name="contractMode" value="new" {{ old('contractMode', $defVals['contractMode']) == 'new' ? 'checked' : '' }}>
                              New</label>
                        </div>
                        @endif
                        <div class="form-check form-check-inline">
                           <label class="form-check-label">
                              <input type="radio" class="contractmode form-check-input" name="contractMode" value="old" {{ old('contractMode', $defVals['contractMode']) == 'old' ? 'checked' : '' }}>
                              Legacy/Executed Contracts </label>
                        </div>
                     </div>
                  </div>
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-1" aria-expanded="true">
                        Basic Contract Information
                     </button>
                  </h2>
                  <div id="accordionWithIcon-1" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <hr class="mt-1" />
                        <div class="row g-3">

                           <div class="col-md-6">
                              <label class="form-label" for="contracttype">Contract Type <span class="text-danger">*</span></label>
                              <select class="form-select select2 contracttype" name="BasicContract[contractType]" id="contracttype">
                                <option value="">--Select Contract Type--</option>
                                 @foreach ($contractTypes as $contractType)
                                 
                                 <option data-catid="{{ $contractType->categoryId }}" {{ old('BasicContract.contractType') == $contractType->contract_type_id ? 'selected' : '' }} data-detid="{{ $contractType->departmentId }}" value="{{ $contractType->contract_type_id }}">
                                    {{ $contractType->contract_type }}
                                 </option>
                                 @endforeach
                              </select>
                           </div>
                           <div class="col-md-6">
                              <label class="form-label" for="DepartmentType">Department <span class="text-danger">*</span></label>
                              <select id="DepartmentType" name="BasicContract[DepartmentType]" class="form-select select2" data-allow-clear="true">
                                 <option value="">Select Department</option>

                                 @foreach($ent as $en)
                                 <option value="{{$en->id}}" {{ old('BasicContract.DepartmentType') == $en->id ? 'selected' : '' }}>{{$en->name}}</option>
                                 @endforeach

                              </select>
                           </div>
                           {{-- Category is hidden on V3: it is derived from the selected contract
                                type by contract.js, so the select stays in the DOM (and keeps
                                posting BasicContract[catgoeryType]) but is not shown. --}}
                           <div class="col-md-6 d-none">
                              <label class="form-label" for="catgoeryType">Category <span class="text-danger">*</span></label>
                              <select id="catgoeryType" name="BasicContract[catgoeryType]" class="form-select select2" data-allow-clear="true">
                                 @foreach($catego as $en)
                                 <option value="{{$en->id}}" {{ old('BasicContract.catgoeryType') == $en->id ? 'selected' : '' }}>{{$en->name}}</option>
                                 @endforeach
                              </select>
                           </div>
                           <div class="col-md-6 unRequiredFields">
                              <label class="form-label" for="Exclusivity">Exclusivity</label>
                              <select   name="BasicContract[Exclusivity]" class="form-select select2" data-allow-clear="true">
                                 <option {{ old('BasicContract.Exclusivity') == 'Exclusivity to Company' ? 'selected' : '' }} value="Exclusivity to Company">Exclusive to Company</option>
                                 <option {{ old('BasicContract.Exclusivity') == 'Exclusive to Contracting Party' ? 'selected' : '' }} value="Exclusive to Contracting Party">Exclusive to Contracting Party</option>
                                 <option {{ old('BasicContract.Exclusivity') == 'Mutually Exclusive' ? 'selected' : '' }} value="Mutually Exclusive">Mutually Exclusive</option>
                                 <option {{ old('BasicContract.Exclusivity', $defVals['Exclusivity']) == 'Non Exclusive' ? 'selected' : '' }} value="Non Exclusive">Non Exclusive</option>
                              </select>
                           </div>
                           <div class="col-md-6 unRequiredFields">
                              <label class="form-label" for="contractDescription">Contract
                                 Description</label>
                              <textarea class="form-control" id="contractDescription" name="BasicContract[contractDescription]" rows="5">{{old('BasicContract.contractDescription')}}</textarea>
                           </div>
                           <div class="col-md-6 unRequiredFields">
                              <label class="form-label" for="contracttypetags">Other Scopes</label>
                              <select class="form-select select2 contracttypetags" name="BasicContract[contractTypeTags][]" id="contracttypetags" multiple>
                                <option value="">--Select Tags--</option>
                                 @foreach ($contractTypes as $contractType)
                                 
                                 <option data-catid="{{ $contractType->categoryId }}" {{ in_array($contractType->contract_type_id, old('BasicContract.contractTypeTags', [])) ? 'selected' : '' }} value="{{ $contractType->contract_type_id }}">
                                    {{ $contractType->contract_type }}
                                 </option>
                                 @endforeach
                              </select>
                           </div>
                           @if(env('enable_contract_priority'))
                                <div class="col-md-6 unRequiredFields">      
                                    <label class="form-label" for="priority">Priority:</label>
                                    <select class="select2 form-select" id ="priority" name = "priority">
                                        <option selected>Choose Priority</option>
                                        <option value="low" {{ (old('priority', 'medium') == 'low' ? 'selected' : '' ) }}>Low</option>
                                        <option value="medium" {{ (old('priority', 'medium') == 'medium' ? 'selected' : '' ) }}>Medium</option>
                                        <option value="high" {{ (old('priority', 'medium') == 'high' ? 'selected' : '' ) }}>High</option>
                                    </select>  
                                </div>
                            @else
                                <input type="hidden" value="{{ old('priority', 'medium') }}"/>
                            @endif                           
                           <div class="row mb-3">
                              @include('contract::contract.createCustomField', ['categoryId' => 1])
                           </div>

                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-2" aria-expanded="false">
                        Party Details <i class="ti ti-help-circle text-warning" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="The system allows only one Internal party.
If you need to add another party from the same organization or branch group, please select Inter-Group."></i>
                     </button>
                  </h2>
                  <div id="accordionWithIcon-2" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <hr class="mt-1" />
                        <div class="row g-3">
                           @include('contract::contract.partyDetailsCreateV3', ['paryda', 'contractPartys'=>old('Partygroup.party', [[],['mode'=>'External']])])
                           <div class="panel-body">
                              <div class="party-group">
                              </div>
                              <!--<button class="admo">+Add more parties</button>-->
                              <button type="button" class="btn btn-primary me-sm-3 me-1 admo">+Add more
                                 parties</button>
                           </div>

                        </div>
                        <div class="row add_users" style="margin-top: 30px;">
                           <input type="hidden" id="user_position" value="1" />
                           <div class="col-md-6">
                              <div class="row" style="" id="">

                              </div>
                           </div>
                           <div class="col-md-6">
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-3" aria-expanded="false">
                        Contract Duration
                     </button>
                  </h2>
                  <div id="accordionWithIcon-3" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <hr class="mt-1" />
                        <div class="row g-3">

                           <div class="col">
                              @include('contract::contract.contractDuration', ['v3Form' => true])
                              {{-- Derived from the start/end dates by contractV3.js; read-only. --}}
                              <div class="row mt-3">
                                 <div class="col-md-6">
                                    <label class="form-label" for="tenure">Tenure
                                       <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Calculated automatically from the contract start and end dates."></i>
                                    </label>
                                    <input type="text" id="tenure" name="tenure" class="form-control" value="{{ old('tenure') }}" placeholder="Calculated from start and end dates" readonly>
                                 </div>
                              </div>
                           </div>
                           <div class="unRequiredFields">
                                @include('contract::contract.createCustomField', ['categoryId' => 2])
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-4" aria-expanded="false">
                        Contract Value
                     </button>
                  </h2>
                  <div id="accordionWithIcon-4" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <hr class="mt-0" />
                        <div class="row g-3">

                           <div class="card-body">

                              <div class="row mb-3">
                                 <div class="col-md-2">
                                    <label class="form-label" for="ContractValue">Contract Value</label>
                                    <select id="ContractValue" name="ContractValue[currency]" class="form-select select2" data-allow-clear="true">
                                       @foreach (currency() as $currency)
                                       <option value="{{ $currency }}" {{ old('ContractValue.currency') == $currency ? 'selected' : '' }}>{{ $currency }}</option>
                                       @endforeach
                                    </select>
                                 </div>
                                 <div class="col-md-4">
                                    <label class="form-label" for="formValidationSelect2">Billing Frequency <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Frequency at which invoices are issued (e.g., monthly, quarterly, annually)."></i></label>
                                    <select id="BillingFrequency" name="ContractValue[billingFrequency]" class="form-select select2 calculateBilling" data-allow-clear="true">
                                       <option {{ old('ContractValue.billingFrequency') == 'Weekly' ? 'selected' : '' }} value="Weekly">Weekly</option>
                                       <option {{ old('ContractValue.billingFrequency') == 'Monthly' ? 'selected' : '' }} value="Monthly">Monthly</option>
                                       <option {{ old('ContractValue.billingFrequency') == 'Quarterly' ? 'selected' : '' }} value="Quarterly">Quarterly</option>
                                       <option {{ old('ContractValue.billingFrequency') == 'Half Yearly' ? 'selected' : '' }} value="Half Yearly">Half Yearly</option>
                                       <option {{ old('ContractValue.billingFrequency') == 'Annually' ? 'selected' : '' }} value="Annually">Annually</option>
                                       <option {{ old('ContractValue.billingFrequency') == 'Onetime' ? 'selected' : '' }} value="Onetime">One Time</option>
                                    </select>
                                 </div>
                                 <div class="col-md-4">
                                     <label class="form-label" for="ContractBillingValue">Billing Value <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="The amount billed in each billing period. The annual and total contract value are calculated from this and the contract tenure."></i> <span class="required-field-old text-danger" style="display:{{ old('contractMode', $defVals['contractMode']) == 'old' ? 'inline-block' : 'none'}}">*</span></label>
                                    <input type="number" class="form-control calculateBilling" placeholder="" name="ContractValue[billingvalue]" id="ContractBillingValue" value="{{ old('ContractValue.billingvalue') }}">
                                 </div>                                 
                              </div>
                              <div class="row mb-3">
                                 <div class="col-md-6 annualValueDiv d-none"><label class="form-label" for="ContractValueAnnual">Annual Contract Value</label>
                                    <label class="btn btn-label-warning btn-sm mt-xl-6 waves-effect"><span class="align-middle" id="ContractValAnnText"></span></label>                                
                                    <input type="hidden" readonly class="form-control" placeholder="" name="ContractValue[value]" id="ContractValueAnnual" value="{{ old('ContractValue.value') }}">
                                 </div>
                                 <div class="col-md-6 totalValueDiv d-none"><label class="form-label" for="totalContractValue">Total Contract Value</label>
                                    <label class="btn btn-label-warning btn-sm mt-xl-6 waves-effect"><span class="align-middle" id="totContValText"></span></label>                                 
                                    <input type="hidden" readonly class="form-control" placeholder="" name="ContractValue[totalvalue]" id="totalContractValue" value="{{ old('ContractValue.totalvalue') }}">
                                 </div>
                              </div>
                              <hr class="mt-3 unRequiredFields" />
                              <div class="row mb-3 unRequiredFields">
                                 <div class="col-md-6">
                                    <label class="form-label" for="PaymentSchedule">Payment Schedule <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Details of payment milestones, amounts, and due dates."></i></label>
                                    <textarea class="form-control" id="PaymentSchedule" name="ContractValue[paymentSchedule]" rows="3">{{ old('ContractValue.paymentSchedule') }}</textarea>
                                    
                                 </div>
                                 <div class="col-md-6">
                                    <label class="form-label" for="paymentTerms">Payment Terms <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Terms and conditions governing payments, including payment methods any late payment
                                                    penalties."></i></label>
                                    <textarea class="form-control" id="paymentTerms" name="ContractValue[paymentTerms]" rows="3">{{ old('ContractValue.paymentTerms') }}</textarea>
                                 </div>
                              </div>
                              <hr class="mt-3 unRequiredFields" />
                              <div class="row mb-3 unRequiredFields">
                                 <div class="col-md-4">
                                    <label class="form-label" for="Currencycontract">Taxes and Fees <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Any applicable taxes, fees, or surcharges associated with the contract."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="Taxes" name="ContractValue[taxes]" value="{{ old('ContractValue.taxes') }}">
                                 </div>
                              </div>
                              <hr class="mt-3 unRequiredFields" />
                              <div class="row mb-3 unRequiredFields">
                                 <div class="col-md-6">
                                    <label class="form-label" for="Currencycontract">Escalation Clauses <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Provisions for adjusting contract prices over time based on predetermined factors such as
                                                    inflation or market fluctuations."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="EscalationClauses" name="ContractValue[escalationClauses]" value="{{ old('ContractValue.escalationClauses') }}">
                                 </div>
                                 <div class="col-md-4">
                                    <label class="form-label" for="Currencycontract">Discounts or Rebates <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Any discounts or rebates applied to the contract."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="Discounts" name="ContractValue[discounts]" value="{{ old('ContractValue.discounts') }}">
                                 </div>
                              </div>
                              <hr class="mt-3 unRequiredFields" />
                              <div class="row mb-3 unRequiredFields">
                                 <div class="col-md-6">
                                    <label class="form-label" for="Currencycontract">Retention or Holdbacks <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Amounts withheld from payments as retention or holdbacks pending completion of certain
                                                    milestones or obligations."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="Retention" name="ContractValue[retention]" value="{{ old('ContractValue.retention') }}">
                                 </div>
                                 <div class="col-md-4">
                                    <label class="form-label" for="Currencycontract">Payment Escrow <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Details of any funds held in escrow for payment security or dispute resolution purposes."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="Payment" name="ContractValue[payment_escrow]" value="{{ old('ContractValue.payment_escrow') }}">
                                 </div>
                              </div>
                              <hr class="mt-3 unRequiredFields" />
                              <div class="row mb-3 unRequiredFields">
                                 <div class="col-md-6">
                                    <label class="form-label" for="Currencycontract">Financial Guarantees or Bonds <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Information about any financial guarantees or bonds required under the contract."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="Financial Guarantees" name="ContractValue[financialGuarantees]" value="{{ old('ContractValue.financialGuarantees') }}">
                                 </div>
                                 <div class="col-md-4 d-none">
                                    <label class="form-label" for="Currencycontract">Currency Conversion <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Terms for currency conversion if the contract involves transactions in multiple currencies."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="CurrencyConversion" name="ContractValue[currencyConversion]" value="{{ old('ContractValue.currencyConversion') }}">
                                 </div>
                              </div>
                           </div>
                           {{-- Price revision terms: a unit and an amount. --}}
                           <hr class="mt-3" />
                           <div class="row mb-3">
                              <div class="col-md-6">
                                 <label class="form-label" for="price_revision_type">Price Revision Terms
                                    <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Whether the agreed price revision is expressed as a percentage or an absolute value."></i>
                                 </label>
                                 <select class="form-select" name="price_revision_type" id="price_revision_type">
                                    <option value="">-Select-</option>
                                    <option value="percentage" {{ old('price_revision_type') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                    <option value="value" {{ old('price_revision_type') == 'value' ? 'selected' : '' }}>Value</option>
                                 </select>
                              </div>
                              <div class="col-md-6">
                                 <label class="form-label" for="price_revision_value">Price Revision Amount</label>
                                 <input type="number" step="0.01" min="0" class="form-control" id="price_revision_value" name="price_revision_value" value="{{ old('price_revision_value') }}" placeholder="">
                              </div>
                           </div>
                           <div class="row mb-3">
                              @include('contract::contract.createCustomField', ['categoryId' => 3])
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-5" aria-expanded="true">
                        Contract Custom Fields / Miscellaneous
                     </button>
                  </h2>
                  <div id="accordionWithIcon-5" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <div class="row g-3">

                           <div class="card-body">

                              <div class="panel panel-default">

                                 <div class="panel-collapse">
                                    <div class="panel-body">
                                       <div class="col-sm-12">
                                          @include('contract::contract.createCustomField', ['categoryId' => 4])
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>

                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-6" aria-expanded="false">
                        Contract Attachments
                     </button>
                  </h2>
                  <div id="accordionWithIcon-6" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <hr class="mt-0" />
                        <div class="col mt-2">
                           <div class="form-check form-check-inline">
                              <label class="form-check-label">
                                 <input type="radio" class="attachmentstype form-check-input" name="attachments_type" value="Upload" data-div="upload" {{ old('attachments_type', 'Upload') == 'Upload' ? 'checked' : ''}} />
                                 Upload file </label>
                           </div>
                           @if(env('enable_contract_template'))
                               <div class="form-check form-check-inline">
                                  <label class="form-check-label">
                                     <input type="radio" class="{{ env('enable_contract_template') ? 'attachmentstype' : '' }} form-check-input" name="attachments_type" value="template" data-div="template" {{ old('attachments_type', 'Upload') == 'template' ? 'checked' : ''}}/>
                                     Take from template</label>
                               </div>
                           @endif
                        </div>
                        <div class="row">
                            <div class="col-12 col-lg-6 attachmentsdiv" id="attachments_type_upload" style="display: {{ old('attachments_type', 'Upload') == 'template' ? 'none' : ''}}">
                               <div class="col-12">
                                  <div class="card mb-4">
                                     <div class="card-body">
                                        <div class="form-group files color">
                                           <input type="file" name="file" class="form-control">
                                        </div>
                                     </div>
                                  </div>
                               </div>
                            </div>
                            <div class="col-12 col-lg-6 attachmentsdiv" id="attachments_type_template" style="display: {{ old('attachments_type', 'Upload') == 'Upload' ? 'none' : ''}}">
                                <div class="mt-2">
                                    <div id="template-preview-container">
                                        <div id="template-preview-info" class="alert alert-info d-none">
                                            <strong>Template:</strong> <span id="template-preview-name"></span>
                                            <a id="template-download-btn" href="javascript:void(0)" class="btn btn-sm btn-primary ms-3" style="display:none;">
                                                <i class="ti ti-download"></i> Download DOCX
                                            </a>
                                        </div>
                                        <div id="template-preview-loading" class="text-center d-none py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2">Loading template preview...</p>
                                        </div>
                                        <iframe id="template-preview-frame" style="display:none;" src=""></iframe>
                                        <div id="template-preview-empty" class="alert alert-warning d-none">
                                            No agreement template found for this contract type.
                                        </div>
                                    </div>
                                    <input type="hidden" id="agreement_template_id" name="agreement_template_id" value="{{ old('agreement_template_id') }}" />
                                    <textarea id="template_text" name="template_text" hidden>{{ old('template_text') }}</textarea>
                                    <input type="file" hidden id="docxFile" name="docxFile" />
                                </div>
                            </div>
                            <div class="col-12 col-lg-6 d-none">
                                <div class="timeline-event card p-0 aos-init aos-animate" data-aos="fade-right">
                                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                                      <h5 class="card-title mb-0">AI Suggessionts <i class="ms-2 ti ti-md ti-writing"></i></h5>
                                      <div class="meta">
                                        <span class="badge rounded-pill bg-label-primary me-1">Contract Type</span>
                                        <span class="badge rounded-pill bg-label-success">Tags</span>
                                        <span class="badge rounded-pill bg-label-warning">Party</span>
                                        <span class="badge rounded-pill bg-label-danger">Terms</span>
                                      </div>
                                    </div>
                                    <div class="card-body">
                                      <p class="mb-2">
                                          <h6>Reading Contract data's <span class="text-primary ti ti-flashing ti-book"></span></h6>
                                      </p>
                                      <div class="d-flex justify-content-between align-items-center flex-wrap">
                                      </div>
                                    </div>
                                  </div>                            
                            </div>  
                        </div>
                     </div>
                  </div>
               </div>
               {{-- Annexures: one upload slot per active master annexure, plus free-form rows.
                    Uploaded Word files are appended to the executed contract PDF. --}}
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionAnnexures" aria-expanded="false">
                        Annexures
                     </button>
                  </h2>
                  <div id="accordionAnnexures" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                         <hr class="mt-0" />
                        <div id="annexureSelectHint" class="alert alert-info mb-3 {{ old('BasicContract.contractType') ? 'd-none' : '' }}">
                           Select a contract type to view applicable annexures.
                        </div>
                        <div id="annexureFields" class="{{ old('BasicContract.contractType') ? '' : 'd-none' }}">
                         <p class="text-muted small">
                            Upload the completed Word document for each annexure that applies. Word files only
                            (.doc or .docx). Uploaded annexures are appended to the final contract PDF.
                         </p>

                        <div id="annexureMasterRows">
                           @forelse (($annexures ?? collect()) as $annexure)
                           <div class="row g-3 align-items-end mb-3 pb-3 border-bottom master-annexure-row" data-annexure-id="{{ $annexure->id }}" data-contract-type="{{ $annexure->contract_type }}">
                              <div class="col-md-5">
                                 <label class="form-label" for="annexure-file-{{ $annexure->id }}">
                                    {{ $annexure->annexure_name }}
                                 </label>
                                 @if($annexure->title)
                                 <div class="text-muted small mb-1">{{ $annexure->title }}</div>
                                 @endif
                                 <input type="hidden" name="annexures[{{ $annexure->id }}][annexure_master_id]" value="{{ $annexure->id }}">
                                 <input type="file" id="annexure-file-{{ $annexure->id }}" name="annexures[{{ $annexure->id }}][file]" class="form-control" accept=".doc,.docx">
                              </div>
                              <div class="col-md-4">
                                 @if($annexure->sample_file)
                                 <a class="btn btn-sm btn-label-primary" href="{{ route('contract-setup.annexures.sample', $annexure->id) }}">
                                    <i class="ti ti-download ti-xs me-1"></i> Download Sample
                                 </a>
                                 @else
                                 <span class="text-muted small">No sample available</span>
                                 @endif
                              </div>
                           </div>
                           @empty
                           <div class="alert alert-info mb-0" id="no-annexures-message">
                              No annexures have been configured yet. An administrator can add them under
                              Setup &rarr; Annexure Master.
                           </div>
                           @endforelse
                        </div>

                        {{-- Free-form annexures not present in the master list. --}}
                        <h6 class="mt-4">Other Annexures</h6>
                        <div id="customAnnexureRows">
                           @foreach (old('custom_annexures', []) as $customIndex => $customAnnexure)
                           <div class="row g-3 align-items-end mb-3 custom-annexure-row">
                              <div class="col-md-5">
                                 <label class="form-label">Annexure Name</label>
                                 <input type="text" name="custom_annexures[{{ $customIndex }}][annexure_name]" class="form-control" value="{{ $customAnnexure['annexure_name'] ?? '' }}">
                              </div>
                              <div class="col-md-5">
                                 <label class="form-label">File</label>
                                 <input type="file" name="custom_annexures[{{ $customIndex }}][file]" class="form-control" accept=".doc,.docx">
                              </div>
                              <div class="col-md-2">
                                 <button type="button" class="btn btn-sm btn-label-danger removeCustomAnnexure">
                                    <i class="ti ti-trash ti-xs"></i> Remove
                                 </button>
                              </div>
                           </div>
                           @endforeach
                        </div>
                        <button type="button" id="addCustomAnnexure" class="btn btn-sm btn-label-primary mt-2">
                           <i class="ti ti-plus ti-xs me-1"></i> Add Annexure
                        </button>

                        {{-- Row template cloned by contractV3.js; __INDEX__ is replaced on insert. --}}
                        <template id="customAnnexureTemplate">
                           <div class="row g-3 align-items-end mb-3 custom-annexure-row">
                              <div class="col-md-5">
                                 <label class="form-label">Annexure Name</label>
                                 <input type="text" name="custom_annexures[__INDEX__][annexure_name]" class="form-control">
                              </div>
                              <div class="col-md-5">
                                 <label class="form-label">File</label>
                                 <input type="file" name="custom_annexures[__INDEX__][file]" class="form-control" accept=".doc,.docx">
                              </div>
                              <div class="col-md-2">
                                 <button type="button" class="btn btn-sm btn-label-danger removeCustomAnnexure">
                                    <i class="ti ti-trash ti-xs"></i> Remove
                                 </button>
                              </div>
                           </div>
                        </template>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionOwnership" aria-expanded="false">
                        Ownership
                     </button>
                  </h2>
                  <div id="accordionOwnership" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <hr class="mt-1" />
                        <div class="row g-3">
                           <div class="col-md-6">
                              <label class="form-label" for="owner">Owner/Inititor <span class="text-danger">*</span></label>
                              <select class="form-select" name="owner" id="ownership">
                                 <option value="">-Co-ordinator-</option>
                                 @foreach ($usersSel as $user)
                                 <option value="{{ $user->id }}" {{ old('owner', $owner_initiator_id) == $user->id ? 'selected' : '' }}>
                                   {{ $user->Salutation }}
                                    {{ $user->FirstName }}
                                    {{ $user->LastName }}
                                    ({{ $user->Email }})
                                 </option>
                                 @endforeach
                              </select>
                           </div>
                           <div class="col-md-6">
                              <label class="form-label" for="ownership-signatory">Signatory <span class="text-danger">*</span></label>
                              <select class="form-select" name="signatory" id="ownership-signatory" disabled>
                                 <option value="">-Select Signatory-</option>
                                 @foreach ($users as $user)
                                 <option value="{{ $user->id }}" {{ old('signatory') == $user->id ? 'selected' : '' }}>
                                   {{ $user->Salutation }}
                                    {{ $user->FirstName }}
                                    {{ $user->LastName }}
                                    ({{ $user->Email }})
                                 </option>
                                 @endforeach
                              </select>
                           </div>
                           <div class="col-md-6">
                              <label class="form-label" for="users-notify">Users To Notify</label>
                              <select class="form-select" name="userNotify[]" id="users-notify" multiple>
                                 @foreach ($usersSel as $user)
                                 <option value="{{ $user->id }}" {{ in_array($user->id, old('userNotify', [])) == $user->id ? 'selected' : '' }}>
                                   {{ $user->Salutation }}
                                    {{ $user->FirstName }}
                                    {{ $user->LastName }}
                                    ({{ $user->Email }})
                                 </option>
                                 @endforeach
                              </select>
                           </div>                           
                          <div class="col-md-6">  
                              <div class="form-group signing_date" style="display:{{ old('contractMode', $defVals['contractMode']) == 'old' ? 'block' : 'none'}}">
                                 <label class="form-label">Signing Date <span class="required-field-old text-danger" style="display:{{ old('contractMode', $defVals['contractMode']) == 'old' ? 'inline-block' : 'none'}}">*</span></label>
                                 <!--<label>Date</label>-->
                              <input type="date" name="Duration[signingDate]" class="form-control flatpickr" placeholder="Signing Date" value="{{ old('Duration.signingDate') }}"/>

                                 <div class="clearfix">
                                    <small class="form-text text-muted">The date on which the contract is signed by all parties involved. This may or may not be the same as the effective date, depending on the terms of the contract.
                                    </small>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>               
            </div>
         </div>
         <div class="buy-now">
            <button type="button" id="createContractButton" class="btn-buy-now btn btn-primary me-sm-3 me-1 waves-effect waves-light">Submit</button>
         </div>

         <div class="modal fade" id="contactLegalCreateModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
               <div class="modal-content">
                  <div class="modal-header">
                     <h5 class="modal-title">Contact Group Legal Advisor</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                     @php
                        $defaultLegalAdvisorId = old('legal_advisor_id');
                        if (empty($defaultLegalAdvisorId)) {
                           $defaultLegalAdvisorId = optional(($legalAdvisors ?? collect())->first())->id;
                        }
                     @endphp
                     <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="contactLegalNow" name="contact_legal_now" value="1" {{ old('contact_legal_now') ? 'checked' : '' }}>
                        <label class="form-check-label" for="contactLegalNow">Send request to legal after contract is created</label>
                     </div>
                     <div class="d-none">
                        <label class="form-label" for="create_legal_advisor_id">Legal Advisor</label>
                        <select class="form-select" name="legal_advisor_id" id="create_legal_advisor_id">
                           <option value="">-Select Legal Advisor-</option>
                           @foreach (($legalAdvisors ?? collect()) as $advisor)
                           <option value="{{ $advisor->id }}" {{ (string) $defaultLegalAdvisorId === (string) $advisor->id ? 'selected' : '' }}>
                              {{ $advisor->name }}{{ $advisor->designation ? ' - ' . $advisor->designation : '' }} ({{ $advisor->email_id }})
                           </option>
                           @endforeach
                        </select>
                     </div>
                     <div class="mb-0">
                        <label class="form-label" for="create_legal_comment">Comment</label>
                        <textarea class="form-control" name="legal_contact_comment" id="create_legal_comment" rows="5" placeholder="Share context for legal advisor.">{{ old('legal_contact_comment') }}</textarea>
                     </div>
                  </div>
                  <div class="modal-footer">
                     <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                     <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Save</button>
                  </div>
               </div>
            </div>
         </div>
      </form>

   </div>
</div>

<!-- Horizontal Onboarding modals -->


<!-- Form with Image horizontal Modal -->
<div class="modal-onboarding modal fade animate__animated" id="onboardHorizontalImageModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content text-center">
         <div class="modal-header border-0">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            </button>
         </div>
         <div class="modal-body onboarding-horizontal p-0">
             <div class="popap">
               
             </div>
         </div>

      </div>
   </div>
</div>
</div>
</div>
</div>
</div>
</div>
<script>
    function form_modal_submit(idForm){
    $(`#${idForm}`).submit();
}
</script>

@endsection
