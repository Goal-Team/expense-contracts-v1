{{-- Approval Flow Chart using Bootstrap --}}

@php
    // Parse rules safely and normalize into groups
    $rawRules = null;
    try {
        $rawRules = is_string($contract->rules_id) ? json_decode($contract->rules_id, true) : $contract->rules_id;
    } catch (\Throwable $e) {
        $rawRules = null;
    }
    
    $rules_id = is_array($rawRules) ? $rawRules : [];
    $groups = [];
    
    if (!empty($rules_id) && is_array($rules_id)) {
        foreach ($rules_id as $r) {
            // Extract approver groups from rules
            if (isset($r['approver'])) {
                $approverData = $r['approver'];
                // Decode if it's a JSON string
                if (is_string($approverData)) {
                    $approverData = json_decode($approverData, true);
                }
                
                if (is_array($approverData)) {
                    foreach ($approverData as $g) {
                        // Decode approvers if it's a JSON string
                        $approversData = $g['approvers'] ?? [];
                        if (is_string($approversData)) {
                            $approversData = json_decode($approversData, true);
                        }
                        
                        if (isset($approversData) && is_array($approversData)) {
                            $groups[] = [
                                'approval_type' => $g['approval_type'] ?? 'sequential',
                                'approvers' => $approversData
                            ];
                        }
                    }
                }
            } elseif (isset($r['approvers'])) {
                $approversData = $r['approvers'];
                if (is_string($approversData)) {
                    $approversData = json_decode($approversData, true);
                }
                
                if (is_array($approversData)) {
                    $groups[] = [
                        'approval_type' => $r['approval_type'] ?? 'sequential',
                        'approvers' => $approversData
                    ];
                }
            }
        }
    }
    
    // Append signatory group from contract's `signatory` column (if present)
    if (!empty($contract->signatory)) {
        try {
            $signUser = \App\Models\AddUsers::select('id', decrypt_data('Email', 'AddUsers') . " as Email", decrypt_data('FirstName', 'AddUsers') . " as FirstName")->where('id', $contract->signatory)->first();
            if ($signUser) {
                $groups[] = [
                    'approval_type' => 'sequential',
                    'approvers' => [['email' => strtolower(trim($signUser->Email)), 'name' => ($signUser->FirstName ?? '')]],
                    'is_signatory' => true
                ];
            }
        } catch (\Throwable $e) {
            // ignore any lookup/decrypt errors - signatory display is optional
        }
    }

    // Build lookup maps from existing approval rows
    $mapByEmail = [];
    if (!empty($approvalsArr) && is_array($approvalsArr)) {
        foreach ($approvalsArr as $grp) {
            foreach ($grp as $row) {
                $u = null;
                if (is_string($row->username)) {
                    $tmp = json_decode($row->username, true);
                    $u = is_array($tmp) ? $tmp : null;
                } elseif (is_object($row->username)) {
                    $u = (array) $row->username;
                }
                $email = strtolower(trim($u['email'] ?? $u['Email'] ?? ''));
                try {
                    $status = function_exists('decryptString') ? decryptString($row->approval_status, 'approval_status') : $row->approval_status;
                } catch (\Throwable $e) {
                    $status = $row->approval_status;
                }
                $status = strtolower(strval($status));
                if ($email) $mapByEmail[$email] = ['row' => $row, 'status' => $status];
            }
        }
    }
