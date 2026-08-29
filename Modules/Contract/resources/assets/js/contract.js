var quill;

$('input[type="file"]').change(function (e) {
    var fileName = e.target.files[0]?.name;

    //console.log(fileName);
    $('.custom-file-label').html(fileName);
});

function download(canvas, filename) {
  var lnk = document.createElement('a'), e;
  lnk.download = filename;
  lnk.href = canvas.toDataURL("image/png;base64");
  
  if (document.createEvent) {
    e = document.createEvent("MouseEvents");
    e.initMouseEvent("click", true, true, window,
                     0, 0, 0, 0, 0, false, false, false,
                     false, 0, null);

    lnk.dispatchEvent(e);
  } else if (lnk.fireEvent) {
    lnk.fireEvent("onclick");
  }
}

// Create Signature End

$(document).ready(function () {
    if (typeof Tagify !== 'undefined') {
    const tagifyBasicEl = document.querySelector('#contractTags');
    const TagifyBasic = new Tagify(tagifyBasicEl);
    }
    
    checkFinancialLimit();
    
    $('#ownership').select2({placeholder: "Select Owner/Initiator", allowClear: true });
    $('#ownership-signatory').select2({placeholder: "Select Signatory", allowClear: true });
    $('#users-notify').select2({placeholder: "Select More Users", allowClear: true });
    
    $('#accordionOwnership').on('shown.bs.collapse', function () {
        checkFinancialLimit();
    });   


    if($('#executeContractForm').length){
        //checkGPS();
    }
    
    setTimeout(function () {
        $('.emptyattachemnt').each(function (index) {
            var currentElement = $(this);
            currentElement.closest('tr').addClass('error-row');
        });
    }, 500);

    $('.rus').on('click', function () {
        $(this).closest('li').remove();
    });

    $('.addgroup').on('click', function () {
        $.ajax({
            url: APP_URL + '/contracts/eventgroup?count=' + $('.taskgroup').length, // Update with your route
            type: 'GET',
            success: function (response) {
                $('#taskgroup').append(response);
            },
            error: function (xhr, status, error) {
                // Handle error
                // alert('Form submission failed: ' + error);
            }
        });
    });
    
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

    $(document).on('change', '.partygroup[value="Internal"]', function() {
        const nameCurrent = $(this).attr('name');
        if($(this).is(":checked")){
            $('.partygroup[value="Internal"]').each(function(){
                let nameLoop = $(this).prop('name');
                if(nameCurrent != nameLoop){
                    if($(this).is(":checked")){
                        $(this).prop('checked', false);
                        $(`[name='${nameLoop}'][value="Intergroup"]`).prop('checked', true).trigger('change');
                    }
                }
            });
        }
    });
    
    //Toggle Error Rows
    $(document).on('click', '.toggle-error-rows', async function() { 
        const toggleClasses = $(this).data('toggle-rows');
        if($(this).hasClass('btn-danger')){
            $(this).toggleClass('btn-danger btn-label-danger');
            $("tr.preview-row").each(function(){
                $(this).find(`.${toggleClasses}`).removeClass('toggled-cols');
            });
            if($('.toggled-cols').length > 0){
                $("tr.preview-row").filter(function(){
                    return $(this).find(`.toggled-cols`).length == 0;
                }).hide();
            }else{
              $("tr.preview-row").show();  
            }
        }else{
            $("tr.preview-row").filter(function(){
                return $(this).find(`.toggled-cols`).length == 0;
            }).hide();

            $("tr.preview-row").filter(function(){
                $(this).find(`.${toggleClasses}`).addClass('toggled-cols');
                return $(this).find(`.${toggleClasses}`).length != 0;
            }).show();

            $(this).toggleClass('btn-label-danger btn-danger');
        }
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
                    url: APP_URL + '/contracts/checkOtpApprovals',
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
                    url: APP_URL + '/setUpSigning',
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
        var noChangesDoc = $("#noChangesUpdate").is(":checked");
        formData.append('skipDocument', noChangesDoc);
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
    
    
    //Reload Template Content
    getTemplateForContract(true);   
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
            url: APP_URL + '/contracts/sentOtpApprovals',
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

    //Change Signature Pad Option
    $(document).on('click', '.signPadOption', function(){
        $('.signOptions').addClass('d-none');
        if($(this).is(":checked")){
            let divId = $(this).data('divid');
            $(`#${divId}`).removeClass('d-none');
        }
    });
    
    //On Change of Billing Values
    $(document).on('change keyup keydown keypress', '.calculateBilling', function(){
        let contractVal = $('#ContractBillingValue').val();
        let contractBillFreq = $('#BillingFrequency').val();
        let contractEffeType = $('[name="Duration[effectiveDate]"]:checked').val();
        let contractEffeDate = $('[name="Duration[fixedDate]"]').val();
        let contractEffeFixedDt = $('[name="Duration[fixedtimeEndDateofContract]"]').val();
        let contractEffeOneTmDt = $('[name="Duration[onetimeEndDateofContract]"]').val();
        
        let billingFreqArr = {
            'Weekly':52,
            'Monthly':12,
            'Quarterly':4,
            'Half Yearly':2,
            'Annually':1,
            'Onetime':1
            };

        // Average length of one billing period in days, used to prorate contracts that run
        // for less than a year. Averages (30.44 / 91.31 / 182.63) keep month-length
        // differences from shifting the period count.
        let billingPeriodDays = {
            'Weekly':7,
            'Monthly':30.44,
            'Quarterly':91.31,
            'Half Yearly':182.63,
            'Annually':365.25
            };


        let conEffeTypeDate = {
            'onetimeContract':contractEffeOneTmDt,
            'fixedTerm':contractEffeFixedDt
        };
        let contEndType = conEffeTypeDate[contractEffeType] ?? '';
        
        $('.annualValueDiv').addClass('d-none');
        $('.totalValueDiv').addClass('d-none');
        $('#totalContractValue').val(0);
        $('#totContValText').text(0);
        if(contractVal !== "" && contractBillFreq !== ""){
            let billingVal = parseFloat(contractVal) || 0;

            // Tenure of the contract, when both dates are known.
            let tenureYears = 0;
            let tenureDays = 0;
            if(contEndType !== "" && contractEffeDate !== ""){
                let dateVals = getYearDiff(contEndType, contractEffeDate, false);
                tenureYears = dateVals['years'];
                tenureDays = dateVals['days'];
            }

            var anualVal = (billingVal * (billingFreqArr[contractBillFreq]));

            // A contract shorter than a year is only billed for the periods that fall inside
            // its tenure, so a full year's worth of billing overstates it - a weekly 10,000
            // contract running 01-Jul to 31-Jul reported 520,000 and tripped the wrong DOA
            // approval level. Contracts of a year or more keep the per-year figure, which the
            // total then multiplies out over the tenure.
            if(contractBillFreq != 'Onetime' && tenureDays > 0 && tenureYears < 1){
                let periodDays = billingPeriodDays[contractBillFreq];
                if(periodDays){
                    // Rounded up, because a part period is still billed once, but with a 5%
                    // tolerance so a tenure that sits a hair over an exact multiple is not
                    // charged an extra period: 01-Jul to 31-Dec is 183 days, which is 6.01
                    // average months and must count as 6, not 7.
                    let periods = Math.max(1, Math.ceil((tenureDays / periodDays) - 0.05));
                    anualVal = (billingVal * periods);
                }
            }

            $('#ContractValueAnnual').val(anualVal.toFixed(2));
            $('#ContractValAnnText').text(anualVal.toFixed(2));
            $('.annualValueDiv').removeClass('d-none');

            if(contEndType !== "" && contractEffeDate !== ""){
                if(tenureYears > 0){
                    let yearVal = tenureYears.toFixed(2);
                    let totVal = anualVal.toFixed(2);
                    if(contractBillFreq != 'Onetime' && yearVal > 1){
                        totVal = (yearVal * anualVal).toFixed(2);
                    }
                    if(totVal > 0){
                        if(contractEffeType != 'evergreen'){
                            $('#totalContractValue').val(totVal);
                            $('#totContValText').text(totVal);
                            $('.totalValueDiv').removeClass('d-none');
                        }
                    }
                }
            }
        }
        
    });
    
    
    $('#approvalAddUpdatesForm').submit(function (e) {
        e.preventDefault(); // Prevent the default form submission
        

            var btntext = $('#btn_save_updates_approve').text();
            $('#btn_save_updates').addClass('loading').attr('disabled', true);
            
            var formData = new FormData(this);
            formData.append('actionBtntext', btntext);
            
            var noChangesDoc = $("#noChangesUpdate").is(":checked");
            formData.append('skipDocument', noChangesDoc);        
            contractApprovals(formData);

    });

    $(document).on('click', '#myCurrentReview', function(){
        $('#btn_save_updates_approve').trigger('click');
    });
    
    
    $('#btn_save_updates_approve').click(function () {
        
        var btntext = $('#btn_save_updates').text();
        $('#btn_save_updates').text('Send');
        var btntextApp = $('#btn_save_updates_approve').text();

        if (btntextApp == 'Send to Approval' || btntextApp == 'Send to next Approval') {
            $('#btn_save_updates').text('Approve');
        }
        if (btntext == 'Send to Owner for revision') {
            $('#btn_save_updates').text('Send');
        }
        if (btntextApp == 'Send to Signing') {
            $('#btn_save_updates').text('Send to Signatory');
        }
        if(btntextApp == 'To Sign' || btntextApp == 'Send to next Approval'){
            $('.misgtable').show();
        }
        $('.updatesHeading').text(btntextApp);
        
        var index = $('#indexId').val();
        $('#appType' + index).val('approved');
        $("#updatesDiv" + index).css('display', '');
        hideOtherTimelineElements(btntextApp);
    });
    
    $('.btn_save_updates_approve_pl').click(function () {
        
        var index = $(this).data('up-div');
        var btntext = $(`#btn_save_updates_${index}`).text();
        $(`#btn_save_updates_${index}`).text('Send');
        var btntextApp = $(this).text();

        if (btntextApp == 'Send to Approval' || btntextApp == 'Send to next Approval') {
            $(`#btn_save_updates_${index}`).text('Approve');
        }
        if (btntext == 'Send to Owner for revision') {
            $(`#btn_save_updates_${index}`).text('Send');
        }
        if (btntextApp == 'Send to Signing') {
            $(`#btn_save_updates_${index}`).text('Send to Signatory');
        }
        if(btntextApp == 'To Sign' || btntextApp == 'Send to next Approval'){
            $('.misgtable').show();
        }
        $('.updatesHeading').text(btntextApp);
        
        $('#appType' + index).val('approved');
        $("#updatesDiv" + index).css('display', '');
        hideOtherTimelineElements(btntextApp);
    }); 
    
    $('.btn_save_updates_pl').click(function () {
        var index = $(this).data('sub-form');
        var btntext = $(`#approvalAddUpdatesForm_${index}`).submit();
    });
    
    $('.approvalAddUpdatesForm_pl').submit(function (e) {
        e.preventDefault(); // Prevent the default form submission
        var index = $(this).data('sub-form');

        var btntext = $(`#btn_save_updates_approve_${index}`).text();
        $(`#btn_save_updates_${index}`).addClass('loading').attr('disabled', true);
        
        var formData = new FormData(this);
        formData.append('actionBtntext', btntext);
        
        var noChangesDoc = $("#noChangesUpdate").is(":checked");
        formData.append('skipDocument', noChangesDoc);        
        contractApprovals(formData);

    });    

    $('#btn_save_updates_reject').click(function () {
        
        
        var btntext = $('#btn_save_updates').text();
        var btntextRej = $('#btn_save_updates_reject').text();
        if (btntext == 'Approve') {
            $('#btn_save_updates').text('Reject');
        }
        if (btntextRej == 'Send to Owner') {
            $('#btn_save_updates').text('Send to Owner for revision');
        }
        $('.updatesHeading').text(btntextRej);
        
        $('.mandateField').each(function(index) {
            $(this).prop('required', false);
        });        

        var index = $('#indexId').val();

        $('#appType' + index).val('rejected');
        $("#updatesDiv" + index).css('display', '');
        hideOtherTimelineElements(btntextRej);
    });

    $('.fa-eye.editView').click(function () {
        var index = $(this).attr('value');
        // $('#appType').val('approved');
        $("#edit_indexId").val(index);
        $("#EditDiv" + index).css('display', '');
        $(".fa-eye.editViewClose").css('display', 'block');
        $(".fa-eye.editView").css('display', 'none');
    });

    $('.fa-eye.editViewClose').click(function () {
        var index = $(this).attr('value');
        // $('#appType').val('approved');
        $("#edit_indexId").val(index);
        $("#EditDiv" + index).css('display', 'none');
        $(".fa-eye.editView").css('display', 'block');
        $(".fa-eye.editViewClose").css('display', 'none');
    });

    $('#btn_cancel_updates').click(function () {

        var index = $('#indexId').val();
        $("#updatesDiv" + index).css('display', 'none');

    });

    $('.btn_cancel_edit_updates').on('click', function () {
        // var index = $('.editView').attr('value'); 

        var index = $('#edit_indexId').val();
        $("#EditDiv" + index).css('display', 'none');
    });

    function initializeSortable() {

        if ($.fn.sortable) {

            $("#sortable").sortable({
                placeholder: "accordion-placeholder",
                connectWith: "#sortable",
                revert: true,
                handle: ".cursor",
                helper: "clone",
                start: function (e, ui) {
                    ui.placeholder.html('<div class="col-sm-6 high ">' + ui.item.html() + '</div>');
                },
                update: function (event, ui) {
                    // Update index and group values after sorting
                    $(this).find('input.index').each(function (index) {
                        $(this).val(index + 1);
                    });
                    $(this).find('input.group').val($(this).closest('.panel').find('.panel-title').data('id'));
                },
            }).disableSelection();
        }


        $('.rus').on('click', function () {
            $(this).closest('li').remove();
        });
    }

    initializeSortable();
    
    
    //Function Contract Approvals
    
    function contractApprovals(formData){
        $.ajax({
            url: APP_URL + '/contracts/updateApprovals', // Update with your route
            type: 'POST',
            data: formData,
            processData: false, // Important for file uploads
            contentType: false, // Important for file uploads
            success: function (response) {
                if (response.message == 'successful!') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Successful',
                        customClass: {
                            confirmButton: 'btn btn-success waves-effect waves-light'
                        }
                    });
                    $('#load').css('visibility',"hidden");
                    location.reload();
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

    $('.cloneuses').click(function () {
        var newItem = $('.idsect li').clone();
        console.log(newItem);
        var newIndex = $('#sortable li').length + 1;
        newItem.find('label').text('Select User ' + newIndex);
        $('#sortable').append(newItem);

        $('#sortable li:last-child select').wrap('<div class="position-relative"></div>');
        $('#sortable li:last-child select').select2({
            dropdownParent: $('#sortable li:last-child select').parent()
        });
        initializeSortable();
    });

    $('#approversForm').submit(function (e) {
        e.preventDefault(); // Prevent the default form submission

        // Gather form data
        var formData = $(this).serialize();



        $.ajax({
            url: APP_URL + '/contracts/updateflow', // Update with your route
            type: 'POST',
            data: formData,
            success: function (response) {
                console.log(response);

                location.reload();
            },
            error: function (xhr, status, error) {
                // Handle error
                alert('Form submission failed: ' + error);
            }
        });
    });

  // Full Toolbar
  // --------------------------------------------------------------------
  const fullToolbar = [
    [
      {
        font: []
      },
      {
        size: []
      }
    ],
    ['bold', 'italic', 'underline', 'strike'],
    [
      {
        color: []
      },
      {
        background: []
      }
    ],
    [
      {
        script: 'super'
      },
      {
        script: 'sub'
      }
    ],
    [
      {
        header: '1'
      },
      {
        header: '2'
      },
      'blockquote',
      'code-block'
    ],
    [
      {
        list: 'ordered'
      },
      {
        list: 'bullet'
      },
      {
        indent: '-1'
      },
      {
        indent: '+1'
      }
    ],
    [{ direction: 'rtl' }],
    ['link', 'image', 'video', 'formula'],
    ['clean']
  ];
  if($('#template-editor').length){
      quill = new Quill('#template-editor', {
        bounds: '#template-editor',
        placeholder: 'Type Something...',
        modules: {
          formula: true,
          toolbar: fullToolbar
        },
        theme: 'snow'
    });    
    
    $('#btn-html-shower').on('click', () => {
        // Get HTML content
        var html = quill.root.innerHTML;
        console.log(html);
    
    }); 
    $('#btn-html-undo').on('click', () => {
        quill.history.undo();
        console.log('undo');
    
    }); 
    $('#btn-html-redo').on('click', () => { 
        quill.history.redo();
    }); 
    $('#btn-doc-downloader').on('click', () => {
        // Get HTML content
        var html = quill.root.innerHTML;
        var converted = htmlDocx.asBlob(('<!DOCTYPE html>' + html));
        saveAs(converted, 'test.docx');
        
    });
    
}


    $('#show-pdf-close').click( function() { 
        let reDirectUrl = $(this).attr('data-href');
        window.location.href = reDirectUrl;
    });   

  $('.attachmentstype').click( function() {
        let showDiv = $(this).attr('data-div');
        if(showDiv == 'template'){
            if($('input[name="contractMode"]:checked').val() == "old"){
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: 'For Legacy Contracts Templates Not Allowed',
                        customClass: {
                            confirmButton: 'btn btn-danger waves-effect waves-light'
                        }                    
                    });
                    $('[data-div="upload"]').prop('checked', true);
                    $(this).prop('checked', false);                
            }
            else{
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
                }
                else{
                    $('.attachmentsdiv').hide();
                    $(`#attachments_type_${showDiv}`).show(); 
                    $('input[name="file"]').val('');
                    getTemplateForContract();
                }
            }
        }else{
            $('.attachmentsdiv').hide();
            $(`#attachments_type_${showDiv}`).show(); 
            // Reset template preview when switching to upload
            $('#template-preview-frame').hide().attr('src', '');
            $('#template-preview-info').addClass('d-none');
            $('#template-preview-empty').addClass('d-none');
            $('#agreement_template_id').val('');
        }
  });
  
  $(document).on('click', '#showSignPad', function(){
      $('#attachments_type_signing_pad').show();
      checkGPS();
  });
  
$('#addObligationForm').submit(function (e) {
    e.preventDefault();

    var contract_id = $("#contract_id_obg").attr("value");

    var owner = $(".owner").attr("value");
    var signatory = $(".signatory").attr("value");
    
    var branchName = $(".branchName").attr("value");
    var signatory = $(".signatory").attr("value");
    
    var sliderName = $(".sliderName").text();
    var task_id = $("#task_id").attr("value");


    

    var formData = new FormData(this);
    formData.append('contract_id', contract_id);
    formData.append('owner', owner);
    formData.append('signatory', signatory);
    formData.append('branchName', branchName);
    formData.append('sliderName', sliderName);
    formData.append('task_id', task_id);

    $.ajax({
        url: APP_URL + '/contracts/addObligation', // Update with your route
        type: 'POST',
        data: formData,
        processData: false, // Important for file uploads
        contentType: false, 
        // contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if(response.message == 'successful!'){
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Successful',
                    customClass: {
                    confirmButton: 'btn btn-success waves-effect waves-light'
                    }
                });
                location.reload();
            }else{
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something Went Wrong',
                    customClass: {
                    confirmButton: 'btn btn-danger waves-effect waves-light'
                    }
                });
                location.reload();
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
        }
    });
    
});


