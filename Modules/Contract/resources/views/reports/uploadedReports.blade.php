@extends('layouts/layoutMaster')

@section('title', 'Reports')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
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
<div class="container">
</div>
<div class="row mb-4 g-4 SectionToPrint">
<div class="col-12">
    @if (session('success'))
    <p class="alert alert-success float-start">
        {{ session('success') }}
    </p>
    @endif
    @if (session('error'))
    <p class="alert alert-danger float-start">
        {{ session('error') }}
    </p>
    @endif
  <div class="float-end">
     <a href="{{ url('/contracts/builk-import') }}"><button type="button" class="btn btn-warning waves-effect">Import More <i class="ms-2 ti ti-table-down me-1"></i></i></button></a>
  </div>
</div>        
@if((is_array($dataStored) || is_object($dataStored)) && count($dataStored) > 0)
  <div class="col-md-12">    
    <div class="card h-100">
    <div class="card-header d-flex justify-content-between">
        <div class="card-title m-0">
          <h5 class="mb-0">Uploaded Contracts</h5>
        </div>      
    </div>
      <div class="card-body row widget-separator g-0">
        <div class="col-sm-2 d-flex justify-content-between border-shift border-end d-none">
        </div>
        <div class="col-sm-12 d-flex justify-content-between border-shift border-end">
            <div class="card-datatable table-responsive">
                
                <table class="table border-top" id="storedContracts">
                     <thead>
                        <tr>
                           <th>S.No.</th>
                           <th>Contract Status</th>
                           <th>Contract Name</th>
                           <th>Location</th>
                           <th>Contract Type</th>
                           <th>Effective Date</th>
                           <th>End Date</th>
                           <th>Contract Value</th>
                           <th class="text-center">Actions</th>
                        </tr>
                     </thead>
                     <tbody>
                         @php
                         
                            $statusColorClass = array(
                                'active' => 'success',
                                'expired' => 'danger',
                                'pending' => 'warning',
                                'renewed' => 'info',
                                'terminated' => 'danger',
                                'completed' => 'secondary'
                            );                           
                
                            $columnKeys = ['substatus','contract_name','location_branch','contract_type','fixed_date','contract_end_date', 'currency_value_converted', 'id'];
                            $tableHtml = '';
                            $sno = 0;
                
                            foreach($dataStored as $data){
                                $sno++;
                                
                                $tableHtml .= '<tr>';
                                $tableHtml .='<td>'.$sno.'</td>';
                                foreach($columnKeys as $ck){
                                    if($ck == 'id'){
                                        $tableHtml .='<td><a target="new" href="'.url('/contracts/'.$data->$ck).'">View</a></td>'; 
                                    }
                                    elseif($ck == 'substatus'){
                                        $colorClassStatus = $statusColorClass[$data->$ck] ?? 'primary';
                                        $statusTag = "<span class='text-white border rounded bg-$colorClassStatus p-1'>".ucfirst($data->$ck)."</span>";                                    
                                        $tableHtml .='<td>'.$statusTag.'</td>';
                                    }
                                    else{
                                        $tableHtml .='<td>'.$data->$ck.'</td>';
                                    }
                                }
                                $tableHtml .='</tr>';
                            }
                            echo $tableHtml;
                         @endphp
                     </tbody>
                </table>
               
              </div>            
        </div>
      </div>
    </div>
  </div>
@endif
</div>
@section('page-script')

<script type="module">
$(document).ready(function() {
    if ($('#storedContracts').length){
        $('#storedContracts').DataTable();
    }
});
</script>
@endsection
@endsection
