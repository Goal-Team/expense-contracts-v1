@extends('layouts/layoutMaster')
@section('title', 'Contracts - List')
<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/animate-css/animate.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'

])
@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/moment/moment.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection
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
.status-completed{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    /*background-color: #007bff;*/
    background-color: #a7a9ad;
    
}
.status-active{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #28c76f;
    
}
.status-expired{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #ff264a;
    
}
.status-terminate{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #08cfe6;
    
}

.status-renewed{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #7367f0;
    
}

.status-initialdraft{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #a8aaae;
    
}

.status-negotiation{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #28c76f;
    
}
.substatusText {
  text-transform: capitalize;
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
  
#wrapper-scroll,
#wrapper-table {
  overflow-y: hidden;
}
#wrapper-scroll {
  height: 20px;
  overflow-x: auto;
  position: sticky; 
  bottom:0  
}

#wrapper-scroll::-webkit-scrollbar {
    width: 1px;
}

#wrapper-scroll::-webkit-scrollbar-track {
    box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
}

#wrapper-scroll::-webkit-scrollbar-thumb {
    background-color: #c0c0c0;
}

#wrapper-table::-webkit-scrollbar {
    display: none;
}

#custom-scroll {
  width: 100%;
  height: 1px;
} 

#table-scroll .table-responsive{
    overflow-x: hidden !important;
}
  
</style>
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js',
'resources/assets/vendor/libs/select2/select2.js',
])
<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')
<!--@vite(['resources/assets/js/tables-datatables-advanced.js'])-->
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contractlist.js"></script>
@endsection
@section('content')

<?php cookie()->queue(cookie()->forget('historical')); ?>

<style>
    .act .card.card-border-shadow-primary:after {
        border-bottom-color: #7367f0;
    }
     .act .card.card-border-shadow-warning:after {
    border-bottom-color: #ff9f43;
}
     .act .card.card-border-shadow-info:after {
    border-bottom-color: #00cfe8;
}
     .act  .card.card-border-shadow-success:after {
    border-bottom-color: #28c76f;
}

  .act .card[class*=card-border-shadow-] {
    box-shadow: 0 0.25rem 1rem rgba(165, 163, 174, 0.4);
  }
  .act .card[class*=card-border-shadow-]:after {
    border-width: 5px;
  }
  
  .select2-results__options{
        font-size:14px !important;
 }
 
 .select2-selection__rendered {
    line-height: 31px !important;
}
.select2-container .select2-selection--single {
    height: 35px !important;
}
.select2-selection__arrow {
    height: 34px !important;
}
</style>
<div class="row">
    <div class="col-12">
    <button type="button" id="clearAllFilters" class="btn rounded-pill btn-outline-warning waves-effect float-end mb-2 d-none"> <i class="tf-icons ti ti-square-x ti-xs me-2"></i> Clear Filters </button>
{{-- Filter state comes from the URL query string, not cookies (dev rule 2026-08-27). --}}
@if (request()->query('my'))
        <button type="button" id="clearMyActions" class="btn rounded-pill btn-outline-success waves-effect float-end mb-2"> <i class="tf-icons ti ti-wall ti-xs me-2"></i> Show All Contracts </button>
@endif
@if (!request()->query('my') && $selstatus !== '')
        <button type="button" id="clearAllActions" class="btn rounded-pill btn-outline-youtube waves-effect float-end mb-2" data-user="{{Helper::userInfo()->email ?? ''}}"> <i class="tf-icons ti ti-user ti-xs me-2"></i> Show My Contracts </button>
