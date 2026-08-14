@extends('layouts/layoutMaster')

@section('title', 'Contracts View')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/quill/typography.scss',
'resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/quill/editor.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/dropzone/dropzone.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/tagify/tagify.scss'
])
@endsection

@section('vendor-script')

<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection

@section('page-script')
@vite([
'resources/assets/js/forms-selects.js',
'resources/assets/js/forms-tagify.js',
'resources/assets/js/forms-typeahead.js'
])

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>

<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contract.js"></script>

@endsection

@section('content')
<div class="col-sm-12">
    <h1>
        View Contract - {{ $contract->contract_name }} ({{ $contract->contract_status }})


    </h1>


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
                    <li class="nav-item "><a href="/contractsdemo/contract/{{ $contract->id }}">
                            <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                                data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                                aria-selected="true">Details</button>
                        </a></li>
                    <li class="nav-item "><a href="/contractsdemo/contract/{{ $contract->id }}?tab=edit">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                                aria-selected="false">Edit</button>
                        </a></li>
                    <li class="nav-item active"><a href="/contractsdemo/contract/{{ $contract->id }}?tab=timeline">
                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                                aria-selected="false">Approvals</button>
                        </a></li>
                         <li class="nav-item "><a href="/contractsdemo/contract/{{ $contract->id }}?tab=flow">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                                aria-selected="false">Flow</button>
                        </a></li>
                        
        @elseif (isset($_GET['tab']) && $_GET['tab'] == 'edit')

            <li class="nav-item "><a href="/contractsdemo/contract/{{ $contract->id }}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a>
            </li>
            <li class="nav-item active"><a href="/contractsdemo/contract/{{ $contract->id }}?tab=edit">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item "><a href="/contractsdemo/contract/{{ $contract->id }}?tab=timeline">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>
                   <li class="nav-item "><a href="/contractsdemo/contract/{{ $contract->id }}?tab=flow">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                                aria-selected="false">Flow</button>
                        </a></li>
                        
                        @elseif (isset($_GET['tab']) && $_GET['tab'] == 'flow')

            <li class="nav-item "><a href="/contractsdemo/contract/{{ $contract->id }}">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a>
            </li>
            <li class="nav-item "><a href="/contractsdemo/contract/{{ $contract->id }}?tab=edit">
                    <button type="button" class="nav-link " role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item "><a href="/contractsdemo/contract/{{ $contract->id }}?tab=timeline">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>
                   <li class="nav-item active"><a href="/contractsdemo/contract/{{ $contract->id }}?tab=flow">
                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                                aria-selected="false">Flow</button>
                        </a></li>

            @else
            <li class="nav-item active"><a href="/contractsdemo/contract/{{ $contract->id }}">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-home" aria-controls="navs-top-home"
                        aria-selected="true">Details</button>
                </a></li>

            <li class="nav-item active"><a href="/contractsdemo/contract/{{ $contract->id }}?tab=edit">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Edit</button>
                </a></li>
            <li class="nav-item active"><a href="/contractsdemo/contract/{{ $contract->id }}?tab=timeline">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-top-profile" aria-controls="navs-top-profile"
                        aria-selected="false">Approvals</button>
                </a></li>
                   <li class="nav-item "><a href="/contractsdemo/contract/{{ $contract->id }}?tab=flow">
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

