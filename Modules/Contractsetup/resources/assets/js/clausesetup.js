 
 $(document).ready(function() { 
    // Initialize select2 plugin for dropdowns
    showCategories();
    
    
    // Fetch data on contract type change
    $('#contracttype').on('change', function () {
        getData();
    });    

   $('#contracttype').select2();

    // Open select options modal
    $('.openselctopino').click(function () { 
       $('#dropdownOptions').html(''); 
       listingGroup($('#val').val().split(","));     
       $('.saveselct-wrap').show();
       $('.saveselctupdate-wrap').hide();
   });
   $("#form-list").delegate(".editselctopino", "click", function (e) {
       e.preventDefault();
       $('#dropdownOptions').html('');
       listingGroup($(this).closest('.title').find('.value').val().split(","));

       $('.saveselct-wrap').hide();
       $('.saveselctupdate-wrap').show();

       $('.formlabel').removeClass('curt');

       $(this).closest('.formlabel').addClass('curt');

   });

   $('.saveselctupdate').click(function () {
       var optionsArray = $('#selectoptions').serializeObject().select.options;
       var optionsString = optionsArray.join(",");
       console.log(optionsString);
       $('.curt').find('.value').val(optionsString);

       setTimeout(() => {
           $('.btn-close').trigger('click');
       }, 100);
   });
   $("#selectoptions").delegate(".opton-del", "click", function (e) {
       e.preventDefault();
       if (confirm("Are you sure?")) {
           $(this).closest('.dropdownoption').remove();
       }
   });
   $(".panel-body").delegate(".list-type", "change", function(e) {
       e.preventDefault();
       if ($(this).val() == 'select') {
           $(this).closest('.title').find('.editselctopino').show();
       } else {
           $(this).closest('.title').find('.editselctopino').hide();
       }
   });
   // Delete a custom field
   $("#form-list").delegate(".delete", "click", function(e) {
       e.preventDefault();
       if (confirm("Are you sure?")) {
           $(this).closest('.clause-rows').find('.status').val(0);
           $(this).closest('.clause-rows').hide();
           showToast('Clause deleted successfully!', 'danger');
       }
   });

   // Category Change
    $('#category').on('change', function () {
        const selected = $(this).find(':selected');
        const value = selected.val();
        const required = selected.data('title-required');

        if (value !== '') {
            $('#editClauseBtn').removeClass('d-none').data('title-id', value).data('required', required).data('text', selected.text());
        } else {
            $('#editClauseBtn').addClass('d-none');
        }
    });
    
    $(document).on('click', '.editClauseBtn', function () {
        const clauseText = $(this).data('text');
        const isRequired = $(this).data('required');
        const clauseId = $(this).data('title-id');

        // Set clause title in input
        $('#clauseTitle').val(clauseText.trim());

        // Set checkbox based on required
        $('input[name="required_title"]').prop('checked', parseInt(isRequired) === 1);

        // Add hidden input for ID
        if (!$('#cluaseTitleAddForm input[name="category_id"]').length) {
            $('#cluaseTitleAddForm').append(
                `<input type="hidden" name="category_id" id="editClauseId" value="${clauseId}">`
            );
        } else {
            $('#editClauseId').val(clauseId);
        }

        // Show the modal
        $('#cluaseTitleAdd').modal('show');
    });  
    
    // When modal is closed, remove the hidden ID input
    $('#cluaseTitleAdd').on('hidden.bs.modal', function () {
        if($('#editClauseId').length){
            $('#editClauseId').remove();
        }
        
        $('#cluaseTitleAddForm')[0].reset();
    });    

   // Add New Title
    $(document).on("click", ".saveClauseTitle", function(e) {
    e.preventDefault();
    
    let titleVal = $('#clauseTitle').val();
    let isRequired = $('input[name="required_title"]').is(':checked') ? 1 : 0;
    let titleId = $('#editClauseId').val() ?? 0;

    if (titleVal === '') {
        $('#titleAlert').removeClass("d-none").text('Please Enter Title...');
    } else {
        $.ajax({
            url: APP_URL + '/contract-setup/clause/title-add',
            type: 'POST',
            data: {
                clauseTitle: titleVal,
                required_title: isRequired,
                category_id:titleId
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    if (!$('#titleAlert').hasClass("d-none")) {
                        $('#titleAlert').hide();
                        $('#cluaseTitleAdd').modal('hide');
                        getData();
                        showToast(response.message, 'success');
                    }
                } else {
                    $('#titleAlert').show().text('Title Already Taken');
                }
            },
            error: function(xhr, status, error) {
                $('#titleAlert').show().text('Invalid Name');
            }
        });
    }
});


   // Add new option in select modal
   $('.addoption').click(function(e) {
       e.preventDefault();
       listingGroup(['option1']);
   });
   // Save select options
    // Save select options
    $('.saveselct').click(function () {
       var optionsArray = $('#selectoptions').serializeObject().select.options;
       var optionsString = optionsArray.join(",");
       $('#val').val(optionsString);
       setTimeout(() => {
           $('.btn-close').trigger('click');
       }, 100);
   });
   // Form validation
   $('#createCustom').validate({
       ignore: [],
       errorPlacement: function(error, element) {
           if (element.hasClass('select2-hidden-accessible')) {
               error.insertAfter(element.next('.select2'));
           } else {
               error.insertAfter(element);
           }
       },
       rules: {
           val: {
               required: true
           },
            
           category: {
               required: true
           }
       },
       messages: {
           val: {
               required: "Please enter a Title Description"
           },
           contracttype: {
               required: "Please select a Contract Type"
           },
           category: {
               required: "Please select a Category"
           }
       },
       submitHandler: function(form) {
           $.ajax({
               url: 'clause/add',
               type: 'POST',
               data: $(form).serialize(),
               dataType: 'json',
               headers: {
                   'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
               },
               success: function(response) {
                   showToast('Clause created successfully!', 'success');
                   getData();
                   setTimeout(() => {
                       $('#type,#category').val(null).trigger('change');
                       $('#createCustom')[0].reset();
                   }, 500);
               },
               error: function(xhr, status, error) {
                   console.error(xhr.responseText);
               }
           });
           return false;
       }
   });

   // Trigger validation on input change
   $('select, input').on('change', function() {
       $('#createCustom').valid();
   });

   // Show/Hide select options link based on field type
   $('#type').on('change', function () {

    $('.openselctopino').hide();

    if ($(this).val() == 'select') {
        $('#val').val('option1,option2,option3');
        $('.openselctopino').show();
    } else {
       
        $('#val').val('');
    }
});

   getData();

   function listingGroup(selectOption, index = -1) {
       selectOption.forEach(function(option, i) {
           // Check if the current iteration index matches the provided index
           if (index === i) {
               // If the index matches, set the input value to the provided option
               var value = option;
           } else {
               // Otherwise, set the input value to the current option in the array
               var value = option;
           }

           
           var dropdownOption = $('<div class="dropdownoption col-sm-12 formlabel row"></div>');

           dropdownOption.append('<div class="col-1"><div class="cusor"><img  style="width:1rem" src="/contractsdemo/images/Move.png" ></div></div><div class="col-8"><input type="text" value="' +
               value + '" name="select[options][]" class="form-control float-left"></div><div class="col-2"><button class="opton-del btn btn-danger pull-right">Delete</button></div>');
           $('#dropdownOptions').append(dropdownOption);
       });
   }
   
   function showCategories(){
    $('#category').select2({
          ajax: {
            url: APP_URL + '/contract-setup/clause/titles',
            processResults: function (data) {
              // Map the response to add `title_required: 1` if optional === 0
              const resultsWithRequiredFlag = data.results.map(item => {
                return {
                  ...item,
                  title_required: item.optional
                };
              });
        
              return {
                results: resultsWithRequiredFlag
              };
            }
          },
          templateResult: formatOption,
          templateSelection: formatSelection,
            language: {
                noResults: function () {
                    return `Not found create new <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#cluaseTitleAdd"><i class="ti ti-plus"></i> Title</button>`;
                }
            }, 
            escapeMarkup: function (markup) {
                return markup;
            }          
        });
   }
   
  
});


//For Additional Attribute
function formatOption(data) {
  if (!data.id) return data.text;

  const $option = $('<span></span>')
    .text(data.text)
    .attr('data-title-required', data.title_required); // 1 or 0

  return $option;
}

function formatSelection(data) {
  return data.text;
}


$('.formdata').click(function(e) {

   e.preventDefault();
   $.ajax({
       url: APP_URL + '/contract-setup/clause/modify',
       type: 'POST',
       data: $('#updateCustom').serializeObject(),
       headers: {
           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
       },
       success: function(response) {
          showToast('Updated Successfully!', 'primary');
          getData();
       },
       error: function(xhr, status, error) {
           console.error(xhr.responseText);
       }
   });
});


$('input.group').each(function(index) {
    $(this).val($(this).closest('.panel-default').find('.panel-title').data('id'));
});




$("#dropdownOptions").sortable({
   placeholder: "accordion-placeholder",
   connectWith: ".dropdownoption",
   handle: ".cusor",
   helper: "clone",
   start: function(e, ui) {
       ui.placeholder.html('<div class="col-sm-6 high ">' + ui.item.html() + '</div>');
   },
   update: function(event, ui) {
       // Update index and group values after sorting
       $(this).find('input.index').each(function(index) {
           $(this).val(index + 1);
       });
       $('input.group').each(function(index) {
        $(this).val($(this).closest('.panel-default').find('.panel-title').data('id'));
    });
    
   },
}).disableSelection();


   // Fetch form data
   function getData() {
       var form = $('#createCustom').serialize();
       $.ajax({
           url: 'clause/list',
           type: 'POST',
           data: form,
           headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           },
           success: function(response) {
               $('#form-list').html(response);
               resetIndex();
           },
           error: function(xhr, status, error) {
               console.error(xhr.responseText);
           }
       });
   }
   // Fetch data on contract type change 
   
   
         // Function to reset index values
   function resetIndex() {
        $('.panel-body').find('input.index').each(function(index) {
            $(this).val(index + 1);
        });
        $('input.group').each(function(index) {
            $(this).val($(this).closest('.panel-default').find('.panel-title').data('id'));
        });
    }
    
  function showToast(message, type = 'success') {
    // type: 'success', 'primary', 'danger', etc.
    const toastContainer = document.getElementById('toast-container');

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.role = 'alert';
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.style.minWidth = '350px'; // bigger width
    toast.style.fontSize = '1.1rem';
    toast.style.borderRadius = '0.5rem';

    // Toast inner HTML
    toast.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    `;

    toastContainer.appendChild(toast);

    // Initialize and show toast
    const bsToast = new bootstrap.Toast(toast, { delay: 4000 });
    bsToast.show();

    // Remove toast element from DOM after hidden to avoid clutter
    toast.addEventListener('hidden.bs.toast', () => {
      toast.remove();
    });
  }
