 
        
<div class="modal fade" id="modalCenter" tabindex="-1" aria-hidden="true" role="dialog" style="display: none;">
    <!--<div class="modal-onboarding modal fade animate__animated " id="onboardImageModal" tabindex="-1" aria-modal="true" role="dialog" style="display: block;">-->
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="modalCenterTitle">Approval Process</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
      
          
          <form id="ApprovalProcessPopup" method="POST" enctype="multipart/form-data">              
             
          
          <table class="table misgtable" style="display:none">
            {{-- This table is EMPTY ON PURPOSE. Do not wire a variable into it without reading
                 the whole note.

                 The loop used to read $reqfields, which nothing in the repo sets, so
                 ?tab=timelineedit returned HTTP 500 on "Undefined variable $reqfields" - on main
                 as well, so this tab has been dead for everyone. Ticket 03 added the ?? [] guard,
                 which is what makes the tab render at all.

                 $reqfieldsText looks like the variable it was renamed to and the dev confirmed
                 that on 2026-08-22, so the loop briefly read it. That was reverted the same day,
                 because wiring it up does more than fill a table:

                 contract.js:1272 and :1306 count the rows in this table and DISABLE the Send
                 button on "Send For Approval" and "Send For Signing". The gate has never run for
                 anybody, because the tab holding it returned 500. Filling the table switches a
                 dead gate on.

                 And the test below is wrong for part of the map. ContractController adds a row to
                 $reqfieldsText for every required custom field, keyed by custom_field_id. A custom
                 field value lives in custom_field_data, not on the contracts row, so
                 $contract->$key is null and the field always reads "Missing" - proved on contract
                 16, whose custom field 57 holds a value. So the gate would block Send for
                 contracts that are complete.

                 The controller already computes the right answer: $reqfieldsVal[$key] is the
                 boolean "is this field satisfied", including the signing_date rule. A correct gate
                 reads that, not $contract->$key. That is functional work on the approval flow, not
                 performance work, so it is its own ticket - see
                 .scratch/contract-detail-page-perf/issues/24-approval-gate.md --}}
            @foreach (($reqfields ?? []) as $key => $label)
                @empty($contract->$key)
                    <tr>
                        <td class="text-danger">{{ $label }}</td>
                        <td class="text-danger">Missing</td>
                    </tr>
                @endempty
            @endforeach
        </table>

        <div class="modal-body">
           
            <div class="row">
            <div class="col mb-4">
                <label for="nameWithTitle" class="form-label">Short Description</label>
                <input type="text" id="shortDescrip" class="form-control" placeholder="Enter Short Description">
            </div>
            </div>
            <div class="row g-4">
            <div class="col mb-4">
                <label for="emailWithTitle" class="form-label">Description</label>
                <textarea class="form-control" id="ReviewDescription" rows="3"></textarea>
            </div>
            
            </div>
            <div class="row g-4">
            <div class="col mb-4">
                <div class="input-group">
                    <!--<input type="file" class="form-control" id="reviewFile">-->
                    <!--<label class="input-group-text" for="reviwFile">Upload</label>-->
                    
                    <div class="form-group mfiles" style="margin-top: 19px;">
                        <input class="myFilenew" name="photos[]" type="file">
                    </div>

                    <table id="fileListnew" class="table">
                        <tr>
                            <td colspan="2">
                                <h5>Attachments</h5>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            </div>

        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">Close</button>
            <button type="submit" id="paperIcon" class="btn btn-primary waves-effect waves-light">Send</button>
        </div>
         
        <div class="misgtablenote" style="display:none; text-align:center;margin: 0 1rem;"><div class="alert alert-danger" role="alert">Before submit the form, please fill the reqired values</div></div>
        </form>
        </div>
    </div>
</div>


@foreach ($approvalsArr as $key => $approvalsData)

<input type="hidden" id="curAppStatus" class="form-control" value="{{$approvalsData[0]->status}}">
<input type="hidden" id="appRowId" class="form-control" value="{{$approvalsData[0]->id}}">
@if (count($approvalsData) == 1 && $approvalsData[0]->status != 'Signing' && $approvalsData[0]->status != 'review' && $approvalsData[0]->status != 'Approval')

@if ($approvalsData[0]->status == 'Draft' && $approvalsData[0]->previous_status == 'Draft'
&& $approvalsData[0]->flag == 1 )

