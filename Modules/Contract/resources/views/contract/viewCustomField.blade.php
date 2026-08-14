 

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

<div class="clearfix">
 
    <div class="row"> 

        @foreach ($customFields as $customField)

        @if ($customField->category == $categoryId)
             @if ($customField->field_type == 'date')
        <div class="col-sm-6 mt-3 datepikr groupby groupby-{{$customField->contract_type}} {{ !empty($customField->is_generic) ? 'groupby-generic' : '' }}">
            @else
        <div class="col-sm-6 mt-3 groupby groupby-{{$customField->contract_type}} {{ !empty($customField->is_generic) ? 'groupby-generic' : '' }}">
            @endif
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
                <input type="text" disabled class="form-control required{{ $customField->required }}" id="{{$customField->field_name}}-text" name="customFields[{{$customField->custom_field_id}}][value]">
                @endif
                
                @if ($customField->field_type == 'textarea')
                <textarea disabled class="form-control required{{ $customField->required }}" id="{{$customField->field_name}}-text" name="customFields[{{$customField->custom_field_id}}][value]"></textarea>
                @endif
                
                
                
                @if ($customField->field_type == 'date')
                <input type="text" disabled class="form-control flatpickr required{{ $customField->required }}" id="{{$customField->field_name}}-date" name="customFields[{{$customField->custom_field_id}}][value]">
                @endif
                @if ($customField->field_type == 'number')
                <input type="number" disabled class="form-control required{{ $customField->required }}" id="{{$customField->field_name}}-number" name="customFields[{{$customField->custom_field_id}}][value]">
                @endif
                @if ($customField->field_type == 'select')
                <select disabled class="select2 form-select form-select-lg required{{ $customField->required }}" id="{{$customField->custom_field_id }}-customFields-option" name="customFields[{{$customField->custom_field_id}}][value]">
                    <option value="">-Select-</option>
                    @foreach (explode(',', $customField->field_default_value) as $currency)
                    <option value="{{ $currency }}">{{ $currency }}</option>
                    @endforeach
                </select>
                @endif
                @if ($customField->field_type == 'currency')
                <select disabled class="select2 form-select form-select-lg required{{ $customField->required }}"  name="customFields[{{$customField->custom_field_id}}][value]">
                    <option value="">-Select-</option>
                    @foreach (currency() as $currency)
                    <option value="{{ $currency }}">{{ $currency }}</option>
                    @endforeach
                </select>
                @endif
            </div>
        </div>
        @endif
        @endforeach
    </div>
</div>