<div class="card-body">
    <div class="row mb-3">
        <div class="col-md-12" id="addUpdatesDiv" style="display:none;margin-left:37px;">
            <div class="panel panel-primary">
                <div class="panel-heading" style="height: 40px;">
                    <button type="button" id="btn-close" class="btn-close pull-right" aria-label="Close"></button>
                </div>
                <form action="{{ 'updateApprovals' }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="panel-body">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nextActionItem">Next Action Date<span style="color:red"></span></label>
                                <input type="date" name="nextActionDate" placeholder="Enter Next Action"
                                    class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="nextActionItem">Next Action<span style="color:red"></span></label>
                                <input type="text" name="nextActionItem" placeholder="Enter Next Action"
                                    class="form-control">
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="contactId" placeholder="Enter Next Action"
                                    class="form-control" value="{{$contract->id}}">
                            </div>
                            <hr>

                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nextAction">Next Action Description</label>
                                <textarea rows="2" type="text" name="nextAction"
                                    placeholder="Enter Next Action Description" class="form-control"></textarea>
                            </div>
                            <div class="form-group mx-sm-3 mb-2">
                                <input id="myFile" name="file" type="file">
                            </div>

                        </div>
                    </div>

                    <div class="panel-footer" style="height: 50px;">
                        <button type="submit" id="btn_save_updates"
                            class="btn btn-success btn-sm pull-right">Update</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <div class="row mb-3">
        <div class="row">
            <!-- timeline item 1 left dot -->
            <!--<div class="col-auto text-center flex-column d-none d-sm-flex">-->
            <!--<div class="row h-50">-->
            <!--    <div class="col">&nbsp;</div>-->
            <!--    <div class="col">&nbsp;</div>-->
            <!--</div>-->
            <!--<h5 class="m-2">-->
            <!--    <span class="badge badge-pill bg-light border">&nbsp;</span>-->
            <!--</h5>-->
            <!--<div class="row h-50">-->
            <!--    <div class="col border-right">&nbsp;</div>-->
            <!--    <div class="col">&nbsp;</div>-->
            <!--</div>-->
            <!--</div>-->
            <!-- timeline item 1 event content -->
            <div class="col py-2">
                @foreach ($approvalsArr as $approvalsData)
                <div class="card">
                    <div class="card-body mt-3">

                        <div class="row mb-3 mt-3">
                            <div class="col-md-4 mt-3">
                                <div class="float-right text-muted">{{ $approvalsData->created_date }}</div>
                                <h5 class="card-title">{{ $approvalsData->next_action_item }}</h5>
                                <p class="card-text">{{ $approvalsData->next_action_description }}</p>
                            </div>
                            <div class="col-md-4 mt-3">
                                <span class="badge bg-info text-dark pull-right" style="padding: 9px;margin-top:10px;">
                                    Status : {{ $approvalsData->status }}</span>
                            </div>
                            @if ($loop->first)
                            <div class="col-md-4 mt-3">
                                <div class="pull-right">
                                    <div style="display: inline-block">
                                        <button type="button" id="btn_approval_next"
                                            class="btn btn-success btn-sm">{{$approvalsData->button_text}} - {{
                                            $approvalsData->status }}</button>
                                    </div>
                                    <div style="display: inline-block;">
                                        <button type="button" id="btn_approval_reject"
                                            class="btn btn-danger btn-sm">Send to Owner</button>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>
@elseif (isset($_GET['tab']) && $_GET['tab'] == 'flow')

@include('contract::contract.contractFlow')

@elseif (isset($_GET['tab']) && $_GET['tab'] == 'edit')
@include('contract::contract.viewDetailContractEdit')
@else

<div class="row my-4">
   <div class="col">
      <form class="row" id="createcontract" action="store/contract" method="POST" enctype="multipart/form-data">
          <div class="col-md mb-4 mb-md-2">
            <div class="accordion mt-3" id="accordionWithIcon">
                <div class="card accordion-item active">
                   <div class="card-header">
                     <label class="form-check-label">Contract</label>
                     <div class="col mt-2">
                         
                        <div class="form-check form-check-inline">
                           <label class="form-check-label">
                           <input type="radio" class="contractmode form-check-input" name="contractMode" value="new" {{ $contract->contract_mode == 'new' ? 'checked' : '' }}
>
                           New</label>
                        </div>
                        <div class="form-check form-check-inline">
                           <label class="form-check-label">
                           <input type="radio" class="contractmode form-check-input" name="contractMode" value="old" {{ $contract->contract_mode == 'old' ? 'checked' : '' }}
