@php
    $userInfo = \App\Helpers\Helpers::userInfo();
    $handoffStatus = strtolower((string) ($contract->contract_status ?? ''));
    $handoffSubstatus = strtolower((string) ($contract->substatus ?? ''));
    $isOwnerHandoffPendingStage = in_array($handoffStatus, ['review', 'approval'], true)
        || in_array($handoffSubstatus, ['in review', 'pending approval', 'pending'], true);
    $isOwnerHandoffPostApprovalStage = in_array($handoffStatus, ['signing', 'executed', 'active', 'expired', 'terminated', 'completed'], true);
    $showOwnerHandoff = $isOwnerHandoffPendingStage && !$isOwnerHandoffPostApprovalStage;

    
@endphp
<div class="container">
    <div class="row">
        <div class="col-md-12">
            @if(!empty($userCanGate) && $userCanGate && $showOwnerHandoff)
                @if(!empty($canAdvanceNext) && $canAdvanceNext)
                    @php $hasExternalRepresentativeOptions = !empty($externalRepresentativeOptions) && count($externalRepresentativeOptions) > 0; @endphp
                    <div class="card mb-3 mt-3 border-primary">
                        <div class="card-body d-flex flex-column gap-3">
                            <div>
                                <h6 class="mb-1">Owner Handoff</h6>
                                <p class="mb-0 text-muted">Current completed level is waiting for owner action to trigger the next level.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-end">
                                <form method="POST" action="{{ route('contracts.approval.advance.next', ['id' => $contract->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Send To Next Level</button>
                                </form>

                                <form method="POST" action="{{ route('contracts.approval.preapprover.add', ['id' => $contract->id]) }}" class="d-flex flex-wrap gap-2 align-items-end flex-grow-1">
                                    @csrf
                                    <div class="flex-grow-1">
                                        <label class="form-label mb-2" for="preapproverSelect">Add External Pre-Approver(s)</label>
                                        <div class="select2-style-container" id="preapproverContainer">
                                            <div class="select2-selection">
                                                <div class="select2-selection__rendered">
                                                    <ul class="select2-selection__rendered-list" id="selectedItems"></ul>
                                                    <input type="text" class="select2-search-input" id="searchInput" placeholder="Search or add pre-approvers..." {{ $hasExternalRepresentativeOptions ? '' : 'disabled' }}>
                                                </div>
                                                <span class="select2-arrow">
                                                    <b></b>
                                                </span>
                                            </div>
                                            <select name="representative_ids[]" id="preapproverSelect" class="form-select d-none" multiple {{ $hasExternalRepresentativeOptions ? '' : 'disabled' }}>
                                                @foreach(($externalRepresentativeOptions ?? collect()) as $opt)
                                                    <option value="{{ $opt['representative_id'] }}">{{ $opt['label'] }}</option>
                                                @endforeach
                                            </select>
                                            @if(!$hasExternalRepresentativeOptions)
                                                <div class="alert alert-info py-2 px-3 mt-2 mb-0 small">No active external representative options available.</div>
                                            @endif
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm" id="submitPreapprover" {{ $hasExternalRepresentativeOptions ? '' : 'disabled' }}>Add Pre-Approver Group</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            @if(strtolower($contract->contract_status) == 'signing' && strtolower($contract->substatus) == 'approved')
                @php
                    $userIsOwner = false;
                    try {
                        $ownerId = $contract->created_by;
                        $ownerEmail = "";
                        $ownerUser = \App\Models\AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $ownerId)->first();
                        if ($ownerUser) {
                            $ownerEmail = $ownerUser->Email;
                        }                        
                        $currentEmail = optional($userInfo)->email ?? '';
                        if ($ownerEmail && strtolower($ownerEmail) === strtolower($currentEmail)) $userIsOwner = true;
                    } catch (\Throwable $e) { $userIsOwner = false; }
                    $isSignActionsVisible = ($userIsOwner || session()->get('contractSessionUserRole') == 'Admin' || session()->get('contractSessionUserRole') == 'Super Admin');
                @endphp

                @if($isSignActionsVisible)
                    @php
                        try {
                            $signId = $contract->signatory;
                            $signEmail = "";
                            $signName = "";
                            $signUser = \App\Models\AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $signId)->first();
                            if ($signUser) {
                                $signEmail = $signUser->Email;
                                $signName = $signUser->FirstName;
                            }                        
                            } catch (\Throwable $e) { $userIsOwner = false; }
                    @endphp
                    <div class="mb-3 mt-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body d-flex flex-column flex-sm-row align-items-start gap-3">
                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center" style="width:56px;">
                                    <i class="ti ti-pdf text-danger" style="font-size:28px;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                                        <div>
                                            <div class="text-muted small">Signing Authority: <b> {{ $signName }} ({{ $signEmail }})</b></div>                                            
                                            <div class="fw-semibold">Signing Actions</div>
                                            <div class="text-muted small">Upload or capture signatures for the finalized contract</div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button class="btn btn-sm btn-outline-primary d-flex align-items-center" title="Upload Signed PDF" data-bs-toggle="modal" data-bs-target="#uploadSignedPdfModal" {{ empty($contract->contract_attachment) ? 'disabled' : '' }}><i class="bi bi-upload me-1"></i>Upload PDF</button>
                                            <!--<button class="btn btn-sm btn-outline-secondary d-flex align-items-center" title="Draw Signature" data-bs-toggle="modal" data-bs-target="#drawSignatureModal" {{ empty($contract->contract_attachment) ? 'disabled' : '' }}><i class="bi bi-pencil-square me-1"></i>Draw</button>
                                            <button class="btn btn-sm btn-outline-info d-flex align-items-center" title="Upload Signature Image" data-bs-toggle="modal" data-bs-target="#uploadSignatureModal" {{ empty($contract->contract_attachment) ? 'disabled' : '' }}><i class="bi bi-image me-1"></i>Upload Sig</button>-->
                                            <button class="btn btn-sm btn-outline-warning d-flex align-items-center" title="eSign using provider" data-bs-toggle="modal" data-bs-target="#esignModal" {{ empty($contract->contract_attachment) ? 'disabled' : '' }}><i class="bi bi-key me-1"></i>eSign</button>
                                        </div>
                                    </div>
                                    <div class="mt-2"><small class="text-muted">If the contract template is not created, these options are disabled.</small></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Signed PDF Modal -->
                    <div class="modal fade" id="uploadSignedPdfModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ url('contracts/approval/'.$contract->id.'/complete-sign') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Upload Signed PDF</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Signed PDF</label>
                                            <input type="file" name="signed_file" accept="application/pdf" class="form-control" required />
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Upload</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Draw Signature Modal -->
                    <div class="modal fade" id="drawSignatureModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Draw Signature</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST" action="{{ url('contracts/approval/contract-custom/'.$contract->id.'/complete-sign') }}">
                                    @csrf
                                    <div class="modal-body">
                                        <canvas id="signaturePad" style="border:1px solid #ddd; width:100%; height:200px;"></canvas>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSignature">Clear</button>
                                        </div>
                                        <input type="hidden" name="signed_file_base64" id="signedFileBase64" />
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary" id="saveSignatureBtn">Save Signature</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Signature Modal (png/jpeg) -->
                    <div class="modal fade" id="uploadSignatureModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ url('contracts/approval/contract-custom/'.$contract->id.'/complete-sign') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Upload Signature Image</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Signature Image (png/jpg)</label>
                                            <input type="file" name="signature_image" accept="image/*" class="form-control" required />
                                            <small class="text-muted">Uploaded image will be saved as base64 and processed by admin.</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Upload</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- eSign Modal (send to provider + notify) -->
                    <div class="modal fade" id="esignModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Send for eSign</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="esignStatusArea">
                                        <p class="mb-2">This will send the contract to current approvers for e-sign using the configured eSign provider. Approvers will receive the sign link by email.</p>
                                        <div class="mb-2"><strong>Contract:</strong> {{ $contract->contract_unique_id ?? $contract->id }}</div>
                                        <div id="esignResult" style="display:none;"></div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" id="sendEsignBtn" class="btn btn-primary" data-contract-id="{{ $contract->id }}">Send eSign</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            @php
                    $hasPending = false;
                    foreach ($approvals as $a) {
                        try { $st = strtolower(decryptString($a->approval_status, 'approval_status') ?? $a->approval_status); } catch (\Throwable $e) { $st = strtolower($a->approval_status ?? ''); }
                        if ($st === 'pending') { $hasPending = true; break; }
                    }
                @endphp

                @if($hasPending && !(strtolower($contract->contract_status) == 'signing' && strtolower($contract->substatus) == 'approved'))
                <div class="approval-timeline-header d-flex align-items-center gap-2 mb-3">
                    <h2 class="mb-0" id="approvalTimelineHeading"><i class="bi bi-clock-history me-2 text-primary"></i>Approval Timeline</h2>
                    <span class="badge bg-primary rounded-pill" aria-label="Pending approvals count">{{ collect($approvals)->filter(function($a){ try { $s = strtolower(decryptString($a->approval_status, 'approval_status') ?? $a->approval_status); } catch(\Throwable $e) { $s = strtolower($a->approval_status ?? ''); } return $s === 'pending'; })->count() }} pending</span>
                </div>
                @php
                    // Group approvals by `unique_id` and show groups sequentially: show completed groups and the first incomplete group only
                    $groups = [];
                    $groupOrder = [];
                    foreach ($approvals as $a) {
                        $uid = $a->unique_id ?? 'ungrouped';
                        $groups[$uid][] = $a;
                        if (!isset($groupOrder[$uid]) || $a->orderval < $groupOrder[$uid]) {
                            $groupOrder[$uid] = $a->orderval;
                        }
                    }
                    // sort by minimal ordinality
                    uasort($groupOrder, function($a, $b){ return $a - $b; });

                    $visibleGroups = [];
                    $stop = false;
                    foreach (array_keys($groupOrder) as $uid) {
                        if ($stop) break;
                        $group = $groups[$uid];
                        $allApproved = true;
                        foreach ($group as $g) {
                            try { $st = strtolower(decryptString($g->approval_status, 'approval_status') ?? $g->approval_status); } catch (\Throwable $e) { $st = strtolower($g->approval_status ?? ''); }
                            if ($st !== 'approved') { $allApproved = false; break; }
                        }
                        $visibleGroups[$uid] = $group;
                        if (!$allApproved) { $stop = true; }
                    }
                @endphp
                <div class="timeline-flow-chart" role="list" aria-labelledby="approvalTimelineHeading">
                @foreach($visibleGroups as $uid => $group)
                    @php
                        $groupType = strtolower($group[0]->approval_type_row ?? ($group[0]->approval_type ?? 'sequential'));
                        $groupStageType = strtolower($group[0]->stage_type ?? 'internal');
                        $groupAutoNext = (int)($group[0]->auto_next_enabled ?? 0) === 1;
                        $groupAwaitingOwner = (int)($group[0]->awaiting_owner_trigger ?? 0) === 1;
                        $groupDynamicApproverEnabled = (int)($group[0]->dynamic_approver_enabled ?? 0) === 1;
                        $groupLabel = $groupStageType === 'external_pre' ? 'External Pre-Approver' : 'Internal Approver';
                    @endphp
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-2 mt-3">
                        <span class="badge bg-light text-dark border">{{ $groupLabel }} Group</span>
                        <span class="badge {{ $groupType === 'parallel' ? 'bg-info' : 'bg-secondary' }}">{{ ucfirst($groupType) }}</span>
                        <span class="badge {{ $groupAutoNext ? 'bg-success' : 'bg-warning text-dark' }}">{{ $groupAutoNext ? 'Auto Next' : 'Manual Next' }}</span>
                        @if($groupDynamicApproverEnabled)
                            <span class="badge bg-dark">Dynamic Approvers</span>
                        @endif
                        @if($groupAwaitingOwner)
                            <span class="badge bg-primary">Waiting Owner Trigger</span>
                        @endif
                    </div>
                @foreach($group as $approval)
                    @php
                        try { $st = strtolower(decryptString($approval->approval_status, 'approval_status') ?? $approval->approval_status); } catch (\Throwable $e) { $st = strtolower($approval->approval_status ?? 'pending'); }
                    @endphp
                    @if($groupType === 'sequential' && $st === 'pending' && (int)$approval->flag !== 1)
                        @continue
                    @endif
                    <div class="timeline-item roleeee{{session()->get('contractSessionUserRole')}}" role="listitem" aria-label="Approval step for {{ $approval->approver_name }}">
                        <div class="timeline-marker {{ $approval->flag == 1 ? 'active' : '' }} {{ $st === 'approved' ? 'completed' : ($st === 'rejected' ? 'rejected' : '') }}" aria-hidden="true"></div>
                        <div class="timeline-content">
                            @php
                                $userEmail = strtolower(optional($userInfo)->email ?? '');
                                $userRole = session()->get('contractSessionUserRole');
                                $isAdmin = ($userRole === 'Super Admin') || (optional($userInfo)->email ?? '') === 'admin@legalitysimplified.com';
                                $isActive = ($approval->flag == 1);
                                $status = strtolower(decryptString($approval->approval_status, 'approval_status') ?? 'pending');
                                $isCurrent = $isAdmin || ($isActive && $userEmail !== '' && strtolower($approval->approver_email ?? '') === $userEmail);
                                $groupLocked = in_array($approval->approver_type_row ?? '', $lockedGroups ?? []);
                                $canAct = ($isCurrent || $isAdmin) && $status === 'pending';
                                $canEditInputs = ($isCurrent || $isAdmin) && !$groupLocked && $status === 'pending';

                                // Determine if the approver is the contract owner (to change label)
                                $isApproverOwner = false;
                                try {
                                    $ownerEmail = null;
                                    if (!empty($contract->created_by)) {
                                        $ownerUser = \App\Models\AddUsers::select(decrypt_data('Email', 'AddUsers'))->where('id', $contract->created_by)->first();
                                        if ($ownerUser) $ownerEmail = $ownerUser->Email ?? null;
                                    }
                                    if ($ownerEmail && strtolower($approval->approver_email ?? '') === strtolower($ownerEmail)) {
                                        $isApproverOwner = true;
                                    }
                                } catch (\Throwable $e) { /* ignore */ }

                                // Stage-aware Approve/Reject button labels
                                $stage = strtolower((string) ($contract->contract_status ?? ''));
                                $isEarlyStage = in_array($stage, ['review', 'negotiation', 'finalization', 'finalize'], true);
                                $approveLabel = $isEarlyStage ? 'Reviewed' : ($isApproverOwner ? 'Send to Approval' : 'Approve');
                                $rejectLabel = in_array($stage, ['draft','review', 'negotiation', 'finalization', 'finalize', 'approval'], true) ? 'Suggest Changes' : 'Reject';
                            @endphp

                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div class="flex-grow-1">
                                    <h4 class="mb-1 d-flex align-items-center gap-2">
                                        <i class="bi {{ $st === 'approved' ? 'bi-check-circle-fill text-success' : ($st === 'rejected' ? 'bi-x-circle-fill text-danger' : 'bi-hourglass-split text-warning') }}" aria-hidden="true"></i>
                                        {{ $approval->approver_name }}
                                        <small class="text-muted fw-normal">({{ $approval->approver_email }})</small>
                                    </h4>
                                    @if($isAdmin && !$isActive)
                                        <p class="mb-1"><span class="badge bg-info-subtle text-info border border-info"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Admin override</span></p>
                                    @endif
                                    @php
                                        $status = strtolower(decryptString($approval->approval_status, 'approval_status') ?? 'pending');
                                        $badge = ($status === 'approved') ? 'success' : (($status === 'rejected') ? 'danger' : 'warning');
                                        $statusIcon = ($status === 'approved') ? 'bi-check-lg' : (($status === 'rejected') ? 'bi-x-lg' : 'bi-clock');
                                    @endphp
                                    <p class="mb-1"><strong>Status:</strong> <span class="badge bg-{{ $badge }} d-inline-flex align-items-center gap-1" role="status"><i class="bi {{ $statusIcon }}" aria-hidden="true"></i>{{ ucfirst($status) }}</span></p>
                                    <p class="mb-0"><strong>Comments:</strong> {{ decryptString($approval->next_action_item, 'next_action_item') }}</p>
                                    <p class="mb-0"><small class="text-muted">{{ decryptString($approval->next_action_description, 'next_action_description') }}</small></p>

                                    @if($status !== 'pending')
                                        <p class="mb-0 small text-muted mt-1">
                                            @php
                                                $actorName = null; $actorEmail = null;
                                                if(!empty($approval->updated_by)){
                                                    try { $actorJson = json_decode($approval->updated_by); if ($actorJson) { $actorName = $actorJson->name ?? null; $actorEmail = $actorJson->email ?? null; } } catch (\Throwable $e) { }
                                                }
                                                if(empty($actorName)) { $actorName = $approval->approver_name ?? null; }
                                            @endphp

                                            @if($status === 'approved')
                                                <i class="bi bi-check2-circle text-success me-1" aria-hidden="true"></i>Approved by <strong>{{ $actorName ?? $approval->approver_name }}</strong> on {{ $approval->updated_on ? \Carbon\Carbon::parse($approval->updated_on)->format('d M Y H:i') : '-' }}
                                            @elseif($status === 'rejected')
                                                <i class="bi bi-x-circle text-danger me-1" aria-hidden="true"></i>Rejected by <strong>{{ $actorName ?? $approval->approver_name }}</strong> on {{ $approval->updated_on ? \Carbon\Carbon::parse($approval->updated_on)->format('d M Y H:i') : '-' }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <!-- Action feedback area -->
                                    <div class="approval-feedback mb-2" id="approvalFeedback{{ $approval->id }}" style="display:none;" role="alert" aria-live="polite"></div>

                                    <div class="d-flex flex-column gap-2 align-items-end" role="group" aria-label="Approval actions for {{ $approval->approver_name }}">
                                        @if($status === 'pending')
                                            <button type="button"
                                                class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#approvalDetails{{ $approval->id }}"
                                                aria-expanded="false"
                                                aria-controls="approvalDetails{{ $approval->id }}"
                                                title="View action details and description for this approval step">
                                                <i class="bi bi-info-circle" aria-hidden="true"></i>
                                                <span>Details</span>
                                            </button>
                                        @endif

                                        @if($canAct)
                                            <div class="d-flex gap-2">
                                                <button type="button"
                                                    class="btn btn-success btn-sm approve-btn d-inline-flex align-items-center gap-1 shadow-sm"
                                                    data-action="approve"
                                                    data-target="#approvalForm{{ $approval->id }}"
                                                    data-approval-id="{{ $approval->id }}"
                                                    aria-label="{{ $isApproverOwner ? 'Send contract to approval workflow' : $approveLabel . ' this approval step' }}"
                                                    title="{{ $isApproverOwner ? 'Submit contract into the approval workflow' : $approveLabel . ' and advance to next step' }}">
                                                    <i class="bi {{ $isApproverOwner ? 'bi-send' : 'bi-check-circle' }}" aria-hidden="true"></i>
                                                    <span>{{ $approveLabel }}</span>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-outline-danger btn-sm reject-btn d-inline-flex align-items-center gap-1"
                                                    data-action="reject"
                                                    data-target="#approvalForm{{ $approval->id }}"
                                                    data-approval-id="{{ $approval->id }}"
                                                    data-approver-name="{{ $approval->approver_name }}"
                                                    data-reject-label="{{ $rejectLabel }}"
                                                    aria-label="{{ $rejectLabel }} this approval step"
                                                    title="{{ $rejectLabel }} and return for revision — a reason will be required">
                                                    <i class="bi bi-x-circle" aria-hidden="true"></i>
                                                    <span>{{ $rejectLabel }}</span>
                                                </button>
                                            </div>
                                        @elseif($status !== 'pending')
                                            <span class="badge bg-light text-muted border d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-check2-all" aria-hidden="true"></i> Completed
                                            </span>
                                        @else
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1 opacity-50" disabled aria-disabled="true" title="Awaiting previous approval steps">
                                                    <i class="bi {{ $isApproverOwner ? 'bi-send' : 'bi-check-circle' }}" aria-hidden="true"></i>
                                                    <span>{{ $approveLabel }}</span>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1 opacity-50" disabled aria-disabled="true" title="Awaiting previous approval steps">
                                                    <i class="bi bi-x-circle" aria-hidden="true"></i>
                                                    <span>{{ $rejectLabel }}</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

@if($status === 'pending')
                                    <div class="collapse mt-2" id="approvalDetails{{ $approval->id }}" role="region" aria-label="Details for {{ $approval->approver_name }}">
                                        <div class="card card-body p-3 border-start border-primary border-3 bg-light">
                                            <p class="mb-1 d-flex align-items-center gap-1"><i class="bi bi-lightning-charge text-primary" aria-hidden="true"></i><strong>Action:</strong> {{ decryptString($approval->next_action_item, 'next_action_item') }}</p>
                                            <p class="mb-0 d-flex align-items-start gap-1"><i class="bi bi-card-text text-secondary" aria-hidden="true"></i><strong>Description:</strong> {{ decryptString($approval->next_action_description, 'next_action_description') }}</p>
                                        </div>
                                    </div>
                                @endif

@if($status === 'pending')
                                    <div class="approval-controls mt-3">
                                        <div class="collapse" id="approvalForm{{ $approval->id }}" role="region" aria-label="Approval form for {{ $approval->approver_name }}">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-body p-3">
                                                    <!-- Context banner for the selected action -->
                                                    <div class="approval-action-banner mb-3 p-2 rounded d-none" id="actionBanner{{ $approval->id }}" role="status" aria-live="polite"></div>

                                                    <form method="POST"
                                                          action="{{ route('contracts.approval.respond.new', ['id' => $contract->id, 'approvalId' => $approval->id]) }}"
                                                          class="approval-form"
                                                          data-approval-id="{{ $approval->id }}">
                                                        @csrf
                                                        <input type="hidden" name="action" value="" class="approval-action-input">

                                                        @if($canEditInputs)
                                                            <fieldset>
                                                                <legend class="fs-6 fw-semibold mb-2"><i class="bi bi-calendar-range me-1 text-primary" aria-hidden="true"></i>Contract Details</legend>
                                                                <div class="row g-2 mb-3">
                                                                    <div class="col-md-4">
                                                                        <label class="form-label" for="startDate{{ $approval->id }}">Start Date</label>
                                                                        <input type="date" id="startDate{{ $approval->id }}" name="contract_start_date" value="{{ old('contract_start_date', $contract->fixed_date) }}" class="form-control form-control-sm" aria-describedby="startDateHelp{{ $approval->id }}" />
                                                                        <div id="startDateHelp{{ $approval->id }}" class="form-text">Contract effective start date</div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <label class="form-label" for="endDate{{ $approval->id }}">End Date</label>
                                                                        <input type="date" id="endDate{{ $approval->id }}" name="contract_end_date" value="{{ old('contract_end_date', $contract->contract_end_date) }}" class="form-control form-control-sm" aria-describedby="endDateHelp{{ $approval->id }}" />
                                                                        <div id="endDateHelp{{ $approval->id }}" class="form-text">Contract expiration date</div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <label class="form-label" for="contractValue{{ $approval->id }}">Contract Value</label>
                                                                        <input type="text" id="contractValue{{ $approval->id }}" name="contract_value" value="{{ old('contract_value', decryptString($contract->currency_value ?? '', 'currency_value') ?? '') }}" class="form-control form-control-sm" aria-describedby="valueHelp{{ $approval->id }}" />
                                                                        <div id="valueHelp{{ $approval->id }}" class="form-text">Total contract monetary value</div>
                                                                    </div>
                                                                </div>
                                                            </fieldset>
                                                        @else
                                                            @if($groupLocked)
                                                                <div class="alert alert-warning py-2 d-flex align-items-center gap-2" role="alert">
                                                                    <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                                                    <span>Group locked — another approver already approved this group. You can still approve or reject.</span>
                                                                </div>
                                                            @endif
                                                        @endif

                                                        <div class="mb-3">
                                                            <label class="form-label" for="comments{{ $approval->id }}">
                                                                <i class="bi bi-chat-left-text me-1" aria-hidden="true"></i>Comments
                                                                <span class="comments-required-label text-danger d-none">*</span>
                                                                <span class="comments-optional-label text-muted">(optional)</span>
                                                            </label>
                                                            <textarea name="comments" id="comments{{ $approval->id }}" class="form-control" rows="2" placeholder="Add any notes or feedback..."
                                                                      aria-describedby="commentsHelp{{ $approval->id }}"></textarea>
                                                            <div id="commentsHelp{{ $approval->id }}" class="form-text">Provide context for your decision. Required when rejecting.</div>
                                                        </div>

                                                        <div class="d-flex justify-content-end gap-2 align-items-center">
                                                            <button type="button"
                                                                    class="btn btn-light btn-sm d-inline-flex align-items-center gap-1"
                                                                    data-bs-toggle="collapse"
                                                                    data-bs-target="#approvalForm{{ $approval->id }}"
                                                                    aria-label="Cancel and close form">
                                                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                                                                <span>Cancel</span>
                                                            </button>
                                                            <button type="submit"
                                                                    class="btn btn-primary btn-sm approval-submit-btn d-inline-flex align-items-center gap-1"
                                                                    data-approval-id="{{ $approval->id }}">
                                                                <span class="submit-label"><i class="bi bi-send me-1" aria-hidden="true"></i>Submit</span>
                                                                <span class="submit-loading d-none"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting&hellip;</span>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                        </div>
                    </div>
                @endforeach
                @endforeach
            </div>

            @endif

            <!-- Rejection Confirmation Modal -->
            <div class="modal fade" id="rejectConfirmModal" tabindex="-1" aria-labelledby="rejectConfirmModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-danger border-top border-3">
                        <div class="modal-header bg-danger bg-opacity-10">
                            <h5 class="modal-title d-flex align-items-center gap-2" id="rejectConfirmModalLabel">
                                <i class="bi bi-exclamation-triangle-fill text-danger" aria-hidden="true"></i>
                                Confirm <span id="rejectConfirmModalAction">Rejection</span>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2">You are about to <strong class="text-danger">reject</strong> the approval step for:</p>
                            <p class="fw-semibold mb-3" id="rejectConfirmApproverName"></p>
                            <div class="alert alert-warning py-2 d-flex align-items-start gap-2" role="alert">
                                <i class="bi bi-info-circle-fill mt-1" aria-hidden="true"></i>
                                <div>
                                    <strong>This action cannot be undone.</strong> The approval workflow will be paused and the contract owner will be notified. A reason is required.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="rejectConfirmReason"><strong>Reason for rejection</strong> <span class="text-danger">*</span></label>
                                <textarea id="rejectConfirmReason" class="form-control" rows="3" required placeholder="Explain why this approval is being rejected..." aria-required="true"></textarea>
                                <div class="invalid-feedback" id="rejectReasonError">A reason is required to reject.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light d-inline-flex align-items-center gap-1" data-bs-dismiss="modal">
                                <i class="bi bi-arrow-left" aria-hidden="true"></i> Go Back
                            </button>
                            <button type="button" id="rejectConfirmSubmit" class="btn btn-danger d-inline-flex align-items-center gap-1">
                                <i class="bi bi-x-circle" aria-hidden="true"></i> Confirm <span id="rejectConfirmSubmitAction">Rejection</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @if(!$hasPending)
                <div class="card mb-3 mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Approval History</h5>
                        <small class="text-muted">Completed approvals</small>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                        @foreach($approvals as $approval)
                            @php
                                try { $status = strtolower(decryptString($approval->approval_status, 'approval_status') ?? $approval->approval_status); } catch (\Throwable $e) { $status = strtolower($approval->approval_status ?? ''); }
                                if ($status === 'pending') continue;
                                $badge = ($status === 'approved') ? 'success' : (($status === 'rejected') ? 'danger' : 'secondary');
                                // actor: prefer updated_by, fallback to created_by
                                $actorName = null; $actorEmail = null;
                                if (!empty($approval->updated_by)) {
                                    try { $actorJson = json_decode($approval->updated_by); if ($actorJson) { $actorName = $actorJson->name ?? null; $actorEmail = $actorJson->email ?? null; } } catch (\Throwable $e) {}
                                }
                                if (empty($actorName) && !empty($approval->created_by)) {
                                    try { $actorJson = json_decode($approval->created_by); if ($actorJson) { $actorName = $actorJson->name ?? null; $actorEmail = $actorJson->email ?? null; } } catch (\Throwable $e) {}
                                }
                                $when = $approval->updated_on ?? $approval->created_at ?? null;
                            @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="me-3">
                                    <div class="fw-semibold">{{ $approval->approver_name }} <small class="text-muted">({{ $approval->approver_email }})</small></div>
                                    <div class="small text-muted">{{ function_exists('decryptString') ? @decryptString($approval->next_action_item, 'next_action_item') : $approval->next_action_item }}</div>
                                    <div class="mt-1">{{ function_exists('decryptString') ? @decryptString($approval->next_action_description, 'next_action_description') : $approval->next_action_description }}</div>
                                    <div class="text-muted small mt-1">Action by: <strong>{{ $actorName ?? '—' }}</strong> ({{ $actorEmail ?? '—' }})</div>
                                    <div><span class="badge bg-info text-dark">Stage: {{ $approval->stage_name ?? '—' }}</span></div>
                                    <!-- <div class="text-muted small mt-1">Stage: <strong>{{ $approval->stage_name ?? '—' }}</strong></div> -->
                                </div>
                                <div class="text-end">
                                    <div><span class="badge bg-{{ $badge }}">{{ ucfirst($status) }}</span></div>
                                    <div class="small text-muted mt-1">{{ $when ? \Carbon\Carbon::parse($when)->format('d M Y H:i') : '-' }}</div>
                                </div>
                            </li>
                        @endforeach
                        </ul>
                    </div>
                </div>
            @endif            
            @if(isset($contract->contract_attachment_filename))
                <a href="{{attachmentDummyUrl($contract->contract_attachment, true, $contract->id)}}" class="d-none" target="_blank">
                    {{$contract->contract_attachment_filename}}
                </a>   
            @endif            
        </div>
    </div>
</div>

<script>
(function(){
    'use strict';

    // ---- Approval button handler (approve opens form, reject opens confirmation modal) ----
    var pendingRejectTarget = null;
    var pendingRejectApprovalId = null;

    document.addEventListener('click', function(e){
        var btn = e.target.closest('.approve-btn, .reject-btn');
        if(!btn) return;
        var action = btn.getAttribute('data-action');
        var target = btn.getAttribute('data-target') || btn.getAttribute('data-bs-target');
        var approvalId = btn.getAttribute('data-approval-id');
        if(!target) return;

        if(action === 'reject') {
            // Show rejection confirmation modal
            pendingRejectTarget = target;
            pendingRejectApprovalId = approvalId;
            var approverName = btn.getAttribute('data-approver-name') || '';
            var nameEl = document.getElementById('rejectConfirmApproverName');
            if(nameEl) nameEl.textContent = approverName;
            var rejectLabel = btn.getAttribute('data-reject-label') || 'Rejection';
            var modalActionEl = document.getElementById('rejectConfirmModalAction');
            if(modalActionEl) modalActionEl.textContent = rejectLabel;
            var submitActionEl = document.getElementById('rejectConfirmSubmitAction');
            if(submitActionEl) submitActionEl.textContent = rejectLabel;
            var reasonField = document.getElementById('rejectConfirmReason');
            if(reasonField) { reasonField.value = ''; reasonField.classList.remove('is-invalid'); }
            var modal = new bootstrap.Modal(document.getElementById('rejectConfirmModal'));
            modal.show();
            return;
        }

        // Approve action: open the form and set action
        openApprovalForm(target, action, approvalId);
    });

    function openApprovalForm(target, action, approvalId) {
        var form = document.querySelector(target + ' form');
        if(!form) return;
        var input = form.querySelector('.approval-action-input');
        if(!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'action';
            input.className = 'approval-action-input';
            form.prepend(input);
        }
        input.value = action;

        // Update action banner
        var banner = document.getElementById('actionBanner' + approvalId);
        if(banner) {
            banner.classList.remove('d-none');
            if(action === 'approve') {
                banner.className = 'approval-action-banner mb-3 p-2 rounded bg-success bg-opacity-10 text-success border border-success';
                banner.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> You are <strong>approving</strong> this step. Review details and click Submit.';
            } else {
                banner.className = 'approval-action-banner mb-3 p-2 rounded bg-danger bg-opacity-10 text-danger border border-danger';
                banner.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i> You are <strong>rejecting</strong> this step. A reason is required.';
            }
        }

        // Toggle comments required/optional labels
        var reqLabel = form.querySelector('.comments-required-label');
        var optLabel = form.querySelector('.comments-optional-label');
        var textarea = form.querySelector('textarea[name="comments"]');
        if(action === 'reject') {
            if(reqLabel) reqLabel.classList.remove('d-none');
            if(optLabel) optLabel.classList.add('d-none');
            if(textarea) textarea.setAttribute('required', 'required');
        } else {
            if(reqLabel) reqLabel.classList.add('d-none');
            if(optLabel) optLabel.classList.remove('d-none');
            if(textarea) textarea.removeAttribute('required');
        }

        // Open collapse
        var collapseEl = document.querySelector(target);
        if(collapseEl && typeof bootstrap !== 'undefined') {
            var bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
            bsCollapse.show();
        } else if(collapseEl && !collapseEl.classList.contains('show')) {
            collapseEl.classList.add('show');
        }
        if(textarea) setTimeout(function(){ textarea.focus(); }, 300);
    }

    // ---- Rejection confirmation modal submit ----
    var confirmBtn = document.getElementById('rejectConfirmSubmit');
    if(confirmBtn) {
        confirmBtn.addEventListener('click', function(){
            var reasonField = document.getElementById('rejectConfirmReason');
            var reason = (reasonField ? reasonField.value : '').trim();
            if(!reason) {
                reasonField.classList.add('is-invalid');
                reasonField.focus();
                return;
            }
            reasonField.classList.remove('is-invalid');

            // Close modal
            var modalEl = document.getElementById('rejectConfirmModal');
            var modal = bootstrap.Modal.getInstance(modalEl);
            if(modal) modal.hide();

            // Open the approval form with reject action
            if(pendingRejectTarget) {
                openApprovalForm(pendingRejectTarget, 'reject', pendingRejectApprovalId);
                // Pre-fill the reason into the form's comments
                var form = document.querySelector(pendingRejectTarget + ' form');
                if(form) {
                    var ta = form.querySelector('textarea[name="comments"]');
                    if(ta) ta.value = reason;
                }
            }
            pendingRejectTarget = null;
            pendingRejectApprovalId = null;
        });
    }

    // ---- Rejection reason live validation ----
    var rejectReasonField = document.getElementById('rejectConfirmReason');
    if(rejectReasonField) {
        rejectReasonField.addEventListener('input', function(){
            if(this.value.trim()) this.classList.remove('is-invalid');
        });
    }

    // ---- Form submit: loading state & validation ----
    document.addEventListener('submit', function(e){
        var form = e.target.closest('.approval-form');
        if(!form) return;
        var actionInput = form.querySelector('.approval-action-input');
        var action = actionInput ? actionInput.value : '';
        var textarea = form.querySelector('textarea[name="comments"]');

        // Require comments for rejection
        if(action === 'reject' && textarea && !textarea.value.trim()) {
            e.preventDefault();
            textarea.classList.add('is-invalid');
            textarea.focus();
            return;
        }
        if(textarea) textarea.classList.remove('is-invalid');

        // Loading state
        var submitBtn = form.querySelector('.approval-submit-btn');
        if(submitBtn) {
            submitBtn.disabled = true;
            var labelEl = submitBtn.querySelector('.submit-label');
            var loadingEl = submitBtn.querySelector('.submit-loading');
            if(labelEl) labelEl.classList.add('d-none');
            if(loadingEl) loadingEl.classList.remove('d-none');
        }

        // Show feedback area
        var approvalId = form.getAttribute('data-approval-id');
        var feedbackEl = document.getElementById('approvalFeedback' + approvalId);
        if(feedbackEl) {
            feedbackEl.style.display = 'block';
            feedbackEl.className = 'approval-feedback mb-2 alert alert-info py-1 px-2 small';
            feedbackEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Processing your response&hellip;';
        }
    });
})();

// Signature pad basic implementation
(function(){
    var canvas = document.getElementById('signaturePad');
    if (canvas) {
        var ctx = canvas.getContext('2d');
        var drawing = false;
        var rect = canvas.getBoundingClientRect();

        function resizeCanvas() {
            var w = canvas.clientWidth;
            var h = 200;
            canvas.width = w * window.devicePixelRatio;
            canvas.height = h * window.devicePixelRatio;
            canvas.style.width = w + 'px';
            canvas.style.height = h + 'px';
            ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#000';
        }
        resizeCanvas();

        function getPos(e) {
            var rect = canvas.getBoundingClientRect();
            var x, y;
            if (e.touches) {
                x = e.touches[0].clientX - rect.left;
                y = e.touches[0].clientY - rect.top;
            } else {
                x = e.clientX - rect.left;
                y = e.clientY - rect.top;
            }
            return {x: x, y: y};
        }

        canvas.addEventListener('mousedown', function(e){ drawing = true; var p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); });
        canvas.addEventListener('mousemove', function(e){ if(!drawing) return; var p = getPos(e); ctx.lineTo(p.x,p.y); ctx.stroke(); });
        document.addEventListener('mouseup', function(e){ drawing = false; });

        canvas.addEventListener('touchstart', function(e){ drawing = true; var p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); });
        canvas.addEventListener('touchmove', function(e){ if(!drawing) return; var p = getPos(e); ctx.lineTo(p.x,p.y); ctx.stroke(); e.preventDefault(); });
        document.addEventListener('touchend', function(e){ drawing = false; });

        document.getElementById('clearSignature') && document.getElementById('clearSignature').addEventListener('click', function(){ ctx.clearRect(0,0,canvas.width,canvas.height); });

        // On modal show clear canvas
        var drawModal = document.getElementById('drawSignatureModal');
        if (drawModal) {
            drawModal.addEventListener('shown.bs.modal', function(){ ctx.clearRect(0,0,canvas.width,canvas.height); resizeCanvas(); });
        }

        // On save set hidden input
        var saveBtn = document.getElementById('saveSignatureBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', function(e){
                e.preventDefault();
                var dataUrl = canvas.toDataURL('image/png');
                var input = document.getElementById('signedFileBase64');
                input.value = dataUrl;
                // submit the parent form
                var form = input.closest('form');
                if (form) form.submit();
            });
        }
    }
})();

