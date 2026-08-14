@extends('layouts/layoutMaster')
@section('title', ' Contracts Obligations')
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
'resources/assets/vendor/libs/quill/katex.js',
'resources/assets/vendor/libs/tagify/tagify.js',
'resources/assets/vendor/libs/quill/quill.js',
'resources/assets/vendor/libs/cleavejs/cleave.js',
'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
'resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/dropzone/dropzone.js',
'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])

@endsection
<!-- Page Scripts -->
@section('page-script')

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/jquery-ui.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/obligations.js"></script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Obligations /</span> List
</h4>

    <div class="card mb-4">
  <div class="card-widget-separator-wrapper">
    <div class="card-body card-widget-separator">
      <div class="row gy-4 gy-sm-1">
          
            <div class="col-sm-6 col-lg-4">
                
                 <a href="?status=pending" <?php if (!isset($_GET['status'])) {
                  echo 'class="act"';
                  } ?> <?php if (isset($_GET['status']) && $_GET['status'] == 'pending') {
                  echo 'class="act"';
                  } ?>>
                      <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-3 pb-sm-0">
                            <div>
                                
                              <h3 class="mb-1" id="count_pending"></h3>
                              <p class="mb-0">Pending</p>
                            </div>
                            <span class="avatar me-sm-4">
                              <span class="avatar-initial bg-label-secondary rounded">
                                  <i class="ti ti-file-invoice ti-md"></i>
                              </span>
                            </span>
                      </div>
                </a>
          <hr class="d-none d-sm-block d-lg-none me-4">
        </div>
        
            <div class="col-sm-6 col-lg-4">
                
                <a href="?status=completed" <?php if (!isset($_GET['status'])) {
                  echo 'class="act"';
                  } ?> <?php if (isset($_GET['status']) && $_GET['status'] == 'completed') {
                  echo 'class="act"';
                  } ?>>
                  <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-3 pb-sm-0">
                    <div>
                      <h3 class="mb-1" id="count_completed"></h3>
                      <p class="mb-0">Completed</p>
                    </div>
                    <span class="avatar me-lg-4">
                      <span class="avatar-initial bg-label-secondary rounded"><i class="ti ti-checks ti-md"></i></span>
                      <!--<i class="ti ti-checks ti-md"></i>-->
                      <!--<i class="ti ti-file-invoice ti-md"></i>-->
                    </span>
                  </div>
                </a>
          <hr class="d-none d-sm-block d-lg-none">
        </div>

          
            <div class="col-sm-6 col-lg-4">
                
                <a href="?status=inprogress" <?php if (!isset($_GET['status'])) {
                  echo 'class="act"';
                  } ?> <?php if (isset($_GET['status']) && $_GET['status'] == 'inprogress') {
                  echo 'class="act"';
                  } ?>>
                  <div class="d-flex justify-content-between align-items-start border-end pb-3 pb-sm-0 card-widget-3">
                    <div>
                      <h3 class="mb-1" id="count_inprogress"></h3>
                      <p class="mb-0">Inprogress</p>
                    </div>
                    <span class="avatar me-sm-4">
                      <span class="avatar-initial bg-label-secondary rounded">
                          <i class="ti ti-progress ti-md"></i>
                      </span>
                    </span>
                  </div>
                 </a>
        </div>
      </div>
    </div>
  </div>
</div>
  <div class="card">
  <div class="card-header pull-right">
    <button class="btn btn-label-linkedin waves-effect waves-light" data-bs-toggle="offcanvas" data-bs-target="#addPaymentOffcanvas">
    <span class="d-flex align-items-center justify-content-center text-nowrap">
      <i class="ti ti-plus ti-xs me-1"></i>Add Obligation</span>
    </button> 
  </div>
  <div class="table-responsive text-nowrap">
    <table class="table">
      <thead>
        <tr>
          <th>Task Name</th>
          <th>Priority</th>
          <th>End Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
       
      @foreach($ContractObligations as $index => $task)

        <tr data-id="{{ $task }}">
          <td class ="task_name"> {{ $task->obligation_name }}</td>
          <td class = "task_priority">{{$task->priority}}</td>
          <td class ="task_end_date">
          {{ $task->due_date}}
          </td>
          @if($task->status == 'Pending')
            <td class ="task_status"><span class="badge bg-label-primary me-1">{{ $task->status }}</span></td>
          @endif

          @if($task->status == 'Completed')
            <td class = "task_status"><span class="badge bg-label-success me-1">{{ $task->status }}</span></td>
          @endif

          @if($task->status == 'Inprogress')
            <td class = "task_status"><span class="badge bg-label-warning me-1">{{ $task->status }}</span></td>
          @endif
          <td>
            <div class="dropdown">
              <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                <i class="ti ti-dots-vertical"></i></button>
              <div class="dropdown-menu">
                <a class="dropdown-item editObligationBtn" href="#" ><i class="ti ti-pencil me-1"></i> Edit</a>
                <a class="dropdown-item delObligationBtn" href="javascript:void(0);"><i class="ti ti-trash me-1"></i> Delete</a>
              </div>
            </div>
          </td>
        </tr>
        @endforeach  
      </tbody>
    </table>
  </div>
</div>


