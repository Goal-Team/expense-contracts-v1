<div class="clearfix">
    <h3>
        Custom Fields
    </h3>
    <div class="row"> 

        @foreach ($customFields as $customField)

        @if ($customField->category == $categoryId)
        <div class="col-sm-6">
            <div class="form-group required-{{ $customField->required }}">
                <label for="Currencycontract">                     
                    {{ $customField->field_name }}                    
                    @if(isset($customField->required) && $customField->required == 1)
                    <span class="text-danger">*</span>
                    @endif
                </label> 

                <input type="hidden" value="{{ $customField->field_name}}" name="customFields[{{$customField->custom_field_id}}][label]">

                <input type="hidden" value="{{ $customField->custom_field_id }}" name="customFields[{{$customField->custom_field_id}}][id]">

                @if ($customField->field_type == 'text')
                <input type="text" class="form-control required{{ $customField->required }}" id="{{$customField->field_name}}-text" name="customFields[{{$customField->custom_field_id}}][value]">
                @endif
                @if ($customField->field_type == 'date')
                <input type="date" class="form-control required{{ $customField->required }}" id="{{$customField->field_name}}-date" name="customFields[{{$customField->custom_field_id}}][value]">
                @endif
                @if ($customField->field_type == 'number')
                <input type="number" class="form-control required{{ $customField->required }}" id="{{$customField->field_name}}-number" name="customFields[{{$customField->custom_field_id}}][value]">
                @endif
                @if ($customField->field_type == 'select')
                <select class="form-control required{{ $customField->required }}" id="{{$customField->field_name}}-option" name="customFields[{{$customField->custom_field_id}}][value]">
                    <option value="">-Select-</option>
                    @foreach (explode(',', $customField->field_default_value) as $currency)
                    <option value="{{ $currency }}">{{ $currency }}</option>
                    @endforeach
                </select>
                @endif
                @if ($customField->field_type == 'currency')
                <select class="form-control required{{ $customField->required }}"  name="customFields[{{$customField->custom_field_id}}][value]">
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