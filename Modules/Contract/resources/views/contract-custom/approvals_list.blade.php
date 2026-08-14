@php
    $userInfo = \App\Helpers\Helpers::userInfo();
@endphp

<style>
/* ========== Approval Workflow Timeline ========== */
.approvals-chart { 
    overflow-x: auto; 
    padding: 16px 0; 
    background: #f8fafc;
    border-radius: 8px;
}

/* Timeline container */
.timeline-approvals {
    display: flex;
    align-items: flex-start;
    padding: 20px 12px 12px;
    position: relative;
    gap: 8px;
}

/* Connector line between steps */
.timeline-approvals::before {
    content: '';
    position: absolute;
    top: 40px;
    left: 24px;
    right: 24px;
    height: 2px;
    background: #e2e8f0;
    z-index: 0;
}

/* Each timeline step */
.timeline-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
    min-width: 180px;
    max-width: 200px;
    flex: 0 0 auto;
}

/* Step number badge */
.step-indicator {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.95rem;
    margin-bottom: 12px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Step indicator states */
.step-indicator.completed {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
}
.step-indicator.active {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
    animation: pulse-ring 2s ease-out infinite;
}
.step-indicator.pending {
    background: #fff;
    color: #94a3b8;
    border: 2px solid #e2e8f0;
}
.step-indicator.rejected {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
}

@keyframes pulse-ring {
    0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
    70% { box-shadow: 0 0 0 8px rgba(59, 130, 246, 0); }
    100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
}

/* Step card */
.step-card {
    background: #fff;
    border-radius: 10px;
    padding: 14px;
    width: 100%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s ease;
}
.step-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.step-card.is-active {
    border-color: #3b82f6;
    background: linear-gradient(to bottom, #eff6ff, #fff);
}

/* Card header */
.step-card .card-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 6px;
    margin-bottom: 8px;
}
.step-card .step-info {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}
.step-card .step-type {
    font-weight: 600;
    font-size: 0.85rem;
    color: #1e293b;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.step-card .step-user {
    font-size: 0.75rem;
    color: #64748b;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-top: 2px;
}

/* Status badge */
.step-status {
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.2px;
    white-space: nowrap;
}
.step-status.status-approved { background: #dcfce7; color: #166534; }
.step-status.status-pending { background: #e0f2fe; color: #0369a1; }
.step-status.status-active { background: #dbeafe; color: #1d4ed8; }
.step-status.status-rejected { background: #fee2e2; color: #991b1b; }
.step-status.status-completed { background: #dcfce7; color: #166534; }

/* Last action info */
.step-card .last-action {
    font-size: 0.78rem;
    color: #475569;
    padding: 8px;
    background: #f8fafc;
    border-radius: 6px;
    margin-bottom: 10px;
    min-height: 42px;
}
.step-card .last-action strong {
    color: #334155;
    display: block;
    margin-bottom: 2px;
}
.step-card .last-action .action-text {
    max-height: 32px;
    overflow: auto;
    line-height: 1.4;
}

/* Action buttons */
.step-card .action-buttons {
    display: flex;
    gap: 6px;
}
.step-card .action-buttons .btn {
    flex: 1;
    padding: 6px 10px;
    font-size: 0.8rem;
    font-weight: 500;
    border-radius: 6px;
}
.step-card .action-buttons .btn-success {
    background: #10b981;
    border-color: #10b981;
}
.step-card .action-buttons .btn-success:hover {
    background: #059669;
    border-color: #059669;
}
.step-card .action-buttons .btn-danger {
    background: #ef4444;
    border-color: #ef4444;
}
.step-card .action-buttons .btn-danger:hover {
    background: #dc2626;
    border-color: #dc2626;
}

/* iframe sizing */
.approvals-iframe { width: 100%; height: 420px; border: 0; }

/* Responsive */
@media (max-width: 768px) {
    .timeline-step { min-width: 180px; max-width: 200px; }
    .step-indicator { width: 32px; height: 32px; font-size: 0.85rem; }
}
</style>

<div class="row">
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="fas fa-project-diagram me-2"></i>Approval Workflow</strong>
                <span class="badge bg-secondary">{{ $approvalEntries->count() }} Steps</span>
            </div>
            <div class="card-body p-2">
                <div class="approvals-chart">
                    <!-- Horizontal timeline -->
                    <div class="timeline-approvals" role="list">
                        @foreach($approvalEntries as $idx => $entry)
                            @php
                                $userFirstName = $entry->username_decrypted_name;
                                $username = $entry->username_decrypted_email ?? $entry->username;
                                $isActive = (int)$entry->flag === 1;
                                $isCurrent = false;
                                if ($userInfo) {
                                    $cur = strtolower($userInfo->email ?? $userInfo->FirstName ?? '');
                                    $ent = strtolower($username ?? '');
                                
                                    if (!empty($ent) && $isActive) {
                                        $isCurrent = (strpos($ent,'@') !== false) ? ($cur === $ent) : ($cur === strtolower($ent));
                                    }
                                }
                                
                                // Determine step state
                                $stepState = 'pending';
                                if ($entry->approval_status_decrypted === 'approved' || $entry->approval_status_decrypted === 'completed') {
                                    $stepState = 'completed';
                                } elseif ($entry->approval_status_decrypted === 'rejected') {
                                    $stepState = 'rejected';
                                } elseif ($isActive) {
                                    $stepState = 'active';
                                }
                                
                                $statusLabel = ucfirst($entry->approval_status_decrypted);
                                $approvalTextmap = [
                                    "Verifier"      => "Verification",
                                    "Approver"      => "Approval",
                                    "Preapprover"   => "Pre-Approval",
                                    "Owner"         => "Owner Review",
                                    "Signatory"     => "Signatory"
                                ];                                
                                $stepTitle = $approvalTextmap[$entry->approver_type_row] ?? $entry->approver_type_row;
                                $viewUrl = url('/contracts/approval/contract-custom/'.$contract->id.'/approval/'.$entry->id.'/view');
                            @endphp

                            <div class="timeline-step" role="listitem" data-view-url="{{ $viewUrl }}">
                                <!-- Step number indicator -->
                                <div class="step-indicator {{ $stepState }}" title="Step {{ $idx + 1 }}: {{ $statusLabel }}">
                                    @if($stepState === 'completed')
                                        <i class="fas fa-check"></i>
                                    @elseif($stepState === 'rejected')
                                        <i class="fas fa-times"></i>
                                    @else
                                        {{ $idx + 1 }}
                                    @endif
                                </div>

                                <!-- Step card -->
                                <div class="step-card {{ $isActive ? 'is-active' : '' }}" data-entry-id="{{ $entry->id }}">
                                    <div class="card-header-row">
                                        <div class="step-info">
                                            <div class="step-type">{{ $stepTitle }}</div>
                                            <div class="step-user" title="{{ $username }}">{!! $userFirstName."<br/>".$username !!}</div>
                                        </div>
                                        <span class="step-status status-{{ $entry->approval_status_decrypted }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </div>

                                    <div class="last-action">
                                        <strong>Last Action</strong>
                                        @if(!empty($entry->button_text))
                                            <div class="action-text" style="font-weight:600;">{{ $entry->button_text }}</div>
                                        @endif
                                        <div class="action-text">{{ $entry->next_action_description ?? 'Waiting for action' }}</div>
                                    </div>

                                    <div class="action-buttons">
                                        @if($isCurrent)
                                            <form method="POST" action="{{ url('/contracts/approval/contract-custom/'.$contract->id.'/approval/'.$entry->id.'/respond') }}" style="display:contents;">
                                                @csrf
                                                <input type="hidden" name="action" value="">
                                                <input type="hidden" name="comments" value="">
                                                <button type="button" class="btn btn-sm btn-success quick-approve-btn" {{ ($entry->approver_type_row === 'Owner' && empty($contract->contract_attachment)) ? "disabled" : '' }}>
                                                    <i class="fas fa-check me-1"></i>{{ $entry->approver_type_row === 'Owner' ? (!empty($contract->contract_attachment) ? 'Send' : 'Missing') : 'Approve' }}
                                                </button>
                                                @if($entry->approver_type_row !== 'Owner')
                                                <button type="button" class="btn btn-sm btn-danger quick-reject-btn">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                                @endif
                                            </form>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary w-100" disabled>
                                                @if($stepState === 'completed')
                                                    <i class="fas fa-check-circle me-1"></i>Done
                                                @elseif($stepState === 'rejected')
                                                    <i class="fas fa-times-circle me-1"></i>Rejected
                                                @else
                                                    <i class="fas fa-clock me-1"></i>Waiting
                                                @endif
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Approval Details Modal -->
                <div class="modal fade" id="approvalDetailsModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Approval Step Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body p-0">
                        <iframe id="approvalDetailsIframe" class="approvals-iframe" src="about:blank"></iframe>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Approve/Reject Modal -->
                <div class="modal fade" id="approvalActionModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="approvalActionTitle">Confirm Action</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3">
                          <label for="approvalComments" class="form-label">Comments</label>
                          <textarea id="approvalComments" class="form-control" rows="4" placeholder="Enter comments (required for rejection)"></textarea>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="approvalActionConfirm" class="btn btn-primary">Confirm</button>
                      </div>
                    </div>
                  </div>
                </div>

            </div>
        </div>

        @if(!empty($canViewForm) && !$isOwner)
        <div class="card mb-3">
            <div class="card-header"><strong>Uploaded Contract File</strong></div>
            <div class="card-body">
                @if(!empty($contract->contract_attachment))
                    @if(isset($contract->contract_attachment_filename))
                        @if(fileStorageType() != 'Local')
                            @php 
                                $getFinalUrl = get_google_drive_doc_link($contract->contract_attachment_filename,$contract->contract_attachment, 'edit', 'openfile');
                                $getFinalUrlNew = get_google_drive_doc_link($contract->contract_attachment_filename,$contract->contract_attachment, 'edit', 'openfile');
                            @endphp
                            <div class="alert alert-danger mx-2">If below document Not Loaded Please <a href="{{$getFinalUrlNew}}" target="blank">Click Here</a>. Because of some security reasons its not loaded.</div>
                            <iframe src="{{ $getFinalUrl }}" height="500" width="100%"></iframe>
                        @else
                            @include('contract::contract.viewContractDocument')
                        @endif   
                    @endif
                @else
                    <div class="text-muted">No contract file uploaded.</div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4 d-none">
        <div class="card mb-3">
            <div class="card-header"><strong>Approval Actions</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ url('/contracts/approval/contract-custom/'.$contract->id.'/approval/notify') }}">@csrf
                    <button type="submit" class="btn btn-primary w-100 mb-2"><i class="fas fa-bell"></i> Notify Owner (Edit Requested)</button>
                </form>

                <a href="{{ url('/contracts/'.$contract->id) }}" class="btn btn-outline-secondary w-100"><i class="fas fa-arrow-left"></i> Back to Contract</a>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Approvals Summary</strong></div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><strong>Total Steps:</strong> {{ $approvalEntries->count() }}</li>
                    <li><strong>Current Contract Status:</strong> {{ $contract->contract_status ?? '-' }}</li>
                    <li><strong>Substatus:</strong> {{ $contract->substatus ?? '-' }}</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    let currentForm = null;
    let currentAction = null; // 'approve' or 'reject'

    // Open details when clicking card
    document.querySelectorAll('.timeline-step .step-card').forEach(function(card){
        card.addEventListener('click', function(e){
            // prevent clicks on action buttons from opening details
            if (e.target.closest('.action-buttons')) return;
            const viewUrl = card.closest('.timeline-step')?.dataset?.viewUrl;
            if (!viewUrl) return;
            const iframe = document.getElementById('approvalDetailsIframe');
            iframe.src = viewUrl;
            const modal = new bootstrap.Modal(document.getElementById('approvalDetailsModal'));
            modal.show();
        });
    });

    document.addEventListener('click', function(e){
        const approveBtn = e.target.closest('.quick-approve-btn');
        const rejectBtn = e.target.closest('.quick-reject-btn');

        if (approveBtn || rejectBtn) {
            const btn = approveBtn || rejectBtn;
            currentForm = btn.closest('form');
            if (!currentForm) return;
            currentAction = approveBtn ? 'approve' : 'reject';

            // set modal title and button text
            const title = document.getElementById('approvalActionTitle');
            title.textContent = currentAction === 'approve' ? 'Confirm Approval' : 'Confirm Rejection';
            const confirmBtn = document.getElementById('approvalActionConfirm');
            confirmBtn.textContent = currentAction === 'approve' ? 'Approve' : 'Reject';
            confirmBtn.className = currentAction === 'approve' ? 'btn btn-success' : 'btn btn-danger';

            // prefill comments (if any hidden input has value)
            const commentsInputHidden = currentForm.querySelector('input[name="comments"]');
            document.getElementById('approvalComments').value = commentsInputHidden ? (commentsInputHidden.value || '') : '';

            // show modal
            const modal = new bootstrap.Modal(document.getElementById('approvalActionModal'));
            modal.show();
        }
    });

    // Handle confirm in modal
    document.getElementById('approvalActionConfirm').addEventListener('click', function(){
        const comments = document.getElementById('approvalComments').value.trim();
        if (currentAction === 'reject' && comments === '') {
            alert('Please provide comments for rejection.');
            return;
        }
        if (!currentForm) return;
        const commentsInput = currentForm.querySelector('input[name="comments"]');
        const actionInput = currentForm.querySelector('input[name="action"]');
        if (commentsInput) commentsInput.value = comments;
        if (actionInput) actionInput.value = currentAction;

        // submit the form
        currentForm.submit();
    });
});
</script>