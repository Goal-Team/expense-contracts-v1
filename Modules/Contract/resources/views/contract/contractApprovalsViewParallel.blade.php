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

  canvas {
    border: 1px solid black;
    border-radius: 4px;
  }

  .signOptions{
      height:250px;
  }

  #signFileOption{
      border: 1px solid black;
      border-radius: 4px;
  }
</style>
@php
$appDataAppRules = json_decode(trim($contract->rules_id));
$approvalTypeContract = $appDataAppRules[0]->approval_type ?? '';
@endphp

<div class="col-12" id="modalCenter" style="display: none;">
    <div class="card" role="">
        <div class="">
        <div class="card-header">
            <h5 class="card-title" id="modalCenterTitle">Approval Process</h5>
        </div>
          
          <form id="ApprovalProcessPopup" method="POST" enctype="multipart/form-data">              
            <div class="card-body">
                <div class="row">
                <div class="col-md-6">
                    <label for="nameWithTitle" class="form-label">Short Description</label>
                    <input type="text" id="shortDescrip" class="form-control mb-4" placeholder="Enter Short Description">
    
                    <label for="emailWithTitle" class="form-label">Description</label>
                    <textarea class="form-control mb-4" id="ReviewDescription" rows="3"></textarea>
                    <div class="form-check form-check-inline mb-4">
                        <h6 for="noChanges">
                            <input name="noChanges" id="noChanges" class="form-check-input" type="checkbox" value="1" checked/>
                            No Changes in Documents</h6>
                    </div><br/>
                    @if(fileStorageType() != 'Local')
                        <label for="fileTypeDoc2">Support Documents If Any</label>
                    @else
                        <label for="fileTypeDoc2">Contract/Support Documents</label>
                    @endif
                    <select class="form-control" id="fileTypeDocnew">
                        <option value="" selected>-- Select Document Type --</option>
                        <option value="contract">Contract Document</option>
                        <option value="additional">Support Documents</option>
                    </select>
                    <div class="input-group">
                        <div class="form-group mfiles" style="margin-top: 19px;">
                            <input class="myFilenew" name="photos[]" type="file">
                        </div>
    
                        <table id="fileListnew" class="table">
                            <tr>
                                <td colspan="3">
                                    <h5>Attachments</h5>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                  <div class="misgtable topone" style="display:none">
                    @php
                        $disableSubmit = 0;
                    @endphp
                    @foreach ($reqfieldsVal as $key => $vals)
                        @php
                        $buttonDisable = false;
                        $orgVal = $reqfieldsVals[$key];
                        $inpVal = $reqfieldsVals[$key];
                        if($reqfieldsInpType[$key] == 'date' && $inpVal == $key){
                            $orgVal = "";
                            $inpVal = date('Y-m-d');
                        }
                        @endphp                    
                        @if(!$vals)
                            @php
                                $buttonDisable = true;
                                $disableSubmit++;
                            @endphp
                        @endif
                          <div id="{{$key.'-section-id'}}">
                            <label for="{{$key.'-id'}}" class="form-label">{{ $reqfieldsText[$key] }}</label>
                            <div class="input-group {{$reqfieldsInpType[$key]}}">
                                @if(!in_array($reqfieldsInpType[$key] ,['radio', 'select']))
                                    <input type="{{$reqfieldsInpType[$key]}}" id="{{$key.'-id'}}" name="{{$reqfieldsInpField[$key]}}[{{$key}}]" class="form-control {{ empty($inpVal) ? 'mandateField' : '' }}" placeholder="Enter {{ $reqfieldsText[$key] }}" value="{{ $inpVal }}" {{ empty($orgVal) ? 'required':'disabled'}}>
                                @else
                                @php
                                    $inpOptions = explode(',', $reqFieldsOptions['value'][$key]);
                                    $inpOptionText = explode(',', $reqFieldsOptions['text'][$key]);
                                @endphp
                                 @if($reqfieldsInpType[$key] == 'radio')
                                     @foreach($inpOptions as $ke => $inopt)
                                        <label class="form-check-inline form-check">
                                            <input type="{{$reqfieldsInpType[$key]}}" name="{{$reqfieldsInpField[$key]}}[{{$key}}]" class="form-check-input {{ empty($inpVal) ? 'mandateField' : '' }}" {{ $inpVal == $inopt ? 'checked' : ''}} value="{{ $inopt }}"/>
                                            {{$inpOptionText[$ke]}}
                                        </label>
                                     @endforeach
                                 @endif
                                 @if($reqfieldsInpType[$key] == 'select')
                                    <select name="{{$reqfieldsInpField[$key]}}[{{$key}}]" class="form-select {{ empty($inpVal) ? 'mandateField' : '' }}">
                                     @foreach($inpOptions as $ke => $inopt)
                                        <option value="{{ $inopt }}" {{ $inpVal == $inopt ? 'selected' : ''}}>{{$inpOptionText[$ke]}}</option>
                                     @endforeach
                                    </select>
                                 @endif                                 
                                @endif
                               @if($reqfieldsInpEdit[$key])
                                    <button type="button" class="btn btn-primary btn-icon editInputApprovals" data-enableedit="{{$key.'-id'}}"><i class="ti ti-edit"></i></button>
                                @endif
                            </div>
                            @if(empty($inpVal))
                                <span class="text-danger d-block">Required</span>
                            @endif
                          </div>
                    @endforeach
                 </div>                
                </div>
            </div>
    
            </div>
        @if(isset($contract->contract_attachment_filename))
            @if(fileStorageType() != 'Local')
                @php 
                    $getFinalUrl = get_google_drive_doc_link($contract->contract_attachment_filename,$contract->contract_attachment, 'edit', 'dfdfdh');
                    $getFinalUrlNew = get_google_drive_doc_link($contract->contract_attachment_filename,$contract->contract_attachment, 'edit', 'gfhdgfdhg');
                @endphp
                <div class="alert alert-danger mx-2" data-open="edit">If below document Not Loaded Please <a href="{{$getFinalUrlNew}}" target="blank">Click Here</a>. Because of some security reasons its not loaded.</div>
                <iframe src="{{ $getFinalUrl }}" height="500" width="100%"></iframe>
            @else
                @include('contract::contract.viewContractDocument')
            @endif  
        @endif         
        <div class="d-flex align-items-center my-2 px-3 gap-2">
            <button type="submit" id="paperIconSub" class="btn btn-success btn-sm pull-right {{$disableSubmit}}">Send</button>
            <a role="button" class="btn btn-danger btn-sm pull-right" href="{{url('contracts/'.$contract->id.'?tab=timeline')}}">Close</a>
        </div>
         
        <div class="misgtablenote" style="display:none; text-align:center;margin: 0 1rem;"><div class="alert alert-danger" role="alert">Before submit the form, please fill the reqired values</div></div>
        </form>
        </div>
    </div>
