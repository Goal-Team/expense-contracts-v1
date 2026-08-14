'use strict';

(function () {

        $(document).on('click', '.remove-row', function (event) {

          var dataId = $(this).attr('data-id');

          Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            customClass: {
              confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
              cancelButton: 'btn btn-label-secondary waves-effect waves-light'
            },
            buttonsStyling: false
          }).then(function (result) {
            if (result.value) {
                 $.ajax({
                        url: APP_URL + '/parties/contract-parties-delete/'+dataId, 
                        type: 'get', 
                        success: function(response) {
                           Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'Your file has been deleted.',
                customClass: {
                  confirmButton: 'btn btn-success waves-effect waves-light'
                }
              });
              
              location.reload();

                        },
                        error: function(error) {
                            console.log('Error submitting form:', error);
                        }
                    });
              
            } else if (result.dismiss === Swal.DismissReason.cancel) {
              Swal.fire({
                title: 'Cancelled',
                text: 'Your imaginary file is safe :)',
                icon: 'error',
                customClass: {
                  confirmButton: 'btn btn-success waves-effect waves-light'
                }
              });
            }
          });
        });
        
        //For Show All fields in Contract Create
        
        $(document).on('change', '#showAllFields', async function (e){
            if(!$(this).is(':checked')){
                $('.unRequiredFields').hide();
            }else{
                $('.unRequiredFields').show();
            }
        });        
})();

