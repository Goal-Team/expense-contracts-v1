@extends('layouts/layoutMaster')

@section('title', 'Tasks')

<!-- Vendor Styles -->
@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/animate-css/animate.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/moment/moment.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

<!-- Page Scripts -->
@section('page-script')

<script type="module" src="{{url('/')}}/Modules/Tasks/resources/assets/js/tasks.js"></script>

@endsection

@section('content')



<style>
    .btn:not([class*=btn-label-]):not([class*=btn-outline-]) {
        box-shadow: none;
    }
    
    .filterClass{
        display: none;
    }
</style>

<style>
    .col-lg-2.col-sm-2.mb-4 {
        width: 20%;
    }
   .headStyle {
   display: flex;
   align-items: center;
   justify-content: space-between;
   margin-right: 15px;
   }
   .table th {
    text-align: left !important;
}
table.dataTable.table-striped>tbody>tr:nth-of-type(odd)>* {
    box-shadow: none;
   }
   table tr th, table tr td{
    border-right-width: 0 !important;
   }
   table.table-bordered.dataTable thead tr:first-child th, table.table-bordered.dataTable thead tr:first-child td {
    border-top-width: 0 !important;
    }
    table td.dataTables_empty {
        padding: 5rem !important;
    }
 @media(max-width:767px){
     
    .col-lg-2.col-sm-2.mb-4 {
        width: 100%;
    } 
   
   table.table td {
    padding-left: 5%;
    }
    table thead {
          display: none;
    }
    table td {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      border-bottom: 1px solid #eee;
      font-size: 15px;
      line-height: 1.35em;
    }
    table td:before {
      content: attr(data-label);
      font-size: 0.9em;
      text-align: left;
      font-weight: bold;
      text-transform: capitalize;
      max-width: 45%;
      color: #545454;
    }
    table td + td {
      margin-top: 0.8em;
      text-align: left;
    }
    table td:last-child {
      border-bottom: 0;
    }
    .project-list-table {
      border-collapse: separate;
      border-spacing: 0 12px
    }
    
    .project-list-table tr {
      background-color: #fff
    }
    
    .table-nowrap td,
    .table-nowrap th {
      white-space: nowrap;
    }
    
    .table-borderless>:not(caption)>*>* {
      border-bottom-width: 0;
    }
    
    .table>:not(caption)>*>* {
      padding: 0.75rem 0.75rem;
      background-color: var(--bs-table-bg);
      border-bottom-width: 1px;
      box-shadow: inset 0 0 0 9999px var(--bs-table-accent-bg);
    }
    table.table tbody tr:nth-of-type(odd) {
        background-color: rgba(204, 209, 216, 0.5);
    }
    table.table tbody tr, table.table tbody td {
    margin: 1rem 0;
}
  }
</style>

 <!--<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheets" />-->
 
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Tasks /</span> List
</h4>

<!-- Invoice List Widget -->

<!--<a href="{{ url('tasks/tasks-add') }}" class="btn btn btn-primary mb-4" role="button">Add New Tasks</a>-->

<!-- Invoice List Table -->
<div class="row">
    @if (isset($_COOKIE['myFilterTasks']))
        <div class="col-12">
            <button type="button" id="clearMyActions" class="btn rounded-pill btn-outline-success waves-effect float-end mb-2"> <i class="tf-icons ti ti-wall ti-xs me-2"></i> Show All Tasks </button>
        </div> 
    @endif
    @if (!isset($_COOKIE['myFilterTasks']))
        <div class="col-12">
            <button type="button" id="clearAllActions" class="btn rounded-pill btn-outline-youtube waves-effect float-end mb-2" data-user="{{Helper::userInfo()->id}}"> <i class="tf-icons ti ti-user ti-xs me-2"></i> Show My Tasks </button>
        </div> 
    @endif
</div>
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
                              <p class="mb-0">Pending Tasks</p>
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
                      <p class="mb-0">Completed Tasks</p>
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
                      <p class="mb-0">Inprogress Tasks</p>
                    </div>
                    <span class="avatar me-sm-4">
                      <span class="avatar-initial bg-label-secondary rounded">
                          <i class="ti ti-progress ti-md"></i>
                      </span>
                    </span>
                  </div>
                 </a>
        </div>
        <!--<div class="col-sm-6 col-lg-3">-->
        <!--  <div class="d-flex justify-content-between align-items-start">-->
        <!--    <div>-->
        <!--      <h3 class="mb-1">0</h3>-->
        <!--      <p class="mb-0">Pending For Review</p>-->
        <!--    </div>-->
        <!--    <span class="avatar">-->
        <!--      <span class="avatar-initial bg-label-secondary rounded"><i class="ti ti-clock ti-md"></i></span>-->
        <!--    </span>-->
        <!--  </div>-->
        <!--</div>-->
      </div>
    </div>
  </div>
</div>

@if (isset($_GET['status']))
     <input type="hidden" id="status" value="{{ $_GET['status'] }}">
     @endif
     @if (!isset($_GET['status']))
     <input type="hidden" id="status" value="pending">
     @endif


    <div class="card">
        <div class="container mt-4 mb-4">
        <div class="row align-items-center">
          @if ($message = Session::get('success'))
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <b>{{ $message }}</b>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
          @endif
          <div class="col-md-6">
            <div class="mb-3">
              <h5 class="card-title">Task List <span class="text-muted fw-normal ms-2"></span>
              </h5>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3">
              <div>
                  
                <a href="{{ url('tasks/tasks-add') }}" class="btn btn-primary">
                  <i class="bx bx-plus me-1"></i> Add New </a>
              </div>
              <!-- <div class="dropdown">
                <a class="btn btn-link text-muted py-1 font-size-16 shadow-none dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="bx bx-dots-horizontal-rounded"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <a class="dropdown-item" href="#">Action</a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="#">Another action</a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="#">Something else here</a>
                  </li>
                </ul>
              </div> -->
            </div>
          </div>
        </div>
        
        <div class="card-datatable text-responsive">
        <table id="example" class="dt-column-search table">
          <thead>
            <tr>
              <th>S.No.</th>
              <th>Task Name</th>
              <th>Status</th>
              <th>Start Date</th>
              <th>Due date</th>
              <th>Action</th>
            </tr>
          </thead>
        </table>
      </div>

        </div>
        
    </div>

@endsection