// eSign send handler
document.addEventListener('click', function(e){
    var btn = e.target.closest('#sendEsignBtn');
    if(!btn) return;
    var contractId = btn.getAttribute('data-contract-id');
    if(!contractId) return;
    var statusArea = document.getElementById('esignResult');
    btn.disabled = true;
    var originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';

    fetch(APP_URL + '/esign/send/' + contractId, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '',
            'Accept': 'application/json'
        }
    }).then(function(res){ return res.json().then(function(json){ return {ok: res.ok, status: res.status, json: json}; }); })
    .then(function(r){
        if(r.ok){
            var sent = r.json.sent || [];
            var html = '<div class="alert alert-success">eSign sent to ' + sent.length + ' recipient(s).</div>';
            if(sent.length > 0){
                html += '<ul class="small">';
                sent.forEach(function(s){ html += '<li><strong>' + s.email + '</strong>: <a href="' + s.link + '" target="_blank">Open link</a></li>'; });
                html += '</ul>';
            }
            statusArea.style.display = 'block';
            statusArea.innerHTML = html;
        }else{
            var err = r.json || {};
            statusArea.style.display = 'block';
            statusArea.innerHTML = '<div class="alert alert-danger">Failed: '+(err.error || err.message || 'Unknown error')+'</div>';
        }
    }).catch(function(err){
        statusArea.style.display = 'block';
        statusArea.innerHTML = '<div class="alert alert-danger">Request failed: '+err.message+'</div>';
    }).finally(function(){ btn.disabled = false; btn.innerHTML = originalText; });
});

