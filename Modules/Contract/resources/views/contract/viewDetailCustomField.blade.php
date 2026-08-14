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
<div class="customFieldTitleSection customFieldTitleSection_{{$categoryId}}">
    <h6 class="mt-2">Custom Fields</h6>
    <hr class="mt-0" />
</div>
@php
$showTitle = 0;
@endphp
<div class="clearfix">
    <div class="row">
        @foreach ($customFields as $customField)
            @if ($customField->category == $categoryId)
            @php
            if($customField->contract_type == $contract->contract_type_id){
            
                $showTitle++;
            }
            @endphp             
                <div class="col-sm-6 mt-2 {{ $customField->field_type == 'date' ? 'datepikr' : ' ' }} groupby groupby-{{$customField->contract_type}} {{ !empty($customField->is_generic) ? 'groupby-generic' : '' }}" data-catet="{{$categoryId}}" data-count="1">
                    <div class="form-group required-{{ $customField->required }}">
                        <label for="Currencycontract">
    
                            {{ decryptString($customField->field_name, 'typename') }}
    
                            @if(isset($customField->required) && $customField->required == 1)
                            <span class="text-danger">*</span>
                            @endif
                        </label>
    
    
    
    
                        <input type="hidden" value="{{ decryptString($customField->field_name, 'typename') }}" name="customFields[{{$customField->custom_field_id}}][label]">
    
                        <input type="hidden" value="{{ $customField->custom_field_id }}" name="customFields[{{$customField->custom_field_id}}][id]">
    
    
    
                        @if ($customField->field_type == 'text')
                        <input type="text" class="form-control required{{ $customField->required }}" id="{{decryptString($customField->field_name, 'typename')}}-text" name="customFields[{{$customField->custom_field_id}}][value]" disabled value="{{dataCustomFields($contract->id, $customField->custom_field_id)}}">
                        @endif
    
                        @if ($customField->field_type == 'textarea')
                        <textarea class="form-control required{{ $customField->required }}" id="{{decryptString($customField->field_name, 'typename')}}-text" name="customFields[{{$customField->custom_field_id}}][value]" disabled>{{dataCustomFields($contract->id, $customField->custom_field_id)}}
                        </textarea>
                        @endif
    
    
    
                        @if ($customField->field_type == 'date')
                        <input disabled type="text" class="form-control flatpickr required{{ $customField->required }}" id="{{decryptString($customField->field_name, 'typename')}}-date" name="customFields[{{$customField->custom_field_id}}][value]" value="{{dataCustomFields($contract->id, $customField->custom_field_id)}}">
                        @endif
                        @if ($customField->field_type == 'number')
                        <input type="number" class="form-control required{{ $customField->required }}" id="{{decryptString($customField->field_name, 'typename')}}-number" name="customFields[{{$customField->custom_field_id}}][value]" disabled value="{{dataCustomFields($contract->id, $customField->custom_field_id)}}">
                        @endif
                        @if ($customField->field_type == 'select')
                        <select class="select2 form-select form-select-lg required{{ $customField->required }}" id="{{$customField->custom_field_id }}-customFields-option" name="customFields[{{$customField->custom_field_id}}][value]" disabled>
                            <option value="">-Select-</option>
                            @foreach (explode(',', $customField->field_default_value) as $currency)
                            @if(dataCustomFields($contract->id, $customField->custom_field_id) == $currency)
                            <option value="{{ $currency }}" selected>{{ $currency }}</option>
                            @else
                            <option value="{{ $currency }}">{{ $currency }}</option>
                            @endif
                            @endforeach
                        </select>
                        @endif
                        @if ($customField->field_type == 'currency')
                        <select class="select2 form-select form-select-lg required{{ $customField->required }}" id="{{$customField->custom_field_id }}-customFields-currency" name="customFields[{{$customField->custom_field_id}}][value]" disabled>
                            <option value="">-Select-</option>
                            @foreach (currency() as $currency)
                            @if(dataCustomFields($contract->id, $customField->custom_field_id) == $currency)
                            <option value="{{ $currency }}" selected>{{ $currency }} </option>
                            @else
                            <option value="{{ $currency }}">{{ $currency }}</option>
                            @endif
                            @endforeach
                        </select>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
        </div>
    </div>
    @if($showTitle == 0 )
    <style>
      .customFieldTitleSection_{{$categoryId}}{
          display: none;
      }  
    </style>
    @endif    