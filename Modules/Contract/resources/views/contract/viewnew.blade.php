@extends('layouts/layoutMaster')
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

<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')

@vite(['resources/assets/js/forms-file-upload.js'])
@vite(['resources/assets/js/form-layouts.js'])

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/jquery-ui.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contract.js"></script>
@endsection

@section('content')
<style>

    #approvalForm .btn-label-warning,
    #approvalForm .btn-label-warning:hover {

        border-color: transparent !important;
        background: #7367f0 !important;
        color: #fff !important;

    }
    
    table thead tr{
        vertical-align: middle;
    }
    
    .dateTd{
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

.horizontal.timeline .steps .step.done{
    background-color: green;
}

.horizontal.timeline .steps .step.done{
    background-color: green;
}

.horizontal.timeline .steps .step.current{
    background-color: orange;
}

.horizontal.timeline .steps .step:first-child {
  margin-left: 0;
}
.horizontal.timeline .steps .step:last-child {
  margin-right: 0;
  color: #71CB35;
}
.horizontal.timeline .steps .step span {
  position: absolute;
  top: calc(100% + 1em);
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
  color: #000;
}
.horizontal.timeline .steps .step.current span:before {
  content: "";
  display: block;
  position: absolute;
  top: 50%;
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
.step:after
{
  content:'';
  width:5em;/* size of your margin */
  border-bottom:2px solid #d4d4d4;
  position:absolute;
  top:50%;

}

.step.done:after, .step.done:before
{
    border-color: green;
}

.step.current:before
{
    border-color: green;
}

.step:after {
  left:100%;
}
.step:before {
  right:100%;
}

.step:after {
  left:100%;
}
.step:before {
  right:100%;
}
.step:first-of-type:before,
.step:last-of-type:after {
  display:none;
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

</style>

<div class="row invoice-preview mb-4">
    <div class="col-12">
        <div class="card invoice-preview-card p-sm-12">
            <div class="card-body rounded p-0">
            <div class="d-flex flex-xl-row flex-md-column flex-sm-row flex-column p-4">
              <div class="mb-xl-0 mb-6 text-heading col-6">
                <div class="d-flex svg-illustration mb-6 gap-2 align-items-center">
                  <h5 class="fw-bold">
                    {{ decryptString($contract->contract_name, 'contract_name') }}
                  </h5>
                </div>
                <table>
                  <tbody>
                    <tr>
                      <td class="pe-4 text-start">Contract ID:</td>
                      <td class="text-start border-bottom-0">#</td>
                    </tr>                       
                    @if(isset($contract->fixed_date)) 
                    <tr>
                      <td class="pe-4 text-start">Effective From:</td>
                      <td class="fw-medium text-start border-bottom-0">{{date("d-m-Y", strtotime($contract->fixed_date))}}</td>
                    </tr>
                    <tr>
                      <td class="pe-4 text-start">Termination On:</td>
                      <td class="fw-medium text-start border-bottom-0">@if(isset($contract->onetime_end_date))
                {{date("d-m-Y", strtotime($contract->onetime_end_date))}} 
                @else {{'NA'}} @endif</td>
                    </tr>
                    @endif
                    <tr>
                      <td class="pe-4 text-start">Contract Value:</td>
                      <td class="text-start border-bottom-0">{{ decryptString($contract->currency, 'currency')." ".decryptString($contract->currency_value, 'currency_value') }}</td>
                    </tr>                    
                </table>
              </div>
              <div class="col-6">
                <h5 class="mb-6 fw-bold">Details</h5>
            <table>
              <tbody>
                <tr>
                  <td class="pe-4 text-start">Branch:</td>
                  <td class="text-start border-bottom-0">{{ $branchs[0]->BranchName }}</td>
                </tr>
                <tr>
                  <td class="pe-4 text-start align-baseline">Parties:</td>
                  <td class="text-start border-bottom-0">
                      <div>
                      @php
                      $partycount = 1;
                      foreach($contractPartyData as $condata){
                        echo "Party ". $partycount ." - ".$condata->Nameoftheentity."<br/>";
                        $partycount++;
                      }
                      @endphp
                      </div>
                  </td>
                </tr>
                <tr>
                  <td class="pe-4 text-start">Attachments</td>
                  <td class="text-start border-bottom-0"></td>
                </tr>                
              </tbody>
            </table>                
              </div>
            </div>
            @php
                $doneProgress = 0;
                switch ($contract->contract_status) {
                    case 'executed':
                        $doneProgress = 7;
                        break;
                    case 'draft':
                        $doneProgress = 1;
                        break;        
                    case 'review':
                        $doneProgress = 2;
                        break;        
                    case 'Negotiation':
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
            <div class="my-4 p-4 mx-5">
                <div class="horizontal timeline">
            	<div class="steps">
            		<div class="step {{ $contract->contract_status == 'draft' ? 'current' : ''}} {{ $doneProgress > 1 ? 'done' : '' }}">
            			<span>Draft</span>
            		</div>
            		<div class="step {{ $contract->contract_status == 'review' ? 'current' : ''}} {{ $doneProgress > 2 ? 'done' : '' }}">
            			<span>Review</span>
            		</div>
            		<div class="step {{ $contract->contract_status == 'Negotiation' ? 'current' : ''}} {{ $doneProgress > 3 ? 'done' : '' }}">
            			<span>Negotiation</span>
            		</div>
            		<div class="step {{ $contract->contract_status == 'pending' ? 'current' : ''}} {{ $doneProgress > 4 ? 'done' : '' }}">
            			<span>Pending Approval</span>
            		</div>
            		<div class="step {{ $contract->contract_status == 'approved' ? 'current' : ''}} {{ $doneProgress > 5 ? 'done' : '' }}">
            			<span>Approved</span>
            		</div>
            		<div class="step {{ $contract->contract_status == 'sigining' ? 'current' : ''}} {{ $doneProgress > 6 ? 'done' : '' }}">
            			<span>Sigining</span>
            		</div>
            		<div class="step {{ $contract->contract_status == 'Executed' ? 'current' : ''}} {{ $doneProgress >= 7 ? 'done' : '' }}">
            			<span>Executed</span>
            		</div>
            	</div>
            </div>
            </div>
            @if($contract->contract_status == 'executed')
                @php
                    $statusClass = '';
                    $statusStrin = '';
                    switch ($contract->substatus) {
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
                        case 'Terminated':
                            $statusClass = 'info';
                            $statusStrin = $contract->substatus;
                            break;
                        case 'completed':
                            $statusClass = 'secondary';
                            $statusStrin = $contract->substatus;
                            break;
                    }                
                @endphp
                <p class="bg-{{$statusClass}} w-100 m-0 mt-4 py-2 text-center text-white fw-bold text-uppercase">STATUS : {{ $contract->substatus }}</p>
            @endif
        </div>
        </div>
    </div>
    <div class="d-flex align-content-center flex-wrap gap-3 d-lg-none">
        <div class="d-flex gap-3">
            <a href="{{url('/')}}" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
        </div>
    </div>
</div>

<div class="col-sm-12">
    <!--<h3>-->
    <!--    View Contract - {{ $contract->contract_name }} ({{ $contract->contract_status }})-->
    <!--</h3>-->


    <div class="col-sm-12">
        <!--<ul class="nav nav-tabs">-->
        <!--    @if (isset($_GET['tab']))-->
        <!--        <li><a href="/contractsdemo/contract/{{ $contract->id }}">Details</a></li>-->
        <!--<li class="active"><a href="/contract/{{ $contract->id }}?tab=commets">Commets</a></li>-->
        <!--        <li class="active"><a href="/contractsdemo/contract/{{ $contract->id }}?tab=timeline">Approvals</a></li>-->
        <!--    @else-->
        <!--        <li class="active"><a href="/contractsdemo/contract/{{ $contract->id }}">Details</a></li>-->
        <!--<li><a href="/contracts/contract/{{ $contract->id }}?tab=commets">Commets</a></li>-->
        <!--        <li><a href="/contractsdemo/contract/{{ $contract->id }}?tab=timeline">Approvals</a></li>-->
        <!--    @endif-->



        <!--</ul>-->


        <ul class="nav nav-tabs m-0 m0" role="tablist">

            @if (isset($_GET['tab']) && $_GET['tab'] == 'timeline')
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id) }}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=edit' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=timeline' )}}">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=flow' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Flow</button>
                </a></li>

            @elseif (isset($_GET['tab']) && $_GET['tab'] == 'edit')

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id )}}">
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
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=flow' )}}">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Flow</button>
                </a></li>

            @elseif (isset($_GET['tab']) && $_GET['tab'] == 'flow')

            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id )}}">
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
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id.'?tab=flow' )}}">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Flow</button>
                </a></li>

            @else
            <li class="nav-item active"><a href="{{ url('contracts/'.$contract->id )}}">
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
            <li class="nav-item "><a href="{{ url('contracts/'.$contract->id.'?tab=flow' )}}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Flow</button>
                </a></li>



            @endif

        </ul>


    </div>
    <!--<div class="col-sm-12" style = "padding-bottom:15px;">-->
    <!--    <ul class="nav nav-tabs">-->
    <!--        @if (isset($_GET['tab']))-->
    <!--            <li><a href="/contract/{{ $contract->id }}">Details</a></li>-->
    <!--            <li class="active"><a href="/contract/{{ $contract->id }}?tab=timeline">Timeline</a></li>-->
    <!--        @else-->
    <!--            <li class="active"><a href="/contract/{{ $contract->id }}">Details</a></li>-->
    <!--            <li><a href="/contract/{{ $contract->id }}?tab=timeline">Timeline</a></li>-->
    <!--        @endif-->

    <!--    </ul>-->
    <!--</div>-->
