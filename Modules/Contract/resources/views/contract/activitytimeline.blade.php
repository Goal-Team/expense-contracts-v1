<div class="row mb-6 g-6" style ="margin-top:20px">
    <div class="col-lg-12">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">
        <h5 class="card-title m-0 me-2 pt-1 mb-2 d-flex align-items-center"><i class="ti ti-list-details me-3"></i> Approvals Activity Timeline</h5>
      </div>
      @php
        $approvalsArr = $approvalsArr->reverse();      
      @endphp
      @foreach ($approvalsArr as $key => $approvalsData)
     
      <!--<h4>{{$key}}</h4>-->
        @foreach ($approvalsData as $approvalsValues)

            @if(strtolower($approvalsValues->status) == 'signing' && strtolower($approvalsValues->previous_status) == 'approval' && $approvalsValues->next_status == '' && $approvalsValues->button_text == null)
                @break;
            @endif
            
            <!-- {{ $approvalsData }} -->
                @if(count($approvalsData) > 0)
                    @php
                        //if($approvalsValues->approval_status == 'rejected') {
                            $text = 'Sent for Under Revision';
                            $textCreate = 'Rejected';
                        //}else{
                            $text = 'Sent For '. ucfirst($approvalsValues->next_status).' On';
                            
                            $textPreStatus = $approvalsValues->previous_status;
                            if(strtolower($approvalsValues->previous_status) == 'draft'){
                                $textPreStatus = 'review';
                            }
                            if(strtolower($approvalsValues->previous_status) == 'approved'){
                                $textPreStatus = 'signing';
                            }
                            if(strtolower($approvalsValues->previous_status) == 'negotiation'){
                                $textPreStatus = 'approval';
                            }
                            
                            $textCreate = 'For '. ucfirst($textPreStatus).' On';
                            
                            if($approvalsValues->button_text != null){
                                $text = $approvalsValues->button_text;
                                if(strtolower($text) == 'approval on'){
                                    $text = 'Approved On';
                                }
                                if(strtolower($text) == 'external'){
                                    $text = 'Signed On';
                                }
                                
                                if(strtolower($text) == 'signed on' && strtolower($approvalsValues->next_status) == 'signing'){
                                    $text = 'Send For Signing On';
                                }
                                
                                if(strtolower($text) == 'approved on' && strtolower($approvalsValues->status) == 'signing' && strtolower($approvalsValues->next_status) == ''){
                                    $text = 'Signed On';
                                }
                            }
                        //}
                        
                        $timelineArr = [];
                        
                        if($approvalsValues->updated_by){
                            $updateText1 = "[timelinebehalfhistory]";
                            if(json_decode($approvalsValues->username)->email != json_decode($approvalsValues->updated_by)->email){
                               $updateText1 = json_decode($approvalsValues->updated_by)->name ." (". json_decode($approvalsValues->updated_by)->email .") On Behalf of [[timelinebehalfhistory]]"; 
                            }
                            $updateText = str_replace('[timelinebehalfhistory]', "<b>".json_decode($approvalsValues->username)->name ." (". json_decode($approvalsValues->username)->email .")</b>", $updateText1) . " " .$text. " " . (($approvalsValues->updated_on != null ) ? \Carbon\Carbon::parse($approvalsValues->updated_on)->format('d/m/Y H:i:s') : '-');
                            if(!(json_decode($approvalsValues->username)->email == json_decode($approvalsValues->updated_by)->email)){
                            }
                                $timelineArr['updated'] = $updateText;
                        }
                        
                        $createText1 = "[timelinebehalffromhistory]";
                        
                        if($approvalsValues->updated_by == null){
                            if($approvalsValues->created_by && json_decode($approvalsValues->username)->email != json_decode($approvalsValues->created_by)->email){
                               $createText1 = json_decode($approvalsValues->created_by)->name ." (". json_decode($approvalsValues->created_by)->email .") Send To [timelinebehalffromhistory]";
                            }else{
                               $createText1 = json_decode($approvalsValues->created_by)->name ." (". json_decode($approvalsValues->created_by)->email .") Received";  
                            }
                            $createText = str_replace('[timelinebehalffromhistory]', "<b>".json_decode($approvalsValues->username)->name ." (". json_decode($approvalsValues->username)->email .")</b>", $createText1) . " " .$textCreate. " " . (($approvalsValues->created_at != null ) ? \Carbon\Carbon::parse($approvalsValues->created_at)->format('d/m/Y H:i:s') : '-');
                            $timelineArr['created'] = $createText;
                       }
                        


                    @endphp
                    @foreach($timelineArr as $tkey => $timeArr)
                    <div class="card-body pb-xxl-0 {{ $tkey }}" style ="margin-top:10px">
                        <ul class="timeline mb-0">
                        <li class="timeline-item timeline-item-transparent">
                            <span class="timeline-point timeline-point-success"></span>
                            <div class="timeline-event">
                                <div class="timeline-header mb-3">
                                    <h6 class="mb-0" data-status-attr="{{ "curr-".$approvalsValues->approval_status."-pre-".$approvalsValues->previous_status."-next-".$approvalsValues->next_status}}">
                                       
                                       {!! $timeArr !!}
                                        
                                    </h6>
                                    <small class="text-muted"></small>
                                </div>
                                @if($tkey != 'created')
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
    <a href="{{ attachmentDummyUrl($approvalsValues->attachments, true) }}" target="_blank" title="{{ $approvalsValues->attachments_filename }}"><i class="ti ti-checklist ti-md"></i></i></a><br></tr></td>
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
                                                    <a href="{{ attachmentDummyUrl($file->path, true) }}" target="_blank" title="{{$file->name}}"><i class="ti ti-file-certificate ti-md"></i></a>
                                               @endif
                                               @endforeach
                                        </div>
                                       @endif
                                            
                                    </p>
                                @endif
                            </div>
                        </li>
                        </ul>
                    </div>
                    @endforeach
                @else
                    <h4 class="mb-3 text-center"> No Records</h4>
                @endif
            
        @endforeach
      @endforeach
    </div>
  </div>
</div> 