$(document).on('click', '.editObligationBtn', function(e) {
    e.preventDefault();
    
    var row = $(this).closest('tr'); // Get the closest table row
    //get value from td
    var trRow = row.data('id'); 
  
    var task_id = trRow.id;
    var task_name = trRow.obligation_name;
    var task_priority = trRow.priority;
    var task_description = trRow.description;
    var task_end_date = trRow.due_date;
    var onetime_end_date = trRow.onetime_end_date;
    var task_status = trRow.status;
    var task_type = trRow.task_type;
    var repeats = trRow.repeats;
    var end_frequency = trRow.end_frequency;
    var frequency = trRow.frequency;
    var recuring_due_date = trRow.recuring_due_date;

     // Set the data in the slider

     $('#taskName').val(task_name);
     $('#task_status').val(task_status).change();
     $('#task_priority').val(task_priority).change();
     $('#obDueDate').val(task_end_date);
     $('#task_description').val(task_description);
     $('#OnetimeDate').val(onetime_end_date);
     $('#repeats').val(repeats);
     $('#recuringEndDate').val(recuring_due_date);
     $('#days').val(frequency).change();
     $('#task_ends_on').val(end_frequency).change();
     $('#task_type').val(task_type).change();
     $('#task_id').attr( 'value',task_id);

     var flatpickrInstanceD = flatpickr("#obDueDate");
      flatpickrInstanceD.setDate(task_end_date);


     var flatpickrInstanceOD = flatpickr("#OnetimeDate");
      flatpickrInstanceOD.setDate(onetime_end_date);

     var flatpickrInstance = flatpickr("#recuringEndDate");
      flatpickrInstance.setDate(recuring_due_date);

     $('.sliderName').text("update");
     $('#popUpTitle').text("Update");
     $('#popUpAction').text("Update");
    var offcanvasElement = document.getElementById('addPaymentOffcanvas');
    var offcanvas = new bootstrap.Offcanvas(offcanvasElement);
    offcanvas.show(); 
    // $('#addPaymentOffcanvas').modal('show');
});


