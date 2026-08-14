 
 $(document).ready(function() { 
    // Initialize select2 plugin for dropdowns
   $('#category').select2();

   $('#contracttype').trigger('change');

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
           $(this).closest('.col-sm-6').find('.status').val(0);
           $(this).closest('.col-sm-6').hide();
           $('#dstate').show();
           setTimeout(() => {
               $('#dstate').hide();
           }, 2000);
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
           label: {
               required: true
           },
            
           category: {
               required: true
           }
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
       },
       submitHandler: function(form) {
           $.ajax({
               url: 'contract',
               type: 'POST',
               data: $(form).serialize(),
               dataType: 'json',
               headers: {
                   'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
               },
               success: function(response) {
                   $('#state').show();
                   setTimeout(() => {
                       getData();
                       $('#state').hide();
                       $('#type,#category').val(null).trigger('change');
                       $('#createCustom')[0].reset();
                       $('.openselctopino').hide();
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
  
});

$('.formdata').click(function(e) {

   e.preventDefault();
   $.ajax({
       url: 'update',
       type: 'POST',
       data: $('#updateCustom').serializeObject(),
       headers: {
           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
       },
       success: function(response) {
           $('#ustate').show();
           setTimeout(() => {
               $('#ustate').hide();
               getData();
           }, 500);
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
       var form = $('#createCustom');
       $.ajax({
           url: 'party/list-ind',
           type: 'POST',
           data: 1,
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

   // Function to reset index values
   function resetIndex() {
        $('.panel-body').find('input.index').each(function(index) {
            $(this).val(index + 1);
        });
        $('input.group').each(function(index) {
            $(this).val($(this).closest('.panel-default').find('.panel-title').data('id'));
        });
    }
