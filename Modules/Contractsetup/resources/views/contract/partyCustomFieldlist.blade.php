 @section('page-script')
@vite([
  'resources/assets/js/forms-selects.js',
  'resources/assets/js/forms-tagify.js',
  'resources/assets/js/forms-typeahead.js'
])

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>

<script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/partycustomfields.js"></script>

<script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/jquery.serialize-object.js"></script>

@endsection

<form id="updateCustom">  
     <div class="panel-group" id="accordion">
        
         <!-- Check if categories exist and iterate through each category -->
         @if (isset($categorys) && !empty($categorys))
         @foreach ($categorys as $category)

       
         <div class="panel panel-default">
             <div class="panel-heading">
                 <h4 class="panel-title" data-id="{{ $category->category_id }}">
                     {{ $category->category_name }}
                 </h4>
             </div>
             <div id="{{ $category->category_id }}" class="panel-collapse">
                 <div class="row panel-body">
                     <!-- Check if custom fields exist and iterate through each field -->
                     @if (isset($lists) && !empty($lists))
                     @foreach ($lists as $list)
                     @if ($list->category == $category->category_id)
                     <div class="col-sm-6">
                         <div class="formlabel form-label">
                             <div class="cursor">
                                 <img src="{{ asset('images/drag.webp') }}" style="width: 0.9rem;margin-top: -1.4rem;">
                             </div>
                             <div class="title">
                                 <!-- Required checkbox -->
                                 <input type="checkbox" {{ $list->required == 1 ? 'checked' : '' }} title="required" name="custom_fields[field{{ $list->custom_field_id }}][required]" value="{{ $list->required }}">

                                 <!-- Field name input -->
                                 <input type="text"  name="custom_fields[field{{ $list->custom_field_id }}][name]" class="form-control inputtxt" value="{{ $list->field_name }}">

                                 <!--<input type="text" name="custom_fields[field{{ $list->custom_field_id }}][name]" value="{{ decryptString($list->field_name, 'typename') }}">-->

                                 <span class="formintype">
                                     <!-- Edit button for select type fields -->
                                     @if ($list->field_type == 'select')
                                     <a class="editselctopino" href="javascript:vodi(0)" style="" data-bs-toggle="modal" data-bs-target="#slectOption">E</a>
                                     @endif
                                     <!-- Delete button -->
                                     <button class="btn btn-sm btn-icon delete" style="margin-left: 10px;"><i class="ti ti-trash"></i></button>
                                 </span>
                                 <span class="formintype">
                                     <!-- Field type dropdown -->
                                     <select class="form-control list-type" name="custom_fields[field{{ $list->custom_field_id }}][type]" aria-label="Type">
                                         <option {{ $list->field_type == 'text' ? 'selected' : '' }} value="text">Text</option>
                                         <option {{ $list->field_type == 'textarea' ? 'selected' : '' }} value="textarea">Textarea</option>
                                         <option {{ $list->field_type == 'date' ? 'selected' : '' }} value="date">Date</option>
                                         <option {{ $list->field_type == 'number' ? 'selected' : '' }} value="number">Number</option>
                                         <option {{ $list->field_type == 'select' ? 'selected' : '' }} value="select">Select</option>
                                         <option {{ $list->field_type == 'currency' ? 'selected' : '' }} value="currency">Currency</option> 
                                     </select>
                                 </span>
 

                                 <!-- Hidden fields for additional data -->
                                 <input type="hidden" name="custom_fields[field{{ $list->custom_field_id }}][group]" class="group" value="{{ $category->category_id }}">
                                 <input type="hidden" name="custom_fields[field{{ $list->custom_field_id }}][value]" class="value" value="{{ $list->field_default_value }}">
                                 <input type="hidden" class="index" name="custom_fields[field{{ $list->custom_field_id }}][order]" value="{{ $list->order_id }}">
                                 <input type="hidden" name="custom_fields[field{{ $list->custom_field_id }}][id]" value="{{ $list->custom_field_id }}">
                                 <input class="status" type="hidden" name="custom_fields[field{{ $list->custom_field_id }}][status]" value="{{ $list->status }}">
                             </div>
                         </div>
                     </div>
                     @endif
                     @endforeach
                     @endif
                 </div>
             </div>
         </div>
         @endforeach
         @endif
     </div>
 </form>
 
 <style>
     .panel-body{
         min-height: 5rem;
     }
 </style>

<script>
   
    $(function() {
        // Make the panel body sortable
        $(".panel-body").sortable({
            placeholder: "accordion-placeholder",
            connectWith: ".panel-body",
            handle: ".cursor",
            helper: "clone",
            start: function(e, ui) {
                ui.placeholder.html('<div class="col-sm-6 high ">' + ui.item.html() + '</div>');
            },
            update: function(event, ui) {
                // Update index and group values after sorting
                $(this).find('input.index').each(function(index) {
                    $(this).val(index + 1);
                });
                $(this).find('input.group').val($(this).closest('.panel').find('.panel-title').data('id'));
            },
        }).disableSelection();

       

    });
</script> 