@extends('layouts/layoutMaster')
@section('title', ' Contracts')

<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/quill/typography.scss', 
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/quill/katex.scss', 
'resources/assets/vendor/libs/quill/editor.scss', 
'resources/assets/vendor/libs/select2/select2.scss', 
'resources/assets/vendor/libs/dropzone/dropzone.scss', 
'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 
'resources/assets/vendor/libs/tagify/tagify.scss',
'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/quill/katex.js', 
'resources/assets/vendor/libs/flatpickr/flatpickr.js', 
'resources/assets/vendor/libs/quill/quill.js', 
'resources/assets/vendor/libs/cleavejs/cleave.js', 
'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 
'resources/assets/vendor/libs/moment/moment.js', 
'resources/assets/vendor/libs/flatpickr/flatpickr.js', 
'resources/assets/vendor/libs/select2/select2.js', 
'resources/assets/vendor/libs/dropzone/dropzone.js', 
'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js', 
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/apex-charts/apexcharts.js'
])

<link href="{{ url('/') }}/assets/css/custom.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')

@vite([
'resources/assets/js/forms-file-upload.js',
'resources/assets/js/form-layouts.js',
'resources/assets/js/charts-apex.js'
])

<script type="module" src="{{ url('/') }}/assets/js/jquery.validate.min.js"></script>

