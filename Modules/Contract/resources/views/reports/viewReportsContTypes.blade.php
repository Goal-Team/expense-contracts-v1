@extends('layouts/layoutMaster')

@section('title', 'Reports')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
  'resources/assets/vendor/libs/jstree/jstree.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
  'resources/assets/vendor/libs/jstree/jstree.js',
'resources/assets/vendor/libs/select2/select2.js',
])
@endsection

@section('page-style')
<link rel="stylesheet" href="{{url('/')}}/Modules/Contract/resources/assets/sass/reports.css" />
@endsection

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Contracts / </span>Reports
  @include('contract::reports.report-menu', ['pageTitle' => 'By Type'])
</h4>

<style>
#layout-menu{ display:none; }
.layout-page{ padding-left: 0 !important; }
h4.py-3.mb-4{ display: none; }
</style>

<input type="hidden" id="currentLocation" value="{{ request()->get('locationId') ?? 0}}" />
<button type="button" id="downloadCanva" class="d-none">Download</button>
<div class="row mb-4 g-4">
  <div class="col-md-12">
    <div class="card h-100 SectionToPrint" id="sectionToCanva">
      <div class="card-body row">   
        <div class="col-md-3 border-shift border-end">
          <div class="d-flex flex-column align-items-center h-100">
              <h2 class="text-primary d-flex align-items-center gap-1 mb-2">{{ $allContracts }}</h2>
              <p class="h6 mb-1">Total Contracts</p>
              <p class="pe-2 mb-2">By Type</p>
              <span class="badge bg-label-primary p-2 mb-sm-0 d-none">+5 This week</span>
              <hr class="d-sm-none">
              <div id="typesChart"></div>
          </div>
        </div>
        <div class="col-sm-9 gap-2 text-nowrap d-flex flex-column justify-content-between ps-sm-4 pt-2 py-sm-2 d-none">
          @php
          $avalTyps = "";
          $unavalTyps = "";          
          foreach($contractTypes as $contype){
        
            $prog_width = 0;
            $conTypeCount = 0;
            if(isset($contTypeCountArr[$contype->contract_type_id]) && $allContracts > 0){
                $conTypeCount = $contTypeCountArr[$contype->contract_type_id];
                $prog_width = ( $conTypeCount / $allContracts) * 100;
            }
            if($conTypeCount >0){
              $avalTyps .= '                  
            <div class="d-flex align-items-center gap-3">
              <small style="width:50rem;" class="text-wrap">'. ucfirst($contype->contract_type) .'</small>
              <div class="progress w-100" style="height:10px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: '.$prog_width.'%" aria-valuenow="'. $conTypeCount .'" aria-valuemin="0" aria-valuemax="'. $allContracts .'"></div>
              </div>
              <small class="badge bg-primary">'. $conTypeCount .'</small>
            </div>';
            }else{
              $unavalTyps .= '                  
            <div class="d-flex align-items-center gap-3">
              <small style="width:50rem;" class="text-wrap">'. ucfirst($contype->contract_type) .'</small>
              <div class="progress w-100" style="height:10px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: '.$prog_width.'%" aria-valuenow="'. $conTypeCount .'" aria-valuemin="0" aria-valuemax="'. $allContracts .'"></div>
              </div>
              <small class="badge bg-primary">'. $conTypeCount .'</small>
            </div>';              
            }
          }
          @endphp
          <div class="nav-align-top mb-4 p-2">
          <ul class="nav nav-tabs nav-fill" role="tablist">
            <li class="nav-item">
              <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#contracts-avl" aria-controls="contracts-avl" aria-selected="true">Available</button>
            </li>
            <li class="nav-item">
              <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#contracts-un-avl" aria-controls="contracts-un-avl" aria-selected="false">Not Available</button>
            </li>
          </ul>         
          <div class="tab-content pb-0">
            <div class="tab-pane fade show active" id="contracts-avl" role="tabpanel">
                <div class="gap-2 text-nowrap d-flex flex-column">
                    {!!$avalTyps!!}
                </div>
            </div>
            <div class="tab-pane fade" id="contracts-un-avl" role="tabpanel">
                <div class="gap-2 text-nowrap d-flex flex-column">
                    {!!$unavalTyps!!}
                </div>                
            </div>
          </div>
         </div>
        </div>
        <div class="col-9 row">
          <h5 class="card-title text-center">Location               
		<select class="form-select select2 text-center" id="locationChangeCtype" style="margin:0 auto">
                    <option value="">-Select-</option>
                    @foreach ($branchLocation as $branch)
                    <option value="{{ $branch->id }}" {{ (request()->get('locationId') ?? 0) == $branch->id ? 'selected' : '' }}>{{ $branch->BranchName}}</option>
                    @endforeach
                </select> Wise Count  </h5>
                
                <form id="exportLocationForm" method="POST" action="{{ url('/contracts/reports/export-location-type') }}" class="d-inline ms-2">
                    @csrf
                    <input type="hidden" name="locationId" class="export-location-id" value="{{ request()->get('locationId') ?? 0 }}">
                  <button type="submit" class="btn btn-outline-success btn-sm" id="exportLocationBtn" title="Export location-wise contract type counts to Excel">
                    <i class="fas fa-file-excel"></i> Export Type Excel
                    </button>
                </form>

                <form id="exportSubstatusForm" method="POST" action="{{ url('/contracts/reports/export-type-substatus') }}" class="d-inline ms-2">
                    @csrf
                    <input type="hidden" name="locationId" class="export-location-id" value="{{ request()->get('locationId') ?? 0 }}">
                    <button type="submit" class="btn btn-outline-primary btn-sm" id="exportSubstatusBtn" title="Export contract type vs substatus counts to Excel">
                        <i class="fas fa-file-excel"></i> Export Substatus Excel
                    </button>
                </form>

          <div id="departmentTree" class="col-6">
            <ul>
              @php
                $avalDeps = "";
                $unavalDeps = "";
                $contTypeNameArr = [];
                foreach($contractTypes as $contype){
                  $prog_width = 0;
                  $contypeCount = 0;
                  if(isset($contTypeCountArr[$contype->contract_type_id]) && $allContracts > 0){
                      $contTypeNameArr[$contype->contract_type_id] = '"'.$contype->contract_type.'"';
                      $contypeCount = $contTypeCountArr[$contype->contract_type_id];
                  }

                  echo '<li class="py-1 position-relative showLocationTable" data-ctype="'.$contype->contract_type_id.'" data-ctypename="'.$contype->contract_type.'" data-jstree=\'{"icon" : "ti ti-building-bank text-warning"}\'>'.$contype->contract_type.' <span class="badge badge-center bg-label-secondary bg-glow position-absolute end-0 me-1">'.$contypeCount.'</span></li>';
                }
              @endphp
            </ul>
          </div>
          <div class="col-6 card">
            <div class="card-datatable table-responsive" id="locationListType">
              <table class="table" id="locationTable">              
                <thead>
                  <tr>
                    <!-- <th>S.No</th> -->
                    <th>Location</th>
                    <th class="text-center">Available</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>                  
        </div>
      </div>
      </div>
    </div>
  </div>
