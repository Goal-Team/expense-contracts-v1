@extends('layouts.layoutMaster')

@section('title', 'Contract Type - List')

<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
])

<style>
    .headStyle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-right: 15px;
    }
</style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js'
])

<!--<link href="{{ url('/') }}/assets/css/custom.css" rel="stylesheet" />-->
@endsection

@section('page-script')

<script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/contracttypelist.js"></script>

@endsection

@section('content')

<style>
        .btn:not([class*=btn-label-]):not([class*=btn-outline-]) {
            box-shadow: none;
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

<div class="card">
    @if(Session::has('success'))
    <p class="alert {{ Session::get('alert-class', 'alert-danger') }}">{{ Session::get('success') }}</p>
    @endif
    <div class="headStyle">
        <h5 class="card-header">Contract Type List</h5>
        <div>
            <a href="{{url('/')}}/contract-setup/contract-type" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> Add New
            </a>
        </div>
    </div>
    
    <div class="card-datatable text-responsive">
            <table id="example" class="dt-column-search table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Contract Type</th>
                    <th>Category</th>
                    <th>Department</th>
                    <th>Short Name</th>
                    <th>Status</th>
                    <!--<th>Action</th>-->
                </tr>
              </thead>
            </table>
          </div>

</div>
@endsection