<div class="row mb-6 g-6">
    <div class="col-lg-12" style="margin-top: 20px;">
        <div class="card h-100">
            <form id="approvalForm">
                <button type= "button" class=" btn btn-outline-primary text-nowrap d-inline-flex position-relative me-4 waves-effect mt-3"
                    style="margin-left: 15px;">
                    <!-- {{ $approvalsData[0]->status }} - {{ $contract->substatus }} -->
                    {{ $contract->contract_status }} - {{ $contract->substatus }}
                </button>
                <div class="divider">
                        <div class="divider-text" style ="font-size:18px !important"><b>{{ $approvalsData[0]->username }}</b></div>
                </div>
                <div class="card-body mt-3">
                    <!-- <h5 class ="text-center"> {{ $approvalsData[0]->username }} </h5> -->
                    
                    
                    <!-- <button type= "button" class=" btn btn-outline-danger text-nowrap d-inline-flex position-relative me-4 waves-effect">
                        {{ $contract->substatus }}
                    </button> -->
                    

                    <div class="d-flex flex-column flex-sm-row justify-content-between text-center gap-6">
                        <div class="d-flex flex-column align-items-center">

                        </div>
                        <div class="d-flex flex-column align-items-center" style="cursor: pointer;">
                            <span class="p-4 border-1 border-primary rounded-circle border-dashed mb-0 w-px-75 h-px-75" style="border-color: #148000 !important;">
                            <i class="fa-solid fa-circle-check" id="modalPopUpReview" 
                            style="margin-left: -3px;font-size:xx-large;color: #4CAF50;margin-top: -4px;"> </i>
                                <!-- <i class="fa-solid fa-circle-check" data-bs-toggle="modal" data-bs-target="#modalCenter" 
                                        style="margin-left: -3px;font-size:xx-large;color: #4CAF50;margin-top: -4px;"> </i> -->
                                <!-- <i class="fa-solid fa-circle-check" id = "paperIcon" data-bs-toggle="modal" data-bs-target="#modalCenter" style="margin-left: -3px;font-size:xx-large;color: #4CAF50;margin-top: -4px;"> </i> -->
                                <!-- <i class="fa-solid fa-circle-check" data-bs-toggle="offcanvas" data-bs-target="#addEventSidebar" aria-controls="addEventSidebar" style="margin-left: -3px;font-size:xx-large;color: #4CAF50;margin-top: -4px;"> </i> -->
                                <!-- </a> -->
                            </span>
                            
                            <p style="color: #4CAF50;" class="my-2">Click Icon to Send For review</p>
                        </div>
                        <div class="d-flex flex-column align-items-center">

                        </div>
                        <div class="d-flex flex-column align-items-center" style="cursor: pointer;">
                            <span class="p-4 border-1 border-primary rounded-circle border-dashed mb-0 w-px-75 h-px-75" 
                            style="border-color: firebrick !important;">
                                <!-- <i class="fa-solid fa-circle-check" id="paperIconNegaotition" 
                                    style="font-size:xx-large;color:firebrick;margin-top: -4px;margin-left:-3px"> </i> -->
                                    <!-- <i class="fa-solid fa-circle-check" data-bs-toggle="modal" data-bs-target="#modalCenter" 
                                    style="margin-left: -3px;font-size:xx-large;color:firebrick;margin-top: -4px;"> </i> -->
                                    <i class="fa-solid fa-circle-check" id = "modalPopUpNegotiation" 
                                    style="margin-left: -3px;font-size:xx-large;color:firebrick;margin-top: -4px;"> </i>

                                <!-- </a> -->
                            </span>
                            <p style="color:firebrick;" class="my-2">Click Icon to Send For Negotiation</p>
                        </div>
                        <div class="d-flex flex-column align-items-center">

                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>
