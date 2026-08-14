@extends('layouts/blankLayout')
@section('title', ' Contracts')
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
'resources/assets/vendor/libs/tagify/tagify.js',
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

@endsection
<!-- Page Scripts -->
@section('page-script')

@vite(['resources/assets/js/forms-file-upload.js'])
@vite(['resources/assets/js/form-layouts.js'])

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/jquery-ui.js"></script>
<script type="module" src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/25686/jSignature.min.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/externalApp.js"></script>
@endsection

@section('content')
@if(isset($_GET['clearcokke']))
<?php 
cookie()->queue(cookie()->forget('historical'));
cookie()->queue(cookie()->forget('attachment')); ?>
@if(request()->cookie('historical') )
<script>
    window.location.reload();
</script>
@endif
@endif
@if(isset($_GET['history']))
<?php
cookie()->queue('historical', $_GET['history'], 60);
?>
@endif
@if(isset($_GET['attachment']))
<?php
die;
cookie()->queue('attachment', true, 60);
?>
@endif
@if (isset($_GET['tab']) && $_GET['tab'] == 'attachment')
<script>
    if(document.getElementById("attachmentDivFocus")){
        window.onload = function() {
          document.getElementById("attachmentDivFocus").scrollIntoView();;
        }
    }
</script>
@endif
<style>
    .myFile.disabled,
    .myFilenew.disabled {
        display: none;
    }

    #approvalForm .btn-label-warning,
    #approvalForm .btn-label-warning:hover {

        border-color: transparent !important;
        background: #7367f0 !important;
        color: #fff !important;

    }

    table thead tr {
        vertical-align: middle;
    }

    .dateTd {
        min-width: 150px;
    }

    .substatusText {
        text-transform: capitalize;
    }

    /*** Timeline Start ***/
    .horizontal.timeline {
        display: flex;
        position: relative;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    .horizontal.timeline:before {
        content: "";
        display: block;
        position: absolute;
        width: 100%;
        height: 0.2em;
        background-color: #f2f2f2;
    }

    .horizontal.timeline .line {
        display: block;
        position: absolute;
        width: 12.5%;
        height: 0.2em;
        background-color: green;
    }

    .horizontal.timeline .steps {
        display: flex;
        position: relative;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    .horizontal.timeline .steps .step {
        display: block;
        position: relative;
        bottom: calc(100% + 1em);
        margin: 0;
        box-sizing: content-box;
        background-color: #d4d4d4;
        border-radius: 50%;
        z-index: 500;
        width: 25px;
        height: 25px;
    }

    .horizontal.timeline .steps .step.done {
        background-color: green;
    }

    .horizontal.timeline .steps .step.done {
        background-color: green;
    }

    .horizontal.timeline .steps .step.current {
        background-color: orange;
    }

    .horizontal.timeline .steps .step:first-child {
        margin-left: 0;
    }

    .horizontal.timeline .steps .step:last-child {
        margin-right: 0;
        color: #71CB35;
    }

    .horizontal.timeline .steps .step span:not(.progress-text) {
        position: absolute;
        top: calc(100% + 1em);
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
        color: #000;
    }

    .horizontal.timeline .steps .step.current span:not(.progress-text):before {
        content: "";
        display: block;
        position: absolute;
        top: -27px;
        left: 50%;
        transform: translate(-50%, -50%);
        padding: 1em;
        background-color: currentColor;
        border-radius: 50%;
        opacity: 0;
        z-index: -1;
        animation-name: animation-timeline-current;
        animation-duration: 2s;
        animation-iteration-count: infinite;
        animation-timing-function: ease-out;
    }

    .horizontal.timeline .steps .step.current span {
        opacity: 0.8;
    }

    .step:before,
    .step:after {
        content: '';
        width: calc(100% + 4em);
        border-bottom: 2px solid #d4d4d4;
        position: absolute;
        top: 50%;

    }

    .step.done:after,
    .step.done:before {
        border-color: green;
    }

    .step.current:before {
        border-color: green;
    }

    .step:after {
        left: 100%;
    }

    .step:before {
        right: 100%;
    }

    .step:after {
        left: 100%;
    }

    .step:before {
        right: 100%;
    }

    .step:first-of-type:before,
    .step:last-of-type:after {
        display: none;
    }

    @media (max-width: 768px) {
  
        .step:before, .step:after{
            border-bottom: 0;
            width: calc(100% + 1em);
        }

        .progress-text{
            display: none;
        }
        
    }

    @media (min-width: 1440px) {
        .step:before, .step:after{
            width: calc(100% + 8em);
        }
    }    

    @keyframes animation-timeline-current {
        from {
            transform: translate(-50%, -50%) scale(0);
            opacity: 1;
        }

        to {
            transform: translate(-50%, -50%) scale(1);
            opacity: 0;
        }
    }

  .accordion-item.has-error {
      border: 1px solid red !important;
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
<div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
       <div class="d-flex flex-column justify-content-center">
          <h4 class="mb-1 mt-3">Contract Details</h4>
       </div>
    </div>
    <div class="row invoice-preview mb-4">
        <div class="col-12">
            <div class="card invoice-preview-card">
                <div class="card-body rounded p-0">
                    <div class="d-flex flex-xl-row flex-md-column flex-sm-row flex-column p-4">
                        <div class="mb-xl-0 mb-6 col-xl-6 col-md-12 col-sm-7 col-12">
                            <div class="d-flex svg-illustration mb-6 gap-2 align-items-center">
                                <h5 class="fw-bold">
                                    {{ decryptString($contract->contract_name, 'contract_name') }}
                                </h5>
                            </div>
                            <table>
                                <tbody>
                                    <tr>
                                        <td class="pe-4 text-start">Contract ID:</td>
                                        <td class="text-start border-bottom-0">{{ $contract->contract_unique_id }}</td>
                                    </tr>
                                    @if(isset($contract->fixed_date))
                                    <tr>
                                        <td class="pe-4 text-start">Effective From:</td>
                                        <td class="fw-medium text-start border-bottom-0">{{date("d-m-Y", strtotime($contract->fixed_date))}}</td>
                                    </tr>
                                    <tr>
                                        <td class="pe-4 text-start">Termination On:</td>
                                        <td class="fw-medium text-start border-bottom-0">
                                            @if(isset($contract->contract_end_date) && $contract->contract_end_date != 'null' && strtolower($contract->substatus) != 'terminated')
                                            {{date("d-m-Y", strtotime($contract->contract_end_date))}}
                                            @elseif(strtolower($contract->substatus) == 'terminated')
                                            {{date("d-m-Y", strtotime($contract->termination_date))}}
                                            @else 
                                            {{'NA'}} 
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    @if(!empty(decryptString($contract->currency_value, 'currency_value')))
                                        <tr>
                                            <td class="pe-4 text-start">Contract Value:</td>
                                            <td class="text-start border-bottom-0">{{ currency_formatter(decryptString($contract->currency, 'currency') ,decryptString($contract->currency_value, 'currency_value')) }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="col-xl-6 col-md-12 col-sm-7 col-12">
                            <h5 class="mb-6 fw-bold">Details</h5>
                            <table>
                                <tbody>
                                    <tr>
                                        <td class="pe-4 text-start">Branch:</td>
                                        <td class="text-start border-bottom-0">{{ $branchFirst->LegalName }}</td>
                                    </tr>
                                    <tr>
                                        <td class="pe-4 text-start align-baseline">Parties:</td>
                                        <td class="text-start border-bottom-0">
                                            <div>
                                                @php
    
                                                $partycount = 1;
                                                $currentParties = [];
                                                foreach($contractPartyData as $condata){
                                                $signed = "";
                                                if($condata->signed){
                                                    $signed = '<span class="ms-1 badge badge-center rounded-pill bg-label-success"><i class="icon-base ti ti-signature"></i></span>';
                                                }
                                                echo "<span class='mb-2'>Party ". $partycount ." - ".$condata->Nameoftheentity.$signed."</span><br />";
                                                $partycount++;
                                                if($condata->contract_party_location_id == !null){
                                                $currentParties[] = $condata->contract_party_id;
                                                }else{
                                                $currentParties[] = $condata->contract_party_exe_id;
                                                }
                                                }
                                                @endphp
                                            </div>
                                        </td>
                                    </tr>
                                    @if($accessData[3] ?? 0 == 1)
                                    <tr>
                                        <td class="pe-4 text-start">Attachments</td>
                                        <td class="text-start border-bottom-0">
                                        @if(isset($contract->contract_attachment_filename))                                        
                                            <a role="button" class="btn mt-2 text-nowrap d-inline-flex position-relative" href="{{ url('contracts/external/approval/'.$exId.'?tab=attachment' )}}">
                                                <i class="ti ti-file-invoice ti-md text-primary" id="show-pdf" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="{{$contract->contract_attachment_filename}}"></i>
                                            </a> 
                                        @endif                                      
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @php
                    $doneProgress = 0;
                    $statusLower = strtolower($contract->contract_status);
                    switch ($statusLower) {
                    case 'executed':
                    $doneProgress = 7;
                    break;
                    case 'draft':
                    $doneProgress = 1;
                    break;
                    case 'review':
                    $doneProgress = 2;
                    break;
                    case 'negotiation':
                    $doneProgress = 3;
                    break;
                    case 'approval':
                    $doneProgress = 4;
                    break;
                    case 'approved':
                    $doneProgress = 5;
                    break;
                    case 'signing':
                    $doneProgress = 6;
                    break;
                    }
                    @endphp
                    <div class="my-5 p-5 mx-4">
                        <div class="horizontal timeline {{$contract->contract_status}}">
                            <div class="steps">
                                <div class="step {{ $statusLower == 'draft' ? 'current' : ''}} {{ $doneProgress > 1 ? 'done' : '' }}">
                                    <span><i class="ti ti-file" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Draft"></i> <span class="progress-text">Draft</span></span>
                                </div>
                                <div class="step {{ $statusLower == 'review' ? 'current' : ''}} {{ $doneProgress > 2 ? 'done' : '' }}">
                                    <span><i class="ti ti-pencil" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Review"></i> <span class="progress-text">Review</span></span>
                                </div>
                                <div class="step {{ $statusLower == 'negotiation' ? 'current' : ''}} {{ $doneProgress > 3 ? 'done' : '' }}">
                                    <span><i class="fa-regular fa-message" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Negotiation"></i> <span class="progress-text">Negotiation</span></span>
                                </div>
                                <div class="step {{ $statusLower == 'approval' ? 'current' : ''}} {{ $doneProgress > 4 ? 'done' : '' }}">
                                    <span><i class="fa-solid fa-spinner" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Pending Approval"></i> <span class="progress-text">Pending Approval</span></span>
                                </div>
                            
                                <div class="step {{ $statusLower == 'signing' ? 'current' : ''}} {{ $doneProgress > 6 ? 'done' : '' }}">
                                    <span><i class="fa-solid fa-file-signature" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Signing"></i> <span class="progress-text">Sigining</span></span>
                                </div>
                                <div class="step {{ $doneProgress >= 7 ? 'done' : '' }}">
                                    <span><i class="fa-solid fa-file-contract" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Executed"></i> <span class="progress-text">Executed</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($contract->contract_status == 'executed')
                    @php
                    $statusClass = '';
                    $statusStrin = '';
                    switch (strtolower($contract->substatus)) {
                    case 'active':
                    $statusClass = 'success';
                    $statusStrin = $contract->substatus;
                    break;
                    case 'expired':
                    $statusClass = 'danger';
                    $statusStrin = $contract->substatus;
                    break;
                    case 'pending':
                    $statusClass = 'warning';
                    $statusStrin = $contract->substatus;
                    break;
                    case 'renewed':
                    $statusClass = 'primary';
                    $statusStrin = $contract->substatus;
                    break;
                    case 'terminated':
                    $statusClass = 'info';
                    $statusStrin = $contract->substatus;
                    break;
                    case 'completed':
                    $statusClass = 'secondary';
                    $statusStrin = $contract->substatus;
                    break;
                    case 'amended':
                    $statusClass = 'primary';
                    $statusStrin = $contract->substatus;
                    break;
                    }
                    @endphp
                    <p class="bg-{{$statusClass}} w-100 m-0 mt-4 py-2 text-center text-white fw-bold text-uppercase">STATUS : {{ $contract->substatus }}</p>
                    @else
                    <p class="bg-warning w-100 m-0 mt-4 py-2 text-center text-white fw-bold text-capitalize">Pending : {{ $contract->substatus }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @if( $accessData[3] ?? 0 == 1)
    <div class="col-sm-12">
    
    
        <div class="col-sm-12">
    
    
            <ul class="nav nav-tabs m-0 m0" id="mainTabDetails" role="tablist" style="padding: 5px;">
    
                <li class="nav-item"><a href="{{ url('contracts/external/approval/'.$exId) }}">
                        <button type="button" class="nav-link {{(isset($_GET['tab']) && $_GET['tab'] == 'timeline') ? '': 'active'}}" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                            aria-selected="true">Details</button>
                    </a></li>
            @if (count($approvalsArr) > 0)
                <li class="nav-item" id="timelineFlows"><a href="{{ url('contracts/external/approval/'.$exId.'?tab=timeline' )}}">
                        <button type="button" class="nav-link {{(isset($_GET['tab']) && $_GET['tab'] == 'timeline') ? 'active': ''}}" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                            aria-selected="false">Signing</button>
                    </a></li>
            @endif
            </ul>
    
        </div>
    
    </div>
    
    @if (isset($_GET['tab']) && $_GET['tab'] == 'timeline')

        @if (count($approvalsArr) == 0)
        
        <div class="card">
        
            <p class="mt-4" style="text-align: center;">No record found</p>
        
        </div>
        
        
        @endif 
        <div class="row">
            <div class="contractApprovals col">
                @foreach ($approvalsArr as $key => $approvalsData)
                    @if(json_decode(decryptString($approvalsData[0]->username, 'username'))->email == $emailCheck && $approvalsData[0]->flag == -1)
                        @if($approvalsData[0]->status == 'Signing' && str_contains(strtolower($contract->substatus), 'external'))
                            @include('contract::contract.signExApprovals',  ['approvalValues' => $approvalsData[0], 'lindex' => $loop->index])
                        @endif
                    @endif
                @endforeach        
            </div>
        </div>


@else
    @include('contract::contract.viewExContractDocument')
@endif
    @else
        <h5>Please Contact Support <a href="mailto:{{ env('support_mail') }}">{{ env('support_mail') }}</a> For Further Details</h5>
    @endif
</div>
<script>
    //PreviewSignature Image
    function setSignature(file) {
      var input = file.target;
      var reader = new FileReader();
      reader.onload = function(e){
        let finalSign = e.target.result;
        $('#currentSign').val(finalSign);
        $('#previewSignImg').attr('src', finalSign);
      };
      reader.readAsDataURL(input.files[0]);
    } 
    
    //Signature Drawer
    const canvas = document.getElementById('signatureCanvas');
    if(canvas){
        const ctx = canvas.getContext('2d');
        const clearButton = document.getElementById('clearButton');
        let isDrawing = false;
        
        // Event listeners for mouse events
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);
        
        // Event listeners for touch events
        canvas.addEventListener('touchstart', startDrawing);
        canvas.addEventListener('touchmove', draw);
        canvas.addEventListener('touchend', stopDrawing);
        canvas.addEventListener('touchcancel', stopDrawing);
        
        function startDrawing(e) {
            isDrawing = true;
            ctx.beginPath(); // Start a new path
            const x = e.offsetX || e.touches[0].pageX - canvas.offsetLeft;
            const y = e.offsetY || e.touches[0].pageY - canvas.offsetTop;
            ctx.moveTo(x, y); // Move the pen to the starting point
        }
        
        function draw(e) {
            if (!isDrawing) return;
        
            const x = e.offsetX || e.touches[0].pageX - canvas.offsetLeft;
            const y = e.offsetY || e.touches[0].pageY - canvas.offsetTop;
            ctx.lineTo(x, y); // Draw a line to the current mouse position
            ctx.stroke(); // Apply the line
        }
        
        function stopDrawing() {
            isDrawing = false;
        }
        
        clearButton.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        });
    }     
</script>
@endsection
@section('footer')
@endsection