$(document).on('click', '.delObligationBtn', function(e) {
    e.preventDefault();
    
    var row = $(this).closest('tr'); // Get the closest table row
    //get value from td
    var trRow = row.data('id'); 
  
    var task_id = trRow.id;

    Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        customClass: {
          confirmButton: 'btn btn-primary me-2 waves-effect waves-light',
          cancelButton: 'btn btn-label-secondary waves-effect waves-light'
        },
        buttonsStyling: false
      })
    .then(function (result) {
       if(result.isConfirmed){
            $.ajax({
                url: APP_URL + '/contracts/deleteObligation',
                type: 'POST',
                data: { "task_id": task_id},
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if(response.message == 'successful!'){
                        Swal.fire({
                            icon: 'success',
                            title: 'Obligation Updates',
                            text: response.message,
                            customClass: {
                                confirmButton: 'btn btn-success waves-effect waves-light'
                            }
                        }).then(function (result) {
                            if (result.value){
                                location.reload();
                            }
                        });
                    }else{
                        Swal.fire({
                            icon: 'error',
                            title: 'Sorry!',
                            text: response.message,
                            customClass: {
                                confirmButton: 'btn btn-dager waves-effect waves-light'
                            }
                        });                        
                    }
        
                    //
        
                },
                error: function (error) {
                    console.log('Error submitting form:', error);
                }
            });
        }

    });

});


//--------End -----------//  
});

var myOffcanvas = document.getElementById('addPaymentOffcanvas');
if(myOffcanvas){
    myOffcanvas.addEventListener('hidden.bs.offcanvas', function () {
         $('#popUpTitle').text("Add");
         $('#popUpAction').text("Add");
    });
}

$(document).on('change', '#showAllData', async function (e){
    if($(this).is(':checked')){
        $('.preview-row').not('.error-row').hide();
    }else{
        $('.preview-row').show();
    }
});
$(document).on('change', '#attach-save input[type="file"]', async function (e){
    
    const files = e.target.files;
    const fileNames = [];

    for (let i = 0; i < files.length; i++) {
        fileNames.push(files[i].name);
    }
    
    let FilesMissed = 0;
    $('.attachmentsLoaded').each(function(elm){
        $(this).removeClass('emptyattachemnt');
        if ($.inArray($(this).find('i').data('bs-original-title'), fileNames) === -1) {
           FilesMissed++;
        }
    });
    if(FilesMissed > 0){
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Some Files Missed/Name Mismatched Click Ok to check preview or Click Upload Again',
            showCancelButton: true,
            cancelButtonText: 'Upload Again',
            customClass: {
                confirmButton: 'btn btn-danger waves-effect waves-light',
                cancelButton: 'btn btn-success waves-effect waves-light'
            }
        }).then(function (result) {
            if(result.value){
                $('.attachmentsLoaded').each(function(elm){
                    $(this).removeClass('emptyattachemnt');
                    let fileName = $(this).find('i').data('bs-original-title');
                    if ($.inArray(fileName, fileNames) === -1) {
                       $(this).append(`<i class="ti ti-exclamation-circle ti-xs text-danger error-files-up" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-custom-class="tooltip-danger" data-bs-original-title="File (${fileName}) Missed/Name Mismatched With Excel and Attachment"><i/>`);
                       $(this).find('i').toggleClass('text-success','text-secondary');
                       let firstChild = $(this).parent('.preview-row').addClass('error-row').find('td:first-child');
                       firstChild.find('i').hide();
                       firstChild.append(`<i class="ti ti-exclamation-circle ti-xs text-danger error-files-up" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-custom-class="tooltip-danger" data-bs-original-title="File (${fileName}) Missed/Name Mismatched With Excel and Attachment"><i/>`);
                       $('.show-error-switch').removeClass('d-none');
                       $('.error-files-up').tooltip();
                    }
                });                
                $('#attach-save .step2').click();
                $('#submit-files, .step3').attr('disabled', true);
            }else{
               $('#attach-save form')[0].reset();
            }
        });
    }
    
});

// Fetch Template
function getTemplateForContract(getOldData=false) {
    if($('input[name="attachments_type"]:checked').val() === 'template'){
        // Show loading state
        $('#template-preview-loading').removeClass('d-none');
        $('#template-preview-frame').hide();
        $('#template-preview-empty').addClass('d-none');
        $('#template-preview-info').addClass('d-none');

        $.ajax({
            url: APP_URL + '/agreement-templates/resolve-for-contract',
            type: 'POST',
            data: {contracttype: $('#contracttype').val()},
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#template-preview-loading').addClass('d-none');

                if(response.found && response.has_docx){
                    // Set template info
                    $('#template-preview-name').text(response.template_name || 'Agreement Template');
                    $('#template-preview-info').removeClass('d-none');
                    $('#agreement_template_id').val(response.template_id);

                    // Set download button URL
                    $('#template-download-btn').attr('href', response.preview_download_url).show();

                    // Hide preview frame (using download only)
                    $('#template-preview-frame').hide();
                    $('#template-preview-empty').addClass('d-none');
                } else {
                    // No template found
                    $('#template-preview-frame').hide().attr('src', '');
                    $('#template-preview-info').addClass('d-none');
                    $('#template-download-btn').hide();
                    $('#agreement_template_id').val('');
                    $('#template-preview-empty').removeClass('d-none');
                }
            },
            error: function(xhr, status, error) {
                $('#template-preview-loading').addClass('d-none');
                $('#template-preview-frame').hide().attr('src', '');
                $('#template-download-btn').hide();
                $('#template-preview-empty').removeClass('d-none').text('Error loading agreement template.');
                console.error(xhr.responseText);
            }
        });
    }
}

$('#modalPopUpReview').click(function () {
    $("#modalCenter").show();
    // onboardImageModal
    // $("#onboardImageModal").modal("show");
    let processTitle_ = 'Send For Review Process';
    $('#modalCenterTitle').text(processTitle_);    
    $('.misgtable').hide();
    $('.misgtablenote').hide();
    $('#paperIcon').attr('disabled', false);
    // $('#paperIconNegaotition').attr("id","paperIcon");
    $('#approvalForm').hide();
    hideOtherTimelineElements(processTitle_);

});
$('#modalPopUpNegotiation').click(function () {
    $("#modalCenter").show();
    let processTitle_ = 'Send For Negotiation Process';
    $('#modalCenterTitle').text(processTitle_);

    $('.misgtable').hide();
    $('.misgtablenote').hide();
    $('#paperIcon').attr('disabled', false);
    // $('#paperIcon').attr("id","paperIconNegaotition");
    $('#approvalForm').hide();
    hideOtherTimelineElements(processTitle_);
});