</div>

@if (isset($_GET['tab']) && $_GET['tab'] == 'timeline')
<input type="hidden" id="contractId" class="form-control" value="{{$contract->id}}">


@if (count($approvalsArr) == 0)

<div class="card">

    <p class="mt-4" style="text-align: center;">No record found</p>

</div>


@endif


@foreach ($approvalsArr as $key => $approvalsData)

<input type="hidden" id="curAppStatus" class="form-control" value="{{$approvalsData[0]->status}}">

@if (count($approvalsData) == 1 && $approvalsData[0]->status != 'Signing')

@if ($approvalsData[0]->status == 'Draft' && $approvalsData[0]->previous_status == 'Draft'
&& $approvalsData[0]->flag == 1 )

<div class="row mb-6 g-6">
    <div class="col-lg-12">
        <div class="card h-100">
            <form id="approvalForm">

                <div class="card-body mt-3">
                    <button type="button" class="btn btn-label-warning text-nowrap d-inline-flex position-relative me-4">
                        {{ $approvalsData[0]->status }}
                    </button>

                    <div class="d-flex flex-column flex-sm-row justify-content-between text-center gap-6">
                        <div class="d-flex flex-column align-items-center">

                        </div>
                        <div class="d-flex flex-column align-items-center" style="cursor: pointer;">
                            <span class="p-4 border-1 border-primary rounded-circle border-dashed mb-0 w-px-75 h-px-75" style="border-color: #148000 !important;">
                                <!-- <i class="fa-solid fa-circle-check"></i> -->
                                <!-- <i class="fa-solid fa-paper-plane" id = "paperIcon" style="margin-left: -5px;font-size:xx-large;color: #7367f0;margin-top: -4px;"> -->
                                <!-- </i> -->
                                <!-- <a href = ""> -->
                                <i class="fa-solid fa-circle-check" id="paperIcon" style="margin-left: -3px;font-size:xx-large;color: #4CAF50;margin-top: -4px;"> </i>
                                <!-- </a> -->
                            </span>
                            <p style="color: #4CAF50;" class="my-2">Click Icon to Send For review</p>
                        </div>
                        <div class="d-flex flex-column align-items-center">

                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>
@elseif($approvalsData[0]->flag == 1 && $approvalsData[0]->status != 'Signing' && $approvalsData[0]->previous_status != 'Approved')
<div class="row mb-6 g-6">
    <div class="col-lg-12" style="margin-top: 25px;">
        <div class="card h-100">
            <form id="approvalForm">

                <div class="card-body" style="margin-top: 0;">
                    <button type="button" class="btn btn-label-warning btnStatus text-nowrap d-inline-flex position-relative me-4">
                        {{ $approvalsData[0]->status }}
                    </button>

                    <div class="d-flex flex-column flex-sm-row justify-content-between text-center gap-6" style="cursor: pointer;">
                        <div class="d-flex flex-column align-items-center">

                        </div>
                        <div class="d-flex flex-column align-items-center">
                            <span class="p-4 border-1 border-primary mb-0 w-px-75 h-px-75">
                                <i class="fa-solid fa-circle-check" id="paperIcon" style="margin-left: -5px;font-size:xx-large;color: #4CAF50;margin-top: -4px;"> </i>
                                <!-- <i class="fa-solid fa-paper-plane" id = "paperIcon" style="margin-left: -5px;font-size:xx-large;color: #7367f0;margin-top: -4px;">
                                    </i> -->
                            </span>
                            @if ($approvalsData[0]->status == 'Negotiation')
                            <p style="color: #4CAF50;" class="my-2">Click Icon to Send For Approval</p>
                            @elseif($approvalsData[0]->status == 'Approved')
                            <p style="color: #4CAF50;" class="my-2">Click Icon to Send For Signing</p>
                            @endif
                        </div>

                        <div class="d-flex flex-column align-items-center">
                            <span class="p-4 border-1 border-primary mb-0 w-px-75 h-px-75">
                                <i class="fa-solid fa-times-circle" id="paperIconReject"
                                    style="margin-left: -5px;font-size:xx-large;color: #f44336;margin-top: -4px;"> </i>

                            </span>
                            <p style="color: #f44336;" class="my-2">Send For {{ $approvalsData[0]->previous_status }}</p>

                        </div>
                        <div class="d-flex flex-column align-items-center">

                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>
@endif
@else

