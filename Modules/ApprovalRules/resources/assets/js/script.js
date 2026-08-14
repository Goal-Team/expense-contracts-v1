$(document).ready(function() {  
    var url = window.location.href;
    var last = url.lastIndexOf('/') +1;
    var base_url =  url.substring(0,last);
    //console.log("base_url",base_url);
    
    
    //For Form Submit Disabled
    $('#financial_form .btn-buy-now').attr('disabled', 'disabled');
    
    $(document).on('change','#financial_form .form-control,.form-select, .form-check-input' ,function (event) {
        disEnableButton();
    });    


    $('.numberonly').keypress(function (e) {    
        var charCode = (e.which) ? e.which : event.keyCode
        if (String.fromCharCode(charCode).match(/[^0-9]/g))
            return false;
    });

     
     $('.form-select').each(function(){
         $(this).select2();
     });

     // Initialize approval_user_type and group approver type visibility on load
     $('.approval_user_type').each(function(){
        $(this).trigger('change');
     });
     $('.group-approver-type').each(function(){
        $(this).trigger('change');
     });
     
     //Select Any/All
     $(document).on('change','.opt-select-any-all', function(){
        let currVal = $(this).val();
        if( jQuery.inArray("0", currVal) !== -1 && currVal.length > 1){
            let selectedItems = $(this).find('option:not(:disabled)').map(function() { return this.value });
            selectedItems = selectedItems.filter(function( obj ) {
              return selectedItems[obj] == '0';
            });
            $(this).val(selectedItems).trigger('change');
        }
     });
     
     //Select All
     $(document).on('change','.opt-select-all', function(){
        let currVal = $(this).val();
        if( jQuery.inArray("all", currVal) !== -1){
            let selectedItems = $(this).find('option:not(:disabled)').map(function() { return this.value });
            selectedItems = selectedItems.filter(function( obj ) {
              return selectedItems[obj] !== 'all';
            });
            $(this).val(selectedItems).trigger('change');
        }
     });    
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
    });
    

    $(document).on('change','.approval_user', function(e) {
        const curSel = $(this);
        const curVal = curSel.val();
        const curSelId = curSel.data('id');
        const curSelAppRow = curSel.data('row-type');
        let selNotifyUsers = $(`.userNotiValSetter${curSelAppRow}`);
        let curSelNotifyUsers = selNotifyUsers.val();
        let selValues = [];
        selNotifyUsers.find(`option:disabled`).attr('disabled', false);
        let signUser = $(`.defaultValSetter${curSelAppRow}.userSign`).val();
        $(`.users-${curSelAppRow} .approval_user`).each(function(e_){
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
            selNotifyUsers.find(`option[value="${$(this).val()}"]`).attr('disabled', true); 
            selValues.push($(this).val());
        });
        
        if(signUser != ""){
            selNotifyUsers.find(`option[value="${signUser}"]`).attr('disabled', true); 
            selValues.push(signUser);            
        }

        curSelNotifyUsers = curSelNotifyUsers.filter(function( obj ) {
          return !selValues.includes(obj);
        });
        selNotifyUsers.val(curSelNotifyUsers).trigger('change');
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
        
        var mode =  $(this).data('mode');
        var tabType =  $(this).data('tab-type');
        var index = parseInt($('#user_position_'+tabType).val())+1;
        user_add_row(mode, tabType,index);
       // alert("user_add_row");
    }); 
    $(document).on('click','.user_add_row_party',function (event) {  

        var mode =  $(this).data('mode');
        var index = parseInt($('#user_position').val())+1;
        user_add_row_party(mode,index);
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
        let val = $(this).val();

        // Generic toggle using existing by_name_desg classes
        $(`.users-${curr_row_app_type} .by_name_desg_${curr_row}`).addClass('d-none').attr('name', 'by_not_sel[]');
        $(`.users-${curr_row_app_type} .by_${val}_${curr_row}`).removeClass('d-none').attr('name', 'approval_required_users[]');

        // Explicitly show/hide designation and name-specific fields for clarity
        // Hide users dropdown when 'designation' selected, show designation select (.apprDesgUsr)
        if(val === 'designation'){
            $(`.users-${curr_row_app_type} .apprNameUsr[data-row-sel="${curr_row}"]`).addClass('d-none').attr('name', 'by_not_sel[]');
            $(`.users-${curr_row_app_type} .apprDesgUsr[data-row-sel="${curr_row}"]`).removeClass('d-none').attr('name', 'approval_required_desg[]');
        } else {
            $(`.users-${curr_row_app_type} .apprDesgUsr[data-row-sel="${curr_row}"]`).addClass('d-none').attr('name', 'by_not_sel[]');
            $(`.users-${curr_row_app_type} .apprNameUsr[data-row-sel="${curr_row}"]`).removeClass('d-none').attr('name', 'approval_required_users[]');
        }
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
                }else if($(obj).is(':input')){
                    $(this).val(curVal).trigger('change');
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
                        var position = index;
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
    
    function user_add_row_party(mode,index){
    $.ajax({
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url: APP_URL + "/contract-setup/party-approval-add-users/"+index,
                type : "GET",
                dataType: "html",
                success:function(data) {
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
        return $.ajax({
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

                        // also initialize users inside group approvers
                        $('.group-approver-select').each(function(){
                            var $sel = $(this);
                            if($sel.children().length <= 1){
                                $sel.empty();
                                $sel.append($("<option></option>").attr("value", "").text("--Select Approver--"));
                                $.each(data, function(key,value) {
                                    $sel.append($("<option></option>").attr("value", value.id+":"+value.FirstName+":"+value.Email).text(value.FirstName+" "+value.LastName+"("+value.Email+")"));
                                });
                                $sel.select2();
                            }
                        });

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

    // ----------------- Approval Groups UI ------------------
    $(document).on('click', '.add-approval-group', function(e){
        var appType = $(this).data('tab-type');
        var parentType = $(this).data('parent-type');
        addApprovalGroup(appType, parentType);
    });

    function addApprovalGroup(appType, parentType, groupData = null){
        var container = $('.approval-groups-'+appType+'-'+parentType);
        var gid = Date.now();
        var html = `
            <div class="approval-group border rounded p-3 mb-2" data-gid="${gid}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <label>Role</label>
                        <select class="form-select group-role" data-app="${appType}">
                            <option value="Approver">Approver</option>
                            <option value="Verifier">Verifier</option>
                            <option value="Preapprover">Pre Approver</option>
                            <option value="Signatory">Signatory</option>
                            <option value="Negotiator">Negotiator</option>
                            <option value="Finalizer">Finalizer</option>
                        </select>
                    </div>
                    <div>
                        <label>File Permission</label>
                        <select class="form-select group-file-permission" data-app="${appType}">
                            <option value="editor">Editor</option>
                            <option value="commentator">Commentator</option>
                            <option value="readonly">Readonly</option>
                        </select>
                    </div>
                    <div>
                        <label>Approval Type</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input group-type" type="radio" name="group_type_${gid}" value="sequential" checked> Sequential
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input group-type" type="radio" name="group_type_${gid}" value="parallel"> Parallel
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input group-auto-next" type="checkbox" id="group_auto_next_${gid}" value="1">
                            <label class="form-check-label" for="group_auto_next_${gid}">Auto move next group</label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input group-dynamic-approver" type="checkbox" id="group_dynamic_approver_${gid}" value="1">
                            <label class="form-check-label" for="group_dynamic_approver_${gid}">Dynamic Approvers</label>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-danger remove-group">Remove Group</button>
                    </div>
                </div>
                <div class="group-approvers"></div>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-success add-group-approver">Add Approver</button>
                </div>
            </div>
        `;
        container.append(html);
        if(groupData){
            var $group = container.find(`.approval-group[data-gid="${gid}"]`);
            $group.find('.group-role').val(groupData.role || 'Approver');
            $group.find('.group-file-permission').val(groupData.file_permission || 'editor');
            $group.find(`.group-type[value="${groupData.approval_type}"]`).prop('checked', true);
            var autoNextEnabled = Number(groupData.auto_next_enabled || 0) === 1;
            $group.find('.group-auto-next').prop('checked', autoNextEnabled);
            var dynamicApproverEnabled = Number(groupData.dynamic_approver_enabled || 0) === 1;
            $group.find('.group-dynamic-approver').prop('checked', dynamicApproverEnabled);
            if(Array.isArray(groupData.approvers)){
                groupData.approvers.forEach(function(appr){
                    addGroupApproverRow(appType, gid, appr);
                });
            }
        }
        disEnableButton();
    }

    $(document).on('click', '.remove-group', function(){
        $(this).closest('.approval-group').remove();
    });

    $(document).on('click', '.add-group-approver', function(){
        var $g = $(this).closest('.approval-group');
        var $parentSection = $g.closest('[class^="approval-groups-"]');
        var classAttr = $parentSection.attr('class');
        var match = classAttr.match(/approval-groups-([^ ]+)-([^ ]+)/);
        var appType = match ? match[1] : '';
        var parentType = match ? match[2] : '';
        var gid = $g.data('gid');
        addGroupApproverRow(appType, gid);
    });

    function addGroupApproverRow(appType, gid, approver = null){
        var $group = $(`.approval-group[data-gid="${gid}"]`);
        var idx = $group.find('.group-approver-row').length + 1;
        var html = `
            <div class="row group-approver-row mt-2">
                <div class="col-3">
                    <select class="form-select group-approver-type" data-idx="${idx}">
                        <option value="name">By Name</option>
                        <option value="designation">By Designation</option>
                    </select>
                </div>
                <div class="col-5 approver-inputs">
                    <select class="form-select group-approver-select" style="width:100%"></select>
                    <select class="form-select d-none group-approver-designation apprDesgUsr" style="width:100%">
                        <option value="">Select Designation</option>
                        <option value="unit_head">Unit Head</option>
                        <option value="branch_head">Branch Head</option>
                        <option value="branch_dep_head">Branch Dept Head</option>
                        <option value="overall_dept_head">Over All Dept Head</option>
                    </select>
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-sm btn-danger remove-approver">Remove</button>
                </div>
            </div>
        `;
        $group.find('.group-approvers').append(html);
        var $row = $group.find('.group-approver-row').last();
        // initialize select2 and populate options
        var $sel = $row.find('.group-approver-select');
        var req = get_users($sel, idx);

        function applyApproverToRow() {
            if (!approver) return;

            if (approver.type == 'designation') {
                $row.find('.group-approver-type').val('designation').trigger('change');
                $row.find('.group-approver-designation').val(approver.name).removeClass('d-none');
                $sel.addClass('d-none');
                // hide select2 container as well
                $sel.next('.select2-container').hide();
                // clear any stale name value in hidden user select
                $sel.val(null).trigger('change.select2');
            } else {
                $row.find('.group-approver-type').val('name').trigger('change');
                $sel.val(approver.id+":"+approver.name+":"+approver.email).trigger('change.select2');
            }
        }

        if(approver){
            // set value only after get_users AJAX finishes populating options
            req.done(function(){
                applyApproverToRow();
            }).fail(function(){
                // fallback: try to set immediately
                applyApproverToRow();
            });
        }

    }

    $(document).on('change', '.group-approver-type', function(){
        var $row = $(this).closest('.group-approver-row');
        var val = $(this).val();
        if(val == 'designation'){
            $row.find('.group-approver-select').addClass('d-none');
            // hide select2 widget if present
            $row.find('.group-approver-select').each(function(){
                $(this).next('.select2-container').hide();
            });
            $row.find('.group-approver-designation').removeClass('d-none');
        } else {
            $row.find('.group-approver-select').removeClass('d-none');
            // show select2 widget if present
            $row.find('.group-approver-select').each(function(){
                $(this).next('.select2-container').show();
            });
            $row.find('.group-approver-designation').addClass('d-none');
        }
    });

    $(document).on('click', '.remove-approver', function(){
        $(this).closest('.group-approver-row').remove();
    });

    function serializeApprovalGroups(){
        $('[id^="approval_groups"]').each(function(){
            var input = $(this);
            var appType = input.attr('id').replace('approval_groups','');
            var groups = {
                review: [],
                negotiation: [],
                finalization: [],
                approval: [],
                signatory: [],
                _parent_routing: {}
            };
            ['review', 'negotiation', 'finalization', 'approval', 'signatory'].forEach(function(parentType){
                // Collect parent-level routing
                var onApprove = $('.parent-on-approve[data-parent-type="'+parentType+'"]').val();
                var onReject = $('.parent-on-reject[data-parent-type="'+parentType+'"]').val();
                if(onApprove || onReject){
                    groups._parent_routing[parentType] = {
                        on_approve: onApprove,
                        on_reject: onReject
                    };
                }
                
                // Collect inner groups
                $('.approval-groups-'+appType+'-'+parentType+' .approval-group').each(function(){
                    var $g = $(this);
                    var role = $g.find('.group-role').val();
                    var file_permission = $g.find('.group-file-permission').val() || 'editor';
                    var approval_type = $g.find('.group-type:checked').val();
                    var auto_next_enabled = $g.find('.group-auto-next').is(':checked') ? 1 : 0;
                    var dynamic_approver_enabled = $g.find('.group-dynamic-approver').is(':checked') ? 1 : 0;
                    var approvers = [];
                    $g.find('.group-approver-row').each(function(){
                        var $r = $(this);
                        var type = $r.find('.group-approver-type').val();
                        if(type == 'designation'){
                            var name = $r.find('.group-approver-designation').val();
                            approvers.push({id:0,type:'designation',name:name,email:''});
                        } else {
                            var val = $r.find('.group-approver-select').val();
                            if(val){
                                var parts = val.split(":");
                                approvers.push({id:parts[0],type:'name',name:parts[1],email:parts[2]});
                            }
                        }
                    });
                    groups[parentType].push({role:role,file_permission:file_permission,approval_type:approval_type,auto_next_enabled:auto_next_enabled,dynamic_approver_enabled:dynamic_approver_enabled,approvers:approvers});
                });
            });
            input.val(JSON.stringify(groups));
        });
    }

    // on submit, serialize groups
    $('#financial_form').on('submit', function(e){
        serializeApprovalGroups();
        return true;
    });

    // load existing groups on page load
    $(document).ready(function(){
        $('[id^="approval_groups"]').each(function(){
            var input = $(this);
            var appType = input.attr('id').replace('approval_groups','');
            var val = input.val();
            if(val){
                try {
                    var groups = JSON.parse(val);
                    if(typeof groups === 'object' && !Array.isArray(groups)){
                        ['review', 'negotiation', 'finalization', 'approval', 'signatory'].forEach(function(parentType){
                            if(Array.isArray(groups[parentType])){
                                groups[parentType].forEach(function(g){
                                    addApprovalGroup(appType, parentType, g);
                                });
                            }
                        });
                    } else if(Array.isArray(groups)){
                        groups.forEach(function(g){
                            var parentType = 'approval';
                            if(g.role === 'Signatory'){
                                parentType = 'signatory';
                            } else if(g.role === 'Verifier' || g.role === 'Preapprover'){
                                parentType = 'review';
                            } else if(g.role === 'Negotiator'){
                                parentType = 'negotiation';
                            } else if(g.role === 'Finalizer'){
                                parentType = 'finalization';
                            }
                            addApprovalGroup(appType, parentType, g);
                        });
                    }
                } catch (e) {
                    console.log('Invalid groups json', e);
                }
            }
        });
    });

    // -----------------------------------------------------

    
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