</div>
@foreach ($approvalsArr as $key => $approvalsData)
@if($loop->last)
<input type="hidden" id="curAppStatus" class="form-control" value="{{$approvalsData[0]->status}}">
<input type="hidden" id="appRowId" class="form-control" value="{{$approvalsData[0]->id}}">
@endif
@if (count($approvalsData) == 1 && $approvalsData[0]->status != 'Signing' && $approvalsData[0]->status != 'review' && $approvalsData[0]->status != 'Approval')

@if ($approvalsData[0]->status == 'Draft' && $approvalsData[0]->previous_status == 'Draft'
&& $approvalsData[0]->flag == 1 )

<div class="row mb-6 g-6">
    <div class="col-lg-12" style="margin-top: 20px;">
        <div class="card h-100">
            <div>
                <button type= "button" class=" btn btn-outline-primary text-nowrap d-inline-flex position-relative me-4 waves-effect mt-3"
                    style="margin-left: 15px;" disabled>
                    <!-- {{ $approvalsData[0]->status }} - {{ $contract->substatus }} -->
                    {{ $contract->contract_status }} - {{ $contract->substatus }}
                </button>
                <div class="divider">
                        <div class="divider-text" style ="font-size:18px !important"><b>{{ json_decode($approvalsData[0]->username)->name ." (". json_decode($approvalsData[0]->username)->email .")" }}</b></div>
                </div>
                @if( Helper::accessInfo(json_decode($approvalsData[0]->username)->email) )
                    <div class="card-body mt-3" id="approvalForm">
                    

                    <div class="d-flex flex-column flex-sm-row justify-content-between text-center gap-6">
                        <div class="d-flex flex-column align-items-center">

                        </div>
                        <div class="d-flex flex-column align-items-center" style="cursor: pointer;">
                            <span class="p-4 border-1 border-primary rounded-circle border-dashed mb-0 w-px-75 h-px-75" style="border-color: #148000 !important;">
                            <i class="fa-solid fa-circle-check" id="modalPopUpReview" 
                            style="margin-left: -3px;font-size:xx-large;color: #4CAF50;margin-top: -4px;"> </i>
                            </span>
                            
                            <p style="color: #4CAF50;" class="my-2">Click Icon to Send For review</p>
                        </div>
                        <div class="d-flex flex-column align-items-center">

                        </div>
                        <div class="d-flex flex-column align-items-center" style="cursor: pointer;">
                            <span class="p-4 border-1 border-primary rounded-circle border-dashed mb-0 w-px-75 h-px-75" 
                            style="border-color: firebrick !important;">
                                    <i class="fa-solid fa-circle-check" id = "modalPopUpNegotiation" 
                                    style="margin-left: -3px;font-size:xx-large;color:firebrick;margin-top: -4px;"> </i>
                            </span>
                            <p style="color:firebrick;" class="my-2">Click Icon to Send For Negotiation</p>
                        </div>
                        <div class="d-flex flex-column align-items-center">

                        </div>
                    </div>
                </div>
                @else
                  <div class="alert alert-warnig" role="alert">Oops! no access for approval please contact admin</div>
                @endif
        </div>
    </div>
