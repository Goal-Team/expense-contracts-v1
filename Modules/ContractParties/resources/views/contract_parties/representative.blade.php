<hr style="margin-top: 15px;" class="representative_row_{{$index+1}}">
<div class="col-md-12 representative_row_{{$index+1}}" style="text-align: right;"><a id="{{$index+1}}" class="btn btn-danger representative_delete_row" data-index="{{$index+1}}"  style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-minus me-1"></i> Delete </a></div>
<div class="col-md-6 representative_row_{{$index+1}}">
    <label for="representative_name" class="form-label required">Representative Name</label>
    <input type="hidden" name="representative[{{$index}}][representative_id]" value="" />
    <input type="text" class="form-control" required name="representative[{{$index}}][representative_name]" />
</div>
<div class="col-md-6 representative_row_{{$index+1}}">
    <label for="representative_email" class="form-label">Email ID</label>
    <input type="email" class="form-control representative_email" id="email_{{$index+1}}" name="representative[{{$index}}][representative_email]" />
    <div class="invalid-feedback">Email is invalid</div>
</div>
<div class="col-md-6 representative_row_{{$index+1}} unRequiredFields">
    <label for="representative_designation" class="form-label">Designation</label>
    <input type="text" class="form-control" name="representative[{{$index}}][representative_designation]"/>
</div>
<div class="col-md-3 representative_row_{{$index+1}} unRequiredFields">
    <label for="representative_contact" class="form-label">Contact Number</label>
    <input type="text" class="form-control numberonly" name="representative[{{$index}}][representative_contact]" maxlength="10"/>
</div>
<div class="col-md-3 representative_row_{{$index+1}} unRequiredFields">
    <label for="representative_nationality" class="form-label">Nationality</label>
    <input type="text" class="form-control" name="representative[{{$index}}][representative_nationality]" />
</div>
<div class="col-md-6 representative_row_{{$index+1}} international-only" style="display:none;">
    <label for="representative_passport" class="form-label required">Passport Number <i class="ti ti-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Passport number is required for international representatives."></i></label>
    <input type="text" class="form-control" name="representative[{{$index}}][passport_number]" />
</div>
<div class="col-md-6 representative_row_{{$index+1}}">
    <label for="representative_nationality" class="form-label">Board Resolution File</label>
    <input type="file" class="form-control" name="representative[{{$index}}][representative_brs]" />
</div>