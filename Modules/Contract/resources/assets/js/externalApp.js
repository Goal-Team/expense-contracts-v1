// Create Signature End

$(document).ready(async function () {
    //Check GPS Enabled Or Not
    checkGPS();
    
    if (typeof Tagify !== 'undefined') {
    const tagifyBasicEl = document.querySelector('#contractTags');
    const TagifyBasic = new Tagify(tagifyBasicEl);
    }

    var url = window.location.href;
    var last = url.lastIndexOf('/') + 1;
    var base_url = url.substring(0, last);
    
    $('#ownership').select2({placeholder: "Select Owner/Initiator", allowClear: true });
    $('#ownership-signatory').select2({placeholder: "Select Signatory", allowClear: true });
    
    $('#accordionOwnership').on('shown.bs.collapse', function () {
        
        var formData = new FormData();
        let formLoc = [];
        $('.userBranch').each(function(elm){
            console.log($(this).val());
            if($(this).val()){
                formLoc.push($(this).val());
            }
        })
        formData.append('location', JSON.stringify(formLoc));
        formData.append('DepartmentType', $('[name="BasicContract[DepartmentType]"]').val());
        formData.append('catgoeryType', $('[name="BasicContract[catgoeryType]"]').val());
        formData.append('contractType', $('[name="BasicContract[contractType]"]').val());
        formData.append('value', $('[name="ContractValue[value]"]').val());
        
        $.ajax({
            url: APP_URL + '/getSignatory', // Update with your route
            type: 'POST',
            data: formData,
            processData: false, // Important for file uploads
            contentType: false, 
            // contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },            
            success: function (response) {
                let signatoryResp = (response.message).split(':');
                
                if(signatoryResp.length > 0){
                    $('#ownership-signatory').val(parseInt(signatoryResp[0])).trigger('change');
                }
            },
            error: function (xhr, status, error) {
                // Handle error
                alert('Form submission failed: ' + error);
            }
        });
    })    
    
    //Approval Signature Actions Tab
    $(document).on('click', '.step3', function() { 
        $('#execute-save-tab').click().removeClass('completed');
        $('#verify-signature-tab').addClass('completed');
        $('#signature-tab').addClass('completed');
    });

    $(document).on('click', '.step2', function() { 
        $('#verify-signature-tab').click().removeClass('completed');
        $('#signature-tab').addClass('completed');
        $('#execute-save-tab').removeClass('completed');
        checkSignature();
    });

    $(document).on('click', '.step1', function() { 
        $('#signature-tab').click().removeClass('completed');
        $('#verify-signature-tab').removeClass('completed');
        $('#execute-save-tab').removeClass('completed');
    });
    
    $('#approvalSignatureForm').submit(function (e) {
        e.preventDefault(); // Prevent the default form submission
        
        let signPresent = $('#currentUserId');
        let signOtpPresent = $('#nextOtp');
        let signOtpPresentApp = $('.OtpSection').length;
        let signOtpAction = $('#otpActionType').val();
        $('#load').css('visibility',"visible");
        
        if(signPresent && signOtpAction != 'verify' && signOtpAction != 'verified' && signOtpPresentApp > 0){
            $('#btn_save_updates').addClass('loading').attr('disabled', true);
            $('#load').css('visibility',"visible"); 
            $('#nextOtp').val("");
            
            var formData = new FormData(this);
            sendOtp(formData);           
        }
        else if(signPresent && signOtpAction == 'verify' && signOtpPresentApp > 0){
            
            if(signOtpPresent.val() == ""){
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing',
                    text: 'Please Enter OTP',
                    customClass: {
                        confirmButton: 'btn btn-danger waves-effect waves-light'
                    }
                });                
            }else{
                var formData = new FormData(this);
                $.ajax({
                    url: APP_URL + '/contracts/external/checkOtpApprovals',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },                    
                    success: function (response) {
                        $('#load').css('visibility',"hidden");                
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                customClass: {
                                    confirmButton: 'btn btn-success waves-effect waves-light'
                                }
                            });
                            $('.OtpSection').addClass('d-none');
                            $('#sendOtp').text('Proceed Signing');
                            $('#otpActionType').val('verified');
                            $('#signatureActionDiv').show();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                customClass: {
                                    confirmButton: 'btn btn-danger waves-effect waves-light'
                                }
                            });
                            $('#load').css('visibility',"hidden");
                        }
                    },
                    error: function (xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error,
                            customClass: {
                                confirmButton: 'btn btn-danger waves-effect waves-light'
                            }
                        });
                        // Handle error
                        $('#btn_save_updates').removeClass('loading').attr('disabled', false);
                        $('#load').css('visibility',"hidden");
                    }
                }); 
            }
        }
        else if(signPresent && signOtpAction == 'verified' && signOtpPresentApp > 0){
                var formData = new FormData(this);
                $.ajax({
                    url: APP_URL + '/contracts/external/setUpSign',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },                    
                    success: function (response) {
                        $('#btn_save_updates').removeClass('loading').attr('disabled', false);
                        $('#load').css('visibility',"hidden");                
                        if (response.success) {
                            $('.step3').click();
                            if(response.html){
                                $('#documentHtmlViewer').html(response.html);
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                customClass: {
                                    confirmButton: 'btn btn-danger waves-effect waves-light'
                                }
                            });
                            $('#load').css('visibility',"hidden");
                        }
                    },
                    error: function (xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error,
                            customClass: {
                                confirmButton: 'btn btn-danger waves-effect waves-light'
                            }
                        });
                        // Handle error
                        $('#btn_save_updates').removeClass('loading').attr('disabled', false);
                        $('#load').css('visibility',"hidden");
                    }
                });             
        }

    });
    
     $('#executeContractForm').submit(async function (e) {
        e.preventDefault(); // Prevent the default form submission

        let formData = new FormData(this);
        let signPng = $('#currentSign').val();
        formData.append('actionBtntext', 'To Sign');
        formData.append('skipDocument', 'true');
        formData.append('signPng', signPng);
        const geoPresent = await getLocation();
    
        if (!geoPresent.success) {
            $('#load').css('visibility',"hidden");
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'GPS Not Enabled Please Enable It And Try Refresh',
                customClass: {
                    confirmButton: 'btn btn-danger waves-effect waves-light'
                },
                didClose: () => {
                    $('button').attr('disabled', true);
                }
            });
        }else{   
            formData.append('signPngLoc', geoPresent.message);
            contractApprovals(formData);
        }
    });

     $('#executeFormSignedDoc').submit(function (e) {
        e.preventDefault(); // Prevent the default form submission

        let formData = new FormData(this);
        formData.append('actionBtntext', 'To Sign');
        formData.append('skipDocument', 'false');
        contractApprovals(formData);
    });
    
    $('#btn_resend_otp').click(function (e) {
        $('#otpActionType').val('resend');
        $('#approvalSignatureForm').submit();
    });
    $('#btn_verify_otp').click(function (e) {
        $('#otpActionType').val('verify');
        $('#approvalSignatureForm').submit();
    });  
    
    //Change Signature Pad Option
    $(document).on('click', '.signPadOption', function(){
        $('.signOptions').addClass('d-none');
        if($(this).is(":checked")){
            let divId = $(this).data('divid');
            $(`#${divId}`).removeClass('d-none');
        }
    });

    
    $('#show-pdf-close').click( function() { 
        let reDirectUrl = $(this).attr('data-href');
        window.location.href = reDirectUrl;
    });   

  $('.attachmentstype').click( function() {
        let showDiv = $(this).attr('data-div');
        if(showDiv == 'template'){
            if($('#contracttype').val() == ""){
                Swal.fire({
                    icon: 'warning',
                    title: 'Warning',
                    text: 'Please Choose Contract Type',
                    customClass: {
                        confirmButton: 'btn btn-danger waves-effect waves-light'
                    }                    
                });
                $('[data-div="upload"]').prop('checked', true);
                $(this).prop('checked', false);
            }else{
                $('.attachmentsdiv').hide();
                $(`#attachments_type_${showDiv}`).show();                
                getTemplateForContract();
            }            
        }else{
            $('.attachmentsdiv').hide();
            $(`#attachments_type_${showDiv}`).show();            
        }
  });
    
});
    function checkSignature(){
        let checkedOptionSign = $('#signatureOptionTabs').find('button.active').attr('id');

        if(checkedOptionSign == 'signature-draw-tab'){
            var getSignImage = document.getElementById("signatureCanvas");        
              if (getSignImage.getContext) {
                 var ctx = getSignImage.getContext("2d");                
                 var finalSign = getSignImage.toDataURL("image/png");      
            }                                
            $('#currentSign').val(finalSign);
            $('#previewSignImg').attr('src', finalSign);
        }        
    }
    
    function sendOtp(formData_){
        $.ajax({
            url: APP_URL + '/contracts/external/sentOtpApprovals',
            type: 'POST',
            data: formData_,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },            
            success: function (response) {
                $('#btn_save_updates').removeClass('loading').attr('disabled', false);
                $('#load').css('visibility',"hidden");                
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        customClass: {
                            confirmButton: 'btn btn-success waves-effect waves-light'
                        }
                    });
                    $('.OtpSection').removeClass('d-none');
                    $('#signatureActionDiv').hide();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        customClass: {
                            confirmButton: 'btn btn-danger waves-effect waves-light'
                        }
                    });
                    // location.reload();
                    $('#btn_save_updates').removeClass('loading').attr('disabled', false);
                    $('#load').css('visibility',"hidden");
                }
            },
            error: function (xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error,
                    customClass: {
                        confirmButton: 'btn btn-danger waves-effect waves-light'
                    }
                });
                // Handle error
                $('#btn_save_updates').removeClass('loading').attr('disabled', false);
                $('#load').css('visibility',"hidden");
            }
        });         
    }

    
    
    //Function Contract Approvals
    async function contractApprovals(formData){
         $.ajax({
            url: APP_URL + '/contracts/external/updateApprovals', // Update with your route
            type: 'POST',
            data: formData,
            processData: false, // Important for file uploads
            contentType: false, // Important for file uploads
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        customClass: {
                            confirmButton: 'btn btn-success waves-effect waves-light'
                        }
                    });
                    //location.reload();
                    $('#load').css('visibility',"hidden");
                    $('#execute-save').remove();
                    setTimeout(function(){
                        location.reload();
                    }, 5000); 
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        customClass: {
                            confirmButton: 'btn btn-danger waves-effect waves-light'
                        }
                    });
                    // location.reload();
                    $('#btn_save_updates').removeClass('loading').attr('disabled', false);
                    $('#load').css('visibility',"hidden");
                }
            },
            error: function (xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error,
                    customClass: {
                        confirmButton: 'btn btn-danger waves-effect waves-light'
                    }
                });
                // Handle error
                $('#btn_save_updates').removeClass('loading').attr('disabled', false);
                $('#load').css('visibility',"hidden");
            }
        });
    }
    
    //CheckGps
    async function checkGPS() {
        const geoPresent = await getLocation();
    
        if (!geoPresent.success) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'GPS Not Enabled Please Enable It And Try Refresh',
                customClass: {
                    confirmButton: 'btn btn-danger waves-effect waves-light'
                },
                didClose: () => {
                    $('button').attr('disabled', true);
                }
            });
        }
    }

    function getLocation() {
        return new Promise((resolve, reject) => {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        resolve(locationJson(
                            position.coords.latitude +
                            "," + position.coords.longitude,
                            true
                        ));
                    },
                    (error) => {
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                resolve(locationJson("Location permission denied."));
                                break;
                            case error.POSITION_UNAVAILABLE:
                                resolve(locationJson("Location information is unavailable."));
                                break;
                            case error.TIMEOUT:
                                resolve(locationJson("Location request timed out."));
                                break;
                            default:
                                resolve(locationJson("An unknown error occurred."));
                                break;
                        }
                    }
                );
            } else {
                resolve(locationJson("Geolocation is not supported by this browser."));
            }
        });
    }
    
    function locationJson(locMessage, success = false) {
        return {
            message: locMessage,
            success: success
        };
    }