$('#modalPopUpApproval').click(function () {
    $("#modalCenter").show();
    
    let processTitle_ = 'Send For Approval Process';
    $('#modalCenterTitle').text(processTitle_);

    $('.misgtable').hide();
    $('.misgtablenote').hide();
    $('#paperIcon').attr('disabled', false);

    $('.misgtable').show();
    if ($('.misgtable tbody tr').length > 0) {
        $('#paperIcon').attr('disabled', true);
        $('.misgtablenote').show();
    }
    $('#approvalForm').hide();
    $('#signing_date-id').hide().attr('disabled', true);
    $('#signing_date-section-id').hide();
    hideOtherTimelineElements(processTitle_);
});
$('#modalPopUpReviewBack').click(function () {

    $('.misgtable').hide();
    $('.misgtablenote').hide();
    $('#paperIcon').attr('disabled', false);
    
    $('.mandateField').each(function(index) {
        $(this).prop('required', false);
    });

    $("#modalCenter").show();
    let processTitle_ = 'Send Back to Review';
    $('#modalCenterTitle').text(processTitle_);
    $('#approvalForm').hide();
    hideOtherTimelineElements(processTitle_);
});
$('#modalPopUpSign').click(function () {
    $('.misgtable').hide();
    $('.misgtablenote').hide();

    $("#modalCenter").show();
    let processTitle_ = 'Send For Signing Process';
    $('#modalCenterTitle').text(processTitle_);

    $('.misgtable').show();
    if ($('.misgtable tbody tr').length > 0) {
        $('#paperIcon').attr('disabled', true);
        $('.misgtablenote').show();
    }
    $('#approvalForm').hide();
    $('#signing_date-id').show().attr('disabled', false);
    $('#signing_date-section-id').show();
    hideOtherTimelineElements(processTitle_);
});



$('#ApprovalProcessPopup').submit(function (e) {

    e.preventDefault();
    var contract_id = $("#contractId").attr("value");
    var curAppStatus = $("#curAppStatus").attr("value");

    var shortDescrip = $("#shortDescrip").val();
    var appRowId = $("#appRowId").val();
    var ReviewDescription = $("#ReviewDescription").val();
    var fileTypeDoc = $("#fileTypeDoc").val();
    var noChangesDoc = $("#noChanges").is(":checked");

    var approveVal = 'approved';

    var nextAppStatus = '';


    if (curAppStatus == 'Negotiation') {
        nextAppStatus = 'Approval';
    } else if (curAppStatus == 'Approved') {
        nextAppStatus = 'Signing';
    } else if (curAppStatus == 'Draft') {
        nextAppStatus = 'review';
    } else if (curAppStatus == 'review') {
        nextAppStatus = 'review';
    }


    var modalTitle = $('#modalCenterTitle').text();

    var negoStrFlag = modalTitle.indexOf("Negotiation");
    var appStrFlag = modalTitle.indexOf("Approval");
    var revBackStrFlag = modalTitle.indexOf("Back1");
    var signStrFlag = modalTitle.indexOf("Signing");
    var reviewStrFlag = modalTitle.indexOf("Back to Review");

    if (reviewStrFlag !== -1) {
        nextAppStatus = 'review';
    }

    var formData = new FormData(this);
    formData.append('id', contract_id);
    formData.append('nextAppStatus', nextAppStatus);
    formData.append('curAppStatus', curAppStatus);
    formData.append('userInputVal', approveVal);
    formData.append('ReviewDescription', ReviewDescription);
    formData.append('shortDescrip', shortDescrip);
    formData.append('appRowId', appRowId);
    
    var noChangesDoc = $("#noChanges").is(":checked");
    formData.append('skipDocument', noChangesDoc);


    if (negoStrFlag !== -1) {
        negotiation(formData, curAppStatus);
    } else if (revBackStrFlag !== -1) {
        rejectProcess(formData, curAppStatus)
    } else if (signStrFlag !== -1) {
        signProcess(formData, curAppStatus)
    } 
    else {
        $('#paperIconSub').addClass('loading').attr('disabled', true);
        $.ajax({
            url: APP_URL + '/contracts/sendContractForReview', // Update with your route
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
                        title: 'Action ' + nextAppStatus + ' Updates',
                        text: 'Successful',
                        customClass: {
                            confirmButton: 'btn btn-success waves-effect waves-light'
                        }
                    });
                    $('#load').css('visibility',"hidden");
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
                    $('#load').css('visibility',"hidden");
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
                $('#load').css('visibility',"hidden");
                // alert('Form submission failed: ' + error);
            }
        });
    }

});



function negotiation(formData, curAppStatus) {
    var nextAppStatus = 'Negotiation';
    var approveVal = 'approved';
    formData.delete("nextAppStatus");
    formData.delete("userInputVal");
    formData.append('nextAppStatus', nextAppStatus);
    formData.append('userInputVal', approveVal);


    $('#paperIconSub').addClass('loading').attr('disabled', true);
    $.ajax({
        url: APP_URL + '/contracts/sendContractForReview', // Update with your route
        type: 'POST',
        data: formData,
        processData: false, // Important for file uploads
        contentType: false,
        // contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.message == 'successful!') {
                Swal.fire({
                    icon: 'success',
                    title: 'Action ' + nextAppStatus + ' Updates',
                    text: 'Successful',
                    customClass: {
                        confirmButton: 'btn btn-success waves-effect waves-light'
                    }
                });
                $('#load').css('visibility',"hidden");
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
                $('#load').css('visibility',"hidden");
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
            $('#load').css('visibility',"hidden");
        }
    });
}

function rejectProcess(formData, curAppStatus) {

    var rejectVal = 'rejected';

    var nextAppStatus = '';

    if (curAppStatus == 'Negotiation') {
        nextAppStatus = 'review'
    } else if (curAppStatus == 'Approved') {
        nextAppStatus = 'Approval'
    } else if (curAppStatus == 'Draft') {
        nextAppStatus = 'review'
    }


    formData.delete("nextAppStatus");
    formData.delete("userInputVal");
    formData.append('nextAppStatus', nextAppStatus);
    formData.append('userInputVal', rejectVal);


    $('#paperIconSub').addClass('loading').attr('disabled', true);
    $.ajax({
        url: APP_URL + '/contracts/sendContractForReview',
        type: 'POST',
        // data: { "id": contract_id, "nextAppStatus": nextAppStatus, "curAppStatus": curAppStatus, "userInputVal": rejectVal,
        //     "ReviewDescription":ReviewDescription, "shortDescrip":shortDescrip,"appRowId":appRowId
        //  },
        data: formData,
        processData: false, // Important for file uploads
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.message == 'successful!') {
                Swal.fire({
                    icon: 'success',
                    title: 'Action ' + nextAppStatus + ' Updates',
                    text: '',
                    customClass: {
                        confirmButton: 'btn btn-success waves-effect waves-light'
                    }
                });
                $('#load').css('visibility',"hidden");
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
                $('#load').css('visibility',"hidden");
            }



        },
        error: function (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Form submission failed: ' + error,
                customClass: {
                    confirmButton: 'btn btn-danger waves-effect waves-light'
                }
            });
            $('#paperIconSub').removeClass('loading').attr('disabled', false);
            $('#load').css('visibility',"hidden");
        }
    });
}

function signProcess(formData, curAppStatus) {

    var approveVal = 'approved';

    var nextAppStatus = 'Signing';

    formData.delete("nextAppStatus");
    formData.delete("userInputVal");
    formData.append('nextAppStatus', nextAppStatus);
    formData.append('userInputVal', approveVal);
    $('#paperIconSub').addClass('loading').attr('disabled', true);
    $.ajax({
        url: APP_URL + '/contracts/sendContractForReview',
        type: 'POST',
        data: formData,
        processData: false, // Important for file uploads
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.message == 'successful!') {
                Swal.fire({
                    icon: 'success',
                    title: 'Action ' + nextAppStatus + ' Updates',
                    text: '',
                    customClass: {
                        confirmButton: 'btn btn-success waves-effect waves-light'
                    }
                });
                $('#load').css('visibility',"hidden");
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
                $('#load').css('visibility',"hidden");
            }



        },
        error: function (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Form submission failed: ' + error,
                customClass: {
                    confirmButton: 'btn btn-danger waves-effect waves-light'
                }
            });
            $('#paperIconSub').removeClass('loading').attr('disabled', false);
            $('#load').css('visibility',"hidden");
        }
    });
}




