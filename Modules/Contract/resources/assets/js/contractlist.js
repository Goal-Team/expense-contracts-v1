/**
 * DataTables Advanced (jquery)
 */

'use strict';

$(function () {
  var contractListParams = new URLSearchParams(window.location.search);
  var partyIdFilter = contractListParams.get('party_id') || 0;
    
    $(".select2").select2();
    
    $("#contractlocs").select2({
        placeholder: "Choose Location/Branch",
        allowClear: true
    });
    
    $("#contracttype").select2({
        placeholder: "Choose Type/Group",
        allowClear: true
    });
    
    $("#contractcates").select2({
        placeholder: "Choose Category",
        allowClear: true
    });   
    
    $("#contractstats").select2({
        placeholder: "Choose Status",
        allowClear: true
    });    
    
    $(document).on('click', '.remove-row', function(){
    
    // $('.remove-row').on('click', function() {
     
            
    var dataId = $(this).attr('data-id');
    
    var url = window.location.href;
    var last = url.lastIndexOf('/') +1;
    var base_url =  url.substring(0,last);


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
                url: APP_URL+'/contracts/delete/'+ dataId, 
                type: 'get', 
                success: function(response) {
                   Swal.fire({
        icon: response.errClass,
        title: 'Alert!',
        text: response.message,
        customClass: {
          confirmButton: 'btn btn-primary waves-effect waves-light'
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
  
  
  // ================= termination action ====================//


$(document).on("click", ".open-terminationModal", function () {
  var conId = $(this).data('id');
  $("#conId").val( conId );
  $('#terminationReason').val('');
  $("#basicModal").modal('show');
});


// ==================== Update Action ===========================//
  
  // ==================== Update Action ===========================//

$(document).on('click', '#btnTermination', function(e){

  e.preventDefault(); // Prevent the default form submission
  var token = "{{ csrf_token() }}";

  var dataId = $("#conId").attr('value');

  // Gather form data
  var terminationReason = $('#terminationReason').val();
    $.ajax({
        headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
      url: base_url + 'contracts/terminateContract', // Update with your route
      type: 'POST',
      data: {terminationReason:terminationReason,contract_id:dataId},
      success: function (response) {
         alert(response.message);
         $("#basicModal").modal('hide');

      },
      error: function (xhr, status, error) {
          // Handle error
          alert('Form submission failed: ' + error);
      }
  });
});

// ================= termination action ends ====================//


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
    drawCallback: function (settings) {
          if(settings.json?.counts){
              // console.log(settings.json.counts);
              for(var curVal in settings.json.counts){
                $(`#status_${curVal}`).find('.count-disp').text(settings.json.counts[curVal]);
              }
              
          }
    },
      "initComplete": function(settings, json) {
        //   this.api().columns([2,3,4,7,8]).every( function () {
        //         var column = this;
        //         var select = $('<select class="select2 form-select filterColumn_'+ column[0] +'" style="cursor: pointer;"><option value="">Select All</option></select>')
        //         .appendTo(  $('thead tr:eq(0) th:eq(' + this.index()  + ')') )
        //             .on( 'change', function () {
        //                 var val = $.fn.dataTable.util.escapeRegex(
        //                     $(this).val()
        //                 );
 
        //                 if(column.length > 0){
        //                     //For Settting History Filter behalf column filter
        //                     if(!_getCookie('filterSet')){
        //                         //console.log(_getCookie('filterSet'));
        //                         let columnKey = 'filterColumn_'+ column[0];
        //                         let filtersSetData = {};
        //                          filtersSetData['filterColumn_'+ column[0]] = $(this).val();
        //                         _setCookie('filterApplied', true, 1);
        //                         _setCookie('filterSet', JSON.stringify(filtersSetData), 1);
        //                     }else{
        //                         let getFilterSet = _getCookie('filterSet') ?? '[]';
        //                         let filtersSetData = JSON.parse(getFilterSet);
        //                         filtersSetData['filterColumn_'+ column[0]] = $(this).val();
        //                         _setCookie('filterSet', JSON.stringify(filtersSetData), 1);
        //                         //console.log(filtersSetData);
        //                     }
        //                 }
        //                 column
        //                     .search( val ? '^'+val+'$' : '', true, false )
        //                     .draw();
        //             } );
 
        //         column.data().unique().sort().each( function ( d, j ) {
        //             let selected = '';
        //             let selVal = '';
        //             if(_getCookie('filterApplied') === 'true'){
        //                 if(_getCookie('filterSet')){
        //                     let allFilters = JSON.parse(_getCookie('filterSet'));
        //                     if((allFilters['filterColumn_'+ column[0]]?.replace(/\/\//g, "/")) == d){
        //                         selected = "selected";
        //                         selVal = d;
        //                         column.search(selVal).draw()
        //                     }
        //                 }
        //             }                    
        //             select.append( '<option value="'+d+'" '+selected+'>'+d+'</option>' );
        //         } );
        //     } );
        },
      // The server pages, filters, sorts and searches now (Laravel side:
      // ContractController::listContractData + App\Support\ServerSideDataTable).
      // Only one page of rows crosses the wire per draw.
      serverSide: true,
      ajax: {
          'url': APP_URL + '/contracts/data',
          // A function, not an object, so the cookies are read again on every
          // draw instead of once when the table is built.
          data: function (d) {
             d.status = _getCookie('filterStatus') ?? $('#status').val();
             d.userData = _getCookie('myFilterStatus') ?? 0;
             d.contype = _getCookie('filterConType') ?? 0;
             d.locations = _getCookie('filterConLoc') ?? 0;
             d.party_id = partyIdFilter;
          },
          'method': 'post',
            headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
      },
      "ordering": true,
      processing: true,
      columns: [
        { data: 'id',   render : function ( data, type, row, meta)
           {
               // Row number keeps counting across pages.
               var rowIndex = meta.settings._iDisplayStart + meta.row + 1;
               return rowIndex;
           }},
        { data: 'contract_name'},
        { data: 'location_branch'},
        { data: 'contract_type' },
        { data: 'catgoery_id' },
        { data: 'fixed_date' },
        { data: 'contract_end_date' },
        { data: 'contract_priority' },
        { data: 'substatus' },
        { data: 'currency_value_converted' },
        { data: 'contract_attachment_filename' },
        {
            data: null,
           //className: 'dt-center editor-edit',
            //defaultContent: '',
            orderable: false,
            render : function ( data, type, row ) 
           {
               
            //For Settting History Filter behalf status
            let columnKey = 'status';
            if(!_getCookie('filterStatus')){
                _setCookie('filterApplied', true, 1);
                _setCookie('filterStatus', $('#status').val(), 1);
            }
            
            var button = '<a href="' + APP_URL +'/contracts/' + data.id+'?tab=details" class="btn btn-sm btn-icon dropdown-toggle hide-arrow text-body" data-bs-toggle="tooltip" title="Preview"><i class="ti ti-eye mx-2 ti-sm"></i></a>';

            
            let fixedTermAction = '';
            if(data.end_contract_type == 'fixedTerm' && data.contract_status == 'executed'){
              fixedTermAction = '<div class="dropdown-divider"></div> <a href="' + APP_URL +'/contracts/renew/' + data.id+'" class="dropdown-item text-warning">Initiate Renewal/Addendum</a>';
            }
            let terminationAction = '';
            if(data.contract_status == 'executed'){
              terminationAction = '<div class="dropdown-divider"></div><a href="' + APP_URL +'/contracts/terminate/' + data.id+'"  class="dropdown-item text-info">Terminate</a>';
            }
                var button2 ='<div class="d-inline-block">' +
          '<a href="javascript:;" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></a>' +
          '<div class="dropdown-menu dropdown-menu-end m-0">' +
          '<a href="' + APP_URL+'/contracts/' + data.id+'?tab=edit" class="dropdown-item">Edit</a>' +
          '<div class="dropdown-divider"></div>' +
        //   '<a href="/financial-delete" onclick=parties_delete(' + data.id+',"/contractsdemo/contract-setup/financial-delete") id="pid_' + data.id+'"  data-id="' + data.id+'"  class="dropdown-item text-danger delete-record">Delete</a>' +
          '<a href="#" data-id="' + data.id+'"  class="dropdown-item text-danger remove-row">Delete</a>'  +
          terminationAction +
           fixedTermAction +
          // '<a href="#" data-id="' + data.id+'"  class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#basicModal">Terminate</a>' +
          '</div>' +
          '</div>';
            
             return button+' '+button2;
           }
        },
        { data: 'currency_value' },
      ],
      'columnDefs': [
           {
              'targets': 0,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('scope', 'row'); 
                 $(td).attr('data-label', 'ID');
              },
              "orderable": false
           },
           {
                "targets": 1,
                'createdCell':  function (td, cellData, rowData, row, col) {
                     $(td).attr('data-label', 'Contract Name'); 
                  },
                "render": function (data, type, row, meta) {

                    if (type === 'display') {
                        let linkUrl = `${APP_URL}/contracts/${row['id']}`;
                        return `<a href="${linkUrl}?tab=details" class="custom-ta">${data}
                            </a>
                            <div class="d-flex">
                            <a href="${linkUrl}?tab=details" class="btn p-1 btn-label-secondary me-1" title="View Contract">
                                <i class="ti ti-eye text-warning ti-xs"></i>
                            </a>
                            <a href="${linkUrl}?tab=edit" class="btn p-1 btn-label-secondary me-1" title="Edit Contract">
                                <i class="ti ti-file-pencil text-warning ti-xs"></i>
                            </a>
                            <a href="${linkUrl}?tab=timeline" class="btn p-1 btn-label-secondary me-1" title="Go Approval">
                                <i class="ti ti-file-time text-success ti-xs"></i>
                            </a>
                            <a href="${linkUrl}?tab=history" class="btn p-1 btn-label-secondary" title="Show History">
                                <i class="ti ti-history text-primary ti-xs"></i>
                            </a>
                            </div>
                            `;
                    } else {
                        return data; // Return the original data for filtering
                    }
                },
              "orderable": false
           },
           {
              'targets': 2,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'Location'); 
              },
              "orderable": false
           },
          {
              'targets': 3,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'Contract Type'); 
              },
              "orderable": false
          },
           {
              'targets': 5,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'Fixed Date'); 
              },
              "orderable": false
           },
           {
              'targets': 6,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'End Date'); 
              }
           },
           {
              'targets': 7,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'Priority'); 
              },
                "render": function (data, type, row, meta) {

                    if (type === 'display') {
                        return capitalizeFirstLetter(data);
                    } else {
                        return data;
                    }
                }, 
                "orderable": false
           },
           {
              'targets': 8,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'Status'); 
              },
                "render": function (data, type, row, meta) {
                    
                    // console.log(data);

                    if (type === 'display') {
                        if(data == 'completed'){
                            return '<div class="status-completed substatusText" data-count-id="status_executed_completed" data-count-exe="1">' + data + '</div>';
                        }else if(data == 'active'){
                            return '<div class="status-active substatusText" data-count-id="status_executed_active" data-count-exe="1">' + data + '</div>';
                        }else if(data == 'expired'){
                            return '<div class="status-expired substatusText" data-count-id="status_executed_expired" data-count-exe="1">' + data + '</div>';
                        }else if(data == 'Terminated'){
                            return '<div class="status-terminate substatusText" data-count-id="status_executed_terminated" data-count-exe="1">' + data + '</div>';
                        }else if(data == 'renewed'){
                            return '<div class="status-renewed substatusText" data-count-id="status_executed_renewed" data-count-exe="1">' + data + '</div>';
                        }else if(row.contract_status == 'Negotiation'){
                            return '<div class="status-negotiation substatusText" data-count-id="status_negotiation" data-count-exe="0">' + row.contract_status + '</div>';
                        }
                        else if(data == 'Initial Draft'){
                            return '<div class="status-initialdraft substatusText" data-count-id="status_'+ (row.contract_status).toLowerCase() +'" data-count-exe="0">' + row.contract_status + '</div>';
                        }  else if(data.toLowerCase() == 'pending approval'){
                            return '<div class="status-renewed substatusText" data-count-id="status_'+ (row.contract_status).toLowerCase() +'" data-count-exe="0">' + data + '</div>';
                        }  
                        else if(data.toLowerCase() == 'under process' || data.toLowerCase() == 'review'){
                            return '<div class="status-renewed substatusText" data-count-id="status_review" data-count-exe="0">' + data + '</div>';
                        }
                        else {
                            return '<div class="status-renewed substatusText" data-count-id="status_executed_renewed" data-count-exe="1">' + data + '</div>';
                        }
                        
                        // return '<a href="' + base_url+'contracts/' + row['id'] + '" class="custom-tag">' + data +
                        //     '</a>';
                    } else {
                        return data; // Return the original data for filtering
                    }
                },
              "orderable": false
           },
           
            {
              'targets': 4,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'Category'); 
              },
              "orderable": false
          },
           {
              'targets': 9,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'Value'); 
              },
                visible: false ,
                "orderData": [ 12 ]
           },
           {
              'targets': 11,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'Actions'); 
              }
           },
           {
              'targets': 12,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'ORG Value'); 
              },
              visible: false
           },
           {
              'targets': 10,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'Attachment'); 
              },
              "render": function (data, type, row, meta) {
            
                if (type === 'display') {
                    return '<a href="' + APP_URL +'/contracts/' + row['id'] + '?tab=attachment" class="custom-ta">View Document</a>';
                } else {
                    return data; // Return the original data for filtering
                }
              },
              "orderable": false,              
              visible: false
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
                    return (result != 'null' ? result : '-');
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
    
    dt_filter.on('search.dt', function() {
        recount_status_(dt_filter.rows( { filter : 'applied'} ).data(), dt_filter.rows( { filter : 'applied'} ).nodes().length);
    });
    
    
    $(document).on("change", ".filterContractList", function () {
        //For Settting History Filter behalf column filter
        $('.filterContractList').each(function (i) {
            var filterVals = $(this).val();
            var filterName = $(this).attr('id');
            if(!_getCookie('filterSet')){
                let columnKey = filterName;
                let filtersSetData = {};
                 filtersSetData[columnKey] = filterVals;
                _setCookie('filterApplied', true, 1);
                _setCookie('filterSet', JSON.stringify(filtersSetData), 1);
            }else{
                let getFilterSet = _getCookie('filterSet') ?? '[]';
                let filtersSetData = JSON.parse(getFilterSet);
                let columnKey = filterName;
                filtersSetData[columnKey] = filterVals;
                _setCookie('filterSet', JSON.stringify(filtersSetData), 1);
            }
        });
        if($(this).attr('name') != 'contractstats'){
            dt_filter.ajax.reload();
        }else{
          _setCookie('filterStatus', $(this).val());
          window.location.href=`${APP_URL}/contracts/list`;            
        }
    });    
    
    $('#column-filter-table').on('change', function (e) {
        e.preventDefault();
 
        let columnIn = $(this).val();
        columnIn = columnIn.map(v => parseInt(v));
        columnIn.push(0);
        columnIn.push(11);
        let visibleCols = dt_filter.columns().visible();
        
        $.each(visibleCols, function( index, colIdx ) {
        
            var column = dt_filter.column( index );
            
            // Toggle the visibility
            if(columnIn.includes(index)){
                // Get the column API object
                column.visible( true );
            }else{
                column.visible( false );
            }
            
        });
    } );    
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
                $('.add_users').append('<input type="hidden" id="user_position" value="1" /><div class="col-md-6"><div class="row" style="" id=""><div class="col-md-6 select_users"><select class="form-select users" aria-label="select example" id="approval_required_users_1" name="approval_required_users[]" required><option value="">Select Approver</option></select> </div><div class="col-md-6 select_users_btn" style="text-align: center;"><a id="" class="btn-success user_add_row" onclick ="user_add_row()" style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-plus me-1"></i> </a></div></div></div><div class="col-md-6"></div>');
                $("#approval_required_users_1").prop("required", true);
                var $users = $("#approval_required_users_1");
                get_users($users,1)
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
    
      const wrapperScroll = document.getElementById('wrapper-scroll');
      const wrapperTable = document.querySelector('#table-scroll .table-responsive');
      wrapperScroll.onscroll = () => {
        wrapperTable.scrollLeft = wrapperScroll.scrollLeft;
      };
      wrapperTable.onscroll = () => {
        wrapperScroll.scrollLeft = wrapperTable.scrollLeft;
      };
      
    // ================= Onclick Status action ====================//
    
    
    $(document).on("click", ".loadstatus", function () {
      let statusName = $(this).data('stat');
      _setCookie('filterStatus', statusName);
      window.location.href=`${APP_URL}/contracts/list`;
    });
    $(document).on("click", "#clearMyActions", function () {
      _deleteCookie('myFilterStatus');
       _setCookie('filterStatus', 'all');
      window.location.href=`${APP_URL}/contracts/list`;
    });
    
    $(document).on("click", "#clearAllFilters", function () {
        _setCookie('filterStatus', 'all');
        _deleteCookie('filterApplied');
        _deleteCookie('filterSet');
        _deleteCookie('filterConLoc'); 
        _deleteCookie('filterConType');
        window.location.href=`${APP_URL}/contracts/list`;
    });

    $(document).on("click", "#clearAllActions", function () {
      let userName = $(this).data('user');
      _setCookie('myFilterStatus', userName);
      window.location.href=`${APP_URL}/contracts/list`;
    });
    
     if(_getCookie('filterStatus')){
        $(`#status_${_getCookie('filterStatus')}`).addClass("act");
        $("status").val(_getCookie('filterStatus'));
     }
    
    // ================= Onclick Status action ends ====================//      
       
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
    
    $('.user_delete_row').on("click",function () {
        
        user_delete_row();
    });
    
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
        
        var url = window.location.href;
        var last = url.lastIndexOf('/') +1;
        var base_url =  url.substring(0,last);

        if(location != '' && department != '' && category != '' && contract_type != '' && upper_limit_value != '' && lower_limit_value != '')
        {
            var myFormData =  $('#financial_form').serialize();
             //console.log('myFormData',myFormData);
             $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: APP_URL+"/contract-setup/check_limit", // if you say $(this) here it will refer to the ajax call not $('#country')
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
    

    $('.user_add_row').on("click",function () {
        
        user_add_row();
    });
    
    // $('.user_delete_row').on("click",function () {
        
    //     user_delete_row('+user_position+');
    // });
    
    function user_add_row()
    {
        var user_position = parseInt($('#user_position').val())+1;
        // $('.add_users').last().append('<div class="col-md-6 user_row_'+user_position+'"><div class="row" style="" id=""><div class="col-md-6 select_users" style="margin-top: 20px;"><select class="form-select users" aria-label="select example" id="users_'+user_position+'" name="approval_required_users[]"><option value="">Select Approver</option></select></div><div class="col-md-6 select_users_btn" style="margin-top: 20px;text-align: center;"><a id="" class="btn-danger user_delete_row" onclick = "user_delete_row('+user_position+')" style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-minus me-1"></i> </a></div></div></div><div class="col-md-6 user_row_'+user_position+'"></div>');
        $('.add_users').last().append('<div class="col-md-6 user_row_'+user_position+'"><div class="row" style="" id=""><div class="col-md-6 select_users" style="margin-top: 20px;"><select class="form-select users" aria-label="select example" id="users_'+user_position+'" name="approval_required_users[]"><option value="">Select Approver</option></select></div><div class="col-md-6 select_users_btn" style="margin-top: 20px;text-align: center;"><a id="" class="btn-danger user_delete_row" style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-minus me-1"></i> </a></div></div></div><div class="col-md-6 user_row_'+user_position+'"></div>');
        $('#users_'+user_position).select2();
        $('#user_position').val(user_position);
        var $users = $("#users_"+user_position);
        get_users($users,user_position)
    }
    
    // function user_delete_row(val)
    // {
    //     $('.user_row_'+val).remove();
    // }
    
    function get_users($users,user_position)
    {
        var url = window.location.href;
        var last = url.lastIndexOf('/') +1;
        var base_url =  url.substring(0,last);
        $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: APP_URL+"/contract-setup/getUsers", // if you say $(this) here it will refer to the ajax call not $('#country')
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
    
    function recount_status_(dataFiltered, dataFilteredLength){
        if(_getCookie('filterConLoc') || _getCookie('filterConType')){
            $('#clearAllFilters').removeClass('d-none');
        }
        
        if(_getCookie('filterApplied') === 'true' && _getCookie('filterSet')){
            $('#clearAllFilters').removeClass('d-none');
        }
    }
    
    function _setCookie(name, value, daysToExpire, path = '/', domain = '') {
    const cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)}`

    let expires = ''
    if (daysToExpire) {
      const expirationDate = new Date()
      expirationDate.setTime(expirationDate.getTime() + daysToExpire * 24 * 60 * 60 * 1000)
      expires = `; expires=${expirationDate.toUTCString()}`
    }

    const pathString = `; path=${path}`
    const domainString = domain ? `; domain=${domain}` : ''

    document.cookie = `${cookie}${expires}${pathString}${domainString}`
  }

  function _getCookie(name) {
    const cookies = document.cookie.split('; ')

    for (let i = 0; i < cookies.length; i++) {
      const [cookieName, cookieValue] = cookies[i].split('=')
      if (decodeURIComponent(cookieName) === name) {
        return decodeURIComponent(cookieValue)
      }
    }

    return null
  }

  function _checkCookie(name) {
    return this._getCookie(name) !== null
  }

  function _deleteCookie(name) {
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
  }
  
  function capitalizeFirstLetter(string) {
    return string?.charAt(0).toUpperCase() + string?.slice(1);
  }