@elseif($approvalsData[0]->flag == 1 && ($approvalsData[0]->status != 'Signing' && $approvalsData[0]->previous_status != 'Approved'))
<div class="row mb-6 g-6">
    <div class="col-lg-12" style="margin-top: 25px;">
        <div class="card h-100">
            <form id="approvalForm">
                <button type= "button" class=" btn btn-outline-info text-nowrap d-inline-flex position-relative me-4 waves-effect mt-3"
                    style="margin-left: 15px;">
                    <!-- {{ $approvalsData[0]->status }} - {{ $contract->substatus }} -->
                    {{ $contract->contract_status }} - {{ $contract->substatus }}
                </button>
                <div class="divider">
                        <div class="divider-text" style ="font-size:18px !important"><b>{{ $approvalsData[0]->username }}</b></div>
                </div>

                <div class="card-body" style="margin-top: 0;    ">
                    <!-- <button type="button" class="btn btn-label-warning btnStatus text-nowrap d-inline-flex position-relative me-4">
                        {{ $approvalsData[0]->status }}
                    </button> -->
                    <!-- <button type= "button" class=" btn btn-outline-info text-nowrap d-inline-flex position-relative me-4 waves-effect">
                        {{ $approvalsData[0]->status }} - {{ $contract->substatus }}
                    </button> -->

                    <div class="d-flex flex-column flex-sm-row justify-content-between text-center gap-6" style="cursor: pointer;">
                        <div class="d-flex flex-column align-items-center">

                        </div>
                        <div class="d-flex flex-column align-items-center">
                            <span class="p-4 border-1 border-primary mb-0 w-px-75 h-px-75">
                                <!-- <i class="fa-solid fa-circle-check" id="paperIcon" 
                                style="margin-left: -5px;font-size:xx-large;color: #4CAF50;margin-top: -4px;"> </i> -->
                                <i class="fa-solid fa-circle-check" id = "modalPopUpApproval" 
                                style="margin-left: -5px;font-size:xx-large;color: #4CAF50;margin-top: -4px;"> </i>
                                <!-- <i class="fa-solid fa-paper-plane" id = "paperIcon" style="margin-left: -5px;font-size:xx-large;color: #7367f0;margin-top: -4px;">
                                    </i> -->
                            </span>
                            @if ($approvalsData[0]->status == 'Negotiation')
                            <p style="color: #4CAF50;" class="my-2">Send For Approval</p>
                            @elseif($approvalsData[0]->status == 'Approved')
                            <p style="color: #4CAF50;" class="my-2">Send For Signing</p>
                            @endif
                        </div>
                        @php
                            if($approvalsData[0]->status == 'Negotiation'){
                                $other_status = 'Signing';
                            }else{
                                $other_status = '';
                            }
                        @endphp
                        <div class="d-flex flex-column align-items-center">
                            <span class="p-4 border-1 border-primary mb-0 w-px-75 h-px-75">
                                <!-- <i class="fa-solid fa-times-circle" id="paperIconReject" -->
                                <i class="fa-solid fa-times-circle" id = "modalPopUpReviewBack"
                                    style="margin-left: -5px;font-size:xx-large;color: #f44336;margin-top: -4px;"> </i>

                            </span>
                            <!-- <p style="color: #f44336;" class="my-2">Send to owner</p> -->
                            <p style="color: #f44336;" class="my-2">Send For {{ $approvalsData[0]->previous_status }}</p>

                        </div>
                        @if ($approvalsData[0]->status != 'Approved' &&  $approvalsData[0]->status != 'Signing')
                        <div class="d-flex flex-column align-items-center">
                            <span class="p-4 border-1 border-primary mb-0 w-px-75 h-px-75">
                                
                                <!-- <i class="fa-solid fa-pen-clip" id="iconSign" -->
                                <i class="fa-solid fa-pen-clip" id="modalPopUpSign"
                                    style="margin-left: -5px;font-size:xx-large;color: #00008b;margin-top: -4px;"> </i>
                            </span>
                            
                                <p style="color: #00008b;" class="my-2">Send For {{ $other_status }}</p>
                           
                        </div>
                        @endif
                        <div class="d-flex flex-column align-items-center">

                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>
