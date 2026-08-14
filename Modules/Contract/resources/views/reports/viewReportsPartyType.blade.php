@extends('layouts/layoutMaster')

@section('title', 'Reports')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
])
@endsection

@section('page-style')
<link rel="stylesheet" href="{{url('/')}}/Modules/Contract/resources/assets/sass/reports.css" />
@endsection

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Contracts / </span>Reports By Party Type
</h4>

<div class="row mb-4 g-4">
  <div class="col-md-12">
      <div class="card h-100">
        <div class="card-header pb-0">
          <div class="d-flex justify-content-between">
            <small class="d-block mb-2 text-muted">Total Contracts</small>
            <p class="card-text text-success d-none">+18.2%</p>
          </div>
          <h4 class="card-title mb-1">{{$allContracts}}</h4>
        </div>
        <div class="card-body">
          <div class="row mt-4">
            <div class="col-1 text-center">
              <div class="d-flex gap-2 align-items-center mb-2">
                <span class="badge bg-label-primary p-1 rounded"><i class="ti ti-user-down ti-xs"></i></span>
                <p class="mb-0">Internal</p>
              </div>
              <h5 class="mb-0 pt-1">62.2%</h5>
              <small class="text-muted">6,440</small>
            </div>
            <div class="col-1">
              <div class="divider divider-vertical">
                <div class="divider-text">
                  <span class="badge-divider-bg bg-label-secondary">VS</span>
                </div>
              </div>
            </div>
            <div class="col-1 text-center">
              <div class="d-flex gap-2 justify-content-end align-items-center mb-2">
                <p class="mb-0">External</p>
                <span class="badge bg-label-primary p-1 rounded"><i class="ti ti-user-up ti-xs"></i></span>
              </div>
              <h5 class="mb-0 pt-1">25.5%</h5>
              <small class="text-muted">12,749</small>
            </div>
            <div class="col-1">
              <div class="divider divider-vertical">
                <div class="divider-text">
                  <span class="badge-divider-bg bg-label-secondary">VS</span>
                </div>
              </div>
            </div>
            <div class="col-2 text-center">
              <div class="d-flex gap-2 justify-content-center align-items-center mb-2">
                <p class="mb-0">Related Party</p>
                <span class="badge bg-label-primary p-1 rounded"><i class="ti ti-user-code ti-xs"></i></span>
              </div>
              <h5 class="mb-0 pt-1">25.5%</h5>
              <small class="text-muted">12,749</small>
            </div>
            <div class="col-1">
              <div class="divider divider-vertical">
                <div class="divider-text">
                  <span class="badge-divider-bg bg-label-secondary">VS</span>
                </div>
              </div>
            </div>
            <div class="col-1 text-center">
              <div class="d-flex gap-2 justify-content-end align-items-center mb-2">
                <p class="mb-0">Vendors</p>
                <span class="badge bg-label-primary p-1 rounded"><i class="ti ti-user-bolt ti-xs"></i></span>
              </div>
              <h5 class="mb-0 pt-1">25.5%</h5>
              <small class="text-muted">12,749</small>
            </div>
            <div class="col-1">
              <div class="divider divider-vertical">
                <div class="divider-text">
                  <span class="badge-divider-bg bg-label-secondary">VS</span>
                </div>
              </div>
            </div>
            <div class="col-1 text-center">
              <div class="d-flex gap-2 justify-content-end align-items-center mb-2">
                <span class="badge bg-label-primary p-1 rounded"><i class="ti ti-user-heart ti-xs"></i></span>
                <p class="mb-0">Customer</p>
              </div>
              <h5 class="mb-0 pt-1">25.5%</h5>
              <small class="text-muted">12,749</small>
            </div>
            <div class="col-1">
              <div class="divider divider-vertical">
                <div class="divider-text">
                  <span class="badge-divider-bg bg-label-secondary">VS</span>
                </div>
              </div>
            </div>
            <div class="col-1 text-center">
              <div class="d-flex gap-2 justify-content-end align-items-center mb-2">
                <p class="mb-0">Supplier</p>
                <span class="badge bg-label-primary p-1 rounded"><i class="ti ti-user-dollar ti-xs"></i></span>
              </div>
              <h5 class="mb-0 pt-1">25.5%</h5>
              <small class="text-muted">12,749</small>
            </div>
          </div>
          <div class="d-flex align-items-center mt-4">
            <div class="progress w-100" style="height: 8px;">
              <div class="progress-bar bg-info" style="width: 70%" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100"></div>
              <div class="progress-bar bg-primary" role="progressbar" style="width: 30%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>
        </div>
      </div>
  </div>
</div>

<!-- review List Table -->
<div class="card">
  <div class="card-datatable table-responsive">
    <table class="table border-top" id="departmentReport">
         <thead>
            <tr>
               <th>S.No.</th>
               <th>Contract Name</th>
               <th>Location</th>
               <th>Contract Type</th>
               <th>Effective Date</th>
               <th>End Date</th>
            </tr>
         </thead>
    </table>
  </div>
</div>


@section('page-script')


<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/reports.js"></script>

<script type="module">
$(document).ready(function() {
});
</script>
@endsection

@endsection
