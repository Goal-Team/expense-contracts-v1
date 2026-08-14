//  List

if ($.fn.DataTable) {
    $('#example thead input').on('keyup change', function () {

        var index = $(this).closest('th').index();
        $('#example').DataTable().column(index).search(this.value).draw();
    });


    // console.log(window.location, 'acdd');

    // var urlParams = new URLSearchParams(window.location.search);
    // console.log(urlParams.has('status'));

    var url = window.location.href;
    var last = url.lastIndexOf('/') + 1;
    var base_url = url.substring(0, last);

    new DataTable('#example', {
        ajax: 'contracts/data?status=' + $('#status').val(),
        processing: true,
        serverSide: false,
        layout: {
            topStart: {
                buttons: [{
                    extend: 'colvis',
                    columns: ':not(.noVis)',
                    popoverTitle: 'Column visibility'
                }]
            }
        },
        "scrollX": true,
        "columnDefs": [{
            "targets": [0],
            "searchable": true,
            "orderable": true
        },
        {
            targets: 1,
            className: 'noVis'
        },

        {
            "targets": 2,
            "render": function (data, type, row, meta) {
                if (type === 'display') {
                    return '<a href="' + base_url + 'contract/' + row[0] + '" class="custom-tag">' + data +
                        '</a>';
                } else {
                    return data; // Return the original data for filtering
                }
            }
        }
            // Add additional column definitions as needed
        ],
        // "columns": [
        //     { visible: true },
        // ]

    });
}

$('input[type="file"]').change(function (e) {
    var fileName = e.target.files[0].name;

    console.log(fileName);
    $('.custom-file-label').html(fileName);
});

// Create Form

