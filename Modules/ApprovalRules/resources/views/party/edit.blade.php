@extends('layouts/layoutMaster')

@section('title', 'Edit Party Approver Rules')

<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/quill/typography.scss',
'resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/quill/editor.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/dropzone/dropzone.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/tagify/tagify.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite([  
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])

<!-- Page Scripts -->
@section('page-script')
@vite([
])

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />

@endsection

  @section('content')
  
    
    <div class="row my-4">
    
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
           <div class="d-flex flex-column justify-content-center">
              <h4 class="mb-1 mt-3">Edit Party Approver Rules</h4>
           </div>
           <div class="d-flex align-content-center flex-wrap gap-3">
              <div class="d-flex gap-3">
                 <a href="{{url('/')}}/contract-setup/party-approval-rules" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
              </div>
           </div>
        </div>
      <div class="row align-items-center">
            <div class="col-md-12">
                <div class="">
                    <!-- <h5 class="card-title">Entity Details</h5> -->
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <b>{{ $message }}</b>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
      
      
        <div class="col">
          <form class="row" id="financial_form" action="{{url('/')}}/contract-setup/party-approval-edit/{{ $financial->id }}" method="POST" enctype="multipart/form-data">
            @csrf
            
                <div class="col-md mb-4 mb-md-2">
                    <div class="accordion mt-3" id="accordionWithIcon">
                        <div class="accordion-item card mt-4 active">
                          <h2 class="accordion-header d-flex align-items-center">
                             <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-1" aria-expanded="true">
                             IF
                             </button>
                          </h2>
                          <div id="accordionWithIcon-1" class="accordion-collapse collapse show">
                             <div class="accordion-body">
                                <hr class="mt-1" />
                                <div class="row g-3 mt-3">
                                    
                                    <div class="row">
                                           <input type="hidden" name="financial_id" value="{{ $financial->id }}" />
                                           <input type="hidden" id="approval_status" value="{{ $financial->approval_status }}" />
                                           <div class="col-md-6 financial_form mt-2">
                                              <label for="location" class="select2 form-label required">Location</label>
                                             
                                               <select class="form-select select2 financial_form"
                                                 name="location" id="location" required>
                                                <option value="0">Any / All</option>
                                                  @foreach ($branch as $branch_data)
                                                        <option value="{{ $branch_data->id }}"
                                                            {{ $financial->branch == $branch_data->id ? 'selected' : '' }}>
                                                            {{ $branch_data->BranchName }}</option>
                                                    @endforeach
                                              </select>
                                            </div>
                                            <div class="col-md-6 financial_form mt-2">
                                              <label for="access_level" class="form-label required">Access Level</label>
                                                <select class="form-select select2" aria-label="select example" id="access_level" name="access_level">
                                                  <option value="">Select Access Level</option>
                                                  @foreach($geo_graph as $geo)
                                                      <option value="{{$geo->id}}" {{ $geo->id == $financial->accesslevel ? 'selected' : '' }}>{!! $geo->tname !!}</option>
                                                  @endforeach
                                                </select>                                                
                                            </div>
                                        </div>
                                </div>
                             </div>
                          </div>
                       </div>
                        <div class="accordion-item card mt-4">
                          <h2 class="accordion-header d-flex align-items-center">
                             <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-2" aria-expanded="false">
                             THEN
                             </button>
                          </h2>
                            <div id="accordionWithIcon-2" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    <hr class="mt-1" />
                                    <div class="row g-3">
                                        <div class="card-body">
                                            <div class="row">
                                                <input type="hidden" name="approval_status" id="required" value="{{old('approval_status', $financial->approval_status)}}" />
                                                <div class="row add_users" style="margin-top: 30px; display: {{ ($financial->approval_status == 'required') ? 'block':'none'}}">


                                                    @php 
                                                        $approval_required_users = json_decode($financial->approval_required_users) ?? []; 
                                                    @endphp
                                                    <input type="hidden" id="user_position"
                                                        value="{{ (count($approval_required_users) == 0) ? 1 : count($approval_required_users) }}" />                                                    
                                                    @if ($financial->approval_status == 'required')
                                                    @foreach ($approval_required_users as $key => $approval_required_users_data)
                                                    @php
                                                    $approval_required_users_data->type = $approval_required_users_data->type ?? 'name';
                                                    @endphp
                                                    <div class="user_row_{{ $key + 1 }} repeater {{ $key > 0 ? 'mt-3': '' }}">
                                                        <div class="row" id="">
                                                            <div class="col-2">
                                                               <select class="form-select approval_user_type" name="approval_required_user_type[]" data-row-sel="{{ $key + 1 }}">
                                                                <option value="name" {{$approval_required_users_data->type == 'name' ? 'selected' : ''}}>By Name</option>
                                                                <option value="designation" {{$approval_required_users_data->type == 'designation' ? 'selected' : ''}}>By Designation</option>
                                                              </select>                                
                                                            </div>
                                                            <div class="col {{ $approval_required_users_data->type == 'name' ? 'd-none' : '' }} by_name_desg_{{ $key + 1 }} by_designation_{{ $key + 1 }}">
                                                               <select class="form-select user_type" name="approval_required_desg[]">
                                                                <option value="branch_head" {{$approval_required_users_data->name == 'branch_head' ? 'selected' : ''}}>Branch Head</option>
                                                                <option value="branch_dep_head" {{$approval_required_users_data->name == 'branch_dep_head' ? 'selected' : ''}}>Branch Dept Head</option>
                                                                <option value="overall_dept_head" {{$approval_required_users_data->name == 'overall_dept_head' ? 'selected' : ''}}>Over All Dept Head</option>
                                                              </select>                                
                                                            </div>                                                            
                                                            <div class="col select_users {{ $approval_required_users_data->type == 'designation' ? 'd-none' : '' }} by_name_desg_{{ $key + 1 }} by_name_{{ $key + 1 }}">
                                                                <select class="form-select users approval_user"
                                                                    data-id="{{ $key + 1 }}" aria-label="select example"
                                                                    id="approval_required_users_{{ $key + 1 }}"
                                                                    name="approval_required_users[]">
                                                                    <option value="">Select Approver</option>
                                                                    @foreach ($add_users as $add_users_data)
                                                                    <option
                                                                        value="{{ $add_users_data->id }}:{{ $add_users_data->FirstName }}:{{$add_users_data->Email}}"
                                                                        {{ $approval_required_users_data->id == $add_users_data->id ? 'selected' : '' }}>
                                                                        {{ $add_users_data->FirstName." ".$add_users_data->LastName."(".$add_users_data->Email.")" }}
                                                                    </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col user_row_operation select_users_btn">
                                                                <a href="javascript:;" class="nav-link btn justify-content-center btn-delete"
                                                                    style="font-size: 12px;color: #fff !important;cursor: pointer;">

                                                                    <div class="badge bg-label-danger rounded p-2">

                                                                        <i class="ti ti-minus me-1"></i>
                                                                    </div>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                    @else
                                                    <div class="user_row_1 repeater">
                                                      <div class="row" style="" id="">
                                                        <div class="col-2">
                                                           <select class="form-select approval_user_type" name="approval_required_user_type[]" data-row-sel="1">
                                                            <option value="name">By Name</option>
                                                            <option value="designation">By Designation</option>
                                                          </select>                                
                                                        </div>
                                                        <div class="col d-none by_name_desg_1 by_designation_1">
                                                           <select class="form-select user_type" name="approval_required_desg[]">
                                                            <option value="branch_head">Branch Head</option>
                                                            <option value="branch_dep_head">Branch Dept Head</option>
                                                            <option value="overall_dept_head">Over All Dept Head</option>
                                                          </select>                                
                                                        </div>
                                                        <div class="col select_users by_name_desg_1 by_name_1">
                                                          <select class="form-select users approval_user" aria-label="select example" id="approval_required_users_1" name="approval_required_users[]">
                                                            <option value="">Select Approver</option>
                                                            @foreach($add_users as $add_users_data)
                                                            <option value="{{ $add_users_data->id }}:{{ $add_users_data->FirstName }}:{{$add_users_data->Email}}">{{ $add_users_data->FirstName." ".$add_users_data->LastName."(".$add_users_data->Email.")" }}</option>
                                                            @endforeach
                                                          </select>
                                                        </div>
                                                        <div class="col select_users_btn">
                                                          <a href="javascript:;" class="nav-link btn justify-content-center user_add_row" data-mode="no_approve">
                                                            <div class="badge bg-label-success rounded p-2">
                                                              <i class="ti ti-plus ti-sm"></i>
                                                            </div>
                                                          </a>
                                                        </div>
                                                      </div>
                                                    </div>                                                    
                                                    @endif
                                                </div>
                                            </div>
                                        </div><!-- end card-body -->
                                    </div>
                                </div>
                            </div>
                    </div>
                </div>
                
                <div class="buy-now">
                  <!--<a href="https://1.envato.market/vuexy_admin" target="_blank" class="btn btn-primary btn-buy-now waves-effect waves-light">Submit</a>-->
                  
                   <button type="submit" class="btn-buy-now btn btn-primary me-sm-3 me-1 waves-effect waves-light">Submit</button>
                </div>
    

            <!--  <div class="col-md-12 mt-4" style="text-align: right;">-->
            <!--  <button type="submit" id="financial_save" class="btn btn-success">Submit</button>-->
            <!--  <button type="button" class="btn btn-primary"><a href="/contractsdemo/contract-setup/approval-rules" style="color: #FFF;text-decoration: none;">Cancel</a></button>-->
            <!--</div>-->
          </form>
        </div>
    </div>

     
    </div>
    <!--<script  type="module" src="{{ asset('assets/js/jquery-1.10.2.js') }}"></script>-->
    <script type="module" src="{{url('/')}}/Modules/ApprovalRules/resources/assets/js/jquery-1.10.2.js"></script>
    <script type="module" src="{{url('/')}}/Modules/ApprovalRules/resources/assets/js/script.js"></script>
    
    <script type="module">
        $(document).ready(function() {
            $('.users,#location,#category,#contract_type,#department').select2();
            $('.user_row_operation a:first').remove();
            // $('.user_row_operation').first().prepend(
            //     '<a class="btn-success user_add_row"  data-mode="no_approve" style="font-size: 12px;color: #fff !important;cursor: pointer;"><i class="ti ti-plus me-1"></i></a>'
            //     );
            $('.user_row_operation').first().prepend(
                '<a class="nav-link btn justify-content-center user_add_row"  data-mode="no_approve" style="font-size: 12px;color: #fff !important;cursor: pointer;"><div class="badge bg-label-success rounded p-2"><i class="ti ti-plus ti-sm"></i></div></div></a>'
                );

        });
    </script>
    
 @endsection  
   