$(document).on("click",".saveVar", function(e) {
    
       let titleVal = $('#varTitle').val();
       let textVal = $('#VarText').val();
       
       e.preventDefault();
       
       if(titleVal == '' || textVal == ''){
           $('#titleAlert').removeClass("d-none").text('Please Enter Values...');
       }else{
            let formVal = $('#customVarAddForm').serializeObject();
            addEditVars(formVal);
       }
});

$(document).on("click",".updateVar", function(e) {
    
       let titleVal = $('#varEditTitle').val();
       let textVal = $('#VarEditText').val();
       
       e.preventDefault();
       
       if(titleVal == '' || textVal == ''){
           $('#titleAlert').removeClass("d-none").text('Please Enter Values...');
       }else{
            let formVal = $('#customVarEditForm').serializeObject();
            addEditVars(formVal, 'Edit');
       }
});

$(document).on("click",".editVars", function(e) {
    
       let varId = $(this).data('var-id');
       $('#customVarEditForm')[0].reset();
        $.ajax({
           url: APP_URL+'/contract-setup/vars/custom-var-edit',
           type: 'POST',
           data:  {var_token:varId},
           headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           },
           success: function(response) {
               $('#varEdit').modal('show');
               if(response.success){
                    $('#varId').val(response.data.var_id);  
                    $('#varEditTitle').val(response.data.var_field);  
                    $('#VarEditText').val(response.data.var_disp_var);  
                    $('#varEditTables').val(response.data.var_table);  
                    $('#titleEditAlert').show().text(response.message);                  
               }else{
                    $('#titleEditAlert').show().text(response.message);                  
               }
           },
           error: function(xhr, status, error) {
               $('#titleEditAlert').show().text('Invalid Name');
           }
       });
});

function addEditVars(formVal, alertDiv='Add'){

        $.ajax({
           url: APP_URL+'/contract-setup/vars/custom-var-add',
           type: 'POST',
           data: formVal,
           headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           },
           success: function(response) {
               //$('#form-list').html(response);
               if(response.success){
                   if(!$(`#title${alertDiv}Alert`).hasClass("d-none")){
                    $(`#title${alertDiv}Alert`).hide();
                    $(`#var${alertDiv}`).modal('hide');
                    window.location.reload();
                   }
               }else{
                    $(`#title${alertDiv}Alert`).show().text('Var Already Taken');                  
               }
           },
           error: function(xhr, status, error) {
               $(`#title${alertDiv}Alert`).show().text('Invalid Name');
           }
       });    
}
