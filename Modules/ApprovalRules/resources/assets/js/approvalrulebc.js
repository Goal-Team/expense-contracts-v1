$(document).ready(function() {  
    var url = window.location.href;
    var last = url.lastIndexOf('/') +1;
    var base_url =  url.substring(0,last);
    //console.log("base_url",base_url);
    //@date:: 18 May 2024,  @author :: Mangaleswari, @desc:: allow numbers only
    $('.numberonly').keypress(function (e) {    
        var charCode = (e.which) ? e.which : event.keyCode
        if (String.fromCharCode(charCode).match(/[^0-9]/g))
            return false;
    });
    //@date:: 21 May 2024,  @author :: Mangaleswari, @desc:: email validation
    $('#email,.representative_email').blur(function() {
        var attr_id = $(this).attr('id');
        //alert(attr_id);
        var testEmail = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i;
        if (testEmail.test(this.value)) 
        {
            $('#'+attr_id).removeClass('is-invalid');
            return true;
        }
        else{
            $('#'+attr_id).addClass('is-invalid');
            return false;
        } 
    });
    //@date:: 21 May 2024,  @author :: Mangaleswari, @desc:: GSTIN validation
    $("#gstinnumber").change(function(){ 
        var inputvalues = $(this).val();
        var gst_regex = $('#gst_regex').val();
        var gstinformat = new RegExp(gst_regex);

        if (gstinformat.test(inputvalues)) {
            $('#gstinnumber').removeClass('is-invalid');
            var PANNumber = inputvalues.slice(2, 12);
            $('#PANNumber').val(PANNumber);
            return true;
        } else {
            $('#gstinnumber').addClass('is-invalid');
            $("#gstinnumber").focus();
            return false;
        }

    });
    //@date:: 21 May 2024,  @author :: Mangaleswari, @desc:: PAN validation
    $('#PANNumber').change(function (event) {   
          var pan_regex = $('#pan_regex').val();  
          var regExp = pan_regex; 
          var txtpan = $(this).val(); 
          if( txtpan.match(regExp) ){ 
            $('#PANNumber').removeClass('is-invalid');
            return true;
          }
          else {
           $('#PANNumber').addClass('is-invalid');
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
    $('#representative_add_row').click(function (event) {

        var position = parseInt($('#position').val())+1;
        var count = parseInt($('#position').val());

        // $('#representative_section').append('<hr style="margin-top: 15px;" class="representative_row_'+position+'"><div class="col-md-12 representative_row_'+position+'" style="text-align: right;"><a id="'+position+'" class="btn btn-danger representative_delete_row" onclick = "representative_delete_row('+position+')" style="font-size: 12px;"><i class="bx bx-minus-circle me-1"></i> Delete </a></div><div class="col-md-6 representative_row_'+position+'"><label for="representative_name" class="form-label required">Representative Name</label><input type="hidden"  name="representative['+count+'][representative_id]" value=""  /><input type="text" class="form-control"  name="representative['+count+'][representative_name]"  required /></div><div class="col-md-6 representative_row_'+position+'"><label for="representative_email" class="form-label required">Email ID</label><input type="email" class="form-control representative_email" onchange = "representative_email('+position+',this.value)"  id="email_'+position+'" name="representative['+count+'][representative_email]" required /><div class="invalid-feedback">Email is invalid</div></div><div class="col-md-6 representative_row_'+position+'"><label for="representative_designation" class="form-label required">Designation</label><input type="text" class="form-control" name="representative['+count+'][representative_designation]" required /></div><div class="col-md-3 representative_row_'+position+'"><label for="representative_contact" class="form-label required">Contact Number</label><input type="text" class="form-control numberonly" name="representative['+count+'][representative_contact]"  maxlength="10" required /></div><div class="col-md-3 representative_row_'+position+'"><label for="representative_nationality" class="form-label">Nationality</label><input type="text" class="form-control" name="representative['+count+'][representative_nationality]" /></div>');
        
        $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: "/contractsdemo/parties/representative-section/"+count,
                type : "GET",
                dataType: "html",
                success:function(data) {
                    //console.log('data',data);
                    if(data)
                    {
                        $('#representative_section').append(data);
                        $('#position').val(position);
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

    //@date:: 24 May 2024,  @author :: Mangaleswari, @desc:: Engagement Level funtion
    $(".engagement_level").on('change', function() {

        var id = $(this).attr('id');
        if(id == "branch")
        {
            $('#engagement_branch').css('display','block');
            $('#engagement_access_level').css('display','none');
            $("#engagement_branch").prop("required", true);
            $("#engagement_access_level").prop("required", false);
        }else
        {
            $('#engagement_branch').css('display','none');
            $('#engagement_access_level').css('display','block');
            $("#engagement_branch").prop("required", false);
            $("#engagement_access_level").prop("required", true);
        }
    });
     var $country = $('#country');
     var $state = $("#state");

    $('#country').change(function() {
        //alert("calling");
        let countryID = $(this).val();
        var token = "{{ csrf_token() }}";
        if (countryID) {
            $state.empty();
            $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: "/contractsdemo/parties/getState", // if you say $(this) here it will refer to the ajax call not $('#country')
                data : {'Countryid' : $country.val()},
                type : 'POST',
                dataType : 'json',
                success:function(data) {
                    //console.log("datavalue",data);
                    if(data.length != 0)
                    {
                        $state.empty();
                        $state.append($("<option></option>").attr("value", "").text("--Select State--"));
                        $.each(data, function(key,value) {
                            // console.log("value",value);
                            $state.append($("<option></option>").attr("value", value.id).text(value.name)); // name refers to the objects value when you do you ->lists('name', 'id') in laravel
                        });
                        $state.select2();
                        if($('#exist_state').val() != '')
                        {
                            $("#state").select2("val", $('#exist_state').val());
                            //$("#state").val($('#exist_state').val());
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
                 return false;
            }else
            {
                $('#upper_limit').removeClass('is-invalid');
                 check_limit()
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
                // $('.add_users').append('<input type="hidden" id="user_position" value="1" /><div class="col-md-6"><div class="row" style="" id=""><div class="col-md-6 select_users"><select class="form-select users" aria-label="select example" id="approval_required_users_1" name="approval_required_users[]" required><option value="">Select Approver</option></select> </div><div class="col-md-6 select_users_btn" style="text-align: center;"><a id="" class="btn-success user_add_row" onclick ="user_add_row()" style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-plus me-1"></i> </a></div></div></div><div class="col-md-6"></div>');
                $('.add_users').append('<input type="hidden" id="user_position" value="0" />');
                //user_add_row('no_auto')
                var index = parseInt($('#user_position').val())+1;
              $.ajax({
                    headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                    url: "/contractsdemo/contract-setup/financial-add-users/"+index,
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
                        // var message = responseJSON.message;
                        // console.log('error_message',message);
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
            return false;
        }else if(lower_limit !='' && upper_limit =='')
        {
            $('#upper_limit_error').html("upper_limit should not be empty");
            $('#upper_limit').addClass('is-invalid');
            return false;
        }else 
        {
            return true;
        }
    });     
    $('.user_add_row').click(function (event) {  
        //alert("user_add_row");
        var index = parseInt($('#user_position').val())+1;
        var mode =  $(this).data('mode');
          $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: "/contractsdemo/contract-setup/financial-add-users/"+index,
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
            return true;
        }
        else{
            $('#email_'+id).addClass('is-invalid');
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
                url: "/contractsdemo/contract-setup/financial-add-users/"+index,
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
     //@date:: 22 May 2024,  @author :: Mangaleswari, @desc:: delete parties functionality
    function parties_delete(id,url)
    {      
        // var id = $(this).data('id');
        // var url = $(this).data('url');
        $('#delete-paries').attr('data-id',id);

        if(confirm("Are you sure you want to delete this?")){
            $("#pid_"+id).attr("href", url+"/"+id);
        }
        else{
            return false;
        }
    }