<script type="module">
$(document).ready(function() {
  const donutChartEl = document.querySelector('#milestonesChart'),
    donutChartConfig = {
      chart: {
        height: 390,
        type: 'donut'
      },
      labels: ['Draft', 'Executed', 'Negotiation'],
      series: [{{$counts["draft"]}}, {{ $counts["executed"] }}, {{ $counts["negotiation"] }}],
      colors: ['#98c958', '#00a69c', '#009249'],
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
        show: true,
        position: 'bottom',
        markers: { offsetX: -3 },
        itemMargin: {
          vertical: 3,
          horizontal: 10
        },
        labels: {
          colors: "black",
          show: true,
          useSeriesColors: true
        }
      },
      plotOptions: {
        pie: {
          donut: {
            labels: {
              show: true,
              name: {
                fontSize: '2rem',
                fontFamily: 'Public Sans'
              },
              value: {
                fontSize: '1.2rem',
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
@section('content')

<div class="col-lg-12">
<div class=" bg-transparent shadow-none my-6 border-0">
  <div class="row p-0 pb-6 g-6">
    <div>
      <h5 class="mb-2">Welcome back,<span class="h4">{{ 'Username' }}</span></h5>
      <div class="col-12 col-lg-5">
        <p>Contracts requiring your attention</p>
      </div>        
    </div>
    <div class="col-12 col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
        <h5 class="card-title mb-0">Actionable Items</h5>
      </div>
       <div class="card-body d-flex align-items-end">
        <div class="w-100">
            <div class="row gy-3">
                <div class="col-md-4 col-6">
                    <div class="d-flex align-items-center">
                      <div class="badge rounded bg-label-primary me-4 p-2">
                        <div class="avatar-initial bg-transparent">
                          <div>
                            <i class="ti ti-pencil fs-large text-success"></i>
                          </div>
                        </div>
                      </div>
                      <div class="content-right">
                        <p class="mb-0 fw-medium">Review In Process</p>
                        <h4 class="text-dark fw-bold mb-0">{{ $counts["review"] }}</h4>
                      </div>
                </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="d-flex align-items-center">
                  <div class="badge rounded bg-label-primary me-4 p-2">
                    <div class="avatar-initial rounded bg-transparent">
                      <div>
                        <i class="fa-solid fa-spinner fs-large text-info"></i>
                      </div>
                    </div>
                  </div>
                  <div class="content-right">
                    <p class="mb-0 fw-medium">Approval In Process</p>
                    <h4 class="text-dark fw-bold mb-0">{{ $counts["approval"] }}</h4>
                  </div>
                </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="d-flex align-items-center">
                  <div class="badge rounded bg-label-primary me-4 p-2">
                    <div class="avatar-initial rounded bg-transparent">
                      <div>
                        <i class="fa-solid fa-file-signature fs-large text-primary"></i>
                      </div>
                    </div>
                  </div>
                  <div class="content-right">
                    <p class="mb-0 fw-medium">Sigining In Process</p>
                    <h4 class="text-dark fw-bold mb-0">{{ $counts["signing"] }}</h4>
                  </div>
                </div> 
                </div>
            </div>
        </div>
       </div>
      </div>
      <div class="d-flex justify-content-evenly flex-wrap gap-4 me-12 bg-white p-1 pb-2 mt-4">
        <p class="bg-dark-mejenta text-white p-2 text-center text-uppercase w-100 fw-bold dash-action-headers">Contract Activation</p>
        <div class="d-flex align-items-center gap-4 me-6 me-sm-0">
          <div class="avatar avatar-lg">
            <div class="avatar-initial bg-dark-primary">
              <div>
                <i class="fa-solid fa-hourglass-start fs-large text-white"></i>
              </div>
            </div>
          </div>
          <div class="content-right">
            <p class="mb-0 fw-medium">Pending Activation</p>
            <h4 class="text-dark fw-bold mb-0">{{ $counts["executed_pending"] }}</h4>
          </div>
        </div>
        <div class="d-flex align-items-center gap-4">
          <div class="avatar avatar-lg">
            <div class="avatar-initial bg-info rounded">
              <div>
                <i class="ti ti-alert-circle fs-large text-white"></i>
              </div>
            </div>
          </div>
          <div class="content-right">
            <p class="mb-0 fw-medium">Expired</p>
            <h4 class="text-dark fw-bold mb-0">{{ $counts["executed_expired"] }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-4 ps-md-4 ps-lg-6">
      <div class="align-items-center bg-white p-1" style="position: relative;">
        <p class="text-dark p-2 text-center text-uppercase w-100 fw-bold dash-action-headers">Contract Milestones</p>
        <div id="milestonesChart"></div>
      </div>
    </div>
    <div class="col-12 col-lg-3">
      <div class="d-flex justify-content-evenly flex-wrap gap-4 me-12 bg-white p-1 mt-4">
        <p class="bg-danger text-white p-2 text-center text-uppercase w-100 fw-bold dash-action-headers">Approaching Renewal</p>
        <div class="d-flex align-items-center gap-4">
          <div class="avatar avatar-lg">
            <div class="avatar-initial bg-danger rounded">
              <div>
                <i class="fa-solid fa-rotate-right fs-large text-white"></i>
              </div>
            </div>
          </div>
          <div class="content-right">
            <p class="mb-0 fw-medium">Approaching Renewal</p>
            <h4 class="text-dark fw-bold mb-0">0</h4>
          </div>
        </div>
      </div>
    </div>    
    <div class="col-12 col-lg-9">
      <div class="d-flex justify-content-evenly flex-wrap gap-4 me-12 bg-white p-4 mt-4">
        <p class="bg-warning text-white p-2 text-center text-uppercase w-100 fw-bold dash-action-headers">Actionable Items</p>
        <div class="d-flex align-items-center gap-4 me-6 me-sm-0">
          <div class="avatar avatar-lg">
            <div class="avatar-initial bg-success">
              <div>
                <i class="fa-solid fa-file-circle-check fs-large text-white"></i>
              </div>
            </div>
          </div>
          <div class="content-right">
            <p class="mb-0 fw-medium">Active</p>
            <h4 class="text-dark fw-bold mb-0">{{ $counts["executed_active"] }}</h4>
          </div>
        </div>
        <div class="d-flex align-items-center gap-4 me-6 me-sm-0">
          <div class="avatar avatar-lg">
            <div class="avatar-initial bg-secondary">
              <div>
                <i class="fa-solid fa-file-lines fs-large text-white"></i>
              </div>
            </div>
          </div>
          <div class="content-right">
            <p class="mb-0 fw-medium">Completed</p>
            <h4 class="text-dark fw-bold mb-0">{{ $counts["executed_completed"] }}</h4>
          </div>
        </div>
        <div class="d-flex align-items-center gap-4">
          <div class="avatar avatar-lg">
            <div class="avatar-initial bg-info rounded">
              <div>
                <i class="fa-solid fa-file-circle-xmark fs-large text-white"></i>
              </div>
            </div>
          </div>
          <div class="content-right">
            <p class="mb-0 fw-medium">Terminated</p>
            <h4 class="text-dark fw-bold mb-0">{{ $counts["executed_terminated"] }}</h4>
          </div>
        </div>
        <div class="d-flex align-items-center gap-4">
          <div class="avatar avatar-lg">
            <div class="avatar-initial bg-primary rounded">
              <div>
                <i class="fa-solid fa-paste fs-large text-white"></i>
              </div>
            </div>
          </div>
          <div class="content-right">
            <p class="mb-0 fw-medium">Renewed</p>
            <h4 class="text-dark fw-bold mb-0">{{ $counts["executed_renewed"] }}</h4>
          </div>
        </div>
      </div>
    </div>    
  </div>
</div>
</div>



<style>
.bg-light-success{
    background-color: #98c958 !important;
}

.bg-dark-success{
    background-color: #009249 !important;
}

.bg-dark-primary{
    background-color: #1a75b9 !important;
}

.bg-dark-mejenta{
    background-color: #00a69c !important;
}

.dash-action-headers{
    font-size: 1.2rem;
}
</style>
@endsection