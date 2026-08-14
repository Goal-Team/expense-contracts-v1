<!-- <div class="mt-3 mb-3">
  <button class="btn btn-label-linkedin waves-effect waves-light" data-bs-toggle="offcanvas" data-bs-target="#addPaymentOffcanvas">
    <span class="d-flex align-items-center justify-content-center text-nowrap">
      <i class="ti ti-plus ti-xs me-1"></i>Add Obligation</span>
  </button>
</div> -->

  <!-- <table class="table-responsive text-nowrap">
    <thead>
      <th>Name of Task</th>
      <th>Due Date</th>
      <th>Status</th>
      <th>Priority</th>
    </thead>
    <tbody>
      <tr>
        <td>testing</td>
        <td>2024-10-10</td>
        <td>pending</td>
        <td>Medium</td>
      </tr>
      <tr>
        <td>testing</td>
        <td>2024-10-10</td>
        <td>pending</td>
        <td>Medium</td>
      </tr>
      <tr>
        <td>testing</td>
        <td>2024-10-10</td>
        <td>pending</td>
        <td>Medium</td>
      </tr>
      <tr>
        <td>testing</td>
        <td>2024-10-10</td>
        <td>pending</td>
        <td>Medium</td>
      </tr>
      <tr>
        <td>testing</td>
        <td>2024-10-10</td>
        <td>pending</td>
        <td>Medium</td>
      </tr>
      <tr>
        <td>testing</td>
        <td>2024-10-10</td>
        <td>pending</td>
        <td>Medium</td>
      </tr>
      <tr>
        <td>testing</td>
        <td>2024-10-10</td>
        <td>pending</td>
        <td>Medium</td>
      </tr>
      <tr>
        <td>testing</td>
        <td>2024-10-10</td>
        <td>pending</td>
        <td>Medium</td>
      </tr>
      <tr>
        <td>testing</td>
        <td>2024-10-10</td>
        <td>pending</td>
        <td>Medium</td>
      </tr>
      <tr>
        <td>testing</td>
        <td>2024-10-10</td>
        <td>pending</td>
        <td>Medium</td>
      </tr>
      <tr>
        <td>testing</td>
        <td>2024-10-10</td>
        <td>pending</td>
        <td>Medium</td>
      </tr> 
    </tbody>
  </table> -->

<!-- 
  <div class="card mt-4">
  <div class="card-datatable table-responsive">
    <table class="contracts-obligation table border-top">
         <thead>
            <tr>
            <th>Task Name</th>
            <th>Priority</th>
            <th>End Date</th>
            <th>Status</th>
            <th>Actions</th>
            </tr>
         </thead>
    </table>
  </div>
</div> -->



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
          <!-- @if( $task->task_type == 'oneTime')
            {{$task->onetime_end_date}}
          @endif

          @if( $task->task_type == 'recurring')
            {{$task->recuring_due_date}}
          @endif -->
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
    <!-- <div class="d-flex justify-content-between bg-lighter p-2 mb-4">
      <p class="mb-0">Contract Name:</p>
      <p class="fw-medium mb-0">{{ decryptString($contract->contract_name, 'contract_name') }}</p>
    </div>
    <div class="d-flex justify-content-between bg-lighter p-2 mb-4">
      <p class="mb-0">Branch Name:</p>
      <p class="fw-medium mb-0">
        @foreach($contractPartys as $index => $contractParty)
        @foreach ($branchs as $branch)
        @if($branch->id == $contractParty->contract_party_location_id)
        {{ $branch->BranchName}}
        @endif
        @endforeach
        @endforeach
      </p>
    </div> -->
    <div class>
      <p class ="sliderName" style = "display:none;">Add</p>
    </div>
    <div class>
      <p class ="branchName" value = "{{ $contractParty->contract_party_location_id }}" 
          style = "display:none;">Add</p>
    </div>
    <div class="d-flex justify-content-between bg-lighter p-2 mb-4">
      <p class="mb-0">Owner Name:</p>
      <p class="fw-medium mb-0 owner" value ="{{ $contract->owner }}">
        @foreach ($users as $user)
          @if($contract->owner == $user->id)
            {{ $user->FirstName }}
            {{ $user->LastName }}
          @endif
        @endforeach
      </p>
    </div>
    <div class="d-flex justify-content-between bg-lighter p-2 mb-4">
      <p class="mb-0">Reviewer Name:</p>
      <p class="fw-medium mb-0 signatory" value ="{{ $contract->signatory }}">
        @foreach ($users as $user)
          @if($contract->signatory == $user->id)
            {{ $user->FirstName }}
            {{ $user->LastName }}
          @endif
        @endforeach
      </p>
    </div>
    
    <form id="addObligationForm" method="POST" enctype="multipart/form-data">
      <input type = "hidden" id ="contract_id_obg" value = "{{ $contract->id }}">
      <input type = "hidden" id ="task_id" value ="">
      <div class="mb-6" style="margin-bottom: 20px;margin-top: 20px;">
        <label class="form-label" for="invoiceAmount">Obligation</label>
        <div class="input-group">
          <input type="text" id="taskName" name="taskName" class="form-control invoice-amount" placeholder="Task Name" />
        </div>
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
        <!-- <div class="input-group">
          <span class="input-group-text">
            <select class="select2 form-select days" id="days" name="days">
              <option selected>Choose Days</option>
              @foreach(range(1, 31) as $day)
              <option value="{{ $day }}">{{ $day }}</option>
              @endforeach
            </select>
            <select class="select2 form-select month" id="month" name="month">
              <option selected>Choose Month</option>
              @foreach (range(1, 12) as $month)
              <option value="{{ date('F', mktime(0, 0, 0, $month, 1)) }}">{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
              @endforeach
            </select>

        </div> -->
        <!--  <div class="form-group" style="margin-bottom: 20px;margin-top: 20px;">
          <label for="recurrence">Recurrence Frequency:</label>
          <select class="select2 form-select recurrence" id="recurrence" name="recurrence" aria-label="Default select example">
              <option selected>Choose Recurrence Frequency</option>
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
              <option value="yearly">Yearly</option>
          </select>
        </div> -->
        <!-- <div class="form-group" style="margin-bottom: 20px;margin-top: 20px;">
          <label for="days">Select Month:</label>
          <select class="select2 form-select month" id="days" name="days">
          <option selected>Choose Month</option>
            @foreach (range(1, 12) as $month)
              <option value="{{ date('F', mktime(0, 0, 0, $month, 1)) }}">{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group" style="margin-bottom: 20px;margin-top: 20px;">
          <label for="days">Select Days of the Week:</label>
          <select class="select2 form-select days" id="days" name="days">
          <option selected>Choose Days</option>
            @foreach(range(1, 31) as $day) 
              <option value="{{ $day }}">{{ $day }}</option>
              @endforeach
          </select>
        </div>
        <div class="form-group" style="margin-bottom: 20px;margin-top: 20px;">
          <label for="days">Select Days of the Week:</label>
          <select class="select2 form-select days" id="days" name="days">
          <option selected>Choose Days</option>
              <option value="monday">Monday</option>
              <option value="tuesday">Tuesday</option>
              <option value="wednesday">Wednesday</option>
              <option value="thursday">Thursday</option>
              <option value="friday">Friday</option>
              <option value="saturday">Saturday</option>
              <option value="sunday">Sunday</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom: 20px;margin-top: 20px;">
          <label for="recurrence"> Frequency:</label>
          <select class="select2 form-select reminder" id="reminder" name="reminder" aria-label="Default select example">
              <option selected>Choose Reminder Frequency</option>
              <option value="1day">1 day before</option>
            <option value="3days">3 days before</option>
            <option value="1week">1 week before</option>>
          </select>
        </div> -->
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