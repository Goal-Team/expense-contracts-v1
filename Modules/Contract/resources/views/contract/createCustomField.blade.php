 @section('vendor-style')
 @vite([
 'resources/assets/vendor/libs/quill/typography.scss',
 'resources/assets/vendor/libs/quill/katex.scss',
 'resources/assets/vendor/libs/quill/editor.scss',
 'resources/assets/vendor/libs/select2/select2.scss',
 'resources/assets/vendor/libs/dropzone/dropzone.scss',
 'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
 'resources/assets/vendor/libs/tagify/tagify.scss'
 ])
 @endsection

<div class="customFieldTitleSection customFieldTitleSection_{{$categoryId}}" style="display: none;">
    <h6 class="mt-4">Custom Fields</h6>
    <hr class="mt-0" />
</div>
<div class="clearfix">
     <div class="row">
         @foreach ($customFields as $customField)
             @if($customField->category == $categoryId)
                 <div class="col-sm-6 {{ (isset($customField->required) && $customField->required == 1) ? '' : 'unRequiredFields' }} {{ $customField->field_type == 'date' ? 'datepikr' : ' ' }} groupby groupby-{{$customField->contract_type}} {{ !empty($customField->is_generic) ? 'groupby-generic' : '' }}" data-catet="{{$categoryId}}" data-count="1">
                     <div class="form-group required-{{ $customField->required }}">
                         <label for="Currencycontract">
            
                             {{ $customField->field_name}}
            
                             @if(isset($customField->required) && $customField->required == 1)
                             <span class="text-danger">*</span>
                             @endif
                         </label>
            
            
            
            
                         <input type="hidden" value="{{ $customField->field_name }}" name="customFields[{{$customField->custom_field_id}}][label]">
            
                         <input type="hidden" value="{{ $customField->custom_field_id }}" name="customFields[{{$customField->custom_field_id}}][id]">
            
            
            
                         @if ($customField->field_type == 'text')
                         <input type="text" class="form-control required{{ $customField->required }}" id="{{$customField->field_name}}-text" name="customFields[{{$customField->custom_field_id}}][value]" value="{{old('customFields.'.$customField->custom_field_id.'.value')}}" {{ $customField->required == 1 ? 'required' : '' }}>
                         @endif
            
                         @if ($customField->field_type == 'textarea')
                         <textarea class="form-control required{{ $customField->required }}" id="{{$customField->field_name}}-text" name="customFields[{{$customField->custom_field_id}}][value]" {{ $customField->required == 1 ? 'required' : '' }}>{{old('customFields.'.$customField->custom_field_id.'.value')}}</textarea>
                         @endif
            
            
            
                         @if ($customField->field_type == 'date')
                         <input type="text" class="form-control flatpickr required{{ $customField->required }}" id="{{$customField->field_name}}-date" name="customFields[{{$customField->custom_field_id}}][value]" value="{{old('customFields.'.$customField->custom_field_id.'.value')}}" {{ $customField->required == 1 ? 'required' : '' }}>
                         @endif
                         @if ($customField->field_type == 'number')
                         <input type="number" class="form-control required{{ $customField->required }}" id="{{$customField->field_name}}-number" name="customFields[{{$customField->custom_field_id}}][value]" value="{{old('customFields.'.$customField->custom_field_id.'.value')}}" {{ $customField->required == 1 ? 'required' : '' }}/>
                         @endif
                         @if ($customField->field_type == 'select')
                         <select class="select2 form-select form-select-lg required{{ $customField->required }}" id="{{$customField->custom_field_id }}-customFields-option" name="customFields[{{$customField->custom_field_id}}][value]" {{ $customField->required == 1 ? 'required' : '' }}>
                             <option value="">-Select-</option>
                             @foreach (explode(',', $customField->field_default_value) as $currency)
                             <option value="{{ $currency }}" {{old('customFields.'.$customField->custom_field_id.'.value') == $currency ? 'selected' : ''}}>{{ $currency }}</option>
                             @endforeach
                         </select>
                         @endif
                         @if ($customField->field_type == 'currency')
                         <select class="select2 form-select form-select-lg required{{ $customField->required }}" name="customFields[{{$customField->custom_field_id}}][value]" {{ $customField->required == 1 ? 'required' : '' }}>
                             <option value="">-Select-</option>
                             @foreach (currency() as $currency)
                             <option value="{{ $currency }}" {{old('customFields.'.$customField->custom_field_id.'.value') == $currency ? 'selected' : ''}}>{{ $currency }}</option>
                             @endforeach
                         </select>
                         @endif
                         @if ($customField->field_type == 'tablename')
                         <select class="select2 form-select form-select-lg required{{ $customField->required }}" multiple name="customFields[{{$customField->custom_field_id}}][value][]" {{ $customField->required == 1 ? 'required' : '' }}>
                             <option value="">-Select-</option>
                             @php
                                $tableName = explode(',',$customField->field_default_value);
                                $tablePrimary = $tableName[1];
                                $tableText = $tableName[2];
                                $tabledata = get_table_data($tableName[0], $tableName[2]);
                             @endphp
                             @foreach ($tabledata as $tdata)
                             <option value="{{ $tdata->$tablePrimary }}" {{old('customFields.'.$customField->custom_field_id.'.value') == $tdata->$tablePrimary ? 'selected' : ''}}>{{ $tdata->$tableText }}</option>
                             @endforeach
                         </select>
                         @endif
                     </div>
                </div>
             @endif
         @endforeach
      </div>
</div>