@endif
    </div> 
    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-2 col-sm-4 mb-4">
                  
                <a href="javascript:;" class="loadstatus" id="status_draft" data-stat="draft">
                    <div class="card card-border-shadow-primary h-100">
                      <div class="card-body">
                        <div class="d-flex align-items-center mb-2 pb-1">
                          <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-file ti-md"></i></span>
                          </div>
                          <h4 class="ms-1 mb-0 count-disp">{{ $counts['draft'] }}</h4>
                        </div>
                        <p class="mb-1">Draft</p>
                      </div>
                    </div>
                </a>
              </div>
            <div class="col-lg-2 col-sm-4 mb-4">
                  
                 <a href="javascript:;" class="loadstatus" id="status_review" data-stat="review">
                  
                    <div class="card card-border-shadow-warning h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                      <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-warning"><i class='ti ti-pencil ti-md'></i></span>
                      </div>
                      <h4 class="ms-1 mb-0 count-disp">{{ $counts['review'] }}</h4>
                    </div>
                    <p class="mb-1">Review</p>
                  </div>
                </div>
                 </a>
              </div>
            <div class="col-lg-2 col-sm-4 mb-4">
                  <a href="javascript:;" class="loadstatus" id="status_negotiation" data-stat="negotiation">
                    <div class="card card-border-shadow-success h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                      <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-success"><i class='ti ti-check ti-md'></i></span>
                      </div>
                      <h4 class="ms-1 mb-0 count-disp">{{ $counts['negotiation'] ?? 0 }}</h4>
                    </div>
                    <p class="mb-1">Negotiation</p>
                  </div>
                </div>
                  </a>
              </div>
            <div class="col-lg-2 col-sm-4 mb-4">
                  <a href="javascript:;" class="loadstatus" id="status_finalization" data-stat="finalization">
                    <div class="card card-border-shadow-warning h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                      <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-warning"><i class='ti ti-file-check ti-md'></i></span>
                      </div>
                      <h4 class="ms-1 mb-0 count-disp">{{ $counts['finalization'] ?? 0 }}</h4>
                    </div>
                    <p class="mb-1">Finalization</p>
                  </div>
                </div>
                  </a>
              </div>

              <div class="col-lg-2 col-sm-4 mb-4">
                  <a href="javascript:;" class="loadstatus" id="status_approval" data-stat="approval">
                    <div class="card card-border-shadow-info h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                      <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-info"><i class="fa fa-check"></i></span>
                      </div>
                      <h4 class="ms-1 mb-0 count-disp">{{ $counts['approval'] }}</h4>
                    </div>
                    <p class="mb-1">Pending Approval</p>
                  </div>
                </div>
                  </a>
              </div>
              <div class="col-lg-2 col-sm-4 mb-4">
                  <a href="javascript:;" class="loadstatus" id="status_signing" data-stat="signing">
                    <div class="card card-border-shadow-info h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                      <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-info"><i class='fa fa-file-signature'></i></span>
                      </div>
                      <h4 class="ms-1 mb-0 count-disp">{{ $counts['signing'] }}</h4>
                    </div>
                    <p class="mb-1">Signing</p>
                  </div>
                </div>
                  </a>
              </div>
              <div class="col-lg-2 col-sm-4 mb-4">
                  <a href="javascript:;" class="loadstatus" id="status_executed" data-stat="executed">
                    <div class="card card-border-shadow-info h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                      <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-info"><i class='fa fa-file-signature'></i></span>
                      </div>
                      <h4 class="ms-1 mb-0 count-disp">{{ $counts['executed'] }}</h4>
                    </div>
                    <p class="mb-1">Executed</p>
                  </div>
                </div>
                  </a>
              </div>
              <div class="col-lg-2 col-sm-4 mb-4">
                  <a href="javascript:;" class="loadstatus" id="status_all" data-stat="all">
                    <div class="card card-border-shadow-secondary h-100">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                      <div class="avatar me-2">
                        <span class="avatar-initial rounded bg-label-secondary"><i class='fa fa-file-signature'></i></span>
                      </div>
                      <h4 class="ms-1 mb-0 count-disp">{{ $counts['all'] ?? 0 }}</h4>
                    </div>
                    <p class="mb-1">All</p>
                  </div>
                </div>
                  </a>
              </div>              
        </div>
      </div>
    <div class="">
    @if ($selstatus == 'executed' || str_contains($selstatus, 'executed_'))
    <div class="d-flex justify-content-evenly mb-2 bg-white p-3 rounded shadow-lg">
      <a href="javascript:;" id="status_executed_renewed" data-stat="executed_renewed" role="button" class="loadstatus btn btn-{{ $selstatus == 'executed_renewed' ? '' : 'outline-' }}primary text-nowrap d-inline-flex position-relative me-4">
        Renewed
        <span class="ms-2 badge badge-center count-disp rounded-pill bg-{{ $selstatus == 'executed_renewed' ? 'light text-dark' : 'primary text-white' }}">{{ $counts['executed_renewed'] }}</span>
      </a>
      <a href="javascript:;" id="status_executed_amended" data-stat="executed_amended" role="button" class="loadstatus btn btn-{{ $selstatus == 'executed_amended' ? '' : 'outline-' }}primary text-nowrap d-inline-flex position-relative me-4">
        Amended
        <span class="ms-2 badge badge-center count-disp rounded-pill bg-{{ $selstatus == 'executed_amended' ? 'light text-dark' : 'primary text-white' }}">{{ $counts['executed_amended'] }}</span>
      </a>
      <a href="javascript:;" id="status_executed_expired" data-stat="executed_expired" role="button" class="loadstatus btn btn-{{ $selstatus == 'executed_expired' ? '' : 'outline-' }}danger text-nowrap d-inline-flex position-relative me-4">
        Expired
        <span class="ms-2 badge badge-center count-disp rounded-pill bg-{{ $selstatus == 'executed_expired' ? 'light text-dark' : 'danger text-white' }}">{{ $counts['executed_expired'] }}</span>
      </a>
      <a href="javascript:;" id="status_executed_Terminated" data-stat="executed_Terminated" role="button" class="loadstatus btn btn-{{ $selstatus == 'executed_Terminated' ? '' : 'outline-' }}info text-nowrap d-inline-flex position-relative me-4">
        Terminated
        <span class="ms-2 badge badge-center count-disp rounded-pill bg-{{ $selstatus == 'executed_Terminated' ? 'light text-dark' : 'info text-white' }}">{{ $counts['executed_terminated'] }}</span>
      </a>
      <a href="javascript:;" id="status_executed_pending" data-stat="executed_pending" role="button" class="loadstatus btn btn-{{ $selstatus == 'executed_pending' ? '' : 'outline-' }}warning d-inline-flex position-relative me-4">
        Pending Activation
        <span class="ms-2 badge badge-center count-disp rounded-pill bg-{{ $selstatus == 'executed_pending' ? 'light text-dark' : 'warning text-white' }}">{{ $counts['executed_pending'] }}</span>
      </a>
      <a href="javascript:;" id="status_executed_active" data-stat="executed_active" role="button" class="loadstatus btn btn-{{ $selstatus == 'executed_active' ? '' : 'outline-' }}success text-nowrap d-inline-flex position-relative me-4">
        Active
        <span class="ms-2 badge badge-center count-disp rounded-pill bg-{{ $selstatus == 'executed_active' ? 'light text-dark' : 'success text-white' }}">{{ $counts['executed_active'] }}</span>
      </a>
      <a href="javascript:;" id="status_executed_completed" data-stat="executed_completed" role="button" class="loadstatus btn btn-{{ $selstatus == 'executed_completed' ? '' : 'outline-' }}secondary text-nowrap d-inline-flex position-relative me-4">
        Completed
        <span class="ms-2 badge badge-center count-disp rounded-pill bg-{{ $selstatus == 'executed_completed' ? 'light text-dark' : 'secondary text-white' }}">{{ $counts['executed_completed'] }}</span>
      </a>
    </div>
    @endif
  </div>
     <input type="hidden" id="status" value="{{ $selstatus !== '' ? $selstatus : 'draft' }}">

       <div class="">
     @if ($selstatus == 'draft' || str_contains($selstatus, 'draft_'))
    <div class="mb-2 bg-white p-3 rounded shadow-lg">
      <a href="javascript:;" id="status_initial_draft" data-stat="draft_initial" role="button" class="loadstatus btn btn-{{ $selstatus == 'draft_initial' ? '' : 'outline-' }}secondary text-nowrap d-inline-flex position-relative me-4">
        Initial Draft
        <span class="ms-2 count-disp badge badge-center rounded-pill bg-{{ $selstatus == 'draft_initial' ? 'light text-secondary' : 'secondary text-white' }}">{{ $counts['initial_draft'] }}</span>
      </a>
      <a href="javascript:;" id="status_under_revision" data-stat="draft_under_revision" role="button" class="loadstatus btn btn-{{ $selstatus == 'draft_under_revision' ? '' : 'outline-' }}primary text-nowrap d-inline-flex position-relative me-4">
        Under Revision
        <span class="ms-2 count-disp badge badge-center rounded-pill bg-{{ $selstatus == 'draft_under_revision' ? 'light text-dark' : 'primary text-white' }}">{{ $counts['under_revision'] }}</span>
      </a>
    </div>
    @endif
    </div>
    
