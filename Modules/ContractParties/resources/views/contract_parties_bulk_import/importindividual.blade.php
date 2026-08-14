@extends('layouts.layoutMaster')

@section('title', ' Contracts Import')
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
        setTimeout(() => {
        if ($('.emptyattachemnt').length > 0) {
            $('#createcontractstore').hide();
        };    
        }, 500);
        
    });
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
        @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
        @endif        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Bulk Upload Contract Parties <span class="badge bg-warning">Individual</span></h5>
                <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ (session('data')) ? '' : 'active' }}" id="upload-data-tab" data-bs-toggle="tab"
                            data-bs-target="#upload-data" type="button" role="tab" aria-controls="upload-data"
                            aria-selected="true">1. Download Data</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ (session('data')) ? 'active' : '' }}" id="verify-info-tab" data-bs-toggle="tab" data-bs-target="#verify-info"
                            type="button" role="tab" aria-controls="verify-info" aria-selected="false">2. Upload &
                            Verify Info</button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade {{ (session('data')) ? '' : 'show active' }}" id="upload-data" role="tabpanel"
                        aria-labelledby="upload-data-tab">

                        <h6 class="card-title">Step 1: Download the template and fill in the data using this Excel
                            template only.</h6>
                        <h6 class="card-title">Step 2: Fill the template with details.</h6>
                        <div class="row my-4">
                            <div class="col">
                                <h5 class="card-title">1. Download Template:</h5>
                                <p>Using older versions of Office/Excel? Download the template here.</p>
                                <form id="createcontract" action="template-download-ind-parties" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <button type="submit"
                                        class="btn btn-primary me-sm-3 me-1 waves-effect waves-light">Download
                                        template</button>
                                </form>
                            </div>
                        </div>

                    </div>
                    <div class="tab-pane fade {{ (session('data')) ? 'show active' : '' }}" id="verify-info" role="tabpanel" aria-labelledby="verify-info-tab">

                        <div class="row my-4">
                            <div class="col">
                                <h5 class="card-title">2.Upload Data</h5>
                                <form id="createcontract" action="{{ url('parties/builk-ind-import/upload') }}"
                                    method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="csv_file" class="form-label">Choose CSV File</label>
                                        <input class="form-control" type="file" id="csv_file" name="file"
                                            accept=".xlsx" required>
                                    </div>
                                    <button type="submit"
                                        class="btn btn-primary me-sm-3 me-1 waves-effect waves-light">Upload
                                        File</button>
                                </form>
                                <?php $filemissing = 0; ?>

                                @if (session('data')) 
                                    @php 
                                        $filemissing = 0; 
                                        $sheetBodyHtml = "";
                                        
                                        foreach (session('data') as $sheetName => $sheetData){
                                            if(strtolower($sheetName) == 'organization'){
                                                $sheetBodyHtml .="<h3>Sheet: $sheetName </h3>";
                                                $sheetBodyHtml .="<div class='table-responsive mt-5 mb-5'>
                                                    <table border='1' class='table'>
                                                        <thead>
                                                            <tr>";
                                                foreach ($sheetData[0] as $header){
                                                    $sheetBodyHtml .="<th>$header</th>";
                                                }
                                                $sheetBodyHtml .="</tr></thead><tbody>";
                                                
                                                $dataample = array(); 
                                                $totalRows = 0;
                                                
                                                foreach (array_slice($sheetData, 1) as $row){
                                                    
                                                    if (array_filter($row)){
                                                    $sheetBodyHtml .="<tr>";
                                                        if(isset($dataample[$row['3'].$row['5'].$row['6']])){
                                                        $totalRows++;
                                                            foreach ($row as $key => $cell){
                                                                $sheetBodyHtml .="<td class='emptyattachemnt'>Data is duplicate</td>";
                                                            }
                                                        }else{
                                                            $parties_label = session('parties_label');
                                                            foreach ($row as $key => $cell){
                                                                $totalRows++;
                                                                
                                                                if($key == 4 && !empty($cell) && !preg_match("/".$parties_label['gst']['regex_pattern']."/",$cell)){
                                                                    $sheetBodyHtml .="<td class='emptyattachemnt'>Invalid GST $cell</td>";
                                                                }elseif($key == 5 && !empty($cell) && !preg_match("/".$parties_label['pan']['regex_pattern']."/",$cell)){
                                                                    $sheetBodyHtml .="<td class='emptyattachemnt'> Invalid PAN $cell</td>";
                                                                }else{
                                                                    $sheetBodyHtml .="<td>$cell</td>";
                                                                }
                                                            }
                                                        } 
                                                       
                                                    $sheetBodyHtml .="</tr>";
                                                    }   
                                                    $dataample[$row['3'].$row['5'].$row['6']] = '1'; 
        
                                                }
                
                                                $sheetBodyHtml .="</tbody></table></div>";
                                            }else{
                                                $totalRows = 1;
                                                $sheetBodyHtml .='<div class="row mt-4">
                                                    <div class="col-10">
                                                        <span class="alert alert-danger">
                                                              <i class="ti ti-exclamation-circle ti-md"></i>
                                                            Invalid Sheet Name ('.$sheetName.') Please use sheet name as <b class="emptyattachemnt">Individual</b>
                                                        </span>
                                                    </div>
                                                </div>';
                                            }
                                        }                                    
                                    @endphp
                                    @if($totalRows == 0)
                                        <div class="row mt-4">
                                            <div class="col-10">
                                                <span class="alert alert-danger">
                                                      <i class="ti ti-exclamation-circle ti-md"></i>
                                                    Empty Sheet Please Upload Valid Sheet
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <form id="createcontractstore" action="{{ url('parties/builk-org-import/store') }}"
                                            method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <button type="submit" id="upload"
                                                class="btn btn-primary me-sm- mt-3 me-1 waves-effect waves-light">Upload </button>
                                        </form>
    
                                        {!! $sheetBodyHtml !!}
                                    @endif                                
                                @endif



                                @if ($errors->any())
                                <div>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection