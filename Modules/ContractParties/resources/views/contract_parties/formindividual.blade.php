<style>
label.required:after {
    content: "*";
    color: red;
    font-size: 15px;
    font-weight: 900;
}    
</style>
 <div class="container">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
               <div class="d-flex flex-column justify-content-center">
                  <h4 class="mb-1">
                    Add New Contract Parties <span class="badge bg-warning">Individual</span>
                  </h4>
               </div>
                <div class="form-check form-switch show-error-switch float-end">
                  <input class="form-check-input" type="checkbox" role="switch" id="showAllFields">
                  <label class="form-check-label ms-2 fs-5 fw-bold" for="showAllFields">Show All Fields</label>
                </div>
            </div>
 </div>               
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
                                              <div class="col-md-6 mt-2">
                                                <input type="hidden" name="legal_entity" id="individual" value="individual" {{old('legal_entity', 'individual') == 'individual' ? 'checked' : '' }}/>
                                              </div>
                                              
                                              <h5 class="card-title mt-3">Company Details :</h5>
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
                                                <label for="indid" class="form-label required">Identity Number</label>
                                                <input type="text" class="form-control required" id="indid" name="gst" value="{{old('gst')}}"/>
                                              </div>
                                              <div class="col-md-6 mt-2 unRequiredFields">
                                                <label for="pan" class="form-label">{{$parties_label['pan']['label_name']}}</label>
                                                <input type="text" class="form-control" id="PANNumber" name="pan"  maxlength="10" />
                                                <div class="invalid-feedback">{{$parties_label['pan']['error_text']}}</div>
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
                <div id="response">
                    
                </div>
                <div class="buy-now">
                    <button type="submit" class="btn-buy-now btn btn-primary me-sm-3 me-1 waves-effect waves-light">Submit</button>
                </div>
         </form>
         
<script>
    'use strict';