$(document).ready(function () {

    $('.task_type').on('change', function () {

        if ($(this).val() == 'recurring') {
            $('.cornDiv').show();
            $('.oneTimeDiv').hide();
        }else{
            $('.oneTimeDiv').show();
            $('.cornDiv').hide();
        }

    })

    $('.task_ends_on').on('change', function () {

        if ($(this).val() == 'on') {
            $('.endsOnDiv').show();
        }else{
            $('.endsOnDiv').hide();
        }

    })

    // $('.cloneuses').click(function(){
    //     $('#sortable').append($('#sortable li:last-child').html());
    // })






    //   $("#sortable").sortable({
    //              placeholder: "accordion-placeholder",
    //              connectWith: "#sortable",
    //              revert: true,
    //              handle: ".cursor",
    //              helper: "clone",
    //              start: function(e, ui) {
    //                  ui.placeholder.html('<div class="col-sm-6 high ">' + ui.item.html() + '</div>');
    //              },
    //              update: function(event, ui) {
    //                  // Update index and group values after sorting
    //                  $(this).find('input.index').each(function(index) {
    //                      $(this).val(index + 1);
    //                  });
    //                  $(this).find('input.group').val($(this).closest('.panel').find('.panel-title').data('id'));
    //              },
    //          }).disableSelection();




    var localState = 0;

    $('#updateStateTo5').click(function () {
        localState = 5;
        $('#stateText').addClass('hide');
        $('#stateText5').removeClass('hide');
    });

    $('#updateStateTo0').click(function () {
        localState = 0;
        $('#stateText5').addClass('hide');
        $('#stateText').removeClass('hide');
    });



    $('.showinedit, #termination').hide();

    //$('.signing_date').hide();

    $('.contractmode').on('change', function () {
        if ($(this).val() == 'old') {
            $('.showinedit').show();
            $('.signing_date').show();
            $('.signing_date input').attr('required', true);
            $('.required-field-old').show();
            $('[data-div="upload"]').prop('checked', true);
            quill.setContents([]);
            $('.attachmentsdiv').hide();
            $(`#attachments_type_upload`).show();           

        }else{
            $('.showinedit').hide();
            $('.signing_date').hide();
            $('.signing_date input').removeAttr('required');
            $('.required-field-old').hide();
        }
    })

    function updateContractName() {
        var selectedText = $('#contracttype').find(':selected').text();
        const d = new Date();
        let year = d.getFullYear();
        $('#contractName').val($.trim(selectedText));
    }

    // Custom fields flagged is_generic carry .groupby-generic and apply to every contract
    // type, so they are always part of the visible set alongside the type-specific ones.
    function groupbyFor(contTypeId) {
        return $('.groupby-' + contTypeId).add('.groupby-generic');
    }

    function renderCustomFieldsForContractType(contTypeId) {
        $('.groupby').hide();
        let $visibleGroups = groupbyFor(contTypeId);
        $visibleGroups.show();

        $('.customFieldTitleSection').hide();
        let visibleCategoryIds = {};

        $visibleGroups.each(function () {
            let countAppers = $(this).data('count');
            let typeCustom = $(this).data('catet');

            if (countAppers > 0 && typeCustom !== undefined) {
                visibleCategoryIds[typeCustom] = true;
            }
        });

        Object.keys(visibleCategoryIds).forEach(function (categoryId) {
            $('.customFieldTitleSection_' + categoryId).show();
        });
    }

    renderCustomFieldsForContractType($('#contracttype').val());

    $('#contracttype').on('change', function () {
        updateContractName();
        let contTypeId = $(this).val();
        renderCustomFieldsForContractType(contTypeId);

        getTemplateForContract();
    });



    $('.typerenewal').on('change', function () {
        if ($(this).val() == 'manualRenewal') {
            $('.typerenewallable').text('Manual renewal date');
            $('[name="Duration[autoRenewalDate]"]').next('.flatpickr.input').attr('placeholder','Manual renewal date');
            $('.auto-renewal-section').hide();
        }
        if ($(this).val() == 'automaticrenewal') {
            $('.typerenewallable').text('Auto renewal Date');
            $('[name="Duration[autoRenewalDate]"]').next('.flatpickr.input').attr('placeholder','Auto renewal date');
            $('.auto-renewal-section').show();
        }
    })



    $('.attachment_group').on('change', function () {
        if ($(this).val() == 'takefromtemplate') {
            $('.custom-file').hide()
        } else {
            $('.custom-file').show()
        }
    })

    setTimeout(() => {

        if ($('.createcontractnew').length == 1) {

        } else {
            $('.partygroup').on('change', function () {
                if ($(this).val() == 'External') {
                    $(this).closest('.group-ry').find('.Internal').hide();
                    $(this).closest('.group-ry').find('.External').show();
                }
                if ($(this).val() == 'Internal') {
                    $(this).closest('.group-ry').find('.Internal').show();
                    $(this).closest('.group-ry').find('.External').hide();
                }
            })
        }
    }, 500);

    //  store/contract

    $(document).on('click', '.removerow', function () {
        $(this).closest('.group-ry').remove();
    });



    function addMorePartis() {


        // The V3 create page serves a party row carrying the extra vendor/address/contact
        // fields, so the source URL is overridable. Defaults to the standard row.
        var partyRowUrl = window.PARTY_ROW_URL || '/contracts/create/parties';

        $.ajax({
            url: APP_URL + partyRowUrl + '?typ=jss',
            type: 'POST',
            data: {'mode':'External'},
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $(".party-group").append(response);
                $('.partyc').each(function (index) {
                    $(this).html(index + 1);
                    $(this).closest('.group-ry').attr('id', 'rodid' + (index + 1));
                    $(this).closest('.group-ry').find('.partyExternal').attr('id', 'partyExternal_' + (index + 1));
                    $(this).closest('.group-ry').find('.partySubType').attr('data-party-row', (index + 1));
                })

                $('.removerow').click(function () {
                    $(this).closest('.group-ry').remove();
                });

                $('.index').each(function (index) {
                    $(this).val(index);
                })

                $('.group-ry').each(function (index) {
                    $(this).addClass('gropuid' + index);
                    if (typeof select2 !== 'undefined') {
                        $(this).find('.contractname:not(.isinput)').select2();
                        //$(this).find('.Internal .userBranch')
                        let selectedType = $(this).find('.partygroup:checked').attr('id');

                        if(selectedType == 'Internal'){
                            if($(this).find('.Internal .userBranch').val() == ""){
                                $(this).find('.partycontracttype').select2();
                                $(this).find('.Internal .userBranch').val('').trigger('change').next(".select2-container").show();
                                $(this).find('.Internal .allBranch').val('').trigger('change').next(".select2-container").hide(); 
                            }
                        }
                        
                        if(selectedType == 'Intergroup'){
                            if($(this).find('.Internal .allBranch').val() == ""){
                                $(this).find('.partycontracttype').select2();
                                $(this).find('.Internal .userBranch').val('').trigger('change').next(".select2-container").hide();
                                $(this).find('.Internal .allBranch').val('').trigger('change').next(".select2-container").show();
                            }
                        }
                        
                        if(selectedType == 'External'){
                            $(this).find('.partycontracttype').select2();
                            $(this).find('.Internal .userBranch').val('').trigger('change').next(".select2-container").hide();
                            $(this).find('.Internal .allBranch').val('').trigger('change').next(".select2-container").hide();
                        }
                    }

                });

                $('.group-ry').each(function (index) {
                    $(this).find('.partygroup').attr('name', 'Partygroup[party][' +
                        index + '][mode]');
                    $(this).find('.contractname').attr('name', 'Partygroup[party][' +
                        index + '][internal_name]');
                    $(this).find('.partycontracttype.allBranch').attr('name',
                        'Partygroup[party][' + index + '][location_grp]');
                    $(this).find('.partycontracttype.userBranch').attr('name',
                        'Partygroup[party][' + index + '][location]');
                    $(this).find('.partyExternal').attr('name', 'Partygroup[party][' +
                        index + '][external_name]');
                    $(this).find('.partyExternal').attr('id', 'partyExternal_' + index);                        
                    $(this).find('.partySubType').attr('name', 'Partygroup[party][' +
                        index + '][external_type]');
                    $(this).find('.partySubType').attr('id', 'partyExternal_' + index + '_type');                        
                    $(this).find('.partySubType').attr('data-party-row', index);                        
                    $(this).find('.index').attr('name', 'Partygroup[party][' + index +
                        '][index]');
                    // V3-only fields; the selectors simply match nothing on the other pages.
                    $(this).find('.party-vendor-code').attr('name', 'Partygroup[party][' +
                        index + '][vendor_code]');
                    $(this).find('.party-contact-details').attr('name', 'Partygroup[party][' +
                        index + '][contact_details]');
                    $(this).find('.party-address').attr('name', 'Partygroup[party][' +
                        index + '][party_address]');
                    $(this).find('.party-vendor-code, .party-contact-details, .party-address')
                        .attr('data-party-row', index);

                    // Destroy any existing instance before re-initialising: this loop runs
                    // over EVERY party row on each "Add more parties" click, so a plain
                    // .select2() would stack a new instance (and its listeners) on the
                    // already-initialised rows and eventually lock up the dropdowns.
                    $(this).find('.partySubType, .partyExternal').each(function () {
                        if ($(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2('destroy');
                        }
                        $(this).select2();
                    });
                    //$(this).find('.Internal .userBranch').val('').trigger('change').next(".select2-container").hide();
                    //$(this).find('.Internal .allBranch').val('').trigger('change').next(".select2-container").show();                    
                })

                $('.contractname:not(.isinput)').each(function (index) {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                    $(this).select2();
                })
                $('.partycontracttype').on('change', function () {
                    if ($(this).val() != null) {
                        $(this).closest('.col-sm-6').find('.address-list li').hide();
                        $(this).closest('.col-sm-6').find('.address-list li#' + $(this)
                            .val()).show();
                    }
                })

            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    }

    $('.admo').click(function (e) {
        e.preventDefault();
        addMorePartis();
    });

    // The create pages render only the selected party's address; every other party is fetched
    // here on pick. Rendering all of them cost 7.6 MB of an 8.9 MB page on 5,001 parties.
    //
    // The show/hide path below is unchanged, so the pages that still pre-render the whole list
    // - partyDetails, partyDetailsEdit, partyDetailsView - behave exactly as before. The fetch
    // only runs when the picked party has no <li> in the page yet.
    $(document).on('change','.partyExternal', function () {
        var $list = $(this).closest('.col-sm-6').find('.external-address-list');
        var partyId = $(this).val();

        $list.find('li').hide();

        if (partyId == null || partyId === "") {
            return;
        }

        var $item = $list.find('li#' + partyId);

        if ($item.length) {
            $item.show();
            return;
        }

        $.get(APP_URL + '/contracts/create/party-address', { party: partyId })
            .done(function (html) {
                if (!html) {
                    return;
                }
                // The pick may have moved on while the request was in flight.
                if ($list.closest('.col-sm-6').find('.partyExternal').val() != partyId) {
                    return;
                }
                $list.find('li').hide();
                $list.append(html);
            });
    });

    $(document).on('change keyup keydown','.mandateField', function () {
        if ($(this).val() != null && $(this).val() != "") {
            $(this).parent('.input-group').parent('.approvalInpsSection').find('.mandateFieldReq').hide();
        }else{
            $(this).parent('.input-group').parent('.approvalInpsSection').find('.mandateFieldReq').show();           
        }
    });
    $(document).on('change','[name="approvalInps[end_contract_type]"]', function () {
        if ($(this).val() != null && $(this).val() == 'evergreen') {
            $('[name="approvalInps[contract_end_date]"]').removeAttr('required');
            $('[name="approvalInps[contract_end_date]"]').parent('.input-group').parent('.approvalInpsSection').hide().find('.mandateFieldReq').hide()
        }else{
            $('[name="approvalInps[contract_end_date]"]').prop('required', true);
            $('[name="approvalInps[contract_end_date]"]').parent('.input-group').parent('.approvalInpsSection').show().find('.mandateFieldReq').show()
        }
    });
    
    // Re-initialising select2 on an element that is already a select2 leaves the old
    // instance (and its listeners/containers) behind. Repeated party-type changes then
    // stack instances until opening the dropdown locks up the page. Always destroy first.
    function resetPartyNameSelect2($el, currentRow) {
        if (!$el.length) {
            return;
        }
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        $el.select2({
            language: {
                searching: function () {
                    return "Searching...";
                },
                noResults: function () {
                    return `No Party Name Found Click to create new   <button type="button" class="badge bg-primary cusocli" data-exdd="${currentRow}" data-bs-toggle="modal" data-bs-target="#onboardHorizontalImageModal">Create</button>`;
                }
            },
            escapeMarkup: function (markup) {
                return markup;
            }
        });
    }

    $(document).on('change','.partySubType', function () {
        // Read the attribute, not .data(): rows are re-indexed with .attr('data-party-row')
        // when parties are added/removed, and jQuery's .data() cache would keep the stale
        // index and populate the wrong row's name dropdown.
        let currentRow = $(this).attr('data-party-row');
        let subType = $(this).val();
        let $nameSelect = $(`#partyExternal_${currentRow}`);

        // No type chosen -> just clear the name list, no request needed.
        if (!subType) {
            if ($nameSelect.hasClass('select2-hidden-accessible')) {
                $nameSelect.select2('destroy');
            }
            $nameSelect.html('<option value="">- Select -</option>');
            resetPartyNameSelect2($nameSelect, currentRow);
            return;
        }

        $.ajax({
            method: 'POST',
            url: APP_URL+'/contracts/create/partylist',
            data: {partySubType : subType},
            headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                var optionsHtml = '<option value="">- Select -</option>';
                $.each(response.results,function(key, value)
                {
                    optionsHtml += '<option value="' + value.id + '">' + value.text + '</option>';
                });
                // Destroy before replacing the options, then re-init once.
                if ($nameSelect.hasClass('select2-hidden-accessible')) {
                    $nameSelect.select2('destroy');
                }
                $nameSelect.html(optionsHtml);
                resetPartyNameSelect2($nameSelect, currentRow);
                $nameSelect.trigger('ajax:done');
            },
            error: function(xhr, status, error) {
               console.error(xhr.responseText);
            }
        });
    });
    
    //For Show All fields in Contract Create
    
    $(document).on('change', '#showAllFields', async function (e){
        if(!$(this).is(':checked')){
            $('.unRequiredFields').css('display', 'none');
        }else{
            $('.unRequiredFields.row').css('display', 'flex');
            $('.unRequiredFields:not(.row)').css('display', 'inline');
            $('hr.unRequiredFields').css('display', 'block');
        }
    });    

    $('.commencementDate').on('change', function () {
        $('#FixedDate, #Eventbased').hide();
        $('#' + $(this).val()).show();
    })

    $('.conditionEndContract').on('change', function () {
        $('#conditionEndContractOthers').hide();
        if ($(this).val() == 'others') {
            $('#conditionEndContractOthers').show();
        }
    })

    $('.contractCommencementEffectiveDate').on('change', function () {
        $('#termination, #evergreen, #fixedTerm, #onetimeContract').hide();
        $('#' + $(this).val()).show();
    });
    
    $('.allBranch').each(function(elm){
        if($(this).val() == ""){
            $(this).next(".select2-container").hide();
        }else{
            $($('.userBranch')[elm]).next(".select2-container").hide();
        }
    });

    $(document).on('change', '.partygroup', function () {
        if ($(this).attr('id') == 'External') {
            $(this).closest('.group-ry').find('.Internal').hide();
            $(this).closest('.group-ry').find('.External').show();
            $(this).closest('.group-ry').find('.Internal .userBranch').val('');
            $(this).closest('.group-ry').find('.Internal .allBranch').val('');
        }
        if ($(this).attr('id') == 'Internal') {
            $(this).closest('.group-ry').find('.Internal').show();
            $(this).closest('.group-ry').find('.Internal .userBranch').val('').trigger('change').next(".select2-container").show();
            $(this).closest('.group-ry').find('.Internal .allBranch').val('').trigger('change').next(".select2-container").hide();
            $(this).closest('.group-ry').find('.External').hide();
        }
        if ($(this).attr('id') == 'Intergroup') {
            $(this).closest('.group-ry').find('.Internal').show();
            $(this).closest('.group-ry').find('.Internal .userBranch').val('').trigger('change').next(".select2-container").hide();
            $(this).closest('.group-ry').find('.Internal .allBranch').val('').trigger('change').next(".select2-container").show();
            $('.partycontracttype').select2("val", "");
            $(this).closest('.group-ry').find('.External').hide();
        }
    });

    $.validator.addClassRules({
        'contractName': {
            required: true
        },
        'contracttype': {
            required: true
        },
        'description': {
            required: true
        },
        'required1': {
            required: true
        },
    });

    $('#createcontract').on('change input', 'input, select, textarea', function () {
        $(this).valid();
    });
    
    $('#createContractButton').on('click', function (e) {
        let createForm = document.getElementById('createcontract');
        
        var formData = new FormData(createForm);
        
        if(typeof quill !== 'undefined' && quill && $('#template-editor').length > 0){
            let html = quill.root.innerHTML;
            $('#template_text').text(encodeHTML(html));
        }

        createForm.submit();   
        
        $('#load').css('visibility',"visible");
    });    

    $('.timelinetabs a').on('click', function () {
        var dtype = $(this).attr('data-type');

        $('.timelinetabs a').removeClass('active');
        $(this).addClass('active');
        switch (dtype) {
            case 'Detail':
                $('.contractFlow').hide();
                $('.contractApprovals').show();
                break;

            case 'Chart':
                $('.contractFlow').show();
                $('.contractApprovals').hide();
                break;
        }

    });

    $('#createcontract1').validate({
        escapeHtml: true,
        ignore: [],
        errorPlacement: function (error, element) {
            if (element.hasClass('select2-hidden-accessible')) {
                error.insertAfter(element.next('.select2'));
            } else if (element.hasClass('flatpickr')) {
                error.insertAfter(element.next('.flatpickr.input'));
            } else {
                error.insertAfter(element);
            }
        },
        highlight: function (element, errorClass, validClass) {
            $(element).closest('.accordion-item').addClass('has-error');
            if ($(element).hasClass('flatpickr')) {
                $(element).closest('.accordion-item').addClass('has-error');
            }
        },
        unhighlight: function (element, errorClass, validClass) {
            var $accordionItem = $(element).closest('.accordion-item');

            // Check if there are any visible error messages within the accordion item
            if ($accordionItem.find('.error:visible').length === 0) {
                $accordionItem.removeClass('has-error');
            }
        },
        rules: {
            "owner": {
                required: true
            },
            "BasicContract[signatory]": {
                required: true,
            },
            "BasicContract[catgoeryType]": {
                required: true,
            },
            "BasicContract[DepartmentType]": {
                required: true,
            },
            "Duration[signingDate]": {
                required: function (element) {
                    if ($('.contractmode:checked').val() == 'old') {
                        return true;
                    }
                },
            },
            "ContractValue[paymentSchedule]": {
                required: function (element) {
                    if ($('.contractmode:checked').val() == 'old') {
                        return true;
                    }
                },
            },

            "Duration[fixedtimeEndDateofContract]": {
                required: function () {
                    return $('.contractmode:checked').val() === 'old' &&
                        $('input[name="Duration[effectiveDate]"]:checked').val() === 'fixedTerm';
                },
            },
            "Duration[terminationDate]": {
                required: function () {
                    return $('.contractmode:checked').val() === 'old' &&
                        $('input[name="Duration[effectiveDate]"]:checked').val() === 'termination';
                },
            },
            "Duration[fixedDate]": {
                required: function () {
                    return $('.contractmode:checked').val() === 'old' &&
                        $('input[name="Duration[commencementDate]"]:checked').val() === 'FixedDate';
                },
            },
            "Duration[onetimeEndDateofContract]": {
                required: function () {
                    return $('.contractmode:checked').val() === 'old' &&
                        $('input[name="Duration[effectiveDate]"]:checked').val() === 'onetimeContract';
                },
            },

            "ContractValue[Retention]": {
                required: function (element) {
                    if ($('.contractmode:checked').val() == 'old') {
                        return true;
                    }
                    // return false;  
                },
            },
            "ContractValue[payment_escrow]": {
                required: function (element) {
                    if ($('.contractmode:checked').val() == 'old') {
                        return true;
                    }
                    // return false;  
                },
            },

            'ContractValue[value]': {
                required: function (element) {
                    if ($('.contractmode:checked').val() == 'old') {
                        return true;
                    }
                    // return false;  
                },
            },
        },
        messages: {
            label: {
                required: "Please enter a Field Name"
            },
            contracttype: {
                required: "Please select a Contract Type"
            },
            category: {
                required: "Please select a Category"
            }
        }
    });
    
    $('#onboardHorizontalImageModal').on('hidden.bs.modal', function (e) {
       let datacut = $('.popap').data('cut');
        $('.group-ry.gropuid' + datacut).find('.partySubType').trigger('change');
    });    

});



if (typeof flatpickr !== 'undefined') {


    $(".flatpickr").flatpickr({
        altInput: true,
        altFormat: "d-m-Y",
        //   defaultDate: new Date(),
        dateFormat: "Y-m-d",
        prevArrow: "<i class='fa fa-chevron-left'></i>",
        nextArrow: "<i class='fa fa-chevron-right'></i>",
        allowInput:true
    });


}

$('.contracttype').change(function () {
    var selectedOption = $(this).find('option:selected');

    var catid = selectedOption.data('catid');
    var detid = selectedOption.data('detid');
    var contyp = $(this).val();
    
    if(typeof catid != "string"){
        catid = `${catid}`;
    }
    // Category values can be free text (e.g. "F and B service"), so they are matched on the
    // option's value rather than interpolated into an attribute selector, which would throw
    // on spaces and other selector characters.
    let finalCates = catid != "" ? catid.split(',').map(function (val) { return val.trim(); }) : null;
    $("#catgoeryType option").attr('disabled', 'disabled');
    $("#catgoeryType option[value='1']").removeAttr('disabled', 'disabled');
    finalCates?.forEach(function(val, idx){
        $("#catgoeryType option").filter(function () {
            return this.value === val;
        }).removeAttr('disabled');
    });

    if(finalCates && finalCates.length > 0){
        $('#catgoeryType').val('');
        $('#catgoeryType').val(finalCates[0]).trigger('change');
    }else{
      $("#catgoeryType").val($("#catgoeryType option:first").attr('value') ?? '').trigger('change');
    }
    $('#DepartmentType').val('').val(detid).trigger('change');
    $('#contracttypetags').val('').val(contyp).trigger('change');
    $("#catgoeryType").select2({
    templateResult: function(option, container) {
        if ($(option.element).attr("disabled") == "disabled"){ 
          //$(container).css("display","none");
        }

        return option.text;
    }
});

});



$(document).on('click', '.representative_delete_row', function (event) {

    $('.representative_row_' + $(this).attr('id')).remove();

})





$(document).on('click', '.cusocli', function (event) {
    $('.popap').attr('data-cut', $(this).attr('data-exdd'));
    let subType = $(`#partyExternal_${$(this).attr('data-exdd')}_type`).val();
    
    $.ajax({
        url: APP_URL + `/parties/contract-parties-${subType != "individual" ? 'org' : 'ind'}-add?by=ajax`,
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            $('.popap').html(response);
        }
    });
})


$(document).on('click', '.navstascokie', function (event) {

    event.preventDefault();
    var now = new Date();
    now.setTime(now.getTime() + (60 * 60 * 1000)); // 60 minutes * 60 seconds * 1000 milliseconds 
    var expires = "expires=" + now.toUTCString();
    var id = $('.navstas').attr('data-id');
    document.cookie = 'historical=' + id + '; ' + expires + '; path=/';

    setInterval(() => {
        window.location.href = $(this).attr('href');
    }, 500);
})


function getCookie(name) {
    // Construct a regular expression to match the cookie name
    var value = "; " + document.cookie;
    var parts = value.split("; " + name + "=");

    // If the cookie is not found, return null
    if (parts.length == 2) {
        return parts.pop().split(";").shift();
    }

    return null;
}


/*** Link / Unlink Contracts ***/
$(document).on('click', '.linkContract', function () {

    let linkParId = $(this).data("linkcon");
    let linkStatus = $(this).data("linktype");
    let linkId = $("#contractRefId").val();
    Swal.fire({
        title: 'Do You really want to ' + linkStatus + ' this contract?',
        text: "Please Type Continue",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        input: "text",
        customClass: {
            confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
            cancelButton: 'btn btn-label-secondary waves-effect waves-light'
        },
        buttonsStyling: false
    })
        .then(function (result) {
            if (result.value.toLowerCase() == 'continue') {
                $.ajax({
                    url: APP_URL + '/contracts/link',
                    type: 'POST',
                    data: { "linkid": linkId, "linkStatus": linkStatus, "parentContract": linkParId },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {

                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Contract Link Alert',
                                text: response.message,
                                customClass: {
                                    confirmButton: 'btn btn-success waves-effect waves-light'
                                }
                            }).then(function (result) {
                                if (result.value) {
                                    location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Sorry!',
                                text: response.message,
                                customClass: {
                                    confirmButton: 'btn btn-dager waves-effect waves-light'
                                }
                            });
                        }

                        //

                    },
                    error: function (error) {
                        console.log('Error submitting form:', error);
                    }
                });

            }

        });
});

/*** Resend External Email ***/
$(document).on('click', '.resendEmailExternal', function () {

    let externalEmail = $(this).data("ex-mail");
    let linkId = $("#contractLinkId").val();
    Swal.fire({
        title: `Confirmation`,
        text: `Do You really want to send email to ${externalEmail}? Please Type Send`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        input: "text",
        customClass: {
            confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
            cancelButton: 'btn btn-label-secondary waves-effect waves-light'
        },
        buttonsStyling: false
    })
        .then(function (result) {
            $('#load').css('visibility',"visible");
            if ((result.value?.toLowerCase() ?? '') == 'send') {
                $.ajax({
                    url: APP_URL + '/resend/sendExternalMail',
                    type: 'POST',
                    data: { "linkid": linkId, "exMail": externalEmail },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        $('#load').css('visibility',"hidden");
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'External Signature Mail Resend Request',
                                text: response.message,
                                customClass: {
                                    confirmButton: 'btn btn-success waves-effect waves-light'
                                }
                            }).then(function (result) {
                                if (result.value) {
                                    //location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Sorry!',
                                text: response.message,
                                customClass: {
                                    confirmButton: 'btn btn-dager waves-effect waves-light'
                                }
                            });
                        }

                        //

                    },
                    error: function (error) {
                        $('#load').css('visibility',"hidden");
                        console.log('Error submitting form:', error);
                    }
                });

            }else{
                $('#load').css('visibility',"hidden");
                Swal.fire({
                    icon: 'error',
                    title: 'Sorry!',
                    text: 'Invalid Comment Please Try Again',
                    customClass: {
                        confirmButton: 'btn btn-dager waves-effect waves-light'
                    }
                });                
            }

        });
});






$(document).on('change', '.myFile', function () {
    let file = $(this)[0].files[0];
    let fileType = $('#fileTypeDoc').val();
    if (file) {
        $(this).addClass('disabled');

        let fileName = file.name;
        $('#fileList').append(`
            <tr class="file-item">
                <td>${fileName}</td>
                <td>${fileType.toUpperCase()}</td>
                <td><button class="remove-file btn btn-danger btn-sm pull-right" data-file-name="${fileName}">Remove</button></td>
            </tr>
        `);
        $(this).parent('.mfiles').prepend(`<input type="hidden" class="disabled-fileType" data-file-name="${fileName}" name="fileType[${fileName}]" value="${fileType}"/><input class="myFile" name="photos[]" type="file">`);
        if(fileType == 'contract'){
            $('#fileTypeDoc option[value="contract"]').removeAttr('selected');
        }
    }
});

$(document).on('change', '.myFile1', function () {
    let file = $(this)[0].files[0];
    let fileType = $('#fileTypeDoc1').val();
    if (file && fileType != "") {
        $(this).addClass('disabled');

        let fileName = file.name;
        $('#fileList').append(`
            <tr class="file-item">
                <td>${fileName}</td>
                <td>${fileType.toUpperCase()}</td>
                <td><button class="remove-file btn btn-danger btn-sm pull-right" data-file-name="${fileName}" data-doctype="${fileType}" data-docsel="fileTypeDoc1">Remove</button></td>
            </tr>
        `);
        $(this).parent('.mfiles').prepend(`<input type="hidden" class="disabled-fileType" data-file-name="${fileName}" name="fileType[${fileName}]" value="${fileType}"/><input class="myFile1" name="photos[]" type="file">`);
        if(fileType == 'contract'){
            $('#fileTypeDoc1 option[value="contract"]').attr('disabled', true).removeAttr('selected');
            $('#fileTypeDoc1').val("").trigger('change');
        }
    }else{
        alert('Please Select Document Type');
        $(this).val('');
    }
});

