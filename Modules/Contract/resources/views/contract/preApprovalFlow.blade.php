@php
    $userInfo = \App\Helpers\Helpers::userInfo();
    $preapprovalStage = $contract->preapproval_stage ?? 'review';
    $stages = ['review', 'negotiation', 'finalization'];
    $stageLabels = [
        'review' => 'Review',
        'negotiation' => 'Negotiation',
        'finalization' => 'Finalization',
    ];
    $currentStageIndex = array_search($preapprovalStage, $stages);
@endphp

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h4 class="mb-3">Review Flow</h4>
                    <div class="progress-steps d-flex justify-content-between align-items-center mb-4">
                        @foreach($stages as $index => $stage)
                            <div class="step-item d-flex flex-column align-items-center flex-grow-1 position-relative">
                                <div class="step-circle {{ $index < $currentStageIndex ? 'completed' : ($index === $currentStageIndex ? 'active' : 'pending') }}">
                                    @if($index < $currentStageIndex)
                                        <i class="bi bi-check-circle-fill"></i>
                                    @else
                                        <span>{{ $index + 1 }}</span>
                                    @endif
                                </div>
                                <div class="step-label {{ $index === $currentStageIndex ? 'active' : ($index < $currentStageIndex ? 'completed' : '') }}">
                                    {{ $stageLabels[$stage] }}
                                </div>
                                @if($index < count($stages) - 1)
                                    <div class="step-connector {{ $index < $currentStageIndex ? 'completed' : '' }}"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="text-center">
                        <span class="badge bg-primary fs-6">Current Stage: {{ ucfirst($preapprovalStage) }}</span>
                    </div>
                </div>
            </div>

            @if($preapprovalStage === 'negotiation')
                @php
                    // Negotiation rows only exist once the negotiation email has been
                    // dispatched, so their presence marks the stage as "sent".
                    $negotiationRows = $preApprovalSteps
                        ->where('stage_name', 'negotiation')
                        ->filter(function ($r) { return (int)($r->superseded ?? 0) === 0; });
                    $negotiationApproval = $negotiationRows->where('flag', 1)->first();
                    $negotiationSent = $negotiationRows->count() > 0;
                    $negotiationEmails = $negotiationRows
                        ->pluck('approver_email')
                        ->filter()
                        ->unique()
                        ->values();
                @endphp
                <div class="card shadow-sm border-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Negotiation Stage</h5>
                        <p class="card-text">Send the contract to external parties for negotiation review.</p>
                        @if(!$negotiationSent)
                            <form method="POST" action="{{ route('contract.negotiationEmail', ['id' => $contract->id]) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-envelope me-2"></i>Send Negotiation Email
                                </button>
                            </form>
                        @else
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>Negotiation email has been sent to external parties.
                                @if($negotiationEmails->count() > 0)
                                    <div class="mt-2 mb-0 small">
                                        <strong>Sent to:</strong>
                                        <ul class="mb-0">
                                            @foreach($negotiationEmails as $negEmail)
                                                <li>{{ $negEmail }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <div class="mt-2 mb-0 small text-muted">
                                    <i class="bi bi-info-circle me-1"></i>The contract owner has been notified of this action.
                                </div>
                            </div>
                        @endif
                        {{-- Owner "Update Contract Document" upload — temporarily hidden, to be re-enabled in future. Change @if(false) to @if(true) to restore. --}}
                        @if(false)
                        <hr>
                        <div class="mt-3">
                            <label class="form-label fw-semibold mb-1">
                                <i class="bi bi-upload me-1"></i>Update Contract Document
                            </label>
                            <p class="small text-muted mb-2">As the owner, you can upload a revised contract document during negotiation. Uploading the same file type updates the existing document in place, so previous versions are retained in cloud storage for change tracking.</p>
                            <form method="POST"
                                  action="{{ route('contract.preApproval.updateAttachment', ['id' => $contract->id]) }}"
                                  enctype="multipart/form-data"
                                  class="d-flex flex-column flex-sm-row gap-2 align-items-start">
                                @csrf
                                <input type="file" name="attachment_file" class="form-control" style="max-width:360px;"
                                       accept=".pdf,.doc,.docx,.xlsx,.xls" required>
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bi bi-cloud-arrow-up me-1"></i>Upload
                                </button>
                            </form>
                            @error('attachment_file')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif
                        <hr>
                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-success approve-btn" data-action="approve" data-stage="negotiation" data-approval-id="{{ $negotiationApproval->id ?? '' }}">
                                <i class="bi bi-check-circle me-2"></i>Approve & Continue
                            </button>
                            <button type="button" class="btn btn-outline-danger reject-btn" data-action="reject" data-stage="negotiation" data-approval-id="{{ $negotiationApproval->id ?? '' }}">
                                <i class="bi bi-x-circle me-2"></i>Suggest Changes & Return to Review
                            </button>
                        </div>
                    </div>
                </div>
            @else
                @php
                    $currentStageSteps = $preApprovalSteps->where('stage_name', $preapprovalStage);
                    $groupedSteps = $currentStageSteps->groupBy('group_key');
                @endphp
                @foreach($groupedSteps as $groupKey => $steps)
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                {{ ucfirst($preapprovalStage) }} Group
                                @if($steps->first()->approval_type_row === 'parallel')
                                    <span class="badge bg-info ms-2">Parallel</span>
                                @else
                                    <span class="badge bg-secondary ms-2">Sequential</span>
                                @endif
                            </h5>
                            @foreach($steps as $step)
                                @php
                                    try {
                                        $st = strtolower(decryptString($step->approval_status, 'approval_status') ?? $step->approval_status);
                                    } catch (\Throwable $e) {
                                        $st = strtolower($step->approval_status ?? 'pending');
                                    }
                                    $userEmail = strtolower(optional($userInfo)->email ?? '');
                                    $userRole = session()->get('contractSessionUserRole');
                                    $isAdmin = ($userRole === 'Super Admin') || (optional($userInfo)->email ?? '') === 'admin@legalitysimplified.com';
                                    $isActive = ($step->flag == 1);
                                    $isCurrent = $isAdmin || ($isActive && $userEmail !== '' && strtolower($step->approver_email ?? '') === $userEmail);
                                    $canAct = ($isCurrent || $isAdmin) && $st === 'pending';
                                @endphp
                                <div class="approval-step-item mb-3 p-3 border rounded {{ $isActive ? 'border-primary' : 'border-light' }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <i class="bi {{ $st === 'approved' ? 'bi-check-circle-fill text-success' : ($st === 'rejected' ? 'bi-x-circle-fill text-danger' : 'bi-hourglass-split text-warning') }}"></i>
                                                {{ $step->approver_name }}
                                            </h6>
                                            <p class="mb-1 small text-muted">{{ $step->approver_email }}</p>
                                            <span class="badge bg-{{ $st === 'approved' ? 'success' : ($st === 'rejected' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($st) }}
                                            </span>
                                            @if($st !== 'pending')
                                                <p class="mb-0 small text-muted mt-2">
                                                    @php
                                                        $actorName = null;
                                                        if(!empty($step->updated_by)){
                                                            try {
                                                                $actorJson = json_decode($step->updated_by);
                                                                if ($actorJson) $actorName = $actorJson->name ?? null;
                                                            } catch (\Throwable $e) {}
                                                        }
                                                    @endphp
                                                    @if($st === 'approved')
                                                        Approved by {{ $actorName ?? $step->approver_name }} on {{ $step->updated_on ? \Carbon\Carbon::parse($step->updated_on)->format('d M Y H:i') : '-' }}
                                                    @elseif($st === 'rejected')
                                                        Rejected by {{ $actorName ?? $step->approver_name }} on {{ $step->updated_on ? \Carbon\Carbon::parse($step->updated_on)->format('d M Y H:i') : '-' }}
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                        @if($canAct)
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-success btn-sm approve-btn" 
                                                        data-action="approve" 
                                                        data-approval-id="{{ $step->id }}"
                                                        data-stage="{{ $preapprovalStage }}">
                                                    <i class="bi bi-check-circle me-1"></i>Approve
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm reject-btn" 
                                                        data-action="reject" 
                                                        data-approval-id="{{ $step->id }}"
                                                        data-stage="{{ $preapprovalStage }}">
                                                    <i class="bi bi-x-circle me-1"></i>Suggest Changes
                                                </button>
                                            </div>
                                        @elseif($st !== 'pending')
                                            <span class="badge bg-light text-muted border">Completed</span>
                                        @else
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-success btn-sm opacity-50" disabled>
                                                    <i class="bi bi-check-circle me-1"></i>Approve
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm opacity-50" disabled>
                                                    <i class="bi bi-x-circle me-1"></i>Suggest Changes
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            @php
                                $groupDynamicEnabled = (int)($steps->first()->dynamic_approver_enabled ?? 0) === 1;
                                $groupHasPending = $steps->contains(function ($s) {
                                    try {
                                        $s2 = strtolower(decryptString($s->approval_status, 'approval_status') ?? $s->approval_status);
                                    } catch (\Throwable $e) {
                                        $s2 = strtolower($s->approval_status ?? 'pending');
                                    }
                                    return !in_array($s2, ['approved', 'rejected'], true);
                                });
                            @endphp
                            @if(!empty($userCanGate) && $groupDynamicEnabled && $groupHasPending)
                                <hr>
                                <form method="POST" action="{{ route('contracts.approval.group.approver.add', ['id' => $contract->id, 'groupId' => $groupKey]) }}" class="row g-2 align-items-end">
                                    @csrf
                                    <div class="col-md-7">
                                        <label class="form-label mb-1"><i class="bi bi-person-plus me-1"></i>Add Dynamic Approver</label>
                                        <select name="approver_id" class="form-select form-select-sm dynamic-approver-select2" data-placeholder="Select Approver" required>
                                            <option value="">Select Approver</option>
                                            @foreach(($dynamicApproverOptions ?? collect()) as $opt)
                                                <option value="{{ $opt['id'] }}">{{ $opt['name'] }} ({{ $opt['email'] }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus-circle me-1"></i>Add Approver
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title mb-3">Approval History</h5>
                    @php
                        $completedSteps = $preApprovalSteps->filter(function($step) {
                            try {
                                $st = strtolower(decryptString($step->approval_status, 'approval_status'));
                                return $st === 'approved' || $st === 'rejected';
                            } catch (\Throwable $e) {
                                return false;
                            }
                        })->sortBy('updated_on');
                    @endphp
                    @if($completedSteps->isEmpty())
                        <p class="text-muted">No completed steps yet.</p>
                    @else
                        @foreach($completedSteps as $completedStep)
                            @php
                                try {
                                    $st = strtolower(decryptString($completedStep->approval_status, 'approval_status'));
                                } catch (\Throwable $e) {
                                    $st = 'unknown';
                                }
                            @endphp
                            <div class="history-item mb-2 pb-2 border-bottom">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>{{ $completedStep->approver_name }}</strong> - {{ ucfirst($completedStep->stage_name) }}
                                    </div>
                                    <span class="badge bg-{{ $st === 'approved' ? 'success' : 'danger' }}">{{ ucfirst($st) }}</span>
                                </div>
                                <small class="text-muted">{{ $completedStep->updated_on ? \Carbon\Carbon::parse($completedStep->updated_on)->format('d M Y H:i') : '-' }}</small>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="preApprovalActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="preApprovalActionForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="preApprovalActionModalTitle">Approval Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="preApprovalActionMessage"></p>
                    <div class="mb-3">
                        <label for="preApprovalComments" class="form-label">Comments</label>
                        <textarea class="form-control" id="preApprovalComments" name="comments" rows="3" maxlength="2000"></textarea>
                    </div>
                    <input type="hidden" name="action" id="preApprovalAction">
                    <input type="hidden" name="approval_id" id="preApprovalApprovalId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="preApprovalSubmitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .progress-steps {
        position: relative;
        padding: 20px 0;
    }
    .step-item {
        position: relative;
        flex: 1;
        text-align: center;
    }
    .step-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-size: 20px;
        background-color: #e9ecef;
        color: #6c757d;
        border: 3px solid #dee2e6;
    }
    .step-circle.active {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }
    .step-circle.completed {
        background-color: #198754;
        color: white;
        border-color: #198754;
    }
    .step-label {
        font-size: 14px;
        color: #6c757d;
    }
    .step-label.active {
        color: #0d6efd;
        font-weight: 600;
    }
    .step-label.completed {
        color: #198754;
    }
    .step-connector {
        position: absolute;
        top: 25px;
        left: 50%;
        width: 100%;
        height: 3px;
        background-color: #dee2e6;
        z-index: -1;
    }
    .step-connector.completed {
        background-color: #198754;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const approveButtons = document.querySelectorAll('.approve-btn');
        const rejectButtons = document.querySelectorAll('.reject-btn');
        const modal = new bootstrap.Modal(document.getElementById('preApprovalActionModal'));
        
        const contractId = '{{ $contract->id }}';

        function buildRespondUrl(approvalId) {
            return APP_URL + `/contracts/${contractId}/pre-approval/${approvalId}/respond`;
        }

        approveButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.dataset.action;
                const approvalId = this.dataset.approvalId;
                const stage = this.dataset.stage;

                if (!approvalId) {
                    alert('No active approval step found for this stage.');
                    return;
                }

                document.getElementById('preApprovalAction').value = action;
                document.getElementById('preApprovalApprovalId').value = approvalId;
                document.getElementById('preApprovalActionForm').action = buildRespondUrl(approvalId);
                document.getElementById('preApprovalActionMessage').textContent = `Are you sure you want to approve this ${stage} step?`;
                document.getElementById('preApprovalComments').required = false;
                modal.show();
            });
        });

        rejectButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.dataset.action;
                const approvalId = this.dataset.approvalId;
                const stage = this.dataset.stage;

                if (!approvalId) {
                    alert('No active approval step found for this stage.');
                    return;
                }

                document.getElementById('preApprovalAction').value = action;
                document.getElementById('preApprovalApprovalId').value = approvalId;
                document.getElementById('preApprovalActionForm').action = buildRespondUrl(approvalId);
                document.getElementById('preApprovalActionMessage').textContent = `Are you sure you want to reject this ${stage} step? This may return the contract to a previous stage.`;
                document.getElementById('preApprovalComments').required = true;
                modal.show();
            });
        });
    });
</script>
