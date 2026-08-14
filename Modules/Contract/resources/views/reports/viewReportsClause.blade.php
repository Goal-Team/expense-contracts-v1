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
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Contracts / </span>Reports
  @include('contract::reports.report-menu', ['pageTitle' => 'By Clauses', 'report'=>false])
</h4>

<div class="row mb-4 g-4">
  <div class="col-md-12">
    <div class="card h-100">
      <div class="card-body row">   

        <div class="col-12 row">
          <h5 class="card-title text-center">Contract Wise Report Against Clauses</h5>
          <div class="col-12 card">
            <div class="card-datatable table-responsive" id="clausesListContracts">
              <table class="table" id="clausesListContractsTable">              
                <thead>
                  <tr>
                      <th>Contract</th>
                    @php
                        $clauseArr = [];
                        foreach($contractClauses as $conClause){
                            $clauseArr[] = $conClause->category_id;
                            echo "<th class=\"text-center\">".$conClause->category_name."</th>";
                        }
                    @endphp
                  </tr>
                </thead>
                <tbody>
                    @php
                        foreach($contDatas as $con_d){
                            echo "<tr>";
                            echo "<td><a href='".url('contracts/'.$con_d->id)."' target='_blank'>".$con_d->contract_unique_id."</a></td>";
                            foreach($clauseArr as $cl_d){
                                if(!in_array($cl_d, $con_d->linkedClauses)){
                                    echo '<td align="center"><i class="ti ti-circle-x text-danger ti-md"></i></td>';
                                }else{
                                    echo '<td align="center"><i class="ti ti-circle-check text-success ti-md"></i></td>';
                                }
                            }
                            echo "<tr/>";
                        }
                    @endphp 
                    
                </tbody>
              </table>
            </div>
          </div>                  
        </div>
      </div>
      </div>
    </div>
  </div>
</div>

@endsection