> 
                           Legacy Contracts </label>
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
                                 <option value="">{{ $contract->contract_type }}</option>
                                 <!--@foreach ($contractTypes as $contractType)-->
                                 <!--<option value="{{ $contractType->contract_type_id }}">-->
                                 <!--   {{ $contractType->contract_type }}-->
                                 <!--</option>-->
                                 <!--@endforeach-->
                              </select>
                           </div>
                           <div class="col-md-6">
                              <!--<label class="form-label" for="ecommerce-product-barcode">Contract Type</label>-->
                              <label class="form-label" for="signatory">Signatory <span class="text-danger">*</span></label>
                              <!--<select id="signatory" name="BasicContract[signatory]" class="form-select select2" data-allow-clear="true">-->
                              <!--<option value="">Select Signatory</option>-->
                              <!--<option value="Test">Test</option>-->
                              <!--<option value="Demo">Demo</option>-->
                              <!--</select>    -->
                              <select class="form-select select2 " name="BasicContract[signatory]"
                                 id="signatory" disabled>
                                 <option value="">-Select Signatory-</option>
                                 @foreach ($users as $user)
                                 <option value="{{ $user->id }}">
                                    {{ $user->FirstName }}
                                 </option>
                                 @endforeach
                              </select>
                           </div>
                           <div class="col-md-6">
                              <label class="form-label" for="catgoeryType">Department <span class="text-danger">*</span></label>
                              <select id="catgoeryType" name="BasicContract[DepartmentType]"
                                 class="form-select select2" data-allow-clear="true" disabled>
                                 <option value="">{{ $contract->department_id }}</option>
                                 
                              </select>
                           </div>
                           <div class="col-md-6">
                              <label class="form-label" for="DepartmentType">Category <span class="text-danger">*</span></label>
                              <select id="DepartmentType" name="BasicContract[catgoeryType]"
                                 class="form-select select2 DepartmentType" data-allow-clear="true" disabled>
                                 <option value="">{{ $contract->catgoery_id }}</option>
                              </select>
                           </div>
                           <div class="col-md-6">
                               <label class="form-label" for="contractDescription">Contract
                               Description</label>
                               <textarea class="form-control" id="contractDescription"
                                  name="BasicContract[contractDescription]" rows="5" disabled>{{ $contract->contract_description }}</textarea>
                            </div>
                            
                            <!--<div class="col-12">-->
                            <!--    <h6 class="mt-4">Custom Fields</h6>-->
                            <!--    <hr class="mt-0" />-->
                            <!--</div>-->
                            <!--<div class="row mb-3">-->
                            <!--    @include('contract::contract.viewCustomField', ['categoryId' => 1]) -->
                            <!--</div>-->

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
                         @include('contract::contract.partyDetailsView', ['paryda', $contractPartys])
                        <hr class="mt-1" />
                        <div class="row g-3">
                            
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
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-3" aria-expanded="false">
                     Contract Duration
                     </button>
                  </h2>
                  <div id="accordionWithIcon-3" class="accordion-collapse collapse">
                     <div class="accordion-body">
                        <hr class="mt-1" />
                        
                        <div class="form-group signing_date">
                            <h5>Signing Date</h5>
                            <!--<label>Date</label>-->
                            <div class="clearfix row">
                                <div class="col-sm-4"><input type="date" name="Duration[signingDate]" class="form-control flatpickr" value="{{ $contract->signing_date }}"/></div>
                            </div>
                            <div class="clearfix">
                                <small class="form-text text-muted">The date on which the contract is signed by all parties involved. This may or may not be the same as the effective date, depending on the terms of the contract.
                                </small>
                            </div>
                        </div>
                        <div class="">
                            <div class="col-sm-12">
                                <div class="form-group mt-3">
                                    <h5>Contract Commencement</h5>
                                    <hr class="mt-0" />
                                    <label>Effective date:</label>
                                    <div class="clearfix mt-2">
                                        <label class="form-check-inline form-check">
                                            <input type="radio" class="form-check-input commencementDate"  name="Duration[commencementDate]" value="FixedDate" {{ $contract->end_contract_type == 'onetimeContract' ? 'checked' : '' }}>
                                            Fixed Date
                                        </label>
                                        <label class="form-check-inline form-check">
                                            <input class="form-check-input commencementDate" type="radio" name="Duration[commencementDate]" value="Eventbased" {{ $contract->end_contract_type == 'Eventbased' ? 'checked' : '' }}>
                                            Event based commencement
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12" id="FixedDate">
                                <div class="form-group mt-3">
                                    <label>Fixed Date</label>
                                    <div class="clearfix row">
                                        <div class="col-sm-4"><input type="date" name="Duration[fixedDate]" class="form-control flatpickr" value="{{ $contract->fixed_date }}"/></div>
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
                <label class="form-check-inline form-check"><input type="radio" class="contractCommencementEffectiveDate form-check-input" name="Duration[effectiveDate]" value="onetimeContract" {{ $contract->end_contract_type == 'onetimeContract' ? 'checked' : '' }}>One time Contract</label>
                <label class="form-check-inline form-check"><input class="contractCommencementEffectiveDate form-check-input" type="radio" name="Duration[effectiveDate]" value="fixedTerm" {{ $contract->end_contract_type == 'fixedTerm' ? 'checked' : '' }}>Fixed Term Contract with Periodic Renewal</label>
                <label class="form-check-inline form-check"><input class="contractCommencementEffectiveDate form-check-input" type="radio" name="Duration[effectiveDate]" value="evergreen" {{ $contract->end_contract_type == 'evergreen' ? 'checked' : '' }}>Evergreen Contracts </label>
                <label class="form-check-inline form-check showinedit"> <input class="contractCommencementEffectiveDate form-check-input" type="radio" name="Duration[effectiveDate]" value="termination" {{ $contract->end_contract_type == 'termination' ? 'checked' : '' }}>Termination</label>
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
                        <input type="date" name="Duration[onetimeEndDateofContract]" class="form-control flatpickr" value="{{ $contract->onetime_end_date }}"/>
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
                        <input type="date" class="form-control flatpickr" name="Duration[fixedtimeEndDateofContract]" value="{{ $contract->fixedterm_end_date }}"/>
                    </div>

                </div>

                <div class="form-group  col-sm-5 mt-2">
                    <label>Type of Renewal</label>
                    <div class="clearfix">
                        <select class="form-control typerenewal" name="Duration[typeRenewal]" disabled>
                            <option value="{{ $contract->renewal_type }}">{{ $contract->renewal_type }}</option>
                            <!--<option value="manualRenewal">Manual Renewal with notice</option>-->
                        </select>
                    </div>
                </div>
                <div class="form-group  col-sm-4 mt-2">
                    <label>Period of auto renewal</label>
                    <div class="clearfix row">
                        <div class="col-sm-5"><input class="form-control" type="text" name="Duration[periodAutoRenewal]"></div>
                        <div class="col-sm-7">
                            <select class="form-control" name="Duration[periodAutoRenewalPeriod]" disabled>
                                <option value="years">{{ $contract->period_auto_renewal_unit }}</option>
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
                        <input type="date" class="form-control flatpickr" name="Duration[autoRenewalDate]" value="{{ $contract->auto_renewal_date }}"/>
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
                    <select class="form-control conditionEndContract" name="Duration[conditionEndContract]">
                        <option value="mutually">{{ $contract->evergreen_condition }}
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
                     <input type="text" style="display: none;" id="conditionEndContractOthers" class="form-control" name="Duration[conditionEndContractOthers]">
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
                        <input type="date" class="form-control" name="Duration[terminationDate]" />
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="form-group  col-sm-6">
                    <label>Reason for termination</label>
                    <div class="clearfix">
                        <select class="form-control" name="Duration[reasonTermination]">
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
            <input type="checkbox" class="form-check-input " id="Reminder" name="Duration[reminderEnable]" />
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
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-top-home" role="tabpanel">
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Alert Me about</label>
                        <select class="select2 form-select valid" id="AlertMe" name="Duration[Reminder][first][alertMe]" aria-invalid="false" disabled>
                            <option>{{ $contract->reminder_first_alert }}</option>
                            <!--<option>Internal Due Date</option>-->
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class="">Alert Me on</label>
                         <div class="col">
                            <input type="text" class="form-control" min="1" name="Duration[Reminder][first][alertMeDay]" value="{{ $contract->reminder_first_alertMeOn }}"/>
                        </div>
                         <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][first][alertMePrior]" disabled>
                                <option value="days">{{ $contract->reminder_first_alert_repeats }}</option>
                                <!--<option value="months">Months</option>-->
                                <!--<option value="years">Years</option>-->
                            </select>
                        </div>
                        <div class="col">
                            <select class="select2 form-select  col-sm-6" name="Duration[Reminder][first][alertMeType]">
                                <option value="prior">{{ $contract->reminder_second_alert }}</option>
                                <!--<option value="prior">Prior</option>-->
                                <!--<option value="after">After</option>-->
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Repeats</label>
                        <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][first][repeats]" aria-invalid="false" disabled>
                            <option>{{ $contract->reminder_first_alert_repeats }}</option>
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
                            <option>{{ $contract->reminder_second_alert }}</option>
                            <!--<option>Renewal/Internal Due Date</option>-->
                            <!--<option>Internal Due Date</option>-->
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class=" ">Alert Me on</label>
                       <div class="col">
                            <input type="number" class="form-control" min="1" name="Duration[Reminder][second][alertMeDay]" />
                        </div>
                       <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][second][alertMePrior]">
                                <option value="days">Days</option>
                                <option value="months">Months</option>
                                <option value="years">Years</option>
                            </select>
                        </div>
                         <div class="col">
                            <select class="select2 form-select  col-sm-6" name="Duration[Reminder][second][alertMeType]">
                                <option value="prior">Prior</option>
                                <option value="prior">Prior</option>
                                <option value="after">After</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Repeats</label>
                        <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][second][repeats]" aria-invalid="false" disabled>
                            <option>{{ $contract->reminder_second_alert_repeats }}</option>
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
                            <option>{{ $contract->reminder_escalation_alert }}</option>
                            <!--<option>Renewal/Internal Due Date</option>-->
                            <!--<option>Internal Due Date</option>-->
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label class=" ">Alert Me on</label>
                        <div class="col">
                            <input type="number" class="form-control"  min="1" name="Duration[Reminder][escalation][alertMeDay]" />
                        </div>
                        <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMePrior]">
                                <option value="days">Days</option>
                                <option value="months">Months</option>
                                <option value="years">Years</option>
                            </select>
                        </div>
                        <div class="col">
                            <select class="select2 form-select col-sm-6" name="Duration[Reminder][escalation][alertMeType]">
                                <option value="prior">Prior</option>
                                <option value="after">After</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label> Repeats</label>
                        <select class="select2 form-select valid" id="Repeats" name="Duration[Reminder][escalation][repeats]" aria-invalid="false" disabled>
                            <option>{{ $contract->reminder_escalation_alert_repeats }}</option>
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
                            
                          <div class="card-body">
                               <!--<h3 class="mt-3">Contract Value</h3>-->
                               
                               <div class="row mb-3">
                                  <div class="col-md-2">
                                     <label class="form-label" for="ContractValue">Contract Value</label>
                                     <select id="ContractValue" name="ContractValue[currency]" class="form-select select2"
                                        data-allow-clear="true" disabled>
                                        <!--@foreach (currency() as $currency)-->
                                        <option value="{{ $currency }}">{{ $contract->currency }}</option>
                                        <!--@endforeach-->
                                     </select>
                                     <p>The total monetary value of the contract.</p>
                                  </div>
                                  <div class="col-md-4"><label class="form-label" for="ContractValue"></label>
                                     <input type="number" class="form-control" placeholder="" name="ContractValue[value]"
                                        id="ContractValue" value="{{ $contract->currency_value }}" disabled>
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="PaymentSchedule">Payment Schedule <span class="text-danger">*</span></label>
                                     <input type="text" class="form-control" placeholder="" id="PaymentSchedule"
                                        name="ContractValue[paymentSchedule]" value="{{ $contract->payment_schedule }}" disabled>
                                     <p>Details of payment milestones, amounts, and due dates.</p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-2">
                                     <label class="form-label" for="Currencycontract">Currency of the contract</label>
                                     <select id="Currencycontract" name="ContractValue[currencyContract]" class="form-select select2"
                                        data-allow-clear="true" disabled>
                                        <!--@foreach (currency() as $currency)-->
                                        <option value="{{ $currency }}">{{ $contract->currency_contract }}</option>
                                        <!--@endforeach-->
                                     </select>
                                     <p>The total monetary value of the contract.</p>
                                  </div>
                                  <div class="col-md-8">
                                     <label class="form-label" for="PaymentSchedule">Payment Terms</label>
                                     <textarea class="form-control" id="PaymentSchedule" name="ContractValue[paymentSchedule]"
                                        rows="3" disabled>{{ $contract->currency_contract }}</textarea>
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
                                        <option value="monthly">{{ $contract->billing_frequency }}</option>
                                        <!--<option value="quarterly">Quarterly</option>-->
                                        <!--<option value="annually">Annually</option>-->
                                     </select>
                                     <p>Frequency at which invoices are issued (e.g., monthly, quarterly, annually).</p>
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="Currencycontract">Taxes and Fees</label>
                                     <input type="text" class="form-control" placeholder="" id="Taxes" name="ContractValue[taxes]" value="{{ $contract->taxes }}" disabled>
                                     <p>Any applicable taxes, fees, or surcharges associated with the contract.</p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-6">
                                     <label class="form-label" for="Currencycontract">Escalation Clauses</label>
                                     <input type="text" class="form-control" placeholder="" id="EscalationClauses"
                                        name="ContractValue[escalationClauses]" value="{{ $contract->escalation_clauses }}" disabled>
                                     <p>Provisions for adjusting contract prices over time based on predetermined factors such as
                                        inflation or market fluctuations.
                                     </p>
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="Currencycontract">Discounts or Rebates</label>
                                     <input type="text" class="form-control" placeholder="" id="Discounts"
                                        name="ContractValue[discounts]" value="{{ $contract->escalation_clauses }}" disabled>
                                     <p>Any discounts or rebates applied to the contract.</p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-6">
                                     <label class="form-label" for="Currencycontract">Retention or Holdbacks</label>
                                     <input type="text" class="form-control" placeholder="" id="Retention"
                                        name="ContractValue[Retention]" value="{{ $contract->retention }}" disabled>
                                     <p>Amounts withheld from payments as retention or holdbacks pending completion of certain
                                        milestones or obligations.
                                     </p>
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="Currencycontract">Payment Escrow</label>
                                     <input type="text" class="form-control" placeholder="" id="Payment"
                                        name="ContractValue[payment]" value="{{ $contract->payment_escrow }}" disabled>
                                     <p>Details of any funds held in escrow for payment security or dispute resolution purposes.</p>
                                  </div>
                               </div>
                               <hr class="mt-3" />
                               <div class="row mb-3">
                                  <div class="col-md-6">
                                     <label class="form-label" for="Currencycontract">Financial Guarantees or Bonds</label>
                                     <input type="text" class="form-control" placeholder="" id="Financial Guarantees"
                                        name="ContractValue[financialGuarantees]" value="{{ $contract->payment_escrow }}" disabled>
                                     <p>Information about any financial guarantees or bonds required under the contract.</p>
                                  </div>
                                  <div class="col-md-4">
                                     <label class="form-label" for="Currencycontract">Currency Conversion</label>
                                     <input type="text" class="form-control" placeholder="" id="CurrencyConversion"
                                        name="ContractValue[currencyConversion]" value="{{ $contract->currency_conversion }}" disabled>
                                     <p>Terms for currency conversion if the contract involves transactions in multiple currencies.
                                     </p>
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
                            
                            <div class="card-body">

                               <div class="panel panel-default">

                                  <div class="panel-collapse">
                                     <div class="panel-body">
                                        <div class="col-sm-12">
                                           @include('contract::contract.viewCustomField', ['categoryId' => 4])
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
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-6" aria-expanded="false">
                     Contract Attachments
                     </button>
                  </h2>
                  <div id="accordionWithIcon-6" class="accordion-collapse collapse">
                     <div class="accordion-body">
                         <hr class="mt-0" />
                        <div class="col-12 col-lg-8">
                          <!-- Media -->
                          <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                              <h5 class="mb-0 card-title">Media</h5>
                              <a href="javascript:void(0);" class="fw-medium">Add media from URL</a>
                            </div>
                            <div class="card-body">
                              <form action="/upload" class="dropzone needsclick" id="dropzone-basic">
                                <div class="dz-message needsclick">
                                  <p class="fs-4 note needsclick pt-3 mb-1">Drag and drop your image here</p>
                                  <p class="text-muted d-block fw-normal mb-2">or</p>
                                  <span class="note needsclick btn bg-label-primary d-inline" id="btnBrowse">Browse image</span>
                                </div>
                                <div class="fallback">
                                  <input name="file" type="file" />
                                </div>
                           
                            </div>
                          </div>
                          <!-- /Media -->
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
      
       <button type="submit" class="btn-buy-now btn btn-primary me-sm-3 me-1 waves-effect waves-light">Submit</button>
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