<!-- Add Obligation slider -->
<div class="offcanvas offcanvas-end" id="addPaymentOffcanvas" aria-hidden="true">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title"><span id="popUpTitle">Add</span> Obligation</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body flex-grow-1">
    <form id="addObligationForm" method="POST" enctype="multipart/form-data">
      <input type = "hidden" id ="task_id" value ="">
      <input type = "hidden" id ="sliderName" value ="Add">
      <div class="mb-6" style="margin-bottom: 20px;margin-top: 20px;">
      <label class="form-label">Owner Name:</label>
        <select class="select2 form-select owner" name="task_owner" id="task_owner">
            <option selected value="">-- Select Task Owner --</option>
            @foreach($users as $add_users_data)
                <option  value="{{$add_users_data->id}}" {{ (old('task_owner') == $add_users_data->id ? 'selected' : '' ) }}>{{$add_users_data->FirstName." ".$add_users_data->LastName}}</option>
            @endforeach
        </select>
      </div>
      <div class="mb-6" style="margin-bottom: 20px;margin-top: 20px;">
      <label class="form-label">Reviewer Name:</label>
        <select class="select2 form-select signatory" name="task_signatory" id="task_signatory">
            <option selected value="">-- Select Task Reviewer --</option>
            @foreach($users as $add_users_data)
                <option  value="{{$add_users_data->id}}" {{ (old('task_owner') == $add_users_data->id ? 'selected' : '' ) }}>{{$add_users_data->FirstName." ".$add_users_data->LastName}}</option>
            @endforeach
        </select> 
      </div>
      <div class="mb-6" style="margin-bottom: 20px;margin-top: 20px;">
        <label class="form-label" for="invoiceAmount">Obligation</label>
        <div class="input-group">
          <input type="text" id="taskName" name="taskName" class="form-control invoice-amount" placeholder="Task Name" />
        </div>
      </div>
      <div class="mb-6" style="margin-bottom: 20px;margin-top: 20px;">
        <label class="form-label" for="invoiceAmount">Contract</label>
        <select class="select2 form-select" id="contract_id_obg" name = "contract_id" >
            <option selected>Choose Contract</option>
            @foreach($contracts_list as $cn_data)
                <option  value="{{$cn_data->id}}" {{ (old('contract_id') == $cn_data->id ? 'selected' : '' ) }}>{{$cn_data->contract_name.'('.$cn_data->contract_unique_id.')'}}</option>
            @endforeach
        </select> 
      </div>

      <div class="mb-6" style="margin-bottom: 20px;margin-top: 20px;">
        <label for="first_name">Status:</label>
        <select class="select2 form-select" id="task_status" name="task_status" aria-label="Default select example">
          <option selected>Choose Status</option>
          <option value="Pending">Pending</option>
          <option value="Inprogress">Inprogress</option>
          <option value="Completed">Completed</option>
        </select>
      </div>
      <div class="mb-6" style="margin-bottom: 20px;margin-top: 20px;">
        <label for="first_name">Priority:</label>
        <select class="select2 form-select" id="task_priority" name="task_priority" aria-label="Default select example">
          <option selected>Choose Priority</option>
          <option value="Low">Low</option>
          <option value="Medium">Medium</option>
          <option value="High">High</option>
        </select>
      </div>
      <div class="mb-6" style="margin-bottom: 20px;margin-top: 20px;">
        <label class="form-label" for="payment-note">Description</label>
        <textarea class="form-control" id="task_description" name ="task_description" rows="2"></textarea>
      </div>
      <div >
          <div class="mb-6" style="margin-bottom: 20px;margin-top: 20px;">
            <label class="form-label" for="payment-date">Due Date</label>
            <input type="date" id="obDueDate" name="dueDate" class="form-control flatpickr" placeholder="Due Date" />
          </div>
        </div>
      <div class="mb-6" style="margin-bottom: 20px;margin-top: 20px;">
        <label for="first_name">Task Type:</label>
        <select class="select2 form-select task_type" id="task_type" name="task_type" aria-label="Default select example">
          <option selected>Choose Task Type</option>
          <option value="recurring">Recurring</option>
          <option value="oneTime">One Time</option>
        </select>
      </div>
      <div class="oneTimeDiv" style="display:none">
        <div class="mb-6" style="margin-bottom: 20px;margin-top: 20px;">
          <label class="form-label" for="payment-date">Onetime Date</label>
          <input type="date" name="OnetimeDate" id = "OnetimeDate" class="form-control flatpickr" placeholder="Date" />
        </div>
      </div>
      <hr>
      <div class="cornDiv" style="display:none">
        <h5 class="taskTypeTitle">Recurring Tasks</h5>
        <hr>
        <div class="">
          <label for="recurrence"> Frequency :</label>
          <div class="input-group" style="margin-bottom: 20px;margin-top: 20px;">
            <input type="text" class="form-control" placeholder="Repeats" name ="repeats" id="repeats" aria-describedby="basic-addon14">
            <span class="input-group-text" id="basic-addon14">
              <select class="select2 form-select month" id="days" name="frequency">
                <option selected>Choose Frequency</option>
                <option value="daily">daily</option>
                <option value="weekly">weekly</option>
                <option value="monthly">monthly</option>
                <option value="yearly">yearly</option>
              </select>
            </span>
          </div>
        </div>
        <label for="recurrence">Ends:</label>
        <div class="mb-6" style="margin-bottom: 20px;margin-top: 20px;">
          <select class="select2 form-select task_ends_on" id="task_ends_on" name="task_ends_on" aria-label="Default select example">
            <option selected>Choose</option>
            <option value="never">Never</option>
            <option value="on">On</option>
          </select>
        </div>
        <div class="endsOnDiv" style="display:none">
          <div class="mb-6" style="margin-bottom: 20px;margin-top: 20px;">
            <label class="form-label" for="payment-date">Recuring End Date</label>
            <input type="date" id="recuringEndDate" name="recuringEndDate" class="form-control flatpickr" placeholder="End Date" />
          </div>
        </div>
      </div>
      <hr>
      <div class="mb-6 d-flex flex-wrap">
        <button type="submit" class="btn btn-primary me-4" id="popUpAction">Add</button>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">Cancel</button>
      </div>
    </form>
  </div>
</div>
<!--  -->
@endsection
@section('footer')
@endsection