$(document).ready(function () {

    var url = window.location.href;
    var last = url.lastIndexOf('/') + 1;
    var base_url = url.substring(0, last);


    setTimeout(function () {
        $('.emptyattachemnt').each(function (index) {
            var currentElement = $(this);
            currentElement.closest('tr').addClass('missing-data');
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





    $('#approvalAddUpdatesForm').submit(function (e) {
        e.preventDefault(); // Prevent the default form submission

        var btntext = $('#btn_save_updates_approve').text();
        // alert(btntext);return;

        // Gather form data
        // var formData = $(this).serialize()+'&actionBtntext='+btntext;

        var formData = new FormData(this);
        formData.append('actionBtntext', btntext);
        // console.log(formData);return;
        var url = window.location.href;
        var last = url.lastIndexOf('/') + 1;
        var base_url = url.substring(0, last);

        $.ajax({
            url: base_url + 'updateApprovals', // Update with your route
            type: 'POST',
            data: formData,
            processData: false, // Important for file uploads
            contentType: false, // Important for file uploads
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
                        text: response.message,
                        customClass: {
                            confirmButton: 'btn btn-danger waves-effect waves-light'
                        }
                    });
                    // location.reload();
                }
                // location.reload();
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
            }
        });
    });
    // $('#editApprovalAddUpdatesForm').submit(function (e) {
    //     e.preventDefault(); // Prevent the default form submission

    //     // Gather form data
    //     // var formData = $(this).serialize();
    //     var formData = new FormData(this); // Create FormData object from form

    //     var url = window.location.href;
    //     var last = url.lastIndexOf('/') + 1;
    //     var base_url = url.substring(0, last);

    //     $.ajax({
    //         url: APP_URL + '/contracts/updateApprovals', // Update with your route
    //         type: 'POST',
    //         data: formData,
    //         success: function (response) {
    //             location.reload();
    //         },
    //         error: function (xhr, status, error) {
    //             // Handle error
    //             alert('Form submission failed: ' + error);
    //         }
    //     });
    // });

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

        $('.updatesHeading').text(btntextApp);
        // if(btntextApp == 'Send to Negotiation'){
        //     $('#btn_save_updates').text(btntextApp);
        // }

        var index = $('#indexId').val();
        // $('#updatesDiv'+index).css({"border-color": "#24b364", 
        //     "border-width":"1px",
        //     "padding": "15px", 
        //     "border-style":"solid"});
        $('#appType' + index).val('approved');
        $("#updatesDiv" + index).css('display', '');
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
        // 
        // if(btntextRej == 'Send to Owner'){
        //     $('#btn_save_updates_reject').text(btntextRej);
        // }
        var index = $('#indexId').val();
        // $('#updatesDiv'+index).css({"border-color": "#ea5455", 
        //     "border-width":"2px",
        //     "padding": "15px", 
        //     "border-style":"solid"});
        $('#appType' + index).val('rejected');
        $("#updatesDiv" + index).css('display', '');
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

    // $('#btn_save_updates_reject').click(function () {

    //     var index = $('#indexId').val();
    //     $('#appType' + index).val('rejected');
    //     $("#updatesDiv" + index).css('display', '');
    // });

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

        var url = window.location.href;
        var last = url.lastIndexOf('/') + 1;
        var base_url = url.substring(0, last);



        $.ajax({
            url: base_url + 'updateflow', // Update with your route
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
      var fullEditor = new Quill('#template-editor', {
        bounds: '#template-editor',
        placeholder: 'Type Something...',
        modules: {
          formula: true,
          toolbar: fullToolbar,
          history: {
            delay: 2000,
            maxStack: 500,
            userOnly: true
          }          
        },
        theme: 'snow'
    });    
    
    $('#btn-html-shower').on('click', () => { 
        // Get HTML content
        var html = fullEditor.root.innerHTML;
        console.log(html);
    
    }); 
    $('#btn-html-undo').on('click', () => { 
        fullEditor.history.undo();
        console.log('undo');
    
    }); 
    $('#btn-html-redo').on('click', () => { 
        fullEditor.history.redo();
        console.log('redo');
    
    }); 
    $('#btn-doc-downloader').on('click', () => { 
        // Get HTML content
        var html = fullEditor.root.innerHTML;
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
        $('.attachmentsdiv').hide();
        $(`#attachments_type_${showDiv}`).show();
  });
});


$('#modalPopUpReview').click(function () {
    $("#modalCenter").modal("show");
    // onboardImageModal
    // $("#onboardImageModal").modal("show");
    $('#modalCenterTitle').text('Send For Review Process');
    $('.misgtable').hide();
    $('.misgtablenote').hide();
    $('#paperIcon').attr('disabled', false);
    // $('#paperIconNegaotition').attr("id","paperIcon");

});
$('#modalPopUpNegotiation').click(function () {
    $("#modalCenter").modal("show");
    $('#modalCenterTitle').text('Send For Negotiation Process');

    $('.misgtable').hide();
    $('.misgtablenote').hide();
    $('#paperIcon').attr('disabled', false);
    // $('#paperIcon').attr("id","paperIconNegaotition");
});

$('#modalPopUpApproval').click(function () {
    $("#modalCenter").modal("show");
    $('#modalCenterTitle').text('Send For Approval Process');

    $('.misgtable').hide();
    $('.misgtablenote').hide();
    $('#paperIcon').attr('disabled', false);

    $('.misgtable').show();
    if ($('.misgtable tbody tr').length > 0) {
        $('#paperIcon').attr('disabled', true);
        $('.misgtablenote').show();
    }
});
$('#modalPopUpReviewBack').click(function () {

    $('.misgtable').hide();
    $('.misgtablenote').hide();
    $('#paperIcon').attr('disabled', false);

    $("#modalCenter").modal("show");
    $('#modalCenterTitle').text('Send Back to Review');
});
$('#modalPopUpSign').click(function () {
    $('.misgtable').hide();
    $('.misgtablenote').hide();

    $("#modalCenter").modal("show");
    $('#modalCenterTitle').text('Send For Signing Process');

    $('.misgtable').show();
    if ($('.misgtable tbody tr').length > 0) {
        $('#paperIcon').attr('disabled', true);
        $('.misgtablenote').show();
    }
});



$('#ApprovalProcessPopup').submit(function (e) {

    e.preventDefault();
    var contract_id = $("#contractId").attr("value");
    var curAppStatus = $("#curAppStatus").attr("value");

    var shortDescrip = $("#shortDescrip").val();
    var appRowId = $("#appRowId").val();
    var ReviewDescription = $("#ReviewDescription").val();


    var approveVal = 'approved';

    var nextAppStatus = '';


    if (curAppStatus == 'Negotiation') {
        nextAppStatus = 'Approval'
    } else if (curAppStatus == 'Approved') {
        nextAppStatus = 'Signing'
    } else if (curAppStatus == 'Draft') {
        nextAppStatus = 'review'
    } else if (curAppStatus == 'review') {
        nextAppStatus = 'review'
    }

    var url = window.location.href;
    var last = url.lastIndexOf('/') + 1;
    var base_url = url.substring(0, last);


    var modalTitle = $('#modalCenterTitle').text();

    var negoStrFlag = modalTitle.indexOf("Negotiation");
    var appStrFlag = modalTitle.indexOf("Approval");
    var revBackStrFlag = modalTitle.indexOf("Back1");
    var signStrFlag = modalTitle.indexOf("Signing");
    var reviewStrFlag = modalTitle.indexOf("Back to Review");

    if (reviewStrFlag !== -1) {
        nextAppStatus = 'review';
    }


    // alert(reviewStrFlag);return;

    var formData = new FormData(this);
    formData.append('id', contract_id);
    formData.append('nextAppStatus', nextAppStatus);
    formData.append('curAppStatus', curAppStatus);
    formData.append('userInputVal', approveVal);
    formData.append('ReviewDescription', ReviewDescription);
    formData.append('shortDescrip', shortDescrip);
    formData.append('appRowId', appRowId);


    if (negoStrFlag !== -1) {
        negotiation(formData, curAppStatus);
    } else if (revBackStrFlag !== -1) {
        rejectProcess(formData, curAppStatus)
    } else if (signStrFlag !== -1) {
        signProcess(formData, curAppStatus)
    } else {

        $.ajax({
            url: base_url + 'sendContractForReview', // Update with your route
            type: 'POST',
            // data: { "id": contract_id, "nextAppStatus": nextAppStatus, "curAppStatus": curAppStatus, 
            //     "userInputVal": approveVal,"ReviewDescription":ReviewDescription, "shortDescrip":shortDescrip,
            //     "appRowId":appRowId
            // },
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
                        text: 'Something Went Wrong',
                        customClass: {
                            confirmButton: 'btn btn-danger waves-effect waves-light'
                        }
                    });
                    // location.reload();
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

    var url = window.location.href;
    var last = url.lastIndexOf('/') + 1;
    var base_url = url.substring(0, last);

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
                    text: 'Something Went Wrong',
                    customClass: {
                        confirmButton: 'btn btn-danger waves-effect waves-light'
                    }
                });
                // location.reload();
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
                    text: 'Something Went Wrong',
                    customClass: {
                        confirmButton: 'btn btn-danger waves-effect waves-light'
                    }
                });
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
                    text: 'Something Went Wrong',
                    customClass: {
                        confirmButton: 'btn btn-danger waves-effect waves-light'
                    }
                });
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
        }
    });
}




