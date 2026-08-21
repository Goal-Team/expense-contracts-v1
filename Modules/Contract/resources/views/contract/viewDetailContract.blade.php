@extends('layouts/layoutMaster')
@section('title', ' Edit/View Contract')
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
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
'resources/assets/vendor/libs/jquery-sticky/jquery-sticky.js'
])

@endsection
<!-- Page Scripts -->
@section('page-script')

@vite(['resources/assets/js/forms-file-upload.js'])
@vite(['resources/assets/js/form-layouts.js'])

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/jquery-ui.js"></script>
<script type="module" src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/25686/jSignature.min.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contract.js"></script>
<script>
    // Upgrade the "Add Dynamic Approver" dropdowns (pre-approval + main flow) to select2.
    (function () {
        function initDynamicApproverSelect2() {
            if (!(window.jQuery && jQuery.fn && jQuery.fn.select2)) return;
            jQuery('.dynamic-approver-select2').each(function () {
                var $el = jQuery(this);
                if ($el.hasClass('select2-hidden-accessible')) return; // already initialised
                $el.select2({
                    width: '100%',
                    placeholder: $el.data('placeholder') || 'Select Approver',
                    allowClear: true
                });
            });
        }
        if (window.jQuery) {
            jQuery(function () { initDynamicApproverSelect2(); });
            // Re-init when a hidden tab/collapse becomes visible (select2 mis-sizes at width 0).
            jQuery(document).on('shown.bs.tab shown.bs.collapse', function () {
                setTimeout(initDynamicApproverSelect2, 50);
            });
        } else {
            document.addEventListener('DOMContentLoaded', initDynamicApproverSelect2);
        }
    })();
</script>
@endsection

@section('content')
@php
    $addLocationScript = false;
@endphp
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
@php
    // Which past version the Historical tab shows. The History tab links to it as
    // ?tab=historical&history=<history_id>, and the cookie above remembers the last one so the
    // tab stays reachable from the other tabs. The controller swaps the contract for that
    // snapshot; see ContractController::viewContract().
    // Empty means no version is chosen. Then the Historical nav item is not drawn, and the body
    // falls back to the live contract, the same as the Details tab.
    $historicalVersionId = $_GET['history'] ?? request()->cookie('historical') ?? '';
@endphp
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
        width: calc(100% + 3.5em);
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
            width: calc(100% + 6em);
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

@if($contract->storage_type != fileStorageType())
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <span class="alert-icon rounded"><i class="ti ti-files-off ti-md"></i></span>
        <span class="text-dark ms-2">Due to the change in storage to <b>{{ fileStorageType() }}</b> from <b>{{ $contract->storage_type }}</b> , the attachment files are currently inaccessible</span>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<!-- Or if using 'with()' -->