// ---- Select2-style Multi-Select Implementation ----
(function(){
    var select = document.getElementById('preapproverSelect');
    var container = document.getElementById('preapproverContainer');
    var searchInput = document.getElementById('searchInput');
    var selectedItemsContainer = document.getElementById('selectedItems');
    var selection = container?.querySelector('.select2-selection');
    
    if (!select || !container) return;

    var allOptions = Array.from(select.options).map(function(opt){
        return { value: opt.value, label: opt.textContent };
    });

    var isOpen = false;
    var filteredOptions = [...allOptions];

    // Render selected items as chips
    function renderSelectedItems() {
        var selected = Array.from(select.selectedOptions);
        selectedItemsContainer.innerHTML = '';
        
        if (selected.length === 0 && !isOpen) {
            searchInput.placeholder = 'Search or add pre-approvers...';
        }
        
        selected.forEach(function(opt){
            var chip = document.createElement('li');
            chip.className = 'select2-selection-item';
            chip.innerHTML = '<span class="select2-selection-item-label">' + opt.textContent.trim() + '</span><span class="select2-selection-item-remove" data-value="' + opt.value + '" role="button" tabindex="0" aria-label="Remove ' + opt.textContent.trim() + '">×</span>';
            chip.addEventListener('click', function(e){
                if (e.target.classList.contains('select2-selection-item-remove')) {
                    removeOption(opt.value);
                    searchInput.focus();
                }
            });
            chip.addEventListener('keydown', function(e){
                if (e.target.classList.contains('select2-selection-item-remove') && (e.key === 'Enter' || e.key === ' ')) {
                    e.preventDefault();
                    removeOption(opt.value);
                    searchInput.focus();
                }
            });
            selectedItemsContainer.appendChild(chip);
        });
    }

    // Remove option from selection
    function removeOption(value) {
        select.querySelector('option[value="' + value + '"]').selected = false;
        renderSelectedItems();
        updateSubmitButton();
    }

    // Add option to selection
    function addOption(value) {
        var option = select.querySelector('option[value="' + value + '"]');
        if (option) {
            option.selected = true;
            renderSelectedItems();
            updateSubmitButton();
        }
    }

    // Update submit button state
    function updateSubmitButton() {
        var submitBtn = document.getElementById('submitPreapprover');
        if (submitBtn) {
            submitBtn.disabled = select.selectedOptions.length === 0;
        }
    }

    // Filter dropdown options based on search input
    function filterOptions() {
        var query = searchInput.value.toLowerCase();
        filteredOptions = allOptions.filter(function(opt){
            return opt.label.toLowerCase().includes(query);
        });
        renderDropdown();
    }

    // Render dropdown options
    function renderDropdown() {
        var existingDropdown = container.querySelector('.select2-dropdown');
        if (existingDropdown) existingDropdown.remove();

        if (!isOpen || filteredOptions.length === 0) return;

        var dropdown = document.createElement('div');
        dropdown.className = 'select2-dropdown select2-dropdown-open';
        
        if (filteredOptions.length === 0) {
            dropdown.innerHTML = '<div class="select2-no-results">No matching results</div>';
        } else {
            filteredOptions.forEach(function(opt){
                var isSelected = Array.from(select.selectedOptions).some(function(s){ return s.value === opt.value; });
                var item = document.createElement('div');
                item.className = 'select2-dropdown-item' + (isSelected ? ' selected' : '');
                item.textContent = opt.label;
                item.setAttribute('data-value', opt.value);
                item.addEventListener('click', function(){
                    if (isSelected) {
                        removeOption(opt.value);
                    } else {
                        addOption(opt.value);
                    }
                    searchInput.focus();
                });
                dropdown.appendChild(item);
            });
        }
        
        container.appendChild(dropdown);
    }

    // Toggle dropdown visibility
    function toggleDropdown() {
        isOpen = !isOpen;
        if (isOpen) {
            container.classList.add('open');
            selection.classList.add('open');
            filterOptions();
            searchInput.focus();
        } else {
            container.classList.remove('open');
            selection.classList.remove('open');
            var dropdown = container.querySelector('.select2-dropdown');
            if (dropdown) dropdown.remove();
        }
    }

    // Event listeners
    selection.addEventListener('click', toggleDropdown);
    searchInput.addEventListener('input', filterOptions);
    
    searchInput.addEventListener('keydown', function(e){
        if (e.key === 'Escape') {
            isOpen = false;
            container.classList.remove('open');
            selection.classList.remove('open');
            var dropdown = container.querySelector('.select2-dropdown');
            if (dropdown) dropdown.remove();
        } else if (e.key === 'ArrowDown' && isOpen) {
            e.preventDefault();
            var items = container.querySelectorAll('.select2-dropdown-item');
            if (items.length > 0) items[0].focus();
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e){
        if (!container.contains(e.target) && isOpen) {
            isOpen = false;
            container.classList.remove('open');
            selection.classList.remove('open');
            var dropdown = container.querySelector('.select2-dropdown');
            if (dropdown) dropdown.remove();
        }
    });

    // Initialize
    renderSelectedItems();
    updateSubmitButton();
})();
</script>

<style>
/* ---- Approval Timeline ---- */
.approval-timeline-header h2 {
    font-size: 1.35rem;
    font-weight: 600;
    color: #1a1a2e;
}

.timeline-flow-chart {
    position: relative;
    padding-left: 36px;
    margin-top: 0.5rem;
}

.timeline-flow-chart::before {
    content: '';
    position: absolute;
    left: 17px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #dee2e6 0%, #adb5bd 100%);
}

.timeline-flow-chart .timeline-item {
    position: relative;
    margin-bottom: 16px;
    border-left: none !important;
    transition: transform 0.15s ease;
}

.timeline-flow-chart .timeline-item:hover {
    transform: translateX(2px);
}

.timeline-flow-chart .timeline-marker {
    position: absolute;
    left: -26px;
    top: 6px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #dee2e6;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
    transition: all 0.2s ease;
    z-index: 1;
}

.timeline-flow-chart .timeline-marker.active {
    background: #198754;
    box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.25);
    animation: pulse-green 2s infinite;
}

