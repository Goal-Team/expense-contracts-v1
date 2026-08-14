@extends('layouts/layoutMaster')

@section('title', 'Reports')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
  'resources/assets/vendor/libs/jstree/jstree.scss',
  'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
  'resources/assets/vendor/libs/jstree/jstree.js',
  'resources/assets/vendor/libs/select2/select2.js'
])
@endsection

@section('page-style')
<link rel="stylesheet" href="{{url('/')}}/Modules/Contract/resources/assets/sass/reports.css" />
@endsection

@section('content')
<div id="loadReport"></div>
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Contracts / </span>Reports
  @include('contract::reports.report-menu', ['pageTitle' => 'By Value'])
</h4>

<style>
#loadReport{
    width:100%;
    height:100%;
    position:fixed;
    z-index:9999;
    background:url("{{url('assets/logo/OnTrackLogo.png')}}") no-repeat center center rgb(255 255 255 / 80%);
    background-size: 150px auto;
    visibility: hidden;
}
.dotted-af-text:after {
  border-bottom: 1px dotted black;
  content: '';
  flex: 1;
}   
.dotted-border{
    border-bottom: 1px dotted black;
}
.jstree-anchor.jstree-clicked{
    background: none;
    color: #000;
    border: none;
    box-shadow: none;
}
.status-completed{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    /*background-color: #007bff;*/
    background-color: #a7a9ad;
    
}
.status-active{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #28c76f;
    
}
.status-expired{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #ff264a;
    
}
.status-terminate{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #08cfe6;
    
}
.status-renewed{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #7367f0;
    
}
.status-initialdraft{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #a8aaae;
    
}
.status-negotiation{
    display: inline-block;
     padding: .25em .4em; 
    font-size: 14px;
    font-weight: bolder;
    line-height: 1.3;
     text-align: center; 
     white-space: nowrap; 
     vertical-align: baseline; 
    border-radius: .25rem;
    color: #fff;
    background-color: #28c76f;
    
}
.substatusText {
  text-transform: capitalize;
}
</style>
<div class="row mb-4 g-4">
  <div class="col-md-12">
    <div class="card h-100">
      <div class="card-body row SectionToPrint">   
        <div class="col-md-3 border-shift border-end">
          <div class="d-flex flex-column align-items-center h-100">
              <h2 class="text-primary d-flex align-items-center gap-1 mb-2" id="totConValByDep">{{ currency_formatter(env('default_currency'),$allContracts) }}</h2>
              <p class="h6 mb-1"><span id="totConTxtByDep" class="fw-bold">Total</span> Value</p>
              <p class="pe-2 mb-2 text-center">By <br/>
				<!--<select id="comparisionSelect" class="form-select">-->
				<!--	<option value="0">OverAll</option>-->
				<!--</select>-->
			  </p>
              <hr class="d-sm-none">
              <div id="typesChart"></div>
              <div id="donutChartContractTree"></div>
          </div>
        </div>
        <div class="col-9">
          <h5 class="card-title text-center">
            <div class="btn-group d-none" role="group">
              <span role="button" class="btn btn-sm btn-warning active-toggle waves-effect count-toggle" data-toggle-tb="all">All</span>
              <span role="button" class="btn btn-sm btn-outline-secondary waves-effect count-toggle" data-toggle-tb="count">Only Count</span>
              <span role="button" class="btn btn-sm btn-outline-secondary waves-effect count-toggle" data-toggle-tb="val">Only Value</span>
            </div>            
            <div class="btn-group d-none" role="group">
              <span role="button" class="btn btn-sm btn-outline-secondary waves-effect available-toggle" data-toggle-tb="all">All</span>
              <span role="button" class="btn btn-sm btn-warning active-toggle waves-effect available-toggle" data-toggle-tb="yes">Available</span>
            </div>

            <div class="form-check form-switch show-error-switch float-end col">
              <input class="form-check-input" type="checkbox" role="switch" id="showAllCounts">
              <label class="ms-2 fs-6 fw-bold" for="showAllCounts">Show All</label>
            </div>            
            <br/>
          </h5>
 
          <!--<div id="contractValueTree" class="col-12">-->
          <!--  <ul>-->

          <!--  </ul>-->
          <!--</div>-->
          <div id="contractDetailTree" class="col-12">
          </div>
          <h6 class="text-danger blink mt-4 mb-1">Notes</h6>
            <div class="col">
                <span class='badge bg-danger'>Total</span> <span class='badge bg-info'>With Value</span> <span class='badge bg-warning'>No Value</span>                
            </div>          
          <div class="col-12 card d-none">
            <div class="card-datatable table-responsive" id="locationListType">
              <table class="table" id="locationValueTable">              
                <thead>
                  <tr>
                    <!-- <th>S.No</th> -->
                    <th>Location</th>
                    <th class="text-center">Value</th>
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
<!-- review List Table -->
<div class="card">
  <div class="card-datatable table-responsive">
    <table class="table border-top" id="departmentDetailReport">
         <thead>
            <tr>
               <th>S.No.</th>
               <th>Contract Name</th>
               <th>Location</th>
               <th>Contract Type</th>
               <th>Effective Date</th>
               <th>End Date</th>
               <th>Contract value</th>
               <th>Status</th>
            </tr>
         </thead>
    </table>
  </div>
</div>  

@endsection
