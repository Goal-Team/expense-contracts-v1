@extends('layouts/layoutMaster')

@section('title', 'Contract Parties')

<!-- Vendor Styles -->
@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/animate-css/animate.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/moment/moment.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
])

<link href="{{url('/')}}/assets/css/partycustom.css" rel="stylesheet" />

@endsection

<!-- Page Scripts -->
@section('page-script')

<script type="module" src="{{url('/')}}/Modules/ContractParties/resources/assets/js/partylist.js"></script>

@endsection
  @section('content')
  
    <style>
        .btn:not([class*=btn-label-]):not([class*=btn-outline-]) {
            box-shadow: none;
        }
        .card-datatable {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
            max-width: 100%;
        }
        .card-datatable .dataTables_wrapper {
            overflow-x: auto;
            max-width: 100%;
        }
        .card-datatable table.dataTable {
            width: 100% !important;
            max-width: 100%;
            table-layout: auto;
        }
        .card-datatable table.dataTable th,
        .card-datatable table.dataTable td {
            white-space: nowrap;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .columns-list {
            max-height: 300px;
            overflow-y: auto;
            min-width: 180px;
        }
        .columns-list .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
        }
        .columns-list .dropdown-item input[type="checkbox"] {
            margin: 0;
        }
        .dt-action-buttons {
            display: none !important;
        }
    </style>
    
    <style>
 @media(max-width:767px){
      
     .col-lg-2.col-sm-2.mb-4 {
         width: 100%;
     } 
    
     .card-datatable.table-responsive {
         overflow-x: auto;
     }
     .card-datatable table {
         min-width: 900px;
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
 <input type="hidden" id="fstatus" value="<?php echo isset($_COOKIE['pfilterStatus']) ? $_COOKIE['pfilterStatus'] : 'all'; ?>">
  <div class="card mb-5">
      <div class="card-header d-flex justify-content-between">
        <h5 class="card-title mb-0">Parties Type</h5>
      </div>

      
      <div class="card-body pt-2">
        <div class="row">
          <div class="col-md-2 col-6 clickableDashItems cursor-pointer">
            <a href="javascript:;" class="loadstatus {{ isset($_COOKIE['pfilterStatus']) && $_COOKIE['pfilterStatus'] == 'external' ? 'active' : '' }}" data-stat="external" id="statu_external">
              <div class="d-flex align-items-center setrount">

                <div class="card-info">
                  <h5 class="mb-0">{{$external}}</h5>
                  <small>External</small>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-2 col-6 clickableDashItems cursor-pointer">
            <a href="javascript:;" class="loadstatus {{ isset($_COOKIE['pfilterStatus']) &&  $_COOKIE['pfilterStatus'] == 'related_party' ? 'active' : '' }}" data-stat="related_party" id="statu_related_party">
              <div class="d-flex align-items-cente relatedrount">

                <div class="card-info">
                  <h5 class="mb-0">{{$related_party}}</h5>
                  <small>Related party</small>
                </div>
              </div>
            </a>
          </div>
     
        <div class="col-md-2 col-6 clickableDashItems cursor-pointer">
          <a href="javascript:;" class="loadstatus {{ isset($_COOKIE['pfilterStatus']) &&  $_COOKIE['pfilterStatus'] == 'vendors' ? 'active' : '' }}" data-stat="vendors" id="statu_vendors">
            <div class="d-flex align-items-center vendorrount">

              <div class="card-info">
                <h5 class="mb-0">{{$vendors}}</h5>
                <small>Vendors</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-md-2 col-6 clickableDashItems cursor-pointer">
          <a href="javascript:;" class="loadstatus {{ isset($_COOKIE['pfilterStatus']) &&  $_COOKIE['pfilterStatus'] == 'customer' ? 'active' :'' }}" data-stat="customer" id="statu_customer">
            <div class="d-flex align-items-center customerrount">

              <div class="card-info">
                <h5 class="mb-0">{{$customer}}</h5>
                <small>Customer</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-md-2 col-6 clickableDashItems cursor-pointer">
          <a href="javascript:;" class="loadstatus {{ isset($_COOKIE['pfilterStatus']) &&  $_COOKIE['pfilterStatus'] == 'supplier' ? 'active' : '' }}" data-stat="supplier" id="statu_supplier">
            <div class="d-flex align-items-center supplierrount">

              <div class="card-info">
                <h5 class="mb-0">{{$supplier}}</h5>
                <small>Supplier</small>
              </div>
            </div>
          </a>
        </div>
           </div>
      </div>
  </div> 
  
  
  
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
              <h5 class="card-title">Contract Parties List <span class="badge bg-primary">Organization</span></h4>
              </h5>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3">
              <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{url('/')}}/parties/contract-parties-org-bulk-import" class="btn btn-success">
                  <i class="ti ti-user-down me-1"></i> Parties Import </a>
                @if(session()->get('contractSessionUserRole') === 'Super Admin')
                <a href="{{url('/')}}/parties/parties-update-import" class="btn btn-info">
                  <i class="ti ti-file-import me-1"></i> Update Parties </a>
                @endif
                <a href="{{ URL('/parties/contract-parties-org-add') }}" class="btn btn-primary">
                  <i class="ti ti-plus me-1"></i> Add New </a>
                <div class="dropdown">
                  <button class="btn btn-label-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-file-export me-1"></i> Export
                  </button>
                  <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                    <li><a class="dropdown-item dt-export-btn" href="#" data-format="print"><i class="ti ti-printer me-2"></i>Print</a></li>
                    <li><a class="dropdown-item dt-export-btn" href="#" data-format="csv"><i class="ti ti-file-text me-2"></i>Csv</a></li>
                    <li><a class="dropdown-item dt-export-btn" href="#" data-format="excel"><i class="ti ti-file-spreadsheet me-2"></i>Excel</a></li>
                    <li><a class="dropdown-item dt-export-btn" href="#" data-format="pdf"><i class="ti ti-file-description me-2"></i>Pdf</a></li>
                    <li><a class="dropdown-item dt-export-btn" href="#" data-format="copy"><i class="ti ti-copy me-2"></i>Copy</a></li>
                  </ul>
                </div>
                <div class="dropdown">
                  <button class="btn btn-label-primary dropdown-toggle" type="button" id="columnsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-layout-columns me-1"></i> Columns
                  </button>
                  <ul class="dropdown-menu columns-list" aria-labelledby="columnsDropdown" id="columnsDropdownMenu"></ul>
                </div>
              </div>
            </div>
          </div>
        </div>

          <div class="card-datatable table-responsive">
            <table id="example" class="dt-column-search table display">
              <thead>
                <tr>
                  <th>S.No.</th>
                   <th>Action</th>
                  <th>Name</th>
                  <th>Type</th>
                  <th>Address</th>
                  <th>Phone</th>
                  <th>Email</th>
                  <th>Vendor Code</th>
                  <th>Active Vendor Code</th>
                  <th>Legal Entity</th>
                  <th>Role In Contract</th>
                  <th>Engagement Level</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
        
    </div>
        
      <!-- Modal -->
        <div class="modal fade zoomIn" id="deleteRecordModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mt-2 text-center">
                            <i class="bi bi-trash3 display-5 text-danger"></i>
                            <div class="mt-4 pt-2 fs-base mx-4 mx-sm-5">
                                <h4>Are you Sure ?</h4>
                                <p class="text-muted mb-0">Are you Sure You want to Remove this Record ?</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                            <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn w-sm btn-danger" data-id="" id="delete-paries">Yes, Delete It!</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end modal -->
  @endsection   