$(document).ready(function() {  
    
    window.parties_delete= function(id,url)
    {     

        if(confirm("Are you sure you want to delete this?")){
            $("#pid_"+id).attr("href", url+"/"+id);
        }
        else{
            return false;
        }
    }
    
    
    //Party Approval / Reject
    $('#modalPopUpApproval').click(function () {
        $("#modalApproveParty").modal('show');
    });
    $('#modalPopUpReject').click(function () {
        $("#modalRejectParty").modal('show');
    });
    
    $('#ApprovalProcessPopup').submit(function (e) {

    e.preventDefault();
    var contract_id = $("#contractPartyId").val();
    var curAppStatus = $("#curAppStatus").val();

    var shortDescrip = $("#comments").val();
    var appRowId = $("#appRowId").val();


    var approveVal = 'approved';

    var nextAppStatus = 'approved';

    var formData = new FormData(this);
    formData.append('id', contract_id);
    formData.append('nextAppStatus', nextAppStatus);
    formData.append('curAppStatus', curAppStatus);
    formData.append('userInputVal', approveVal);
    formData.append('shortDescrip', shortDescrip);
    formData.append('appRowId', appRowId);

    $('#paperIconSub').addClass('loading').attr('disabled', true);
    $.ajax({
        url: APP_URL + '/parties/partyApprovalFlow',
        type: 'POST',
        data: formData,
        processData: false, // Important for file uploads
        contentType: false, // Important for file uploads
        // contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.message == 'successful!') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Successfull',
                    customClass: {
                        confirmButton: 'btn btn-success waves-effect waves-light'
                    }
                });
                location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message ?? 'Something Went Wrong',
                    customClass: {
                        confirmButton: 'btn btn-danger waves-effect waves-light'
                    }
                });
                $('#paperIconSub').removeClass('loading').attr('disabled', false);
                $('#load').css('visibility', 'hidden');
            }
            // 
        },
        error: function (xhr, status, error) {
            // Handle error
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Form submission failed: ' + error,
                customClass: {
                    confirmButton: 'btn btn-danger waves-effect waves-light'
                }
            });
            $('#paperIconSub').removeClass('loading').attr('disabled', false);
            $('#load').css('visibility', 'hidden');
        }
    });

});    
    $('#RejectProcessPopup').submit(function (e) {

    e.preventDefault();
    var contract_id = $("#contractPartyId").val();
    var curAppStatus = $("#curAppStatus").val();

    var shortDescrip = $("#commentsRej").val();
    var appRowId = $("#appRowId").val();


    var approveVal = 'rejected';

    var nextAppStatus = 'rejected';

    var formData = new FormData(this);
    formData.append('id', contract_id);
    formData.append('nextAppStatus', nextAppStatus);
    formData.append('curAppStatus', curAppStatus);
    formData.append('userInputVal', approveVal);
    formData.append('shortDescrip', shortDescrip);
    formData.append('appRowId', appRowId);

    $('#paperIconSub').addClass('loading').attr('disabled', true);
    $.ajax({
        url: APP_URL + '/parties/partyApprovalFlow',
        type: 'POST',
        data: formData,
        processData: false, // Important for file uploads
        contentType: false, // Important for file uploads
        // contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.message == 'successful!') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Successfull',
                    customClass: {
                        confirmButton: 'btn btn-success waves-effect waves-light'
                    }
                });
                location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message ?? 'Something Went Wrong',
                    customClass: {
                        confirmButton: 'btn btn-danger waves-effect waves-light'
                    }
                });
                // location.reload();
                $('#paperIconSub').removeClass('loading').attr('disabled', false);
                $('#load').css('visibility', 'hidden');
            }
            // 
        },
        error: function (xhr, status, error) {
            // Handle error
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Form submission failed: ' + error,
                customClass: {
                    confirmButton: 'btn btn-danger waves-effect waves-light'
                }
            });
            $('#paperIconSub').removeClass('loading').attr('disabled', false);
            $('#load').css('visibility', 'hidden');
            // alert('Form submission failed: ' + error);
        }
    });

});    
    
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
    
    $('#email,.representative_email').blur(function() {
        var attr_id = $(this).attr('id');
        var attr_req = $(this).attr('required');
        if(attr_req){
            //alert(attr_id);
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
    
    $("#gstinnumber").change(function(){ 
        var inputvalues = $(this).val();
        var gst_regex = $('#gst_regex').val();
        var gstinformat = new RegExp(gst_regex);
        
        if(inputvalues != ""){
            if (gstinformat.test(inputvalues)) {
                $('#gstinnumber').removeClass('is-invalid');
                disEnableButton();
                var PANNumber = inputvalues.slice(2, 12);
                $('#PANNumber').val(PANNumber);
                return true;
            } else {
                $('#gstinnumber').addClass('is-invalid');
                disEnableButton();
                $("#gstinnumber").focus();
                return false;
            }
        }else{
          $('#gstinnumber').removeClass('is-invalid');  
        }

    });
    //@date:: 21 May 2024,  @author :: Mangaleswari, @desc:: PAN validation
    $('#PANNumber').change(function (event) {   
          var pan_regex = $('#pan_regex').val();  
          var regExp = pan_regex; 
          var txtpan = $(this).val(); 
          if( txtpan.match(regExp) ){ 
            $('#PANNumber').removeClass('is-invalid');
            disEnableButton();
            return true;
          }
          else {
           $('#PANNumber').addClass('is-invalid');
           disEnableButton();
           return false;
           event.preventDefault(); 
          } 
    });
    //@date:: 21 May 2024,  @author :: Mangaleswari, @desc:: is_related_party switch funtion
    $("#is_related_party").on('change', function() {
        if ($(this).is(':checked')) {
            $(this).attr('value', 1);
        }
        else {
           $(this).attr('value', 0);
        }
    });
    //@date:: 21 May 2024,  @author :: Mangaleswari, @desc:: Representative add row funtion
     
        $(document).on('click', '#representative_add_row', function (event) {

        var position = parseInt($('#position').val())+1;
        var count = parseInt($('#position').val());
        
        $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: APP_URL + "/parties/representative-section/"+count,
                type : "GET",
                dataType: "html",
                success:function(data) {
                    //console.log('data',data);
                    if(data)
                    {
                        $('#representative_section').append(data);
                                                // ensure newly added row respects current customer_type
                                                $('input[name="customer_type"]:checked').trigger('change');                        
                        $('#position').val(position);
                        return true;
                    }else
                    {
                        return false;
                    }
                },
                error:function(err){
                    var responseJSON = err.responseJSON;
                    return false;
                }
        }); 
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
    
    // Escalation Matrix: add/remove rows
    $(document).on('click', '#escalation_add_row', function (event) {
        var position = parseInt($('#escalation_position').val())+1;
        var count = parseInt($('#escalation_position').val());
        var html = "<hr style='margin-top: 15px;' class='escalation_row_"+position+"'>" +
                   "<div class='col-md-12 escalation_row_"+position+"' style='text-align: right;'><a id='"+position+"' class='btn btn-danger escalation_delete_row' data-index='"+position+"' style='font-size: 12px;color: #fff !important;cursor: pointer;'><i class='ti ti-minus me-1'></i> Delete </a></div>" +
                   "<div class='col-md-6 escalation_row_"+position+"'>" +
                   "<label for='escalation_name' class='form-label required'>Name</label>" +
                   "<input type='text' class='form-control' required name='escalation["+count+"][name]' />" +
                   "</div>" +
                   "<div class='col-md-6 escalation_row_"+position+"'>" +
                   "<label for='escalation_designation' class='form-label required'>Designation</label>" +
                   "<input type='text' class='form-control' required name='escalation["+count+"][designation]' />" +
                   "</div>";
        $('#escalation_section').append(html);
        $('#escalation_position').val(position);
    });

    $(document).on('click', '.escalation_delete_row', e => {
        let $select = $(e.target);
        let index = $select.data("index");
        $('.escalation_row_'+index).remove();
    });
    
     var $country = $('#country');     
  
    $('#country').change(function() {
        let countryID = $(this).val();
        var token = "{{ csrf_token() }}";
        var $state = $("#state");

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
                        $state.select2();
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