@endphp

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">
                        <i class="bi bi-diagram-3"></i> Approval Flow Chart
                    </h5>
                </div>
                <div class="card-body">
                    @if(empty($groups))
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle"></i> No approval flow defined for this contract.
                        </div>
                    @else
                        {{-- Timeline/Flow Display --}}
                        <div class="approval-flow-timeline">
                            @foreach($groups as $groupIndex => $group)
                                @php
                                    $gType = strtolower($group['approval_type'] ?? 'sequential');
                                    $approvers = $group['approvers'] ?? [];
                                @endphp

                                <div class="row mb-4 align-items-stretch">
                                    {{-- Left timeline marker --}}
                                    <div class="col-auto text-center">
                                        <div class="timeline-marker">
                                            <div class="timeline-dot bg-primary text-white fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                {{ $groupIndex + 1 }}
                                            </div>
                                            @if($groupIndex < count($groups) - 1)
                                                <div style="width: 2px; height: 40px; background: #dee2e6; margin: 0 auto;"></div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Group Card --}}
                                    <div class="col">
                                        <div class="card h-100 border-start border-primary border-4">
                                            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                                                <div>
                                                    @if(!empty($group['is_signatory']))
                                                        <h6 class="mb-0">Signatory</h6>
                                                        <small class="text-muted">Signatory (Sequential)</small>
                                                    @else
                                                        <h6 class="mb-0">Group {{ $groupIndex + 1 }}</h6>
                                                        <small class="text-muted">{{ ucfirst($gType) }} Approval</small>
                                                    @endif
                                                </div>
                                                <span class="badge bg-primary">@if(!empty($group['is_signatory'])) SIGNATORY @else {{ strtoupper($gType[0]) . substr($gType, 1) }} @endif</span>
                                            </div>
                                            <div class="card-body">
                                                @if($gType === 'parallel')
                                                    {{-- Parallel Layout: Grid --}}
                                                    <div class="row g-2">
                                                        @foreach($approvers as $ap)
                                                            @php
                                                                $email = strtolower(trim($ap['email'] ?? ''));
                                                                $map = $mapByEmail[$email] ?? null;
                                                                $rowStatus = $map['status'] ?? 'not-created';
                                                                $row = $map['row'] ?? null;
                                                            @endphp
                                                            <div class="col-md-6 col-lg-4">
                                                                <div class="border rounded p-2 h-100">
                                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                                        <div class="flex-grow-1">
                                                                            <div class="fw-semibold small">{{ $ap['name'] ?? $ap['email'] }}</div>
                                                                            <div class="text-muted small text-truncate">{{ $ap['email'] ?? '' }}</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="d-flex gap-1 flex-wrap">
                                                                        @if($row)
                                                                            @php $displayStatus = $rowStatus; @endphp
                                                                            @if($displayStatus === 'pending')
                                                                                <span class="badge bg-warning text-dark">Pending</span>
                                                                            @elseif($displayStatus === 'approved')
                                                                                <span class="badge bg-success text-dark">Approved</span>
                                                                            @elseif($displayStatus === 'rejected')
                                                                                <span class="badge bg-danger text-light">Rejected</span>
                                                                            @else
                                                                                <span class="badge bg-secondary text-light">{{ ucfirst($displayStatus) }}</span>
                                                                            @endif
                                                                            @if((int)$row->flag === 1 && $displayStatus === 'pending')
                                                                                <span class="badge bg-info text-dark">Active</span>
                                                                            @endif
                                                                        @else
                                                                            <span class="badge bg-secondary text-light">Not created</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    {{-- Sequential Layout: List --}}
                                                    <div class="list-group list-group-flush">
                                                        @foreach($approvers as $apIndex => $ap)
                                                            @php
                                                                $email = strtolower(trim($ap['email'] ?? ''));
                                                                $map = $mapByEmail[$email] ?? null;
                                                                $rowStatus = $map['status'] ?? 'not-created';
                                                                $row = $map['row'] ?? null;
                                                            @endphp
                                                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0 {{ $apIndex > 0 ? 'border-top' : '' }}">
                                                                <div>
                                                                    <div class="fw-semibold">
                                                                        <span class="badge bg-light text-dark me-2">{{ $apIndex + 1 }}</span>
                                                                        {{ $ap['name'] ?? $ap['email'] }}
                                                                    </div>
                                                                    <div class="text-muted small">{{ $ap['email'] ?? '' }}</div>
                                                                </div>
                                                                <div class="d-flex gap-1 flex-wrap">
                                                                    @if($row)
                                                                        @php $displayStatus = $rowStatus; @endphp
                                                                        @if($displayStatus === 'pending')
                                                                            <span class="badge bg-warning text-dark">Pending</span>
                                                                        @elseif($displayStatus === 'approved')
                                                                            <span class="badge bg-success text-dark">Approved</span>
                                                                        @elseif($displayStatus === 'rejected')
                                                                            <span class="badge bg-danger text-light">Rejected</span>
                                                                        @else
                                                                            <span class="badge bg-secondary text-light">{{ ucfirst($displayStatus) }}</span>
                                                                        @endif
                                                                        @if((int)$row->flag === 1 && $displayStatus === 'pending')
                                                                            <span class="badge bg-info text-dark">Active</span>
                                                                        @endif
                                                                    @else
                                                                        <span class="badge bg-secondary text-light">Not created</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Legend --}}
                        <div class="mt-4 pt-3 border-top">
                            <h6 class="mb-2">Status Legend</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success text-dark">Approved</span>
                                    <small class="text-muted">Completed approval</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    <small class="text-muted">Waiting for action</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-danger text-light">Rejected</span>
                                    <small class="text-muted">Approval declined</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-info text-dark">Active</span>
                                    <small class="text-muted">Currently assigned</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary text-light">Not created</span>
                                    <small class="text-muted">Awaiting activation</small>
                                </div>
                            </div>
                        </div>

                        {{-- Contract Status Info --}}
                        <div class="mt-4 pt-3 border-top">
                            <div class="alert alert-light mb-0">
                                <small class="text-muted">
                                    <strong>Contract Status:</strong> <span class="badge bg-primary">{{ ucfirst($contract->contract_status) }}</span>
                                </small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline-marker {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .card-header {
        background-color: #f8f9fa !important;
    }

    .approval-flow-timeline .card {
        transition: box-shadow 0.3s ease;
    }

    .approval-flow-timeline .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    .list-group-item {
        background-color: transparent;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
    }
</style>