.timeline-flow-chart .timeline-marker.completed {
    background: #198754;
    box-shadow: 0 0 0 2px #198754;
}

.timeline-flow-chart .timeline-marker.rejected {
    background: #dc3545;
    box-shadow: 0 0 0 2px #dc3545;
}

@keyframes pulse-green {
    0% { box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.25); }
    50% { box-shadow: 0 0 0 6px rgba(25, 135, 84, 0.1); }
    100% { box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.25); }
}

.timeline-flow-chart .timeline-content {
    background: #ffffff;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}

.timeline-flow-chart .timeline-content:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-color: #dee2e6;
}

/* Button refinements */
.timeline-flow-chart .btn {
    font-weight: 500;
    letter-spacing: 0.01em;
    transition: all 0.15s ease;
}

.timeline-flow-chart .btn-success {
    background-color: #198754;
    border-color: #198754;
}

.timeline-flow-chart .btn-success:hover:not(:disabled) {
    background-color: #157347;
    border-color: #146c43;
    box-shadow: 0 2px 6px rgba(25, 135, 84, 0.3);
}

.timeline-flow-chart .btn-outline-danger:hover:not(:disabled) {
    box-shadow: 0 2px 6px rgba(220, 53, 69, 0.25);
}

.timeline-flow-chart .btn:disabled,
.timeline-flow-chart .btn[aria-disabled="true"] {
    cursor: not-allowed;
    pointer-events: auto;
}

