@extends('layouts/layoutMaster')

@section('title', 'Reports')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
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

@section('page-style')
<link rel="stylesheet" href="{{url('/')}}/Modules/Contract/resources/assets/sass/reports.css" />
@endsection

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Contracts / </span>Reports
  @include('contract::reports.report-menu', ['pageTitle' => 'By Departments'])
</h4>

<div class="row mb-4 g-4">
  <div class="col-md-12">
    <div class="card h-100 SectionToPrint">
      <div class="card-body row">
        <div class="col-md-3 border-shift border-end">
          <div class="d-flex flex-column align-items-center h-100">
            <h2 class="text-primary mb-2 text-center">{{ $allContracts }}</h2>
            <p class="h6 mb-1 text-center">Total Contracts</p>
            <p class="mb-2 text-center">By Departments</p>
            <span class="badge bg-label-primary p-2 mb-sm-0 d-none">+5 This week</span>
            <div id="deprtmentsChart"></div>
          </div>
        </div>
        <div class="col-sm-9 ps-sm-4 pt-2 py-sm-2">
          @php
          $avalDeps = "";
          $unavalDeps = "";
          $contDeptNameArr = [];
		  $contDeptCountsArr = [];
          foreach($contractDepts as $condept){
        
              $prog_width = 0;
              $conDeptCount = 0;
			  $conDeptValue = 0;
              if(isset($contDeptCountArr[$condept->id]) && $allContracts > 0){
                  $contDeptNameArr[$condept->id] = '"'.$condept->name.'"';
				  $conDeptCount = $contDeptCountArr[$condept->id]['count'];
				  $conDeptValue = currency_formatter(env('default_currency'),$contDeptCountArr[$condept->id]['value']);
				  $contDeptCountsArr[$condept->id] = $conDeptCount;
                  $prog_width = ( $contDeptCountArr[$condept->id]['value'] / $allValues) * 100;
              }

          if($conDeptCount >0){
            $avalDeps .= '<div class="d-flex align-items-center gap-3 cursor-pointer loadDeptmentData" data-cdept="'.$condept->id.'">
              <small style="width:50rem;" class="text-wrap">'.ucfirst($condept->name).'</small><span class="btn btn-sm btn-primary bg-glow ms-2">'. $conDeptCount.'</span>
              <div class="w-100">
                  <div class="progress w-100" style="height:10px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: '.$prog_width.'%" aria-valuenow="'. $conDeptValue.'" aria-valuemin="0" aria-valuemax="'. $allValues.'"></div>
                  </div>
                  <span class="text-warning w-100 text-center d-inline-block">'. $conDeptValue.'</span>
              </div>
            </div>';
          }else{
            $unavalDeps .= '<div class="d-flex align-items-center gap-3">
              <small style="width:50rem;" class="text-wrap">'.ucfirst($condept->name).'</small>
              <div class="progress w-100" style="height:10px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: '.$prog_width.'%" aria-valuenow="'. $conDeptValue.'" aria-valuemin="0" aria-valuemax="'. $allValues.'"></div>
              </div>
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
                      {!!$avalDeps!!}
                  </div>
              </div>
              <div class="tab-pane fade" id="contracts-un-avl" role="tabpanel">
                  <div class="gap-2 text-nowrap d-flex flex-column">
                      {!!$unavalDeps!!}
                  </div>                
              </div>
            </div>
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
               <th>Value</th>
            </tr>
         </thead>
    </table>
  </div>
</div>


@section('page-script')

<script type="module">
$(document).ready(function() {
  const donutChartEl = document.querySelector('#deprtmentsChart'),
    donutChartConfig = {
      chart: {
        height: 175,
        width: 200,
        parentHeightOffset: 0,
        type: 'donut'
      },
      labels: [{!!implode(',',array_values($contDeptNameArr))!!}],
      series: [{{implode(',',array_values($contDeptCountsArr))}}],
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
});
</script>
@endsection

@endsection
