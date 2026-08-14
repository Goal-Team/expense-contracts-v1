
$(document).ready(function () {

$('.select2').select2({
    dropdownParent: $('#addPaymentOffcanvas')
});

$('#addObligationForm').submit(function (e) {
    e.preventDefault();

    var contract_id = $("#contract_id_obg").val();

    var owner = $("#task_owner").val();
    var signatory = $("#task_signatory").val();
    
    var sliderName = $("#sliderName").val();
    var task_id = $("#task_id").attr("value");


    

    var formData = new FormData(this);
    formData.append('contract_id', contract_id);
    formData.append('owner', owner);
    formData.append('signatory', signatory);
    formData.append('sliderName', sliderName);
    formData.append('task_id', task_id);

    $.ajax({
        url: APP_URL+'/contracts/addObligation', // Update with your route
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
                    text: 'Successfull',
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
                //location.reload();
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
            $('#load').css('visibility',"hidden");
        }
    });
    
});

$(document).on('click', '.editObligationBtn', function(e) {
    e.preventDefault();
    
    var row = $(this).closest('tr'); // Get the closest table row
    //get value from td
    var trRow = row.data('id'); 
  
    var task_id = trRow.id;
    var task_contract_id = trRow.contract_id;
    var task_owner = trRow.owner;
    var task_reviewer = trRow.reviewer;
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
     $('#contract_id_obg').val(task_contract_id);
     $('#task_owner').val(task_owner);
     $('#task_signatory').val(task_reviewer);

    $('.select2').select2({
        dropdownParent: $('#addPaymentOffcanvas')
    });

     var flatpickrInstanceD = flatpickr("#obDueDate");
      flatpickrInstanceD.setDate(task_end_date);


     var flatpickrInstanceOD = flatpickr("#OnetimeDate");
      flatpickrInstanceOD.setDate(onetime_end_date);

     var flatpickrInstance = flatpickr("#recuringEndDate");
      flatpickrInstance.setDate(recuring_due_date);

     $('#sliderName').val("update");
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
                            title: 'Updates Info',
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


var myOffcanvas = document.getElementById('addPaymentOffcanvas')
myOffcanvas.addEventListener('hidden.bs.offcanvas', function () {
     $('#popUpTitle').text("Add");
     $('#popUpAction').text("Add");
});



function negotiation(formData, curAppStatus) {
    var nextAppStatus = 'Negotiation';
    var approveVal = 'approved';
    formData.delete("nextAppStatus");
    formData.delete("userInputVal");
    formData.append('nextAppStatus', nextAppStatus);
    formData.append('userInputVal', approveVal);

    var url = window.location.href;
    var last = url.lastIndexOf('/') + 1;
    var base_url = url.substring(0, last);
    $('#paperIconSub').addClass('loading').attr('disabled', true);
    $.ajax({
        url: base_url + 'sendContractForReview', // Update with your route
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

    var url = window.location.href;
    var last = url.lastIndexOf('/') + 1;
    var base_url = url.substring(0, last);
    $('#paperIconSub').addClass('loading').attr('disabled', true);
    $.ajax({
        url: base_url + 'sendContractForReview',
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
                    title: '',
                    text: '',
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
        }
    });
}

function signProcess(formData, curAppStatus) {

    var approveVal = 'approved';

    var nextAppStatus = 'Signing';

    var url = window.location.href;
    var last = url.lastIndexOf('/') + 1;
    var base_url = url.substring(0, last);


    formData.delete("nextAppStatus");
    formData.delete("userInputVal");
    formData.append('nextAppStatus', nextAppStatus);
    formData.append('userInputVal', approveVal);
    $('#paperIconSub').addClass('loading').attr('disabled', true);
    $.ajax({
        url: base_url + 'sendContractForReview',
        type: 'POST',
        // data: { "id": contract_id, "nextAppStatus": nextAppStatus, "curAppStatus": curAppStatus, "userInputVal": approveVal,
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
                    title: '',
                    text: '',
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
  

});

if (typeof flatpickr !== 'undefined') {


    $(".flatpickr").flatpickr({
        altInput: true,
        altFormat: "F j, Y",
        //   defaultDate: new Date(),
        dateFormat: "Y-m-d",
        prevArrow: "<i class='fa fa-chevron-left'></i>",
        nextArrow: "<i class='fa fa-chevron-right'></i>"
    });


}



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