</div>
</div>
@elseif($approvalsData[0]->flag == 1 && ($approvalsData[0]->status != 'Signing' && $approvalsData[0]->previous_status != 'Approved'))
<div class="row mb-6 g-6">
    <div class="col-lg-12" style="margin-top: 25px;">
        <div class="card h-100">
            <div id="approvalForm">
                <button type= "button" class=" btn btn-outline-info text-nowrap d-inline-flex position-relative me-4 waves-effect mt-3"
                    style="margin-left: 15px;" disabled>
                    {{ $contract->contract_status }} - {{ $contract->substatus }}
                </button>
                <div class="divider">
                        <div class="divider-text" style ="font-size:18px !important"><b>{{ json_decode($approvalsData[0]->username)->name ." (". json_decode($approvalsData[0]->username)->email .")" }}</b></div>
                </div>

                <div class="card-body" style="margin-top: 0;">
                    <div class="d-flex flex-column flex-sm-row justify-content-between text-center gap-6" style="cursor: pointer;">
                        <div class="d-flex flex-column align-items-center">

                        </div>
                        <div class="d-flex flex-column align-items-center d-none">
                            <span class="p-4 border-1 border-primary mb-0 w-px-75 h-px-75">
                                <i class="fa-solid fa-circle-check" id = "modalPopUpApproval" 
                                style="margin-left: -5px;font-size:xx-large;color: #4CAF50;margin-top: -4px;"> </i>
                            </span>
                            @if ($approvalsData[0]->status == 'Negotiation')
                            <!--<p style="color: #4CAF50;" class="my-2">Send For Approval</p>-->
                            @endif
                            @if($approvalsData[0]->status == 'Approved')
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
                                <i class="fa-solid fa-times-circle" id = "modalPopUpReviewBack"
                                    style="margin-left: -5px;font-size:xx-large;color: #f44336;margin-top: -4px;"> </i>

                            </span>
                            <p style="color: #f44336;" class="my-2">Send For {{ $approvalsData[0]->previous_status }}</p>

                        </div>
                        @if ($approvalsData[0]->status != 'Approved' &&  $approvalsData[0]->status != 'Signing')
                        <div class="d-flex flex-column align-items-center">
                            <span class="p-4 border-1 border-primary mb-0 w-px-75 h-px-75">
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
                        @foreach ($approvalsData as $approvalsValues)
                        @if($loop->first )
                        
                        <div class="card-header">
                                <button type="button" class="btn btn-outline-dribbble waves-effect text-uppercase" disabled> 
                                {{ $contract->contract_status }} - {{ $contract->substatus }} - Parallel Approval Flow
                                </button>
                            <hr>
                        </div>
                        @endif
                        <div class="card-body {{ $approvalsValues->approval_status }}_timelines">
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
                                        <div class="timeline-header mb-3 33">
                                            <h6 class="mb-0">{{ json_decode($approvalsValues->username)->name ." (". json_decode($approvalsValues->username)->email .")" }}</h6>
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
                                        @if(!empty($approvalsValues->next_action_item))
                                        <p class="mb-3">
                                            Short Description : {{ $approvalsValues->next_action_item }}
                                        </p>
                                        @endif
                                        @if(!empty($approvalsValues->next_action_description))
                                        <p class="mb-3">
                                           Description :  {{ $approvalsValues->next_action_description }}
                                        </p>
                                        @endif
                                <p class="mb-3">
                                   @if($approvalsValues->attachments !== null && !empty($approvalsValues->attachments))
                                   <div class = "col-md-5">
                                       <h6 class="mb-1">Contract Document</h6>
