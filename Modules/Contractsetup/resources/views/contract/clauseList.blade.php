@section('page-script')
@vite([
  'resources/assets/js/forms-selects.js',
  'resources/assets/js/forms-tagify.js',
  'resources/assets/js/forms-typeahead.js'
])

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>

<script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/clausesetup.js"></script>

<script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/jquery.serialize-object.js"></script>

@endsection

<form id="updateCustom">  
     <div class="panel-group" id="accordion">

         <!-- Check if categories exist and iterate through each category -->
         @if (isset($categorys) && !empty($categorys))
         @foreach ($categorys as $category)
         @php
            $cateWiseData = 0;
         @endphp
         <div class="panel panel-default">
             <div class="panel-heading d-flex">
                 <h6 class="panel-title border rounded p-2 bg-clause-title text-white flex-grow-1 align-self-center mb-0" data-id="{{ $category->category_id }}">
                     {{ strtolower($category->category_name) }}
                    <a href="javascript:;" class="float-end editClauseBtn" data-title-id="{{ $category->category_id }}" data-text="{{$category->category_name}}" data-required="{{ $category->required }}">
                        <span class="ti ti-edit text-white"></span>
                    </a>                     
                     <span class="text-white float-end me-2">{{ $category->required == 1 ? 'Required' : 'Optional' }}</span>
                 </h6>
             </div>
             <div id="{{ $category->category_id }}" class="panel-collapse mt-2">
                 <div class="row panel-body pppp">
                     <!-- Check if custom fields exist and iterate through each field -->
                     @if (isset($lists) && !empty($lists))
                     @foreach ($lists as $list)
                     @if ($list->category == $category->category_id)
                     @php
                        $cateWiseData++;
                     @endphp
                     <div class="col-12 clause-rows">
                         <div class="">
                             <div class="title d-flex">
                                 <i class="ti ti-grip-vertical cursor align-self-center text-dark"></i>
                                 <!-- Required checkbox -->
                                 <textarea name="custom_fields[field{{ $list->custom_field_id }}][value]" rows="4" class="value form-control mb-2">{{ $list->field_default_value }}</textarea>
                                <div class="form-check form-check-danger align-self-center ms-2">
                                  
                                 <input class="form-check-input" type="checkbox" {{ $list->required == 1 ? 'checked' : '' }} title="required" name="custom_fields[field{{ $list->custom_field_id }}][required]" value="{{ $list->required }}">
                                </div>
                                 <!-- Field name input -->
                                 <input type="hidden"  name="custom_fields[field{{ $list->custom_field_id }}][name]" class="form-control inputtxt" value="{{ $list->field_name }}">

                                 <button class="btn btn-sm btn-icon delete align-self-center" style="margin-left: 10px;"><i class="ti ti-square-rounded-minus text-danger"></i></button>
                                 <span class="formintype">
                                     <!-- Edit button for select type fields -->
                                     @if ($list->field_type == 'select')
                                     <a class="editselctopino" href="javascript:vodi(0)" style="" data-bs-toggle="modal" data-bs-target="#slectOption">E</a>
                                     @endif
                                     <!-- Delete button -->
                                 </span>
                                 <span class="formintype d-none">
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
                                 <input type="hidden" class="index" name="custom_fields[field{{ $list->custom_field_id }}][order]" value="{{ $list->order_id }}">
                                 <input type="hidden" name="custom_fields[field{{ $list->custom_field_id }}][id]" value="{{ $list->custom_field_id }}">
                                 <input class="status" type="hidden" name="custom_fields[field{{ $list->custom_field_id }}][status]" value="{{ $list->status }}">
                             </div>
                         </div>
                     </div>
                     @endif
                     @endforeach
                     @endif
                     <p class="text-center align-self-center text-warning no-content" style="display: {{ $cateWiseData == 0 ? '' : 'none'}}">-- Content Not Available --</p>
                 </div>
             </div>
         </div>
         <hr/>
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
                ui.placeholder.html('<div class="col-sm-12 high ">' + ui.item.html() + '</div>');
            },
            update: function(event, ui) {
                // Update index and group values after sorting
                $(this).find('input.index').each(function(index) {
                    $(this).val(index + 1);
                });
                $(this).find('input.group').val($(this).closest('.panel').find('.panel-title').data('id'));
                if($(this).closest('.panel').find('.panel-body div').length > 0){
                    $(this).closest('.panel').find('.panel-body p.no-content').hide();
                }else{
                    $(this).closest('.panel').find('.panel-body p.no-content').show();
                }
            },
        }).disableSelection();

       

    });
</script> 