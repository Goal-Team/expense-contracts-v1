@extends('layouts/layoutMaster')

@section('title', 'Custom Variable For Templates')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/tagify/tagify.scss'])
@endsection

@section('vendor-script')
    <link href="{{ url('/') }}/assets/css/custom.css" rel="stylesheet" />
    <link href="{{ url('/') }}/Modules/Contractsetup/resources/assets/css/customfields.css" rel="stylesheet" />
@endsection

@section('page-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/js/forms-selects.js'])
    <script type="module" src="{{ url('/') }}/Modules/Contractsetup/resources/assets/js/jquery-ui.js"></script>
    <script type="module" src="{{ url('/') }}/assets/js/jquery.validate.min.js"></script>
    <script type="module" src="{{ url('/') }}/Modules/Contractsetup/resources/assets/js/jquery.serialize-object.js">
    </script>
    <script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/customvariables.js"></script>
@endsection

@section('content')

<style>

    .clauses-section .bg-clause-title{
        background-color: #be843a;
        text-transform: capitalize;
        border-color: #be843a;
    }
    
    .clauses-section {
        scrollbar-width: none;
        scrollbar-color: #c5c5c5 #f6f6f6;
    }

    /* Works on Chrome, Edge, and Safari */
    .clauses-section::-webkit-scrollbar {
        display: none;
    }

    .clauses-section::-webkit-scrollbar-track {
        background: #f6f6f6;
    }

    .clauses-section::-webkit-scrollbar-thumb {
        background-color: #c5c5c5;
        border-radius: 20px;
        border: 3px solid #f6f6f6;
    }    
</style>

<h4 class="py-3 mb-0">
    <span class="text-muted fw-light">Custom Variables /</span><span class="fw-medium"> Add/Edit</span>
    </h4>
<div class="row">

        <div class="card mb-4 pt-4">

            <div class="row">
                        
                <div class="col-sm-4 col-12 mb-3">
                    <div>
                        <h4>
                            All Variables
                            <a href="javascript:void(0)" role="button" class="btn rounded-pill btn-icon btn-label-primary waves-effect" data-bs-toggle="modal" data-bs-target="#varAdd">
                               <span class="icon-base ti ti-plus"></span>
                            </a>                         
                        </h4>                            
                        <form id="createCustom">
                            @if (!empty($customVars))
                                <div class="col-12 mb-3">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                           <div class="card-datatable text-responsive">
                                              <table class="table">
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Var</th>
                                                    <th>DB Table</th>
                                                    <th>DB Field</th>
                                                    <th>Action</th>
                                                </tr>       
                                                @php $inr = 0; @endphp
                                                @foreach ($customVars as $cuVar)
                                                @php $inr++; @endphp
                                                <tr>
                                                    <td>{{$inr}}</td>
                                                    <td>{{$cuVar->var_disp_var}}</td>
                                                    <td>{{$cuVar->var_table}}</td>
                                                    <td>{{$cuVar->var_field}}</td>
                                                    <td>
                                                        <a href="javascript:void(0)" role="button" class="btn rounded-pill btn-icon btn-label-warning waves-effect editVars" data-var-id="{{$cuVar->var_id}}">
                                                           <span class="icon-base ti ti-pencil"></span>
                                                        </a>                                                        
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </table>
                                            </div>
                                        </div>
                                        <div>
                                            
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
  
                <!-- Success and Error Messages -->
                <div id="state" class="alert alert-success" style="display:none">Created successfully</div>
                <div id="dstate" class="alert alert-danger" style="display:none">Deleted successfully</div>
                <div id="ustate" class="alert alert-warning" style="display:none">Updated successfully</div>
            </div>
            
            <div class="modal-onboarding modal fade animate__animated" id="varAdd" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content text-center">
                        <div class="modal-header border-0">
                            <h6 class="modal-title mb-0">Add New Variable</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="customVarAddForm" class="mt-0 col-6">
                                <label>Custom DB Variable</label><br/>
                                <div class="input-group">
                                    <select class="form-select" id="varTables" name="varTables">
                                        <option value="">-- Select Table --</option>
                                        <option value="contracts">Contracts</option>
                                        <option value="contractparty">Contracts Party</option>
                                        <option value="partycustomfields">Parties Custom Fields</option>
                                        <option value="customfields">Contracts Custom Fields</option>
                                    </select>
                                    <input type="text" name="varTitle" id="varTitle" class="form-control"/>
                                </div>
                                <label>Custom Replace Variable</label><br/>
                                <input type="text" name="VarText" id="VarText" class="form-control"/>
                                <span class="text-danger" id="titleAddAlert" style="display: none;"></span>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary saveVar">Save</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-onboarding modal fade animate__animated" id="varEdit" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content text-center">
                        <div class="modal-header border-0">
                            <h6 class="modal-title mb-0">Edit Variable</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="customVarEditForm" class="mt-0 col-6">
                                <label>Custom DB Variable</label><br/>
                                <div class="input-group">
                                    <select class="form-select" id="varEditTables" name="varTables">
                                        <option value="">-- Select Table --</option>
                                        <option value="contracts">Contracts</option>
                                        <option value="contractparty">Contracts Party</option>
                                        <option value="partycustomfields">Parties Custom Fields</option>
                                        <option value="customfields">Contracts Custom Fields</option>
                                    </select>
                                    <input type="text" name="varTitle" id="varEditTitle" class="form-control"/>
                                </div>                                
                                <label>Custom Replace Variable</label><br/>
                                <input type="text" name="VarText" id="VarEditText" class="form-control"/>
                                <input type="hidden" name="varId" id="varId" class="form-control"/>
                                <span class="text-danger" id="titleEditAlert" style="display: none;"></span>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary updateVar">Save</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