/* Approval action banner */
.approval-action-banner {
    font-size: 0.875rem;
}

/* Approval form card */
.approval-controls .card {
    border-radius: 8px;
}

.approval-controls fieldset {
    border: none;
    padding: 0;
    margin: 0;
}

.approval-controls legend {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.5rem;
    margin-bottom: 0.75rem;
}

/* Feedback area */
.approval-feedback {
    border-radius: 6px;
    font-size: 0.8125rem;
}

/* Focus outline for accessibility */
.timeline-flow-chart .btn:focus-visible,
.approval-controls .form-control:focus-visible,
.approval-controls .btn:focus-visible {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
    box-shadow: none;
}

/* Responsive tweaks */
@media (max-width: 576px) {
    .timeline-flow-chart {
        padding-left: 28px;
    }
    .timeline-flow-chart .timeline-marker {
        left: -22px;
        width: 12px;
        height: 12px;
    }
    .timeline-flow-chart .timeline-content {
        padding: 12px;
    }
}

/* Rejection confirm modal */
#rejectConfirmModal .modal-content {
    border-radius: 10px;
}

/* Skip-link and screen reader only */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    white-space: nowrap;
    border: 0;
}

/* ---- Select2-style Multi-Select ---- */
.select2-style-container {
    position: relative;
    width: 100%;
}