<a href="{{ fileViewUrl($approvalsValues->attachments, true) }}" target="_blank" title="{{ $approvalsValues->attachments_filename }}"><i class="ti ti-checklist ti-md"></i></i></a><br></tr></td>
                                    </div>                                   
                                   @endif                                            
                                    @php
                                        $attachments = json_decode($approvalsValues->attachments_support);
                                    @endphp
                                   
                                   @if(isset($attachments) && count($attachments) > 0)
                                   <div class = "col-md-5 mt-2">
                                       <h6 class="mb-1">Supporting Documents</h6>
                                           @foreach ($attachments as $file)
                                           @if($file->path != "")
                                                <a href="{{ fileViewUrl($file->path, true) }}" target="_blank" title="{{$file->name}}"><i class="ti ti-file-certificate ti-md"></i></a>
                                           @endif
                                           @endforeach
                                    </div>
                                   @endif
                                        
                                </p>
                                        @endif
                                        @if ($approvalsValues->approval_status == 'approved' )
                                        <p class="mt-4"><span class="badge text-bg-success appr">{{$approvalsValues->button_text}} -
                                                {{ \Carbon\Carbon::parse($approvalsValues->updated_at)->format('d/m/Y H:i:s')}}</span>
                                        </p>
                                        @endif
                                        @if ($approvalsValues->approval_status == 'rejected' )
                                        <p class="mt-4"><span class="badge text-bg-danger reje">{{$approvalsValues->button_text}} -
                                                {{ \Carbon\Carbon::parse($approvalsValues->updated_at)->format('d/m/Y H:i:s')}}</span>
                                        </p>
                                        @endif
                                        <!-- {{ $loop->index }} -->
                                        <!-- $approvalsArr[$loop->index - 1]->status == 'approved' -->
                                        @if( Helper::accessInfo(json_decode($approvalsValues->username)->email) )
                                        <div class="d-flex align-items-center mb-2 latestReview">
                                           
                                            @if (($approvalsValues->approval_status == 'pending'))
                                            
                                            <div class="badge bg-lighter rounded d-flex align-items-center">
                                                @if ($approvalsValues->status != 'Signing')
                                                   
                                                        <button type="submit" id="btn_save_updates_approve_{{$loop->index}}" class="btn btn-success btn-sm pull-right btn_save_updates_approve_pl" data-up-div="{{$loop->index}}" style="right: 10px;">Send to next {{ $approvalsValues->status }}</button>

                                                @elseif($approvalsValues->status == 'Signing')
                                                <button type="submit" id="btn_save_updates_approve"
                                                    class="btn btn-success btn-sm pull-right" style="right: 10px;">To Sign</button>
                                                @endif
                                              
                                                @if($contract->substatus != 'Approved')
                                                <button type="submit" id="btn_save_updates_reject"
                                                    class="btn btn-danger btn-sm pull-right">Send to Owner</button>
                                                @endif
                                            </div>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
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
                                            @endif
                                        </div>
                                        @else
                                          <div class="alert alert-warnig" role="alert">Oops! no access for approval please contact admin</div>
                                        @endif

                                        @if ($approvalsValues->approval_status == 'pending' && $loop->index != 0 && $approvalTypeContract != 'parallel')
                                        <p class="card-header"><span class="badge text-bg-info">Waiting for {{ $approvalsValues->status }}</span></p>
                                        @endif
                                        @if ($approvalsValues->approval_status == 'pending')

                                        <div class="card">
                                        @if( Helper::accessInfo(json_decode($approvalsValues->username)->email) )
                                        <div class="latestReview" id="updatesDiv{{ $loop->index }}" style="display:none;">

                                            <!-- <form action="/contracts/updateApprovals" method="POST" enctype="multipart/form-data"> -->
                                            <form id="approvalAddUpdatesForm_{{$loop->index}}" class="approvalAddUpdatesForm_pl" data-sub-form="{{$loop->index}}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div style="padding: 15px;">
                                                    <hr>
                                                        <h5 class="updatesHeading text-center">Form Block</h5>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            @if($approvalsValues->status != 'Signing')
                                                                <div class="form-group">
                                                                    <label for="nextActionItem">Short Description<span style="color:red"></span></label>
                                                                    <input type="text" name="nextActionItem{{ $loop->index }}" placeholder="Enter Next Action"
                                                                        class="form-control">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="nextAction"> Description</label>
                                                                    <textarea rows="2" type="text" name="nextAction{{ $loop->index }}"
                                                                    placeholder="Enter Next Action Description" class="form-control"></textarea>
                                                                </div>
                                                                <hr>
                                                            @endif
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
                                                    @if($approvalsValues->status == 'Signing' && strtolower($contract->substatus) == 'approved' && env('enable_sign_pad'))
                                                    <input type="hidden" name="currentUserId" id="currentUserId" class="form-control"
                                                        value="{{ $approvalsValues->id }}">                                                    
                                                    <div class="w-100 mb-3">
                                                        <div class="form-check form-check-inline mb-2">
                                                            <label for="signPadOption" class="form-check-label">
                                                                <input name="signPadOption" class="signPadOption" data-divid="signPadOption" class="form-check-input" type="radio" value="0" checked/>
                                                                Sign Pad</label>
                                                        </div>
                                                        <div class="form-check form-check-inline mb-2">
                                                            <label for="signUploadOption" class="form-check-label">
                                                                <input name="signPadOption" class="signPadOption" data-divid="signFileOption" class="form-check-input" type="radio" value="1"/>
                                                                Upload Signature</label>
                                                        </div>                                                       
                                                        <div id="signPadOption" class="signOptions">
                                                            <canvas id="signatureCanvas" width="400" height="200"></canvas>
                                                            <br>
                                                            <button type="button" class="btn btn-sm btn-warning" id="clearButton">Clear</button> 
                                                        </div>
                                                        <div id="signFileOption" class="signOptions d-none d-flex">
                                                            <div class="ms-2 justify-content-center align-self-center">
                                                                <label for="avatar">Choose Signature:</label>
                                                                <input type="file" name="uploadsign"/>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr/>
                                                    @endif
                                                    @if($approvalsValues->status == 'review' || ($approvalsValues->status == 'Signing' && strtolower($contract->substatus) != 'pending approval'))
                                                        @if(fileStorageType() == 'Local')
                                                            <div class="form-check form-check-inline">
                                                                <h6 for="noChangesUpdate">
                                                                    <input name="noChangesUpdate" checked id="noChangesUpdate" class="form-check-input" type="checkbox" value="1"/>
                                                                    No Changes in Documents</h6>
                                                            </div>
                                                        @endif                                                    
                                                        <div class="form-group" style="margin-top: 19px;">
                                                            @if(fileStorageType() != 'Local')
                                                                <label for="fileTypeDoc2">Support Documents If Any</label>
                                                            @else
                                                                <label for="fileTypeDoc2">Contract/Support Documents</label>
                                                            @endif
                                                            <select class="form-control" id="fileTypeDoc2">
                                                                <option value="" selected>-- Select Document Type --</option>
                                                                <option value="contract">Contract Document</option>
                                                                <option value="additional">Support Documents</option>
                                                            </select>
                                                            <div class="form-group mfiles" style="margin-top: 19px;">
                                                                <input class="myFile2" name="photos[]" type="file">
                                                            </div>

                                                        <table id="fileList" class="table">
                                                            <tr>
                                                                <td colspan="2">
                                                                    <h5>Attachments</h5>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        </div>

                                                    @endif                                                            
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="misgtable bottomone" style="display:none">
                                                                @php
                                                                    $disableSubmit1 = 0;
                                                                @endphp
                                                                @foreach ($reqfieldsVal as $key => $vals)
                                                                    @php
                                                                    $buttonDisable = false;
                                                                    $orgVal = $reqfieldsVals[$key];
                                                                    $inpVal = $reqfieldsVals[$key];
                                                                    if($reqfieldsInpType[$key] == 'date' && $inpVal == $key){
                                                                        $orgVal = "";
                                                                        $inpVal = date('Y-m-d');
                                                                    }
                                                                    @endphp                    
                                                                    @if(!$vals)
                                                                        @php
                                                                            $buttonDisable = true;
                                                                            $disableSubmit1++;
                                                                        @endphp
                                                                    @endif
                                                                      <div id="{{$key.'-section-id'}}">
                                                                        <label for="{{$key.'-id'}}" class="form-label">{{ $reqfieldsText[$key] }}</label>
                                                                        <div class="input-group">
                                                                            @if($reqfieldsInpType[$key] != 'radio')
                                                                                <input type="{{$reqfieldsInpType[$key]}}" id="{{$key.'-id'}}" name="{{$reqfieldsInpField[$key]}}[{{$key}}]" class="form-control {{ empty($inpVal) ? 'mandateField' : '' }}" placeholder="Enter {{ $reqfieldsText[$key] }}" value="{{ $inpVal }}" {{ empty($orgVal) ? 'required':'disabled'}}>
                                                                            @else
                                                                            @php
                                                                                $inpOptions = explode(',', $reqFieldsOptions['value'][$key]);
                                                                                $inpOptionText = explode(',', $reqFieldsOptions['text'][$key]);
                                                                            @endphp
                                                                             @foreach($inpOptions as $ke => $inopt)
                                                                                <label class="form-check-inline form-check">
                                                                                    <input type="{{$reqfieldsInpType[$key]}}" name="{{$reqfieldsInpField[$key]}}[{{$key}}]" class="form-check-input {{ empty($inpVal) ? 'mandateField' : '' }}" {{ $inpVal == $inopt ? 'checked' : ''}} value="{{ $inopt }}"/>
                                                                                    {{$inpOptionText[$ke]}}
                                                                                </label>
                                                                             @endforeach
                                                                            @endif
                                                                           @if($reqfieldsInpEdit[$key])
                                                                                <button type="button" class="btn btn-primary btn-icon editInputApprovals" data-enableedit="{{$key.'-id'}}"><i class="ti ti-edit"></i></button>
                                                                            @endif
                                                                        </div>
                                                                        @if(empty($inpVal))
                                                                        <span class="text-danger d-block">Required</span>
                                                                        @endif
                                                                      </div>
                                                                @endforeach
                                                            </div>                                                               
                                                        </div>
                                                    </div>
                                                </div>

                                                @php
                                                if($approvalsValues->status == 'Approval') {
                                                    $btn_textA = 'Approve';
                                                }else{
                                                    $btn_textA = 'Update';
                                                }
                                            @endphp
                                            @if(isset($contract->contract_attachment_filename))
                                                @if(fileStorageType() != 'Local')
                                                    @php 
                                                        $getFinalUrl = get_google_drive_doc_link($contract->contract_attachment_filename,$contract->contract_attachment, 'edit', 'dfdtt');
                                                        $getFinalUrlNew = get_google_drive_doc_link($contract->contract_attachment_filename,$contract->contract_attachment, 'edit', 'ghfdhfgd');
                                                    @endphp
                                                    <div class="alert alert-danger mx-2">If below document Not Loaded Please <a href="{{$getFinalUrlNew}}" target="blank">Click Here</a>. Because of some security reasons its not loaded.</div>
                                                    <iframe src="{{ $getFinalUrl }}" height="500" width="100%"></iframe>
                                                @else
                                                    @include('contract::contract.viewContractDocument')
                                                @endif   
                                            @endif
                                            @if($approvalsValues->status == 'Signing' && strtolower($contract->substatus) == 'approved' && env('enable_sign_pad'))
                                                <div class="OtpSection d-none p-2">
                                                    <div class="form-group">
                                                        <label for="nextOtp">OTP<span style="color:red"></span></label>
                                                        <input type="text" name="nextOtp" id="nextOtp" placeholder="Enter OTP"
                                                            class="form-control">
                                                    </div>
                                                    <div class="mt-2">
                                                        <button type="button" id="btn_verify_otp"
                                                            class="btn btn-success btn-sm pull-right">Verify</button>
                                                        <button type="button" id="btn_resend_otp"
                                                            class="btn btn-warning btn-sm pull-right">Resend</button>
                                                            
                                                        <input type="hidden" name="otpActionType" id="otpActionType" value="otp">                                                            
                                                    </div>                                                    
                                                </div>
                                            @endif
                                                <div style="height: 50px;margin-top: 20px;padding: 15px;margin-bottom: 10px;" id="finalActionDiv">
                                                    <button type="button" id="btn_save_updates_{{$loop->index}}" data-sub-form="{{$loop->index}}" data-loading-text="Loading..."
                                                        class="btn btn-primary btn-sm pull-right btn_save_updates_pl">{{ $btn_textA }}</button>
                                                    <button type="button" id="btn_cancel_updates"
                                                        class="btn btn-danger btn-sm pull-right">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                        @endif
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
                                                            @if(fileStorageType() != 'Local')
                                                                <label for="fileTypeDoc2">Support Documents If Any</label>
                                                            @else
                                                                <label for="fileTypeDoc2">Contract/Support Documents</label>
                                                            @endif                                                            
                                                            <select class="form-control" id="fileTypeDoc1">
                                                                <option value="" selected>-- Select Document Type --</option>
                                                                <option value="contract">Contract Document</option>
                                                                <option value="additional">Support Documents</option>
                                                            </select>
                                                            <input class="myFile1" name="photos[]" type="file">
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
</div>
@endif
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
                                    <h6 class="mb-0">{{ json_decode($approvalsValues->username)->name ." (". json_decode($approvalsValues->username)->email .")" }} has {{ $text }} from {{ $approvalsValues->status }} on {{ \Carbon\Carbon::parse($approvalsValues->created_at)->format('d/m/Y H:i:s')}}</h6>
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
                                <p class="mb-3">
                                   @if($approvalsValues->attachments !== null && !empty($approvalsValues->attachments))
                                   <div class = "col-md-5">
                                       <h6 class="mb-1">Contract Document</h6>
