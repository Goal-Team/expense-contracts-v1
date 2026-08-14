{{--
    Custom fields (create) — V2 / optimised.

    Differences vs. createCustomField.blade.php:
      * The stray @section('vendor-style') block has been removed. It re-declared the
        parent page's vendor-style section from inside an @include, which meant the page's
        own vendor styles were silently replaced by this partial's list.
      * Iterates only the fields for $categoryId instead of walking the whole $customFields
        collection once per include (this partial is included four times per page).

    Expected variables:
      $categoryId              the custom field category being rendered
      $customFieldsByCategory  $customFields grouped by ->category (optional; falls back
                               to filtering $customFields when absent)
--}}
@php
    $fieldsForCategory = isset($customFieldsByCategory)
        ? ($customFieldsByCategory[$categoryId] ?? [])
        : collect($customFields)->where('category', $categoryId);
@endphp

<div class="customFieldTitleSection customFieldTitleSection_{{$categoryId}}" style="display: none;">
    <h6 class="mt-4">Custom Fields</h6>
    <hr class="mt-0" />
</div>
<div class="clearfix">
     <div class="row">
         @foreach ($fieldsForCategory as $customField)
             @php $oldValue = old('customFields.'.$customField->custom_field_id.'.value'); @endphp
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
                     <input type="text" class="form-control required{{ $customField->required }}" id="{{$customField->field_name}}-text" name="customFields[{{$customField->custom_field_id}}][value]" value="{{$oldValue}}" {{ $customField->required == 1 ? 'required' : '' }}>
                     @endif

                     @if ($customField->field_type == 'textarea')
                     <textarea class="form-control required{{ $customField->required }}" id="{{$customField->field_name}}-text" name="customFields[{{$customField->custom_field_id}}][value]" {{ $customField->required == 1 ? 'required' : '' }}>{{$oldValue}}</textarea>
                     @endif

                     @if ($customField->field_type == 'date')
                     <input type="text" class="form-control flatpickr required{{ $customField->required }}" id="{{$customField->field_name}}-date" name="customFields[{{$customField->custom_field_id}}][value]" value="{{$oldValue}}" {{ $customField->required == 1 ? 'required' : '' }}>
                     @endif

                     @if ($customField->field_type == 'number')
                     <input type="number" class="form-control required{{ $customField->required }}" id="{{$customField->field_name}}-number" name="customFields[{{$customField->custom_field_id}}][value]" value="{{$oldValue}}" {{ $customField->required == 1 ? 'required' : '' }}/>
                     @endif

                     @if ($customField->field_type == 'select')
                     <select class="select2 form-select form-select-lg required{{ $customField->required }}" id="{{$customField->custom_field_id }}-customFields-option" name="customFields[{{$customField->custom_field_id}}][value]" {{ $customField->required == 1 ? 'required' : '' }}>
                         <option value="">-Select-</option>
                         @foreach (explode(',', $customField->field_default_value) as $currency)
                         <option value="{{ $currency }}" {{$oldValue == $currency ? 'selected' : ''}}>{{ $currency }}</option>
                         @endforeach
                     </select>
                     @endif

                     @if ($customField->field_type == 'currency')
                     <select class="select2 form-select form-select-lg required{{ $customField->required }}" name="customFields[{{$customField->custom_field_id}}][value]" {{ $customField->required == 1 ? 'required' : '' }}>
                         <option value="">-Select-</option>
                         @foreach (currency() as $currency)
                         <option value="{{ $currency }}" {{$oldValue == $currency ? 'selected' : ''}}>{{ $currency }}</option>
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
                         <option value="{{ $tdata->$tablePrimary }}" {{$oldValue == $tdata->$tablePrimary ? 'selected' : ''}}>{{ $tdata->$tableText }}</option>
                         @endforeach
                     </select>
                     @endif
                 </div>
            </div>
         @endforeach
      </div>
</div>