<div class="card-body mt-4">

    <div class="row mb-3">
        <div class="row">
            <div class="col py-2">
                <div class="col-xl-12 mb-6 mb-xl-0">
                    <div class="card">
                        @foreach ($approvalsData as $approvalsValues)
                        @if($loop->first )
                        <div class="card-header">
                            <p class="text-uppercase"> <span class="badge bg-info text-dark">
                                    STATUS :- {{ $approvalsValues->status }}
                                </span></p>
                            <hr>
                        </div>
                        @endif
                        <div class="card-body">
                            <ul class="timeline mb-0">
                                <li class="timeline-item timeline-item-transparent">
                                    @if ($approvalsValues->approval_status == 'approved')
                                    <span class="timeline-point timeline-point-success"></span>
                                    @endif
                                    @if ($approvalsValues->approval_status == 'rejected')
                                    <span class="timeline-point timeline-point-danger"></span>
                                    @endif
                                    @if ($approvalsValues->approval_status == 'pending')
                                    <span class="timeline-point timeline-point-warning"></span>
                                    @endif
                                    <div class="timeline-event">
                                        <div class="timeline-header mb-3">
                                            <h6 class="mb-0">{{ $approvalsValues->username }}</h6>
                                            <!-- <h6 class="mb-0">{{ $loop->index }}</h6> -->
                                            @if ($approvalsValues->approval_status == 'pending')
                                            <small>Date : {{ \Carbon\Carbon::parse($approvalsValues->created_at)->format('d/m/Y H:i:s')}}</small>
                                            @endif
                                            @if ($approvalsValues->approval_status == 'approved' || $approvalsValues->approval_status == 'rejected')
                                            <i class="fa fa-eye editView" value="{{ $loop->index }}" style="cursor: pointer;"></i>
                                            <i class="fa fa-eye editViewClose" value="{{ $loop->index }}" style="cursor: pointer; display: none;"></i>
                                            @endif

                                            <!-- <small class="text-muted">{{ $approvalsValues->created_at }}</small> -->
                                        </div>
                                        <p class="mb-2">
                                            {{ $approvalsValues->next_action_item }}
                                        </p>
                                        @if ($approvalsValues->approval_status == 'approved' )
                                        <p><span class="badge text-bg-success">{{$approvalsValues->button_text}} -
                                                {{ \Carbon\Carbon::parse($approvalsValues->updated_at)->format('d/m/Y H:i:s')}}</span>
                                        </p>
                                        @endif
                                        @if ($approvalsValues->approval_status == 'rejected' )
                                        <p><span class="badge text-bg-danger">{{$approvalsValues->button_text}} -
                                                {{ \Carbon\Carbon::parse($approvalsValues->updated_at)->format('d/m/Y H:i:s')}}</span>
                                        </p>
                                        @endif
                                        <!-- {{ $loop->index }} -->
                                        <!-- $approvalsArr[$loop->index - 1]->status == 'approved' -->
                                        <div class="d-flex align-items-center mb-2">
                                            @if (($approvalsValues->approval_status == 'pending') && $loop->index == 0)
                                            <div class="badge bg-lighter rounded d-flex align-items-center">
                                                @if ($approvalsValues->status != 'Signing')
                                                <button type="submit" id="btn_save_updates_approve"
                                                    class="btn btn-success btn-sm pull-right" style="right: 10px;">Send to next {{ $approvalsValues->status }}</button>
                                                @elseif($approvalsValues->status == 'Signing')
                                                <button type="submit" id="btn_save_updates_approve"
                                                    class="btn btn-success btn-sm pull-right" style="right: 10px;">To Sign</button>
                                                @endif

                                                @if($approvalsValues->status != 'Signing')
                                                <button type="submit" id="btn_save_updates_reject"
                                                    class="btn btn-danger btn-sm pull-right">Send to Owner</button>
                                                @endif
                                            </div>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <!-- @if (isset($approvalsArr[$approvalsValues->unique_id][$loop->index - 1]))
                                        {{ $approvalsArr[$approvalsValues->unique_id][$loop->index - 1]->approval_status }}
                                    @endif -->
                                            @if ($approvalsValues->approval_status == 'pending' && (
                                            $loop->index > 0 && ($approvalsArr[$approvalsValues->unique_id][$loop->index - 1]->approval_status == 'approved'
                                            || $approvalsArr[$approvalsValues->unique_id][$loop->index - 1]->approval_status == 'rejected' )))
                                            <div class="badge bg-lighter rounded d-flex align-items-center">
                                                <button id="btn_save_updates_approve"
                                                    class="btn btn-success btn-sm pull-right" style="right: 10px;">Send to next {{ $approvalsValues->status }}</button>
                                                <button id="btn_save_updates_reject"
                                                    class="btn btn-danger btn-sm pull-right">Send to Owner</button>
                                            </div>
                                            @endif
                                        </div>
                                        @if ($approvalsValues->approval_status == 'pending' && $loop->index != 0)
                                        <p class="card-header"><span class="badge text-bg-info">Waiting for {{ $approvalsValues->status }}</span></p>
                                        @endif
                                        @if ($approvalsValues->approval_status == 'pending')
                                        <div class="" id="updatesDiv{{ $loop->index }}" style="display:none;">
                                            <!-- <form action="/contracts/updateApprovals" method="POST" enctype="multipart/form-data"> -->
                                            <form id="approvalAddUpdatesForm" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div>
                                                    <div class="col-md-6">
                                                        @if($approvalsValues->status == 'Signing')
                                                        <div class="form-group">
                                                            <label>Date of Signing</label>
                                                            <input type="date" name="sign_date" class="form-control flatpickr flatpickr-input">
                                                        </div>
                                                        <hr>
                                                        @endif
                                                        <div class="form-group">
                                                            <label for="nextActionItem">Short Description<span style="color:red"></span></label>
                                                            <input type="text" name="nextActionItem{{ $loop->index }}" placeholder="Enter Next Action"
                                                                class="form-control">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="contactId" placeholder="Enter Next Action"
                                                                class="form-control" value="{{$contract->id}}">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="appId" class="form-control" value="{{$approvalsValues->id}}">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="indexId" id="indexId" class="form-control"
                                                                value="{{ $loop->index }}">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="appType" id="appType{{ $loop->index }}" class="form-control">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="appStatus" id="appStatus" class="form-control"
                                                                value="{{ $approvalsValues->status }}">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="appPreStatus" id="appPreStatus" class="form-control"
                                                                value="{{ $approvalsValues->previous_status }}">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="orderval" id="orderval" class="form-control"
                                                                value="{{ $approvalsValues->orderval }}">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="unique_id" id="unique_id" class="form-control"
                                                                value="{{ $approvalsValues->unique_id }}">
                                                        </div>
                                                        <hr>

                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="nextAction"> Description</label>
                                                            <textarea rows="2" type="text" name="nextAction{{ $loop->index }}"
                                                                placeholder="Enter Next Action Description" class="form-control"></textarea>
                                                        </div>
                                                        <hr>
                                                    </div>
                                                    @if($approvalsValues->status == 'review' || $approvalsValues->status == 'Signing')
                                                    <div class="col-md-6">
                                                        <div class="form-group" style="margin-top: 19px;">
                                                            <input id="myFile" name="photos" type="file">
                                                        </div>
                                                        <hr>
                                                    </div>
                                                    @endif
                                                </div>

                                                <div style="height: 50px;margin-top: 20px;">
                                                    <button type="submit" id="btn_save_updates"
                                                        class="btn btn-primary btn-sm pull-right">Update</button>
                                                    <button type="button" id="btn_cancel_updates"
                                                        class="btn btn-danger btn-sm pull-right">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                        @endif

                                        @if ($approvalsValues->approval_status == 'approved' || $approvalsValues->approval_status == 'rejected')
                                        <div class="" id="EditDiv{{ $loop->index }}" style="display:none;">
                                            <form id="editApprovalAddUpdatesForm">
                                                @csrf
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        @if($approvalsValues->status == 'Signing')
                                                        <div class="form-group">
                                                            <label>Date of Signing</label>
                                                            <input type="date" name="sign_date" class="form-control flatpickr flatpickr-input">
                                                        </div>
                                                        <hr>
                                                        @endif
                                                        <div class="form-group">
                                                            <label for="nextActionItem">Short Description<span style="color:red"></span></label>
                                                            <input type="text" name="edit_nextActionItem{{ $loop->index }}" placeholder="Enter Next Action"
                                                                class="form-control" value="{{ $approvalsValues->next_action_item }}">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="edit_contactId" placeholder="Enter Next Action"
                                                                class="form-control" value="{{$contract->id}}">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="edit_appId" class="form-control" value="{{$approvalsValues->id}}">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" class="edit_indexId" name="edit_indexId" id="edit_indexId" class="form-control">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="edit_appType" id="appType" class="form-control">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="edit_appStatus" id="appStatus" class="form-control"
                                                                value="{{ $approvalsValues->status }}">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="edit_appPreStatus" id="appPreStatus" class="form-control"
                                                                value="{{ $approvalsValues->previous_status }}">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="edit_orderval" id="edit_orderval" class="form-control"
                                                                value="{{ $approvalsValues->orderval }}">
                                                        </div>
                                                        <div class="form-group">
                                                            <input type="hidden" name="_editunique_id" id="_editunique_id" class="form-control"
                                                                value="{{ $approvalsValues->unique_id }}">
                                                        </div>

                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="nextAction"> Description</label>
                                                            <textarea rows="2" type="text" name="edit_nextAction{{ $loop->index }}"
                                                                placeholder="Enter Next Action Description" class="form-control"
                                                                value="{{ $approvalsValues->next_action_description }}">{{ $approvalsValues->next_action_description }}</textarea>
                                                        </div>
                                                    </div>

                                                    @if($approvalsValues->status == 'review' && $approvalsValues->status == 'Signing' && $approvalsValues->status == 'Signed')
                                                    <div class="col-md-6">
                                                        <!--<div class="form-group" style="margin-top: 19px;">-->
                                                        <!--    <input id="myFile" name="edit_photos" type="file">-->
                                                        <!--</div>-->
                                                        <div class="input-group mt-3">
                                                            <input type="file" class="form-control" name="edit_photos" id="myFile">
                                                            <label class="input-group-text" for="myFile">Upload</label>
                                                        </div>

                                                    </div>

                                                    <div class="col-md-6" style="margin-top: 19px;">
                                                        <p><a href=""> {{ $approvalsValues->attachments }} </a> </p>

                                                    </div>
                                                    @endif
                                                </div>

                                                <div style="height: 50px;margin-top: 20px;">
                                                    <button type="" id="btn_save_edit_updates"
                                                        class="btn btn-primary btn-sm pull-right">Update</button>
                                                    <button type="button" class="btn btn-danger btn-sm pull-right 
                                                        btn_cancel_edit_updates">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                        <hr>
                                        @endif
                                    </div>
                                </li>

                            </ul>

                        </div>
                        @endforeach

                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

