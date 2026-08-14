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
    /* .unRequiredFields{ display: none } */
    #showAllFields{ transform: scale(1.5); }
  </style>  
  
  <div class="row my-4">
 <div class="container">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
               <div class="d-flex flex-column justify-content-center">
                  <h4 class="mb-1">
                    Add New Vendor <span class="badge bg-warning">Individual</span>
                  </h4>
               </div>
                <div class="form-check form-switch show-error-switch float-end">
                  <!-- <input class="form-check-input" type="checkbox" role="switch" id="showAllFields">
                  <label class="form-check-label ms-2 fs-5 fw-bold" for="showAllFields">Show All Fields</label> -->
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
          <form class="row g-3" id="parties_form" action="{{url('/')}}/parties/contract-parties-ind-add" method="POST" enctype="multipart/form-data">
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
                                        <div class="row">
                                            <div class="col-md-6 mt-2">
                                                <!-- <label for="contract_type" class="form-label">Party Type</label> -->
                                                <!-- <div>
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
                                                </div> -->
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                <input type="hidden" name="legal_entity" id="individual" value="individual" {{old('legal_entity', 'individual') == 'individual' ? 'checked' : '' }}/>
                                              </div>
                                              
                                              <h5 class="card-title mt-3">Individual Details :</h5>
                                              <!-- <h5 class="card-title mt-3">Company Details :</h5> -->
                                              <div class="col-md-6 mt-2">
                                                <label for="contract_name" class="form-label {{$parties_label['company_name']['is_required']}}">{{$parties_label['company_name']['label_name']}}</label>
                                                <input type="text" class="form-control" id="company_name" name="company_name" {{$parties_label['company_name']['is_required']}} value="{{old('company_name')}}"/>
                                              </div>
                                              <div class="col-md-6 mt-2">
                                                <label for="email" class="form-label required">Email</label>
                                                <input type="email" class="form-control" id="email" name="company_email" {{$parties_label['company_email']['is_required']}}  value="{{old('company_email')}}"/>
                                                <div class="invalid-feedback">{{$parties_label['company_email']['error_text']}}</div>
                                              </div>
                                              <div class="col-md-6 mt-2 party-sub-types party-sub-individual">
                                                <label for="indid" class="form-label required">Doctor Identity Number</label>
                                                <input type="text" class="form-control required" id="indid" name="gst" value="{{old('gst')}}"/>
                                              </div>
                                              <div class="col-md-6 mt-2 unRequiredFields">
                                                <label for="pan" class="form-label">{{$parties_label['pan']['label_name']}}</label>
                                                <input type="text" class="form-control" id="PANNumber" name="pan"  maxlength="10" />
                                                <div class="invalid-feedback">{{$parties_label['pan']['error_text']}}</div>
                                              </div>
                                              <div class="col-md-6 mt-2 unRequiredFields">
                                                <label for="phone" class="form-label {{$parties_label['company_contact']['is_required']}}">Contact Number</label>
                                                <input type="text" class="form-control numberonly" id="company_contact" name="company_contact"  maxlength="10" {{$parties_label['company_contact']['is_required']}} value="{{old('company_contact')}}"/>
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
                                                <select class="form-control" name="state" id="state" >
                                                    <option value="">--Select--</option>
                                                    @if(isset($states))
                                                        @foreach($states as $sval)
                                                        <option {{old('state') == $sval->id ? 'selected' : '' }}>{{$sval->name}}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                              </div>
                                              <div class="col-md-6 mt-2 unRequiredFields">
                                                <label for="vendor_code" class="form-label">Vendor Code</label>
                                                <input type="text" class="form-control" id="vendor_code" name="vendor_code" value="{{old('vendor_code')}}"/>
                                              </div>
                                              <div class="col-md-6 mt-2 unRequiredFields">
                                                <label for="active_vendor_code" class="form-label">Active Vendor Code</label>
                                                <input type="text" class="form-control" id="active_vendor_code" name="active_vendor_code" value="{{old('active_vendor_code')}}"/>
                                              </div>
                                              <div class="col-md-6 mt-2 unRequiredFields">
                                                <label for="website" class="form-label">Website</label>
                                                <input type="text" class="form-control" id="website" name="website" value="{{old('website')}}"/>
                                              </div>                                            
                                            <div class="col-md-12 unRequiredFields">
                                                    @include('contract::contract.createCustomField', ['categoryId' => 9])
                                             </div>
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
                                                      <input class="form-check-input engagement_level" type="radio" {{old('engagement_level', 'access_level') == 'access_level' ? 'checked' : '' }} name="engagement_level" id="access_level" value="access_level" required />
                                                      <label class="form-check-label" for="AccessLevel">Access Level</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                      <input class="form-check-input engagement_level" type="radio" {{old('engagement_level') == 'branch' ? 'checked' : '' }} name="engagement_level" id="branch" value="branch" required />
                                                      <label class="form-check-label" for="branch">Branch</label>
                                                    </div>
                                        
                                                    <div id="engagement_access_level-section" class="mt-2" style="display: {{old('engagement_level','access_level') == 'branch' ? 'none' : '' }};">
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
                                <div class="col-md-12 p-2 unRequiredFields">
                                    @include('contract::contract.createCustomField', ['categoryId' => 7])
                                </div>
                             </div>
                          </div>
                       </div>
                      <div class="accordion-item card mt-4 unRequiredFields">
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
           $('.select2').select2({
            templateSelection: iformat
           });
           var gst_regex = <?php echo json_encode($parties_label['gst']['regex_pattern']); ?>;
           var pan_regex = <?php echo json_encode($parties_label['pan']['regex_pattern']); ?>;
           var email_regex = <?php echo json_encode($parties_label['company_email']['regex_pattern']); ?>; 

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