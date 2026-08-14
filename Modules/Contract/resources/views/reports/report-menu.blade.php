<style>
    /* Change font size of Select2 placeholder */
    .select2-search__field {
        font-size: 15px !important; /* Change this to your desired size */
        line-height: 30px;
    }
    
    /* Allow the select2 container to grow as items are selected */
    .select2-container--default .select2-selection--multiple {
      min-height: 36px !important;
      transition: height 0.2s ease;
    }
    
    .light-style .select2-container--default .select2-selection--multiple {
        
    }  
    
    .light-style .select2-selection--multiple .select2-selection__rendered{
        padding: 0 5px;
    }

    .custom-chip-container {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      padding: 10px 0;
    }
    
    .custom-chip {
      display: inline-flex;
      align-items: center;
      border-radius: 16px;
      padding: 6px 12px;
      font-size: 12px;
    }
    
    .custom-chip .custom-chip-close {
      margin-left: 8px;
      cursor: pointer;
      font-size: 16px;
      line-height: 1;
      color: #fff;
    }
    
    .custom-chip .custom-chip-close:hover {
      color: #000;
    }
    
</style>
{{$pageTitle}} 
@php
$locationFiltered = json_decode($_COOKIE['filterByLocationReport'] ?? '[]');
@endphp
    @if(empty($locationFiltered))
        <div class="custom-chip text-white bg-primary">All Locations</div>
    @endif
    <span class="badge rounded-pill bg-warning bg-glow mb-0 mt-0 ms-2 cursor-pointer" data-bs-toggle="modal" data-bs-target="#contractLocationSelector">
    Filter Location
    </span>
    <div class="custom-chip-container">
        @if(!empty($locationFiltered))
            @foreach ($branchs as $branch)
                @if(in_array($branch->id, $locationFiltered))
                    <div class="custom-chip text-white bg-primary">
                        {{ $branch->LegalName}}
                        <span class="custom-chip-close" data-bs-toggle="modal" data-bs-target="#contractLocationSelector">&times;</span>
                    </div>
                @endif
            @endforeach
        @endif
    </div>       
    <div class="row">
        <div class="col-12">
            <div class="btn-toolbar demo-inline-spacing" role="toolbar" aria-label="Toolbar with button groups">
                <div class="btn-group" role="group" aria-label="First group">
                  <a role="button" href="{{url(('contracts/reports'))}}" class="btn btn-{{ request()->is('contracts/reports') ? 'primary' : 'outline-secondary' }} waves-effect">Non Executed</a>
                  <a role="button" href="{{url(('contracts/reports/executed'))}}" class="btn btn-{{ request()->is('contracts/reports/executed') ? 'primary' : 'outline-secondary' }} waves-effect">Executed</a>
                </div>
                <div class="btn-group" role="group" aria-label="First group">
                  <a role="button" href="{{url(('contracts/reports-expired'))}}" class="btn btn-{{ request()->is('contracts/reports-expired') ? 'primary' : 'outline-secondary' }} waves-effect">Aging</a>
                  <a role="button" href="{{url(('contracts/reports-exceptions'))}}" class="btn btn-{{ request()->is('contracts/reports-exceptions') ? 'primary' : 'outline-secondary' }} waves-effect">Exception</a>
                </div>
                <div class="btn-group" role="group" aria-label="Second group">
                  <a role="button" href="{{url(('contracts/reports-contract-value'))}}" class="btn btn-{{ request()->is('contracts/reports-contract-value') ? 'primary' : 'outline-secondary' }} waves-effect">Detail Report</a>
                  <a role="button" href="{{url(('contracts/reports-contract-tags'))}}" class="btn btn-{{ request()->is('contracts/reports-contract-tags') ? 'primary' : 'outline-secondary' }} waves-effect">Tags</a>
                </div>
                <div class="btn-group" role="group" aria-label="Third group">
                  <a role="button" href="{{url(('contracts/reports-contract-depts'))}}" class="btn btn-{{ request()->is('contracts/reports-contract-depts') ? 'primary' : 'outline-secondary' }} waves-effect">Departments</a>
                  <a role="button" href="{{url(('contracts/reports-contract-clauses'))}}" class="btn btn-{{ request()->is('contracts/reports-contract-clauses') ? 'primary' : 'outline-secondary' }} waves-effect">Clauses</a>
                </div>
                <div class="flex-grow-1"></div>
                <div class="btn-group">
                    <form id="createExport" action="{{url('export-report')}}" method="POST">
                        @csrf
                        <input type="hidden" name="exportUrl" value=""/>
                        <input type="hidden" name="exportParams" value=""/>
                        <input type="hidden" name="imgs" value=""/>
                      <button type="button" class="btn rounded-pill btn-instagram waves-effect waves-light float-end printableButton" data-print-section="SectionToPrint">
                        <i class="tf-icons ti ti-chart-infographic ti-xs me-2"></i> Export Pdf
                      </button> 
                    </form>
                </div>
            </div>
        </div>
    
    </div>

    <div class="modal fade" id="contractLocationSelector">
        <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Choose Location To Filter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col">
            <div class="d-flex align-items-center gap-2 mt-2">
                <select class="form-select" id="locationFilterSel" name="locationFilter[]" multiple style="max-width:200px">
                    @foreach ($branchs as $branch)
                        <option value="{{ $branch->id }}" {{ ($_COOKIE['filterByLocationReport'] ?? false) ? (in_array($branch->id, json_decode($_COOKIE['filterByLocationReport'] ?? '[]')) ? 'selected' : '') : ''}}>{{ $branch->LegalName}}</option>
                    @endforeach
                </select>
                <div class="flex-grow-1"></div>
                <button type="button" id="locationFilter" class="btn btn-md btn-primary waves-effect waves-light ms-2">
                   filter 
                </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
    </div>
@section('in-page-script')
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/html2canvas.min.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/reports.js"></script>
@endsection