$(document).ready(function() {  
    var url = window.location.href;
    var last = url.lastIndexOf('/') +1;
    var base_url =  url.substring(0,last);
    var dt_filter_table = $('.dt-column-search');
    //console.log("base_url",base_url);
    
    
    //For Form Submit Disabled
    $('#financial_form .btn-buy-now').attr('disabled', 'disabled');
    
    $(document).on('change','#financial_form .form-control,.form-select' ,function (event) {
        disEnableButton();
    });    

    //@date:: 18 May 2024,  @author :: Mangaleswari, @desc:: allow numbers only
    $('.numberonly').keypress(function (e) {    
        var charCode = (e.which) ? e.which : event.keyCode
        if (String.fromCharCode(charCode).match(/[^0-9]/g))
            return false;
    });

    
     //$('.user-select').select2();
     
     $('.form-select').each(function(){
         $(this).select2();
     });
     
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
        "initComplete": function() {
        
        },
      ajax: APP_URL+'/contract-setup/party-approval/data',
      columns: [
        { data: 'id',   render : function ( data, type, row, meta) 
           {
               var rowIndex = meta.row + 1; // Adding 1 to start index from 1 instead of 0
               return rowIndex;
           }},
        { data: 'BranchName'},
        { data: 'geoname'},
        { data: 'approval_required_users',
             render : function ( data, type, row ) 
             {
              var  json_data = JSON.parse(data);
              //console.log(json_data);
                if(json_data.length == 0)
                {
                  return 'Auto Approver';
                }else
                {
                  var vals=[];
                    for(var i=0;i<json_data.length;i++){
                       vals.push(json_data[i].name+((json_data[i].type) == 'name' ? "("+ (json_data[i].email ?? '') +")" : ''));
                    }
                  return vals;
                }
             }
         },
        {
            data: null,
           //className: 'dt-center editor-edit',
            //defaultContent: '',
            orderable: false,
            render : function ( data, type, row ) 
           {
            var button = '<a href="' + APP_URL+'/contract-setup/party-approval-edit/' + data.id+'" class="btn btn-sm btn-icon dropdown-toggle hide-arrow text-body" data-bs-toggle="tooltip" title="Preview"><i class="ti ti-eye mx-2 ti-sm"></i></a>';


            
            
            
            var invoice_id = data.id;
            // var button2 ='<li class="list-inline-item"><a class="text-danger parties_delete" onclick=parties_delete(' + data.id+',"/financial-delete") id="pid_' + data.id+'"  data-id="' + data.id+'" data-url="/financial-delete" style="color: #cb1717;cursor: pointer;"><i class="text-primary ti ti-trash font-size-18"></i></a></li>';

                var button2 ='<div class="d-inline-block">' +
          '<a href="' + APP_URL +'/contract-setup/party-approval-edit/' + data.id+'" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></a>' +
          '<div class="dropdown-menu dropdown-menu-end m-0">' +
          '<a href="' + APP_URL + '/contract-setup/party-approval-edit/' + data.id+'" class="dropdown-item">Edit</a>' +
          '<div class="dropdown-divider"></div>' +
        //   '<a href="/contractsdemo/contract-setup/financial-delete/' + data.id+'" onclick=parties_delete(' + data.id+',"/contractsdemo/contract-setup/financial-delete") id="pid_' + data.id+'"  data-id="' + data.id+'"  class="dropdown-item text-danger parties_delete">Delete</a>' +
          '<a href="#"  data-id="' + data.id+'"  class="dropdown-item text-danger remove-row">Delete</a>' +
        //   '<a href="javascript:;" class="dropdown-item text-danger delete-record">Delete</a>' +
          '</div>' +
          '</div>';
            
             return button+' '+button2;
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
                 $(td).attr('data-label', 'Name'); 
              }
           },
           {
              'targets': 2,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'Location'); 
              }
           },
           {
              'targets': 4,
              'createdCell':  function (td, cellData, rowData, row, col) {
                 $(td).attr('data-label', 'Department'); 
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

    
    $(document).on('click','.applyApprRules', function(e) {
        let copyFrom = $(this).data('btn-type');
        let currentSel = $(`.defaultValSetter${copyFrom}`);
        let currentSelRows = $(`.users-${copyFrom} .repeater`);
        $(`.copyRulesFrom${copyFrom} .copyApprovers`).each(function(e, obj){
            if($(obj).is(':checked')){
                currentSel.each(function(e1, obj1){
                    if(($(obj1).is(':radio') && $(obj1).is(':checked')) || $(obj1).is('select')){
                        approvers_tab_selected(obj1, $(obj).data('row-type'));
                    }
                });
                
                //currentSelRows.each(function(e, objval){
                approvers_tab_approvers(currentSelRows, copyFrom, $(obj).data('row-type'));
                //});                
            }
        });
        
        $( document ).ajaxComplete(function() {
            $(`[data-btn-type="${copyFrom}"`).removeClass('btn-primary').addClass('btn-success').html('Applied');
            setTimeout(function() {
                $(`[data-btn-type="${copyFrom}"`).removeClass('btn-success').addClass('btn-primary').html('Apply');
            }, 2000);
        });        
        // if($(this).is(":checked")){
        //     $(`.defaultValSetter0`).each(function(e, obj){
        //         if(($(obj).is(':radio') && $(obj).is(':checked')) || $(obj).is('select')){
        //             approvers_tab(obj, '');
        //         }
        //     });
        // }else{
        //     // Swal.fire({
        //     //     title: 'Warning',
        //     //     text: 'Please Verify All Tab Datas Before Save It',
        //     //     icon: 'warning',
        //     //     customClass: {
        //     //       confirmButton: 'btn btn-success waves-effect waves-light'
        //     //     }
        //     //   }).then(function(result) {
        //     //     if (result.value) {
        //     //         $('.nav-link:not(.active)').attr('data-bs-toggle', 'tab');
        //     //     }
        //     //   });
        // }
    });
    
    $(document).on('change','.approval_user', function(e) {
        const curSel = $(this);
        const curVal = curSel.val();
        const curSelId = curSel.data('id');
        $('.approval_user').each(function(e_){
            if($(this).data('id') != curSelId){
                if(curVal == $(this).val()){
                    Swal.fire({
                        title: 'Already Choosed',
                        text: curVal.split(":")[1] + ' Already Selected Please choose some other',
                        icon: 'warning',
                        customClass: {
                          confirmButton: 'btn btn-success waves-effect waves-light'
                        }
                      });                    
                    curSel.val('').trigger('change');
                }
            }
        })
    });        

    //@date:: 24 May 2024,  @author :: Mangaleswari, @desc:: 
    $(".approval_status").on('change', function() {
        var id = $(this).attr('id');
        var rowAppType = $(this).data('row-type');
        if(id == 'auto')
        {
            $('.users-'+rowAppType).hide();
            //$("#approval_required_users_1").prop("required", false);
        }else
        {
            $('.users-'+rowAppType).show();
            var approval_status = $('#approval_status').val();
            var users = $('.add_users').html();
            //if(!$.trim( $('.add_users').html() ).length && (approval_status == 'auto') )  
            if(approval_status == 'required')
            {
                // $('.add_users').append('<input type="hidden" id="user_position" value="1" /><div class="col-md-6"><div class="row" style="" id=""><div class="col-md-6 select_users"><select class="form-select users" aria-label="select example" id="approval_required_users_1" name="approval_required_users[]" required><option value="">Select Approver</option></select> </div><div class="col-md-6 select_users_btn" style="text-align: center;"><a id="" class="btn-success user_add_row" onclick ="user_add_row()" style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-plus me-1"></i> </a></div></div></div><div class="col-md-6"></div>');
                $('.add_users').append('<input type="hidden" id="user_position" value="0" />');
                //user_add_row('no_auto')
                //var index = parseInt($('#user_position').val())+1;
                var mode =  $(this).data('mode');
                var tabType =  $(this).data('tab-type');
                var index = parseInt($('#user_position_'+tabType).val())+1;
                user_add_row(mode, tabType,index);
            }else
            {
                $("#approval_required_users_1").prop("required", true);
            }
        }
    });  

    //@date:: 05 Jun 2024,  @author :: Mangaleswari, @desc:: PAN validation
    $('#financial_form').submit(function (event) {  
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
    
    $(document).on('click','.user_add_row',function (event) {  
        
        if($('#sameAsNewApproval').is(":checked")){
            $(`.user_add_row`).each(function(e, obj){
                let mode =  $(obj).data('mode');
                let tabType =  $(obj).data('tab-type');
                let index = parseInt($('#user_position_'+tabType).val())+1;
                user_add_row(mode, tabType,index);
            });            
        }else{
            var mode =  $(this).data('mode');
            var tabType =  $(this).data('tab-type');
            var index = parseInt($('#user_position_'+tabType).val())+1;
            user_add_row(mode, tabType,index);
        }
       // alert("user_add_row");
    }); 
    
    $(document).on('click', '.repeater .btn-delete', function(e) {
        Swal.fire({
            title: 'Are you sure?',
            text: "you want to delete this element?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            customClass: {
                confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
                cancelButton: 'btn btn-label-secondary waves-effect waves-light'
            },
            buttonsStyling: false
        }).then(function(result) {
            if (result.value) {
                $(e.target).closest('.repeater').slideUp(400, function() {
                    $(this).remove();
                    if($('#sameAsNewApproval').is(":checked")){
                        let curDelRow = $(this).data('row-sel');
                        $(`.user_row_${curDelRow}`).remove();
                    }
                    disEnableButton();
                });
            }
        });

    }); 
    
    $(document).on('change', '.approval_user_type', function(e) {
        let curr_row = $(this).data('row-sel');
        let curr_row_app_type = $(this).data('row-type');
        $(`.users-${curr_row_app_type} .by_name_desg_${curr_row}`).addClass('d-none').attr('name', 'by_not_sel[]');
        $(`.users-${curr_row_app_type} .by_${$(this).val()}_${curr_row}`).removeClass('d-none').attr('name', 'approval_required_users[]');
    });
});

    function approvers_tab(thisElm, currTab=""){
        //if($('#sameAsNewApproval').is(":checked")){
            const curSel = $(thisElm);
            let curVal = curSel.val();
            const curRowType = curSel.data('row-type');
            const curRowInpt = curSel.data('row-inpt');
            $(`.${curRowInpt}`).each(function(e, obj){
                if($(obj).data('row-type') != currTab){
                    if($(obj).is(':radio')){
                        $(`[value="${curVal}"]:not(.defaultValSetter0)`).trigger('click');
                    }else{
                        $(this).val(curVal).trigger('change.select2');
                    }
                }
            });
        //}        
    }
    
    function approvers_tab_selected(copyFrom, copyTo){
        const curSel = $(copyFrom);
        let curVal = curSel.val();
        const curRowType = curSel.data('row-type');
        const curRowInpt = curSel.data('row-inpt');
        $(`.defaultValSetter${copyTo}.${curRowInpt}`).each(function(e, obj){
            if($(obj).data('row-type') != curRowType){
                if($(obj).is(':radio')){
                    if($(obj).attr('value') == curVal){
                        $(obj).trigger('click');
                    }
                }else{
                    $(this).val(curVal).trigger('change.select2');
                }
            }
        });
    }    
    
    async function approvers_tab_approvers(thisElm, fromTab, toTab){
        //if($('#sameAsNewApproval').is(":checked")){
            const curSel = $(thisElm);
            let defVal = {};
            curSel.each(function(e,obj){
                let curRowSel = $(obj).data('row-sel');
                let index = parseInt($(`#user_position_${toTab}`).val());
                $(obj).find(`.defaultValRowSetter${fromTab}`).each(function(e1, valset){
                    //let curRowType = $(valset).data('row-type');
                    let curRowInpt = $(valset).data('row-inpt');
                    let curVal = $(valset).val();
                    defVal[curRowInpt] = curVal == "" ? false : curVal;
                    if(curRowSel == 1){
                        $(`.user_row_${curRowSel} .defaultValRowSetter${toTab}.${curRowInpt}`).each(function(e2, obj2){
                            if($(obj2).data('row-type') != fromTab){
                                $(obj2).val(curVal).trigger('change');
                            }
                        }); 
                    }
                });
                if(curRowSel == 1 && index > 1){
                    $(`#user_position_${toTab}`).val(1);
                    $(`.users-${toTab} .repeater`).each(function(e3, obj3){
                        if(e3 > 0){
                            $(obj3).remove();
                        }                        
                    });
                   
                }else{
                    if(curRowSel == index + 1){
                        $(`#user_position_${toTab}`).val(index + 1);
                            user_add_row('required', toTab , index + 1, defVal);
                    } 
                }
            });
            

        //}        
    }

    async function user_add_row(mode, tabType,index, defVales=[]){
    $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: APP_URL + "/contract-setup/financial-add-users/"+index,
                type : "GET",
                dataType: "html",
                data: {defVal:defVales, appType: tabType},
                success:function(data) {
                    if(data)
                    {
                        var position = index + 1;
                        $('.users-'+tabType).last().append(data);
                        $('.users').select2();
                        $('#user_position_'+tabType).val(position);
                        if(mode == "no_auto")
                        {
                            $("#approval_required_users_"+tabType+"_1").prop("required", true);                
                            $('.users-'+tabType+'.user_row_operation a:first').remove();
                            $('.users-'+tabType+'.user_row_operation').first().prepend('<a class="btn-success user_add_row" data-mode="no_auto" style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-plus me-1"></i></a>');
                            var $users = $("#approval_required_users_"+tabType+"_1");
                            get_users($users,1);
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
                url: APP_URL + "/contract-setup/getUsers", // if you say $(this) here it will refer to the ajax call not $('#country')
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
                            $users.append($("<option></option>").attr("value", value.id+":"+value.FirstName+":"+value.Email).text(value.FirstName+" "+value.LastName+"("+value.Email+")"));
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
    
    //For Enable Disable Submit Button
    function disEnableButton(){
        let isValid = true;
        $('#financial_form [required]').each(function(){
            if ( $(this).val() === '' ){
                isValid = false;
            }
        });
        if($('.is-invalid').length > 0 || !isValid){
            $('.btn-buy-now').attr('disabled', 'disabled');
        }else{
          $('.btn-buy-now').attr('disabled', false);  
        }        
    }    