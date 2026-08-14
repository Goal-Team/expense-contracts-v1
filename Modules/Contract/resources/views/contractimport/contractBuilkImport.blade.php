@extends('layouts.layoutMaster')

@if(session('data'))
@section('title', ' Uploaded Contract')
@else
@section('title', ' Contracts Import Form')
@endif
<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/quill/typography.scss', 
'resources/assets/vendor/libs/quill/katex.scss', 
'resources/assets/vendor/libs/quill/editor.scss', 
'resources/assets/vendor/libs/select2/select2.scss', 
'resources/assets/vendor/libs/dropzone/dropzone.scss', 
'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 
'resources/assets/vendor/libs/tagify/tagify.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/quill/katex.js', 
'resources/assets/vendor/libs/quill/quill.js', 
'resources/assets/vendor/libs/cleavejs/cleave.js', 
'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 
'resources/assets/vendor/libs/moment/moment.js', 
'resources/assets/vendor/libs/flatpickr/flatpickr.js', 
'resources/assets/vendor/libs/select2/select2.js', 
'resources/assets/vendor/libs/dropzone/dropzone.js', 
'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])

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
        //checkDuplicates();
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
    function checkDuplicates(){
        if($('#duplicatesArray').length){
            document.querySelector('.loading').style.display = 'flex';
            $.ajax({
                url: APP_URL + '/contracts/builk-import/checkduplicates',
                type: 'POST',
                data: { "sessionData": $('#duplicatesArray').text()},
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    document.querySelector('.loading').style.display = 'none';
                    if(response.message == 'success'){
                        Swal.fire({
                            icon: 'success',
                            title: 'No Contract Duplicates',
                            text: response.message,
                            customClass: {
                                confirmButton: 'btn btn-success waves-effect waves-light'
                            }
                        }).then(function (result) {
                            if (result.value){
                            }
                        });
                    }else{
                        $('.step3').attr('disabled', true);
                        for(let respData of response.data){
                            $(`.${respData}`).addClass('error-row').removeClass('preview-row');
                        }                     
                    }
            
                    //
            
                },
                error: function (error) {
                    console.log('Error submitting form:', error);
                }
            });    
        }
    }
</script>

@endsection
@section('content')
<style>
    .emptyattachemnt:not(i) {
        background: red !important;
        color: #fff !important;
        text-transform: capitalize;
        border-radius: 5px;
        padding: 5px; 
        display: block;
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
    .error-row{
        border-bottom: 1px solid red !important;
    }
    tr td{
        position: relative;
    }
    .status-watermark{
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        color: white;
        background: red;
        font-size: 16px;
        text-transform: capitalize;
    } 
    
    .string {
      display: flex;
      flex-direction: column;
      text-align: center;
      animation: move 4s infinite;
    }
    
    .greeting {
      position: relative;
      top: 105px;
      animation: white-out 5s infinite;
    }
    
    .closure::after {
        content: "";
        position: absolute;
        height: 200px;
        width: 250px;
        background: #ffffff;
        transform: translate(-274px, -200px);
    }
    
    .closure::before {
        content: "";
        position: absolute;
        height: 200px;
        width: 250px;
        background: #ffffff;
        transform: translate(-210px, 50px);
    }
    
    .en {
      color: #fa8231;
    }
    
    .es {
      color: #67d231;
    }
    
    .de {
      color: #c678dd;
    }
    
    .it {
      color: #89b0bd;
    }
    
    @keyframes move {
      25% {
        transform: translatey(-75px);
        opacity: 1;
      }
      50% {
        transform: translatey(-130px);
      }
      75% {
        transform: translatey(-190px);
      }
    } 
    
    .ailoader{
      background: #ffffffcf;
      font-size: 2vmin;
      height: 100vh;
      width: 100vw;
      justify-content: center;
      align-items: center;
      color: #e4bb68;
    }
</style>

<div class="container shadow min-vh-100 py-2">
    <div class="container network_wrapper col-sm p-2">

        <!--<div class="loading" style="display:none;"><i class="ti ti-loader-2 mb-2"></i></div>-->
        <div class="loading ailoader" style="display:none;">
            <h2><span style="color:#bd8135;">Analyzing Your </span><span class="ms-1" style="color:#604a9e;"> Upload </span>["</h2>
            <div class="string">
              <h2 class="greeting en">Parties</h2>
              <h2 class="greeting es">Dates</h2>
              <h2 class="greeting de">Duplicates</h2>
              <h2 class="greeting it">Renewals</h2>  
            </div>
            <h2 class="closure">"];</h2>
            <small class="position-absolute bottom-0 left-50">Powered By <img src="{{asset('assets/logo/OnTrackLogo.png')}}" alt="ONTRACK" width="100"/> <span role="button" class="btn btn-xs rounded-pill btn-icon btn-outline-warning fw-bold text-dark"><strong>AI</strong></span></small>
        </div>

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
                            <p>Error No Data</p>
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
                                            document.querySelector('.loading').style.display = 'flex';
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
                                <div class="form-check form-switch show-error-switch">
                                  <input class="form-check-input" type="checkbox" role="switch" id="showAllData">
                                  <label class="form-check-label" for="showAllData">Show Errors Only</label>
                                </div>                                
                                <div class="row">



                                    <div class="col">
                                        <?php $filemissing = 0; ?>

                                        @if (session('data'))
                                        <!--<h2>Uploaded File Content:</h2>-->

                                        @php
                                        $AllsheetBody = "";
                                        
                                        $tags_contract_arr = [];
                                        
                                        $statusColorClass = array(
                                            'active' => 'success',
                                            'expired' => 'danger',
                                            'pending' => 'warning',
                                            'renewed' => 'info',
                                            'terminated' => 'danger',
                                            'completed' => 'secondary'
                                        );   
                                        

                                        $totalRows = 0;
                                        $allPartys = [];
                                        foreach (session('data') as $sheetName => $sheetData){
                                        
                                        $sheetBody = "";
                                        $sheetBody .='<div class="row">
                                            <div class="col-10"></div>
                                            <div class="col-1">
                                                <button class="btn btn-primary me-sm-3 me-1 waves-effect waves-light step1"
                                                    type="button" role="tab" aria-controls="attach-save" aria-selected="false">Prev </button>
                                            </div>
                                            <div class="col-1">
                                                <button class="btn btn-success me-sm-3 me-1 waves-effect waves-light step3"
                                                    type="button" role="tab" aria-controls="attach-save" aria-selected="false">Next </button>
                                            </div>
                                        </div>';
                                        $sheetBody .="<h3>Sheet: $sheetName</h3>";
                                        $sheetBody .='<div class="table-responsive mt-5 mb-5">
                                            <table border="1" class="table">
                                                <thead>
                                                    <tr>[headErrorCol]';
                                                        if(session('errorPresent') && session('errorPresent') > 0){
                                                        $sheetBody .='<th width="200">Other Errors '.session('errorPresent') .'</th>';
                                                        }
                                                        foreach ($sheetData[1] as $header){
                                                            $sheetBody .= "<th>$header</th>";
                                                        }
                                        $sheetBody .='</tr></thead>';
                                        
                                        $sheetBody .= "<tbody>";
                                            $value['error'] = false;
                                            
                                            $currentSheet = 0;
                                            
                                            foreach (array_slice($sheetData, 2) as $kyy => $row){

                                                $value[$kyy]['errormessage'] = [];
                                                $errorRowMessage = [];
                                                
                                                if (array_filter($row)){
    
                                                    if (empty($row[1][1])){
                                                        continue;
                                                    }
                                                    
                                                    if (!isset($row[1]) && !isset($row[2]) && !isset($row[3])) {
                                                        
                                                    }else{
                                                        $totalRows++;
                                                        $currentSheet++;
                                                    }
                                                    
                                                    
                                                    
                                                    $commencement_type = isset($row[19]) ? $row[19] : null;
                                                    $commencement_date = isset($row[20]) ? dateImport($row[20]) : null;
                                                    $contract_end_type = isset($row[21]) ? $row[21] : null;
                                                    $end_date_of_contract = isset($row[22]) ? dateImport($row[22]) : null;
                                                    $contract_sub_status = "";
                                                         
                                                    
                                                    // ============= Status Check ==================== //
                        
                                                    $cur_date = date('Y-m-d');
                                                    $endDateMissed = false;
                                                    
                                                    if($commencement_date != '' && $end_date_of_contract != ''){
                                                        if(strtotime($end_date_of_contract) < strtotime($commencement_date)){
                                                            $errorRowMessage[22] = 'Must Greater Than Start date';
                                                            $value[$kyy]['errormessage'][] = "End date must be greater than start date";
                                                        }
                                                    }
                                                    
                                                    if(isset($row[1]) && $row[1] === 'Legacy Contracts' && $end_date_of_contract != ''){
                                                        $contract_status = 'executed';
                                                        
                                                        if(strtotime($end_date_of_contract) > strtotime($cur_date)){
                                                            $contract_sub_status = 'active';
                                                        }elseif(strtotime($cur_date) > strtotime($end_date_of_contract)){
                                                            $contract_sub_status = 'expired';
                                                        }
                                                        
                                                    }elseif(isset($row[1]) && $row[1] === 'New'){
                                                        $contract_status = 'draft';
                                                        $contract_sub_status = 'draft';
                                                    }else{
                                                        if(($commencement_date == '' || !$commencement_date)){
                                                            $errorRowMessage[20] = 'Missing';
                                                            $value[$kyy]['errormessage'][] = "Start Date Missing";                                                       
                                                        }
                                                        if(($end_date_of_contract == '' || !$end_date_of_contract) && $contract_end_type != 'evergreen'){
                                                            $errorRowMessage[22] = 'Missing';
                                                            $value[$kyy]['errormessage'][] = "End Date Missed";                                                          
                                                        }
                                                    }
                                                    
                                                    if($contract_end_type == 'evergreen' && $row[1] != 'New'){
                                                        $contract_status = 'executed';
                                                        $contract_sub_status = 'active';
                                                    }
                                                    
                                                    
                                                    if($contract_end_type == 'onetimeContract' && $row[1] != 'New'){
                                                        $contract_status = 'executed';
                                                        if($end_date_of_contract != ''){
                                                            if(strtotime($end_date_of_contract) > strtotime($cur_date)){
                                                                $contract_sub_status = 'active';
                                                            }elseif(strtotime($cur_date) > strtotime($end_date_of_contract)){
                                                                $contract_sub_status = 'completed';
                                                            }
                                                        }else{
                                                            $contract_sub_status = 'active';
                                                        }
                                                        
                                                    }
                                                    
                                                    if($contract_end_type == 'fixedTerm' && $row[1] != 'New'){

                                                        if($end_date_of_contract == ''){
                                                            $errorRowMessage[22] = 'Missing';
                                                            $value[$kyy]['errormessage'][] = "End Date Missing For <b> Fixed Term</b>";
                                                        }
                                                        
                                                    }
                                                    
                                                    $signing_date = isset($row[18]) ? dateImport($row[18]) : null;
                                                    
                                                    if ($row[1] == 'New') {
                                                    
                                                        if($signing_date){
                                                            $errorRowMessage[18] = 'Invalid';
                                                            $value[$kyy]['errormessage'][] = "Signed Date Must Be Empty For <b> New Contract</b>";                                                            
                                                        }
                                                    } else {
                                                        
                                                        
                                                        if($signing_date == null){
                                                            $errorRowMessage[18] = 'Missing';
                                                            $value[$kyy]['errormessage'][] = "Signed Date Missing For <b> Legacy Contract</b>";                                                            
                                                        }
                                                    }
                                                    
                                                    if($commencement_date != '' && $signing_date != ''){
                                                        if(strtotime($signing_date) < strtotime($commencement_date)){
                                                            //$errorRowMessage[18] = 'Must Greater Than/Equal To Start date';
                                                            //$value[$kyy]['errormessage'][] = "Signdate date must be greater than/equal to start date";
                                                        }
                                                    }
                                                    
                                                    
                                                    $partysArray = [];
                                                    $requestArray = [];
                                                
                                                    
                                                    //For Renewals
                                                    $previous_contract_exits =  $row[6];
                                                    $previous_contract_no =  $row[7];                                                    
                                                    if($previous_contract_exits != 'No' && $previous_contract_exits != '' ){
                                                        $previous_contract_exits;
                                                        $parid = null;
                                                        if($previous_contract_exits == 'Yes-In Software'){
                                                             //$parid = Contract::where('contract_unique_id', $previous_contract_no)->pluck('id')->first(); 
                                                        }
                                                        if($previous_contract_exits == 'Yes-In This file'){
                                                             /*if(isset($roid[$previous_contract_no])){
                                                                $parid = $roid[$previous_contract_no];
                                                             }else{
                                                                 $parentMissing[$contract->id] = $previous_contract_no;
                                                                 $parid = 0;
                                                             }*/
                                                        }
                                                        
                                                        //Only Upload If Parent Contract Exist
                                                        if($parid > 0){
                                                            Contract::where('id', $parid)->update(['substatus'=> 'renewed']);
                                                            Contract::where('id', $contract->id)->update(['parentcontract'=>$parid]);
                                                        }
                                                    }
                                                    
                                                    $requestArray['fixedDate'] = $commencement_date;
                                                    $requestArray['effectiveDate'] = $contract_end_type;
                                                    $requestArray['endContractDate'] = $end_date_of_contract;
                                                    $requestArray['ContractValue'] = $row[24];
                                                    
                                                    
                                                    $sheetBody .="<tr class='".((isset($row['error']) && $row['error']) ? 'error-row ' : 'preview-row ' ).$sheetName."-".$kyy."' >
                                                        [rowErrorCol]";
                                                        if(isset($row['errormessage'])){
                                                            $sheetBody .="<td>";
                                                                if(!empty($row['errormessage'])){
                                                                    $sheetBody .='<i class="ti ti-exclamation-circle text-danger" data-bs-custom-class="tooltip-danger" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" data-bs-original-title="'.$row['errormessage'].'"></i>';
                                                                }
                                                            $sheetBody .="</td>";
                                                        }
                                                        $totalInternals = 0;
                                                        $checkInternalCols = [8,12,49,53,57];
                                                        foreach ($row as $key => $cell){
        
                                                        if(in_array($key, ['error', 'errormessage'])){
                                                            continue;
                                                        }
                                                        
        
                                                        if($key == 25){
                                                            if(empty($cell)){
                                                                $filemissing = 1;
                                                                $tags_contract_arr[] = 'tag_missed_attachment'; 
                                                                $value[$kyy]['errormessage'][] = "Attachment Missing";
                                                                $sheetBody .='<td>
                                                                    <span class="emptyattachemnt tag_missed_attachment">Attachment Missing</span>
                                                                </td>';
                                                            }else{
                                                              $ext = pathinfo($cell, PATHINFO_EXTENSION);
                                                              if(in_array(strtolower($ext),config('app.SUPPORTED_DOC_TYPES'))){
                                                                if($row[1] != 'New' && strtolower($ext) != 'pdf'){
                                                                     $tags_contract_arr[] = 'tag_invalid_attachment';
                                                                     $value[$kyy]['errormessage'][] = "Invalid Attachment Allowed Only PDF Format";
                                                                     $sheetBody .='<td>
                                                                        <span class="emptyattachemnt tag_invalid_attachment">Invalid Attachment Allowed Only PDF Format</span>
                                                                    </td>';                                                                    
                                                                }else{
                                                                    $sheetBody .='<td class="attachmentsLoaded"><i class="ti ti-file-dots ti-md text-success" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="'.$cell.'"></i></td>';
                                                                }
                                                              }else{
                                                                 $tags_contract_arr[] = 'tag_invalid_attachment';
                                                                 $value[$kyy]['errormessage'][] = "Invalid Attachment Type";
                                                                 $sheetBody .='<td>
                                                                    <span class="emptyattachemnt tag_invalid_attachment">Invalid Attachment in Row</span>
                                                                </td>';
                                                              }
                                                            }
                                                        }else{
                                                        $emptyKeys = [1, 2];
                                                        $trimmedCellLength = strlen(trim($cell));
                                                        if (in_array($key, $emptyKeys) && $trimmedCellLength === 0){
                                                            $tags_contract_arr[] = 'tag_data_missing';
                                                            $value[$kyy]['errormessage'][] = $sheetData[1][$key]." was Missing";
                                                            $sheetBody .="<td><span class='emptyattachemnt tag_data_missing'>$cell Missing</span></td>";
                                                        }else{
                                                            $sheetBody .= '<td>';
        
                                                            if($key == 0){
                                                                if(empty($cell)){
                                                                    $tags_contract_arr[] = 'tag_number_missing';
                                                                    $value[$kyy]['errormessage'][] = "Number was Missing in ".$sheetData[1][$key];
                                                                    $sheetBody .= '<span class="emptyattachemnt tag_number_missing">Number Missing</span>';
                                                                }else{
                                                                $sheetBody .= $cell;
                                                                }
                                                            }elseif($key == 1){
                                                                if(empty($cell)){
                                                                    $tags_contract_arr[] = 'tag_data_missing';
                                                                    $value[$kyy]['errormessage'][] = $sheetData[1][$key]." was Missing";
                                                                    $sheetBody .= '<span class="emptyattachemnt tag_data_missing">missing</span>';
                                                                }else{
                                                                    if(in_array($cell, session('checkType'))){
                                                                        $sheetBody .= $cell;
                                                                    }else{
                                                                        $tags_contract_arr[] = 'tag_contract_mode_missing';
                                                                        $value[$kyy]['errormessage'][] = $sheetData[1][$key]." ($cell) was Mismatched";
                                                                        $sheetBody .= '<span class="emptyattachemnt tag_contract_mode_missing">('.$cell.') was Mismatched</span>';
                                                                    }
                                                                }
        
                                                            }elseif($key == 2){
                                                                if(empty($cell)){
                                                                    $tags_contract_arr[] = 'tag_data_missing';
                                                                    $value[$kyy]['errormessage'][] = $sheetData[1][$key]." was Missing";
                                                                    $sheetBody .= '<span class="emptyattachemnt tag_data_missing">missing</span>';
                                                                }else{
                                                                    if(in_array($cell, session('checkDepartment') ?? [])){
                                                                        $sheetBody .= $cell;
                                                                        $requestArray['DepartmentType'] = $cell;
                                                                    }else{
                                                                        $tags_contract_arr[] = 'tag_department_missing';
                                                                        $value[$kyy]['errormessage'][] = $sheetData[1][$key]." ($cell) was Mismatched";
                                                                        $sheetBody .= '<span class="emptyattachemnt tag_department_missing">('.$cell.') was Mismatched</span>';
                                                                    }
                                                                }
        
                                                            }elseif($key == 3){
                                                                if(empty($cell)){
                                                                    $tags_contract_arr[] = 'tag_data_missing';
                                                                    $value[$kyy]['errormessage'][] = $sheetData[1][$key]." was Missing";
                                                                    $sheetBody .= '<span class="emptyattachemnt tag_data_missing">missing</span>';
                                                                }else{
                                                                    if(in_array($cell, session('checkCategory') ?? [])){
                                                                        $sheetBody .= $cell;
                                                                        $requestArray['catgoeryType'] = $cell;
                                                                    }else{
                                                                        $tags_contract_arr[] = 'tag_category_missing';
                                                                        $value[$kyy]['errormessage'][] = $sheetData[1][$key]." ($cell) was Mismatched";
                                                                        $sheetBody .= '<span class="emptyattachemnt tag_category_missing">('.$cell.') was Mismatched</span>';
                                                                    }
                                                                }
        
                                                            }elseif($key == 4){
                                                                if(empty($cell)){
                                                                    $tags_contract_arr[] = 'tag_data_missing';
                                                                    $value[$kyy]['errormessage'][] = $sheetData[1][$key]." was Missing";
                                                                    $sheetBody .= '<span class="emptyattachemnt tag_data_missing">missing</span>';
                                                                }else{
                                                                    if(in_array($cell, session('checkExclusivity') ?? [])){
                                                                        $sheetBody .= $cell;
                                                                    }else{
                                                                        $tags_contract_arr[] = 'tag_exclusivity_missing';
                                                                        $value[$kyy]['errormessage'][] = $sheetData[1][$key]." ($cell) was Mismatched";
                                                                        $sheetBody .= '<span class="emptyattachemnt tag_exclusivity_missing">('.$cell.') was Mismatched</span>';
                                                                    }
                                                                }
                                                            }elseif($key == 8 || $key == 12 || $key == 49 || $key == 53 || $key == 57){
                                                                if(!empty($cell)){
                                                                    if(in_array($cell, session('checkPartyType') ?? [])){
                                                                        $sheetBody .= $cell;
                                                                        $partysArray[$key]['mode'] = $cell; 
                                                                    }else{
                                                                        $tags_contract_arr[] = 'tag_party_type_missing';
                                                                        $value[$kyy]['errormessage'][] = $sheetData[1][$key]." ($cell) was Mismatched";
                                                                        $sheetBody .= '<span class="emptyattachemnt tag_party_type_missing">('.$cell.') was Mismatched</span>';
                                                                    }
                                                                }elseif(empty($cell) && ($key == 8 || $key == 12)){
                                                                    $tags_contract_arr[] = 'tag_party_type_missing';
                                                                    $value[$kyy]['errormessage'][] = $sheetData[1][$key]." Party Type Missing";
                                                                    $sheetBody .= '<span class="emptyattachemnt tag_party_type_missing">Party Type Missing</span>';                                                                
                                                                }
        
                                                            }elseif($key == 9 || $key == 13 || $key == 50 || $key == 54 || $key == 58){
                                                                if(!empty($row[$key-1]) && in_array($row[$key-1], ['Internal','Intergroup'])){
                                                                    if(!empty($cell)){
                                                                        if(in_array($cell, session('checkInternalPartyName') ?? [])){
                                                                            $sheetBody .= $cell;
                                                                            $partysArray[$key-1]['internal_name'] = $cell;
                                                                        }else{
                                                                            $tags_contract_arr[] = 'tag_internal_party_missing';
                                                                            $value[$kyy]['errormessage'][] = $sheetData[1][$key]." ($cell) was Mismatched";
                                                                            $sheetBody .= '<span class="emptyattachemnt tag_internal_party_missing">('.$cell.') was Mismatched</span>';
                                                                        }
                                                                    }else{
                                                                            $tags_contract_arr[] = 'tag_internal_party_missing';
                                                                            $value[$kyy]['errormessage'][] = $sheetData[1][$key]." Missing";
                                                                            $sheetBody .= '<span class="emptyattachemnt tag_internal_party_missing">Missing</span>';                                                                    
                                                                    }
                                                                }
        
                                                            }elseif($key == 10 || $key == 14 || $key == 51 || $key == 55 || $key == 59){
                                                                if(!empty($row[$key-2]) && in_array($row[$key-2], ['Internal','Intergroup'])){
                                                                    if(!empty($cell)){
                                                                        if(in_array($cell, session('checkPartyInternalLocation') ?? [])){
                                                                            $sheetBody .= $cell;
                                                                            $currInterKey = $key - 2;
                                                                            if($row[$currInterKey] == 'Internal'){
                                                                                $partysArray[$currInterKey]['location'] = $cell;
                                                                            }
                                                                            if($row[$currInterKey] == 'Intergroup'){
                                                                                $partysArray[$currInterKey]['location_grp'] = $cell;
                                                                            }                                                                            
                                                                        }else{
                                                                            $tags_contract_arr[] = 'tag_internal_location_missing';
                                                                            $value[$kyy]['errormessage'][] = $sheetData[1][$key]." ($cell) was Mismatched";
                                                                            $sheetBody .= '<span class="emptyattachemnt tag_internal_location_missing">('.$cell.') was Mismatched</span>';
                                                                        }
                                                                    }else{
                                                                            $tags_contract_arr[] = 'tag_internal_location_missing';
                                                                            $value[$kyy]['errormessage'][] = $sheetData[1][$key]." Missing";
                                                                            $sheetBody .= '<span class="emptyattachemnt tag_internal_location_missing">Missing</span>';                                                                    
                                                                    }
                                                                }
        
                                                            }elseif($key == 11 || $key == 15 || $key == 52 || $key == 56 || $key == 60){
                                                                if(!empty($row[$key-3]) && !in_array($row[$key-3], ['Internal','Intergroup'])){
                                                                    if(!empty($cell)){
                                                                        if(Helper::arraySearchPartial($cell, session('checkPartyExternalPartyName') ?? [])){
                                                                            $sheetBody .= explode(':',$cell)[0] ?? null;
                                                                            
                                                                            $partysArray[$key-3]['external_name'] = explode(':',$cell)[0] ?? '-';
                                                                        }else{
                                                                            $tags_contract_arr[] = 'tag_external_missing';
                                                                            $value[$kyy]['errormessage'][] = $sheetData[1][$key]." ($cell) was Mismatched";
                                                                            $sheetBody .= '<span class="emptyattachemnt tag_external_missing">('.$cell.') was Mismatched</span>';
                                                                        }
                                                                    }else{
                                                                            $tags_contract_arr[] = 'tag_external_missing';
                                                                            $value[$kyy]['errormessage'][] = $sheetData[1][$key]." Missing";
                                                                            $sheetBody .= '<span class="emptyattachemnt tag_external_missing">Missing</span>';                                                                    
                                                                    }
                                                                }
        
                                                            }elseif($key == 16 || $key == 17){
                                                                if(!empty($cell)){
                                                                    if(in_array($cell, session('checkCoordinator') ?? [])){
                                                                        $sheetBody .= $cell;
                                                                    }else{
                                                                        $tags_contract_arr[] = 'tag_coordinate_missing';
                                                                        $value[$kyy]['errormessage'][] = $sheetData[1][$key]." ($cell) was Mismatched";
                                                                        $sheetBody .= '<span class="emptyattachemnt tag_coordinate_missing">('.$cell.') was Mismatched</span>';
                                                                    }
                                                                }
        
                                                            }elseif (in_array($key, $checkInternalCols)) {
                                                                if (isset($cell) && !empty($row[$key+2]) && $cell == "Internal") {
                                                                    /*if ($totalInternals == 0) {
                                                                        $internal_first_location = $partyLoc;
                                                                    }*/
                                                                    $totalInternals++;
                                                                }
                                                                
                                                                if ($totalInternals > 1) {
                                                                    $tags_contract_arr[] = 'tag_internal_party_exceed_limit';
                                                                    $value[$kyy]['errormessage'][] = $sheetData[1][$key]." ($cell) Only One Internal Allowed";
                                                                    $sheetBody .= '<span class="emptyattachemnt tag_internal_party_exceed_limit">Only One Internal Allowed</span>';
                                                                }                                                             
                                                            }
                                                            
                                                            else{
                                                                $sheetBody .= isset($errorRowMessage[$key]) ? '<span class="emptyattachemnt tag_data_missing">'.$errorRowMessage[$key].'</span>' : $cell;
                                                            }
        
                                                        $sheetBody .= '<span class="d-none">'.json_encode($partysArray).'</span></td>';
                                                        }
        
                                                        }
                                                        }
        
                                                    $sheetBody .= '</tr>';
                                                    
                                                    $statusTag = "";
                                                    if(isset($contract_sub_status) && $contract_sub_status != ""){
                                                       $colorClassStatus = $statusColorClass[$contract_sub_status] ?? 'primary';
                                                       $statusTag = "<span class='status-watermark bg-$colorClassStatus m-1'>$contract_sub_status</span>";
                                                    }
                                                    
                                                    $requestArray['partys'] = $partysArray;
                                                    $allPartys[$sheetName][$kyy] = $requestArray;                                                    
                                                    $contractDuplicate = getContractDuplicates($sheetName,$requestArray);
                                                    if($contractDuplicate){
                                                        $value[$kyy]['errormessage'][] = 'Duplicate Contract Exist '.$contractDuplicate->contract_unique_id;
                                                    }

                                                    
                                                    //For Error Columns
                                                    $rowErrorCol = '<td>$statusTag</td>';
                                                    if(isset($value[$kyy]['errormessage'])){
                                                        $rowErrorCol ="<td>$statusTag";
                                                        if(count($value[$kyy]['errormessage']) > 0){
                                                            $idxz = 0;
                                                            $errorList = '<ol>';
                                                            foreach($value[$kyy]['errormessage'] as $errTxt){
                                                               if($idxz == 0){
                                                                $errorList .= '<hr/>';
                                                               }
                                                               $idxz++;
                                                               $errorList .= '<li>'.$errTxt.'<hr/></li>'; 
                                                            }
                                                            $errorList .= '</ol>';
                                                            
                                                            $value['error'] = !$value['error'] ? true : $value['error'];
                                                            $rowErrorCol .='<i class="emptyattachemnt ti ti-exclamation-circle text-danger mb-3" data-bs-custom-class="tooltip-danger" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" data-bs-original-title="'.$errorList.'"></i>';
                                                        }else{
                                                            $rowErrorCol .='<i class="ti ti-circle-check text-success mb-3" data-bs-custom-class="tooltip-success" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" data-bs-original-title="No Errors"></i>';
                                                        }
                                                        $rowErrorCol .="</td>";
                                                    }                                
                                                    if($value['error']){
                                                        $sheetBody = str_replace("[rowErrorCol]", $rowErrorCol, $sheetBody);
                                                    }else{
                                                       $sheetBody = str_replace("[rowErrorCol]", $rowErrorCol, $sheetBody); 
                                                    }
                                                }
                                                

                                            }

                                    $sheetBody .= '</tbody>';
                                    $headErrorCol ='<th width="100">Errors/Current Status</th>';
                                    $sheetBody = str_replace('[headErrorCol]', $headErrorCol, $sheetBody);
                                    
                                            
                                
                                            
                                    $sheetBody .= '</table></div>';
                                    
                                    if($currentSheet == 0){
                                      $sheetBody = "";  
                                    }
                                    
                                    $AllsheetBody .= $sheetBody;
                                }
                                
                                $tags_html = '<div class="d-flex flex-wrap gap-1 my-2" role="group">';
                                
                                $final_tags = array_count_values($tags_contract_arr);
                                
                                if($totalRows == 0){
                                    $AllsheetBody = '<div class="row">
                                            <div class="col-10">
                                                <span class="alert alert-danger">
                                                      <i class="ti ti-exclamation-circle ti-md"></i>
                                                    Empty Sheet Please Upload Valid Sheet
                                                </span>
                                            </div>
                                            <div class="col-2">
                                                <button class="btn btn-primary me-sm-3 me-1 waves-effect waves-light step1 float-end"
                                                    type="button" role="tab" aria-controls="attach-save" aria-selected="false">Prev </button>
                                            </div>
                                        </div>
                                        <style>.show-error-switch{ display: none; }</style>
                                        ';
                                }                                
                                
                                foreach($final_tags as $ktag => $tagcount){
                                    if($tagcount > 0){
                                        $withoutPrefix = preg_replace('/^tag_/', '', $ktag);
                                        $parts = explode('_', $withoutPrefix);
                                        $tags_text = implode(' ', array_map('ucfirst', $parts));
                                        
                                        $tags_html .= '<button type="button" class="toggle-error-rows mx-2 btn btn-xs btn-label-danger waves-effect" data-toggle-rows="'.$ktag.'">'.$tags_text.'<span class="badge badge-center rounded-pill bg-white text-dark fw-bold ms-1">'.$tagcount.'</span></button>';
                                    }
                                }
                                
                                $tags_html .= '</div>';
                                
                                $AllsheetBody .= '<span class="d-none" id="duplicatesArray">'.json_encode($allPartys).'</span>';
                                
                        @endphp
                                    <!-- Tags --> 
                                    {!! $tags_html !!}
                                    
                                    {!! $AllsheetBody !!}

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