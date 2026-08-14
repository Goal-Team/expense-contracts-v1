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
        if ($('.emptyattachemnt').length > 0) {
            $('#uplod button').attr('disabled', true);
            $('.step3').attr('disabled', true);
        }; 
        $(document).on('click', '.step3', function() { 
            $('#attach-save-tab').click();
        });

        $(document).on('click', '.step2', function() { 
            $('#verify-info-tab').click();
        });

        $(document).on('click', '.step1', function() { 
            $('#upload-data-tab').click();
        });

    });
</script>

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

    .loading {
        position: fixed;
        background: #00000099;
        width: 100%;
        left: 0;
        z-index: 999999;
        top: 0;
        height: 100vh;
    }

    .loading i {
        color: #fff;
        font-size: 2rem;
        text-align: center;
        position: absolute;
        left: 50%;
        top: 50%;
        animation: rotateAnimation 2s linear infinite;
        /* 2-second animation, running infinitely */
    }

    /* Define the keyframes for rotation */
    @keyframes rotateAnimation {
        from {
            transform: rotate(0deg);
            /* Start at 0 degrees */
        }

        to {
            transform: rotate(360deg);
            /* Rotate full circle */
        }
    }

</style>



<div class="container shadow min-vh-100 py-2">
    <div class="container network_wrapper col-sm p-2">

        <div class="loading" style="display:none;"><i class="ti ti-loader-2 mb-2"></i></div>

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
                <h5 class="card-title">Bulk Upload Contract</h5>
                <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">

                    @if(session('data'))
                    <li class="nav-item" role="presentation">
                        <button class="nav-link " id="upload-data-tab" data-bs-toggle="tab"
                            data-bs-target="#upload-data" type="button" role="tab" aria-controls="upload-data"
                            aria-selected="true">1. Download Data</button>
                    </li>
                    <li class="nav-item " role="presentation">
                        <button class="nav-link active " id="verify-info-tab" data-bs-toggle="tab" data-bs-target="#verify-info"
                            type="button" role="tab" aria-controls="verify-info" aria-selected="false">
                            2. Verify Info
                        </button>
                    </li>
                    @else
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="upload-data-tab" data-bs-toggle="tab"
                            data-bs-target="#upload-data" type="button" role="tab" aria-controls="upload-data"
                            aria-selected="true">1. Download Data</button>
                    </li>
                    <li class="nav-item " role="presentation">
                        <button class="nav-link" id="verify-info-tab" data-bs-toggle="tab" data-bs-target="#verify-info"
                            type="button" role="tab" aria-controls="verify-info" aria-selected="false">
                            2. Verify Info
                        </button>
                    </li>

                    @endif
                    <li class="nav-item" id="uplod" role="presentation">
                        <button class="nav-link" id="attach-save-tab" data-bs-toggle="tab" data-bs-target="#attach-save"
                            type="button" role="tab" aria-controls="attach-save" aria-selected="false">3. Attach
                            and Save</button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">

                    @if(session('data'))
                    <div class="tab-pane fade " id="upload-data" role="tabpanel"
                        aria-labelledby="upload-data-tab">
                        @else
                        <div class="tab-pane fade show active" id="upload-data" role="tabpanel"
                            aria-labelledby="upload-data-tab">
                            @endif

                            @if (session('data'))
                            <!--<h2>Uploaded File Content:</h2>-->
                            <div class="row">
                                <div class="col-11"></div>

                                <div class="col-1">
                                    <button class="btn btn-success me-sm-3 me-1 waves-effect waves-light step2"
                                        type="button" role="tab" aria-controls="attach-save" aria-selected="false">Next </button>
                                </div>
                            </div>


                            @endif

                            <h6 class="card-title">Step 1: Download the template and fill in the data using this Excel
                                template only.</h6>
                            <h6 class="card-title">Step 2: Fill the template with details.</h6>
                            <h6 class="card-title">Step 3: Upload attachments for data shared in the Excel sheet.</h6>
                            <p>Please ensure attachment file names contain the branch name, or else the attachment will not
                                be attached.</p>
                            <div class="row my-4">
                                <div class="col">
                                    <h5 class="card-title">1. Download Template:</h5>
                                    <p>Using older versions of Office/Excel? Download the template here.</p>
                                    <form id="createcontract" class="ignore-loading" action="template-download" method="POST"
                                        enctype="multipart/form-data">
                                        @csrf
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
                                            @if ($errors->any())
                                                <ul class="list-unstyled mb-0">
                                                    @foreach ($errors->all() as $error)
                                                        <li class="text-dark"><i class="ti ti-exclamation-circle text-danger"></i> {{ ucwords($error) }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif                                            
                                        </div>
                                        <button type="submit"
                                            class="btn btn-primary me-sm-3 me-1 waves-effect waves-light">Download
                                            template</button>
                                    </form>

                                    <h5 class="card-title mt-4">2.Upload Data</h5>
                                    <form id="createcontractview" action="{{url('contracts/builk-import/upload')}}"
                                        method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="csv_file" class="form-label">Choose CSV File</label>
                                            <input class="form-control" type="file" id="csv_file" name="file"
                                                accept=".xlsx, .xls" required>
                                        </div>
                                        <button type="submit"
                                            class="btn btn-primary me-sm-3 me-1 waves-effect waves-light">Upload
                                            File</button>
                                    </form>

                                    <script>
                                        document.getElementById('csv_file').addEventListener('change', function() {
                                            // Submit the form
                                            document.getElementById('createcontractview').submit();

                                            // Show the loading spinner/message
                                            document.querySelector('.loading').style.display = 'block';
                                        });
                                    </script>
                                </div>
                            </div>

                        </div>
                        @if(session('data'))
                        <div class="tab-pane fade active show" id="verify-info" role="tabpanel" aria-labelledby="verify-info-tab">
                            @else
                            <div class="tab-pane fade" id="verify-info" role="tabpanel" aria-labelledby="verify-info-tab">
                                @endif

                                <div class="row">



                                    <div class="col">
                                        <?php $filemissing = 0; ?>

                                        @if (session('data'))
                                        <!--<h2>Uploaded File Content:</h2>-->
                                        <div class="row">
                                            <div class="col-10"></div>
                                            <div class="col-1">
                                                <button class="btn btn-primary me-sm-3 me-1 waves-effect waves-light step1"
                                                    type="button" role="tab" aria-controls="attach-save" aria-selected="false">Prev </button>
                                            </div>
                                            <div class="col-1">
                                                <button class="btn btn-success me-sm-3 me-1 waves-effect waves-light step3"
                                                    type="button" role="tab" aria-controls="attach-save" aria-selected="false">Next </button>
                                            </div>
                                        </div>


                                        @foreach (session('data') as $sheetName => $sheetData)

                                        <h3>Sheet: {{ $sheetName }}</h3>
                                        <div class="table-responsive mt-5 mb-5">
                                            <table border="1" class="table">
                                                <thead>
                                                    <tr>
                                                        @if(session('errorPresent') && session('errorPresent') > 0)
                                                        <th width="200">
                                                            Other Errors {{ session('errorPresent') }}
                                                        </th>
                                                        @endif
                                                        @foreach ($sheetData[1] as $header)
                                                        <th>{{ $header }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody>

                                                    @foreach (array_slice($sheetData, 2) as $row)

                                                    @if (array_filter($row))

                                                    @if (empty($row[1][1]))
                                                    @continue
                                                    @endif
                                                    
                                                    <tr class="{{(isset($row['error']) && $row['error']) ? 'error-row border-bottom border-danger' : 'preview-row' }}">
                                                        @if (isset($row['errormessage']))
                                                            <td>
                                                                @if(!empty($row['errormessage']))
                                                                <i class="ti ti-exclamation-circle text-danger" data-bs-custom-class="tooltip-danger" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" data-bs-original-title="{!! $row['errormessage'] !!}"></i>
                                                                @endif
                                                            </td>
                                                        @endif
                                                        @foreach ($row as $key => $cell)

                                                        @if (in_array($key, ['error', 'errormessage']))
                                                            @continue
                                                        @endif

                                                        @if ($key == 25)
                                                            @if(empty($cell))
                                                            @php 
                                                            $filemissing = 1; 
                                                            @endphp
                                                            <td class="emptyattachemnt">
                                                                ATTACHMENT MISSING {{$key}}
                                                            </td>
                                                            @else
                                                                @php 
                                                                    $ext = pathinfo($cell, PATHINFO_EXTENSION);
                                                                @endphp                                                            
                                                              @if(in_array($ext,config('app.SUPPORTED_DOC_TYPES')))
                                                                <td>{{$cell}}</td>
                                                              @else
                                                                <td class="emptyattachemnt">
                                                                    Invalid Attachment in Row {{$ext}}
                                                                </td>
                                                              @endif
                                                            @endif
                                                        @else
                                                        @php
                                                        $emptyKeys = [1, 2, 5, 9, 15];
                                                        $trimmedCellLength = strlen(trim($cell));
                                                        @endphp

                                                        {{-- Check for array[5] conditions --}}
                                                        @if ($key == 5)
                                                        @if (trim($cell) === 'Internal')
                                                        {{-- Check if array[6] and array[7] are required --}}
                                                        @if (empty($row[6]) || empty($row[7]))
                                                        <td>PARTY 1 INTERNAL NAME & LOCATION</td>
                                                        @else
                                                        <td>{{$cell}}</td>
                                                        @endif
                                                        @elseif (trim($cell) === 'External')
                                                        {{-- Check if array[8] is required --}}
                                                        @if (empty($row[8]))
                                                        <td>PARTY 1 EXTERNAL PARTY NAME</td>
                                                        @else
                                                        <td>{{$cell}} </td>
                                                        @endif
                                                        @else
                                                        <td>{{$cell}}</td>
                                                        @endif

                                                        {{-- Check for array[9] conditions --}}
                                                        @elseif ($key == 9)
                                                        @if (trim($cell) === 'Internal')
                                                        {{-- Check if array[10] is required --}}
                                                        @if (empty($row[10]) || empty($row[11]))
                                                        <td>PARTY 2 INTERNAL NAME & LOCATION</td>
                                                        @else
                                                        <td>{{$cell}}</td>
                                                        @endif
                                                        @elseif (trim($cell) === 'External')
                                                        {{-- Check if array[12] is required --}}
                                                        @if (empty($row[12]))
                                                        <td>PARTY 2 EXTERNAL PARTY NAME</td>
                                                        @else
                                                        <td>{{$cell}}</td>
                                                        @endif
                                                        @else
                                                        <td>{{$cell}}</td>
                                                        @endif

                                                        {{-- Handle other keys and conditions --}}
                                                        @elseif (in_array($key, $emptyKeys) && $trimmedCellLength === 0)
                                                        <td>{{$cell}} Missing</td>
                                                        @else
                                                        <td>

                                                            @if($key == 0)
                                                            @if(empty($cell))
                                                            <span class="emptyattachemnt">Number Missing</span>
                                                            @else
                                                            {{$cell}}
                                                            @endif
                                                            @elseif($key == 1)
                                                            @if(empty($cell))
                                                            <span class="emptyattachemnt">missing</span>
                                                            @else
                                                            @if(in_array($cell, session('checkType')))
                                                            {{$cell}}
                                                            @else
                                                            <span class="emptyattachemnt">missing</span>
                                                            @endif
                                                            @endif

                                                            @elseif($key == 2)
                                                            @if(empty($cell))
                                                            <span class="emptyattachemnt">missing</span>
                                                            @else
                                                            @if(in_array($cell, session('checkDepartment') ?? []))
                                                            {{$cell}}
                                                            @else
                                                            <span class="emptyattachemnt">missing</span>
                                                            @endif
                                                            @endif

                                                            @elseif($key == 3)
                                                            @if(empty($cell))
                                                            <span class="emptyattachemnt">missing</span>
                                                            @else
                                                            @if(in_array($cell, session('checkCategory') ?? []))
                                                            {{$cell}}
                                                            @else
                                                            <span class="emptyattachemnt">missing</span>
                                                            @endif
                                                            @endif

                                                            @elseif($key == 4)
                                                            @if(empty($cell))
                                                            <span class="emptyattachemnt">missing</span>
                                                            @else
                                                            @if(in_array($cell, session('checkExclusivity') ?? []))
                                                            {{$cell}}
                                                            @else
                                                            <span class="emptyattachemnt">missing</span>
                                                            @endif
                                                            @endif
                                                            @elseif($key == 8 || $key == 12)
                                                            @if(!empty($cell))
                                                            @if(in_array($cell, session('checkPartyType') ?? []))
                                                            {{$cell}}
                                                            @else
                                                            <span class="emptyattachemnt">missing</span>
                                                            @endif
                                                            @endif

                                                            @elseif($key == 9 || $key == 13)
                                                            @if(!empty($cell))
                                                            @if(in_array($cell, session('checkInternalPartyName') ?? []))
                                                            {{$cell}}
                                                            @else
                                                            <span class="emptyattachemnt">missing</span>
                                                            @endif
                                                            @endif

                                                            @elseif($key == 10 || $key == 14)
                                                            @if(!empty($cell))
                                                            @if(in_array($cell, session('checkPartyInternalLocation') ?? []))
                                                            {{$cell}}
                                                            @else
                                                            <span class="emptyattachemnt">missing</span>
                                                            @endif
                                                            @endif

                                                            @elseif($key == 11 || $key == 15)
                                                            @if(!empty($cell))
                                                            @if(Helper::arraySearchPartial($cell, session('checkPartyExternalPartyName') ?? []))
                                                            {{$cell}}
                                                            @else
                                                            <!--{{$cell}} -->
                                                            <span class="emptyattachemnt">missing</span>
                                                            @endif
                                                            @endif

                                                            @elseif($key == 16 || $key == 17)
                                                            @if(!empty($cell))
                                                            @if(in_array($cell, session('checkCoordinator') ?? []))
                                                            {{$cell}}
                                                            @else
                                                            <span class="emptyattachemnt">missing</span>
                                                            @endif
                                                            @endif

                                                            @else
                                                            {{$cell}}
                                                            @endif

                                                        </td>
                                                        @endif

                                                        @endif
                                                        @endforeach

                                                    </tr>
                                                    @endif
                                                    @endforeach

                                                </tbody>
                                            </table>
                                        </div>
                                        @endforeach
                                        @endif

                                        @if ($filemissing == 1)
                                        <div class="emptyattachemnt">CONTRACT ATTACHMENTS
                                            Missing</div>
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
                            <div class="tab-pane fade" id="attach-save" role="tabpanel" aria-labelledby="attach-save-tab">
                                <div class="  mb-4">

                                    @if (session('data'))
                                    <!--<h2>Uploaded File Content:</h2>-->
                                    <div class="row">
                                        <div class="col-11"></div>
                                        <div class="col-1">
                                            <button class="btn btn-primary me-sm-3 me-1 waves-effect waves-light step2"
                                                type="button" role="tab" aria-controls="attach-save" aria-selected="false">Prev </button>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="card-body">
                                        <h5 class="card-title">Upload Attachments</h5>

                                        <form method="POST" enctype="multipart/form-data"
                                            action="{{url('contracts/builk-import/storefile')}}">
                                            @csrf

                                            <div class="form-group files color">
                                                <input type="file" name="files[]" class="form-control" multiple>
                                            </div>
                                            <button id="submit-files" type="submit"
                                                class="btn btn-primary waves-effect waves-light mt-3">Upload</button>
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