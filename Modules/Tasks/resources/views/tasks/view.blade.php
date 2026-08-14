@extends('layouts/layoutMaster')

@section('title', 'Tasks')

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

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-invoice.scss')
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/quill/katex.js',
 'resources/assets/vendor/libs/quill/quill.js',
'resources/assets/vendor/libs/cleavejs/cleave.js',
'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
'resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/dropzone/dropzone.js',
 'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js'
])

<script type="module" src="{{url('/')}}/Modules/Tasks/resources/assets/js/taskcrud.js"></script>

@endsection

@section('content')
<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />

    <div class="row my-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
           <div class="d-flex flex-column justify-content-center">
              <h4 class="mb-1">Edit Task</h4>
           </div>
           <div class="d-flex align-content-center flex-wrap gap-3">
              <div class="d-flex gap-3">
                 <a href="{{ url('tasks') }}" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
              </div>
           </div>
        </div>
    <!-- <form action="{{ 'update' }}" method="POST" enctype="multipart/form-data"> -->
        <form action="{{ url('tasks/tasks-update/'.$tasks->id) }}" method="POST" enctype="multipart/form-data"> 
    @csrf  
        <div class="card">
            <div class="card-body">
                <!--<h2 class = "text-center">Update Tasks</h2>-->
                <!--<div style="margin-bottom:10px;">-->
                <!--    <a href = "{{ url('tasks') }}" class="btn btn-outline-primary btn-sm">-->
                <!--        <i class="fa fa-back" aria-hidden="true"></i> Back-->
                <!--    </a>-->
                <!--</div>-->
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Contract Name:</label>  
                            <select class="select2 form-select" id="contract_id" name = "contract_id" >
                                <option selected>Choose Contract</option>
                                @foreach($contracts_list as $cn_data)
                                    <option  value="{{$cn_data->id}}" {{ $tasks->contract_id == $cn_data->id ? 'selected' : '' }}>{{$cn_data->contract_name}}</option>
                                @endforeach
                            </select>  
                        </div>                        
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Task Name:</label>  
                            <input type="text" class="form-control" id="name_of_task" name="name_of_task" value="{{decryptString($tasks->name_of_task, 'name_of_task')}}"/>  
                        </div>
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Priority:</label>
                            <select class="select2 form-select" id ="priority" name = "priority" aria-label="Default select example">
                                <option selected>Choose Priority</option>
                                <option value="low" {{decryptString($tasks->priority, 'priority') == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{decryptString($tasks->priority, 'priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{decryptString($tasks->priority, 'priority') == 'high' ? 'selected' : '' }}>High</option>
                            </select>    
                        </div>
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="task_desc">Description:</label>
                            <textarea type = "text" class="form-control" name="task_desc" id="task_desc" rows ="4"> {{decryptString($tasks->description, 'description')}}</textarea>
                        </div>

                        
                        
                    </div>
                    <div class="col-md-4" >
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Branch:</label>
                            <select class="select2 form-select" id="branch" name = "branch"  aria-label="Default select example">
                                <option selected>Choose Branch</option>
                                @foreach($branch as $br_data)
                                    <option  value="{{$br_data->id}}" {{ $tasks->branch == $br_data->id ? 'selected' : '' }}>{{$br_data->BranchName}}</option>
                                @endforeach
                            </select>
                            <!-- <select class="form-select" id="branch" name = "branch"  aria-label="Default select example">
                                <option selected>Choose Branch</option>
                                <option value="1">Branch 1</option>
                                <option value="2">Branch 2</option>
                                <option value="3">Branch 3</option>
                            </select>   -->
                        </div>
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="start_date">Start Date:</label>  
                            <input type="date" class="form-control" id="start_date"  name="start_date" value="{{decryptString($tasks->start_date, 'start_date')}}"/>  
                        </div>
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="task_owner">Owner:</label>
                            <select class="select2 form-select" name="task_owner" id="task_owner" aria-label="Default select example">
                                <option selected>Task Owner</option>
                                @foreach($add_users as $add_users_data)
                                    <option  value="{{$add_users_data->id}}" {{ $tasks->task_owner == $add_users_data->id ? 'selected' : '' }}>{{$add_users_data->FirstName." ".$add_users_data->LastName}}</option>
                                @endforeach
                            </select>  
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Status:</label>
                            <select class="select2 form-control" class="form-select" id="status" name = "status">
                                <option value="">Choose Status</option>
                                <option value="pending" {{ $tasks->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="inprogress" {{ $tasks->status == 'inprogress' ? 'selected' : '' }}>Inprogress</option>
                                <option value="completed" {{ $tasks->status == 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>  
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="end_date">End Date:</label>  
                            <input type="date" id="end_date"  class="form-control" name="end_date" value="{{decryptString($tasks->end_date, 'end_date')}}"/>
                        </div>
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Reviewer:</label>
                            <select class="select2 form-select" id="task_reviewer" name ="task_reviewer"  aria-label="Default select example">
                                <option selected>Task Reviewer</option>
                                @foreach($add_users as $add_users_data)
                                    <option  value="{{$add_users_data->id}}" {{ $tasks->task_reviewer == $add_users_data->id ? 'selected' : '' }}>{{$add_users_data->FirstName." ".$add_users_data->LastName}}</option>
                                @endforeach
                            </select>  
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
       
        </form>  
    </div>
        
@endsection