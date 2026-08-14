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
             <div class="panel-heading">
                 <h6 class="panel-title border rounded p-2 bg-clause-title text-white" draggable="true" data-con-type="heading" id="clause_title_{{$category->category_id}}_op">
                     {{ ucfirst($category->category_name) }}
                 </h6>
             </div>
             <div id="{{ $category->category_id }}" class="panel-collapse">
                 <div class="row panel-body">
                     <!-- Check if custom fields exist and iterate through each field -->
                     @if (isset($lists) && !empty($lists))
                     @foreach ($lists as $list)
                     @if ($list->category == $category->category_id)
                     @php
                        $cateWiseData++;
                     @endphp
                     <div class="col-12">
                         <div class="mb-2">
                             <div class="title d-flex">
                                 <span class="align-self-center d-none" style="margin-left: 10px;"><i class="ti {{$list->required == 1 ? 'ti-square-rounded-minus text-danger' : 'ti-square-rounded-plus text-primary'}}"></i></span>
                                 <!-- Required checkbox -->
                                 <p class="value form-control ms-2 mb-0 cursor" draggable="true" data-con-type="content" id="clause_{{$list->custom_field_id}}" data-clause-id="{{$category->category_id}}">{{ $list->field_default_value }}</p>
                                 <input type="hidden" {{ $list->required == 1 ? 'checked' : '' }} title="required" name="custom_fields[field{{ $list->custom_field_id }}][required]" value="{{ $list->required }}">

                                 <!-- Field name input -->
                                 <input type="hidden"  name="custom_fields[field{{ $list->custom_field_id }}][name]" class="form-control inputtxt" value="{{ $list->field_name }}">

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
    .custom-toast {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #343a40;
        color: #fff;
        padding: 10px 20px;
        border-radius: 5px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease, transform 0.3s ease;
        z-index: 9999;
    }
    
    .custom-toast.show {
        opacity: 1;
        pointer-events: auto;
    }
     
 </style>

 
 