@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif
@if (session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
        {!! session('success') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
   @if(Session::has('message'))
  <p class="alert {{ Session::get('alert-class', 'alert-info') }} alert-dismissible mb-2">{!! Session::get('message') !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </p>
   @endif
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
   <div class="d-flex flex-column justify-content-center">
      <h4 class="mb-1 mt-3">Edit/View Contract</h4>
   </div>
   <div class="d-flex align-content-center flex-wrap gap-3">
      <div class="d-flex gap-3">
         @php
            $isDraftContract = strtolower((string) $contract->contract_status) === 'draft';
            $prefillLegalComment = '';
            $prefillRequestedByName = '';
            $prefillRequestedByEmail = '';
            try {
                $prefillLegalComment = !empty($contract->legal_contact_comment) ? decryptString($contract->legal_contact_comment, 'legal_contact_comment') : '';
            } catch (\Throwable $e) {
                $prefillLegalComment = (string) ($contract->legal_contact_comment ?? '');
            }
            try {
                $prefillRequestedByName = !empty($contract->legal_requested_by_name) ? decryptString($contract->legal_requested_by_name, 'legal_requested_by_name') : '';
            } catch (\Throwable $e) {
                $prefillRequestedByName = (string) ($contract->legal_requested_by_name ?? '');
            }
            try {
                $prefillRequestedByEmail = !empty($contract->legal_requested_by_email) ? decryptString($contract->legal_requested_by_email, 'legal_requested_by_email') : '';
            } catch (\Throwable $e) {
                $prefillRequestedByEmail = (string) ($contract->legal_requested_by_email ?? '');
            }
         @endphp
         @if($isDraftContract)
         <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#contactLegalModal">Contact Group Legal Advisor</button>
         @endif
         <a href="{{url('/contracts/list')}}" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
      </div>
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
                                        <td class="pe-4 text-start">Annual Contract Value:</td>
                                        <td class="text-start border-bottom-0">{{ currency_formatter(decryptString($contract->currency, 'currency') ,decryptString($contract->currency_value, 'currency_value')) }}</td>
                                    </tr>
                                @endif
                        </table>
                    </div>
                    <div class="col-xl-6 col-md-12 col-sm-7 col-12">
                        <h5 class="mb-6 fw-bold">Details</h5>
                        <table>
                            <tbody>
                                <tr>
                                    <td class="pe-4 text-start">Branch:</td>
                                    <td class="text-start border-bottom-0">{{ $branchFirst->LegalName ?? "" }}</td>
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
                                            $sendReMail = "";
                                            if($condata->signed){
                                                $signed = '<span class="ms-1 badge badge-center rounded-pill bg-label-success"><i class="icon-base ti ti-signature"></i></span>';
                                            }
                                            if($condata->mails){
                                                $sendReMail = '<i class="ti ti-md ti-mail-fast text-warning resendEmailExternal cursor-pointer" data-ex-mail="'.$condata->mails['email'].'" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Resend Email"></i>';
                                                //print_r($condata->mails);
                                            }
                                            echo "<span class='mb-2'>Party ". $partycount ." - ".$condata->Nameoftheentity.$signed.$sendReMail."</span><br />";
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
                                <tr>
                                    <td class="pe-4 text-start">Attachments</td>
                                    <td class="text-start border-bottom-0">
                                    <input type="hidden" id="contractLinkId" class="form-control" value="{{$contract->id}}">
                                    @if(isset($contract->contract_attachment_filename))                                        
                                        <a role="button" class="btn mt-2 text-nowrap d-inline-flex position-relative" href="{{ attachmentDummyUrl($contract->contract_attachment, true, $contract->id) }}" target="_blank">
                                            <i class="ti ti-file-invoice ti-md text-primary" id="show-pdf" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="{{$contract->contract_attachment_filename}}"></i>
                                        </a> 
                                    @endif                                      
                                    </td>
                                </tr>
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
                case 'finalization':
                $doneProgress = 4;
                break;
                case 'approval':
                $doneProgress = 5;
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
                            <div class="step {{ $statusLower == 'finalization' ? 'current' : ''}} {{ $doneProgress > 4 ? 'done' : '' }}">
                                <span><i class="fa-solid fa-clipboard-check" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Finalization"></i> <span class="progress-text">Finalization</span></span>
                            </div>
                            <div class="step {{ $statusLower == 'approval' ? 'current' : ''}} {{ $doneProgress > 5 ? 'done' : '' }}">
                                <span><i class="fa-solid fa-spinner" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Pending Approval"></i> <span class="progress-text">Pending Approval</span></span>
                            </div>
                            <div class="step {{ $statusLower == 'signing' ? 'current' : ''}} {{ $doneProgress > 6 ? 'done' : '' }}">
                                <span><i class="fa-solid fa-file-signature" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Signing"></i> <span class="progress-text">Signing</span></span>
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
    <div class="d-flex align-content-center flex-wrap gap-3 d-lg-none">
        <div class="d-flex gap-3">
                @if($isDraftContract)
                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#contactLegalModal">Contact Group Legal Advisor</button>
                @endif
            <a href="{{url('/')}}" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
        </div>
    </div>
</div>
                                                @if($isDraftContract)
                                                <div class="modal fade" id="contactLegalModal" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Contact Group Legal Advisor</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="POST" action="{{ route('contracts.legal.contact', ['id' => $contract->id]) }}">
                                                                @csrf
                                                                <div class="modal-body">
                                                                    @php
                                                                        $defaultLegalAdvisorId = old('legal_advisor_id', $contract->legal_advisor_id ?? '');
                                                                        if (empty($defaultLegalAdvisorId)) {
                                                                            $defaultLegalAdvisorId = optional(($legalAdvisors ?? collect())->first())->id;
                                                                        }
                                                                    @endphp
                                                                    <div class="d-none">
                                                                        <label class="form-label" for="legal_advisor_id_modal">Legal Advisor <span class="text-danger">*</span></label>
                                                                        <select class="form-select" name="legal_advisor_id" id="legal_advisor_id_modal" required>
                                                                            <option value="">-Select Legal Advisor-</option>
                                                                            @foreach (($legalAdvisors ?? collect()) as $advisor)
                                                                                <option value="{{ $advisor->id }}" {{ (string) $defaultLegalAdvisorId === (string) $advisor->id ? 'selected' : '' }}>
                                                                                    {{ $advisor->name }}{{ $advisor->designation ? ' - ' . $advisor->designation : '' }} ({{ $advisor->email_id }})
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label" for="legal_comment_modal">Comment <span class="text-danger">*</span></label>
                                                                        <textarea class="form-control" name="comment" id="legal_comment_modal" rows="5" required>{{ old('comment', $prefillLegalComment) }}</textarea>
                                                                    </div>
                                                                    @if(!empty($prefillRequestedByName) || !empty($prefillRequestedByEmail))
                                                                    <div class="alert alert-info mb-0">
                                                                        Last requested from: {{ $prefillRequestedByName ?: '-' }}{{ !empty($prefillRequestedByEmail) ? ' (' . $prefillRequestedByEmail . ')' : '' }}
                                                                    </div>
                                                                    @endif
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-warning">Send to Legal</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
<div class="col-sm-12">


    <div class="col-sm-12">


        <ul class="nav nav-tabs m-0 m0 {{ $errors->any() ? '' : 'sticky-element1' }}" id="mainTabDetails" role="tablist" style="padding: 5px;">

            @php
                // One place owns the tab rule - contract_detail_current_tab() in app/helpers.php.
                // The controller calls the same helper, so it skips the work this tab never shows.
                $currentTab = contract_detail_current_tab($contract);
            @endphp
            
            @if ($currentTab == 'pre-approval')
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=details') }}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=edit' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item active" id="preApprovalTab"><a href="{{ url('contracts/'.$contract->id.'?tab=pre-approval' )}}">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=history' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">History</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=obligation' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Obligations</button>
                </a></li>                
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=e-stamp' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">E-Stamp</button>
                </a></li>
            @elseif ($currentTab == 'timeline')
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=details') }}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=edit' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item active" id="timelineFlows"><a href="{{ url('contracts/'.$contract->id.'?tab=timeline' )}}">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=history' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">History</button>
                </a></li>
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=obligation' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Obligations</button>
                </a></li>                
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=e-stamp' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">E-Stamp</button>
                </a></li>
            @elseif ($currentTab == 'edit')

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=details' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a>
            </li>
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=edit' )}}">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=timeline' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=history' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">History</button>
                </a></li>
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=obligation' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Obligations</button>
                </a></li>
                
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=e-stamp' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">E-Stamp</button>
                </a></li>
            @elseif ($currentTab == 'flow')

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=details' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a>
            </li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=edit' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=timeline' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=history')}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">History</button>
                </a></li>
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=obligation' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Obligations</button>
                </a></li>
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=e-stamp' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">E-Stamp</button>
                </a></li>
            @elseif ($currentTab == 'history')

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=details' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a>
            </li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=edit' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=timeline' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>

            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=history' )}}">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">History</button>
                </a></li>


            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=obligation' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Obligations</button>
                </a></li>
                
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=e-stamp' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">E-Stamp</button>
                </a></li>

            @elseif ($currentTab == 'historical')

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=details' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a>
            </li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=edit' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=timeline' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=history' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">History</button>
                </a></li>
                
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=obligation' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Obligations</button>
                </a></li>                

            @if ($historicalVersionId !== '')
            <li class="nav-item active "><a href="{{ url('contracts/'.$contract->id.'?tab=historical&history='. $historicalVersionId )}}">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Historical</button>
                </a> <a href="{{ url('contracts/'.$contract->id )}}?clearcokke="> X </a></li>
            @endif

            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=e-stamp' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">E-Stamp</button>
                </a></li>                
                
            @elseif ($currentTab == 'attachment')

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=details' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a>
            </li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=edit' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=timeline' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=history' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">History</button>
                </a></li>
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=obligation' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Obligations</button>
                </a></li>
            <li class="nav-item active ">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Attachment
                        @if (request()->cookie('attachment'))
                            <span id="show-pdf-close" data-href="{{ url('contracts/'.$contract->id )}}" class="text-danger"><i class="ti ti-x ti-xs"></i></span>
                        @endif                    
                    </button></li>
            @elseif ($currentTab == 'obligation')

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=details' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a>
            </li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=edit' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=timeline' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=history' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">History</button>
                </a></li>

            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=obligation' )}}">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Obligations</button>
                </a></li>
                
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=e-stamp' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">E-Stamp</button>
                </a></li>                
                
            @elseif ($currentTab == 'e-stamp')

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=details' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a>
            </li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=edit' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=timeline' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=history' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">History</button>
                </a></li>

            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=obligation' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Obligations</button>
                </a></li>
                
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=e-stamp' )}}">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">E-Stamp</button>
                </a></li>

            @else

            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=details' )}}">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a></li>

            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=edit' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=timeline' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=history' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">History</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=obligation' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Obligations</button>
                </a></li>
                
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=e-stamp' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">E-Stamp</button>
                </a></li>                

            @endif


            @if ($historicalVersionId !== '')

            @if (isset($_GET['tab']) && $_GET['tab'] == 'historical')

            @else
            <li class="nav-item  "><a href="{{ url('contracts/'.$contract->id.'?tab=historical&history='. $historicalVersionId )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Historical</button>
                </a> <a href="{{ url('contracts/'.$contract->id )}}?clearcokke="> X </a></li>
            @endif

            @endif
            <li class="nav-item active d-none" id="currentFlowApprovals">
                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">
                    Send Approval
                </button>
            </li>
        </ul>

    </div>

</div>

@if ($currentTab == 'timeline')
<input type="hidden" id="contractId" class="form-control" value="{{$contract->id}}">


@if (count($approvalsArr) == 0)

<div class="card">

    <p class="mt-4" style="text-align: center;">No record found</p>

</div>


@endif 
<ul class="nav nav-pills flex-column flex-sm-row mb-6 gap-2 gap-lg-0 mt-3 timechartview timelinetabs">
    <li class="nav-item"><a class="nav-link active waves-effect waves-light" data-type="Detail" href="javascript:void(0);"> Detail view</a></li>
    <li class="nav-item"><a class="nav-link waves-effect waves-light" data-type="Chart" href="javascript:void(0);">Chart View</a></li>
</ul>
<div class="row">
    <div class="contractFlow col" style="display: none;">
        @include('contract::contract.contractFlow')
    </div>

    <div class="contractApprovals col">
        @php
            $appDataAppRules = json_decode(trim($contract->rules_id));
            $approvalTypeContract = $appDataAppRules[0]->approval_type ?? '';
        @endphp
        @foreach ($approvalsArr as $key => $approvalsData)
            @if($loop->last)
                @if($approvalsData[0]->status == 'Signing' && strtolower($contract->substatus) == 'approved')
                    @include('contract::contract.signApprovals',  ['approvalValues' => $approvalsData[0], 'lindex' => $loop->index])
                    @php
                        $addLocationScript = true;
                    @endphp                    
                @else
                    @php
                        $showParallelBlade = true;
                        if(($approvalsData[0]->status == 'Signing' && strtolower($contract->substatus) == 'approved') || $approvalTypeContract != 'parallel'){
                            $showParallelBlade = false;
                        }
                        if($approvalTypeContract == 'parallel' && !$showParallelBlade){
                            $showParallelBlade = true;
                        }
                    @endphp                    
                    {{--
                    @if(!$showParallelBlade)
                        @include('contract::contract.contractApprovalsView')
                    @else
                        @include('contract::contract.contractApprovalsViewParallel')
                    @endif
                    --}}
                    
                    @include('contract::contract.approvalFlow') 
                    
                @endif
            @break
            @endif
        @endforeach        
    </div>
</div>

@elseif ($currentTab == 'pre-approval')
<ul class="nav nav-pills flex-column flex-sm-row mb-6 gap-2 gap-lg-0 mt-3 timechartview timelinetabs">
    <li class="nav-item"><a class="nav-link active waves-effect waves-light" data-type="Detail" href="javascript:void(0);"> Detail view</a></li>
    <li class="nav-item"><a class="nav-link waves-effect waves-light" data-type="Chart" href="javascript:void(0);">Chart View</a></li>
</ul>
<div class="row">
    <div class="contractFlow col" style="display: none;">
        @include('contract::contract.contractFlow')
    </div>

    <div class="contractApprovals col">
        @include('contract::contract.preApprovalFlow')
    </div>
</div>

@elseif ($currentTab == 'timelineedit')
<input type="hidden" id="contractId" class="form-control" value="{{$contract->id}}">


@if (count($approvalsArr) == 0)

<div class="card">

    <p class="mt-4" style="text-align: center;">No record found</p>

</div>


@endif 
<ul class="nav nav-pills flex-column flex-sm-row mb-6 gap-2 gap-lg-0 mt-3 timechartview">
    <li class="nav-item"><a class="nav-link active waves-effect waves-light" data-type="Detail" href="javascript:void(0);"> Detail view</a></li>
    <li class="nav-item"><a class="nav-link waves-effect waves-light" data-type="Chart" href="javascript:void(0);">Chart View</a></li>
</ul>
<div class="row">
    <div class="contractFlow col" style="display: none;">
        @include('contract::contract.contractFlow')
    </div>

    <div class="contractApprovals col">
        @include('contract::contract.contractApprovals')
    </div>
</div>

@elseif ($currentTab == 'history')
<div class="card-body mt-3">
    <hr class="mt-1" />
    <div class="row g-3">
        @php
        $contrac_hist1 = [];
        foreach ($approvalsHistory as $app) {
            $app->updated_at = $app->updated_on;
            $contrac_hist1[] = $app;
        }

        $contrac_hist2 = [];
        foreach ($contractHistory as $app) {
            $contrac_hist2[] = $app;
        }
        
        $totalContracts = count($contrac_hist2);
        
        // Merge collections and sort by start time
        $merged = array_merge($contrac_hist1, $contrac_hist2);
        usort($merged, fn($a, $b) => strtotime($a['updated_at']) - strtotime($b['updated_at']));
        $merged = array_reverse($merged);
        $isContract = 0;
        
        @endphp
        <ul class="timeline mb-0">
            @foreach($merged as $key => $history)
                {{--@if($key >= 1)--}}
                    @if($history->contract_mode)
                    @php
                        $isContract++;
                    @endphp
                    <li class="timeline-item timeline-item-transparent mb-3 {{$key}}">
                        <span class="timeline-point timeline-point-info"></span>
                        <div class="card">
                            <div class="card-body mt-0">
                                <div class="timeline-event">
                                    <div class="timeline-header mb-3">
                                        <h6 class="mb-0">{{ $totalContracts == $isContract ? 'Created' : 'Updated' }} Date {{ date('d-M-Y h:i:s A', strtotime($history->updated_at)) }}</h6>
                                    </div>
                                    <p>
                                        {{ $totalContracts == $isContract ? 'Created' : 'Modified' }} By
                                        {{ $history->user_name->Salutation ?? '' }}
                                        {{ $history->user_name->FirstName ?? '' }}
                                        {{ $history->user_name->LastName ?? '' }}
                                    </p>
                                    <p class="mb-2 col-6">
                                        <a href="{{ url('contracts/' . $contract->id . '?tab=historical') }}&history={{ $history->history_id }}">
                                            <i class="fa fa-eye editView" style="cursor: pointer;"></i> View
                                        </a>
                                        &nbsp;
                                        <button type="button" class="btn btn-sm btn-outline-secondary compare-history-btn" data-history-id="{{ $history->id }}" data-contract-id="{{ $contract->id }}">Compare</button>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </li>
                    @else
                        @php
                        $attachments = json_decode($history->attachments);
                        @endphp
                        @if($history->approval_status == 'approved' || $history->approval_status == 'rejected')
                        <li class="timeline-item timeline-item-transparent mb-3">
                            <span class="timeline-point timeline-point-info"></span>
                            <div class="card">
                                <div class="card-body mt-0">
                                    <div class="timeline-event">
                                        <div class="timeline-header mb-3">
                                            <div class="mt-3">
                                                @php
                                                $status = $history->approval_status;
                                                $updateText = "In Active User";
                                                //if($history->approval_status == 'rejected') {
                                                    //$status = 'Sent for Under Revision';
                                                //}else{
                                                    $status = strtolower(ucfirst(decryptString($history->next_status, 'next_status')));
                                                    
                                                    if($history->button_text != null){
                                                        $status = $history->button_text;
                                                        if(strpos(strtolower($status) ,'on') !== false){
                                                        
                                                            $wholeBtnTextArr = explode('on',strtolower($status));
                                                            
                                                            $text = strtolower(trim($wholeBtnTextArr[0]) ?? 'Invalid');

                                                            if(strtolower($text) == 'approval'){
                                                                $text = 'Approved';
                                                            } 
                                                            
                                                            
                                                            if(strtolower($text) == 'signed' && strtolower($history->next_status) == 'signing'){
                                                                $text = 'Send For Signing';
                                                            }

                                                            if(strtolower($text) == 'approved' && strtolower($history->status) == 'signing' && strtolower($history->next_status) == ''){
                                                                $text = 'Signed On';
                                                            }                                                            
                                                            
                                                            $status = strtolower($text);
                                                        }
                                                        
                                                        if(strtolower($status) == 'external'){
                                                            $status = 'Signed On';
                                                        }                                                         
                                                    }
                                                    
                                                    if($history->updated_by){
                                                        $updateText1 = "[timelinebehalfhistory]";
                                                        if(json_decode($history->username)->email != json_decode($history->updated_by)->email){
                                                            $updateText1 = json_decode($history->updated_by)->name ." (". json_decode($history->updated_by)->email .") On Behalf of [[timelinebehalfhistory]]"; 
                                                        }
                                                        $updateText = str_replace('[timelinebehalfhistory]', "<b>".json_decode($history->username)->name ." (". json_decode($history->username)->email .")</b>", $updateText1) . " On " . (($history->updated_on != null ) ? \Carbon\Carbon::parse($history->updated_on)->format('d-M-Y h:i:s A') : '-');
                                                    }
                                                   
                                                //}                                                
                                                @endphp
                                                <span class="badge {{ 
                                                                strpos($status ,'signed') !== false ? 'bg-success' : 
                                                                (strpos($status ,'review') !== false ? 'bg-info' : 
                                                                ($status === 'rejected' ? 'bg-danger' : 'bg-primary')) }} text-capitalize fs-6"
                                                    data-status-attr="{{ "curr-".decryptString($history->approval_status,'approval_status')."-pre-".decryptString($history->previous_status,'previous_status')."-next-".decryptString($history->next_status, 'next_status') }}">
                                                    {{ (!empty($status) ? $status : 'Initiated') }}
                                                </span>
            
                                                <p class="mt-2">
                                                    By {!! $updateText !!}
                                                </p>
            
            
                                                @if(!empty($history->next_action_item))
                                                <h6>Short Description </h6>
                                                <p>{{ $history->next_action_item }}</p>
                                                @endif
            
                                                @if(!empty($history->next_action_description))
                                                <h6>Description</h6>
                                                <p>{{ $history->next_action_description }}</p>
                                                @endif
                                                @if(isset($attachments))
                                                <p>
                                                    @foreach ($attachments as $file)
            
                                                    <a href="{{ attachmentDummyUrl($file->path, true) }}" target="_blank">{{ $file->name }}</a><br>
                                                    @endforeach
                                                </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @endif
        
                    @endif
            @endforeach
        </ul>




    </div>
</div>



@elseif (isset($_GET['tab']) && $_GET['tab'] == 'flow')

@include('contract::contract.contractFlow')

@elseif (isset($_GET['tab']) && $_GET['tab'] == 'edit')

@include('contract::contract.viewDetailContractEdit')

@elseif (isset($_GET['tab']) && $_GET['tab'] == 'attachment')

@include('contract::contract.viewContractDocument')

@elseif (isset($_GET['tab']) && $_GET['tab'] == 'e-stamp')

@include('contract::contract.viewEstampProcess')

@elseif (isset($_GET['tab']) && $_GET['tab'] == 'obligation')

@include('contract::contract.contractObligation', ['paryda', $contractPartys , 'ContractObligations' , $ContractObligations])

{{-- The Details tab. It is the only tab that renders the Related Contracts region below, so the
     controller reads the same helper and skips the three scans that fill it on every other tab. --}}
@elseif (contract_detail_shows_related_contracts($currentTab))

<div class="row my-4">
    <div class="col">
        
            <div class="col-md mb-4 mb-md-2">
                <div class="accordion mt-3" id="accordionWithIcon">
                    <div class="card accordion-item active">
                        <div class="card-header">
                            <label class="form-check-label">Contract</label>
                            <div class="col mt-2">

                                <div class="form-check form-check-inline">
                                    <label class="form-check-label">
                                        <input type="radio" class="contractmode form-check-input" name="contractMode" disabled value="new" {{ decryptString($contract->contract_mode, 'contract_mode')  == 'new' ? 'checked' : '' }}>
                                        New</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <label class="form-check-label">
                                        <input type="radio" class="contractmode form-check-input" name="contractMode" disabled value="old" {{ decryptString($contract->contract_mode, 'contract_mode') == 'old' ? 'checked' : ''}}>
                                        Legacy Contracts/Executed Contracts </label>
                                </div>
                                <div class="btn-group float-end {{ $contract->contract_status == 'executed' ? '' : 'd-none' }}">
                                    <button type="button" class="btn btn-warning waves-effect waves-light">More Actions</button>
                                    <button type="button" class="btn btn-warning dropdown-toggle dropdown-toggle-split waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="visually-hidden">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        @if(decryptString($contract->end_contract_type, 'end_contract_type') == 'fixedTerm' && $contract->contract_status == 'executed')
                                        <li>
                                            <a href="{{ url('contracts/renew/'.$contract->id) }}" class="dropdown-item waves-effect">
                                                <span class="ti-xs ti ti-receipt-refund me-2 text-warning"></span>Initiate Renewal/Addendum
                                            </a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        @endif
                                        @if($contract->contract_status == 'executed')
                                        <li>
                                            <a href="{{url('contracts/terminate/'.$contract->id)}}" class="dropdown-item waves-effect">
                                                <span class="ti-xs ti ti-square-rounded-x me-2 text-info"></span>Terminate
                                            </a>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <h2 class="accordion-header d-flex align-items-center">
                            <button type="button" class="accordion-button" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-1" aria-expanded="true">
                                Basic Contract Information
                            </button>
                        </h2>
                        <div id="accordionWithIcon-1" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <hr class="mt-1" />
                                <div class="row g-3">

                                    <div class="col-md-6">

                                        <h6 class="mb-1">Contract Type</h6>
                                        <input type="hidden" id="contracttype" value="{{$contract->contract_type_id}}" />
                                        @foreach ($contractTypes as $contractType)
                                        @if($contract->contract_type == decryptString($contractType->contract_type,'contract_type'))
                                        {{ decryptString($contractType->contract_type,'contract_type') }}
                                        @endif
                                        @endforeach

                                    </div>

                                    <div class="col-md-6">
                                        <h6 class="mb-1">Department</h6>
                                        @foreach ($ent as $en)
                                        @if($contract->department_id == $en->name)
                                        {{$en->name}}
                                        @endif
                                        @endforeach


                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1">Category</h6>
                                        @foreach ($catego as $en)
                                        @if($contract->catgoery_id == $en->name)
                                        {{$en->name}}
                                        @endif
                                        @endforeach

                                    </div>


                                    <div class="col-md-6">
                                        <h6 class="mb-1">Exclusivity</h6>
                                        @if(decryptString($contract->exclusivity, 'exclusivity' ) == 'Exclusivity to Company')
                                        <p>Exclusive to Company</p>
                                        @elseif(decryptString($contract->exclusivity, 'exclusivity' ) == 'Exclusive to Contracting Party')
                                        <p>Exclusive to Contracting Party</p>
                                        @elseif(decryptString($contract->exclusivity, 'exclusivity') == 'Mutually Exclusive')
                                        <p>Mutually Exclusive</p>
                                        @elseif(decryptString($contract->exclusivity, 'exclusivity' ) == 'Non Exclusive')
                                        <p>Non Exclusive</p>
                                        @endif


                                    </div>
                                    <div class="col-md-6">

                                        <h6 class="mb-1">Contract Description</h6>


                                        @if(decryptString($contract->contract_description,'contract_description' ) != "")
                                        {{ decryptString($contract->contract_description,'contract_description' )}}
                                        @else
                                        <p>Not Available</p>
                                        @endif

                                    </div>
                                    <div class="col-md-6">

                                        <h6 class="mb-1">Other Scopes</h6>
                                        @php
                                        @endphp
                                        @foreach ($contractTypes as $contractType)
                                        @if(in_array($contractType->contract_type_id, json_decode($contract->contract_tags) ?? []))
                                        @php
                                        $tagsAdded= decryptString($contractType->contract_type,'contract_type');
                                        echo "<span class='badge badge-sm bg-secondary'>$tagsAdded</span>";
                                        @endphp                                        
                                        @endif
                                        @endforeach
                                        
                                        

                                    </div>
                                    @if(env('enable_contract_priority'))
                                    <div class="col-md-6">
                                        <h6 class="mb-1">Priority</h6>
                                        @php
                                        $priorityArr = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];
                                        @endphp
                                        
                                        {{$priorityArr[$contract->contract_priority] ?? ''}}
                                    </div>                                    
                                    @endif
                                    <div class="row mb-3">

                                        @include('contract::contract.viewDetailCustomField', ['categoryId' => 1])

                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>




                    <div class="accordion-item card mt-4">
                        <h2 class="accordion-header d-flex align-items-center">
                            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-2" aria-expanded="false">
                                Party Details
                            </button>
                        </h2>
                        <div id="accordionWithIcon-2" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <hr class="mt-1" />
                                <div class="row g-3">

                                    @include('contract::contract.partyDetailsView', ['paryda', $contractPartys])

                                </div>
                                <div class="row add_users" style="margin-top: 30px;">
                                    <input type="hidden" id="user_position" value="1" />
                                    <div class="col-md-6">
                                        <div class="row" style="" id="">

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item card mt-4">
                        <h2 class="accordion-header d-flex align-items-center">
                            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-3" aria-expanded="false">
                                Contract Duration
                            </button>
                        </h2>
                        <div id="accordionWithIcon-3" class="accordion-collapse collapse">
                            <div class="accordion-body">



                                <div class="">
                                    <div class="col-sm-12">
                                        <div class="form-group mt-3">
                                            <h5>Contract Commencement</h5>
                                            <hr class="mt-0" />
                                            <label>Effective date:</label>
                                            <div class="clearfix mt-2">
                                                <label class="form-check-inline form-check">
                                                    <input type="radio" class="form-check-input commencementDate" name="Duration[commencementDate]" disabled value="FixedDate" {{ decryptString($contract->commencement_type, 'commencement_type') == 'FixedDate' ? 'checked' : '' }}>
                                                    Fixed Date
                                                </label>
                                                <!-- <label class="form-check-inline form-check">
                                                    <input class="form-check-input commencementDate" type="radio" name="Duration[commencementDate]" disabled value="Eventbased" {{ decryptString($contract->commencement_type, 'commencement_type') == 'Eventbased' ? 'checked' : ''  }}>
                                                    Event based commencement
                                                </label> -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12" id="FixedDate">
                                        <h6 class="mt-3 mb-1">Fixed Date</h6>

                                        @if($contract->fixed_date != "")
                                        {{ date('d-m-Y',strtotime($contract->fixed_date)) }}
                                        @else
                                        <p>Not Available</p>
                                        @endif

                                    </div>
                                    <!-- <div class="col-sm-12" id="Eventbased" style="display: none;">
                                        <div class="form-group row mt-3">
                                            <div class="col-sm-12">
                                                <label> Event based commencement</label>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group mt-2">
                                                    <label>(i) Event Condition</label>
                                                    <div class="clearfix">
                                                        <select class="form-control" name="Duration[eventCondition]">
                                                            <option value="uponCompletion">Upon Completion of Specify Event</option>
                                                            <option value="uponDelivery">Upon Delivery of Specify Deliverable</option>
                                                            <option value="uponApproval">Upon Approval of Specify Approval Process</option>
                                                            <option value="other">Other</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group mt-2">
                                                    <label>(ii) Name of event</label>
                                                    <div class="clearfix">
                                                        <textarea class="form-control" name="Duration[nameofevent]"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group mt-2">
                                                    <label>(iii) Event Details</label>
                                                    <div class="clearfix">
                                                        <textarea class="form-control" name="Duration[eventDetails]"></textarea>
                                                        <small class="form-text text-muted">If "Event-Based Commencement" is selected, this field allows the user to provide additional details or specifics about the event triggering the commencement of the contract.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group mt-2">
                                                    <label>(iv) Event deadline</label>
                                                    <div class="clearfix">
                                                        <input type="date" class="form-control flatpickr" name="Duration[eventDeadline]" />
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div> -->


                                </div>
                                <div class="">
                                    <div class="col-sm-12">
                                        <div class="form-group mt-4">
                                            <hr class="mt-0" />
                                            <h5>End of Contract Term</h5>
                                            <hr class="mt-0" />
                                            <label>Effective date:</label>
                                            <div class="clearfix mt-2">
                                                <label class="form-check-inline form-check"><input type="radio" class="contractCommencementEffectiveDate form-check-input" name="Duration[effectiveDate]" disabled value="onetimeContract" {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'onetimeContract' ? 'checked' : '' }}>One time Contract</label>
                                                <label class="form-check-inline form-check"><input class="contractCommencementEffectiveDate form-check-input" type="radio" name="Duration[effectiveDate]" disabled value="fixedTerm" {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'fixedTerm' ? 'checked' : '' }}>Fixed Term Contract with Periodic Renewal</label>
                                                <label class="form-check-inline form-check"><input class="contractCommencementEffectiveDate form-check-input" type="radio" name="Duration[effectiveDate]" disabled value="evergreen" {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'evergreen' ? 'checked' : '' }}>Evergreen/Perpetual Contracts </label>
                                                <label class="form-check-inline form-check showinedit"> <input class="contractCommencementEffectiveDate form-check-input" type="radio" name="Duration[effectiveDate]" disabled value="termination" {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'termination' ? 'checked' : '' }}>Termination</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-4" id="onetimeContract" style="display: {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'onetimeContract' ? '' : 'none' }}">
                                        <div class="form-group mt-3">
                                            <hr class="mt-0" />
                                            <h5>One time Contract</h5>
                                            <hr class="mt-0" />
                                            <div class="form-group">
                                                <h6 class="mb-2">End date of contract</h6>

                                                @if($contract->contract_end_date != "")

                                                {{ date('d-m-Y', strtotime($contract->contract_end_date)) }}
                                                @else
                                                <p>Not Available</p>
                                                @endif

                                            </div>
                                        </div>
                                    </div>


                                    <div class="col-sm-12 mt-2" id="fixedTerm" style="display: {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'fixedTerm' ? '' : 'none' }};">
                                        <hr class="mt-3" />
                                        <h5 class="mt-2">Fixed Term Contract with Periodic Renewal</h5>

                                        <hr class="mt-3" />

                                        <div class="form-group row mt-2">
                                            <div class="row">
                                                <div class="form-group  col-sm-3 mt-2">

                                                    <h6 class="mb-2">End date of contract</h6>

                                                    @if($contract->contract_end_date != "")
    
                                                    {{ date('d-m-Y', strtotime($contract->contract_end_date)) }}
                                                    @else
                                                    <p>Not Available</p>
                                                    @endif

                                                </div>

                                                <div class="form-group  col-sm-5 mt-2">
                                                    <h6 class="mb-2">Type of Renewal</h6>
                                                    
                                                    {{ decryptString($contract->renewal_type , 'renewal_type') == 'automaticrenewal' ? 'Auto' : 'Manual' }} renewal with notice
                                                </div>
                                                @if(decryptString($contract->renewal_type , 'renewal_type') == 'automaticrenewal')
                                                <div class="form-group  col-sm-4 mt-2">
                                                    <h6 class="mb-2">Period of auto renewal</h6>
                                                    {{$contract->period_auto_renewal}}
                                                    {{ decryptString($contract->period_auto_renewal_unit, 'period_auto_renewal_unit') }}
                                                </div>
                                                @endif
                                            </div>
                                            <div class="row mt-2">
                                                <div class="form-group  col-sm-3 mt-2">
                                                    <h6 class="mb-2">{{ decryptString($contract->renewal_type , 'renewal_type') == 'automaticrenewal' ? 'Auto' : 'Manual' }} renewal Date:</h6>
                                                    
                                                    @if($contract->contract_eauto_renewal_datend_date != "")
    
                                                    {{ date('d-m-Y', strtotime($contract->auto_renewal_date)) }}
                                                    @else
                                                    <p>Not Available</p>
                                                    @endif                                                    
                                                </div>
                                            </div>

                                        </div>
                                    </div>



                                    <div class="col-sm-6" style="display: none;" id="evergreen">
                                        <hr class="mt-3" />
                                        <div class="form-group mt-3">
                                            <h5>Evergreen Contracts</h5>
                                            <hr class="mt-3" />
                                            <div class="form-group">

                                                <h6 class="mb-2">Condition for end of contract::</h6>
                                                {{ decryptString($contract->evergreen_condition, 'evergreen_condition') }}

                                                <div class="clearfix">
                                                    <input type="text" style="display: none;" id="conditionEndContractOthers" class="form-control" disabled name="Duration[conditionEndContractOthers]">
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="col-sm-12" id="termination">
                                        <h4>Termination</h4>

                                        <div class="clearfix row">
                                            <div class="form-group col-sm-3">
                                                <div class="form-group">
                                                    <label>Date</label>
                                                    <div class="clearfix">
                                                        <input type="date" class="form-control" name="Duration[terminationDate]" disabled />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <div class="form-group  col-sm-6">
                                                    <label>Reason for termination</label>
                                                    <div class="clearfix">
                                                        <select class="form-control" name="Duration[reasonTermination]" disabled>
                                                            <option value="mutually">When mutually agreed to end</option>
                                                            <option value="termination">When Termination Clause is triggered</option>
                                                            <option value="dispute">Dispute</option>
                                                            <option value="nonRenewal">Non renewal</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="clearfix mt-4">
                                    <hr>
                                    <div class="clearfix mb-4">
                                        <label for="Reminder"> Enable Reminder</label>
                                        <input type="checkbox" class="form-check-input " id="Reminder" name="Duration[reminderEnable]" disabled {{ decryptString($contract->reminder_enable, 'reminder_enable') == 'on' ? 'checked' : '' }}/>
                                    </div>
                                    <div class="nav-align-top nav-tabs-shadow mb-4">

                                        <div class="col-sm-12">
                                            <ul class="nav nav-tabs m-0 m0" role="tablist">
                                                <li class="nav-item">
                                                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-home" aria-controls="navs-top-home" aria-selected="true">Fist level</button>
                                                </li>
                                                <li class="nav-item">
                                                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-profile" aria-controls="navs-top-profile" aria-selected="false">Second Level</button>
                                                </li>
                                                <li class="nav-item">
                                                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-messages" aria-controls="navs-top-messages" aria-selected="false">Escalation Prior</button>
                                                </li>
                                                <li class="nav-item">
                                                  <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-escalation-after" aria-controls="navs-escalation-after" aria-selected="false">Escalation After</button>
                                                </li>                                                 
                                            </ul>

                                        </div>
                                        <!-- alert me on div comes -->


                                        <div class="tab-content">
                                            <div class="tab-pane fade show active" id="navs-top-home" role="tabpanel">
                                                <div class="row">
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <h6 class="mb-2">Alert Me about</h6>
                                                            {{ decryptString( $contract->reminder_first_alert,'reminder_first_alert' )}}

                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group row">
                                                            <h6 class="mb-2">Alert Me on</h6>

                                                            <?php $fristarl  = explode(" ", decryptString($contract->reminder_first_alertMeOn, 'reminder_first_alertMeOn')); ?>
                                                            <div class="col">
                                                                {{ $fristarl[0]}}

                                                            </div>
                                                            <div class="col">
                                                                @if($fristarl[1] == 'days')
                                                                <p>Days</p>
                                                                @elseif($fristarl[1] == 'months')
                                                                <p>Months</p>
                                                                @elseif($fristarl[1] == 'years')
                                                                <p>Years</p>
                                                                @endif

                                                            </div>
                                                            <div class="col">
                                                                @if($fristarl[2] == 'prior')
                                                                <p>Prior</p>
                                                                @elseif($fristarl[2] == 'after')
                                                                <p>After</p>
                                                                @endif

                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <h6 class="mb-2">Repeats</h6>
                                                            {{ decryptString($contract->reminder_first_alert_repeats, 'reminder_first_alert_repeats')}}

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="navs-top-profile" role="tabpanel">
                                                <div class="row">
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <h6 class="mb-2">Alert Me about</h6>
                                                            {{ decryptString($contract->reminder_second_alert,'reminder_second_alert' )}}

                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">

                                                        <div class="form-group row">

                                                            <h6 class="mb-2">Alert Me on</h6>
                                                            <?php $secondarl  = explode(" ", decryptString($contract->reminder_second_alertMeOn, 'reminder_second_alertMeOn')); ?>
                                                            <div class="col">
                                                                {{ $secondarl[0]}}

                                                            </div>
                                                            <div class="col">

                                                                @if($secondarl[1] == 'days')
                                                                <p>Days</p>
                                                                @elseif($secondarl[1] == 'months')
                                                                <p>Months</p>
                                                                @elseif($secondarl[1] == 'years')
                                                                <p>Years</p>
                                                                @endif

                                                            </div>
                                                            <div class="col">
                                                                @if($secondarl[2] == 'prior')
                                                                <p>Prior</p>
                                                                @elseif($secondarl[2] == 'after')
                                                                <p>After</p>
                                                                @endif

                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group">

                                                            <h6 class="mb-2">Repeats</h6>
                                                            {{ decryptString($contract->reminder_second_alert_repeats, 'reminder_second_alert_repeats') }}

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="navs-top-messages" role="tabpanel">
                                                <div class="row">
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <h6 class="mb-2">Alert Me about</h6>
                                                            {{ decryptString($contract->reminder_escalation_alert, 'reminder_escalation_alert') }}

                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group row">

                                                            <h6 class="mb-2">Alert Me on</h6>
                                                            <?php $escalationarl  = explode(" ", decryptString($contract->reminder_escalation_alertMeOn, 'reminder_escalation_alertMeOn')); ?>
                                                            <div class="col">
                                                                {{ $escalationarl[0]}}
                                                            </div>
                                                            <div class="col">

                                                                @if($escalationarl[1] == 'days')
                                                                <p>Days</p>
                                                                @elseif($escalationarl[1] == 'months')
                                                                <p>Months</p>
                                                                @elseif($escalationarl[1] == 'years')
                                                                <p>Years</p>
                                                                @endif

                                                            </div>
                                                            <div class="col">
                                                                @if($escalationarl[2] == 'prior')
                                                                <p>Prior</p>
                                                                @elseif($escalationarl[2] == 'after')
                                                                <p>After</p>
                                                                @endif

                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group">

                                                            <h6 class="mb-2">Repeats</h6>
                                                            {{ decryptString($contract->reminder_escalation_alert_repeats, 'reminder_escalation_alert_repeats')}}

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="navs-escalation-after" role="tabpanel">
                                                <div class="row">
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <h6 class="mb-2">Alert Me about</h6>
                                                            {{ decryptString($contract->reminder_escalation_alert_after, 'reminder_escalation_alert_after') }}

                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group row">

                                                            <h6 class="mb-2">Alert Me on</h6>
                                                            <?php $escalationarl  = explode(" ", decryptString($contract->reminder_escalation_alertMeOn_after, 'reminder_escalation_alertMeOn_after')); ?>
                                                            <div class="col">
                                                                {{ $escalationarl[0] ?? 'Not Available'}}
                                                            </div>
                                                            <div class="col">

                                                                @if($escalationarl[1] ?? 'Not Available' == 'days')
                                                                <p>Days</p>
                                                                @elseif($escalationarl[1] ?? 'Not Available' == 'months')
                                                                <p>Months</p>
                                                                @elseif($escalationarl[1] ?? 'Not Available' == 'years')
                                                                <p>Years</p>
                                                                @endif

                                                            </div>
                                                            <div class="col">
                                                                @if($escalationarl[2] ?? 'Not Available' == 'prior')
                                                                <p>Prior</p>
                                                                @elseif($escalationarl[2] ?? 'Not Available' == 'after')
                                                                <p>After</p>
                                                                @endif

                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group">

                                                            <h6 class="mb-2">Repeats</h6>
                                                            {{ decryptString($contract->reminder_escalation_alert_repeats_after, 'reminder_escalation_alert_repeats_after')}}

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">

                                        @include('contract::contract.viewDetailCustomField', ['categoryId' => 2])

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="accordion-item card mt-4">
                        <h2 class="accordion-header d-flex align-items-center">
                            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-4" aria-expanded="false">
                                Currency
                            </button>
                        </h2>
                        <div id="accordionWithIcon-4" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <hr class="mt-0" />
                                <div class="row g-3">

                                    <div class="card-body mt-2">


                                        <div class="row mb-3">
                                            <div class="col-md-2">
                                                <h6 class="mb-2">billing currency<i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="The total monetary value of the contract."></i></h6>
                                                @if(decryptString($contract->currency, 'currency') != "")
                                                {{ decryptString($contract->currency, 'currency') }}

                                                @else
                                                <p>Not Available</p>
                                                @endif

                                            </div>
                                            <div class="col-md-4">
                                                <h6 class="mb-2">Billing Frequency <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Frequency at which invoices are issued (e.g., Weekly, Monthly, Quarterly, Annually, Onetime)."></i></h6>
                                                @if(decryptString($contract->billing_frequency, 'billing_frequency') != "")
                                                {{ decryptString($contract->billing_frequency, 'billing_frequency') }}
                                                @else
                                                <p>Not Available</p>
                                                @endif
                                            </div>                                            
                                            <div class="col-md-4"><h6 for="ContractValue">Billing Value</h6>

                                                <h6 class="mb-2"></h6>

                                                @if(decryptString($contract->billing_value, 'billing_value') != "")
                                                {{ decryptString($contract->billing_value, 'billing_value') }}(<span class="text-secondary">{{ currency_formatter(decryptString($contract->currency, 'currency') ,decryptString($contract->billing_value, 'billing_value')) }}</span>)

                                                @else
                                                <p>Not Available</p>
                                                @endif

                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-5"><h6 class="form-label" for="ContractValue">Annual Contract Value</h6>

                                                <h6 class="mb-2"></h6>

                                                @if(decryptString($contract->currency_value, 'currency_value') != "")
                                                {{ decryptString($contract->currency_value, 'currency_value') }}(<span class="text-secondary">{{ currency_formatter(decryptString($contract->currency, 'currency') ,decryptString($contract->currency_value, 'currency_value')) }}</span>)

                                                @else
                                                <p>Not Available</p>
                                                @endif

                                            </div>
                                            @if(decryptString($contract->end_contract_type, 'end_contract_type') != 'evergreen')
                                                <div class="col-md-5"><h6 class="form-label" for="ContractValue">Total Contract Value</h6>
    
                                                    <h6 class="mb-2"></h6>
    
                                                    @if(decryptString($contract->total_value, 'total_value') != "")
                                                    {{ decryptString($contract->total_value, 'total_value') }}(<span class="text-secondary">{{ currency_formatter(decryptString($contract->currency, 'currency') ,decryptString($contract->total_value, 'total_value')) }}</span>)
    
                                                    @else
                                                    <p>Not Available</p>
                                                    @endif
    
                                                </div>
                                            @endif
                                        </div>
                                        <hr class="mt-3" />
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <h6 class="mb-2">Payment Schedule <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Details of payment milestones, amounts, and due dates."></i></h6>
                                                @if(decryptString($contract->payment_schedule, 'payment_schedule') != "")
                                                {{ decryptString($contract->payment_schedule, 'payment_schedule') }}
                                                @else
                                                <p>Not Available</p>
                                                @endif

                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="mb-2">Payment Terms <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Terms and conditions governing payments, including payment methods any late payment
                                                    penalties."></i></h6>
                                                @if(decryptString($contract->payment_terms, 'payment_terms') != "")
                                                {{ decryptString($contract->payment_terms, 'payment_terms') }}
                                                @else
                                                <p>Not Available</p>

                                                @endif

                                            </div>
                                        </div>
                                        <hr class="mt-3" />
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <h6 class="mb-2">Taxes and Fees <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Any applicable taxes, fees, or surcharges associated with the contract."></i></h6>
                                                @if(decryptString($contract->taxes, 'taxes') != "")
                                                {{ decryptString($contract->taxes, 'taxes') }}
                                                @else
                                                <p>Not Available</p>
                                                @endif
                                            </div>
                                        </div>
                                        <hr class="mt-3" />
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <h6 class="mb-2">Escalation Clauses <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Provisions for adjusting contract prices over time based on predetermined factors such as
                                                    inflation or market fluctuations."></i></h6>

                                                @if(decryptString($contract->escalation_clauses,'escalation_clauses') != "")
                                                {{ decryptString($contract->escalation_clauses,'escalation_clauses') }}
                                                @else
                                                <p>Not Available</p>
                                                @endif
                                            </div>
                                            <div class="col-md-4">
                                                <h6 class="mb-2">Discounts or Rebates <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Any discounts or rebates applied to the contract."></i></h6>

                                                @if(decryptString($contract->discounts, 'discounts') != "")
                                                {{ decryptString($contract->discounts, 'discounts') }}
                                                @else
                                                <p>Not Available</p>
                                                @endif
                                            </div>
                                        </div>
                                        <hr class="mt-3" />
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <h6 class="mb-2">Retention or Holdbacks <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Amounts withheld from payments as retention or holdbacks pending completion of certain
                                                    milestones or obligations."></i></h6>

                                                @if(decryptString($contract->retention, 'retention' ) != "")
                                                {{ decryptString($contract->retention, 'retention' )}}
                                                @else
                                                <p>Not Available</p>
                                                @endif

                                            </div>
                                            <div class="col-md-4">
                                                <h6 class="mb-2">Payment Escrow <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Details of any funds held in escrow for payment security or dispute resolution purposes."></i></h6>

                                                @if(decryptString($contract->payment_escrow, 'payment_escrow' ) != "")
                                                {{ decryptString($contract->payment_escrow, 'payment_escrow' )}}
                                                @else
                                                <p>Not Available</p>
                                                @endif
                                            </div>
                                        </div>
                                        <hr class="mt-3" />
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <h6 class="mb-2">Financial Guarantees or Bonds <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Information about any financial guarantees or bonds required under the contract."></i></h6>

                                                @if(decryptString($contract->financial_guarantees, 'financial_guarantees' ) != "")
                                                {{ decryptString($contract->financial_guarantees, 'financial_guarantees' )}}
                                                @else
                                                <p>Not Available</p>
                                                @endif
                                            </div>
                                            <div class="col-md-4 d-none">
                                                <h6 class="mb-2">Currency Conversion <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Terms for currency conversion if the contract involves transactions in multiple currencies."></i></h6>

                                                @if(decryptString($contract->currency_conversion, 'currency_conversion') != "")
                                                {{ decryptString($contract->currency_conversion, 'currency_conversion')}}
                                                @else
                                                <p>Not Available</p>
                                                @endif
                                            </div>

                                            <div class="row mb-3">

                                                @include('contract::contract.viewDetailCustomField', ['categoryId' => 3])

                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item card mt-4">
                        <h2 class="accordion-header d-flex align-items-center">
                            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-5" aria-expanded="false">
                                Contract Custom Fields / Miscelleneous
                            </button>
                        </h2>
                        <div id="accordionWithIcon-5" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <hr class="mt-0" />
                                <div class="row g-3">

                                    <div class="card-body mt-2">

                                        <div class="panel panel-default">

                                            <div class="panel-collapse">
                                                <div class="panel-body">
                                                    <div class="col-sm-12">
                                                        @include('contract::contract.viewDetailCustomField', ['categoryId' => 4])
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item card mt-4 d-none">
                        <h2 class="accordion-header d-flex align-items-center">
                            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-10" aria-expanded="false">
                                Category Previous Contracts
                            </button>
                        </h2>
                        <div id="accordionWithIcon-10" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <hr class="mt-1" />
                                <div class="row g-3">
                                    <table class="table table-striped table-borderless dataTable no-footer table-hover">
                                        <thead>
                                            <th>Contract Name</th>
                                            <th>Signing Date</th>
                                            <th>Contract Value</th>
                                            <th>Effective Date</th>
                                            <th>Onetime end date</th>
                                        </thead>
                                        <tbody>
                                            @foreach($contractsoldothers as $contractsoldother)
                                            <tr>
                                                <td>
                                                    <a target="_blank" href="{{ url('/contracts/' . $contractsoldother->id) }}">
                                                        {{ isset($contractsoldother->contract_name) ? decryptString($contractsoldother->contract_name,'contract_name') : '' }}
                                                    </a>
                                                </td>
                                                <td>{{ isset($contractsoldother->signing_date) ? decryptString($contractsoldother->signing_date,'signing_date') : '' }}</td>
                                                <td>{{decryptString($contractsoldother->currency,'currency')}} {{ isset($contractsoldother->currency_value) ? decryptString($contractsoldother->currency_value,'currency_value') : '' }}</td>
                                                <td>{{ isset($contractsoldother->fixed_date) ? decryptString($contractsoldother->fixed_date,'fixed_date') : '' }}</td>
                                                <td>{{ isset($contractsoldother->contract_end_date) ? decryptString($contractsoldother->contract_end_date,'contract_end_date') : '' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item card mt-4">
                        <h2 class="accordion-header d-flex align-items-center">
                            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionParentContract" aria-expanded="false">
                                Related Contracts
                            </button>
                        </h2>
                        <div id="accordionParentContract" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <hr class="mt-1" />
                                <div class="row g-3">
                                    <h4>Previous Contracts</h4>
                                    <table class="table table-striped table-borderless dataTable no-footer table-hover">
                                        <thead>
                                            <th>Contract Name</th>
                                            <th>Signing Date</th>
                                            <th>Contract Value</th>
                                            <th>Effective Date</th>
                                            <th>End date</th>
                                            <th>Actions</th>
                                        </thead>
                                        <tbody>
                                            @php
                                            $prevContracts = [];
                                            @endphp
                                            @foreach($contractsparentList as $contractsoldother)
                                            @php
                                            $prevContracts[] = $contractsoldother->id;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a target="_blank" href="{{ url('/contracts/' . $contractsoldother->id) }}">
                                                        {{ isset($contractsoldother->contract_name) ? decryptString($contractsoldother->contract_name, 'contract_name') : '' }}
                                                    </a>
                                                </td>
                                                <td>{{ isset($contractsoldother->signing_date) ? decryptString($contractsoldother->signing_date,'signing_date') : '' }}</td>
                                                <td>{{decryptString($contractsoldother->currency,'currency')}} {{ isset($contractsoldother->currency_value) ? decryptString($contractsoldother->currency_value, 'currency_value') : '' }}</td>
                                                <td class="dateTd">{{ isset($contractsoldother->fixed_date) ? date('d-m-Y',strtotime(decryptString($contractsoldother->fixed_date,'fixed_date'))) : '' }}</td>
                                                <td class="dateTd">{{ isset($contractsoldother->contract_end_date) ? date('d-m-Y',strtotime(decryptString($contractsoldother->contract_end_date,'contract_end_date'))) : '' }}</td>
                                                <td>
                                                    @php
                                                    $curOtherPartyCon = [];
                                                    foreach($contractsoldother->contractPartyList as $otherCon){
                                                    if($otherCon->contract_party_location_id == !null){
                                                    $curOtherPartyCon[] = $otherCon->contract_party_id;
                                                    }else{
                                                    $curOtherPartyCon[] = $otherCon->contract_party_exe_id;
                                                    }
                                                    }
                                                    // Sort the array
                                                    sort($currentParties);
                                                    sort($curOtherPartyCon);
                                                    //$contractsoldother->id < $contract->id &&
                                                        // Check for equality
                                                        if ($currentParties == $curOtherPartyCon && $contractsoldother->contract_type_id == $contract->contract_type_id && $contractsoldother->department_id == $contract->department_identity){
                                                        @endphp
                                                        @if(decryptString($contractsoldother->end_contract_type, 'end_contract_type') == 'fixedTerm' && $contractsoldother->id == $contract->parentcontract && decryptString($contract->end_contract_type, 'end_contract_type') == 'fixedTerm')
                                                        <button type="button" class="btn btn-sm btn-warning linkContract" data-linkcon="{{ $contractsoldother->id }}" data-linktype="unlink">Unlink</button>
                                                        @endif
                                                        @php
                                                        }
                                                        @endphp
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row g-3">
                                    <h4>Subsequent Contracts</h4>
                                    <table class="table table-striped table-borderless dataTable no-footer table-hover">
                                        <thead>
                                            <th>Contract Name</th>
                                            <th>Signing Date</th>
                                            <th>Contract Value</th>
                                            <th>Effective Date</th>
                                            <th>End date</th>
                                            <th>Actions</th>
                                        </thead>
                                        <tbody>

                                            @foreach($contractsSubseqList as $contractsoldother)
                                            @if(!in_array($contractsoldother->id, $prevContracts))
                                            @php
                                            $prevContracts[] = $contractsoldother->id;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a target="_blank" href="{{ url('/contracts/' . $contractsoldother->id) }}">
                                                        {{ isset($contractsoldother->contract_name) ? decryptString($contractsoldother->contract_name, 'contract_name') : '' }}
                                                    </a>
                                                </td>
                                                <td>{{ isset($contractsoldother->signing_date) ? decryptString($contractsoldother->signing_date,'signing_date') : '' }}</td>
                                                <td>{{decryptString($contractsoldother->currency,'currency')}} {{ isset($contractsoldother->currency_value) ? decryptString($contractsoldother->currency_value, 'currency_value') : '' }}</td>
                                                <td class="dateTd">{{ isset($contractsoldother->fixed_date) ? date('d-m-Y',strtotime(decryptString($contractsoldother->fixed_date,'fixed_date'))) : '' }}</td>
                                                <td class="dateTd">{{ isset($contractsoldother->contract_end_date) ? date('d-m-Y',strtotime(decryptString($contractsoldother->contract_end_date,'contract_end_date'))) : '' }}</td>
                                                <td></td>
                                            </tr>
                                            @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item card mt-4">
                        <h2 class="accordion-header d-flex align-items-center">
                            <input type="hidden" id="contractRefId" value="{{ $contract->id }}" />
                            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-11" aria-expanded="false">
                                Other Contracts With Parties
                            </button>
                        </h2>
                        <div id="accordionWithIcon-11" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <hr class="mt-1" />
                                <div class="row g-3">
                                    <table class="table table-striped table-borderless dataTable no-footer table-hover">
                                        <thead>
                                            <th>Contract Name</th>
                                            <th>Signing Date</th>
                                            <th>Contract Value</th>
                                            <th>Effective Date</th>
                                            <th>End date</th>
                                            <th>Actions</th>
                                        </thead>
                                        <tbody>
                                            @foreach($contractspartsList as $contractsoldother)
                                            @if(!in_array($contractsoldother->id, $prevContracts))
                                            <tr>
                                                <td>
                                                    <a target="_blank" href="{{ url('/contracts/' . $contractsoldother->id) }}">
                                                        {{ isset($contractsoldother->contract_name) ? decryptString($contractsoldother->contract_name, 'contract_name') : '' }}
                                                    </a>
                                                </td>
                                                <td>{{ isset($contractsoldother->signing_date) ? decryptString($contractsoldother->signing_date,'signing_date') : '' }}</td>
                                                <td>{{decryptString($contractsoldother->currency,'currency')}} {{ isset($contractsoldother->currency_value) ? decryptString($contractsoldother->currency_value, 'currency_value') : '' }}</td>
                                                <td class="dateTd">{{ isset($contractsoldother->fixed_date) ? date('d-m-Y',strtotime(decryptString($contractsoldother->fixed_date,'fixed_date'))) : '' }}</td>
                                                <td class="dateTd">{{ isset($contractsoldother->contract_end_date) ? date('d-m-Y',strtotime(decryptString($contractsoldother->contract_end_date,'contract_end_date'))) : '' }}</td>
                                                <td>
                                                    @php
                                                    $curOtherPartyCon = [];
                                                    foreach($contractsoldother->contractPartyList as $otherCon){
                                                    if($otherCon->contract_party_location_id == !null){
                                                    $curOtherPartyCon[] = $otherCon->contract_party_id;
                                                    }else{
                                                    $curOtherPartyCon[] = $otherCon->contract_party_exe_id;
                                                    }
                                                    }
                                                    // Sort the array
                                                    sort($currentParties);
                                                    sort($curOtherPartyCon);
                                                    $hasChild = $contractsoldother->contractParent ?? false;
                                                    // Check for equality
                                                    if (decryptString($contractsoldother->end_contract_type, 'end_contract_type') == 'fixedTerm' && decryptString($contract->end_contract_type, 'end_contract_type') == 'fixedTerm' && $currentParties == $curOtherPartyCon && $contractsoldother->contract_type_id == $contract->contract_type_id && $contractsoldother->department_id == $contract->department_identity && !$hasChild){

                                                    $end_date_of_parent = $contractsoldother->fixedterm_end_date;


                                                    $start_date_of_current = $contract->fixed_date;

                                                    if(strtotime($end_date_of_parent) <= strtotime($start_date_of_current)){

                                                        @endphp

                                                        <button type="button" class="btn btn-sm btn-primary linkContract" data-linkcon="{{ $contractsoldother->id }}" data-linktype="link">Link</button>

                                                        @php
                                                        }
                                                        }
                                                        @endphp
                                                </td>
                                            </tr>
                                            @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item card mt-4">
                        <h2 class="accordion-header d-flex align-items-center">
                            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-6" aria-expanded="false">
                                Contract Attachments
                            </button>
                        </h2>
                        <div id="accordionWithIcon-6" class="accordion-collapse collapse">
                            <div class="accordion-body">



                                <div class="card-body mt-3">
                                    <ul class="timeline mb-0">

                                        @foreach ($approvalsAttach as $key => $approvalsData)
                                        @if(!empty($approvalsData->attachments_filename) || !empty($approvalsData->attachments_support))
                                        <li class="timeline-item timeline-item-transparent">
                                            <span class="timeline-point timeline-point-info"></span>
                                            <div class="timeline-event">
                                                <div class="timeline-header mb-3">
                                                    <div class="mt-3">
                                                        @if(decryptString($approvalsData->status, 'status') == 'Signing')
                                                        <span class="badge bg-success" style="font-size: 15px;"> {{ decryptString($approvalsData->status, 'status')}} </span>
                                                        @elseif(decryptString($approvalsData->status, 'status') == 'Review')
                                                        <span class="badge bg-info" style="font-size: 15px;"> {{ decryptString($approvalsData->status, 'status')}} </span>
                                                        @else
                                                        <span class="badge bg-primary substatusText" style="font-size: 15px;"> {{ decryptString($approvalsData->status, 'status')}} </span>
                                                        @endif

                                                        <p class="mt-2">
                                                            {{ json_decode(decryptString($approvalsData->username, 'username'))->name}}

                                                            on <strong>{{date("d-M-Y", strtotime($approvalsData->created_at))}}</strong>
                                                        </p>
                                                        
                                                        <p>
                                                            @if(!empty($approvalsData->attachments_filename))
                                                            <h6>Contract Documents</h6>
                                                            <a href="{{attachmentDummyUrl($approvalsData->attachments, true, $contract->id)}}" target="_blank">
                                                                {{$approvalsData->attachments_filename ? $approvalsData->attachments_filename :$approvalsData->attachments }}
                                                            </a>
                                                            @endif
                                                            @if(!empty($approvalsData->attachments_support) && count(json_decode($approvalsData->attachments_support)) > 0)
                                                                <h6>Support Documents</h6>
                                                                @foreach (json_decode($approvalsData->attachments_support) as $key => $supportFile)
                                                                    <a href="{{attachmentDummyUrl($approvalsData->attachments, true, $contract->id)}}" target="_blank">
                                                                        {{$supportFile->name}}
                                                                    </a>
                                                                @endforeach
                                                            @endif
                                                        </p>
                                                    </div>
                                                </div>

                                            </div>
                                        </li>
                                        @endif
                                        @endforeach

                                        @if(isset($contract->contract_attachment))
                                        <li class="timeline-item timeline-item-transparent">
                                            <span class="timeline-point timeline-point-info"></span>
                                            <div class="timeline-event">
                                                <div class="timeline-header mb-3">
                                                    <h6 class="mb-0">Contract Created {{date("d-M-Y", strtotime($contract->created_at))}}</h6>
                                                    <h6 class="mb-0">Recently Updated on {{date("d-M-Y H:i:s", strtotime($contract->updated_at))}}</h6>
                                                </div>
                                                <p class="mb-2 col-6">

                                                    <a href="{{attachmentDummyUrl($contract->contract_attachment, true, $contract->id)}}" target="_blank">
                                                        {{$contract->contract_attachment_filename}}
                                                    </a>
                                                </p>
                                            </div>
                                        </li>
                                        @endif
                                    </ul>
                                </div>





                            </div>
                        </div>
                    </div>
<div class="accordion-item card mt-4">
                        <h2 class="accordion-header d-flex align-items-center">
                            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse"
                                data-bs-target="#accordionWithIcon-7" aria-expanded="false">
                                Ownership
                            </button>
                        </h2>
                        <div id="accordionWithIcon-7" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <hr class="mt-1" />
                                <div class="row g-3">
                                    <div class="col-md-12">

                                        <h6 class="mb-1">Owner</h6>
                                        @foreach ($users as $user)
                                        @if($contract->owner == $user->id)
                                        {{ $user->Salutation }}
                                        {{ $user->FirstName }}
                                        {{ $user->LastName }}
                                        @endif
                                        @endforeach
                                    </div>
                                    <div class="col-md-6">

                                        <h6 class="mb-1">Signatory</h6>
                                        @foreach ($users as $user)
                                        @if($contract->signatory == $user->id)
                                        {{ $user->Salutation }}
                                        {{ $user->FirstName }}
                                        {{ $user->LastName }}
                                        @endif
                                        @endforeach

                                    </div>

                                    @if(decryptString($contract->contract_mode, 'contract_mode') == 'old' || $contract->contract_status == 'executed')
                                    <div class="col-md-6">
                                        <div class="form-group ">
                                            <h6 class="mb-1">Signed Date <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="The date on which the contract is signed by all parties involved. This may or may not be the same as the effective date, depending on the terms of the contract."></i></h6>
                                            @if($contract->signing_date != "")

                                            {{ date('d-m-Y', strtotime($contract->signing_date)) }}
                                            @else
                                            <p>Not Available</p>
                                            @endif                                            
                                        </div>
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
</div>
</div>

<!--<h6> Collapsible Section </h6>-->
</div>
</div>
</div>
</div>
</form>




</div>
</div>



@endif
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
        
        const rect = canvas.getBoundingClientRect();
        
        // Event listeners for mouse events
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);
        
        // Event listeners for touch events
        canvas.addEventListener('touchstart', startDrawing, false);
        canvas.addEventListener('touchmove', draw, false);
        canvas.addEventListener('touchend', stopDrawing, false);
        canvas.addEventListener('touchcancel', stopDrawing, false);
        
        function startDrawing(e) {
            
            e.preventDefault();
            isDrawing = true;
            ctx.beginPath(); // Start a new path
            const x = e.offsetX || e.touches[0].pageX - rect.left;
            const y = e.offsetY || e.touches[0].pageY - rect.top;
            ctx.moveTo(x, y); // Move the pen to the starting point
        }
        
        function draw(e) {
            

            if (!isDrawing) return;
            e.preventDefault();
            const x = e.offsetX || e.touches[0].pageX - rect.left;
            const y = e.offsetY || e.touches[0].pageY - rect.top;
            ctx.lineCap = 'round';
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

<!-- Compare History Modal -->
<div class="modal fade" id="compareHistoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Compare History</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="compareHistoryContent">
        <div class="text-center py-3">Loading...</div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('click', function(e){
  var btn = e.target.closest('.compare-history-btn');
  if(!btn) return;
  var historyId = btn.getAttribute('data-history-id');
  var contractId = btn.getAttribute('data-contract-id');
  var modalEl = document.getElementById('compareHistoryModal');
  var modal = new bootstrap.Modal(modalEl);
  var body = document.getElementById('compareHistoryContent');
  body.innerHTML = '<div class="text-center py-3">Loading...</div>';
  fetch(APP_URL + '/contracts/' + contractId + '/history/' + historyId + '/compare', {
    headers: { 'Accept': 'application/json' }
  }).then(function(res){ return res.json(); }).then(function(json){
    if(!json || !json.status){ body.innerHTML = '<div class="alert alert-danger">Failed to fetch history for comparison</div>'; modal.show(); return; }
    var keys = ['contract_name','fixed_date','contract_end_date','currency_value','contract_status','substatus','owner','contract_attachment_filename'];
    var html = '<table class="table table-sm table-striped"><thead><tr><th>Field</th><th>History</th><th>Current</th></tr></thead><tbody>';
    keys.forEach(function(k){
      var h = (json.history_display && json.history_display[k] !== undefined) ? json.history_display[k] : (json.history[k] ?? '—');
      var c = (json.current_display && json.current_display[k] !== undefined) ? json.current_display[k] : (json.current[k] ?? '—');
      var diffClass = (String(h) !== String(c)) ? 'table-warning' : '';
      html += '<tr class="' + diffClass + '"><td><strong>' + k.replace(/_/g,' ') + '</strong></td><td>' + (h === null || h === '' ? '—' : h) + '</td><td>' + (c === null || c === '' ? '—' : c) + '</td></tr>';
    });
    html += '</tbody></table>';
    body.innerHTML = html;
    modal.show();
  }).catch(function(){ body.innerHTML = '<div class="alert alert-danger">Request failed</div>'; modal.show(); });
});
</script>

@endsection
@section('footer')
@endsection