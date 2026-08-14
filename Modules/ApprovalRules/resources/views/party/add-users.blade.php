  <div class="user_row_{{$index}} repeater mt-3">
    <div class="row" style="" id="">
        <div class="col-2">
           <select class="form-select approval_user_type" name="approval_required_user_type[]" data-row-sel="{{ $index }}">
            <option value="name" selected>By Name</option>
          </select>                                
        </div>
      <div class="col select_users by_name_desg_{{ $index }} by_name_{{$index}}">
        <select class="form-select users approval_user" data-id="{{$index}}" id="approval_required_users_{{$index}}" name="approval_required_users[]">
          <option value="">Select Approver</option>
          @foreach($add_users as $add_users_data)
          <option value="{{ $add_users_data->id }}:{{ $add_users_data->FirstName }}:{{$add_users_data->Email}}">{{ $add_users_data->FirstName." ".$add_users_data->LastName."(".$add_users_data->Email.")" }}</option>
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