@extends('layouts/layoutMaster')

@section('title', 'Reports')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
  'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
  'resources/assets/vendor/libs/select2/select2.js'
])
@endsection


@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Contracts / </span>Reports
  @include('contract::reports.report-menu', ['pageTitle' => 'Exception'])
</h4>

<div class="row mb-4 g-4">
<div class="col-12 col-lg-12 mb-4 order-3 order-xl-0 SectionToPrint">
    <div class="card h-100">
      <div class="card-header">
        <div class="card-title mb-0">
          <h5 class="m-0">Exceptions overview</h5>
          <small>Total <span class="badge bg-dark rounded">{{$allContracts}}</span> Contracts</small>
        </div>
      </div>
      <div class="card-body">
        @php 
        $topLabelHtml = "";
        $progessBarHtml = "";
          foreach($exception_count as $excep_ => $vall){
          
                $prog_width = 33.33;
                if($allContracts > 0){
                  //$prog_width = (($vall / $allContracts) * 100);
                }
            
            $topLabelHtml .= '<div class="vehicles-progress-label on-the-way-text" style="width:'.$prog_width.'%;">'.ucfirst($exception_count_label[$excep_]).'</div>';         
            $progessBarHtml .= '<div class="loadExceptionData progress-bar fw-medium text-start bg-'.$exception_count_color[$excep_].' text-white fw-bold fs-6 text-center px-3 rounded-0 cursor-pointer" data-exception="'.$excep_.'" data-cextype="'.$excep_.'" role="progressbar" style="width: '.$prog_width.'%" aria-valuenow="'.$prog_width.'" aria-valuemin="0" aria-valuemax="'.$allContracts.'">'.$vall.'</div>';         
          }
        @endphp
        <div class="d-flex vehicles-progress-labels mb-3">
          {!!$topLabelHtml!!}
        </div>
        <div class="vehicles-overview-progress progress rounded-2 mb-3" style="height: 46px;">
          {!!$progessBarHtml!!}
        </div>
      </div>
    </div>
  </div>
</div>

<!-- review List Table -->
<div class="card">
  <div class="card-datatable table-responsive">
    <table class="table border-top" id="exceptionTable">
         <thead>
            <tr>
               <th>S.No.</th>
               <th>Contract</th>
               <th>Prev Contract</th>
               <th>Exception Details</th>
            </tr>
         </thead>
    </table>
  </div>
</div>
@section('page-script')

<script type="module">
$(document).ready(function() {
});
</script>
@endsection

@endsection
