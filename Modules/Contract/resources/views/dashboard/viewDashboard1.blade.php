@extends('layouts/layoutMaster')

@section('title', 'Contracts Dashboard')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
'resources/assets/vendor/libs/select2/select2.scss',
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/apex-charts/apexcharts.js',
'resources/assets/vendor/libs/select2/select2.js',
])
@endsection

@section('page-script')
@vite([
'resources/assets/js/cards-statistics.js'
])

<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/dashboard.js"></script>
<style>
    #milestonesChart .apexcharts-canvas{
        margin: 0 auto;
    }
</style>
<script type="module">
$(document).ready(function() {
  const donutChartEl = document.querySelector('#milestonesChart'),
    donutChartConfig = {
      chart: {
        height: 200,
        width: 200,
        parentHeightOffset: 0,
        type: 'donut'
      },
      labels: ['Draft', 'Executed', 'Negotiation'],
      series: [{{$counts["draft"]}}, {{ $counts["executed"] }}, {{ $counts["negotiation"] }}],
      colors: ['#28c76f', '#00cfe8', '#ff9f43'],
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
              width: 250,
              height: 250
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
              width: 250,
              height: 250
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
              width: 150,
              height: 150
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
              width: 150,
              height: 150
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
  
  // Radial Bar Chart
  // --------------------------------------------------------------------
  const radialBarChartEl = document.querySelector('#actionableChart'),
    radialBarChartConfig = {
      chart: {
        height: 320,
        type: 'radialBar'
      },
      colors: ['#7367f0', '#00cfe8', '#ff9f43'],
      plotOptions: {
        radialBar: {
          size: 100,
          hollow: {
            size: '40%'
          },
          track: {
            margin: 10,
            background: '#ccc8f9'
          },
          dataLabels: {
            name: {
              fontSize: '1.5rem'
            },
            value: {
              fontSize: '1rem',
              formatter: function (val) {
                return val
              }              
            },
            total: {
              show: false,
              fontWeight: 400,
              fontSize: '1.3rem',
              color: "#000000",
              label: 'Comments',
              formatter: function (w) {
                return '80%';
              }
            }
          }
        }
      },
      responsive: [
        {
          breakpoint: 1400,
          options: {
            chart: {
              height: 275
            }
          }
        },          
        {
          breakpoint: 992,
          options: {
            chart: {
              height: 250
            }
          }
        },
        {
          breakpoint: 576,
          options: {
            chart: {
              height: 250
            }
          }
        },
        {
          breakpoint: 420,
          options: {
            chart: {
              height: 275
            }
          }
        },
        {
          breakpoint: 360,
          options: {
            chart: {
              height: 250
            }
          }
        }
      ],      
      grid: {
        borderColor: "#ccc8f7",
        padding: {
          top: -25,
          bottom: -20
        }
      },
      legend: {
        show: false,
        position: 'bottom',
        labels: {
          colors: "#CCC",
          useSeriesColors: false
        }
      },
      stroke: {
        lineCap: 'round'
      },
      series: [{{ $counts["review"] }}, {{ $counts["approval"] }}, {{ $counts["signing"] }}],
      labels: ['Review', 'Approval', 'Signing'],
    };
  if (typeof radialBarChartEl !== undefined && radialBarChartEl !== null) {
    const radialChart = new ApexCharts(radialBarChartEl, radialBarChartConfig);
    radialChart.render();
  }  
});
</script>
@endsection

@section('content')

<h4 class="py-3 mb-4"><span class="text-muted fw-light">Welcome Back</span> {{ Helper::userInfo()->FirstName ?? '' }}
</h4>

@foreach($approvalsArr as $appr)
    @if(count($appr) == 1 && $appr[0]->approval_status == 'pending' && Helper::accessInfo(json_decode($appr[0]->username)->email ?? "", false))
    @php
    $stusMy[$contractStatus[$appr[0]->contract_id]]++;
    $stusMy['all']++;
    @endphp
    @else
       @foreach ($appr as $appr_)
          @if($appr_->approval_status == 'pending' && Helper::accessInfo(json_decode($appr_->username)->email ?? "", false))
          @php
          $stusMy[$contractStatus[$appr[0]->contract_id]]++;
          $stusMy['all']++;
          @endphp
          @endif
       @endforeach
    @endif
@endforeach
<form action="{{url('/filterDash')}}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <h4>Filters</h4>
        <div class="col-lg-4 col-md-6 mb-4">
            <select class="form-select select2 contracttype" multiple name="contracttype[]" id="contracttype">
             @foreach ($contractTypes as $contractType)
             <option value="{{ $contractType->contract_type_id }}" {{ in_array($contractType->contract_type_id,$selcontype) ? "selected" : "" }}>
                {{ $contractType->contract_type }}
             </option>
             @endforeach
            </select>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <select class="form-select select2" multiple name="contractlocs[]" id="contractlocs">
                @foreach ($branchs as $branch)
                    <option value="{{ $branch->id }}" {{ in_array($branch->id, $sellocal) ? "selected" : "" }}>{{ $branch->LegalName }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </div>
</form>
<div class="row">
    <div class="col-lg-5 col-md-6 mb-4">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between">
            <div class="card-title mb-0">
              <h5 class="mb-1">My Actionable Items</h5>
              <p class="card-subtitle">Total (<b>{{$stusMy['all']}}</b>)</p>
            </div>
          </div>
          <div class="card-body">
            <ul class="p-0 m-0">
              <li class="mb-4 d-flex align-items-center justify-content-between cursor-pointer clickableDashUser" data-status="draft" data-user="{{session()->get('contractSessionUser')}}">
                <div class="d-flex w-50 align-items-center me-4">
                  <div class="badge bg-label-primary rounded p-1_5"><i class="ti ti-file"></i></div>
                  <div class="ms-4">
                    <h6 class="mb-0">Draft</h6>
                    <small class="text-body"></small>
                  </div>
                </div>
                <div class="d-flex align-items-end">
                  <span class="badge bg-label-primary">{{$stusMy['draft']}}</span>
                </div>
              </li>
              <li class="mb-4 d-flex align-items-center justify-content-between cursor-pointer clickableDashUser" data-status="review" data-user="{{session()->get('contractSessionUser')}}">
                <div class="d-flex w-50 align-items-center me-4">
                  <div class="badge bg-label-primary rounded p-1_5"><i class="ti ti-pencil"></i></div>
                  <div class="ms-4">
                    <h6 class="mb-0">Review</h6>
                    <small class="text-body"></small>
                  </div>
                </div>
                <div class="d-flex align-items-end">

                  <span class="badge bg-label-primary">{{$stusMy['review']}}</span>
                </div>
              </li>
              <li class="mb-4 d-flex align-items-center justify-content-between cursor-pointer clickableDashUser" data-status="negotiation" data-user="{{session()->get('contractSessionUser')}}">
                <div class="d-flex w-50 align-items-center me-4">
                  <div class="badge bg-label-warning rounded p-1_5"><i class="ti ti-file-alert"></i></div>
                  <div class="ms-4">
                    <h6 class="mb-0">Negotiation</h6>
                    <small class="text-body"></small>
                  </div>
                </div>
                <div class="d-flex align-items-end">
                  <span class="badge bg-label-warning">{{$stusMy['negotiation']}}</span>
                </div>
              </li>
              <li class="mb-4 d-flex align-items-center justify-content-between cursor-pointer clickableDashUser" data-status="approval" data-user="{{session()->get('contractSessionUser')}}">
                <div class="d-flex w-50 align-items-center me-4">
                  <div class="badge bg-label-info rounded p-1_5"><i class="ti ti-rotate-clockwise-2"></i></div>
                  <div class="ms-4">
                    <h6 class="mb-0">Approvals</h6>
                    <small class="text-body"></small>
                  </div>
                </div>
                <div class="d-flex align-items-end">
                  <span class="badge bg-label-info">{{$stusMy['approval']}}</span>
                </div>
              </li>
              <li class="mb-4 d-flex align-items-center justify-content-between cursor-pointer clickableDashUser" data-status="signing" data-user="{{session()->get('contractSessionUser')}}">
                <div class="d-flex w-50 align-items-center me-4">
                  <div class="badge bg-label-warning rounded p-1_5"><i class="ti ti-file-power"></i></div>
                  <div class="ms-4">
                    <h6 class="mb-0">Signing</h6>
                    <small class="text-body"></small>
                  </div>
                </div>
                <div class="d-flex align-items-end">
                  <span class="badge bg-label-warning">{{$stusMy['signing']}}</span>
                </div>
              </li>
            </ul>
          </div>
        </div>
    </div>
    <div class="col-lg-7 col-md-6 mb-4">
        <div class="row">
            <div class="col-lg-6 col-md-6 col-12 d-flex flex-column">
                <div class="card clickableDashItems cursor-pointer mb-4" data-status="executed_expired">
                  <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="card-title mb-0">
                      <h5 class="mb-1 me-2">{{ $counts["executed_expired"] ?? 0 }}</h5>
                      <small class="mb-0">Expired</small>
                    </div>
                    <div class="card-icon">
                      <span class="badge bg-label-danger rounded p-2">
                        <i class="ti ti-file-time ti-xl"></i>
                      </span>
                    </div>                  
                  </div>
                </div>              
                <div class="card clickableDashItems cursor-pointer mb-4" data-status="all">
                  <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="card-icon">
                      <span class="badge bg-label-primary rounded p-2">
                        <i class="ti ti-file-diff ti-xl"></i>
                      </span>
                    </div>                  
                    <div class="card-title mb-0">
                      <h5 class="mb-1 me-2">{{ $counts["all"] ?? 0 }}</h5>
                      <small class="mb-0">All Contracts</small>
                    </div>
                  </div>
                </div>            
              </div>
            <div class="col-lg-6 col-md-6 col-12 d-flex flex-column">
                <div class="card clickableDashItems cursor-pointer mb-4" data-status="executed_renewed">
                  <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="card-title mb-0">
                      <h5 class="mb-1 me-2">0</h5>
                      <small class="mb-0">Approaching Renewal</small>
                    </div>
                    <div class="card-icon">
                      <span class="badge bg-label-warning rounded p-2">
                        <i class="ti ti-file-report ti-xl">{{ $counts["executed_renewed"] }}</i>
                      </span>
                    </div>
                  </div>
                </div>            
                <div class="card clickableDashItems cursor-pointer mb-4" data-status="executed_expired">
                  <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="card-icon">
                      <span class="badge bg-label-danger rounded p-2">
                        <i class="ti ti-file-time ti-xl"></i>
                      </span>
                    </div>                  
                    <div class="card-title mb-0">
                      <h5 class="mb-1 me-2">{{ $counts["executed_pending"] ?? 0 }}</h5>
                      <small class="mb-0">Pending Activation</small>
                    </div>
                  </div>
                </div>              
              </div>
            <div class="col-lg-12 col-md-12 col-12">
                <div class="card h-100">
              <div class="card-header d-flex justify-content-between">
                <h5 class="card-title mb-0">My Tasks</h5>
                <small class="text-muted"></small>
              </div>
              <div class="card-body pt-2">
                <div class="row gy-3">
                  <div class="col-md-3 col-6 clickableDashTasks cursor-pointer" data-status="pending" data-user="{{Helper::userInfo()->id}}">
                    <div class="d-flex align-items-center">
                      <div class="badge rounded-pill bg-label-warning me-3 p-2"><i class="ti ti-file-invoice ti-xl"></i></div>
                      <div class="card-info">
                        <h5 class="mb-0">{{ $stusMyTask["pending"] }}</h5>
                        <small>Pending</small>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3 col-6 clickableDashTasks cursor-pointer" data-status="inprogress" data-user="{{Helper::userInfo()->id}}">
                    <div class="d-flex align-items-center">
                      <div class="badge rounded-pill bg-label-info me-3 p-2"><i class="ti ti-file-text ti-xl"></i></div>
                      <div class="card-info">
                        <h5 class="mb-0">{{ $stusMyTask["inprogress"] }}</h5>
                        <small>In Progress</small>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3 col-6 clickableDashTasks cursor-pointer" data-status="completed" data-user="{{Helper::userInfo()->id}}">
                    <div class="d-flex align-items-center">
                      <div class="badge rounded-pill bg-label-success me-3 p-2"><i class="ti ti-file-check ti-xl"></i></div>
                      <div class="card-info">
                        <h5 class="mb-0">{{ $stusMyTask["completed"] }}</h5>
                        <small>Completed</small>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3 col-6 clickableDashTasks cursor-pointer" data-status="all">
                    <div class="d-flex align-items-center">
                      <div class="badge rounded-pill bg-label-primary me-3 p-2"><i class="ti ti-select-all ti-xl"></i></div>
                      <div class="card-info">
                        <h5 class="mb-0">{{ $stusMyTask["all"] }}</h5>
                        <small>All Tasks</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            </div>              
        </div>
    </div>
</div>
<div class="row mt-4">
  <div class="col-lg-5 col-md-12 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <div class="card-title mb-0">
          <h5 class="mb-1">Actionable Items</h5>
          <p class="card-subtitle">In Process</p>
        </div>
        <div class="dropdown d-none">
          <button class="btn btn-text-secondary rounded-pill text-muted border-0 p-2 me-n1 waves-effect waves-light" type="button" id="supportTrackerMenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="ti ti-dots-vertical ti-md text-muted"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="supportTrackerMenu">
            <a class="dropdown-item waves-effect" href="javascript:void(0);">View More</a>
            <a class="dropdown-item waves-effect" href="javascript:void(0);">Delete</a>
          </div>
        </div>
      </div>
      <div class="card-body row">
        <div class="col-12 col-sm-4 col-md-12 col-lg-4">
            <div class="mb-2">
                <h2 class="mb-0">{{ $counts["review"] + $counts["approval"] + $counts["signing"] }}</h2>
                <p class="mb-0">Total</p>
            </div>                
          <ul class="p-0 m-0">
            <li class="d-flex gap-4 align-items-center mb-lg-3 pb-1 clickableDashItems cursor-pointer" data-status="review">
              <div class="badge rounded bg-label-primary p-1_5"><i class="ti ti-pencil ti-xl"></i></div>
              <div>
                <h6 class="mb-0 text-nowrap">Review</h6>
                <small class="text-muted">{{ $counts["review"] }}</small>
              </div>
            </li>
            <li class="d-flex gap-4 align-items-center mb-lg-3 pb-1 clickableDashItems cursor-pointer" data-status="approval">
              <div class="badge rounded bg-label-info p-1_5"><i class="ti ti-rotate-clockwise-2 ti-xl"></i></div>
              <div>
                <h6 class="mb-0 text-nowrap">Approval</h6>
                <small class="text-muted">{{ $counts["approval"] }}</small>
              </div>
            </li>
            <li class="d-flex gap-4 align-items-center pb-1 clickableDashItems cursor-pointer" data-status="signing">
              <div class="badge rounded bg-label-warning p-1_5"><i class="ti ti-file-power ti-xl"></i></div>
              <div>
                <h6 class="mb-0 text-nowrap">Sigining</h6>
                <small class="text-muted">{{ $counts["signing"] }}</small>
              </div>
            </li>
          </ul>
        </div>
        <div class="col-12 col-sm-8 col-md-12 col-lg-8 d-flex align-items-center" style="position: relative;">
          <div id="actionableChart" class=""></div>
        </div>
      </div>
    </div>
  </div>    
  <div class="col-lg-7 col-md-12 mb-4">
    <div class="row h-100">
        <div class="col-lg-12 col-md-12 col-12 mb-4">
            <div class="card">
              <div class="card-body d-flex justify-content-between row">
                <div class="d-flex flex-column col-lg-6 col-md-6 col-12">
                  <div class="card-title mb-1">
                    <h5 class="mb-0 text-nowrap">Contract</h5>
                    <small>Milestones</small>
                  </div>
                  <div class="chart-statistics d-none">
                    <h3 class="card-title mb-0">{{ $counts["draft"] + $counts["executed"] + $counts["negotiation"] }}</h3>
                  </div>
                    <div class="">
                      <table class="table card-table">
                        <tbody class="table-border-bottom-0">
                          <tr class="clickableDashItems cursor-pointer" data-status="draft">
                            <td class="w-50 ps-0">
                              <div class="d-flex justify-content-start align-items-center">
                                <div class="me-2">
                                  <i class="ti ti-file ti-md text-success"></i>
                                </div>
                                <small class="mb-0 fw-normal">Draft</small>
                              </div>
                            </td>
                            <td class="text-end pe-0 text-nowrap">
                              <h6 class="mb-0">{{ $counts["draft"] }}</h6>
                            </td>
                          </tr>
                          <tr class="clickableDashItems cursor-pointer" data-status="negotiation">
                            <td class="w-50 ps-0">
                              <div class="d-flex justify-content-start align-items-center">
                                <div class="me-2">
                                  <i class="ti ti-file-alert ti-md text-warning"></i>
                                </div>
                                <small class="mb-0 fw-normal">Negotiation</small>
                              </div>
                            </td>
                            <td class="text-end pe-0 text-nowrap">
                              <h6 class="mb-0">{{ $counts["negotiation"] }}</h6>
                            </td>
                          </tr>
                          <tr class="clickableDashItems cursor-pointer" data-status="finalization">
                            <td class="w-50 ps-0">
                              <div class="d-flex justify-content-start align-items-center">
                                <div class="me-2">
                                  <i class="ti ti-file-check ti-md text-warning"></i>
                                </div>
                                <small class="mb-0 fw-normal">Finalization</small>
                              </div>
                            </td>
                            <td class="text-end pe-0 text-nowrap">
                              <h6 class="mb-0">{{ $counts["finalization"] ?? 0 }}</h6>
                            </td>
                          </tr>
                          <tr class="clickableDashItems cursor-pointer" data-status="executed">
                            <td class="w-50 ps-0">
                              <div class="d-flex justify-content-start align-items-center">
                                <div class="me-2">
                                  <i class="ti ti-file-invoice ti-md text-info"></i>
                                </div>
                                <small class="mb-0 fw-normal">Executed</small>
                              </div>
                            </td>
                            <td class="text-end pe-0 text-nowrap">
                              <h6 class="mb-0">{{ $counts["executed"] }}</h6>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>                  
                </div>
                <div id="milestonesChart" class="col-lg-6 col-md-6 col-12"></div>
              </div>
            </div>
          </div>         
        <div class="col-lg-12 col-md-12 col-12">
            <div class="card">
              <div class="card-header d-flex justify-content-between">
                <h5 class="card-title mb-0">Contract</h5>
                <small class="text-muted">Life Cycle Status</small>
              </div>
              <div class="card-body pt-2">
                <div class="row gy-3">
                  <div class="col-md-3 col-6 clickableDashItems cursor-pointer" data-status="executed_active">
                    <div class="d-flex align-items-center">
                      <div class="badge rounded-pill bg-label-success me-3 p-2"><i class="ti ti-file-like ti-xl"></i></div>
                      <div class="card-info">
                        <h5 class="mb-0">{{ $counts["executed_active"] }}</h5>
                        <small>Active</small>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3 col-6 clickableDashItems cursor-pointer" data-status="executed_completed">
                    <div class="d-flex align-items-center">
                      <div class="badge rounded-pill bg-label-info me-3 p-2"><i class="ti ti-file-text ti-xl"></i></div>
                      <div class="card-info">
                        <h5 class="mb-0">{{ $counts["executed_completed"] }}</h5>
                        <small>Completed</small>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3 col-6 clickableDashItems cursor-pointer" data-status="executed_terminated">
                    <div class="d-flex align-items-center">
                      <div class="badge rounded-pill bg-label-danger me-3 p-2"><i class="ti ti-file-x ti-xl"></i></div>
                      <div class="card-info">
                        <h5 class="mb-0">{{ $counts["executed_terminated"] }}</h5>
                        <small>Terminated</small>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3 col-6 clickableDashItems cursor-pointer" data-status="executed_renewed">
                    <div class="d-flex align-items-center">
                      <div class="badge rounded-pill bg-label-success me-3 p-2"><i class="ti ti-files ti-xl"></i></div>
                      <div class="card-info">
                        <h5 class="mb-0">{{ $counts["executed_renewed"] }}</h5>
                        <small>Renewed</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
        </div>          
    </div>
  </div>
</div>
@endsection
