<hr style="margin-top: 15px;" class="escalation_row_{{$index+1}}">
<div class="col-md-12 escalation_row_{{$index+1}}" style="text-align: right;"><a id="{{$index+1}}" class="btn btn-danger escalation_delete_row" data-index="{{$index+1}}"  style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-minus me-1"></i> Delete </a></div>
<div class="col-md-6 escalation_row_{{$index+1}}">
    <label for="escalation_name" class="form-label required">Name</label>
    <input type="text" class="form-control" required name="escalation[{{$index}}][name]" value="{{$name ?? ''}}" />
</div>
<div class="col-md-6 escalation_row_{{$index+1}}">
    <label for="escalation_designation" class="form-label required">Designation</label>
    <input type="text" class="form-control" required name="escalation[{{$index}}][designation]" value="{{$designation ?? ''}}" />
</div>