</div>
<div class="card">
   @if(Session::has('message'))
  <p class="alert {{ Session::get('alert-class', 'alert-info') }} alert-dismissible mb-2">{!! Session::get('message') !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </p>
   @endif
      <h5 class="card-header">Contracts List
          <div class="col-md-6 float-end">
                <a href="{{url('/')}}/contracts/create" class="btn btn-sm btn-primary float-end">
                    <i class="ti ti-plus me-1"></i> Create
                </a>              
            @if(admin_setting('enable_ai_feature'))
                <!-- Dropdown Button with two options -->
                <!--<div class="btn-group float-end">-->
                <!--    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">-->
                <!--        <i class="ti ti-plus me-1"></i> Create-->
                <!--    </button>-->
                <!--    <ul class="dropdown-menu">-->
                <!--        <li>-->
                <!--            <a class="dropdown-item" href="{{url('/')}}/contracts/create">-->
                <!--                <i class="ti ti-file me-1"></i> Create-->
                <!--            </a>-->
                <!--        </li>-->
                <!--        <li>-->
                <!--            <a class="dropdown-item" href="{{url('/')}}/contracts/ai/create">-->
                <!--                <i class="ti ti-robot me-1"></i> Create with AI-->
                <!--            </a>-->
                <!--        </li>-->
                <!--    </ul>-->
                <!--</div>-->
            @else
                <!-- Single button -->
                <!--<a href="{{url('/')}}/contracts/create" class="btn btn-sm btn-primary float-end">-->
                <!--    <i class="ti ti-plus me-1"></i> Create-->
                <!--</a>-->
            @endif
              <a href="{{url('/')}}/contracts/builk-import" class="me-2 btn btn-sm btn-warning float-end">
             <i class="ti ti-table-down me-1"></i> Contract Import </a>

             <a href="{{url('/')}}/contracts/builk-export" class="me-2 btn btn-sm btn-success float-end">
            <i class="ti ti-table-import me-1"></i> Contract Export </a>
            
            
          </div>     
      </h5>
   <div class="card-body row">
      <div class="col-md-4">
         <select class="select2 mt-2" id="column-filter-table" multiple>
            <option value="1" selected>Contract Name</option>
            <option value="2" selected>Location</option>
            <option value="3" selected>Contract Type</option>
            <option value="4" selected>Category</option>
            <option value="5" selected>Effective Date</option>
            <option value="6" selected>End Date</option>
            <option value="7" selected>Priority</option>
            <option value="8" selected>Status</option>
            <option value="9">Value</option>             
            <option value="10">Attachment</option>             
         </select>
      </div>
         <div class="col-md-8">
            <div class="row">
                <div class="col-6 mb-2">
                <form id="filterContractListForm">
                    <select class="form-select select2 contracttype filterContractList" multiple name="contracttype[]" id="contracttype">
                     @foreach ($contractTypes as $contractType)
                     <option value="{{ $contractType->contract_type_id }}" {{ in_array($contractType->contract_type_id,$selcontype) ? "selected" : "" }}>
                        {{ $contractType->contract_type }}
                     </option>
                     @endforeach
                    </select>
                    </div>
                    
                    <div class="col-6 mb-2">
                    <select class="form-select select2 filterContractList" multiple name="contractcates[]" id="contractcates">
                        @foreach ($ContractCategories as $conCate)
                            <option value="{{ $conCate->id }}" {{ in_array($conCate->id, $selcate) ? "selected" : "" }}>{{ $conCate->name }}</option>
                        @endforeach
                    </select>
                    </div>
                        
                    <div class="col-6">
                    <select class="form-select select2 filterContractList" multiple name="contractlocs[]" id="contractlocs">
                        @foreach ($branchs as $branch)
                            <option value="{{ $branch->id }}" {{ in_array($branch->id, $sellocal) ? "selected" : "" }}>{{ $branch->LegalName }}</option>
                        @endforeach
                    </select>
                    </div>
                        
                    <div class="col-6">
                    <select class="form-select select2 filterContractList" name="contractstats" id="contractstats">
                        <option value="">Choose Status</option>
                        @foreach ($contractStatus as $constat)
                            <option value="{{ $constat->status_key }}" {{ $constat->status_key == $selstatus ? "selected" : "" }}>{{ ucwords($constat->main_status)."-". ucwords($constat->sub_status) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
         </div>        
   </div>
        
       <div class="card-datatable text-responsive" id="table-scroll">
          <table id="example" class="dt-column-search table">
             <thead>
                <tr>
                   <th>S.No.</th>
                   <th>Contract Name</th>
                   <th>Location</th>
                   <th>Contract Type</th>
                   <th>Category</th>
                   <th>Effective Date</th>
                   <th>End Date</th>
                   <th>Priority</th>
                   <th>Status</th>
                   <th>Value</th>
                   <th>Attachment</th>
                   <th>Action</th>
                </tr>
             </thead>
          </table>
        <div id="wrapper-scroll">
          <div id="custom-scroll"></div>
        </div>           
       </div>
</div>
<!--/ Column Search -->

<div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel1">Termination</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                  <div class="row">
                    <div class="col mb-4">
                      <label for="nameBasic" class="form-label">Reason For Termination</label>
                      <textarea class="form-control" id="terminationReason" rows="5"></textarea>
                    </div> 
                    
                    <input type="hidden" id="conId" class="form-control">
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="button" id="btnTermination" class="btn btn-primary">Save changes</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
@endsection