.select2-selection {
    position: relative;
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 0;
    min-height: 42px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.select2-selection:hover:not(:disabled) {
    border-color: #9ca3af;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.select2-selection.open {
    border-color: #3b82f6;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.15);
}

.select2-selection:focus-within {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.select2-selection__rendered {
    display: flex;
    flex-wrap: wrap;
    flex-grow: 1;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    min-height: 42px;
}

.select2-selection__rendered-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    list-style: none;
    margin: 0;
    padding: 0;
    align-items: center;
}

.select2-selection-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #e0e7ff;
    border: 1px solid #c7d2fe;
    color: #312e81;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
    white-space: nowrap;
}

.select2-selection-item-label {
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

.select2-selection-item-remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    cursor: pointer;
    font-size: 1.25rem;
    line-height: 1;
    color: #6366f1;
    transition: all 0.15s ease;
    border-radius: 2px;
    padding: 0;
}

.select2-selection-item-remove:hover {
    background: rgba(99, 102, 241, 0.1);
    color: #4f46e5;
}

.select2-selection-item-remove:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 1px;
}

.select2-search-input {
    flex-grow: 1;
    flex: 1 1 auto;
    min-width: 150px;
    border: none;
    outline: none;
    padding: 6px 4px;
    background: transparent;
    font-size: 0.9375rem;
    color: #1f2937;
}