</div>

@section('page-script')

<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/reports.js"></script>

<script type="module">
$(document).ready(function() {
  const donutChartEl = document.querySelector('#typesChart'),
    donutChartConfig = {
      chart: {
        height: 175,
        width: 200,
        parentHeightOffset: 0,
        type: 'donut'
      },
      labels: [{!!implode(',',array_values($contTypeNameArr))!!}],
      series: [{{implode(',',array_values($contTypeCountArr))}}],
      stroke: {
        show: false,
        curve: 'smooth'
      },
      dataLabels: {
        enabled: false,
        formatter: function (val, opts) {
            return opts.w.config.series[opts.seriesIndex]
        }
      },
      legend: {
        show: false,
        position: 'bottom',
        markers: { offsetX: -3 },
        itemMargin: {
          vertical: 3,
          horizontal: 10
        },
        labels: {
          colors: "black",
          show: true,
          useSeriesColors: false
        }
      },
      plotOptions: {
        pie: {
          donut: {
            labels: {
              show: false,
              name: {
                fontSize: '12.5px',
              },
              value: {
                fontSize: '15px',
                color: "black"
              },
              total: {
                show: false,
                fontSize: '1.5rem',
                color: "black",
                label: 'Operational',
                formatter: function (w) {
                  return '42%';
                }
              }
            }
          }
        }
      },
      responsive: [
        {
          breakpoint: 992,
          options: {
            chart: {
              height: 380
            },
            legend: {
              position: 'bottom',
              labels: {
                colors: 'black',
                useSeriesColors: true
              }
            }
          }
        },
        {
          breakpoint: 576,
          options: {
            chart: {
              height: 320
            },
            plotOptions: {
              pie: {
                donut: {
                  labels: {
                    show: true,
                    name: {
                      fontSize: '1.5rem'
                    },
                    value: {
                      fontSize: '1rem'
                    },
                    total: {
                      fontSize: '1.5rem'
                    }
                  }
                }
              }
            },
            legend: {
              position: 'bottom',
              labels: {
                colors: "black",
                useSeriesColors: true
              }
            }
          }
        },
        {
          breakpoint: 420,
          options: {
            chart: {
              height: 280
            },
            legend: {
              show: false
            }
          }
        },
        {
          breakpoint: 360,
          options: {
            chart: {
              height: 250
            },
            legend: {
              show: false
            }
          }
        }
      ]
    };
  if (typeof donutChartEl !== undefined && donutChartEl !== null) {
    const donutChart = new ApexCharts(donutChartEl, donutChartConfig);
    donutChart.render();
  }
  
  // Keep export forms in-sync with the location select
  $('.export-location-id').val($('#locationChangeCtype').val() || 0);
  $('#locationChangeCtype').on('change', function(){
      $('.export-location-id').val($(this).val() || 0);
  });  
});
</script>

@endsection

@endsection
