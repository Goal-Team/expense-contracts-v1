'use strict';

$(function () {
    
    $(document).on('click', '.remove-row', function(){
      let dataId = $(this).data('id');    
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
                    url: APP_URL + '/parties/contract-parties-delete/'+ dataId, 
                    type: 'get', 
                    success: function(response) {
                      if(response.success){
                       Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Your record has been deleted.',
                            customClass: {
                              confirmButton: 'btn btn-success waves-effect waves-light'
                            }
                       });
          
                       location.reload();
                      }else{
                       Swal.fire({
                            icon: 'warning',
                            title: 'Oops!',
                            text: 'Unable to delete this record',
                            customClass: {
                              confirmButton: 'btn btn-success waves-effect waves-light'
                            }
                       });                          
                      }
    
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
  
    $(document).on("click", ".loadstatus", function () {
      let statusName = $(this).data('stat');
      _setCookie('pfilterStatus', statusName);
      window.location.reload();
    });
    
     if(_getCookie('pfilterStatus')){
        $(`#status_${_getCookie('pfilterStatus')}`).addClass("act");
        $("#fstatus").val(_getCookie('pfilterStatus'));
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

    var dt_filter = dt_filter_table.DataTable({
        "initComplete": function(dtu) {
            let countData = dtu.json.count_data;
            for(let data_ in countData){
                $(`#count_${data_}`).html(countData[data_]);
            }
        },
      ajax: APP_URL + '/parties/data?status=' + (_getCookie('pfilterStatus') ?? $('#fstatus').val()),
      columns: [
        { data: 'id',   render : function ( data, type, row, meta ) 
           {
               var rowIndex = meta.row + 1; // Adding 1 to start index from 1 instead of 0
               return rowIndex;
           }},
           {
            data: null,
            orderable: false,
            render : function ( data, type, row ) 
           {
            var button = '<a href="' + APP_URL + '/parties/contract-parties-org-view/' + data.id+'" class="btn btn-sm btn-icon dropdown-toggle hide-arrow text-body" data-bs-toggle="tooltip" title="Preview"><i class="ti ti-eye mx-2 ti-sm"></i></a>';

            var invoice_id = data.id;

        var button2 ='<div class="d-inline-block">' +
          '<a href=' + APP_URL + '"/parties/contract-parties-org-edit' + data.id+'" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></a>' +
          '<div class="dropdown-menu dropdown-menu-end m-0">' +
          '<a href="' + APP_URL +'/parties/contract-parties-org-edit/' + data.id+'" class="dropdown-item">Edit</a>' +
          '<div class="dropdown-divider"></div>' +
          '<a href="#"  data-id="' + data.id+'"  class="dropdown-item text-danger remove-row">Delete</a>' +
          '</div>' +
          '</div>';
            
             return button+' '+button2;
           }
        },
        { data: 'company_name'},
        { data: 'party_type'},
        { data: 'city' },
        { data: 'company_contact' },
        { data: 'company_email' },
        { data: 'vendor_code' },
        { data: 'active_vendor_code' },
        { data: 'legal_entity' },
        { data: 'role_in_contract' },
        { data: 'engagement_level',   render : function ( data, type, row, meta ) 
           {
               if(row.engagement_level == 1){
                   return "Branch";
               }else{
                   return "Access Level";
               }
           }},
        { data: 'status',   render : function ( data, type, row, meta ) 
           {
               if(row.status == 1){
                   return "<span class='badge bg-success'>Active</span>";
               }else{
                   return "<span class='badge bg-danger'>Inactive</span>";
               }
           }}
        
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
                 $(td).attr('data-label', 'NAME'); 
              }
           },
           {
              'targets': 2,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'TYPE'); 
              }
           },
           {
              'targets': 3,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'ADDRESS'); 
              }
           },
           {
              'targets': 4,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'PHONE'); 
              }
           },
           {
              'targets': 5,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'EMAIL');
              }
           },
           {
              'targets': 6,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'VENDOR CODE');
              }
           },
           {
              'targets': 7,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'ACTIVE VENDOR CODE');
              }
           },
           {
              'targets': 8,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'LEGAL ENTITY');
              }
           },
           {
              'targets': 9,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'ROLE IN CONTRACT');
              }
           },
           {
              'targets': 10,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'ENGAGEMENT LEVEL');
              }
           },
           {
              'targets': 11,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'STATUS');
              }
           }
        ],
        dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        buttons: [
            {
              extend: 'print',
              text: 'Print',
              exportOptions: {
                columns: [3, 4, 5, 6, 7],
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
              text: 'Csv',
              exportOptions: {
                columns: [3, 4, 5, 6, 7],
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
              text: 'Excel',
              exportOptions: {
                columns: [3, 4, 5, 6, 7],
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
              text: 'Pdf',
              exportOptions: {
                columns: [3, 4, 5, 6, 7],
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
              text: 'Copy',
              exportOptions: {
                columns: [3, 4, 5, 6, 7],
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
        ],
      orderCellsTop: true,
    });

    var columnNames = ['S.No.', 'Action', 'Name', 'Type', 'Address', 'Phone', 'Email', 'Vendor Code', 'Active Vendor Code', 'Legal Entity', 'Role In Contract', 'Engagement Level', 'Status'];
    var hiddenByDefault = [7, 8, 9, 10];
    hiddenByDefault.forEach(function(i) {
      dt_filter.column(i).visible(false);
    });

    var $columnsMenu = $('#columnsDropdownMenu');
    columnNames.forEach(function(name, i) {
      var checked = dt_filter.column(i).visible() ? 'checked' : '';
      var $li = $('<li><label class="dropdown-item"><input type="checkbox" ' + checked + ' data-col="' + i + '"> ' + name + '</label></li>');
      $columnsMenu.append($li);
    });
    $columnsMenu.on('change', 'input[type="checkbox"]', function() {
      var colIdx = $(this).data('col');
      dt_filter.column(colIdx).visible($(this).is(':checked'));
    });

    $('.dt-export-btn').on('click', function(e) {
      e.preventDefault();
      var format = $(this).data('format');
      var btnMap = { print: 0, csv: 1, excel: 2, pdf: 3, copy: 4 };
      var idx = btnMap[format];
      if (idx !== undefined) {
        $('.dt-button').eq(idx).trigger('click');
      }
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