@endif
@else
@if($approvalsData[0]->flag == 1)
<div class="card-body mt-4">

    <div class="row mb-3">
        <div class="row">
            <div class="col py-2">
                <div class="col-xl-12 mb-6 mb-xl-0">
                    <div class="card">
                        @php
                        $lastAttachment = "";
                        @endphp
                        @foreach ($approvalsData as $approvalsValues)
                        @if($loop->first )
                        
                        <div class="card-header">
                            <!-- <p class="text-uppercase"> <span class="badge bg-info text-dark">
                                    STATUS :- {{ $approvalsValues->status }}
                                </span></p> -->
                                <button type="button" class="btn btn-outline-dribbble waves-effect text-uppercase"> 
                                <!-- <i class="fa-solid fa-r"></i> -- -->
                                {{ $contract->contract_status }} - {{ $contract->substatus }}
                                 <!-- {{ $approvalsData[0]->status }} - {{ $contract->substatus }}  -->
                                </button>
                                <!-- <button type= "button" class=" btn btn-outline-warning text-nowrap d-inline-flex position-relative me-4 waves-effect text-uppercase">
                                    {{ $approvalsData[0]->status }} - {{ $contract->substatus }}
                                </button> -->
                            <hr>
                        </div>
                        @endif
                        <div class="card-body">
                            <ul class="timeline mb-0 test-time-line">
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
                                            <!-- @if ($approvalsValues->approval_status == 'approved' || $approvalsValues->approval_status == 'rejected')
                                            <i class="fa fa-eye editView" value="{{ $loop->index }}" style="cursor: pointer;"></i>
                                            <i class="fa fa-eye editViewClose" value="{{ $loop->index }}" style="cursor: pointer; display: none;"></i>
                                            @endif -->

                                            <!-- <small class="text-muted">{{ $approvalsValues->created_at }}</small> -->
                                        </div>
                                        @if ($approvalsValues->approval_status == 'approved')
                                        <p class="mb-3">
                                            Short Description : {{ $approvalsValues->next_action_item }}
                                        </p>
                                        <p class="mb-3">
                                           Description :  {{ $approvalsValues->next_action_description }}
                                        </p>
                                        <p class="mb-3">
                                            
                                            @php
                                            $attachments = json_decode($approvalsValues->attachments);
                                            $lastAttachment = end($attachments);
                                            @endphp
                                           
                                           @if(isset($attachments))
                                           @foreach ($attachments as $file)
                                                <a href="{{ fileViewUrl($file->path) }}" target="_blank">{{ $file->name }}</a><br>
                                           @endforeach
                                           @endif
                                                
                                        </p>
                                        @endif
                                        @if ($approvalsValues->approval_status == 'approved' )
                                        <p class="mt-4"><span class="badge text-bg-success">{{$approvalsValues->button_text}} -
                                                {{ \Carbon\Carbon::parse($approvalsValues->updated_at)->format('d/m/Y H:i:s')}}</span>
                                        </p>
                                        @endif
                                        @if ($approvalsValues->approval_status == 'rejected' )
                                        <p class="mt-4"><span class="badge text-bg-danger">{{$approvalsValues->button_text}} -
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
                                            @php
                                                if($approvalsValues->status == 'review') {
                                                    $next_status = 'Negotiation';
                                                }elseif($approvalsValues->status == 'Negotiation'){
                                                    $next_status = 'Approval';
                                                }elseif($approvalsValues->status == 'Approval'){
                                                    $next_status = 'Signing';
                                                }

                                                if( $approvalsValues->next_status == 'Negotiation' ||  $approvalsValues->next_status == 'Signing' ){
                                                    $btnNextText = '';
                                                }else{
                                                    $btnNextText = 'Next';
                                                }
                                            @endphp
                                            <div class="badge bg-lighter rounded d-flex align-items-center">
                                                @if($loop->last)
                                                <button id="btn_save_updates_approve"
                                                    class="btn btn-success btn-sm pull-right" style="right: 10px;">Send to {{ $btnNextText}} {{ $approvalsValues->next_status }}</button>
                                                @else
                                                <button id="btn_save_updates_approve"
                                                    class="btn btn-success btn-sm pull-right" style="right: 10px;">Send to Next {{ $approvalsValues->status }}</button>
                                                @endif    
                                                    <button id="btn_save_updates_reject"
                                                    class="btn btn-danger btn-sm pull-right">Send to Owner</button>
                                            </div>
                                            @endif
                                        </div>
                                        @if ($approvalsValues->approval_status == 'pending' && $loop->index != 0)
                                        <p class="card-header"><span class="badge text-bg-info">Waiting for {{ $approvalsValues->status }}</span></p>
                                        @endif
                                        @if ($approvalsValues->approval_status == 'pending')

                                        <div class="card">
                                        <div class="" id="updatesDiv{{ $loop->index }}" style="display:none;">
                                        @if($lastAttachment != "")
                                            @if(fileStorageType() == 'Google')
                                                <iframe src="{{ 'https://drive.google.com/file/d/' . $lastAttachment->path . '/edit?usp=drive_web' }}" height="500" width="100%"></iframe>
                                            @endif  
                                        @endif
                                            <!-- <form action="/contracts/updateApprovals" method="POST" enctype="multipart/form-data"> -->
                                            <form id="approvalAddUpdatesForm" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div style="padding: 15px;">
                                                    <hr>
                                                        <h5 class="updatesHeading text-center">Form Block</h5>
                                                    <hr>
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
                                                          <div class="form-group mfiles" style="margin-top: 19px;">
                                                            <input class="myFile" name="photos[]" type="file">
                                                        </div>

                                                        <table id="fileList" class="table">
                                                            <tr>
                                                                <td colspan="2">
                                                                    <h5>Attachments</h5>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        </div>
                                                        <hr>
                                                    </div>
                                                    @endif
                                                </div>

                                                @php
                                                if($approvalsValues->status == 'Approval') {
                                                    $btn_textA = 'Approve';
                                                }else{
                                                    $btn_textA = 'Update';
                                                }
                                            @endphp

                                                <div style="height: 50px;margin-top: 20px;padding: 15px;margin-bottom: 10px;">
                                                    <button type="submit" id="btn_save_updates"
                                                        class="btn btn-primary btn-sm pull-right">{{ $btn_textA }}</button>
                                                    <button type="button" id="btn_cancel_updates"
                                                        class="btn btn-danger btn-sm pull-right">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                        @endif

                                        @if ($approvalsValues->approval_status == 'approved' || $approvalsValues->approval_status == 'rejected')
                                        <div class="" id="EditDiv{{ $loop->index }}" style="display:none;">
                                            <form id="editApprovalAddUpdatesForm" >
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
                                                    <div class="col-md-6 card p-3">
                                                        <div class="form-group mfiles" style="margin-top: 19px;">
                                                            <input class="myFile" name="photos[]" type="file">
                                                        </div>

                                                        <table id="fileList" class="table">
                                                            <tr>
                                                                <td colspan="2">
                                                                    <h5>Attachments</h5>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>

                                                    <div class="col-md-6" style="margin-top: 19px;">
                                                        <!--<p><a href=""> {{ $approvalsValues->attachments }} </a> </p>-->
                                                        
                                                       
                                                        

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
@endif

