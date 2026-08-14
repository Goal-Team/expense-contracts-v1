@extends('layouts/layoutMaster')

@section('title', 'Reports')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
])
@endsection

@section('page-style')
<link rel="stylesheet" href="{{url('/')}}/Modules/Contract/resources/assets/sass/reports.css" />
@endsection

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Contracts / </span>Reports
  @include('contract::reports.report-menu', ['pageTitle' => $pageTitle])
</h4>

<div class="row mb-4 g-4">
  <!-- Earning Reports Tabs-->
  <div class="col-12 col-xl-12 mb-4 order-1 order-lg-0 SectionToPrint">
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <div class="card-title m-0">
          <h5 class="mb-0">Aging Reports</h5>
          <small class="text-muted">By Monthly</small>
        </div>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="budgetId" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span id="yearSelected"></span>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="budgetId">
            @for($i=$fromYear; $i<=$toYear; $i++)
              <a class="dropdown-item loadyeardata" data-year="{{$i}}" href="javascript:void(0);">
                {{$i}}
              </a>
            @endfor
          </div>
        </div>        
      </div>
      <div class="card-body">
        <ul class="nav nav-tabs widget-nav-tabs pb-3 gap-4 mx-1 d-flex flex-nowrap" role="tablist" id="expiredContractsNav">
          <li class="nav-item">
            <a href="javascript:void(0);" id="expired" class="nav-link btn active d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-expired" aria-controls="navs-expired" aria-selected="true">
              <div class="badge bg-label-danger rounded p-2"><i class="ti ti-file-report ti-sm"></i></div>
              <h6 class="tab-widget-title mb-0 mt-2">Expired</h6>
              <span class="position-absolute top-50 start-100 translate-middle badge badge-center rounded-pill bg-danger text-white">{{$allContractsExpired}}</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="javascript:void(0);" id="active" class="nav-link btn d-flex flex-column align-items-center justify-content-center" role="tab" data-bs-toggle="tab" data-bs-target="#navs-expiring" aria-controls="navs-expiring" aria-selected="false">
              <div class="badge bg-label-warning rounded p-2"><i class="ti ti-file-report ti-sm"></i></div>
              <h6 class="tab-widget-title mb-0 mt-2">Expiring</h6>
              <span class="position-absolute top-50 start-100 translate-middle badge badge-center rounded-pill bg-warning text-white">{{$allContractsExpirin}}</span>
            </a>
          </li>
        </ul>
        <div class="tab-content p-0 ms-0 ms-sm-2">
          <div class="tab-pane fade show active" id="navs-expired" role="tabpanel">
            <div id="expiredContractsReport"></div>
          </div>
          <div class="tab-pane fade" id="navs-expiring" role="tabpanel">
            <div id="expiringContractsReport"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- List Table -->
<div class="card">
  <div class="card-datatable table-responsive">
    <table class="contracts-report-expired table border-top">
         <thead>
            <tr>
               <th>S.No.</th>
               <th>Contract Name</th>
               <th>Location</th>
               <th>Contract Type</th>
               <th>Effective Date</th>
               <th>End Date</th>
               <th>Contract Value</th>
               <!-- <th class="text-center d-none">Status</th> -->
            </tr>
         </thead>
    </table>
  </div>
</div>

@section('page-script')

