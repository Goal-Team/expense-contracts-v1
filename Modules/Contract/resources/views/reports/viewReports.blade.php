@extends('layouts/layoutMaster')

@section('title', 'Reports')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
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
  @include('contract::reports.report-menu', ['pageTitle' => $pageTitle])
  @if(isset($_COOKIE['filterByStatus']))
    <span role="button" class="btn btn-md btn-{{$statusClass[$_COOKIE['filterByStatus']]}}" id="statusTitle">{{ucfirst($statusLabels[$_COOKIE['filterByStatus']])}}</h5>
  @endif
</h4>

<div class="row mb-4 g-4 SectionToPrint">
  <div class="col-md-12">    
    <div class="card h-100">
      <div class="card-body row widget-separator g-0">
        <div class="col-sm-2 d-flex justify-content-between border-shift border-end">
          <div>
            <h2 class="text-primary d-flex align-items-center gap-1 mb-2">{{ $allContracts }}
              
            </h2>
            <p class="h6 mb-1">Total Contracts</p>
            <p class="pe-2 mb-2">By {{$pageTitle}}</p>
            <span class="badge bg-label-primary p-2 mb-sm-0 d-none">+5 This week</span>
            <hr class="d-sm-none">
          </div>
        </div>
        <div class="col-sm-7 gap-2 text-nowrap d-flex flex-column justify-content-between ps-sm-4 pt-2 py-sm-2">
          @foreach($status as $instatus => $vall)
          @php
              $prog_width = 0;
              if($allContracts > 0){
                  $prog_width = (($vall / $allContracts) * 100);
              }
          @endphp
          <a href="javascript:;" class="loadstatus{{$executed}}" id="status_{{$instatus}}" data-stat="{{$instatus}}">
            <div class="d-flex align-items-center gap-3">
              <small style="width:20rem;" class="text-wrap">{{ ucfirst($statusLabels[$instatus]) }}</small>
              <div class="progress w-100" style="height:10px;">
                <div class="progress-bar bg-{{$statusClass[$instatus]}}" role="progressbar" style="width: {{$prog_width}}%" aria-valuenow="{{ $vall }}" aria-valuemin="0" aria-valuemax="{{ $allContracts }}"></div>
              </div>
              <small class="w-px-20 text-end">{{ $vall }}</small>
            </div>
          </a>
          @endforeach
        </div>
        <div class="col-md-3">
          <div class="mb-5 text-center">
            <h4 class="mb-2 text-nowrap">Contracts Stats</h4>
            <p class="mb-0 d-none"> <span class="me-2">0 Approved</span> <span class="badge bg-label-success">+8.4%</span></p>
          </div>
          <div id="statusChart" class="text-center"></div>
          <div class="d-none">
            <h5 class="mb-2 fw-normal">
              <span class="text-success me-1">50%</span>Approval Ratio
            </h5>
            <small class="text-muted">Weekly Report</small>
          </div>
        </div>        
      </div>
    </div>
  </div>
</div>

<!-- review List Table -->
<div class="card">
  <div class="card-datatable table-responsive">
    <table class="contracts-report table border-top">
         <thead>
            <tr>
               <th>S.No.</th>
               <th>Contract Name</th>
               <th>Location</th>
               <th>Contract Type</th>
               <th>Effective Date</th>
               <th>End Date</th>
               <th>Contract Value</th>
               <!-- <th class="text-center">Status</th> -->
               <!--<th>Parties</th>-->
               <!--<th>Action</th>-->
            </tr>
         </thead>
    </table>
  </div>
</div>

@section('page-script')

<script type="module">
$(document).ready(function() {

  const donutChartEl = document.querySelector('#statusChart'),
    donutChartConfig = {
      chart: {
        height: 175,
        parentHeightOffset: 0,
        type: 'donut'
      },
      labels: [{!! "'" . implode ( "', '",array_values($statusLabels)). "'" !!}],
      series: [{{implode(',',array_values($status))}}],
      stroke: {
        show: false,
        curve: 'smooth'
      },
      colors: [{!! "'".implode ( "', '",array_values($statusColor))."'" !!}],
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
              show: true,
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