$(document).ready(function() {  
    
    $('#parties_form .btn-buy-now').attr('disabled', 'disabled');
    
    $('.groupby').each(function(){
        let sectionCate = $(this).data('catet') ?? 0;
        if(sectionCate > 0){
            $('#parties_form .customFieldTitleSection_' + sectionCate).show();
        }
    });
    
    $('#parties_form .form-control, #parties_form .form-select').change(function (event) {
        disEnableButton();
    });

    //@date:: 18 May 2024,  @author :: Mangaleswari, @desc:: allow numbers only
    $('.numberonly').keypress(function (e) {    
        var charCode = (e.which) ? e.which : event.keyCode
        if (String.fromCharCode(charCode).match(/[^0-9]/g))
            return false;
    });
    $('#parties_form #email,.representative_email').blur(function() {
        var attr_id = $(this).attr('id');
        //alert(attr_id);
        var attr_req = $(this).attr('required');
        if(attr_req){        
            var testEmail = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i;
            if (testEmail.test(this.value)) 
            {
                $('#'+attr_id).removeClass('is-invalid');
                disEnableButton();
                return true;
            }
            else{
                $('#'+attr_id).addClass('is-invalid');
                disEnableButton();
                return false;
            } 
        }
    });

    $("#parties_form #gstinnumber").change(function(){ 
        var inputvalues = $(this).val();
        var gst_regex = $('#parties_form #gst_regex').val();
        var gstinformat = new RegExp(gst_regex);
        
         if(inputvalues != ""){
            if (gstinformat.test(inputvalues)) {
                $('#parties_form #gstinnumber').removeClass('is-invalid');
                disEnableButton();
                var PANNumber = inputvalues.slice(2, 12);
                $('#parties_form #PANNumber').val(PANNumber);
                return true;
            } else {
                $('#parties_form #gstinnumber').addClass('is-invalid');
                disEnableButton();
                $("#parties_form #gstinnumber").focus();
                return false;
            }
         }else{
          $('#gstinnumber').removeClass('is-invalid');  
         }

    });

    //@date:: 21 May 2024,  @author :: Mangaleswari, @desc:: PAN validation
    $('#parties_form #PANNumber').change(function (event) {   
          var pan_regex = $('#parties_form #pan_regex').val();  
          var regExp = pan_regex; 
          var txtpan = $(this).val(); 
          if( txtpan.match(regExp) ){ 
            $('#parties_form #PANNumber').removeClass('is-invalid');
            disEnableButton();
            return true;
          }
          else {
           $('#parties_form #PANNumber').addClass('is-invalid');
           disEnableButton();
           return false;
           event.preventDefault(); 
          } 
    });
    //@date:: 21 May 2024,  @author :: Mangaleswari, @desc:: is_related_party switch funtion
    $("#parties_form #is_related_party").on('change', function() {
        if ($(this).is(':checked')) {
            $(this).attr('value', 1);
        }
        else {
           $(this).attr('value', 0);
        }
    });

    //@date:: 24 May 2024,  @author :: Mangaleswari, @desc:: Engagement Level funtion
    $(".engagement_level").on('change', function() {

        var id = $(this).attr('id');
        if(id == "branch")
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
    });
     var $country = $('#parties_form #country');     
  
    $(document).on('change','#parties_form #country',function() {
        let countryID = $(this).val();
        var token = "{{ csrf_token() }}";
        var $state = $("#parties_form #state");

        if (countryID) {
            $state.empty();
            $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: APP_URL + "/parties/getState", // if you say $(this) here it will refer to the ajax call not $('#country')
                data : {'Countryid' : $country.val()},
                type : 'POST',
                dataType : 'json',
                success:function(data) {
                    if(data.length != 0)
                    {
                        $state.empty();
                        $state.append($("<option></option>").attr("value", "").text("--Select State--"));
                        $.each(data, function(key,value) {
                            $state.append($("<option></option>").attr("value", value.id).text(value.name)); // name refers to the objects value when you do you ->lists('name', 'id') in laravel
                        });
                        // $state.select2({
                        //     dropdownParent: $('#onboardHorizontalImageModal')
                        // });
                        if($('#exist_state').val() != '')
                        {
                            $("#state").val($('#exist_state').val()).trigger('change'); 
                        }
                    }else 
                    {
                        $state.empty();
                        $state.val("").trigger('change');
                        $state.append($("<option></option>").attr("value", "").text("--Select State--"));
                        return false;
                    }                    
                }
            });
        }else 
        {
            $state.empty();
            $state.append($("<option></option>").attr("value", "").text("--Select--"));
            return false;
        }
    }).trigger('change');
     //@date:: 29 May 2024,  @author :: Mangaleswari, @desc:: upper limit should be greater than lower limit
    $("#upper_limit,#lower_limit,#location,#department,#category,#contract_type").on('change', function() {

        var upper_limit_value = $('#upper_limit').val();
        var lower_limit_value = $('#lower_limit').val();
        if(upper_limit_value !='' && lower_limit_value !='')
        {
            if(parseInt(upper_limit_value) <= parseInt(lower_limit_value))
            {
                 $('#upper_limit').addClass('is-invalid');
                 disEnableButton();
                 return false;
            }else
            {
                $('#upper_limit').removeClass('is-invalid');
                disEnableButton();
                 check_limit();
            }
        }else
        {
            return false;
        }
    });   

    //@date:: 24 May 2024,  @author :: Mangaleswari, @desc:: 
    $(".approval_status").on('change', function() {
        var id = $(this).attr('id');
        if(id == 'auto')
        {
            $('.add_users').hide();
            $("#approval_required_users_1").prop("required", false);
        }else
        {
            $('.add_users').show();
            var approval_status = $('#approval_status').val();
            var users = $('.add_users').html();
            if(!$.trim( $('.add_users').html() ).length && (approval_status == 'auto') )  
            {
                $('.add_users').append('<input type="hidden" id="user_position" value="0" />');
                var index = parseInt($('#user_position').val())+1;
              $.ajax({
                    headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                    url: "/financial-add-users/"+index,
                    type : "GET",
                    dataType: "html",
                    success:function(data) {
                        if(data)
                        {
                            var position = parseInt($('#user_position').val())+1;
                            $('.add_users').last().append(data);
                            $('.users').select2();
                            $('#user_position').val(position);
                            $("#approval_required_users_1").prop("required", true);                
                            $('.user_row_operation a:first').remove();
                            $('.user_row_operation').first().prepend('<a class="btn-success user_add_row" data-mode="no_auto" style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-plus me-1"></i></a>');
                            var $users = $("#approval_required_users_1");
                            get_users($users,1)
                            return true;
                        }else
                        {
                             return false;
                        }
                    },
                    error:function(err){
                        var responseJSON = err.responseJSON;
                        console.log('responseJSON',responseJSON);
                        return false;
                    }
              }); 
            }else
            {
                $("#approval_required_users_1").prop("required", true);
            }
        }
    });  

    //@date:: 05 Jun 2024,  @author :: Mangaleswari, @desc:: PAN validation
    $('#financial_save').click(function (event) {  
        var upper_limit = $("#upper_limit").val();
        var lower_limit = $("#lower_limit").val();
        if(upper_limit !='' && lower_limit =='')
        {
            $('#lower_limit_error').html("lower_limit should not be empty");
            $('#lower_limit').addClass('is-invalid');
            disEnableButton();
            return false;
        }else if(lower_limit !='' && upper_limit =='')
        {
            $('#upper_limit_error').html("upper_limit should not be empty");
            $('#upper_limit').addClass('is-invalid');
            disEnableButton();
            return false;
        }else 
        {
            return true;
        }
    });     
    $(document).on('click', '.user_add_row', function (event) {
        var index = parseInt($('#user_position').val())+1;
        var mode =  $(this).data('mode');
          $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: "/financial-add-users/"+index,
                type : "GET",
                dataType: "html",
                success:function(data) {
                    //console.log('data',data);
                    if(data)
                    {
                        var position = parseInt($('#user_position').val())+1;
                        $('.add_users').last().append(data);
                        $('.users').select2();
                        $('#user_position').val(position);
                        if(mode == "no_auto")
                        {
                            $("#approval_required_users_1").prop("required", true);                
                            $('.user_row_operation a:first').remove();
                            $('.user_row_operation').first().prepend('<a class="btn-success user_add_row" data-mode="no_auto" style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-plus me-1"></i></a>');
                            var $users = $("#approval_required_users_1");
                            get_users($users,1)
                        }
                        return true;
                    }else
                    {
                         return false;
                    }
                },
                error:function(err){
                    var responseJSON = err.responseJSON;
                    console.log('responseJSON',responseJSON);
                    // var message = responseJSON.message;
                    // console.log('error_message',message);
                    return false;
                }
        }); 
    });
    
    $(document).on('click', '.changePartySubtype', function (event) {
        var subtype =  $(this).data('subtype');
        $('#navs_pills_common').fadeOut(250);
        setTimeout(function(){
            $('#partysub').val(subtype);
            $('.party-sub-types').hide();
            $(`.party-sub-${subtype}`).show();
            $('.party-sub-types input.required').removeAttr('required');
            $(`.party-sub-${subtype} input.required`).attr('required', true);
            $('#navs_pills_common').fadeIn("slow");
        }, 250);
    });
    
    $('#parties_form').validate({
        ignore: [],
        errorPlacement: function (error, element) {
            if (element.hasClass('select2-hidden-accessible')) {
                error.insertAfter(element.next('.select2'));
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function (form) {

            $.ajax({
                url: APP_URL + '/parties/contract-parties-ind-add?by=ajax',
                type: 'POST', // Use the form's method attribute as the HTTP method
                data: $(form).serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    
                    if(response.message){
                        var datacut = $('.popap').attr('data-cut');
    
                        var text = response.company_name;
                        var value = response.id;
    
                        $('.group-ry.gropuid' + datacut).find('.partySubType').trigger('change');
    
                        setTimeout(() => {
                            $('.btn-close').trigger('click');
                        }, 100);
                        
                        $('#response').html('<p> Party Added Successfully please close popup and choose party</p>');                    
    
                        $('#parties_form')[0].reset();
                    }else{
                        $('#response').html('<p class="px-2 text-danger">An error occurred: ' + response.join('\n') + '</p>');
                    }
                },
                error: function (xhr, status, error) {
                    // Handle error
                    $('#response').html('<p>An error occurred: ' + error + '</p>');
                }
            });
        }
    });    
});
    //@date:: 21 May 2024,  @author :: Mangaleswari, @desc:: Representative delete row funtion
    function representative_delete_row(val)
    {
        $('.representative_row_'+val).remove();
    }
    function representative_email(id,value)
    {
        var testEmail = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i;
        if (testEmail.test(value)) 
        {
            $('#email_'+id).removeClass('is-invalid');
            disEnableButton();
            return true;
        }
        else{
            $('#email_'+id).addClass('is-invalid');
            disEnableButton();
            return false;
        } 
    }
    //@date:: 28 May 2024,  @author :: Mangaleswari, @desc:: user delete row funtion
    function user_delete_row(val)
    {
        $('.user_row_'+val).remove();
    }

    function check_limit()
    {
        var location = $('#location').val();
        var department = $('#department').val();
        var category = $('#category').val();
        var contract_type = $('#contract_type').val();
        var upper_limit_value = $('#upper_limit').val();
        var lower_limit_value = $('#lower_limit').val();

        if(location != '' && department != '' && category != '' && contract_type != '' && upper_limit_value != '' && lower_limit_value != '')
        {
            var myFormData =  $('#financial_form').serialize();
             //console.log('myFormData',myFormData);
             $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: "/check_limit", // if you say $(this) here it will refer to the ajax call not $('#country')
                data : myFormData,
                type : "POST",
                dataType: "json",
                success:function(data) {
                    //console.log('data',data);
                    if(data.status == false)
                    {
                        $('#upper_limit_error').html(data.message);
                        $('#upper_limit').addClass('is-invalid');
                        disEnableButton();
                        $('#financial_save').hide();
                        return false;
                    }else
                    {
                        $('#financial_save').show();
                         return true;
                    }
                },
                error:function(err){
                    var responseJSON = err.responseJSON;
                    var message = responseJSON.message;
                    console.log('error_message',message);
                    return false;
                }
            }); 
        }else
        {
             return false;
        }
    }
    //@date:: 28 May 2024,  @author :: Mangaleswari, @desc:: users add row funtion
    function user_add_row(mode)
    {
        var index = parseInt($('#user_position').val())+1;
        // $('.add_users').last().append('<div class="col-md-6 user_row_'+user_position+'"><div class="row" style="" id=""><div class="col-md-6 select_users" style="margin-top: 20px;"><select class="form-select users" aria-label="select example" id="users_'+user_position+'" name="approval_required_users[]"><option value="">Select Approver</option></select></div><div class="col-md-6 select_users_btn" style="margin-top: 20px;text-align: center;"><a id="" class="btn-danger" onclick = "user_delete_row('+user_position+')" style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-minus me-1"></i> </a></div></div></div><div class="col-md-6 user_row_'+user_position+'"></div>');
        // $('#users_'+user_position).select2();
        // $('#user_position').val(user_position);
        // var $users = $("#users_"+user_position);
        // get_users($users,user_position)

        $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: "/financial-add-users/"+index,
                type : "GET",
                dataType: "html",
                success:function(data) {
                    //console.log('data',data);
                    if(data)
                    {
                        var position = parseInt($('#user_position').val())+1;
                        $('.add_users').last().append(data);
                        $('.users').select2();
                        $('#user_position').val(position);
                        if(mode == "no_auto")
                        {
                            $("#approval_required_users_1").prop("required", true);                
                            $('.user_row_operation a:first').remove();
                            $('.user_row_operation').first().prepend('<a class="btn-success user_add_row" data-mode="no_auto" style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-plus me-1"></i></a>');
                            var $users = $("#approval_required_users_1");
                            get_users($users,1)
                        }
                        return true;
                    }else
                    {
                         return false;
                    }
                },
                error:function(err){
                    var responseJSON = err.responseJSON;
                    console.log('responseJSON',responseJSON);
                    // var message = responseJSON.message;
                    // console.log('error_message',message);
                    return false;
                }
        }); 
    }
    function get_users($users,user_position)
    {
        $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: "/getUsers", // if you say $(this) here it will refer to the ajax call not $('#country')
                type : 'GET',
                dataType : 'json',
                success:function(data) {
                    //console.log("datavalue",data);
                    if(data.length != 0)
                    {
                        $users.empty();
                        $users.append($("<option></option>").attr("value", "").text("--Select Approver--"));
                        $.each(data, function(key,value) {
                            // console.log("value",value);
                            $users.append($("<option></option>").attr("value", value.id+":"+value.FirstName).text(value.FirstName)); // name refers to the objects value when you do you ->lists('name', 'id') in laravel
                        });
                        $users.select2();
                        var exclude_users=[]; 
                         $('select[name="approval_required_users[]"] option:selected').each(function() {
                          exclude_users.push($(this).val());
                         });
                         $("#users_"+user_position+" > option").attr("disabled", function() { 
                             return exclude_users.includes($(this).val());     //Disable if value in exclude_users
                         });
                        //console.log("exclude_users",exclude_users);
                    }else 
                    {
                        $users.empty();
                        $users.val("").trigger('change');
                        $users.append($("<option></option>").attr("value", "").text("--Select Approver--"));
                        return false;
                    }                    
                }
        });
    }
    
    function disEnableButton(){
        let isValid = true;
        $('#parties_form [required]').each(function(){
            if($(this).attr('type') == 'radio'){
                let radioName = $(this).attr('name');
                if($(`input[name=${radioName}]:checked`).length == 0){
                    isValid = false;
                }
            }else{
                if ( $(this).val() === '' ){
                    isValid = false;
                }
            }
        });
        if($('#parties_form .is-invalid').length > 0 || !isValid){
            $('#parties_form .btn-buy-now').attr('disabled', 'disabled');
        }else{
          $('#parties_form .btn-buy-now').attr('disabled', false);  
        }        
    }
</script>         