$(document).on('change', '.myFile2', function () {
    let file = $(this)[0].files[0];
    let fileType = $('#fileTypeDoc2').val();
    if (file && fileType != "") {
        $(this).addClass('disabled');

        let fileName = file.name;
        $('#fileList').append(`
            <tr class="file-item">
                <td>${fileName}</td>
                <td>${fileType.toUpperCase()}</td>
                <td><button class="remove-file btn btn-danger btn-sm pull-right" data-file-name="${fileName}" data-doctype="${fileType}" data-docsel="fileTypeDoc2">Remove</button></td>
            </tr>
        `);
        $(this).parent('.mfiles').prepend(`<input type="hidden" class="disabled-fileType" data-file-name="${fileName}" name="fileType[${fileName}]" value="${fileType}"/><input class="myFile2" name="photos[]" type="file">`);
        if(fileType == 'contract'){
            $('#fileTypeDoc2 option[value="contract"]').attr('disabled', true).removeAttr('selected');
            $('#fileTypeDoc2').val("").trigger('change');
        }
    }else{
        alert('Please Select Document Type');
        $(this).val('');
    }
});


$(document).on('change', '.signFile', function () {
    let file = $(this)[0].files[0];
    let fileType = $('#fileTypeDocSign').val();
    if (file && fileType != "") {

        let fileName = file.name;
        $('#fileList').append(`
            <tr class="file-item">
                <td>${fileName}</td>
                <td>${fileType.toUpperCase()}</td>
                <td><button class="remove-file btn btn-danger btn-sm pull-right" data-file-name="${fileName}" data-doctype="${fileType}" data-docsel="fileTypeDocSign">Remove</button></td>
            </tr>
        `);
        $(this).parent('.files').prepend(`<input type="hidden" class="disabled-fileType" data-file-name="${fileName}" name="fileType[${fileName}]" value="${fileType}"/>`);
    }
});


function hideOtherTimelineElements(tabButtonText=""){
    $('#mainTabDetails .nav-link.active').removeClass('active');
    $('#currentFlowApprovals').removeClass('d-none').addClass('active');
    $('#currentFlowApprovals button').addClass('active');  
    if(tabButtonText != ""){
        $('#currentFlowApprovals button').text(tabButtonText);
    }
    $('.timechartview').hide();
    $('.approved_timelines').hide();
    $('.pending_timelines .timeline li').removeClass();
}

$(document).on('change', '.myFilenew', function () {
    let file = $(this)[0].files[0];
    let fileType = $('#fileTypeDocnew').val();
    if (file && fileType != "") {
        $(this).addClass('disabled');

        let fileName = file.name;
        $('#fileListnew').append(`
            <tr class="file-item">
                <td>${fileName}</td>
                <td>${fileType.toUpperCase()}</td>
                <td><button class="remove-file-new btn btn-danger btn-sm pull-right" data-file-name="${fileName}" data-doctype="${fileType}" data-docsel="fileTypeDocnew">Remove</button></td>
            </tr>
        `);
        $(this).parent('.mfiles').prepend(`<input type="hidden" class="disabled-fileType" data-file-name="${fileName}" name="fileType[${fileName}]" value="${fileType}"/><input class="myFilenew" name="photos[]" type="file">`);
        if(fileType == 'contract'){
            $('#fileTypeDocnew option[value="contract"]').attr('disabled', true).removeAttr('selected');
            $('#fileTypeDocnew').val("").trigger('change');
        }
    }else{
        alert('Please Select Document Type');
        $(this).val('');
    }
});



$(document).on('click', '.editInputApprovals', function (e) {
    e.preventDefault();
    let enableEdit = $(this).data('enableedit');
    $(this).prev(`#${enableEdit}`).prop("disabled", false);
});

$(document).on('click', '.remove-file', function (e) {
    e.preventDefault();

    let fileName = $(this).data('file-name');
    let fileType = $(this).data('doctype');
    let fileTypeSel = $(this).data('docsel');
    
    if(fileType == 'contract'){
        $(`#${fileTypeSel} option[value="contract"]`).attr('disabled', false);
        $(`#${fileTypeSel}`).val("").trigger('change');        
    }

    $(this).parent().parent().remove();
    $('.disabled').each(function () {
        if ($(this)[0].files[0].name === fileName) {
            $(this).remove();
        }
    });
    $('.disabled-fileType').each(function () {
        if ($(this).data('file-name') === fileName) {
            $(this).remove();
        }
    });    
});

$(document).on('click', '.remove-file-new', function (e) {
    e.preventDefault();

    let fileName = $(this).data('file-name');
    let fileType = $(this).data('doctype');
    let fileTypeSel = $(this).data('docsel');
    
    if(fileType == 'contract'){
        $(`#${fileTypeSel} option[value="contract"]`).attr('disabled', false);
        $(`#${fileTypeSel}`).val("").trigger('change');        
    }

    $(this).parent().parent().remove();
    $('.disabled').each(function () {
        if ($(this)[0].files[0].name === fileName) {
            $(this).remove();
        }
    });
    $('.disabled-fileType').each(function () {
        if ($(this).data('file-name') === fileName) {
            $(this).remove();
        }
    });
});

//For Financial Limit Get Signatory
$(document).on('change','.userBranch, #contracttype, #DepartmentType,#catgoeryType, #ContractBillingValue', ()=>{checkFinancialLimit()});


function checkFileTypeExist(chekClass = "", checkVal = "" ){
    if(chekClass != ""){
        $(`.${chekClass}`).each(function () {
            if($(this).val() == checkVal){
                alert('Contract Document Not Allowed To Upload More Than once');
                return false;
            }
        });
        
        return true;
    }
}

function form_modal_submit(idForm){
    $(`#${idForm}`).submit();
}


function getYearDiff(date1,date2, onlyYear=true){
    date1 = new Date(date1);
    date2 = new Date(date2) ;
    var diff = Math.floor(date1.getTime() - date2.getTime());
    var day = 1000 * 60 * 60 * 24;

    var days = Math.floor(diff/day);
    var months = Math.floor(days/31);
    //var years = days/365;
    
    const msInYear = 1000 * 60 * 60 * 24 * 365.25; // Accounts for leap years
    const diffInMs = Math.abs(new Date(date2) - new Date(date1));
    var years = diffInMs / msInYear;      
    
    if(onlyYear){
        return years;
    }
    let details_ = {
        'days':days,
        'months':months,
        'years':years}
        
    return details_;
}

function encodeHTML(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function decodeHTML(str) {
    return String(str).replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"');
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
                            (position.coords.latitude +
                            "," + position.coords.longitude),
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
    
    function checkFinancialLimit(){
        
        if($('#accordionOwnership').length){
            var formData = new FormData();
            let formLoc = [];
            $('.userBranch').each(function(elm){
                if($(this).val()){
                    formLoc.push($(this).val());
                }
            })
            formData.append('location', JSON.stringify(formLoc));
            formData.append('DepartmentType', $('[name="BasicContract[DepartmentType]"]').val());
            formData.append('catgoeryType', $('[name="BasicContract[catgoeryType]"]').val());
            formData.append('contractType', $('[name="BasicContract[contractType]"]').val());
            formData.append('value', $('[name="ContractValue[value]"]').val());
            formData.append('contractMode', $('[name="contractMode"]:checked').val());
            
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
                    if(response.success){
                        let signatoryResp = (response.message.signat).split(':');
                        let notiResp = (response.message.notyfy);
                        
                        if(signatoryResp.length > 0){
                            $('#ownership-signatory').val(parseInt(signatoryResp[0])).trigger('change');
                        }
                        if(notiResp.length > 0){
                            let oldval = $('#users-notify').val();
                            if(oldval == ""){
                                oldval = [];
                            }
                            const mergedUniqueVals = [...new Set([...oldval, ...notiResp])];
                            $('#users-notify').val(mergedUniqueVals).trigger('change');
                        }
                    }else{
                        if($('#accordionOwnership').is( ":visible" )){
                            // Swal.fire({
                            //     icon: 'warning',
                            //     title: 'Missing',
                            //     text: response.message,
                            //     customClass: {
                            //         confirmButton: 'btn btn-danger waves-effect waves-light'
                            //     }
                            // });
                        }
                        $('#ownership-signatory').val('').trigger('change');
                        $('#users-notify').val('').trigger('change');
                    }
                },
                error: function (xhr, status, error) {
                    // Handle error
                    alert('Form submission failed: ' + error);
                }
            });
        }
    }
