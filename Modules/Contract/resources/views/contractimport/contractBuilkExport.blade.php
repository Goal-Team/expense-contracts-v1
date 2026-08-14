@extends('layouts.layoutMaster')

@section('title', ' Contracts Export')
<!-- Vendor Styles -->
@section('vendor-style')
@vite(['resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/tagify/tagify.scss'])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite(['resources/assets/vendor/libs/quill/katex.js', 'resources/assets/vendor/libs/quill/quill.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/dropzone/dropzone.js', 'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js'])

<link href="{{ url('/') }}/assets/css/custom.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')
@vite(['resources/assets/js/forms-file-upload.js'])
@vite(['resources/assets/js/form-layouts.js'])

<script type="module" src="{{ url('/') }}/assets/js/jquery.validate.min.js"></script>
<script type="module" src="{{ url('/') }}/Modules/Contract/resources/assets/js/contract.js"></script>

<script type="module">
    $(document).ready(function() {
        if ($('.emptyattachemnt').length > 0) {
            $('#uplod').hide();
        };
        
        let filterStatus = _getCookie_('filterStatus');
        if(filterStatus){
            $('#filterStatus').val(filterStatus);
        }

        let filterSet = _getCookie_('filterSet');
        if(filterSet){
            $('#filterSearch').val(filterSet);
        }

        $('#btnSelectAllColumns').on('click', function() {
            $('#export_columns option').prop('selected', true);
            $('#export_columns').trigger('change');
        });

        $('#btnRemoveAllColumns').on('click', function() {
            $('#export_columns option').prop('selected', false);
            $('#export_columns').trigger('change');
        });
    });
    
  function _getCookie_(name) {
    const cookies = document.cookie.split('; ')

    for (let i = 0; i < cookies.length; i++) {
      const [cookieName, cookieValue] = cookies[i].split('=')
      if (decodeURIComponent(cookieName) === name) {
        return decodeURIComponent(cookieValue)
      }
    }

    return null
  }    
</script>
{{-- <script type="module" src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/min/dropzone.min.js"></script> --}}
@endsection
@section('content')
<style>
    .emptyattachemnt {
        background: red !important;
        color: #fff !important;
    }

    .missing-data {
        background: red !important;
        color: #fff !important;
    }

    .files input {
        outline: 2px dashed #dbdade;
        outline-offset: -10px;
        -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
        transition: outline-offset .15s ease-in-out, background-color .15s linear;
        padding: 120px 0px 85px 35%;
        text-align: center !important;
        margin: 0;
        width: 100% !important;
    }

    .files input:focus {
        outline: 2px dashed #dbdade;
        outline-offset: -10px;
        -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
        transition: outline-offset .15s ease-in-out, background-color .15s linear;
    }

    .files {
        position: relative
    }

    .files:after {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-upload' width='24' height='24' viewBox='0 0 24 24' stroke-width='2' stroke='%235d596c' fill='none' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath stroke='none' d='M0 0h24v24H0z' fill='none'/%3E%3Cpath d='M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2' /%3E%3Cpolyline points='7 9 12 4 17 9' /%3E%3Cline x1='12' y1='4' x2='12' y2='16' /%3E%3C/svg%3E") !important;
        background: #4b465c14;
        content: "";
        border-radius: 8px;
        position: absolute;
        top: 3rem;
        left: calc(50% - 23px);
        display: inline-block;
        height: 48px;
        width: 48px;
        background-repeat: no-repeat !important;
        background-position: center !important;
    }

    .color input {
        background: #fff;
    }

    .files:before {
        position: absolute;
        bottom: 10px;
        left: 0;
        pointer-events: none;
        width: 100%;
        right: 0;
        height: 57px;
        content: "Drop files here or click to upload";
        display: block;
        margin: 0 auto;
        font-weight: 600;
        text-transform: capitalize;
        text-align: center;
    }
</style>

<div class="container shadow min-vh-100 py-2">
    <div class="container network_wrapper col-sm p-2">


        @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif
        <div class="card">

            <div class="card-body">
               <div>
                     <a href="{{url('/contracts/list')}}" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary float-end">Back</button></a>
               </div>                
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="upload-data" role="tabpanel"
                        aria-labelledby="upload-data-tab">
 
                        <div class="row my-4">
                            <div class="col">
                                <h5 class="card-title">Download data:</h5>
                                <p>Using older versions of Office/Excel? Download the template here.</p>
                                <form id="createcontract" action="builk-export-download" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="status" id="filterStatus" />
                                    <input type="hidden" name="filterSearch" id="filterSearch" />
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="contracttype">Contract Type <span
                                                class="text-danger">*</span></label>
                                        <div class="select2-success">
                                            <select id="contracttype" name="ContractType[]" class="select2 form-select"
                                                multiple>
                                                <option value="0">All</option>
                                                @foreach ($contractTypes as $contractType)
                                                <option value="{{ $contractType->contract_type_id }}">
                                                    {{ $contractType->contract_type }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="export_columns">Export Columns <span class="text-danger">*</span></label>
                                        <div class="mb-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAllColumns">Select All</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRemoveAllColumns">Remove All</button>
                                        </div>
                                        <div class="select2-success">
                                            <select id="export_columns" name="export_columns[]" class="select2 form-select"
                                                multiple>
                                                @foreach ($exportColumns as $columnKey => $columnLabel)
                                                <option value="{{ $columnKey }}" selected>
                                                    {{ $columnLabel }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <small class="text-muted">Select only the columns you want in the Excel download.</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" value="1" id="all_in_one_page" name="all_in_one_page">
                                            <label class="form-check-label" for="all_in_one_page">
                                                All in one page (single sheet)
                                            </label>
                                        </div>
                                    </div>
                                    <button type="submit"
                                        class="btn btn-primary me-sm-3 me-1 waves-effect waves-light">Download Data
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection