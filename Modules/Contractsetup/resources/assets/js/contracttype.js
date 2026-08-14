'use strict';

$(function () {
    $(document).ready(function() {
        $(`#DepartmentType`).select2();
        $(`#GroupName`).select2();
        loadCategoryType();
    });
    
       // Add New Title
   $(document).on("click",".saveCategoryTitle", function(e) {
       let titleVal = $('#categoryTitle').val();
       e.preventDefault();
       if(titleVal == ''){
           $('#titleAlert').removeClass("d-none").text('Please Enter Title...');
       }else{
        $.ajax({
           url: APP_URL+'/contract-setup/category/title-add',
           type: 'POST',
           data: {categoryTitle:  titleVal},
           headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           },
           success: function(response) {
               //$('#form-list').html(response);
               if(response.success){
                   if(!$('#titleAlert').hasClass("d-none")){
                    $('#titleAlert').hide();
                    $('#categoryTypeAdd').modal('hide');
                    $('#catgoeryType')
                    .append($("<option></option>")
                    .attr("value", response.data.id)
                    .text(response.data.name));                     
                    loadCategoryType();
                   }
               }else{
                    $('#titleAlert').show().text('Title Already Taken');                  
               }
           },
           error: function(xhr, status, error) {
               $('#titleAlert').show().text('Invalid Name');
           }
       });
       }
   });
   
   //Load Category
   
   function loadCategoryType(){
        $(`#catgoeryType`).select2({
            language: {
                searching: function () {
                    return "Searching...";
                },
                noResults: function () {
                    return `No Category Found Click to create new   <button type="button" class="badge bg-primary cusocli" data-bs-toggle="modal" data-bs-target="#categoryTypeAdd">Create</button>`;
                }
            },
            escapeMarkup: function (markup) {
                return markup;
            }                     
        });       
   }
});
