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
'resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
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
'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('content')
<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />

<div class="row my-4">
  <div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
      <div class="d-flex flex-column justify-content-center">
        <h4 class="mb-1">Edit Contract Parties <span class="badge bg-warning">Individual</span></h4>
      </div>
      <div class="d-flex align-content-center flex-wrap gap-3">
        <div class="d-flex gap-3">
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
  <div>
      @include('parties::contract_parties.contractApprovalsView')
  </div>  
  <div class="col">
    <form class="row g-3" id="parties_form" action="{{url('/')}}/parties/contract-parties-ind-edit/{{$parties->id}}" method="POST" enctype="multipart/form-data">
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
                        <input class="form-check-input" type="radio" name="contract_type" id="customer" value="customer" {{ ($parties->party_type=="customer")? "checked" : "" }} />
                        <label class="form-check-label" for="customer">Customer</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="contract_type" id="vendor" value="vendor" {{ ($parties->party_type=="vendor")? "checked" : "" }} />
                        <label class="form-check-label" for="vendor">Vendor</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="contract_type" id="supplier" value="supplier" {{ ($parties->party_type=="supplier")? "checked" : "" }} />
                        <label class="form-check-label" for="supplier">Supplier</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="contract_type" id="partner" value="partner" {{ ($parties->party_type=="partner")? "checked" : "" }} />
                        <label class="form-check-label" for="partner">Partner</label>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 mt-2">
                    <label for="legal_entity" class="form-label">Legal Entity</label>
                    <div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="legal_entity" id="corporation" value="corporation" {{ ($parties->legal_entity=="corporation")? "checked" : "" }} />
                        <label class="form-check-label" for="corporation">Corporation</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="legal_entity" id="partnership" value="partnership" {{ ($parties->legal_entity=="partnership")? "checked" : "" }} />
                        <label class="form-check-label" for="partnership">Partnership</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="legal_entity" id="individual" value="individual" {{ ($parties->legal_entity=="individual")? "checked" : "" }} />
                        <label class="form-check-label" for="individual">Individual</label>
                      </div>
                          <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="legal_entity" id="llp" value="llp" {{ ($parties->legal_entity=="llp")? "checked" : "" }} />
                        <label class="form-check-label" for="llp">LLP</label>
                      </div>
                    </div>
                  </div>

                  <h5 class="card-title mt-3">Company Details :</h5>
                  <div class="col-md-6 mt-2">
                    <label for="contract_name" class="form-label {{$parties_label['company_name']['is_required']}}">Name</label>
                    <input type="hidden" name="parties_id" value="{{$parties->id}}" />
                    <input type="text" class="form-control" id="company_name" name="company_name" value="{{ decryptString($parties->company_name, 'company_name')}}" {{$parties_label['company_name']['is_required']}} />
                  </div>
                  <div class="col-md-6 mt-2">
                    <label for="email" class="form-label {{$parties_label['company_email']['is_required']}}">Email</label>
                    <input type="email" class="form-control" id="email" name="company_email" value="{{$parties->company_email}}" {{$parties_label['company_email']['is_required']}} />
                    <div class="invalid-feedback">{{$parties_label['company_email']['error_text']}}</div>
                  </div>
                  <div class="col-md-6 mt-2">
                    <label for="pan" class="form-label {{$parties_label['pan']['is_required']}}">{{$parties_label['pan']['label_name']}}</label>
                    <input type="text" class="form-control" id="PANNumber" name="pan" maxlength="10" value="{{decryptString($parties->pan, 'pan')}}" {{$parties_label['pan']['is_required']}} />
                    <div class="invalid-feedback">{{$parties_label['pan']['error_text']}}</div>
                  </div>
                  <div class="col-md-6 mt-2">
                    <label for="phone" class="form-label {{$parties_label['company_contact']['is_required']}}">Contact Number</label>
                    <input type="text" class="form-control numberonly" id="company_contact" name="company_contact" value="{{$parties->company_contact}}" {{$parties_label['company_contact']['is_required']}} />
                  </div>
                  <div class="col-md-6 mt-2">
                    <label for="building_no" class="form-label">Building No</label>
                    <input type="text" class="form-control" id="building_no" name="building_no" value="{{$parties->building_no}}" />
                  </div>
                  <div class="col-md-6 mt-2">
                    <label for="area_name" class="form-label">Area Name</label>
                    <input type="text" class="form-control" id="area_name" name="area_name" value="{{$parties->area_name}}" />
                  </div>
                  <div class="col-md-3 mt-2">
                    <label for="landmark" class="form-label">Landmark</label>
                    <input type="text" class="form-control" id="landmark" name="landmark" value="{{$parties->landmark}}" />
                  </div>
                  <div class="col-md-3 mt-2">
                    <label for="city" class="form-label">City</label>
                    <input type="text" class="form-control" id="city" name="city" value="{{$parties->city}}" />
                  </div>
                  <div class="col-md-6 mt-2">
                    <label for="pincode" class="form-label">PinCode</label>
                    <input type="text" class="form-control numberonly" id="pincode" name="pincode" value="{{$parties->pincode}}" name="pincode" />
                  </div>
                  <div class="col-md-6 mt-2">
                    <label for="country" class="form-label required">Country</label>
                    <select class="select2 form-select" aria-label="select country" id="country" name="country" required>
                      <option value="">Select Country</option>
                      @foreach($country as $country_data)
                      <option value="{{$country_data->id}}" {{$parties->country == $country_data->id  ? 'selected' : ''}}>{{$country_data->name}}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-6 mt-2">
                    <label for="state" class="form-label">State</label>
                    <input type="hidden" class="form-control" id="exist_state" value="{{$parties->state}}" />
                    <select class="form-control" name="state" id="state">
                      <option value="">--Select--</option>
                    </select>
                  </div>
                  <div class="col-md-6 mt-2">
                    <label for="website" class="form-label">Website</label>
                    <input type="text" class="form-control" id="website" name="website" value="{{$parties->website}}" />
                  </div>
                  <div class="col-md-6 mt-2">
                    <label for="vendor_code" class="form-label">Vendor Code</label>
                    <input type="text" class="form-control" id="vendor_code" name="vendor_code" value="{{$parties->vendor_code}}" />
                  </div>
                  <div class="col-md-6 mt-2">
                    <label for="active_vendor_code" class="form-label">Active Vendor Code</label>
                    <input type="text" class="form-control" id="active_vendor_code" name="active_vendor_code" value="{{$parties->active_vendor_code}}" />
                  </div>


                </div>
              </div>

              <div class="col-md-12 mt-2">

                @include('contract::contract.editCustomFieldParty', ['categoryId' => 9])
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
                          <input class="form-check-input engagement_level" type="radio" name="engagement_level" id="access_level" value="access_level" required {{ ($parties->engagement_level == 0)? "checked" : "" }} />
                          <label class="form-check-label" for="AccessLevel">Access Level</label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input engagement_level" type="radio" name="engagement_level" id="branch" value="branch" required {{ ($parties->engagement_level == 1)? "checked" : "" }} />
                          <label class="form-check-label" for="branch">Branch</label>
                        </div>

                        <div id="engagement_access_level-section" class="mt-2" style="display: none;">
                          <select class="select2 form-select mt-2" aria-label="select example" id="engagement_access_level" name="engagement_access_level">
                            <option value="">Select Access Level</option>
                            @foreach($geo_graph as $geo)
                                <option value="{{$geo->id}}" {{$parties->engagement_access_level == $geo->id  ? 'selected' : ''}}>{!! $geo->tname !!}</option>
                            @endforeach
                          </select>
                        </div>
                        <div id="engagement_branch-section" class="mt-2" style="display: none;">
                          <select class="select2 form-select mt-2" aria-label="select example" id="engagement_branch" name="engagement_branch">
                            <option value="">Select Branch</option>
                            @foreach($branch as $branch_data)
                            <option value="{{$branch_data->id}}" {{$parties->engagement_branch == $branch_data->id  ? 'selected' : ''}}>{{$branch_data->LegalName}}</option>
                            @endforeach
                          </select>
                        </div>

                      </div>
                    </div>
                    <div class="col-md-6 mt-2">
                      <label for="role_in_contract" class="form-label">Role In Contract</label>
                      <div class="mt-2">
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="role_in_contract" id="buyer" value="buyer" {{ ($parties->role_in_contract=="buyer")? "checked" : "" }} />
                          <label class="form-check-label" for="buyer">Buyer</label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="role_in_contract" id="seller" value="seller" {{ ($parties->role_in_contract=="seller")? "checked" : "" }} />
                          <label class="form-check-label" for="seller">Seller</label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="role_in_contract" id="service_provider" value="service_provider" {{ ($parties->role_in_contract=="service_provider")? "checked" : "" }} />
                          <label class="form-check-label" for="service_provider">Service Provider</label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="role_in_contract" id="other" value="other" {{ ($parties->role_in_contract=="other")? "checked" : "" }} />
                          <label class="form-check-label" for="other">Other</label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6 mt-3">
                      <label for="is_related_party" class="form-label">Is Related Party</label>
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_related_party" name="is_related_party" {{ ($parties->is_related_party== 1)? "checked" : "" }} value="{{$parties->is_related_party}}" />
                      </div>
                    </div>

                  </div>
                </div>
              </div>
              <div class="col-md-12">
                @include('contract::contract.editCustomFieldParty', ['categoryId' => 11])
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
                <div class="col-md-12 mb-3">
                  @include('contract::contract.editCustomFieldParty', ['categoryId' => 12])
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

    if ($('.engagement_level').is(':checked')) {
      var selected_value = $('.engagement_level:checked').attr('value');
      if(selected_value == "branch")
        {
            $('#engagement_branch-section').css('display','block');
            $('#engagement_access_level-section').css('display','none');
            $("#engagement_branch").prop("required", true);
            $("#engagement_access_level").prop("required", false);
        }else
        {
            $('#engagement_branch-section').css('display','none');
            $('#engagement_access_level-section').css('display','block');
            $("#engagement_branch").prop("required", false);
            $("#engagement_access_level").prop("required", true);
        }      
    }

    $(document).on('click', '.representative_delete_row', e => {
      let $select = $(e.target);
      let index = $select.data("index");
      $('.representative_row_' + index).remove();
    });

    $(document).on('blur', '.representative_email', e => {
      let $select = $(e.target);
      var attr_id = $select.attr('id');
      let value = $select.val();

      var testEmail = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i;
      if (testEmail.test(value)) {
        $('#' + attr_id).removeClass('is-invalid');
        return true;
      } else {
        $('#' + attr_id).addClass('is-invalid');
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