<script type="module">
$(document).ready(function() {

  // Expire Reports Bar Chart
  // --------------------------------------------------------------------
  const expiredContractsReportEl = document.querySelector('#expiredContractsReport'),
  expiredContractsReportConfig = {
      chart: {
        height: 258,
        parentHeightOffset: 0,
        type: 'bar',
        toolbar: {
          show: false
        }
      },
      plotOptions: {
        bar: {
          columnWidth: '40%',
          startingShape: 'rounded',
          borderRadius: 4,
          distributed: true,
          dataLabels: {
            position: 'top'
          }
        }
      },
      grid: {
        show: true,
        padding: {
          top: 0,
          bottom: 0,
          left: -10,
          right: -10
        }
      },
      colors: [{!!"'" . implode ( "', '",array_values($expiredContBarColor)). "'"!!}],
      dataLabels: {
        enabled: false,
        formatter: function (val) {
          return val;
        },
        //offsetY: 5,
        style: {
          fontSize: '12px',
          colors: ['#CCC'],
          fontWeight: '800',
          fontFamily: 'Public Sans'
        }
      },
      series: [
        {
          data: [{{implode(',',array_values($expiredContractsDat))}}]
        }
      ],
      legend: {
        show: false
      },
      tooltip: {
        enabled: true,
        custom: function({series, seriesIndex, dataPointIndex, w}) {
          return '<span class="badge bg-label-danger mx-auto">' + series[seriesIndex][dataPointIndex] + '</span>'
        }    
      },
      xaxis: {
        categories: [{!!"'" . implode ( "', '",array_values($monthsXais)). "'"!!}],
        axisBorder: {
          show: true,
          color: '#ccc'
        },
        axisTicks: {
          show: false
        },
        labels: {
          style: {
            colors: '#ccc',
            fontSize: '13px',
            fontFamily: 'Public Sans'
          },
          formatter: function (val) {
            return val.slice(0,3);
          }         
        }
      },
      yaxis: {
        labels: {
          offsetX: -15,
          formatter: function (val) {
            return parseInt(val ? val : 0);
          },
          style: {
            fontSize: '13px',
            colors: '#ccc',
            fontFamily: 'Public Sans'
          },
          tickAmount: 10
        }
      },
      responsive: [
        {
          breakpoint: 1441,
          options: {
            plotOptions: {
              bar: {
                columnWidth: '41%'
              }
            }
          }
        },
        {
          breakpoint: 590,
          options: {
            plotOptions: {
              bar: {
                columnWidth: '61%',
                borderRadius: 5
              }
            },
            yaxis: {
              labels: {
                show: false
              }
            },
            grid: {
              padding: {
                right: 0,
                left: -20
              }
            },
            dataLabels: {
              style: {
                fontSize: '12px',
                fontWeight: '400'
              }
            }
          }
        }
      ]
  };
  if (typeof expiredContractsReportEl !== undefined && expiredContractsReportEl !== null) {
    const expiredContractsReports = new ApexCharts(expiredContractsReportEl, expiredContractsReportConfig);
    expiredContractsReports.render();
  }  
  const expiringContractsReport = document.querySelector('#expiringContractsReport'),
    expiringContractsReportConfig = {
      chart: {
        height: 258,
        parentHeightOffset: 0,
        type: 'bar',
        toolbar: {
          show: false
        }
      },
      plotOptions: {
        bar: {
          columnWidth: '40%',
          startingShape: 'rounded',
          borderRadius: 4,
          distributed: true,
          dataLabels: {
            position: 'top'
          }
        }
      },
      grid: {
        show: true,
        padding: {
          top: 0,
          bottom: 0,
          left: -10,
          right: -10
        }
      },
      colors: [{!!"'" . implode ( "', '",array_values($expirinContBarColor)). "'"!!}],
      dataLabels: {
        enabled: false,
        formatter: function (val) {
          return val;
        },
        //offsetY: 5,
        style: {
          fontSize: '12px',
          colors: ['#CCC'],
          fontWeight: '800',
          fontFamily: 'Public Sans'
        }
      },
      series: [
        {
          data: [{{implode(',',array_values($expirinContractsDat))}}]
        }
      ],
      legend: {
        show: false
      },
      tooltip: {
        enabled: true,
        custom: function({series, seriesIndex, dataPointIndex, w}) {
          return '<span class="badge bg-label-warning">' + series[seriesIndex][dataPointIndex] + '</span>'
        } 
      },
      xaxis: {
        categories: [{!!"'" . implode ( "', '",array_values($monthsXais)). "'"!!}],
        axisBorder: {
          show: true,
          color: '#ccc'
        },
        axisTicks: {
          show: false
        },
        labels: {
          style: {
            colors: '#ccc',
            fontSize: '13px',
            fontFamily: 'Public Sans'
          },
          formatter: function (val) {
            return val.slice(0,3);
          }
        }
      },
      yaxis: {
        labels: {
          offsetX: -15,
          formatter: function (val) {
            return parseInt(val);
          },
          style: {
            fontSize: '13px',
            colors: '#ccc',
            fontFamily: 'Public Sans'
          },
          min: 10,
          max: 60000,
          tickAmount: 10
        }
      },
      responsive: [
        {
          breakpoint: 1441,
          options: {
            plotOptions: {
              bar: {
                columnWidth: '41%'
              }
            }
          }
        },
        {
          breakpoint: 590,
          options: {
            plotOptions: {
              bar: {
                columnWidth: '61%',
                borderRadius: 5
              }
            },
            yaxis: {
              labels: {
                show: false
              }
            },
            grid: {
              padding: {
                right: 0,
                left: -20
              }
            },
            dataLabels: {
              style: {
                fontSize: '12px',
                fontWeight: '400'
              }
            }
          }
        }
      ]
    };
  if (typeof expiringContractsReport !== undefined && expiringContractsReport !== null) {
    const expiringContractsReports = new ApexCharts(expiringContractsReport, expiringContractsReportConfig);
    expiringContractsReports.render();
  }  
});
</script>
@endsection

@endsection
