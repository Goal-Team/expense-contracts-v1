<style>
    .myFile1.disabled, .myFile2.disabled{
        display: none;
    }
    option:disabled{
        background: #ddd;
        color: #fff;
    }

    .loading {
        background-color: #212121;
        padding-right: 40px;
        cursor: none;
        position: relative;        
    }
    .loading:before {
        content: "";
        width: 0px;
        height: 0px;
        border-radius: 50%;
        right: 6px;
        top: 50%;
        position: absolute;
        border: 2px solid #212121;
        border-right: 3px solid #27ae60;
        animation: rotate360 .5s infinite linear, exist .1s forwards ease ;
        
    }    
    .loading:after {
        content: '';
        position: absolute;
        right: 6px;
        top: 50%;
        width: 0;
        height: 0;
        box-shadow: 0px 0px 0 1px #212121;
        position: absolute;
        border-radius: 50%;
        animation: rotate360 .5s infinite linear, exist .1s forwards ease;
    }
    
    @keyframes rotate360 { 
        100% {
            transform: rotate(360deg);
        }
    }
    @keyframes exist { 
        100% {
            width: 15px;
            height: 15px;
            margin: -8px 5px 0 0;
        }
    }
    
</style>
<div class="modal fade" id="modalApproveParty">
    <div class="modal-dialog" role="">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="modalCenterTitle">Party Approval Process</h5>
        </div>
          
          <form id="ApprovalProcessPopup" method="POST" enctype="multipart/form-data">              
            <div class="modal-body">
                <div class="row">
                <div class="col-md-6">
                    <label for="emailWithTitle" class="form-label">Comments</label>
                    <textarea class="form-control" id="comments" rows="3"></textarea>
                </div>
            </div>
    
            </div>
        <div class="d-flex align-items-center my-2 px-3 gap-2">
            <button type="submit" id="paperIconSub" class="btn btn-success btn-sm pull-right">Approve</button>
            <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
        </form>
        </div>
    </div>
</div>
<div class="modal fade" id="modalRejectParty">
    <div class="modal-dialog" role="">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="modalCenterTitle">Party Reject Process</h5>
        </div>
          
          <form id="RejectProcessPopup" method="POST" enctype="multipart/form-data">              
            <div class="modal-body">
                <div class="row">
                <div class="col-md-6">
                    <label for="emailWithTitle" class="form-label">Comments</label>
                    <textarea class="form-control" id="commentsRej" rows="3"></textarea>
                </div>
            </div>
    
            </div>
        <div class="d-flex align-items-center my-2 px-3 gap-2">
            <button type="submit" id="paperIconSub" class="btn btn-warning btn-sm pull-right">Reject</button>
            <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
        </form>
        </div>
    </div>
</div>
<input type="hidden" id="contractPartyId" value="{{$parties->id}}"/>
@foreach ($approvalsArr as $key => $approvalsData)
@if($loop->last)
<input type="hidden" id="curAppStatus" class="form-control" value="{{$approvalsData[0]->status}}">
<input type="hidden" id="appRowId" class="form-control" value="{{$approvalsData[0]->id}}">
@endif

@if($approvalsData[0]->approval_status != 'approved' && $approvalsData[0]->approval_status != 'rejected')
<div class="row mb-6 g-6">
    <div class="col-lg-12" style="margin-top: 25px;">
        <div class="card h-100">
            <div id="approvalForm">
                <div class="container d-flex my-4">
                            <b class="justify-content-center align-self-center">{{ "Approval Pending With ". json_decode($approvalsData[0]->username)->name ." (". json_decode($approvalsData[0]->username)->email .")" }}</b> 
                            <div class="flex-grow-1 d-flex justify-content-end">
                                <div class="text-center">
                                    <span class="p-4 border-1 border-primary mb-0 w-px-75 h-px-75">
                                        <i class="fa-solid fa-circle-check" id = "modalPopUpApproval" 
                                        style="margin-left: -5px;font-size:xx-large;color: #4CAF50;margin-top: -4px;"> </i>
                                    </span>
                                    <p style="color: #4CAF50;" class="my-2">Approve</p>
                                </div> 
                                <div class="text-center">
                                    <span class="p-4 border-1 border-primary mb-0 w-px-75 h-px-75">
                                        <!-- <i class="fa-solid fa-times-circle" id="paperIconReject" -->
                                        <i class="fa-solid fa-times-circle" id = "modalPopUpReject"
                                            style="margin-left: -5px;font-size:xx-large;color: #f44336;margin-top: -4px;"> </i>
        
                                    </span>
                                    <!-- <p style="color: #f44336;" class="my-2">Send to owner</p> -->
                                    <p style="color: #f44336;" class="my-2">Reject</p>
        
                                </div>
                            </div>
                        </div>
            </div>
        </div>
    </div>
</div>
@endif

@endforeach

<div class="row mb-6 g-6" style ="margin-top:20px">
    <div class="col-lg-12">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <h5 class="card-title m-0 me-2 pt-1 mb-2 d-flex align-items-center"><i class="ti ti-list-details me-3"></i> Approvals Activity Timeline</h5>
      </div>
      @foreach ($approvalsArr as $key => $approvalsData)
        @foreach ($approvalsData as $approvalsValues)

            @if($approvalsData[0]->flag == 0)
            <!-- {{ $approvalsData }} -->
                @if(count($approvalsData) > 0)
                    @php
                        $timeLineClass = "success";
                        if($approvalsValues->approval_status == 'rejected') {
                            $text = 'Rejected';
                            $timeLineClass = "danger";
                        }else{
                            $text = 'sent for '. $approvalsValues->next_status;
                        }

                    @endphp
                    <div class="card-body pb-xxl-0" style ="margin-top:10px">
                        <ul class="timeline mb-0">
                        <li class="timeline-item timeline-item-transparent">
                            <span class="timeline-point timeline-point-{{ $timeLineClass }}"></span>
                            <div class="timeline-event">
                                <div class="timeline-header mb-3">
                                    <h6 class="mb-0">{{ json_decode($approvalsValues->username)->name ." (". json_decode($approvalsValues->username)->email .")" }} has {{ $text }} on {{ \Carbon\Carbon::parse($approvalsValues->created_at)->format('d/m/Y H:i:s')}}</h6>
                                    <small class="text-muted"></small>
                                </div>
                                <p class="mb-2">
                                <!-- Previous Status : {{ $approvalsValues->status }} -->
                                </p>
                                @if(!empty($approvalsValues->next_action_description))
                                <p class="mb-2">
                                Comments : {{ $approvalsValues->next_action_description }}
                                </p>
                                @endif
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
