@extends('layouts/layoutMaster')
@section('title', ' Contracts')
<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/quill/typography.scss',
'resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/quill/editor.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/dropzone/dropzone.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/tagify/tagify.scss'
])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/quill/katex.js',
 'resources/assets/vendor/libs/quill/quill.js',
'resources/assets/vendor/libs/cleavejs/cleave.js',
'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
'resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/dropzone/dropzone.js',
 'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js'
])

<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')
@vite(['resources/assets/js/form-layouts.js','resources/assets/js/app-ecommerce-product-add.js'])

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contract.js"></script>
@endsection
@section('content')
<!--<h4 class="py-3 mb-4"><span class="text-muted fw-light">Add New Rules</span></h4>-->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
   <div class="d-flex flex-column justify-content-center">
      <h4 class="mb-1 mt-3">Add New Contract</h4>
   </div>
   <div class="d-flex align-content-center flex-wrap gap-3">
      <div class="d-flex gap-3">
         <a href="{{url('/')}}" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
      </div>
   </div>
</div>
<!-- Multi Column with Form Separator -->
<!-- Collapsible Section -->
<div class="row my-4">
   <div class="col">
      <form class="row" id="createcontract" action="store/contract" method="POST" enctype="multipart/form-data">
         @csrf
         <div class="col-md mb-4 mb-md-2">
            <div class="accordion mt-3" id="accordionWithIcon">
               <div class="card accordion-item active">
                   <div class="card-header">
                     <label class="form-check-label">Contract</label>
                     <div class="col mt-2">
                        <div class="form-check form-check-inline">
                           <label class="form-check-label">
                           <input type="radio" class="contractmode form-check-input" name="contractMode" value="new" checked="">
                           New</label>
                        </div>
                        <div class="form-check form-check-inline">
                           <label class="form-check-label">
                           <input type="radio" class="contractmode form-check-input" name="contractMode" value="old"> 
                           Legacy Contracts </label>
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
                              <label class="form-label" for="contracttype">Contract Type <span
                                 class="text-danger">*</span></label>
                              <select class="form-select select2 contracttype"
                                 name="BasicContract[contractType]" id="contracttype">
                                 <option value="">-Select Contract Type-</option>
                                 @foreach ($contractTypes as $contractType)
                                 <option value="{{ $contractType->contract_type_id }}">
                                    {{ $contractType->contract_type }}
                                 </option>
                                 @endforeach
                              </select>
                           </div>
                           <!--<div class="col-md-6">-->
                              <!--<label class="form-label" for="ecommerce-product-barcode">Contract Type</label>-->
                           <!--   <label class="form-label" for="signatory">Signatory <span class="text-danger">*</span></label>-->
                              <!--<select id="signatory" name="BasicContract[signatory]" class="form-select select2" data-allow-clear="true">-->
                              <!--<option value="">Select Signatory</option>-->
                              <!--<option value="Test">Test</option>-->
                              <!--<option value="Demo">Demo</option>-->
                              <!--</select>    -->
                           <!--   <select class="form-select select2 " name="BasicContract[signatory]"-->
                           <!--      id="signatory">-->
                           <!--      <option value="">-Select Signatory-</option>-->
                           <!--      @foreach ($users as $user)-->
                           <!--      <option value="{{ $user->id }}">-->
                           <!--         {{ $user->FirstName }}-->
                           <!--      </option>-->
                           <!--      @endforeach-->
                           <!--   </select>-->
                           <!--</div>-->
                           <div class="col-md-6">
                              <label class="form-label" for="catgoeryType">Department <span class="text-danger">*</span></label>
                              <select id="catgoeryType" name="BasicContract[DepartmentType]"
                                 class="form-select select2" data-allow-clear="true">
                                 <option value="">Select Department</option>
                                 <option value="2">Admin</option>
                                 <option value="3">Finance</option>
                                 <option value="4">Secretarial</option>
                                 <option value="5">Quality</option>
                                 <option value="6">Legal</option>
                                 <option value="7">Marketing</option>
                                 <option value="8">Technology</option>
                                 <option value="9">Human Resources</option>
                                 <option value="10">Engineering</option>
                                 <option value="11">Maintenance</option>
                                 <option value="12">Food &amp; Beverages</option>
                                 <option value="13">Pharmacy</option>
                                 <option value="14">Blood Bank</option>
                                 <option value="15">Radiology</option>
                                 <option value="16">Oncology - NM</option>
                                 <option value="17">Oncology - Therapy</option>
                                 <option value="18">Ultrasound</option>
                                 <option value="19">Gynecology</option>
                                 <option value="20">Transplant</option>
                                 <option value="21">Biomedical Engineering</option>
                                 <option value="22">Medical Records</option>
                                 <option value="23">House Keeping</option>
                                 <option value="24">Transport</option>
                                 <option value="25">Production</option>
                                 <option value="26">Operations</option>
                                 <option value="27">General Stores</option>
                                 <option value="28">FEMA</option>
                                 <option value="29">Fire</option>
                                 <option value="30">Laboratory</option>
                                 <option value="31">EHS</option>
                                 <option value="32">EXIM</option>
                                 <option value="33">Procurement</option>
                                 <option value="34">Electrical Maintenance</option>
                                 <option value="35">Mechanical</option>
                                 <option value="36">Purchase</option>
                                 <option value="37">Environmental Cell</option>
                                 <option value="38">Estate</option>
                                 <option value="39">Sales</option>
                                 <option value="40">Corporate HR</option>
                                 <option value="41">Energy Efficiency</option>
                                 <option value="42">BOE</option>
                                 <option value="43">Dump</option>
                                 <option value="44">Materials</option>
                              </select>
                           </div>
                           <div class="col-md-6">
                              <label class="form-label" for="DepartmentType">Category <span class="text-danger">*</span></label>
                              <select id="DepartmentType" name="BasicContract[catgoeryType]"
                                 class="form-select select2 DepartmentType" data-allow-clear="true">
                                 <option value="">Select Category</option>
                                 <option value="1">C1</option>
                                 <option value="2">C2</option>
                                 <option value="3">C3</option>
                                 <option value="4">C4</option>
                                 <option value="5">C5</option>
                                 <option value="6">C6</option>
                                 <option value="7">C7</option>
                                 <option value="8">C8</option>
                                 <option value="9">C9</option>
                                 <option value="10">C10</option>
                              </select>
                           </div>
                           <div class="col-md-6">
                               <label class="form-label" for="contractDescription">Contract
                               Description</label>
                               <textarea class="form-control" id="contractDescription"
                                  name="BasicContract[contractDescription]" rows="5"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <h6 class="mt-4">Custom Fields</h6>
                                <hr class="mt-0" />
                            </div>
                            <div class="row mb-3">
                                @include('contract::contract.viewCustomField', ['categoryId' => 1]) 
                            </div>

                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-2" aria-expanded="false">
                     Party Details
                     </button>
                  </h2>
                  <div id="accordionWithIcon-2" class="accordion-collapse collapse">
                     <div class="accordion-body">
                        <hr class="mt-1" />
                        <div class="row g-3">
                            
                            <div class="panel-body">
                                <div class="party-group">
                                </div>
                                <!--<button class="admo">+Add more parties</button>-->
                                <button type="submit" class="btn btn-primary me-sm-3 me-1 admo">+Add more
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
        <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse"
            data-bs-target="#accordionWithIcon-7" aria-expanded="false">
            Ownership
        </button>
    </h2>
    <div id="accordionWithIcon-7" class="accordion-collapse collapse">
        <div class="accordion-body">
            <hr class="mt-1" />
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="owner">Owner <span class="text-danger">*</span></label>
                    <select class="form-select select2 " name="owner" id="ownership">
                        <option value="">-Owner-</option>
                        @foreach ($users as $user)
                        <option value="{{ $user->id }}">
                            {{ $user->FirstName }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="Ownership Signatory">Signatory <span
                            class="text-danger">*</span></label>
                    <select class="form-select select2 " name="BasicContract[signatory]" id="ownership-signatory">
                        <option value="">-Select Signatory-</option>
                        @foreach ($users as $user)
                        <option value="{{ $user->id }}">
                            {{ $user->FirstName }}
                        </option>
                        @endforeach
                    </select>
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
                  <div id="accordionWithIcon-3" class="accordion-collapse collapse">
                     <div class="accordion-body">
                        <hr class="mt-1" />
                        <div class="row g-3">
                            
                           <div class="col">
                                @include('contract::contract.contractDuration')
                             </div>
                             @include('contract::contract.viewCustomField', ['categoryId' => 2])
                             <p class="mt-2">The date on which the contract is signed by all parties involved.
                                This may or may not be the same as the effective date, depending on the terms of
                                the contract.
                             </p>

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
                  <div id="accordionWithIcon-4" class="accordion-collapse collapse">
                     <div class="accordion-body">
                         <hr class="mt-0" />
                        <div class="row g-3">
                            
                          <div class="card-body">
                               <!--<h3 class="mt-3">Contract Value</h3>-->
                               
                               <div class="row mb-3">
                                  <div class="col-md-2">
                                     <label class="form-label" for="ContractValue">Contract Value</label>
                                     <select id="ContractValue" name="ContractValue[currency]" class="form-select select2"
                                        data-allow-clear="true">
                                        @foreach (currency() as $currency)
                                        <option value="{{ $currency }}">{{ $currency }}</option>
                                        @endforeach
                                     </select>
                                     <p>The total monetary value of the contract.</p>
                                  </div>
                                  <div class="col-md-4"><label class="form-label" for="ContractValue"></label>
                                     <input type="number" class="form-control" placeholder="" name="ContractValue[value]"
                                        id="ContractValue">
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="PaymentSchedule">Payment Schedule <span class="text-danger">*</span></label>
                                     <input type="text" class="form-control" placeholder="" id="PaymentSchedule"
                                        name="ContractValue[paymentSchedule]">
                                     <p>Details of payment milestones, amounts, and due dates.</p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-2">
                                     <label class="form-label" for="Currencycontract">Currency of the contract</label>
                                     <select id="Currencycontract" name="ContractValue[currencyContract]" class="form-select select2"
                                        data-allow-clear="true">
                                        @foreach (currency() as $currency)
                                        <option value="{{ $currency }}">{{ $currency }}</option>
                                        @endforeach
                                     </select>
                                     <p>The total monetary value of the contract.</p>
                                  </div>
                                  <div class="col-md-8">
                                     <label class="form-label" for="PaymentSchedule">Payment Terms</label>
                                     <textarea class="form-control" id="PaymentSchedule" name="ContractValue[paymentSchedule]"
                                        rows="3"></textarea>
                                     <p>Terms and conditions governing payments, including payment methods any late payment
                                        penalties.
                                     </p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-6">
                                     <label class="form-label" for="formValidationSelect2">Billing Frequency</label>
                                     <select id="BillingFrequency" name="ContractValue[billingFrequency]" class="form-select select2"
                                        data-allow-clear="true">
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="annually">Annually</option>
                                     </select>
                                     <p>Frequency at which invoices are issued (e.g., monthly, quarterly, annually).</p>
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="Currencycontract">Taxes and Fees</label>
                                     <input type="text" class="form-control" placeholder="" id="Taxes" name="ContractValue[taxes]">
                                     <p>Any applicable taxes, fees, or surcharges associated with the contract.</p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-6">
                                     <label class="form-label" for="Currencycontract">Escalation Clauses</label>
                                     <input type="text" class="form-control" placeholder="" id="EscalationClauses"
                                        name="ContractValue[escalationClauses]">
                                     <p>Provisions for adjusting contract prices over time based on predetermined factors such as
                                        inflation or market fluctuations.
                                     </p>
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="Currencycontract">Discounts or Rebates</label>
                                     <input type="text" class="form-control" placeholder="" id="Discounts"
                                        name="ContractValue[discounts]">
                                     <p>Any discounts or rebates applied to the contract.</p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-6">
                                     <label class="form-label" for="Currencycontract">Retention or Holdbacks</label>
                                     <input type="text" class="form-control" placeholder="" id="Retention"
                                        name="ContractValue[Retention]">
                                     <p>Amounts withheld from payments as retention or holdbacks pending completion of certain
                                        milestones or obligations.
                                     </p>
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="Currencycontract">Payment Escrow</label>
                                     <input type="text" class="form-control" placeholder="" id="Payment"
                                        name="ContractValue[payment]">
                                     <p>Details of any funds held in escrow for payment security or dispute resolution purposes.</p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-6">
                                     <label class="form-label" for="Currencycontract">Financial Guarantees or Bonds</label>
                                     <input type="text" class="form-control" placeholder="" id="Financial Guarantees"
                                        name="ContractValue[financialGuarantees]">
                                     <p>Information about any financial guarantees or bonds required under the contract.</p>
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="Currencycontract">Currency Conversion</label>
                                     <input type="text" class="form-control" placeholder="" id="CurrencyConversion"
                                        name="ContractValue[currencyConversion]">
                                     <p>Terms for currency conversion if the contract involves transactions in multiple currencies.
                                     </p>
                                  </div>
                               </div>
                            </div>

                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-5" aria-expanded="false">
                     Contract Custom Fileds / Miscelleneous
                     </button>
                  </h2>
                  <div id="accordionWithIcon-5" class="accordion-collapse collapse">
                     <div class="accordion-body">
                         <hr class="mt-0" />
                        <div class="row g-3">
                            
                            <div class="card-body">

                               <div class="panel panel-default">

                                  <div class="panel-collapse">
                                     <div class="panel-body">
                                        <div class="col-sm-12">
                                           @include('contract::contract.viewCustomField', ['categoryId' => 4])
                                        </div>
                                     </div>
                                     <hr>
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
                  <div id="accordionWithIcon-6" class="accordion-collapse collapse">
                     <div class="accordion-body">
                         <hr class="mt-0" />
                        <div class="col-12 col-lg-8">
                          <!-- Media -->
                          <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                              <h5 class="mb-0 card-title">Media</h5>
                              <a href="javascript:void(0);" class="fw-medium">Add media from URL</a>
                            </div>
                            <div class="card-body">
                              <form action="/upload" class="dropzone needsclick" id="dropzone-basic">
                                <div class="dz-message needsclick">
                                  <p class="fs-4 note needsclick pt-3 mb-1">Drag and drop your image here</p>
                                  <p class="text-muted d-block fw-normal mb-2">or</p>
                                  <span class="note needsclick btn bg-label-primary d-inline" id="btnBrowse">Browse image</span>
                                </div>
                                <div class="fallback">
                                  <input name="file" type="file" />
                                </div>
                           
                            </div>
                          </div>
                          <!-- /Media -->
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="pt-4">
            <button type="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>
            <button type="reset" class="btn btn-label-secondary">Cancel</button>
         </div>
         <div class="buy-now">
      <!--<a href="https://1.envato.market/vuexy_admin" target="_blank" class="btn btn-primary btn-buy-now waves-effect waves-light">Submit</a>-->
      
       <button type="submit" class="btn-buy-now btn btn-primary me-sm-3 me-1 waves-effect waves-light">Submit</button>
    </div>
      </form>
      
      <!--<h6> Collapsible Section </h6>-->
   </div>
</div>
@endsection