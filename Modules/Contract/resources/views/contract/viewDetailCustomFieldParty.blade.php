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
        <div class="col-sm-6 mt-3 datepikr">
            @else
            <div class="col-sm-6 mt-3">
                @endif
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
                    <input type="text" class="form-control required{{ $customField->required }}" id="{{decryptString($customField->field_name, 'typename')}}-text" name="customFields[{{$customField->custom_field_id}}][value]" disabled value="{{dataCustomFieldsParty($parties->id, $customField->custom_field_id)}}">
                    @endif

                    @if ($customField->field_type == 'textarea')
                    <textarea class="form-control required{{ $customField->required }}" id="{{decryptString($customField->field_name, 'typename')}}-text" name="customFields[{{$customField->custom_field_id}}][value]" disabled>{{dataCustomFieldsParty($parties->id, $customField->custom_field_id)}}
                    </textarea>
                    @endif



                    @if ($customField->field_type == 'date')
                    <input disabled type="text" class="form-control flatpickr required{{ $customField->required }}" id="{{decryptString($customField->field_name, 'typename')}}-date" name="customFields[{{$customField->custom_field_id}}][value]" value="{{dataCustomFieldsParty($parties->id, $customField->custom_field_id)}}
">
                    @endif
                    @if ($customField->field_type == 'number')
                    <input type="number" class="form-control required{{ $customField->required }}" id="{{decryptString($customField->field_name, 'typename')}}-number" name="customFields[{{$customField->custom_field_id}}][value]" disabled value="{{dataCustomFieldsParty($parties->id, $customField->custom_field_id)}}
">
                    @endif
                    @if ($customField->field_type == 'select')
                    <select class="select2 form-select form-select-lg required{{ $customField->required }}" id="{{$customField->custom_field_id }}-customFields-option" name="customFields[{{$customField->custom_field_id}}][value]" disabled>
                        <option value="">-Select-</option>
                        @foreach (explode(',', $customField->field_default_value) as $currency)
                        @if(dataCustomFieldsParty($parties->id, $customField->custom_field_id) == $currency)
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
                        @if(dataCustomFieldsParty($parties->id, $customField->custom_field_id) == $currency)
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