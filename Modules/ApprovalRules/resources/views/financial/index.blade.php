@extends('layouts/layoutMaster')

@section('title', 'Approver Rules - List')

<!-- Vendor Styles -->
@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/animate-css/animate.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])

<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/moment/moment.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

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

<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection

<!-- Page Scripts -->
@section('page-script')
<!--@vite(['resources/assets/js/tables-datatables-advanced.js'])-->

<script type="module" src="{{url('/')}}/Modules/ApprovalRules/resources/assets/js/approvalrule.js"></script>
@endsection

@section('content')

<div class="card">

  
  @if(Session::has('message'))
<p class="alert {{ Session::get('alert-class', 'alert-info') }}">{{ Session::get('message') }}</p>
@endif

  <div class="headStyle">
      <h5 class="card-header">Approver Rules List</h5>
      <div>
          
          
        <a href="{{ url('contract-setup/financial-add') }}" class="btn btn-primary">
          <i class="bx bx-plus me-1"></i> Add New </a>
      </div>
  </div>

  
  <div class="card-datatable text-nowrap">
    <table id="example" class="dt-column-search table display">
      <thead>
        <tr>
          <th>S.No.</th>
          <th>Name</th>
          <th>Appover Type</th>
          <th>Location</th>
          <th>Action</th>
          <th>Department</th>
          <th>Category</th>
          <th>Contract Type</th>
          <th>Lower Limit</th>
          <th>Upper Limit</th>
          <th>Approver</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
<!--/ Column Search -->

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

@endsection
