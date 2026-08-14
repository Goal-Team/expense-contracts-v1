@extends('layouts/layoutMaster')

@section('title', 'Contract Parties')

<!-- Vendor Styles -->
@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/dropzone/dropzone.scss',
  'resources/assets/vendor/libs/quill/typography.scss',
  'resources/assets/vendor/libs/quill/katex.scss'
])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/moment/moment.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/dropzone/dropzone.js',
  'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js',
  'resources/assets/vendor/libs/cleavejs/cleave.js',
  'resources/assets/vendor/libs/cleavejs/cleave-phone.js'
])
@endsection

  @section('content')
  
  <link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
  
  <style>
    label.required:after { content:"*"; color:red;font-size: 15px; font-weight: 900; }
    .unRequiredFields{ display: none }
    #showAllFields{ transform: scale(1.5); }
  </style>   
  
  <div class="row my-4">
      <div class="container">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
               <div class="d-flex flex-column justify-content-center">
                  <h4 class="mb-1">
                    Add New Contract Parties <span class="badge bg-primary">Organization</span>
                  </h4>
               </div>
                <div class="form-check form-switch show-error-switch float-end {{ $hideExpenseFields ? 'd-none' : '' }}">
                  <input class="form-check-input" type="checkbox" role="switch" id="showAllFields" {{ $hideExpenseFields ? 'checked' : '' }}>
                  <label class="form-check-label ms-2 fs-5 fw-bold" for="showAllFields">Show All Fields</label>
                </div>                      
               <div class="d-flex align-content-center flex-wrap gap-3">
                  <div class="d-flex gap-3">
                     <a href="{{url('/')}}/parties" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
                  </div>
               </div>
            </div>
            <div class="mb-3">

          </h5>
            @if ($message = Session::get('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <b>{{ $message }}</b>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
      </div>
    
      <div class="col">
          <form class="row g-3" id="parties_form" action="{{url('/')}}/parties/contract-parties-org-add" method="POST" enctype="multipart/form-data">
              @csrf
                <div class="tab-content mt-1">
                    <div class="tab-pane fade active show" role="tabpanel" id="navs_pills_common">
                        <div class="accordion" id="accordionWithIcon">
                        <div class="accordion-item card mt-1 active">
                            <h2 class="accordion-header d-flex align-items-center">
                                <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-1" aria-expanded="true">
                                    Entity Details
                                </button>
                            </h2>
                            <div id="accordionWithIcon-1" class="accordion-collapse collapse show">
                                 <div class="accordion-body">
                                    <hr class="mt-1" />
                                        <div class="row ">
                                            <div class="col-md-6 mt-2 {{$hideExpenseFields ? 'd-none' : ''}}">
                                                <label for="contract_type" class="form-label">Party Type</label>
                                                <div>
                                                  <input type="hidden" id="gst_regex" value="{{ ($parties_label['gst']['regex_pattern']) }}" /> 
                                                  <input type="hidden" id="pan_regex" value="{{ ($parties_label['pan']['regex_pattern']) }}" /> 
                                                  <input type="hidden" id="email_regex" value="{{ ($parties_label['company_email']['regex_pattern']) }}" /> 
                                        
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="contract_type" id="customer" value="customer" {{old('contract_type', 'customer') == 'customer' ? 'checked' : '' }} />
                                                    <label class="form-check-label" for="customer">Customer</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="contract_type" id="vendor" value="vendor" {{old('contract_type', 'customer') == 'vendor' ? 'checked' : '' }}/>
                                                    <label class="form-check-label" for="vendor">Vendor</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="contract_type" id="supplier" value="supplier" {{old('contract_type', 'customer') == 'supplier' ? 'checked' : '' }}/>
                                                    <label class="form-check-label" for="supplier">Supplier</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="contract_type" id="partner" value="partner" {{old('contract_type', 'customer') == 'partner' ? 'checked' : '' }}/>
                                                    <label class="form-check-label" for="partner">Partner</label>
                                                  </div>
                                                </div>
                                              </div>
                                            <div class="col-md-6 mt-2 {{$hideExpenseFields ? 'd-none' : ''}}">
                                                <label for="legal_entity" class="form-label">Legal Entity</label>
                                                <div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="legal_entity" id="corporation" value="corporation" {{old('legal_entity', 'corporation') == 'corporation' ? 'checked' : '' }} />
                                                    <label class="form-check-label" for="corporation">Corporation</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="legal_entity" id="partnership" value="partnership" {{old('legal_entity', 'corporation') == 'partnership' ? 'checked' : '' }}/>
                                                    <label class="form-check-label" for="partnership">Partnership</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="legal_entity" id="individual" value="individual" {{old('legal_entity', 'corporation') == 'individual' ? 'checked' : '' }}/>
                                                    <label class="form-check-label" for="individual">Individual</label>
                                                  </div>
                                                   <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="legal_entity" id="llp" value="llp" {{old('legal_entity', 'corporation') == 'llp' ? 'checked' : '' }}/>
                                                    <label class="form-check-label" for="llp">LLP</label>
                                                  </div>
                                                </div>
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                  <div class="form-group mt-2">
                                                    <label for="organization_type" class="form-label">Organization Type</label>
                                                    <select class="form-select" id="organization_type" name="organization_type">
                                                      <option value="">Select Organization Type</option>
                                                      <option value="firm" {{ old('organization_type') == 'firm' ? 'selected' : '' }}>Firm</option>
                                                      <option value="society" {{ old('organization_type') == 'society' ? 'selected' : '' }}>Society</option>
                                                      <option value="trust" {{ old('organization_type') == 'trust' ? 'selected' : '' }}>Trust</option>
                                                    </select>
                                                  </div>
                                              </div>
                                          <!--<h5 class="card-title mt-3">Customer Type</h5>-->
                                          <hr class="mt-3 {{$hideExpenseFields ? 'd-none' : ''}}"/>
                                          <!-- New fields injected: Customer Type, Payer Type, Entity Type -->
                                          <div class="col-md-6 mt-2">
                                            <label for="customer_type" class="form-label">Customer Type</label>
                                            <div class="mt-2">
                                              <div class="form-check form-check-inline">
                                                <input class="form-check-input customer_type" type="radio" name="customer_type" id="customer_domestic" value="domestic" {{ old('customer_type', 'domestic') == 'domestic' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="customer_domestic">Domestic</label>
                                              </div>
                                              <div class="form-check form-check-inline">
                                                <input class="form-check-input customer_type" type="radio" name="customer_type" id="customer_international" value="international" {{ old('customer_type') == 'international' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="customer_international">International</label>
                                              </div>
                                            </div>
                                          </div>

                                          <div class="col-md-6 mt-2">
                                            <label for="payer_type" class="form-label">Payer Type</label>
                                            <div class="mt-2">
                                              <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="payer_type" id="payer_cash" value="cash" {{ old('payer_type') == 'cash' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="payer_cash">Cash</label>
                                              </div>
                                              <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="payer_type" id="payer_credit" value="credit" {{ old('payer_type') == 'credit' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="payer_credit">Credit</label>
                                              </div>
                                            </div>
                                          </div>

                                          <div class="col-md-6 mt-2">
                                            <label for="entity_type" class="form-label">Entity Type</label>
                                            <select class="form-select" id="entity_type" name="entity_type">
                                              <option value="">Select Entity Type</option>
                                            </select>
                                          </div>
                                          <!-- End new fields -->
                                          <hr class="mt-3"/>
                                          <h5 class="card-title mt-3">Company/Customer Details :</h5>
                                          <div class="col-md-6 mt-2">
                                            <label for="contract_name" class="form-label {{$parties_label['company_name']['is_required']}}">{{$parties_label['company_name']['label_name']}}</label>
                                            <input type="text" class="form-control" id="company_name" name="company_name" {{$parties_label['company_name']['is_required']}} value="{{old('company_name')}}"/>
                                          </div>
                                          
                                          <div class="col-md-6 mt-2 unRequiredFields">
                                            <label for="email" class="form-label {{$parties_label['company_email']['is_required']}}">{{$parties_label['company_email']['label_name']}}</label>
                                            <input type="email" class="form-control" id="email" name="company_email" {{$parties_label['company_email']['is_required']}}  value="{{old('company_email')}}"/>
                                            <div class="invalid-feedback">{{$parties_label['company_email']['error_text']}}</div>
                                          </div>
                                          <div class="col-md-6 mt-2 unRequiredFields">
                                            <label for="vendor_code" class="form-label">Vendor Code</label>
                                            <input type="text" class="form-control" id="vendor_code" name="vendor_code" value="{{old('vendor_code')}}"/>
                                          </div>
                                          <div class="col-md-6 mt-2 unRequiredFields">
                                            <label for="active_vendor_code" class="form-label">Active Vendor Code</label>
                                            <input type="text" class="form-control" id="active_vendor_code" name="active_vendor_code" value="{{old('active_vendor_code')}}"/>
                                          </div>
                                          <div class="col-md-6 mt-2 unRequiredFields gst-field">
                                            <label for="gst" class="form-label {{$parties_label['gst']['is_required']}}">{{$parties_label['gst']['label_name']}} <i class="ti ti-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="GST is required for domestic entities unless specified otherwise."></i></label>
                                            <input type="text" class="form-control {{$parties_label['gst']['is_required']}}" id="gstinnumber" name="gst"  {{$parties_label['gst']['is_required']}} value="{{old('gst')}}"/>
                                            <div class="invalid-feedback">{{$parties_label['gst']['error_text']}}</div>
                                          </div>

                                          <!-- Optional GST file upload (not mandatory) -->
                                          <div class="col-md-6 mt-2 unRequiredFields gst-field">
                                            <label for="gst_file" class="form-label">Upload GST (optional)</label>
                                            <input type="file" class="form-control" id="gst_file" name="gst_file" accept=".pdf,.jpg,.jpeg,.png" />
                                            <div class="form-text text-muted">Accepted formats: PDF, JPG, PNG. File is optional.</div>
                                          </div>

                                          <div class="col-md-6 mt-2 unRequiredFields pan-file-field">
                                            <label for="pan" class="form-label {{$parties_label['pan']['is_required']}}">{{$parties_label['pan']['label_name']}}</label>
                                            <input type="text" class="form-control" id="PANNumber" name="pan"  maxlength="10" {{$parties_label['pan']['is_required']}} value="{{old('pan')}}"/>
                                            <div class="invalid-feedback">{{$parties_label['pan']['error_text']}}</div>
                                          </div>

                                          <!-- Optional PAN file upload (not mandatory) -->
                                          <div class="col-md-6 mt-2 unRequiredFields pan-file-field">
                                            <label for="pan_file" class="form-label {{$parties_label['pan']['is_required']}}">Upload PAN <i class="ti ti-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Upload PAN document (PDF/JPG/PNG)."></i></label>
                                            <input type="file" class="form-control" id="pan_file" name="pan_file" accept=".pdf,.jpg,.jpeg,.png" />
                                            <div class="form-text text-muted">Accepted formats: PDF, JPG, PNG. File is optional.</div>
                                          </div>

                                          <!-- International-only fields -->
                                          <div class="col-md-6 mt-2 international-only" style="display:none;">
                                            <label for="corporate_registration_number" class="form-label required">Corporate Registration Number <i class="ti ti-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Corporate registration number is required for international customers."></i></label>
                                            <input type="text" class="form-control" id="corporate_registration_number" name="corporate_registration_number" />
                                          </div>
                                          <div class="col-md-6 mt-2 international-only" style="display:none;">
                                            <label for="tax_residency_certificate" class="form-label required">Tax Residency Certificate <i class="ti ti-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Upload Tax Residency Certificate (PDF/JPG/PNG)."></i></label>
                                            <input type="file" class="form-control" id="tax_residency_certificate" name="tax_residency_certificate" accept=".pdf,.jpg,.jpeg,.png" />
                                            <div class="form-text text-muted">Upload Tax Residency Certificate (PDF/JPG/PNG).</div>
                                          </div>
                                          <div class="col-md-6 mt-2 international-only" style="display:none;">
                                            <label for="no_permanent_establishment" class="form-label required">No Permanent Establishment <i class="ti ti-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Upload No Permanent Establishment document (PDF/JPG/PNG)."></i></label>
                                            <input type="file" class="form-control" id="no_permanent_establishment" name="no_permanent_establishment" accept=".pdf,.jpg,.jpeg,.png" />
                                            <div class="form-text text-muted">Upload No Permanent Establishment document (PDF/JPG/PNG).</div>
                                          </div>

                                          <div class="col-md-6 mt-2 unRequiredFields">
                                            <label for="phone" class="form-label {{$parties_label['company_contact']['is_required']}}">{{$parties_label['company_contact']['label_name']}}</label>
                                            <input type="text" class="form-control numberonly" id="company_contact" name="company_contact" {{$parties_label['company_contact']['is_required']}} value="{{old('company_contact')}}"/>
                                          </div>
                                          <div class="col-md-6 mt-2 unRequiredFields">
                                            <label for="building_no" class="form-label">Building No</label>
                                            <input type="text" class="form-control" id="building_no" name="building_no" value="{{old('building_no')}}"/>
                                          </div>
                                          <div class="col-md-6 mt-2 unRequiredFields">
                                            <label for="area_name" class="form-label">Area Name</label>
                                            <input type="text" class="form-control" id="area_name" name="area_name" value="{{old('area_name')}}"/>
                                          </div>
                                          <div class="col-md-3 mt-2 unRequiredFields">
                                            <label for="landmark" class="form-label">Landmark</label>
                                            <input type="text" class="form-control" id="landmark" name="landmark" value="{{old('landmark')}}"/>
                                          </div>
                                          <div class="col-md-3 mt-2 unRequiredFields">
                                            <label for="city" class="form-label">City</label>
                                            <input type="text" class="form-control" id="city" name="city" value="{{old('city')}}"/>
                                          </div>
                                          <div class="col-md-6 mt-2 unRequiredFields">
                                            <label for="pincode" class="form-label">PinCode</label>
                                            <input type="text" class="form-control numberonly" id="pincode" name="pincode" value="{{old('pincode')}}"/>
                                          </div>
                                          <div class="col-md-6 mt-2 unRequiredFields">
                                            <label for="country" class="form-label required">Country</label>
                                            <select class="select2 form-select" aria-label="select country" id="country" name="country" required>
                                              <option value="">Select Country</option>
                                              @foreach($country as $country_data)
                                                  <option {{old('country', 1) == $country_data->id ? 'selected' : '' }} value="{{$country_data->id}}">{{$country_data->name}}</option>
                                              @endforeach
                                            </select>
                                          </div>
                                          <div class="col-md-6 mt-2 unRequiredFields">
                                            <label for="state" class="form-label">State</label>
                                            <input type="hidden" class="form-control" id="exist_state" value="{{old('state')}}"/>
                                            <select class="form-control" name="state" id="state">
                                                <option value="">--Select--</option>
                                                @if(isset($states))
                                                    @foreach($states as $sval)
                                                    <option {{old('state') == $sval->id ? 'selected' : '' }}>{{$sval->name}}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                          </div>
                                          <div class="col-md-6 mt-2 unRequiredFields">
                                            <label for="website" class="form-label">Website</label>
                                            <input type="text" class="form-control" id="website" name="website" value="{{old('website')}}"/>
                                          </div>                                            
                                          <div class="col-md-12">
                                            @include('contract::contract.createCustomField', ['categoryId' => 5])
                                          </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item card mt-4">
                          <h2 class="accordion-header d-flex align-items-center">
                             <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-2" aria-expanded="false">
                             Authorized Representative Details <i class="ti ti-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Add authorized representatives and their documents."></i>
                             </button>
                          </h2>
                          <div id="accordionWithIcon-2" class="accordion-collapse collapse show">
                             <div class="accordion-body">
                                <hr class="mt-1" />
                                <div class="row g-3">
                                     <div class="row" id="representative_section">
                                          <div class="col-md-6">
                                            <h5 class="card-title"></h5>
                                          </div>
                                          <div class="col-md-6 mt-2" style="text-align: right;">
                                            <input type="hidden" id="position" value="1" />
                                            <a id="representative_add_row" class="btn btn-primary" style="font-size: 12px;color: #fff !important;cursor: pointer;">
                                              <i class="ti ti-plus me-1"></i> Add </a>
                                          </div>  
                                          @php
                                          $representative = old('representative', 
                                          [
                                          [
                                            'representative_name' => '',
                                            'representative_email' => '',
                                            'representative_designation' => '',
                                            'representative_contact' => '',
                                            'representative_nationality' => ''
                                          ]
                                          ]);
                                          @endphp
                                            @foreach($representative as $key=>$representative_data)
                                                @if($key > 0)
                                                <div class="col-md-12 mt-3 representative_row{{$key+1}} representative_row_{{$key+1}}" style="text-align: right;">
                                                  <hr style="margin-top: 15px;" class="representative_row_{{$key+1}}">
                                                  <a id="{{$key+1}}" class="btn btn-danger representative_delete_row" data-index="{{$key+1}}" style="font-size: 12px;color: #fff !important;cursor: pointer;">
                                                    <i class="ti ti-minus me-1"></i> Delete </a>
                                                </div>
                                                @endif
                                                <div class="col-md-6 mt-2 representative_row_{{$key+1}}">
                                                  <label for="representative_name" class="form-label required">Representative Name</label>
                                                  <input type="text" class="form-control" name="representative[{{$key}}][representative_name]" required value="{{$representative_data['representative_name']}}" />
                                                </div>
                                                <div class="col-md-6 mt-2 representative_row_{{$key+1}}">
                                                  <label for="representative_email" class="form-label required">Email ID</label>
                                                  <input type="email" class="form-control representative_email" required id="email_1" name="representative[{{$key}}][representative_email]" value="{{$representative_data['representative_email']}}" />
                                                  <div class="invalid-feedback">Email is invalid</div>
                                                </div>
                                                <div class="col-md-6 mt-2 representative_row_{{$key+1}} unRequiredFields">
                                                  <label for="representative_designation" class="form-label">Designation</label>
                                                  <input type="text" class="form-control" name="representative[{{$key}}][representative_designation]" value="{{$representative_data['representative_designation']}}" />
                                                </div>
                                                <div class="col-md-3 mt-2 representative_row_{{$key+1}} unRequiredFields">
                                                  <label for="representative_contact" class="form-label">Contact Number</label>
                                                  <input type="text" class="form-control numberonly" name="representative[{{$key}}][representative_contact]" value="{{$representative_data['representative_contact']}}" />
                                                </div>
                                                <div class="col-md-3 mt-2 representative_row_{{$key+1}} unRequiredFields">
                                                  <label for="representative_nationality" class="form-label">Nationality</label>
                                                  <input type="text" class="form-control" name="representative[{{$key}}][representative_nationality]" value="{{$representative_data['representative_nationality']}}" />
                                                </div>
                                                <div class="col-md-6 representative_row_{{$key+1}} international-only" style="display:none;">
                                                  <label for="representative_passport" class="form-label required">Passport Number <i class="ti ti-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Passport number is required for international representatives."></i></label>
                                                  <input type="text" class="form-control" name="representative[{{$key}}][passport_number]" value="{{$representative_data['passport_number'] ?? ''}}" />
                                                </div>                                                
                                                <div class="col-md-6 mt-2 representative_row_{{$key+1}} unRequiredFields">
                                                    <label for="representative_nationality" class="form-label">Board Resolution File</label>
                                                    <input type="file" class="form-control" name="representative[{{$key}}][representative_brs]" />
                                                </div>                                                
                                                <hr style="margin-top: 15px;" class="representative_row_{{$key+1}}">
                                            @endforeach
                                      </div>
                                      
                                       <div class="col-md-12 p-2">
                                            @include('contract::contract.createCustomField', ['categoryId' => 6])
                                     </div>
                                </div>
                             </div>
                          </div>
                       </div>
                        <div class="accordion-item card mt-4 unRequiredFields">
                          <h2 class="accordion-header d-flex align-items-center">
                             <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-escalation" aria-expanded="false">
                             Escalation Matrix <i class="ti ti-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Add escalation contacts (Name & Designation)."></i>
                             </button>
                          </h2>
                          <div id="accordionWithIcon-escalation" class="accordion-collapse collapse">
                             <div class="accordion-body">
                                <hr class="mt-1" />
                                <div class="row g-3">
                                     <div class="row" id="escalation_section">
                                          <div class="col-md-6">
                                            <h5 class="card-title"></h5>
                                          </div>
                                          <div class="col-md-6 mt-2" style="text-align: right;">
                                            <input type="hidden" id="escalation_position" value="1" />
                                            <a id="escalation_add_row" class="btn btn-primary" style="font-size: 12px;color: #fff !important;cursor: pointer;">
                                              <i class="ti ti-plus me-1"></i> Add </a>
                                          </div>
                                          @php
                                          $escalation = old('escalation', [[ 'name' => '', 'designation' => '' ]]);
                                          @endphp
                                          @foreach($escalation as $key=>$esc_data)
                                              @if($key > 0)
                                              <div class="col-md-12 escalation_row_{{$key+1}}" style="text-align: right;"><a id="{{$key+1}}" class="btn btn-danger escalation_delete_row" data-index="{{$index+1}}"  style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-minus me-1"></i> Delete </a></div>
                                              @endif
                                                  <div class="col-md-6 mt-2 escalation_row_{{$key+1}}">
                                                    <label for="escalation_name" class="form-label {{ $key == 0 ? 'required' : ''}}">Name</label>
                                                    <input type="text" class="form-control" name="escalation[{{$key}}][name]" value="{{$esc_data['name']}}"  {{ $key == 0 ? 'required' : ''}}/>
                                                  </div>
                                                  <div class="col-md-6 mt-2 escalation_row_{{$key+1}}">
                                                    <label for="escalation_designation" class="form-label {{ $key == 0 ? 'required' : ''}}">Designation</label>
                                                    <input type="text" class="form-control" name="escalation[{{$key}}][designation]" value="{{$esc_data['designation']}}" {{ $key == 0 ? 'required' : ''}}/>
                                                  </div>
                                              <hr style="margin-top: 15px;" class="escalation_row_{{$key+1}}">
                                          @endforeach
                                   </div>
                          </div>
                       </div>
                         </div>
                        </div>
                        <div class="accordion-item card mt-4 unRequiredFields d-none">
                          <h2 class="accordion-header d-flex align-items-center">
                             <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-3" aria-expanded="false">
                             Contract Details
                             </button>
                          </h2>
                          <div id="accordionWithIcon-3" class="accordion-collapse collapse">
                             <div class="accordion-body">
                                <hr class="mt-1" />
                                <div class="row g-3">
                                     <div class="row">
                                             <div class="col-md-6 mt-2">
                                                <label for="engagement_level" class="form-label required">Engagement Level</label>
                                                  <div class="mt-2">
                                                    <div class="form-check form-check-inline">
                                                      <input class="form-check-input engagement_level" type="radio" {{old('engagement_level', 'access_level') == 'access_level' ? 'checked' : '' }} name="engagement_level" id="access_level" value="access_level" required />
                                                      <label class="form-check-label" for="AccessLevel">Access Level</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                      <input class="form-check-input engagement_level" type="radio" {{old('engagement_level') == 'branch' ? 'checked' : '' }} name="engagement_level" id="branch" value="branch" required />
                                                      <label class="form-check-label" for="branch">Branch</label>
                                                    </div>
                                        
                                                    <div id="engagement_access_level-section" class="mt-2" style="display: {{old('engagement_level', 'access_level') == 'branch' ? 'none' : '' }};">
                                                        <select class="form-select mt-2 select2" aria-label="select example" id="engagement_access_level" name="engagement_access_level">
                                                          <option value="">Select Access Level</option>
                                                          @foreach($geo_graph as $geo)
                                                              <option value="{{$geo->id}}" {{old('engagement_access_level', 1) == $geo->id ? 'selected' : '' }} >{!! $geo->tname !!}</option>
                                                          @endforeach
                                                        </select>
                                                    </div>
                                                    <div id="engagement_branch-section" class="mt-2" style="display: {{old('engagement_level') == 'branch' ? '' : 'none' }};">
                                                      <select class="form-select mt-2 select2" aria-label="select example" id="engagement_branch" name="engagement_branch">
                                                        <option value="">Select Branch</option>
                                                        @foreach($branch as $branch_data)
                                                            <option value="{{$branch_data->id}}" {{old('engagement_branch') == $branch_data->id ? 'selected' : '' }}>{{$branch_data->LegalName}}</option>
                                                        @endforeach
                                                      </select>
                                                    </div>
                                                  </div>
                                              </div> 
                                              <div class="col-md-6 mt-2">
                                                <label for="role_in_contract" class="form-label">Role In Contract</label>
                                                <div class="mt-2">
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="role_in_contract" id="buyer" value="buyer" checked />
                                                    <label class="form-check-label" for="buyer">Buyer</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="role_in_contract" id="seller" value="seller"/>
                                                    <label class="form-check-label" for="seller">Seller</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="role_in_contract" id="service_provider" value="service_provider"/>
                                                    <label class="form-check-label" for="service_provider">Service Provider</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="role_in_contract" id="other" value="other"/>
                                                    <label class="form-check-label" for="other">Other</label>
                                                  </div>
                                                </div>
                                              </div>
                                              <div class="col-md-6 mt-3">
                                                <label for="is_related_party" class="form-label">Is Related Party</label>
                                                <div class="form-check form-switch">
                                                  <input type="hidden" name="is_related_party" value="0">
                                                  <input class="form-check-input" type="checkbox" id="is_related_party" name="is_related_party" value="0" value="{{old('is_related_party')}}"/>
                                                </div>
                                              </div>
                                      </div>
                                </div>
                                <div class="col-md-12 p-2">
                                    @include('contract::contract.createCustomField', ['categoryId' => 7])
                                </div>
                             </div>
                          </div>
                       </div>
                      <div class="accordion-item card mt-4 unRequiredFields d-none">
                              <h2 class="accordion-header d-flex align-items-center">
                                 <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-4" aria-expanded="false">
                                 Custom Information
                                 </button>
                              </h2>
                              <div id="accordionWithIcon-4" class="accordion-collapse collapse">
                                 <div class="accordion-body">
                                    <hr class="mt-1" />
                                    <div class="row g-3">
                                          <div class="col-md-12 p-2">
                                            @include('contract::contract.createCustomField', ['categoryId' => 8])
                                     </div>
                                 </div>
                              </div>
                           </div>
                    </div>
               </div>
                    </div>
                </div>
                <div class="buy-now">
                    <button type="submit" class="btn-buy-now btn btn-primary me-sm-3 me-1 waves-effect waves-light">Submit</button>
                </div>
         </form>
      </div>
  </div>
 
<script type="module" src="{{url('/') }}/assets/js/jquery-1.10.2.js"></script>
<script type="module">
      $(document).ready(function() {
           $('#country').select2();
           
           @if($hideExpenseFields)
            $('.unRequiredFields').show();
           @endif
           $('.select2').select2({
            templateSelection: iformat
           });
           // initialize bootstrap tooltips
           $('[data-bs-toggle="tooltip"]').tooltip();
          // show/hide international-only fields based on customer_type
          function toggleInternationalFields(val) {
            if (val === 'international') {
              $('.international-only').show();
              $('#corporate_registration_number').prop('required', true);
              $('#tax_residency_certificate').prop('required', true);
              $('#no_permanent_establishment').prop('required', true);
              // make representative passport inputs required
              $('input[name^="representative"]').each(function(){
                var name = $(this).attr('name');
                if (name.indexOf('passport_number') !== -1) $(this).prop('required', true);
              });
              $('#no_permanent_establishment').prop('required', true);
              // hide GST / PAN fields and files
              $('.gst-field, .gst-file-field, .pan-field, .pan-file-field').hide();
              $('#gstinnumber, #PANNumber').prop('required', false).removeClass('is-invalid');
            } else {
              $('.international-only').hide();
              $('#corporate_registration_number').prop('required', false);
              $('#tax_residency_certificate').prop('required', false);
              $('#no_permanent_establishment').prop('required', false);
              
              // show GST / PAN fields and files
              $('.gst-field, .gst-file-field, .pan-field, .pan-file-field').show();
              $('#gstinnumber, #PANNumber').prop('required', true);              
              $('input[name^="representative"]').each(function(){
                var name = $(this).attr('name');
                if (name.indexOf('passport_number') !== -1) $(this).prop('required', false);
              });
            }
          }

          // initialize on load
          var initialCustomerType = $('input[name="customer_type"]:checked').val() || 'domestic';
          toggleInternationalFields(initialCustomerType);
          $(document).on('change', 'input[name="customer_type"]', function() {
            toggleInternationalFields($(this).val());
          });
          
           var gst_regex = <?php echo json_encode($parties_label['gst']['regex_pattern']); ?>;
           var pan_regex = <?php echo json_encode($parties_label['pan']['regex_pattern']); ?>;
           var email_regex = <?php echo json_encode($parties_label['company_email']['regex_pattern']); ?>; 

            // Populate Entity Type select from server by scope (domestic/international)
            function populateEntityOptions(type) {
              var $entity = $('#entity_type');
              var old = "{{ old('entity_type') }}";
              $entity.empty();
              $entity.append($('<option>').val('').text('Select Entity Type'));
              if (!type) return;
              var url = (typeof APP_URL !== 'undefined' ? APP_URL : '') + '/parties/parties-get-entity-types';
              $.getJSON(url, { scope: type })
                .done(function(items){
                  items.forEach(function(opt){
                    var $o = $('<option>').val(opt.id).text(opt.name);
                    if (String(old) === String(opt.id)) $o.prop('selected', true);
                    $entity.append($o);
                  });
                })
                .fail(function(){
                  // fallback: leave the select with just default option
                });
            }

            // initialize based on old value or default
            var initialType = $('input[name="customer_type"]:checked').val() || 'domestic';
            populateEntityOptions(initialType);

            $(document).on('change', 'input[name="customer_type"]', function() {
              var val = $(this).val();
              populateEntityOptions(val);
            });

            $(document).on('click', '.representative_delete_row', e => {
                let $select = $(e.target);
                let index = $select.data("index");
                $('.representative_row_'+index).remove();
            });  

            $(document).on('blur', '.representative_email', e => {
                let $select = $(e.target);
                var attr_id = $select.attr('id');
                let value = $select.val();
                //alert(attr_id);
                var testEmail = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i;
                if (testEmail.test(value)) 
                {
                    $('#'+attr_id).removeClass('is-invalid');
                    return true;
                }
                else{
                    $('#'+attr_id).addClass('is-invalid');
                    return false;
                } 
            });
            
            $(".flatpickr").flatpickr({
              altInput: true,
              altFormat: "F j, Y",
              dateFormat: "Y-m-d",
              prevArrow: "<i class='fa fa-chevron-left'></i>",
              nextArrow: "<i class='fa fa-chevron-right'></i>"
            });
      }); 

      function iformat(icon) {
        return $('<span>'+ $.trim(icon.text) + '</span>');
      }      
</script>
<script type="module" src="{{url('/')}}/Modules/ContractParties/resources/assets/js/script.js"></script>
 @endsection