.select2-search-input::placeholder {
    color: #9ca3af;
}

.select2-search-input:disabled {
    background: transparent;
    cursor: not-allowed;
    color: #d1d5db;
}

.select2-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 42px;
    flex-shrink: 0;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s ease;
}

.select2-arrow b {
    display: inline-block;
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 5px solid currentColor;
    transition: transform 0.2s ease;
}

.open .select2-arrow b {
    transform: rotate(180deg);
}

.select2-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-top: none;
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    max-height: 240px;
    overflow-y: auto;
    z-index: 1001;
    margin-top: -1px;
}

.select2-dropdown-open {
    animation: slideDown 0.15s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.select2-dropdown-item {
    padding: 10px 12px;
    cursor: pointer;
    user-select: none;
    transition: all 0.1s ease;
    border-left: 3px solid transparent;
    font-size: 0.9375rem;
    color: #1f2937;
}

.select2-dropdown-item:hover {
    background: #f3f4f6;
    border-left-color: #3b82f6;
}

.select2-dropdown-item.selected {
    background: #eff6ff;
    color: #1e40af;
    border-left-color: #3b82f6;
    font-weight: 500;
}

.select2-dropdown-item.selected::before {
    content: '✓ ';
    color: #3b82f6;
    font-weight: bold;
}

.select2-dropdown-item:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: -2px;
}

.select2-no-results {
    padding: 12px;
    text-align: center;
    color: #9ca3af;
    font-size: 0.9375rem;
}

/* ---- Responsive adjustments ---- */
@media (max-width: 768px) {
    .select2-selection {
        min-height: 38px;
    }

    .select2-selection__rendered {
        min-height: 38px;
        padding: 4px 10px;
        gap: 4px;
    }

    .select2-selection-item {
        padding: 3px 6px;
        font-size: 0.8125rem;
    }

    .select2-selection-item-label {
        max-width: 150px;
    }

    .select2-arrow {
        width: 28px;
        height: 38px;
    }

    .select2-dropdown {
        max-height: 200px;
    }
}

/* Accessibility improvements */
.select2-selection__rendered:focus-within .select2-search-input {
    color: #000;
}

.select2-style-container .alert {
    margin-bottom: 0;
    font-size: 0.875rem;
}
</style>