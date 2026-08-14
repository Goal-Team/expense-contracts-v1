

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
        var count = $('#position').val();
        $('#representative_section').append('<div class="groupWrapper"><hr style="margin-top: 15px;" class="representative_row_'+position+'"><div class="col-md-12 representative_row_'+position+'" style="text-align: right;"><a id="'+position+'" class="btn btn-danger representative_delete_row" data="'+position+'"  style="font-size: 12px;"><i class="bx bx-minus-circle me-1"></i> Delete </a></div><div class="col-md-6 representative_row_'+position+'"><label for="representative_name" class="form-label required">Representative Name</label><input type="hidden"  name="representative['+count+'][representative_id]" value=""  /><input type="text" class="form-control"  name="representative['+count+'][representative_name]"  required /></div><div class="col-md-6 representative_row_'+position+'"><label for="representative_email" class="form-label required">Email ID</label><input type="email" class="form-control representative_email" onchange = "representative_email('+position+',this.value)"  id="email_'+position+'" name="representative['+count+'][representative_email]" required /><div class="invalid-feedback">Email is invalid</div></div><div class="col-md-6 representative_row_'+position+'"><label for="representative_designation" class="form-label required">Designation</label><input type="text" class="form-control" name="representative['+count+'][representative_designation]" required /></div><div class="col-md-3 representative_row_'+position+'"><label for="representative_contact" class="form-label required">Contact Number</label><input type="text" class="form-control numberonly" name="representative['+count+'][representative_contact]"  maxlength="10" required /></div><div class="col-md-3 representative_row_'+position+'"><label for="representative_nationality" class="form-label">Nationality</label><input type="text" class="form-control" name="representative['+count+'][representative_nationality]" /></div></div>');

        // $('#representative_section').append('<hr style="margin-top: 15px;" class="representative_row_'+position+'"><div class="col-md-12 representative_row_'+position+'" style="text-align: right;"><a id="'+position+'" class="btn btn-danger representative_delete_row" onclick = "representative_delete_row('+position+')" style="font-size: 12px;"><i class="bx bx-minus-circle me-1"></i> Delete </a></div><div class="col-md-6 representative_row_'+position+'"><label for="representative_name" class="form-label required">Representative Name</label><input type="hidden"  name="representative['+count+'][representative_id]" value=""  /><input type="text" class="form-control"  name="representative['+count+'][representative_name]"  required /></div><div class="col-md-6 representative_row_'+position+'"><label for="representative_email" class="form-label required">Email ID</label><input type="email" class="form-control representative_email" onchange = "representative_email('+position+',this.value)"  id="email_'+position+'" name="representative['+count+'][representative_email]" required /><div class="invalid-feedback">Email is invalid</div></div><div class="col-md-6 representative_row_'+position+'"><label for="representative_designation" class="form-label required">Designation</label><input type="text" class="form-control" name="representative['+count+'][representative_designation]" required /></div><div class="col-md-3 representative_row_'+position+'"><label for="representative_contact" class="form-label required">Contact Number</label><input type="text" class="form-control numberonly" name="representative['+count+'][representative_contact]"  maxlength="10" required /></div><div class="col-md-3 representative_row_'+position+'"><label for="representative_nationality" class="form-label">Nationality</label><input type="text" class="form-control" name="representative['+count+'][representative_nationality]" /></div>');
        $('#position').val(position);
    }); 

    //@date:: 22 May 2024,  @author :: Mangaleswari, @desc:: delete parties functionality
    $(".parties_delete").click(function(){
        var id = $(this).data('id');
        var url = $(this).data('url');
        $('#delete-paries').attr('data-id',id);

        if(confirm("Are you sure you want to delete this?")){
            $("#pid_"+id).attr("href", url+"/"+id);
        }
        else{
            return false;
        }
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
                url: "http://127.0.0.1:8000/getState", // if you say $(this) here it will refer to the ajax call not $('#country')
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

    //@date:: 28 May 2024,  @author :: Mangaleswari, @desc:: users add row funtion
    $('#user_add_row').click(function (event) {

        var user_position = parseInt($('#user_position').val())+1;
        //var count = $('#user_position').val();

        $('#add_users').append('<div class="col-md-6 user_row_'+user_position+'" style="margin-top: 20px;"><select class="form-select users" aria-label="select example" id="users_'+user_position+'" name="users"><option value="">Select Users</option></select></div><div class="col-md-6 user_row_'+user_position+'" style="margin-top: 20px;text-align: center;"><a id="user_delete_row" class="btn btn-danger" data-id="'+position+'" style="font-size: 12px;"><i class="bx bx-minus-circle me-1"></i> </a></div>');
        $('#users_'+user_position).select2();
        $('#user_position').val(user_position);
     });
});
    //@date:: 21 May 2024,  @author :: Mangaleswari, @desc:: Representative delete row funtion
    $('.representative_delete_row').on('click', function(){
        
        $('.representative_row_'+$(this).attr('data-id')).remove();
        
    })
    // function representative_delete_row(val)
    // {
    //     $('.representative_row_'+val).remove();
    // }
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
    