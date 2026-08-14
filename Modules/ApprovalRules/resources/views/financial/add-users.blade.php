@php
$appTypeArr = $appType != '' ? $appType : 0;

$defVal['apprDesgUsr'] = $defVal['apprDesgUsr'] ?? '';
$defVal['apprNameUsr'] = $defVal['apprNameUsr'] ?? '';
$defVal['apprRole'] = $defVal['apprRole'] ?? 'Approver';

@endphp
<div class="user_row_{{$index}} repeater mt-3" data-row-sel="{{$index}}">
          <div class="row" style="" id="">
            <div class="col-2">
               <select class="form-select approval_user_type defaultValRowSetter{{$appType}} apprUsrTyp" name="approval_required_user_type{{$appType}}[]" data-row-sel="{{ $index }}" data-row-type="{{$appType}}" data-row-inpt="apprUsrTyp">
                <option value="name" {{($defVal['apprUsrTyp'] ?? '') == 'name' ? 'selected' : ''}}>By Name</option>
                <option value="designation" {{($defVal['apprUsrTyp'] ?? '') == 'designation' ? 'selected' : ''}}>By Designation</option>
              </select>                                
            </div>
            <div class="col-2">
                <label class="form-label">Role</label>
                <select class="form-select approval_role defaultValRowSetter{{$appType}} apprRole" name="approval_required_role{{$appType}}[]" data-row-inpt="apprRole">
                    <option value="Approver" {{ ($defVal['apprRole'] ?? 'Approver') == 'Approver' ? 'selected' : '' }}>Approver</option>
                    <option value="Verifier" {{ ($defVal['apprRole'] ?? '') == 'Verifier' ? 'selected' : '' }}>Verifier</option>
                    <option value="Preapprover" {{ ($defVal['apprRole'] ?? '') == 'Preapprover' ? 'selected' : '' }}>Pre Approver</option>
                    <option value="Signatory" {{ ($defVal['apprRole'] ?? '') == 'Signatory' ? 'selected' : '' }}>Signatory</option>
                </select>
            </div>
            <div class="col {{($defVal['apprUsrTyp'] ?? 'name') == 'designation' ? '' : 'd-none'}} by_name_desg_{{$index}} by_designation_{{$index}}">
               <select class="form-select user_type defaultValRowSetter{{$appType}} apprDesgUsr" name="approval_required_desg{{$appType}}[]" data-row-sel="{{ $index }}" data-row-type="{{$appType}}" data-row-inpt="apprDesgUsr">
                <option value="branch_head" {{ $defVal['apprDesgUsr'] == 'branch_head' ? 'selected' : ''}}>Branch Head</option>
                <option value="branch_dep_head" {{ $defVal['apprDesgUsr'] == 'branch_dep_head' ? 'selected' : ''}}>Branch Dept Head</option>
                <option value="overall_dept_head" {{ $defVal['apprDesgUsr'] == 'overall_dept_head' ? 'selected' : ''}}>Over All Dept Head</option>
              </select>                                
            </div>
            <div class="col {{($defVal['apprUsrTyp'] ?? 'name') == 'name' ? '' : 'd-none'}} select_users by_name_desg_{{$index}} by_name_{{$index}}">
              <select class="form-select users approval_user defaultValRowSetter{{$appType}} apprNameUsr" aria-label="select example" id="approval_required_users_{{$appType}}_{{$index}}" name="approval_required_users{{$appType}}[]" data-row-sel="{{ $index }}" data-row-type="{{$appType}}" data-row-inpt="apprNameUsr">
                <option value="">Select Approver</option>
                @foreach($add_users as $add_users_data)
                <option value="{{ $add_users_data->id }}:{{ $add_users_data->FirstName }}:{{$add_users_data->Email}}" {{ $defVal['apprNameUsr'] == $add_users_data->id.":".$add_users_data->FirstName.":".$add_users_data->Email ? 'selected' : '' }}>{{ $add_users_data->FirstName." ".$add_users_data->LastName."(".$add_users_data->Email.")" }}</option>
                @endforeach
              </select>
            </div>
          <div class="col user_row_operation select_users_btn" style="">
            <a href="javascript:;" class="nav-link btn justify-content-center btn-delete" data-mode="no_approve">
              <div class="badge bg-label-danger rounded p-2">
                <i class="ti ti-minus ti-sm"></i>
              </div>
            </a>
          </div>
          </div>
        </div>