@endif

@endforeach


@elseif (isset($_GET['tab']) && $_GET['tab'] == 'flow')

{{-- @include('contract::contract.contractFlow') --}}

@elseif (isset($_GET['tab']) && $_GET['tab'] == 'edit')
@include('contract::contract.viewDetailContractEdit')
@else

<div class="row my-4">
    <div class="col">
        <form class="row" id="createcontract" enctype="multipart/form-data">
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
                                        Legacy Contracts </label>
                                </div>
                                <div class="btn-group float-end {{ $contract->contract_status == 'executed' ? '' : 'd-none' }}">
                                    <button type="button" class="btn btn-warning waves-effect waves-light">More Actions</button>
                                    <button type="button" class="btn btn-warning dropdown-toggle dropdown-toggle-split waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="visually-hidden">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        @if(decryptString($contract->end_contract_type, 'end_contract_type') == 'fixedTerm')
                                        <li>
                                            <a href="{{ url('contracts/renew/'.$contract->id) }}" class="dropdown-item waves-effect">
                                                <span class="ti-xs ti ti-receipt-refund me-2 text-warning"></span>Initiate Renewal
                                            </a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        @endif
                                        @if($contract->contract_status == 'executable')
                                        <li>
                                            <a href="javascript:;" class="dropdown-item waves-effect">
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
                                        <label class="form-label" for="contracttype">Contract Type <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select select2 contracttype"
                                            name="BasicContract[contractType]" id="contracttype" disabled>
                                            @foreach ($contractTypes as $contractType)

                                            @if($contract->contract_type == decryptString($contractType->contract_type,'contract_type'))
                                            <option value="{{ $contractType->contract_type_id }}" selected>
                                                {{ decryptString($contractType->contract_type,'contract_type') }}
                                            </option>
                                            @else
                                            <option value="{{ $contractType->contract_type_id }}">
                                                {{ decryptString($contractType->contract_type,'contract_type') }}
                                            </option>
                                            @endif
                                            @endforeach
                                            <!--@foreach ($contractTypes as $contractType)-->
                                            <!--<option value="{{ $contractType->contract_type_id }}">-->
                                            <!--   {{ $contractType->contract_type }}-->
                                            <!--</option>-->
                                            <!--@endforeach-->
                                        </select>
                                    </div>
                                    <!--<div class="col-md-6">-->
                                    <!--<label class="form-label" for="ecommerce-product-barcode">Contract Type</label>-->
                                    <!--   <label class="form-label" for="signatory">Signatory <span class="text-danger">*</span></label>-->
                                    <!--<select id="signatory" name="BasicContract[signatory]" class="form-select select2" data-allow-clear="true">-->
                                    <!--<option value="">Select Signatory</option>-->
                                    <!--<option value="Test">Test</option>-->
                                    <!--<option value="Demo">Demo</option>-->
                                    <!--</select>    -->
                                    <!--   <select class="form-select select2 " name="BasicContract[signatory]"-->
                                    <!--      id="signatory" disabled>-->
                                    <!--      <option value="">-Select Signatory-</option>-->
                                    <!--      @foreach ($users as $user)-->
                                    <!--      <option value="{{ $user->id }}">-->
                                    <!--         {{ $user->FirstName }}-->
                                    <!--      </option>-->
                                    <!--      @endforeach-->
                                    <!--   </select>-->
                                    <!--</div>-->
                                    <div class="col-md-6">
                                        <label class="form-label" for="catgoeryType">Department <span class="text-danger">*</span></label>
                                        <select id="catgoeryType" name="BasicContract[DepartmentType]"
                                            class="form-select select2" data-allow-clear="true" disabled>
                                            @foreach($ent as $en)
                                            @if($contract->department_id == $en->name)
                                            <option value="{{$en->id}}" selected>{{$en->name}}</option>
                                            @else
                                            <option value="{{$en->id}}">{{$en->name}}</option>
                                            @endif
                                            @endforeach

                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="DepartmentType">Category <span class="text-danger">*</span></label>
                                        <select id="DepartmentType" name="BasicContract[catgoeryType]"
                                            class="form-select select2 DepartmentType" data-allow-clear="true" disabled>
                                            @foreach($catego as $en)
                                            @if($contract->catgoery_id == $en->name)
                                            <option value="{{$en->id}}" selected>{{$en->name}}</option>
                                            @else
                                            <option value="{{$en->id}}">{{$en->name}}</option>
                                            @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <!--                           <div class="col-md-6">-->
                                    <!--                               <label class="form-label" for="Exclusivity">Confidentiality agreement</label>-->
                                    <!--                                 <div class="col mt-2">-->
                                    <!--                                    <div class="form-check form-check-inline">-->
                                    <!--                                       <label class="form-check-label">-->
                                    <!--                                       <input type="radio" class="contractmode form-check-input" disabled name="BasicContract[Confidentialityagreement]" value="Yes" {{$contract->confidentialityagreement == 'Yes' ? 'checked' : ''}}-->

                                    <!--                                       Yes</label>-->
                                    <!--                                    </div>-->
                                    <!--                                    <div class="form-check form-check-inline">-->
                                    <!--                                       <label class="form-check-label">-->
                                    <!--                                       <input type="radio" class="contractmode form-check-input" disabled name="BasicContract[Confidentialityagreement]" value="No" {{$contract->confidentialityagreement == 'No' ? 'checked' : ''}}-->

                                    <!--                                       No </label>-->
                                    <!--                                    </div>-->
                                    <!--                                 </div>-->
                                    <!--                            </div>-->

                                    <div class="col-md-6">
                                        <label class="form-label" for="Exclusivity">Exclusivity</label>
                                        <select id="DepartmentType" name="BasicContract[Exclusivity]"
                                            class="form-select select2" data-allow-clear="true" disabled>
                                            <option value="Exclusivity to Company" {{decryptString($contract->exclusivity, 'exclusivity' ) == 'Exclusivity to Company' ? 'selected' : ''}}>Exclusive to Company</option>
                                            <option value="Exclusive to Contracting Party" {{decryptString($contract->exclusivity, 'exclusivity' ) == 'Exclusive to Contracting Party' ? 'selected' : ''}}>Exclusive to Contracting Party</option>
                                            <option value="Mutually Exclusive" {{decryptString($contract->exclusivity, 'exclusivity') == 'Mutually Exclusive' ? 'selected' : '' }}>Mutually Exclusive</option>
                                            <option value="Non Exclusive" {{decryptString($contract->exclusivity, 'exclusivity' ) == 'Non Exclusive' ? 'selected' : ''}}>Non Exclusive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="contractDescription">Contract
                                            Description</label>
                                        <textarea class="form-control" id="contractDescription"
                                            name="BasicContract[contractDescription]" rows="5" disabled>{{
                                    decryptString($contract->contract_description,'contract_description' )}}</textarea>
                                    </div>

                                    <div class="col-12">
                                        <h6 class="mt-4">Custom Fields</h6>
                                        <hr class="mt-0" />
                                    </div>
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

                                    <!--<div class="panel-body">-->
                                    <!--    <div class="party-group">-->
                                    <!--    </div>-->
                                    <!--<button class="admo">+Add more parties</button>-->
                                    <!--    <button type="submit" class="btn btn-primary me-sm-3 me-1 admo">+Add more-->
                                    <!--    parties</button>-->

                                    <!-- </div>-->

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


                                        <label class="form-label" for="owner">Owner <span class="text-danger">*</span></label>
                                        <select class="form-select select2 " name="owner" id="ownership" disabled>
                                            <option value="">-Owner-</option>
                                            @foreach ($users as $user)


                                            @if($contract->owner == $user->id)
                                            <option value="{{ $user->id }}" selected>
                                               {{ $user->Salutation }}
                                                {{ $user->FirstName }}
                                                {{ $user->LastName }}
                                            </option>
                                            @else
                                            <option value="{{ $user->id }}">
                                                {{ $user->Salutation }}
                                                {{ $user->FirstName }}
                                                {{ $user->LastName }}
                                            </option>
                                            @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="Ownership Signatory">Signatory <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select select2 " name="BasicContract[signatory]" id="ownership-signatory" disabled>
                                            <option value="">-Select Signatory-</option>
                                            @foreach ($users as $user)
                                            @if($contract->signatory == $user->id)-->
                                            <option value="{{ $user->id }}" selected>
                                                {{ $user->Salutation }}
                                                {{ $user->FirstName }}
                                                {{ $user->LastName }}
                                            </option>
                                            @else
                                            <option value="{{ $user->id }}">
                                               {{ $user->Salutation }}
                                                {{ $user->FirstName }}
                                                {{ $user->LastName }}
                                            </option>
                                            @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    @if(decryptString($contract->contract_mode, 'contract_mode') == 'old') 
                           <div class="col-md-6">
                                <div class="form-group ">
                                   <label class="form-label">Signing Date</label>
                                    <!--<label>Date</label>-->
                                    <input type="date" name="Duration[signingDate]" disabled class="form-control flatpickr" value="{{ $contract->signing_date }}" />
                                    
                                    <div class="clearfix">
                                        <small class="form-text text-muted">The date on which the contract is signed by all parties involved. This may or may not be the same as the effective date, depending on the terms of the contract.
                                        </small>
                                    </div>
                                </div>
                                </div>
                                  @endif
                                  
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
                                                    <input type="radio" class="form-check-input commencementDate" name="Duration[commencementDate]" disabled value="FixedDate" {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'onetimeContract' ? 'checked' : '' }}>
                                                    Fixed Date
                                                </label>
                                                <label class="form-check-inline form-check">
                                                    <input class="form-check-input commencementDate" type="radio" name="Duration[commencementDate]" disabled value="Eventbased" {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'Eventbased' ? 'checked' : ''  }}>
                                                    Event based commencement
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12" id="FixedDate">
                                        <div class="form-group mt-3">
                                            <label>Fixed Date</label>
                                            <div class="clearfix row">
                                                <div class="col-sm-4"><input type="date" name="Duration[fixedDate]" class="form-control flatpickr" disabled value="{{ $contract->fixed_date }}" /></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12" id="Eventbased" style="display: none;">
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
                                                            <option value="other">Other with a text field for specifying the event Condition</option>
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
                                    </div>


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
                                                <label class="form-check-inline form-check"><input class="contractCommencementEffectiveDate form-check-input" type="radio" name="Duration[effectiveDate]" disabled value="evergreen" {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'evergreen' ? 'checked' : '' }}>Evergreen Contracts </label>
                                                <label class="form-check-inline form-check showinedit"> <input class="contractCommencementEffectiveDate form-check-input" type="radio" name="Duration[effectiveDate]" disabled value="termination" {{ decryptString($contract->end_contract_type, 'end_contract_type') == 'termination' ? 'checked' : '' }}>Termination</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-4" id="onetimeContract">
                                        <div class="form-group mt-3">
                                            <hr class="mt-0" />
                                            <h5>One time Contract</h5>
                                            <hr class="mt-0" />
                                            <div class="form-group">
                                                <label>End date of contract</label>
                                                <div class="clearfix">
                                                    <input type="date" name="Duration[onetimeEndDateofContract]" class="form-control flatpickr" disabled value="{{ $contract->onetime_end_date }}" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="col-sm-12 mt-2" id="fixedTerm" style="display: none;">
                                        <hr class="mt-3" />
                                        <h5 class="mt-2">Fixed Term Contract with Periodic Renewal</h5>

                                        <hr class="mt-3" />

                                        <div class="form-group row mt-2">
                                            <div class="row">
                                                <div class="form-group  col-sm-3 mt-2">
                                                    <label>End date of contract</label>
                                                    <div class="clearfix">
                                                        <input type="date" class="form-control flatpickr" name="Duration[fixedtimeEndDateofContract]" disabled value="{{ $contract->fixedterm_end_date }}" />
                                                    </div>

                                                </div>

                                                <div class="form-group  col-sm-5 mt-2">
                                                    <label>Type of Renewal</label>
                                                    <div class="clearfix">
                                                        <select class="form-control typerenewal" name="Duration[typeRenewal]" disabled>
                                                            <option value="{{ $contract->renewal_type }}">{{ decryptString($contract->renewal_type , 'renewal_type') }}</option>
                                                            <!--<option value="manualRenewal">Manual Renewal with notice</option>-->
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group  col-sm-4 mt-2">
                                                    <label>Period of auto renewal</label>
                                                    <div class="clearfix row">
                                                        <div class="col-sm-5"><input class="form-control" type="text" name="Duration[periodAutoRenewal]" disabled></div>
                                                        <div class="col-sm-7">
                                                            <select class="form-control" name="Duration[periodAutoRenewalPeriod]" disabled>
                                                                <option value="years">{{ decryptString($contract->period_auto_renewal_unit, 'period_auto_renewal_unit') }}</option>
                                                                <!--<option value="months">Months</option>-->
                                                                <!--<option value="days">Days</option>-->
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="form-group  col-sm-3 mt-2">
                                                    <label class="typerenewallable">Auto renewal Date:</label>
                                                    <div class="clearfix">
                                                        <input type="date" class="form-control flatpickr" name="Duration[autoRenewalDate]" value="{{ $contract->auto_renewal_date }}" disabled />
                                                    </div>
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
                                                <label>Condition for end of contract:</label>
                                                <div class="clearfix">
                                                    <select class="form-control conditionEndContract" name="Duration[conditionEndContract]" disabled>
                                                        <option value="mutually">{{ decryptString($contract->evergreen_condition, 'evergreen_condition') }}
                                                        </option>
                                                        <!--<option value="termination">When Termination Clause is triggered-->
                                                        <!--</option>-->
                                                        <!--<option value="delivered">When good are delivered/ project is completed/ milestone is achieved-->
                                                        <!--</option>-->
                                                        <!--<option value="others">others [specify]-->
                                                        <!--</option>-->
                                                    </select>
                                                </div>
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
                                        <input type="checkbox" class="form-check-input " id="Reminder" name="Duration[reminderEnable]" disabled />
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
                                                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-messages" aria-controls="navs-top-messages" aria-selected="false">Escalation</button>
                                                </li>
                                            </ul>

                                        </div>
                                        <!-- alert me on div comes -->


                                        <div class="tab-content">
                                            <div class="tab-pane fade show active" id="navs-top-home" role="tabpanel">
                                                <div class="row">
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <label> Alert Me about</label>
                                                            <select class="select2 form-select valid" name="Duration[Reminder][first][alertMe]" aria-invalid="false" disabled>
                                                                <option>{{ decryptString( $contract->reminder_first_alert,'reminder_first_alert' )}}</option>
                                                                <!--<option>Internal Due Date</option>-->
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group row">
                                                            <label class="">Alert Me on</label>
                                                            <?php $fristarl  = explode(" ", decryptString($contract->reminder_first_alertMeOn, 'reminder_first_alertMeOn')); ?>
                                                            <div class="col">
                                                                <input type="text" class="form-control" min="1" name="Duration[Reminder][first][alertMeDay]" value="{{ $fristarl[0]}}" disabled />
                                                            </div>
                                                            <div class="col">
                                                                <select class="select2 form-select col-sm-6" name="Duration[Reminder][first][alertMePrior]" disabled>
                                                                    <option value="days" {{$fristarl[1] == 'days' ? 'selected' : '' }}></option>
                                                                    <option value="months" {{$fristarl[1] == 'months' ? 'selected' : '' }}>Months</option>
                                                                    <option value="years" {{$fristarl[1] == 'years' ? 'selected' : '' }}>Years</option>
                                                                </select>
                                                            </div>
                                                            <div class="col">
                                                                <select class="select2 form-select  col-sm-6" name="Duration[Reminder][first][alertMeType]" disabled>
                                                                    <option value="prior" {{$fristarl[2] == 'prior' ? 'selected' : '' }}>Prior</option>
                                                                    <option value="after" {{$fristarl[2] == 'after' ? 'selected' : '' }}>After</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <label> Repeats</label>
                                                            <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][first][repeats]" aria-invalid="false" disabled>
                                                                <option>{{ decryptString($contract->reminder_first_alert_repeats, 'reminder_first_alert_repeats')}}</option>
                                                                <!--<option>Daily</option>-->
                                                                <!--<option>Every 3 days</option>-->
                                                                <!--<option>Weekly</option>-->
                                                                <!--<option>Fortnightly</option>-->
                                                                <!--<option>Monthly</option>-->
                                                                <!--<option>Never</option>-->
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="navs-top-profile" role="tabpanel">
                                                <div class="row">
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <label> Alert Me about</label>
                                                            <select class="select2 form-select valid" id="AlertMe" name="Duration[Reminder][second][alertMe]" aria-invalid="false" disabled>
                                                                <option>{{ decryptString($contract->reminder_second_alert,'reminder_second_alert' )}}</option>
                                                                <!--<option>Renewal/Internal Due Date</option>-->
                                                                <!--<option>Internal Due Date</option>-->
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <!--<div class="form-group row">-->
                                                        <!--    <label class=" ">Alert Me on</label>-->
                                                        <!--   <div class="col">-->
                                                        <!--        <input type="number" class="form-control" min="1" name="Duration[Reminder][second][alertMeDay]" />-->
                                                        <!--    </div>-->
                                                        <!--   <div class="col">-->
                                                        <!--        <select class="select2 form-select col-sm-6" name="Duration[Reminder][second][alertMePrior]">-->
                                                        <!--            <option value="days">Days</option>-->
                                                        <!--            <option value="months">Months</option>-->
                                                        <!--            <option value="years">Years</option>-->
                                                        <!--        </select>-->
                                                        <!--    </div>-->
                                                        <!--     <div class="col">-->
                                                        <!--        <select class="select2 form-select  col-sm-6" name="Duration[Reminder][second][alertMeType]">-->
                                                        <!--            <option value="prior">Prior</option>-->
                                                        <!--            <option value="prior">Prior</option>-->
                                                        <!--            <option value="after">After</option>-->
                                                        <!--        </select>-->
                                                        <!--    </div>-->
                                                        <!--</div>-->
                                                        <div class="form-group row">
                                                            <label class="">Alert Me on</label>
                                                            <?php $secondarl  = explode(" ", decryptString($contract->reminder_second_alertMeOn, 'reminder_second_alertMeOn')); ?>
                                                            <div class="col">
                                                                <input type="text" class="form-control" min="1" name="Duration[Reminder][second][alertMeDay]" value="{{ $secondarl[0]}}" disabled />
                                                            </div>
                                                            <div class="col">
                                                                <select class="select2 form-select col-sm-6" name="Duration[Reminder][second][alertMePrior]" disabled>
                                                                    <option value="days" {{$secondarl[1] == 'days' ? 'selected' : '' }}></option>
                                                                    <option value="months" {{$secondarl[1] == 'months' ? 'selected' : '' }}>Months</option>
                                                                    <option value="years" {{$secondarl[1] == 'years' ? 'selected' : '' }}>Years</option>
                                                                </select>
                                                            </div>
                                                            <div class="col">
                                                                <select class="select2 form-select  col-sm-6" name="Duration[Reminder][second][alertMeType]" disabled>
                                                                    <option value="prior" {{$secondarl[2] == 'prior' ? 'selected' : '' }}>Prior</option>
                                                                    <option value="after" {{$secondarl[2] == 'after' ? 'selected' : '' }}>After</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <label> Repeats</label>
                                                            <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][second][repeats]" aria-invalid="false" disabled>
                                                                <option>{{ decryptString($contract->reminder_second_alert_repeats, 'reminder_second_alert_repeats') }}</option>
                                                                <!--<option>Daily</option>-->
                                                                <!--<option>Every 3 days</option>-->
                                                                <!--<option>Weekly</option>-->
                                                                <!--<option>Fortnightly</option>-->
                                                                <!--<option>Monthly</option>-->
                                                                <!--<option>Never</option>-->
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="navs-top-messages" role="tabpanel">
                                                <div class="row">
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <label> Alert Me about</label>
                                                            <select class="select2 form-select valid" id="AlertMe" name="Duration[Reminder][escalation][alertMe]" aria-invalid="false" disabled>
                                                                <option>{{ decryptString($contract->reminder_escalation_alert, 'reminder_escalation_alert') }}</option>
                                                                <!--<option>Renewal/Internal Due Date</option>-->
                                                                <!--<option>Internal Due Date</option>-->
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <!--<div class="form-group row">-->
                                                        <!--    <label class=" ">Alert Me on</label>-->
                                                        <!--    <div class="col">-->
                                                        <!--        <input type="number" class="form-control"  min="1" name="Duration[Reminder][escalation][alertMeDay]" />-->
                                                        <!--    </div>-->
                                                        <!--    <div class="col">-->
                                                        <!--        <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMePrior]">-->
                                                        <!--            <option value="days">Days</option>-->
                                                        <!--            <option value="months">Months</option>-->
                                                        <!--            <option value="years">Years</option>-->
                                                        <!--        </select>-->
                                                        <!--    </div>-->
                                                        <!--    <div class="col">-->
                                                        <!--        <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMeType]">-->
                                                        <!--            <option value="prior">Prior</option>-->
                                                        <!--            <option value="after">After</option>-->
                                                        <!--        </select>-->
                                                        <!--    </div>-->
                                                        <!--</div>-->
                                                        <div class="form-group row">
                                                            <label class="">Alert Me on</label>
                                                            <?php $escalationarl  = explode(" ", decryptString($contract->reminder_escalation_alertMeOn, 'reminder_escalation_alertMeOn')); ?>
                                                            <div class="col">
                                                                <input type="text" class="form-control" min="1" name="Duration[Reminder][escalation][alertMeDay]" value="{{ $escalationarl[0]}}" disabled />
                                                            </div>
                                                            <div class="col">
                                                                <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMePrior]" disabled>
                                                                    <option value="days" {{$escalationarl[1] == 'days' ? 'selected' : '' }}></option>
                                                                    <option value="months" {{$escalationarl[1] == 'months' ? 'selected' : '' }}>Months</option>
                                                                    <option value="years" {{$escalationarl[1] == 'years' ? 'selected' : '' }}>Years</option>
                                                                </select>
                                                            </div>
                                                            <div class="col">
                                                                <select class="select2 form-select  col-sm-6" name="Duration[Reminder][escalation][alertMeType]" disabled>
                                                                    <option value="prior" {{$escalationarl[2] == 'prior' ? 'selected' : '' }}>Prior</option>
                                                                    <option value="after" {{$escalationarl[2] == 'after' ? 'selected' : '' }}>After</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <label> Repeats</label>
                                                            <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][escalation][repeats]" aria-invalid="false" disabled>
                                                                <option>{{ decryptString($contract->reminder_escalation_alert_repeats, 'reminder_escalation_alert_repeats')}}</option>
                                                                <!--<option>Daily</option>-->
                                                                <!--<option>Every 3 days</option>-->
                                                                <!--<option>Weekly</option>-->
                                                                <!--<option>Fortnightly</option>-->
                                                                <!--<option>Monthly</option>-->
                                                                <!--<option>Never</option>-->
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <h6 class="mt-4">Custom Fields</h6>
                                        <hr class="mt-0" />
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
                                Contract Value
                            </button>
                        </h2>
                        <div id="accordionWithIcon-4" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <hr class="mt-0" />
                                <div class="row g-3">

                                    <div class="card-body mt-2">
                                        <!--<h3 class="mt-3">Contract Value</h3>-->

                                        <div class="row mb-3">
                                            <div class="col-md-2">
                                                <label class="form-label" for="ContractValue">Contract Value</label>
                                                <select id="ContractValue" name="ContractValue[currency]" class="form-select select2"
                                                    data-allow-clear="true" disabled>
                                                    <!--@foreach (currency() as $currency)-->
                                                    <option value="{{ $currency }}">{{ decryptString($contract->currency, 'currency') }}</option>
                                                    <!--@endforeach-->
                                                </select>
                                                <p>The total monetary value of the contract.</p>
                                            </div>
                                            <div class="col-md-8"><label class="form-label" for="ContractValue"></label>
                                                <input type="number" class="form-control" placeholder="" name="ContractValue[value]"
                                                    id="ContractValue" value="{{ decryptString($contract->currency_value, 'currency_value') }}" disabled>
                                            </div>
                                        </div>

                                        <hr class="mt-3" />
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="PaymentSchedule">Payment Schedule <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" placeholder="" id="PaymentSchedule"
                                                    name="ContractValue[paymentSchedule]" value="{{ decryptString($contract->payment_schedule, 'payment_schedule') }}" disabled>
                                                <p>Details of payment milestones, amounts, and due dates.</p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="PaymentTerms">Payment Terms</label>
                                                <textarea class="form-control" id="PaymentTerms" name="ContractValue[paymentTerms]"
                                                    rows="3" disabled>{{ decryptString($contract->payment_terms, 'payment_terms') }}</textarea>
                                                <p>Terms and conditions governing payments, including payment methods any late payment
                                                    penalties.
                                                </p>
                                            </div>
                                        </div>
                                        <hr class="mt-3" />
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="formValidationSelect2">Billing Frequency</label>
                                                <select id="BillingFrequency" name="ContractValue[billingFrequency]" class="form-select select2"
                                                    data-allow-clear="true" disabled>
                                                    <option value="monthly">{{ decryptString($contract->billing_frequency, 'billing_frequency') }}</option>
                                                    <!--<option value="quarterly">Quarterly</option>-->
                                                    <!--<option value="annually">Annually</option>-->
                                                </select>
                                                <p>Frequency at which invoices are issued (e.g., monthly, quarterly, annually).</p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="Currencycontract">Taxes and Fees</label>
                                                <input type="text" class="form-control" placeholder="" id="Taxes" name="ContractValue[taxes]" value="{{ decryptString($contract->taxes, 'taxes') }}" disabled>
                                                <p>Any applicable taxes, fees, or surcharges associated with the contract.</p>
                                            </div>
                                        </div>
                                        <hr class="mt-3" />
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="Currencycontract">Escalation Clauses</label>
                                                <input type="text" class="form-control" placeholder="" id="EscalationClauses"
                                                    name="ContractValue[escalationClauses]" value="{{ decryptString($contract->escalation_clauses,'escalation_clauses') }}" disabled>
                                                <p>Provisions for adjusting contract prices over time based on predetermined factors such as
                                                    inflation or market fluctuations.
                                                </p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="Currencycontract">Discounts or Rebates</label>
                                                <input type="text" class="form-control" placeholder="" id="Discounts"
                                                    name="ContractValue[discounts]" value="{{ decryptString($contract->discounts, 'discounts') }}" disabled>
                                                <p>Any discounts or rebates applied to the contract.</p>
                                            </div>
                                        </div>
                                        <hr class="mt-3" />
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="Currencycontract">Retention or Holdbacks</label>
                                                <input type="text" class="form-control" placeholder="" id="Retention"
                                                    name="ContractValue[retention]" value="{{ decryptString($contract->retention, 'retention' )}}" disabled>
                                                <p>Amounts withheld from payments as retention or holdbacks pending completion of certain
                                                    milestones or obligations.
                                                </p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="Currencycontract">Payment Escrow</label>
                                                <input type="text" class="form-control" placeholder="" id="Payment"
                                                    name="ContractValue[payment_escrow]" value="{{ decryptString($contract->payment_escrow, 'payment_escrow' )}}" disabled>
                                                <p>Details of any funds held in escrow for payment security or dispute resolution purposes.</p>
                                            </div>
                                        </div>
                                        <hr class="mt-3" />
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="Currencycontract">Financial Guarantees or Bonds</label>
                                                <input type="text" class="form-control" placeholder="" id="Financial Guarantees"
                                                    name="ContractValue[financialGuarantees]" value="{{ decryptString($contract->financial_guarantees, 'financial_guarantees' )}}" disabled>
                                                <p>Information about any financial guarantees or bonds required under the contract.</p>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="Currencycontract">Currency Conversion</label>
                                                <input type="text" class="form-control" placeholder="" id="CurrencyConversion"
                                                    name="ContractValue[currencyConversion]" value="{{ decryptString($contract->currency_conversion, 'currency_conversion') }}" disabled>
                                                <p>Terms for currency conversion if the contract involves transactions in multiple currencies.
                                                </p>
                                            </div>
                                            <div class="col-12">
                                                <h6 class="mt-4">Custom Fields</h6>
                                                <hr class="mt-0" />
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
                                Contract Custom Fileds / Miscelleneous
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
                                                <hr>
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
                                                <td>{{ isset($contractsoldother->onetime_end_date) ? decryptString($contractsoldother->onetime_end_date,'onetime_end_date') : '' }}</td>
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
                                Previous Contracts
                            </button>
                        </h2>
                        <div id="accordionParentContract" class="accordion-collapse collapse">
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
                                            @foreach($contractsparentList as $contractsoldother)
                                            <tr>
                                                <td>
                                                    <a target="_blank" href="{{ url('/contracts/' . $contractsoldother->id) }}">
                                                        {{ isset($contractsoldother->contract_name) ? decryptString($contractsoldother->contract_name, 'contract_name') : '' }}
                                                    </a>
                                                </td>
                                                <td>{{ isset($contractsoldother->signing_date) ? decryptString($contractsoldother->signing_date,'signing_date') : '' }}</td>
                                                <td>{{decryptString($contractsoldother->currency,'currency')}} {{ isset($contractsoldother->currency_value) ? decryptString($contractsoldother->currency_value, 'currency_value') : '' }}</td>
                                                <td class="dateTd">{{ isset($contractsoldother->fixed_date) ? date('d-m-Y',strtotime(decryptString($contractsoldother->fixed_date,'fixed_date'))) : '' }}</td>
                                                <td class="dateTd">{{ isset($contractsoldother->onetime_end_date) ? date('d-m-Y',strtotime(decryptString($contractsoldother->onetime_end_date,'onetime_end_date'))) : '' }}</td>
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
                                            <th>Fixed Date</th>
                                            <th>Onetime end date</th>
                                        </thead>
                                        <tbody>
                                            @foreach($contractspartsList as $contractsoldother)
                                            <tr>
                                                <td>
                                                    <a target="_blank" href="{{ url('/contracts/' . $contractsoldother->id) }}">
                                                        {{ isset($contractsoldother->contract_name) ? decryptString($contractsoldother->contract_name, 'contract_name') : '' }}
                                                    </a>
                                                </td>
                                                <td>{{ isset($contractsoldother->signing_date) ? decryptString($contractsoldother->signing_date,'signing_date') : '' }}</td>
                                                <td>{{decryptString($contractsoldother->currency,'currency')}} {{ isset($contractsoldother->currency_value) ? decryptString($contractsoldother->currency_value, 'currency_value') : '' }}</td>
                                                <td class="dateTd">{{ isset($contractsoldother->fixed_date) ? date('d-m-Y',strtotime(decryptString($contractsoldother->fixed_date,'fixed_date'))) : '' }}</td>
                                                <td class="dateTd">{{ isset($contractsoldother->onetime_end_date) ? date('d-m-Y',strtotime(decryptString($contractsoldother->onetime_end_date,'onetime_end_date'))) : '' }}</td>
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
                            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-6" aria-expanded="false">
                                Contract Attachments
                            </button>
                        </h2>
                        <div id="accordionWithIcon-6" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                
                               
                                
                                <div class="card-body mt-3">
                                    <ul class="timeline mb-0">
                                        
                                        @foreach ($approvalsAttach as $key => $approvalsData)
                                        @if(!empty($approvalsData->attachments_filename))
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
                                                            {{ decryptString($approvalsData->username, 'username')}}

                                                            on <strong>{{date("d-M-Y", strtotime($approvalsData->created_at))}}</strong>
                                                        </p> 
                                                        <p>
                                                            <a href="{{ asset('storage/app/' . $approvalsData->attachments)}}" target="_blank">
                                                                {{$approvalsData->attachments_filename ? $approvalsData->attachments_filename :$approvalsData->attachments }}
                                                            </a>
                                                        </p>
                                                    </div>
                                                </div>

                                            </div>
                                        </li>
                                        @endif
                                        @endforeach
                                        <?php
                                       $url = asset('storage/app/'.$contract->contract_attachment);
                                        ?>

                                        @if(isset($contract->contract_attachment))
                                        <li class="timeline-item timeline-item-transparent">
                                            <span class="timeline-point timeline-point-info"></span>
                                            <div class="timeline-event">
                                                <div class="timeline-header mb-3">
                                                    <h6 class="mb-0">Contract Created {{date("d-M-Y", strtotime($contract->created_at))}}</h6>
                                                </div>
                                                <p class="mb-2 col-6">
                                                    <a href="{{ $url }}" target="_blank">
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
                </div>
            </div>
            <!--<div class="pt-4">-->
            <!--   <button type="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>-->
            <!--   <button type="reset" class="btn btn-label-secondary">Cancel</button>-->
            <!--</div>-->
            <div class="buy-now">
                <!--<a href="https://1.envato.market/vuexy_admin" target="_blank" class="btn btn-primary btn-buy-now waves-effect waves-light">Submit</a>-->

                <!--<button type="submit" class="btn-buy-now btn btn-primary me-sm-3 me-1 waves-effect waves-light">Submit</button>-->
            </div>
        </form>
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
@endsection
@section('footer')
@endsection