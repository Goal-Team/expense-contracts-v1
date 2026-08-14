@php
$appTypeArr = $appType != '' ? $appType : 0;
$appType = $appType != '' ? '_'.$appType : $appType;
@endphp
<div class="">
    <div class="row">
      <div class="col-md-6 mt-2 d-none">
        <label for="location" class="form-label">Approver</label>
        <div>
          <div class="form-check form-check-inline mt-1">
            <input class="form-check-input" type="radio" name="approver" id="name" value="name" {{ (old('approver', 'name') == 'name') ? 'checked':''}} />
            <label class="form-check-label" for="name">Name</label>
          </div>
          <div class="form-check form-check-inline mt-1">
            <input class="form-check-input" type="radio" name="approver" id="designation" value="designation" {{ (old('approver', 'name') == 'designation') ? 'checked':''}}/>
            <label class="form-check-label" for="designation">Designation</label>
          </div>
        </div>
      </div>
      <div class="col-md-6 mt-2">
        <label for="location" class="form-label">Signatory</label>
        <div>
            <select class="form-select users defaultValSetter{{$appType}} userSign" name="signatory_user[{{$appTypeArr}}]" data-row-type="{{$appType}}" data-row-inpt="userSign">
                <option value="">-- Select Signatory --</option>
                @foreach ($add_users as $add_users_data)
                <option value="{{ $add_users_data->id }}:{{ $add_users_data->FirstName }}:{{$add_users_data->Email}}"
                {{ old('signatory_user.'.$appTypeArr, json_decode($financial->approval_signatory_owner)->sign->$appTypeArr ?? '') == $add_users_data->id.":".$add_users_data->FirstName.":".$add_users_data->Email ? 'selected' : '' }}>
                    {{ $add_users_data->FirstName." ".$add_users_data->LastName."(".$add_users_data->Email.")" }}
                </option>
                @endforeach
            </select>
        </div>
        <label for="signatSelUTakeingForm{{$appType}}" class="form-label mt-2">Undertaking Form <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Undertaking form from signatory to allow admin can sign behalf of above metioned signatory."></i></label>
        <div class="input-group">
            <input type="file" class="form-control defaultValSetter{{$appType}} userSignUtf" name="signatory_user_utaking[{{$appTypeArr}}]" data-row-type="{{$appType}}" data-row-inpt="userSignUtf"/>
            @php
                $signUtFormPath = json_decode($financial->approval_signatory_owner)->signutform->$appTypeArr ?? null;
            @endphp
            <input type="hidden" name="signatory_user_utaking_old[{{$appTypeArr}}]" value="{{$signUtFormPath}}"/>
            @if($signUtFormPath != null)
                <a href="{{fileViewUrl($signUtFormPath)}}" class="bg-success text-white input-group-text" target="new" alt="View Form"><i class="ti ti-file ti-xs"></i></a>
            @endif
        </div>        
        <div class="d-none">
        <label for="location" class="form-label">Owner</label>
        <div>
            <select class="form-select users defaultValSetter{{$appType}} userOwner" name="owner_user[{{$appTypeArr}}]" data-row-type="{{$appType}}" data-row-inpt="userOwner">
                <option value="">-- Select Owner --</option>
                @foreach ($add_users as $add_users_data)
                <option value="{{ $add_users_data->id }}:{{ $add_users_data->FirstName }}:{{$add_users_data->Email}}">
                    {{ $add_users_data->FirstName." ".$add_users_data->LastName."(".$add_users_data->Email.")" }}
                </option>
                @endforeach
            </select>
        </div>
        </div>
      </div>
      <div class="col-md-6">
        <label for="department" class="form-label mt-2">Approval Type</label>
        @php
            $approvalTypeDecoded = json_decode($financial->approval_type, true);
            $currentApprovalType = is_array($approvalTypeDecoded) ? ($approvalTypeDecoded[$appTypeArr] ?? 'sequential') : (json_decode($financial->approval_type)->$appTypeArr ?? 'sequential');
        @endphp
        <div>
          <div class="form-check form-check-inline mt-1">
            <input class="form-check-input apprType defaultValSetter{{$appType}}" type="radio" name="approval_type[{{$appTypeArr}}]" id="sequential" value="sequential" {{ (old('approval_type.'.$appTypeArr, $currentApprovalType) == 'sequential') ? 'checked':''}} data-row-type="{{$appType}}" data-row-inpt="apprType"/>
            <label class="form-check-label" for="sequential">Sequential</label>
          </div>
          <div class="form-check form-check-inline mt-1">
            <input class="form-check-input apprType defaultValSetter{{$appType}}" type="radio" name="approval_type[{{$appTypeArr}}]" id="parallel" value="parallel" {{ (old('approval_type.'.$appTypeArr, $currentApprovalType) == 'parallel') ? 'checked':''}} data-row-type="{{$appType}}" data-row-inpt="apprType"/>
            <label class="form-check-label" for="parallel">Parallel</label>
          </div>
        </div>
      </div>
      <div class="col-md-6 mt-3">
        <label for="department" class="form-label">Approval Status</label>
        @php
            $approvalStatusDecoded = json_decode($financial->approval_status, true);
            $currentApprovalStatus = is_array($approvalStatusDecoded) ? ($approvalStatusDecoded[$appTypeArr] ?? 'required') : (json_decode($financial->approval_status)->$appTypeArr ?? 'required');
        @endphp
        <div>
          <div class="form-check form-check-inline mt-1">
            <input class="form-check-input approval_status defaultValSetter{{$appType}} apprReqr" type="radio" name="approval_status[{{$appTypeArr}}]" id="required" value="required" {{ (old('approval_status.'.$appTypeArr, $currentApprovalStatus) == 'required') ? 'checked':''}} data-row-type="{{$appType}}" data-row-inpt="apprReqr"/>
            <label class="form-check-label" for="required">Approval Required</label>
          </div>
          <div class="form-check form-check-inline mt-1">
            <input class="form-check-input approval_status defaultValSetter{{$appType}} apprReqr" type="radio" name="approval_status[{{$appTypeArr}}]" id="auto" value="auto" {{ (old('approval_status.'.$appTypeArr, $currentApprovalStatus) == 'auto') ? 'checked':''}} data-row-type="{{$appType}}" data-row-inpt="apprReqr"/>
            <label class="form-check-label" for="auto">Auto Approval</label>
          </div>
        </div>
      </div>
      <div class="col-md-6 mt-3">
        <label for="location" class="form-label">Users To Be Notify</label>
        <div>
            <select class="form-select users userNotiValSetter{{$appType}} defaultValSetter{{$appType}} userNoti opt-select-all" multiple name="user_noti[{{$appTypeArr}}][]" data-row-type="{{$appType}}" data-row-inpt="userNoti">
                <option value="all">Select All</option>
                @foreach ($add_users as $add_users_data)
                <option value="{{ $add_users_data->id }}:{{ $add_users_data->FirstName }}:{{$add_users_data->Email}}"
                {{ in_array($add_users_data->id.":".$add_users_data->FirstName.":".$add_users_data->Email, old('user_noti.'.$appTypeArr, json_decode($financial->approval_signatory_owner)->notify->$appTypeArr ?? [])) ? 'selected' : '' }}
                {{ old('signatory_user.'.$appTypeArr, json_decode($financial->approval_signatory_owner)->sign->$appTypeArr ?? '') == $add_users_data->id.":".$add_users_data->FirstName.":".$add_users_data->Email ? 'disabled' : '' }}
                >
                    {{ $add_users_data->FirstName." ".$add_users_data->LastName."(".$add_users_data->Email.")" }}
                </option>
                @endforeach
            </select>
        </div>
     </div>      
    </div>
    @php
        $approvalStatusForDisplay = json_decode($financial->approval_status, true);
        $currentStatusDisplay = is_array($approvalStatusForDisplay) ? ($approvalStatusForDisplay[$appTypeArr] ?? 'required') : (json_decode($financial->approval_status)->$appTypeArr ?? 'required');
    @endphp
    <div class="row add_users mt-1 users-{{$appType}}" style="display: {{ (old('approval_status.'.$appTypeArr, $currentStatusDisplay) == 'required') ? 'block':'none'}}">
        <label class="form-label mt-3">Approvers List For {{$title}}</label>
        @php
            $app_type = "approval_required_user_type".$appType;
            $app_type_users = "approval_required_users".$appType;
            $app_type_desg = "approval_required_desg".$appType;
            $app_type_role = "approval_required_role".$appType;
            $arrayData = json_decode($financial->$app_type_users);

            $approval_required_users = old($app_type_users, $arrayData); 

            $approval_required_type = old($app_type); 
            $approval_required_desg = old($app_type_desg); 
            $approval_required_role = old($app_type_role, []);

            // Prepare approval groups JSON for edit view
            $stored = $financial->$app_type_users ?? '{}';
            $decoded = json_decode($stored, true);
            $approval_groups = [];
            
            if(is_array($decoded) && count($decoded) > 0){
                // Check if it's already in the new parent-grouped format
                if(isset($decoded['review']) || isset($decoded['negotiation']) || isset($decoded['finalization']) || isset($decoded['approval']) || isset($decoded['signatory'])){
                    $approval_groups = $decoded;
                } else if(isset($decoded[0]['role'])){
                    // Legacy flat array with role field - transform to parent-grouped
                    $approval_groups = ['review' => [], 'negotiation' => [], 'finalization' => [], 'approval' => [], 'signatory' => []];
                    foreach($decoded as $group){
                        $role = $group['role'] ?? 'Approver';
                        unset($group['role']);
                        if($role === 'Signatory'){
                            $approval_groups['signatory'][] = $group;
                        } else if($role === 'Verifier' || $role === 'Preapprover'){
                            $approval_groups['review'][] = $group;
                        } else if($role === 'Negotiator'){
                            $approval_groups['negotiation'][] = $group;
                        } else if($role === 'Finalizer'){
                            $approval_groups['finalization'][] = $group;
                        } else {
                            $approval_groups['approval'][] = $group;
                        }
                    }
                } else {
                    // Legacy flat approver list - transform to single default group under approval
                    $flat = is_array($decoded) ? $decoded : [];
                    $approval_groups = [
                        'review' => [],
                        'negotiation' => [],
                        'finalization' => [],
                        'approval' => [[
                            'role' => 'Approver',
                            'approval_type' => (json_decode($financial->approval_type)->$appTypeArr ?? 'sequential'),
                            'dynamic_approver_enabled' => 0,
                            'approvers' => $flat
                        ]],
                        'signatory' => []
                    ];
                }
            } else {
                $approval_groups = ['review' => [], 'negotiation' => [], 'finalization' => [], 'approval' => [], 'signatory' => []];
            }
            $approval_groups_json = json_encode($approval_groups);
            
            // Get parent-level routing settings from stored data
            $parentRouting = $decoded['_parent_routing'] ?? [];
            $getParentApprove = function($type) use ($parentRouting) {
                return $parentRouting[$type]['on_approve'] ?? '';
            };
            $getParentReject = function($type) use ($parentRouting) {
                return $parentRouting[$type]['on_reject'] ?? '';
            };
        @endphp
        
        <div class="mb-3">
            <h6 class="fw-bold">Review Groups</h6>
            <div class="row mb-2">
                <div class="col-md-6">
                    <label>On Approve → Go to</label>
                    <select class="form-select parent-on-approve" data-parent-type="review" name="parent_on_approve_review">
                        <option value="">-- End Workflow --</option>
                        <option value="negotiation" {{ $getParentApprove('review') == 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                        <option value="finalization" {{ $getParentApprove('review') == 'finalization' ? 'selected' : '' }}>Finalization</option>
                        <option value="approval" {{ $getParentApprove('review') == 'approval' ? 'selected' : '' }}>Approval</option>
                        <option value="signatory" {{ $getParentApprove('review') == 'signatory' ? 'selected' : '' }}>Signatory</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>On Reject → Go to</label>
                    <select class="form-select parent-on-reject" data-parent-type="review" name="parent_on_reject_review">
                        <option value="">-- End Workflow --</option>
                        <option value="negotiation" {{ $getParentReject('review') == 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                        <option value="finalization" {{ $getParentReject('review') == 'finalization' ? 'selected' : '' }}>Finalization</option>
                        <option value="approval" {{ $getParentReject('review') == 'approval' ? 'selected' : '' }}>Approval</option>
                        <option value="signatory" {{ $getParentReject('review') == 'signatory' ? 'selected' : '' }}>Signatory</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center mb-2">
                <button type="button" class="btn btn-sm btn-primary add-approval-group" data-tab-type="{{$appType}}" data-parent-type="review">Add Review Group</button>
                <small class="text-muted">Review groups are processed first</small>
            </div>
            <div class="approval-groups-{{$appType}}-review"></div>
        </div>
        
        <div class="mb-3">
            <h6 class="fw-bold">Negotiation Groups</h6>
            <div class="row mb-2">
                <div class="col-md-6">
                    <label>On Approve → Go to</label>
                    <select class="form-select parent-on-approve" data-parent-type="negotiation" name="parent_on_approve_negotiation">
                        <option value="">-- End Workflow --</option>
                        <option value="review" {{ $getParentApprove('negotiation') == 'review' ? 'selected' : '' }}>Review</option>
                        <option value="finalization" {{ $getParentApprove('negotiation') == 'finalization' ? 'selected' : '' }}>Finalization</option>
                        <option value="approval" {{ $getParentApprove('negotiation') == 'approval' ? 'selected' : '' }}>Approval</option>
                        <option value="signatory" {{ $getParentApprove('negotiation') == 'signatory' ? 'selected' : '' }}>Signatory</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>On Reject → Go to</label>
                    <select class="form-select parent-on-reject" data-parent-type="negotiation" name="parent_on_reject_negotiation">
                        <option value="">-- End Workflow --</option>
                        <option value="review" {{ $getParentReject('negotiation') == 'review' ? 'selected' : '' }}>Review</option>
                        <option value="finalization" {{ $getParentReject('negotiation') == 'finalization' ? 'selected' : '' }}>Finalization</option>
                        <option value="approval" {{ $getParentReject('negotiation') == 'approval' ? 'selected' : '' }}>Approval</option>
                        <option value="signatory" {{ $getParentReject('negotiation') == 'signatory' ? 'selected' : '' }}>Signatory</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center mb-2">
                <button type="button" class="btn btn-sm btn-primary add-approval-group" data-tab-type="{{$appType}}" data-parent-type="negotiation">Add Negotiation Group</button>
                <small class="text-muted">Negotiation groups are processed after review</small>
            </div>
            <div class="approval-groups-{{$appType}}-negotiation"></div>
        </div>
        
        <div class="mb-3">
            <h6 class="fw-bold">Finalization Groups</h6>
            <div class="row mb-2">
                <div class="col-md-6">
                    <label>On Approve → Go to</label>
                    <select class="form-select parent-on-approve" data-parent-type="finalization" name="parent_on_approve_finalization">
                        <option value="">-- End Workflow --</option>
                        <option value="review" {{ $getParentApprove('finalization') == 'review' ? 'selected' : '' }}>Review</option>
                        <option value="negotiation" {{ $getParentApprove('finalization') == 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                        <option value="approval" {{ $getParentApprove('finalization') == 'approval' ? 'selected' : '' }}>Approval</option>
                        <option value="signatory" {{ $getParentApprove('finalization') == 'signatory' ? 'selected' : '' }}>Signatory</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>On Reject → Go to</label>
                    <select class="form-select parent-on-reject" data-parent-type="finalization" name="parent_on_reject_finalization">
                        <option value="">-- End Workflow --</option>
                        <option value="review" {{ $getParentReject('finalization') == 'review' ? 'selected' : '' }}>Review</option>
                        <option value="negotiation" {{ $getParentReject('finalization') == 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                        <option value="approval" {{ $getParentReject('finalization') == 'approval' ? 'selected' : '' }}>Approval</option>
                        <option value="signatory" {{ $getParentReject('finalization') == 'signatory' ? 'selected' : '' }}>Signatory</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center mb-2">
                <button type="button" class="btn btn-sm btn-primary add-approval-group" data-tab-type="{{$appType}}" data-parent-type="finalization">Add Finalization Group</button>
                <small class="text-muted">Finalization groups are processed after negotiation</small>
            </div>
            <div class="approval-groups-{{$appType}}-finalization"></div>
        </div>
        
        <div class="mb-3">
            <h6 class="fw-bold">Approval Groups</h6>
            <div class="row mb-2">
                <div class="col-md-6">
                    <label>On Approve → Go to</label>
                    <select class="form-select parent-on-approve" data-parent-type="approval" name="parent_on_approve_approval">
                        <option value="">-- End Workflow --</option>
                        <option value="review" {{ $getParentApprove('approval') == 'review' ? 'selected' : '' }}>Review</option>
                        <option value="negotiation" {{ $getParentApprove('approval') == 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                        <option value="finalization" {{ $getParentApprove('approval') == 'finalization' ? 'selected' : '' }}>Finalization</option>
                        <option value="signatory" {{ $getParentApprove('approval') == 'signatory' ? 'selected' : '' }}>Signatory</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>On Reject → Go to</label>
                    <select class="form-select parent-on-reject" data-parent-type="approval" name="parent_on_reject_approval">
                        <option value="">-- End Workflow --</option>
                        <option value="review" {{ $getParentReject('approval') == 'review' ? 'selected' : '' }}>Review</option>
                        <option value="negotiation" {{ $getParentReject('approval') == 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                        <option value="finalization" {{ $getParentReject('approval') == 'finalization' ? 'selected' : '' }}>Finalization</option>
                        <option value="signatory" {{ $getParentReject('approval') == 'signatory' ? 'selected' : '' }}>Signatory</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center mb-2">
                <button type="button" class="btn btn-sm btn-primary add-approval-group" data-tab-type="{{$appType}}" data-parent-type="approval">Add Approval Group</button>
                <small class="text-muted">Approval groups are processed after finalization</small>
            </div>
            <div class="approval-groups-{{$appType}}-approval"></div>
        </div>
        
        <div class="mb-3">
            <h6 class="fw-bold">Signatory Groups</h6>
            <div class="row mb-2">
                <div class="col-md-6">
                    <label>On Approve → Go to</label>
                    <select class="form-select parent-on-approve" data-parent-type="signatory" name="parent_on_approve_signatory">
                        <option value="">-- End Workflow --</option>
                        <option value="review" {{ $getParentApprove('signatory') == 'review' ? 'selected' : '' }}>Review</option>
                        <option value="negotiation" {{ $getParentApprove('signatory') == 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                        <option value="finalization" {{ $getParentApprove('signatory') == 'finalization' ? 'selected' : '' }}>Finalization</option>
                        <option value="approval" {{ $getParentApprove('signatory') == 'approval' ? 'selected' : '' }}>Approval</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>On Reject → Go to</label>
                    <select class="form-select parent-on-reject" data-parent-type="signatory" name="parent_on_reject_signatory">
                        <option value="">-- End Workflow --</option>
                        <option value="review" {{ $getParentReject('signatory') == 'review' ? 'selected' : '' }}>Review</option>
                        <option value="negotiation" {{ $getParentReject('signatory') == 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                        <option value="finalization" {{ $getParentReject('signatory') == 'finalization' ? 'selected' : '' }}>Finalization</option>
                        <option value="approval" {{ $getParentReject('signatory') == 'approval' ? 'selected' : '' }}>Approval</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center mb-2">
                <button type="button" class="btn btn-sm btn-primary add-approval-group" data-tab-type="{{$appType}}" data-parent-type="signatory">Add Signatory Group</button>
                <small class="text-muted">Signatory groups are processed last</small>
            </div>
            <div class="approval-groups-{{$appType}}-signatory"></div>
        </div>
        
        <input type="hidden" name="approval_groups{{$appType}}" id="approval_groups{{$appType}}" value="{{ old('approval_groups'.$appType, $approval_groups_json) }}">
    </div>
    <div class="copyRulesFrom{{$appType}}">
        <label class="form-label mb-2 mt-3">Copy Approval Rules</label><br/>
        @foreach(config('app.APPROVAL_TYPES', []) as $appCheck)
            @php
            $checkBoxId = $appCheck != '' ? '_'.$appCheck : $appCheck;
            $checkBoxName = $appCheck != '' ? $appCheck : 'New';
            @endphp
            @if($checkBoxId != $appType)
                <div class="form-check form-check-inline">
                  <input class="form-check-input copyApprovers" checked type="checkbox" id="approvalCopy{{$checkBoxId}}" data-row-type="{{$checkBoxId}}" />
                  <label class="form-check-label" for="approvalCopy{{$checkBoxId}}">{{ucfirst(str_replace('_',' ',$checkBoxName))}}</label>
                </div>
            @endif
        @endforeach
        <button type="button" data-btn-type="{{$appType}}" class="btn btn-sm btn-primary applyApprRules">Apply</button>
    </div>    
</div>