$(document).ready(function () {


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

        }
        if ($(this).val() == 'new') {
            $('.showinedit').hide();
            $('.signing_date').hide();
        }
    })

    function updateContractName() {
        var selectedText = $('#contracttype').find(':selected').text();
        const d = new Date();
        let year = d.getFullYear();
        $('#contractName').val($.trim(selectedText));
    }

    // Custom fields flagged is_generic carry .groupby-generic and apply to every contract type.
    function groupbyFor(contTypeId) {
        return $('.groupby-' + contTypeId).add('.groupby-generic');
    }

    $('.groupby').hide();

    groupbyFor($('#contracttype').val()).show();

    $('#contracttype').on('change', function () {
        updateContractName();
        $('.groupby').hide();
        groupbyFor($(this).val()).show();

    });



    $('.typerenewal').on('change', function () {
        if ($(this).val() == 'manualRenewal') {
            $('.typerenewallable').text('Manual renewal date');
        }
        if ($(this).val() == 'automaticrenewal') {
            $('.typerenewallable').text('Auto renewal Date');
        }
    })



    // var url = window.location.href;
    // var last = url.lastIndexOf('/') + 1;
    // var base_url = url.substring(0, last);

    // var APP_URL = {!! json_encode(url('/')) !!};



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
                url: APP_URL + '/parties/contract-parties-add?by=ajax',
                type: 'POST', // Use the form's method attribute as the HTTP method
                data: $(form).serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {

                    var datacut = $('.popap').attr('data-cut');

                    var text = response.company_name;
                    var value = response.id;

                    $('.group-ry.gropuid' + datacut).find('.partyExternal').append(new Option(text, value)).val(value).trigger('change');

                    setTimeout(() => {
                        $('.btn-close').trigger('click');
                    }, 100);

                    $('#parties_form')[0].reset();
                },
                error: function (xhr, status, error) {
                    // Handle error
                    $('#response').html('<p>An error occurred: ' + error + '</p>');
                }
            });
        }
    });



    $('.attachment_group').on('change', function () {
        if ($(this).val() == 'takefromtemplate') {
            $('.custom-file').hide()
        } else {
            $('.custom-file').show()
        }
    })

    setTimeout(() => {

        if ($('.createcontractnew').length == 1) {
            // addMorePartis();
            // addMorePartis();
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

        var url = window.location.href;
        var last = url.lastIndexOf('/') + 1;
        var site_url = url.substring(0, last);

        let base_url = site_url.replace("/contracts/", "");


        $.ajax({
            url: APP_URL + '/contracts/create/parties?typ=jss',
            type: 'POST',
            data: {},
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $(".party-group").append(response);
                $('.partyc').each(function (index) {
                    $(this).html(index + 1);
                    $(this).closest('.group-ry').attr('id', 'rodid' + (index + 1));
                })

                $('.removerow').click(function () {
                    console.log('remove');
                    $(this).closest('.group-ry').remove();
                });

                $('.index').each(function (index) {
                    $(this).val(index);
                })

                $('.group-ry').each(function (index) {
                    $(this).addClass('gropuid' + index);
                    if (typeof select2 !== 'undefined') {
                        $(this).find('.contractname').select2();
                        $(this).find('.partycontracttype').select2();
                    }
                    $(this).find('.partyExternal').select2({
                        language: {
                            searching: function () {
                                return "Searching...";
                            },
                            noResults: function () {
                                console.log(index);
                                return 'No Party Name Found Click to create new   <button type="button" class="btn btn-primary cusocli" data-exdd="' + index + '" data-bs-toggle="modal" data-bs-target="#onboardHorizontalImageModal">           Create</button> ';
                            }
                        },
                        escapeMarkup: function (markup) {
                            return markup;
                        }
                    });

                });

                $('#rodid1 .contractname, #rodid1 .partyExternal').on('change', function () {
                    console.log('REST');
                });

                $('#rodid2 .contractname, #rodid2 .partyExternal').on('change', function () {
                    console.log('RES2 ');
                });

                $('.group-ry').each(function (index) {
                    $(this).find('.partygroup').attr('name', 'Partygroup[party][' +
                        index + '][mode]');
                    $(this).find('.contractname').attr('name', 'Partygroup[party][' +
                        index + '][internal_name]');
                    $(this).find('.partycontracttype').attr('name',
                        'Partygroup[party][' + index + '][location]');
                    $(this).find('.partyExternal').attr('name', 'Partygroup[party][' +
                        index + '][external_name]');
                    $(this).find('.index').attr('name', 'Partygroup[party][' + index +
                        '][index]');



                })

                $('.contractname').each(function (index) {
                    $(this).select2();
                })
                $('.partycontracttype').on('change', function () {
                    if ($(this).val() != null) {
                        $(this).closest('.col-sm-6').find('.address-list li').hide();
                        $(this).closest('.col-sm-6').find('.address-list li#' + $(this)
                            .val()).show();
                    }
                })
                $('.partyExternal').on('change', function () {
                    if ($(this).val() != null) {
                        $(this).closest('.col-sm-6').find('.external-address-list li')
                            .hide();
                        $(this).closest('.col-sm-6').find('.external-address-list li#' +
                            $(this).val()).show();
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
    })

    $(document).on('change', '.partygroup', function () {
        if ($(this).val() == 'External') {
            $(this).closest('.group-ry').find('.Internal').hide();
            $(this).closest('.group-ry').find('.External').show();
        }
        if ($(this).val() == 'Internal') {
            $(this).closest('.group-ry').find('.Internal').show();
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



//   $(document).ready(function () {
//             const users = {
//                 ApprovalStatus: 'Approval Required',
//                 ApprovalType: 'Sequential',
//                 Approvers: [
//                     { "id": "5", "name": "Test-5" },
//                     { "id": "4", "name": "newTest-4" },
//                     { "id": "2", "name": "sdfTest-2" },
//                     { "id": "1", "name": "kiTest-1" }
//                 ]
//             }

//             $(users.Approvers).each(function () {
//                 $('.users').append(html(this));
//             })

//             function html(approver) {
//                 return `<li>
//                             <span title="${approver.name}">${approver.name.charAt(0)}</span> 
//                         </li>`;
//             }
//         });








$('.contracttype').change(function () {
    var selectedOption = $(this).find('option:selected');

    var catid = selectedOption.data('catid');
    var detid = selectedOption.data('detid');


    $('#catgoeryType').val(detid).trigger('change');
    $('#DepartmentType').val(catid).trigger('change');

});



$(document).on('click', '.representative_delete_row', function (event) {

    $('.representative_row_' + $(this).attr('id')).remove();

})





$(document).on('click', '.cusocli', function (event) {
    $('.popap').attr('data-cut', $(this).attr('data-exdd'));
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
                                title: 'Updates Info',
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






$(document).on('change', '.myFile', function () {
    let file = $(this)[0].files[0];
    if (file) {
        $(this).addClass('disabled');

        let fileName = file.name;
        $('#fileList').append(`
            <tr class="file-item">
                <td>${fileName}</td>
                <td><button class="remove-file btn btn-danger btn-sm pull-right" data-file-name="${fileName}">Remove</button></td>
            </tr>
        `);
        $(this).parent('.mfiles').prepend('<input class="myFile" name="photos[]" type="file">');
    }
});




$(document).on('change', '.myFilenew', function () {
    let file = $(this)[0].files[0];
    if (file) {
        $(this).addClass('disabled');

        let fileName = file.name;
        $('#fileListnew').append(`
            <tr class="file-item">
                <td>${fileName}</td>
                <td><button class="remove-file btn btn-danger btn-sm pull-right" data-file-name="${fileName}">Remove</button></td>
            </tr>
        `);
        $(this).parent('.mfiles').prepend('<input class="myFilenew" name="photos[]" type="file">');
    }
});



$(document).on('click', '.remove-file', function (e) {
    e.preventDefault();

    let fileName = $(this).data('file-name');

    $(this).parent().parent().remove();
    $('.disabled').each(function () {
        if ($(this)[0].files[0].name === fileName) {
            $(this).remove();
        }
    });
});