@endforeach


        

<div class="row mb-6 g-6" style ="margin-top:20px">
    <div class="col-lg-12">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <h5 class="card-title m-0 me-2 pt-1 mb-2 d-flex align-items-center"><i class="ti ti-list-details me-3"></i> Approvals Activity Timeline</h5>
        <div class="dropdown">
          <button class="btn btn-text-secondary rounded-pill text-muted border-0 p-2 me-n1 waves-effect waves-light" type="button" id="timelineWapper" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="ti ti-dots-vertical ti-md text-muted"></i>
          </button>
          <!-- <div class="dropdown-menu dropdown-menu-end" aria-labelledby="timelineWapper">
            <a class="dropdown-item waves-effect" href="javascript:void(0);">Download</a>
            <a class="dropdown-item waves-effect" href="javascript:void(0);">Refresh</a>
            <a class="dropdown-item waves-effect" href="javascript:void(0);">Share</a>
          </div> -->
        </div>
      </div>
      @foreach ($approvalsArr as $key => $approvalsData)
        @foreach ($approvalsData as $approvalsValues)
            @if($approvalsData[0]->flag == 0)
            <!-- {{ $approvalsData }} -->
                @if(count($approvalsData) > 0)
                    @php
                        if($approvalsValues->approval_status == 'rejected') {
                            $text = 'Sent for Under Revision';
                        }else{
                            $text = 'sent for '. $approvalsValues->next_status;
                        }

                    @endphp
                    <div class="card-body pb-xxl-0" style ="margin-top:10px">
                        <ul class="timeline mb-0">
                        <li class="timeline-item timeline-item-transparent">
                            <span class="timeline-point timeline-point-success"></span>
                            <div class="timeline-event">
                                <div class="timeline-header mb-3">
                                    <h6 class="mb-0">{{ $approvalsValues->username }} has {{ $text }} from {{ $approvalsValues->status }} on {{ \Carbon\Carbon::parse($approvalsValues->updated_at)->format('d/m/Y H:i:s')}}</h6>
                                    <small class="text-muted"></small>
                                </div>
                                <p class="mb-2">
                                <!-- Previous Status : {{ $approvalsValues->status }} -->
                                </p>
                                <p class="mb-2">
                                Comments : {{ $approvalsValues->next_action_description }}
                                </p>
                                <p class="mb-3">
                                            
                                    @php
                                        $attachments = json_decode($approvalsValues->attachments);
                                    @endphp
                                   
                                   @if(isset($attachments) && count($attachments) > 0)
                                   <div class = "col-md-5">
                                       <table class = "table table-striped">
                                           <!--<thead>-->
                                           <!--    <th>Attachments</th>-->
                                           <!--</thead>-->
                                           <tbody>
                                               @foreach ($attachments as $file)
                                                    <tr><td style="text-align: justify;"><a href="{{ fileViewUrl($file->path) }}" target="_blank">{{ $file->name }}</a><br></tr></td>
                                               @endforeach
                                           </tbody>
                                        </table>
                                    </div>
                                   @endif
                                        
                                </p>
                            </div>
                        </li>
                        </ul>
                    </div>
                @else
                    <h4 class="mb-3 text-center"> No Records</h4>
                @endif
            @endif
        @endforeach
      @endforeach
    </div>
  </div>
</div>
