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
  'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js',
  'resources/assets/vendor/libs/dropzone/dropzone.js',
  'resources/assets/vendor/libs/cleavejs/cleave.js',
  'resources/assets/vendor/libs/cleavejs/cleave-phone.js'
])
@endsection

  @section('content')
  <link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
  <link href="{{url('/')}}/assets/css/select2.css" rel="stylesheet">
  <style>
    label.required:after { content:"*"; color:red;font-size: 15px; font-weight: 900; }
    .representative_row1
    {
      display: none;
    }
  </style>  
  
    <div class="row my-4">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
               <div class="d-flex flex-column justify-content-center">
                  <h4 class="mb-1">Edit Contract Parties <span class="badge bg-primary">Organization</span></h4>
               </div>
               <div class="d-flex align-content-center flex-wrap gap-3">
                  <div class="d-flex gap-3">
                     <a href="{{url('/')}}/parties/contract-parties-org-edit/{{$parties->id}}" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-warning">Edit</button></a>
                     <a href="{{url('/')}}/parties" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
                  </div>
               </div>
            </div>
        </div>
        <div class="row align-items-center">
          <div class="col-md-12">
            <div class="mb-3">
              <h5 class="card-title">
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
        </div>
        <div class="col">
          <form class="row g-3" id="parties_form" action="{{url('/')}}/parties/contract-parties-edit/{{$parties->id}}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="col-md mb-4 mb-md-2">
                    <div class="accordion mt-3" id="accordionWithIcon">
                        <div class="accordion-item card mt-4 active">
                            <h2 class="accordion-header d-flex align-items-center">
                                 <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-1" aria-expanded="true">
                                 Entity Details
                                 </button>
                              </h2>
                              <div id="accordionWithIcon-1" class="accordion-collapse collapse show">
                                 <div class="accordion-body">
                                    <hr class="mt-1" />
                                        <div class="row">
                                            
                                            <div class="col-md-6 mt-2">
                                                <label for="contract_type" class="form-label">Party Type</label>
                                                <div>
                                                  <input type="hidden" id="gst_regex" value=<?php echo json_encode($parties_label['gst']['regex_pattern']); ?> /> 
                                                  <input type="hidden" id="pan_regex" value=<?php echo json_encode($parties_label['pan']['regex_pattern']); ?> /> 
                                                  <input type="hidden" id="email_regex" value=<?php echo json_encode($parties_label['company_email']['regex_pattern']); ?> /> 
                                        
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="contract_type" id="customer" disabled value="customer"  {{ ($parties->party_type=="customer")? "checked" : "" }} />
                                                    <label class="form-check-label" for="customer">Customer</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="contract_type" id="vendor" disabled value="vendor" {{ ($parties->party_type=="vendor")? "checked" : "" }}/>
                                                    <label class="form-check-label" for="vendor">Vendor</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="contract_type" id="supplier" disabled value="supplier" {{ ($parties->party_type=="supplier")? "checked" : "" }}/>
                                                    <label class="form-check-label" for="supplier">Supplier</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="contract_type" id="partner" disabled value="partner" {{ ($parties->party_type=="partner")? "checked" : "" }}/>
                                                    <label class="form-check-label" for="partner">Partner</label>
                                                  </div>
                                                </div>
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                <label for="legal_entity" class="form-label">Legal Entity</label>
                                                <div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="legal_entity" id="corporation" disabled value="corporation" {{ ($parties->legal_entity=="corporation")? "checked" : "" }} />
                                                    <label class="form-check-label" for="corporation">Corporation</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="legal_entity" id="partnership" disabled value="partnership" {{ ($parties->legal_entity=="partnership")? "checked" : "" }}/>
                                                    <label class="form-check-label" for="partnership">Partnership</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="legal_entity" id="individual" disabled value="individual" {{ ($parties->legal_entity=="individual")? "checked" : "" }}/>
                                                    <label class="form-check-label" for="individual">Individual</label>
                                                  </div>
                                                   <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="legal_entity" id="llp" disabled value="llp" {{ ($parties->legal_entity=="llp")? "checked" : "" }}/>
                                                    <label class="form-check-label" for="llp">LLP</label>
                                                  </div>
                                                </div>
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                <label for="organization_type" class="form-label">Organization Type</label>
                                                <div>
                                                    <p>{{ ucfirst($parties->organization_type) ?? '-' }}</p>
                                                </div>
                                              </div>
                                                </div>
                                              </div>
                                              
                                              <h5 class="card-title mt-3">Company Details :</h5>
                                              <div class="col-md-6 mt-2">
                                                  <h6 class="mb-1">{{$parties_label['company_name']['label_name']}}</h6>
                                                  <p>{{ decryptString($parties->company_name, 'company_name')}}</p>
                                                <!--<label for="contract_name" class="form-label {{$parties_label['company_name']['is_required']}}">{{$parties_label['company_name']['label_name']}}</label>-->
                                                <!--<input type="hidden" name="parties_id" value="{{$parties->id}}" />-->
                                                <!--<input type="text" class="form-control" id="company_name" name="company_name" disabled value="{{ decryptString($parties->company_name, 'company_name')}}" {{$parties_label['company_name']['is_required']}} />-->
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                  <h6 class="mb-1">{{$parties_label['company_email']['label_name']}}</h6>
                                                  <p>{{$parties->company_email}}</p>
                                                <!--<label for="email" class="form-label {{$parties_label['company_email']['is_required']}}">{{$parties_label['company_email']['label_name']}}</label>-->
                                                <!--<input type="email" class="form-control" id="email" name="company_email" disabled value="{{$parties->company_email}}" {{$parties_label['company_email']['is_required']}}  />-->
                                                <!--<div class="invalid-feedback">{{$parties_label['company_email']['error_text']}}</div>-->
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                  <h6 class="mb-1">Vendor Code</h6>
                                                  <p>{{$parties->vendor_code ?? '-'}}</p>
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                  <h6 class="mb-1">Active Vendor Code</h6>
                                                  <p>{{$parties->active_vendor_code ?? '-'}}</p>
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                  <h6 class="mb-1">{{$parties_label['gst']['label_name']}}</h6>
                                                  <p>{{decryptString($parties->gst, 'gst')}}</p>
                                                <!--<label for="gst" class="form-label {{$parties_label['gst']['is_required']}}">{{$parties_label['gst']['label_name']}}</label>-->
                                                <!--<input type="text" class="form-control" id="gstinnumber" name="gst" disabled value="{{decryptString($parties->gst, 'gst')}}"  {{$parties_label['gst']['is_required']}} />-->
                                                <!--<div class="invalid-feedback">{{$parties_label['gst']['error_text']}}</div>-->
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                  <h6 class="mb-1">{{$parties_label['pan']['label_name']}}</h6>
                                                  <p>{{decryptString($parties->pan, 'pan')}}</p>
                                                <!--<label for="pan" class="form-label {{$parties_label['pan']['is_required']}}">{{$parties_label['pan']['label_name']}}</label>-->
                                                <!--<input type="text" class="form-control" id="PANNumber" name="pan" disabled  maxlength="10" value="{{decryptString($parties->pan, 'pan')}}" {{$parties_label['pan']['is_required']}} />-->
                                                <!--<div class="invalid-feedback">{{$parties_label['pan']['error_text']}}</div>-->
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                  <h6 class="mb-1">{{$parties_label['company_contact']['label_name']}}</h6>
                                                  <p>{{$parties->company_contact}}</p>
                                                <!--<label for="phone" class="form-label {{$parties_label['company_contact']['is_required']}}">{{$parties_label['company_contact']['label_name']}}</label>-->
                                                <!--<input type="text" class="form-control numberonly" id="company_contact" disabled name="company_contact"  maxlength="10" value="{{$parties->company_contact}}" {{$parties_label['company_contact']['is_required']}} />-->
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                  <h6 class="mb-1">Building No</h6>
                                                  <p>{{$parties->building_no}}</p>
                                                <!--<label for="building_no" class="form-label required">Building No</label>-->
                                                <!--<input type="text" class="form-control" id="building_no" name="building_no" disabled value="{{$parties->building_no}}" required />-->
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                  <h6 class="mb-1">Area Name</h6>
                                                  <p>{{$parties->area_name}}</p>
                                                <!--<label for="area_name" class="form-label required">Area Name</label>-->
                                                <!--<input type="text" class="form-control" id="area_name" name="area_name" disabled value="{{$parties->area_name}}" required />-->
                                              </div>
                                              <div class="col-md-3 mt-2">
                                                  <h6 class="mb-1">Landmark</h6>
                                                  <p>{{$parties->landmark}}</p>
                                                <!--<label for="landmark" class="form-label">Landmark</label>-->
                                                <!--<input type="text" class="form-control" id="landmark" name="landmark" disabled value="{{$parties->landmark}}"/>-->
                                              </div>
                                              <div class="col-md-3 mt-2">
                                                  <h6 class="mb-1">City</h6>
                                                  <p>{{$parties->city}}</p>
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                  <h6 class="mb-1">PinCode</h6>
                                                  <p>{{$parties->pincode}}</p>
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                 <h6 class="mb-1">Country</h6>
                                                 <p>{{$parties->countryDetails->Name ?? ''}}</p>

                                              </div>
                                              <div class="col-md-6 mt-2">
                                                <label for="state" class="form-label required">State</label>
                                                <input type="hidden" class="form-control" id="exist_state" disabled value="{{$parties->state}}" />
                                                <p>{{$parties->stateDetails->name ?? ''}}</p>
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                  <h6 class="mb-1">Website</h6>
                                                  <p>{{$parties->website}}</p>
                                              </div>
                                            

                                         </div>
                                    </div>
                                    
                                    <div class="col-md-12 p-2">
                                            @include('contract::contract.viewDetailCustomFieldParty', ['categoryId' => 5])
                                     </div>
                                     
                                    <div class="row add_users" style="margin-top: 30px;">
                                       <input type="hidden" id="user_position" value="1" />
                                      
                                       <div class="col-md-6">
                                       </div>
                                    </div>
                                 </div>
                              </div>
                        <div class="accordion-item card mt-4">
                          <h2 class="accordion-header d-flex align-items-center">
                             <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-2" aria-expanded="false">
                             Representative Details
                             </button>
                          </h2>
                          <div id="accordionWithIcon-2" class="accordion-collapse collapse">
                             <div class="accordion-body">
                                <hr class="mt-1" />
                                <div class="row g-3">
                                     <div class="row" id="representative_section">
                                          <div class="col-md-6">
                                            <h5 class="card-title"></h5>
                                          </div>
                                          <div class="col-md-6 mt-2" style="text-align: right;">
                                            <input type="hidden" id="position" value="1" />
                                            <!--<a id="representative_add_row" class="btn btn-primary" style="font-size: 12px;color: #fff !important;cursor: pointer;">-->
                                            <!--  <i class="ti ti-plus me-1"></i> Add </a>-->
                                          </div>  
                                          
                                          @foreach($representative as $key=>$representative_data) 
                                          <div class="col-md-12 mt-3 representative_row{{$key+1}} representative_row_{{$key+1}}" style="text-align: right;">
                                              <!--<hr style="margin-top: 15px;" class="representative_row_{{$key+1}}">-->
                                              <!--<a id="{{$key+1}}" class="btn btn-danger representative_delete_row" data-index="{{$key+1}}"  style="font-size: 12px;color: #fff !important;cursor: pointer;">-->
                                              <!--  <i class="ti ti-minus me-1"></i> Delete </a>-->
                                            </div>
                                              <div class="col-md-6 mt-2 representative_row_{{$key+1}}">
                                                <!--<label for="representative_name" class="form-label required">Representative Name</label>-->
                                                <h6 for="representative_name" class="mb-1">Representative Name</h6>
                                                <input type="hidden" name="representative[{{$key}}][representative_id]" disabled value="{{$representative_data->id}}" />
                                                <p name="representative[{{$key}}][representative_name]">{{$representative_data->representative_name}}</p>
                                                <!--<input type="text" class="form-control" name="representative[{{$key}}][representative_name]"  disabled value="{{$representative_data->representative_name}}" />-->
                                              </div>
                                              <div class="col-md-6 mt-2 representative_row_{{$key+1}}">
                                                <!--<label for="representative_email" class="form-label required">Email ID</label>-->
                                                <h6 for="representative_email" class="mb-1">Email ID</h6>
                                                <!--<input type="email" class="form-control representative_email" id="email_1" name="representative[{{$key}}][representative_email]" disabled value="{{$representative_data->representative_email}}" required />-->
                                                <p class="representative_email" id="email_1" name="representative[{{$key}}][representative_email]">{{$representative_data->representative_email}}</p>
                                                <!--<div class="invalid-feedback">Email is invalid</div>-->
                                              </div>
                                              <div class="col-md-6 mt-2 representative_row_{{$key+1}}">
                                                  <h6 for="representative_designation" class="mb-1">Designation</h6>
                                                  <p name="representative[{{$key}}][representative_designation]">{{$representative_data->representative_designation}}</p>
                                                <!--<label for="representative_designation" class="form-label required">Designation</label>-->
                                                <!--<input type="text" class="form-control" name="representative[{{$key}}][representative_designation]" disabled value="{{$representative_data->representative_designation}}" />-->
                                              </div>
                                              <div class="col-md-3 mt-2 representative_row_{{$key+1}}">
                                                  <h6 for="representative_contact" class="mb-1">Contact Number</h6>
                                                  <p class="numberonly" name="representative[{{$key}}][representative_contact]">{{$representative_data->representative_contact}}</p>
                                                <!--<label for="representative_contact" class="form-label required">Contact Number</label>-->
                                                <!--<input type="text" class="form-control numberonly" name="representative[{{$key}}][representative_contact]"  maxlength="10" disabled value="{{$representative_data->representative_contact}}" required />-->
                                              </div>
                                              <div class="col-md-3 mt-2 representative_row_{{$key+1}}">
                                                  <h6 for="representative_nationality" class="mb-1">Nationality</h6>
                                                  <p name="representative[{{$key}}][representative_nationality]">{{$representative_data->representative_nationality}}</p>
                                                <!--<label for="representative_nationality" class="form-label">Nationality</label>-->
                                                <!--<input type="text" class="form-control" name="representative[{{$key}}][representative_nationality]" disabled value="{{$representative_data->representative_nationality}}" />-->
                                              </div>
                                         
                                           <hr style="margin-top: 15px;" class="representative_row_{{$key+1}}">
                                           @endforeach
                                      </div>
                                </div>
                                <div class="col-md-12 p-2">
                                            @include('contract::contract.viewDetailCustomFieldParty', ['categoryId' => 6])
                                     </div>
                             </div>
                          </div>
                       </div>
                        <div class="accordion-item card mt-4">
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
                                                      <input class="form-check-input engagement_level" type="radio" name="engagement_level" id="access_level" disabled value="access_level" required {{ ($parties->engagement_level == 0)? "checked" : "" }} />
                                                      <label class="form-check-label" for="AccessLevel">Access Level</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                      <input class="form-check-input engagement_level" type="radio" name="engagement_level" id="branch" disabled value="branch" required {{ ($parties->engagement_level == 1)? "checked" : "" }} />
                                                      <label class="form-check-label" for="branch">Branch</label>
                                                    </div>
                                        
                                                    <select class="form-select mt-2" aria-label="select example" id="engagement_access_level" disabled name="engagement_access_level" style="display: {{ ($parties->engagement_level == 1)? '' : 'none' }};">
                                                      <option value="">Select Access Level</option>
                                                        @foreach($geo_graph as $geo)
                                                            <option value="{{$geo->id}}" {{$parties->engagement_access_level == $geo->id  ? 'selected' : ''}}>{!! $geo->tname !!}</option>
                                                        @endforeach
                                                    </select>
                                        
                                                    <select class="form-select mt-2" aria-label="select example" id="engagement_branch" disabled name="engagement_branch" style="display: {{ ($parties->engagement_level == 1)? 'none' : '' }};">
                                                      <option value="">Select Branch</option>
                                                      @foreach($branch as $branch_data)
                                                          <option value="{{$branch_data->id}}" {{$parties->engagement_branch == $branch_data->id  ? 'selected' : ''}}>{{$branch_data->LegalName}}</option>
                                                      @endforeach
                                                    </select>
                                        
                                                  </div>
                                              </div> 
                                              <div class="col-md-6 mt-2">
                                                <label for="role_in_contract" class="form-label">Role In Contract</label>
                                                <div class="mt-2">
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="role_in_contract" id="buyer" disabled value="buyer" {{ ($parties->role_in_contract=="buyer")? "checked" : "" }} />
                                                    <label class="form-check-label" for="buyer">Buyer</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="role_in_contract" id="seller" disabled value="seller" {{ ($parties->role_in_contract=="seller")? "checked" : "" }}/>
                                                    <label class="form-check-label" for="seller">Seller</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="role_in_contract" id="service_provider" disabled value="service_provider" {{ ($parties->role_in_contract=="service_provider")? "checked" : "" }}/>
                                                    <label class="form-check-label" for="service_provider">Service Provider</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="role_in_contract" id="other" disabled value="other" {{ ($parties->role_in_contract=="other")? "checked" : "" }}/>
                                                    <label class="form-check-label" for="other">Other</label>
                                                  </div>
                                                </div>
                                              </div>
                                              <div class="col-md-6 mt-3">
                                                <label for="is_related_party" class="form-label">Is Related Party</label>
                                                <div class="form-check form-switch">
                                                  <input type="hidden" name="is_related_party" disabled value="{{$parties->is_related_party}}">
                                                  <input class="form-check-input" type="checkbox" id="is_related_party" name="is_related_party" {{ ($parties->is_related_party== 1)? "checked" : "" }} value="{{$parties->is_related_party}}" />
                                                </div>
                                              </div>
                                          
                                      </div>
                                </div>
                             </div>
                             <div class="col-md-12 p-2">
                                            @include('contract::contract.viewDetailCustomFieldParty', ['categoryId' => 7])
                                     </div>
                          </div>
                       </div>
                           <div class="accordion-item card mt-4">
                              <h2 class="accordion-header d-flex align-items-center">
                                 <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-4" aria-expanded="false">
                                 Custom Information
                                 </button>
                              </h2>
                              <div id="accordionWithIcon-4" class="accordion-collapse collapse">
                                 <div class="accordion-body">
                                    <hr class="mt-1" />
                                    <div class="col-md-12 p-2">
                                             <div class="col-12 ">
                              <h6 class="mt-4">Custom Fields</h6>
                              <hr class="mt-0">
                           </div>
                                            @include('contract::contract.viewDetailCustomFieldParty', ['categoryId' => 8])
                                     </div>
                                    <div class="row g-3">
                                         <div class="row">
                                              
                                          </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                    </div>
               </div>
               <div class="buy-now">
                <!--<a href="https://1.envato.market/vuexy_admin" target="_blank" class="btn btn-primary btn-buy-now waves-effect waves-light">Submit</a>-->
    
                <!--<button type="submit" class="btn-buy-now btn btn-primary me-sm-3 me-1 waves-effect waves-light">Submit</button>-->
             </div>
         </form>

        </div>
    </div>
        
    
    <script type="module" src="{{url('/') }}/assets/js/jquery-1.10.2.js"></script>
    <script type="module">
      $(document).ready(function() {
          $('#country').select2();
           var gst_regex = <?php echo json_encode($parties_label['gst']['regex_pattern']); ?>;
           var pan_regex = <?php echo json_encode($parties_label['pan']['regex_pattern']); ?>;
           var email_regex = <?php echo json_encode($parties_label['company_email']['regex_pattern']); ?>;

           if ($('.engagement_level').is(':checked'))
           {
              var selected_value =  $('.engagement_level:checked').attr('value');
              if(selected_value == "access_level")
              {
                $('#engagement_branch').css('display','none');
                $('#engagement_access_level').css('display','block');
                $("#engagement_branch").prop("required", false);
                $("#engagement_access_level").prop("required", true);
              }else
              {
                $('#engagement_branch').css('display','block');
                $('#engagement_access_level').css('display','none');
                $("#engagement_branch").prop("required", true);
                $("#engagement_access_level").prop("required", false);
              }
           }  
           // var country = $("#country").val();
           // alert(country);
           // $("#country").val(country).trigger('change');

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
      }); 
    </script>
    <script type="module" src="{{url('/')}}/Modules/ContractParties/resources/assets/js/script.js"></script>
@endsection 