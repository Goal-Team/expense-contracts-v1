'use strict';

$(function () {
    
    
    $(document).on('click', '.remove-row', function(){
    
    // $('.remove-row').on('click', function() {
     
            
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
                url: APP_URL + '/contract-setup/contract-type/delete/'+ dataId, 
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
    
    
  var dt_ajax_table = $('.datatables-ajax'),
    dt_filter_table = $('.dt-column-search'),
    dt_adv_filter_table = $('.dt-advanced-search'),
    dt_row_grouping_table = $('.dt-row-grouping'),
    dt_responsive_table = $('.dt-responsive'),
    startDateEle = $('.start_date'),
    endDateEle = $('.end_date');

  // Advanced Search Functions Starts
  // --------------------------------------------------------------------

  // Datepicker for advanced filter
  var rangePickr = $('.flatpickr-range'),
    dateFormat = 'MM/DD/YYYY';

  if (rangePickr.length) {
    rangePickr.flatpickr({
      mode: 'range',
      dateFormat: 'm/d/Y',
      orientation: isRtl ? 'auto right' : 'auto left',
      locale: {
        format: dateFormat
      },
      onClose: function (selectedDates, dateStr, instance) {
        var startDate = '',
          endDate = new Date();
        if (selectedDates[0] != undefined) {
          startDate = moment(selectedDates[0]).format('MM/DD/YYYY');
          startDateEle.val(startDate);
        }
        if (selectedDates[1] != undefined) {
          endDate = moment(selectedDates[1]).format('MM/DD/YYYY');
          endDateEle.val(endDate);
        }
        $(rangePickr).trigger('change').trigger('keyup');
      }
    });
  }
  
  $(document).ready(function() {


        // $(document).on('click', '.repeater .btn-delete', e => {
        //   if (confirm("Are you sure you want to delete this element?")) {
        //     $(e.target).closest('.repeater').slideUp(400, function() { $(this).remove() });
        //   }
        // });
        
        $('.users,#location,#category,#contract_type,#department').select2();
           $('.user_row_operation a:first').remove();
           $('.user_row_operation').first().prepend('<a class="btn-success user_add_row"  data-mode="no_approve" style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-plus me-1"></i></a>');
      

        $(document).on('click', '.repeater .btn-delete', e => {
          if (confirm("Are you sure you want to delete this element?")) {
            $(e.target).closest('.repeater').slideUp(400, function() { $(this).remove() });
          }
        });
        
        $('.user_add_row').click(function (event) {  
          //alert("user_add_row22")
          var index = parseInt($('#user_position').val())+1;
          var mode =  $(this).data('mode');
          //alert(mode);
          $.ajax({
                  headers: {
                              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                          },
                  url: APP_URL + "/contract-setup/financial-add-users/"+index,
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

  // Filter column wise function
  function filterColumn(i, val) {
    if (i == 5) {
      var startDate = startDateEle.val(),
        endDate = endDateEle.val();
      if (startDate !== '' && endDate !== '') {
        $.fn.dataTableExt.afnFiltering.length = 0; // Reset datatable filter
        dt_adv_filter_table.dataTable().fnDraw(); // Draw table after filter
        filterByDate(i, startDate, endDate); // We call our filter function
      }
      dt_adv_filter_table.dataTable().fnDraw();
    } else {
      dt_adv_filter_table.DataTable().column(i).search(val, false, true).draw();
    }
  }

  // Advance filter function
  // We pass the column location, the start date, and the end date
  $.fn.dataTableExt.afnFiltering.length = 0;
  var filterByDate = function (column, startDate, endDate) {
    // Custom filter syntax requires pushing the new filter to the global filter array
    $.fn.dataTableExt.afnFiltering.push(function (oSettings, aData, iDataIndex) {
      var rowDate = normalizeDate(aData[column]),
        start = normalizeDate(startDate),
        end = normalizeDate(endDate);

      // If our date from the row is between the start and end
      if (start <= rowDate && rowDate <= end) {
        return true;
      } else if (rowDate >= start && end === '' && start !== '') {
        return true;
      } else if (rowDate <= end && start === '' && end !== '') {
        return true;
      } else {
        return false;
      }
    });
  };

  // converts date strings to a Date object, then normalized into a YYYYMMMDD format (ex: 20131220). Makes comparing dates easier. ex: 20131220 > 20121220
  var normalizeDate = function (dateString) {
    var date = new Date(dateString);
    var normalized =
      date.getFullYear() + '' + ('0' + (date.getMonth() + 1)).slice(-2) + '' + ('0' + date.getDate()).slice(-2);
    return normalized;
  };
  // Advanced Search Functions Ends

  // Ajax Sourced Server-side
  // --------------------------------------------------------------------

  if (dt_ajax_table.length) {
    var dt_ajax = dt_ajax_table.dataTable({
      processing: true,
      ajax: assetsPath + 'json/ajax.php',
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
    });
  }

  // Column Search
  // --------------------------------------------------------------------

  if (dt_filter_table.length) {
      
    // Setup - add a text input to each footer cell
    // $('.dt-column-search thead tr').clone(true).appendTo('.dt-column-search thead');
    $('.dt-column-search thead tr:eq(1) th').each(function (i) {
      var title = $(this).text();
      var $input = $('<input type="text" class="form-control" placeholder="Search ' + title + '" />');

      // Add left and right border styles to the parent element
      $(this).css('border-left', 'none');
      if (i === $('.dt-column-search thead tr:eq(1) th').length - 1) {
        $(this).css('border-right', 'none');
      }

      $(this).html($input);

      $('input', this).on('keyup change', function () {
        if (dt_filter.column(i).search() !== this.value) {
          dt_filter.column(i).search(this.value).draw();
        }
      });
    });
    
    var url = window.location.href;
    var last = url.lastIndexOf('/') +1;
    var base_url =  url.substring(0,last);

    var dt_filter = dt_filter_table.DataTable({
        "initComplete": function(dtu) {
            let countData = dtu.json.count_data;
            for(let data_ in countData){
                $(`#count_${data_}`).html(countData[data_]);
            }
        },
      ajax: base_url+'listdata',
      columns: [
        { data: 'contract_type_id',   render : function ( data, type, row, meta ) 
           {
               var rowIndex = meta.row + 1; // Adding 1 to start index from 1 instead of 0
               return rowIndex;
           }},
        { data: 'contract_type'},
        { data: 'category_name' },
        { data: 'business_name' },
        { data: 'short_name' },
        { data: 'status',   render : function ( data, type, row, meta ) 
           {
               if(row.status == 1){
                   return "<span class='badge bg-success'>Active</span>";
               }else{
                   return "<span class='badge bg-danger'>Inactive</span>";
               }
           }},
        {
            data: null,
          //className: 'dt-center editor-edit',
            //defaultContent: '',
            orderable: false,
            render : function ( data, type, row ) 
          {

            var invoice_id = data.contract_type_id;
                var button2 ='<div class="d-inline-block">' +
          '<a href="#' + data.contract_type_id+'" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></a>' +
          '<div class="dropdown-menu dropdown-menu-end m-0">' +
          '<a href="'+ APP_URL + "/contract-setup/contract-type-edit/" + data.contract_type_id+'" class="dropdown-item">Edit</a>' +
          '<div class="dropdown-divider"></div>' +
          '<a href="'+ APP_URL + "/contract-setup/contract-type-delete/" + data.contract_type_id+'"  class="dropdown-item text-danger remove-row">Delete</a>' +
          '</div>' +
          '</div>';
            
             return button2;
          }
        }
      ],
      'columnDefs': [
           {
              'targets': 0,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('scope', 'row'); 
                 $(td).attr('data-label', 'ID'); 
              }
           },
           {
              'targets': 1,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'CONTRACT TYPE'); 
              }
           },
           {
              'targets': 2,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'DEPARTMENT'); 
              }
           },
           {
              'targets': 3,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'CATEGORY'); 
              }
           },
           {
              'targets': 4,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'SHORT NAME'); 
              }
           },
           {
              'targets': 5,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'STATUS'); 
              }
           },
           {
              'targets': 6,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'Actions'); 
              }
           }
           
        ],
        dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-primary dropdown-toggle me-2 waves-effect waves-light',
          text: '<i class="ti ti-file-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
          buttons: [
            {
              extend: 'print',
              text: '<i class="ti ti-printer me-1" ></i>Print',
              className: 'dropdown-item',
              exportOptions: {
                columns: [3, 4, 5, 6, 7],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              },
              customize: function (win) {
                //customize print view for dark
                $(win.document.body)
                  .css('color', config.colors.headingColor)
                  .css('border-color', config.colors.borderColor)
                  .css('background-color', config.colors.bodyBg);
                $(win.document.body)
                  .find('table')
                  .addClass('compact')
                  .css('color', 'inherit')
                  .css('border-color', 'inherit')
                  .css('background-color', 'inherit');
              }
            },
            {
              extend: 'csv',
              text: '<i class="ti ti-file-text me-1" ></i>Csv',
              className: 'dropdown-item',
              exportOptions: {
                columns: [3, 4, 5, 6, 7],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'excel',
              text: '<i class="ti ti-file-spreadsheet me-1"></i>Excel',
              className: 'dropdown-item',
              exportOptions: {
                columns: [3, 4, 5, 6, 7],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'pdf',
              text: '<i class="ti ti-file-description me-1"></i>Pdf',
              className: 'dropdown-item',
              exportOptions: {
                columns: [3, 4, 5, 6, 7],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'copy',
              text: '<i class="ti ti-copy me-1" ></i>Copy',
              className: 'dropdown-item',
              exportOptions: {
                columns: [3, 4, 5, 6, 7],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            }
          ]
        },
        {
          text: '<i class="ti ti-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Add New Record</span>',
          className: 'create-new btn btn-primary waves-effect waves-light'
        }
      ],
      orderCellsTop: true,
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
    });
  }

  // Advanced Search
  // --------------------------------------------------------------------

  // Advanced Filter table

  // on key up from input field
  $('input.dt-input').on('keyup', function () {
    filterColumn($(this).attr('data-column'), $(this).val());
  });

  // Responsive Table
  // --------------------------------------------------------------------


  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 200);
});

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

        $('#representative_section').append('<hr style="margin-top: 15px;" class="representative_row_'+position+'"><div class="col-md-12 representative_row_'+position+'" style="text-align: right;"><a id="'+position+'" class="btn btn-danger representative_delete_row" onclick = "representative_delete_row('+position+')" style="font-size: 12px;"><i class="bx bx-minus-circle me-1"></i> Delete </a></div><div class="col-md-6 representative_row_'+position+'"><label for="representative_name" class="form-label required">Representative Name</label><input type="hidden"  name="representative['+count+'][representative_id]" value=""  /><input type="text" class="form-control"  name="representative['+count+'][representative_name]"  required /></div><div class="col-md-6 representative_row_'+position+'"><label for="representative_email" class="form-label required">Email ID</label><input type="email" class="form-control representative_email" onchange = "representative_email('+position+',this.value)"  id="email_'+position+'" name="representative['+count+'][representative_email]" required /><div class="invalid-feedback">Email is invalid</div></div><div class="col-md-6 representative_row_'+position+'"><label for="representative_designation" class="form-label required">Designation</label><input type="text" class="form-control" name="representative['+count+'][representative_designation]" required /></div><div class="col-md-3 representative_row_'+position+'"><label for="representative_contact" class="form-label required">Contact Number</label><input type="text" class="form-control numberonly" name="representative['+count+'][representative_contact]"  maxlength="10" required /></div><div class="col-md-3 representative_row_'+position+'"><label for="representative_nationality" class="form-label">Nationality</label><input type="text" class="form-control" name="representative['+count+'][representative_nationality]" /></div>');
        $('#position').val(position);
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
                url: "/getState", // if you say $(this) here it will refer to the ajax call not $('#country')
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
                
                $('.add_users').append('<input type="hidden" id="user_position" value="0" />');
                //user_add_row('no_auto')
                var index = parseInt($('#user_position').val())+1;
              $.ajax({
                    headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                    url: APP_URL + "/contract-setup/financial-add-users/"+index,
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
        // alert("user_add_row");
        var index = parseInt($('#user_position').val())+1;
        var mode =  $(this).data('mode');
          $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: APP_URL + "/contract-setup/financial-add-users/"+index,
                type : "GET",
                dataType: "html",
                success:function(data) {
                    // console.log('data',data);
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
                            // get_users($users,1)
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