<a href="{{ fileViewUrl($approvalsValues->attachments, true) }}" target="_blank" title="{{ $approvalsValues->attachments_filename }}"><i class="ti ti-checklist ti-md"></i></i></a><br></tr></td>
                                    </div>                                   
                                   @endif                                            
                                    @php
                                        $attachments = json_decode($approvalsValues->attachments_support);
                                    @endphp
                                   
                                   @if(isset($attachments) && count($attachments) > 0)
                                   <div class = "col-md-5 mt-2">
                                       <h6 class="mb-1">Supporting Documents</h6>
                                           @foreach ($attachments as $file)
                                           @if($file->path != "")
                                                <a href="{{ fileViewUrl($file->path, true) }}" target="_blank" title="{{$file->name}}"><i class="ti ti-file-certificate ti-md"></i></a>
                                           @endif
                                           @endforeach
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
<form method="post" target="_blank" action="{{url('/setUpSigning')}}" id="documentHtmlViewerForm">
    @csrf
    <input type="hidden" name="contactId" id="currentContract"/>
    <input type="hidden" name="currentSign" id="currentSign"/>
</form>
<div class="modal fade" id="documentHtmlViewerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-simple modal-refer-and-earn">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6" id="documentHtmlViewer">
        </div>
      </div>
    </div>
  </div>
</div>