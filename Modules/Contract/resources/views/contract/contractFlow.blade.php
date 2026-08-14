{{-- Approval Flow Chart using Bootstrap --}}

@php
    $rawRules = null;
    try {
        $rawRules = is_string($contract->rules_id) ? json_decode($contract->rules_id, true) : $contract->rules_id;
    } catch (\Throwable $e) {
        $rawRules = null;
    }

    $rules_id = is_array($rawRules) ? $rawRules : [];
    $ruleGroups = [];

    if (!empty($rules_id) && is_array($rules_id)) {
        foreach ($rules_id as $r) {
            $approverPayload = [];
            if (isset($r['approver'])) {
                $approverPayload = $r['approver'];
                if (is_string($approverPayload)) {
                    $approverPayload = json_decode($approverPayload, true);
                }
            } elseif (isset($r['approvers'])) {
                $approverPayload = [[
                    'approval_type' => $r['approval_type'] ?? 'sequential',
                    'dynamic_approver_enabled' => $r['dynamic_approver_enabled'] ?? 0,
                    'approvers' => $r['approvers'] ?? [],
                ]];
            }

            if (!is_array($approverPayload)) {
                continue;
            }

            foreach ($approverPayload as $g) {
                $approversData = $g['approvers'] ?? [];
                if (is_string($approversData)) {
                    $approversData = json_decode($approversData, true);
                }

                if (!is_array($approversData)) {
                    $approversData = [];
                }

                $staticEmails = [];
                foreach ($approversData as $ap) {
                    $email = strtolower(trim((string)($ap['email'] ?? '')));
                    if ($email !== '') {
                        $staticEmails[$email] = true;
                    }
                }

                $ruleGroups[] = [
                    'approval_type' => strtolower((string)($g['approval_type'] ?? 'sequential')),
                    'dynamic_approver_enabled' => (int)($g['dynamic_approver_enabled'] ?? 0) === 1,
                    'approvers' => $approversData,
                    'static_emails' => $staticEmails,
                ];
            }
        }
    }

    // Use the chart-specific collection which includes the pre-approval stages
    // (review/negotiation/finalization) as well as the approval/signing rows.
    $chartRows = collect($chartApprovals ?? $approvals ?? []);

    $mapByEmail = [];
    foreach ($chartRows as $row) {
        $u = null;
        if (is_string($row->username)) {
            $tmp = json_decode($row->username, true);
            $u = is_array($tmp) ? $tmp : null;
        } elseif (is_object($row->username)) {
            $u = (array) $row->username;
        }

        $email = strtolower(trim((string)($u['email'] ?? $u['Email'] ?? '')));
        if ($email === '') {
            continue;
        }

        try {
            $status = function_exists('decryptString') ? decryptString($row->approval_status, 'approval_status') : $row->approval_status;
        } catch (\Throwable $e) {
            $status = $row->approval_status;
        }

        $mapByEmail[$email] = [
            'row' => $row,
            'status' => strtolower((string)$status),
        ];
    }

    $runtimeGroupsMap = [];
    $runtimeGroupOrder = [];
    $runtimeGroupIndex = [];

    $approvalRowsForChart = collect($chartApprovals ?? $approvals ?? [])->sortBy('orderval')->values();

    foreach ($approvalRowsForChart as $row) {
        $groupId = (string)($row->unique_id ?? '');
        if ($groupId === '') {
            $groupId = 'ungrouped';
        }

        if (!isset($runtimeGroupsMap[$groupId])) {
            $runtimeGroupsMap[$groupId] = [
                'group_id' => $groupId,
                'approval_type' => strtolower((string)($row->approval_type_row ?? $row->approval_type_main ?? 'sequential')),
                'dynamic_approver_enabled' => (int)($row->dynamic_approver_enabled ?? 0) === 1,
                'approvers' => [],
                'is_runtime' => true,
            ];
            $runtimeGroupOrder[] = $groupId;
            $runtimeGroupIndex[$groupId] = count($runtimeGroupOrder) - 1;
        }

        $usernamePayload = [];
        if (is_string($row->username)) {
            $decoded = json_decode($row->username, true);
            if (is_array($decoded)) {
                $usernamePayload = $decoded;
            }
        } elseif (is_object($row->username)) {
            $usernamePayload = (array)$row->username;
        } elseif (is_array($row->username)) {
            $usernamePayload = $row->username;
        }

        $email = strtolower(trim((string)($usernamePayload['email'] ?? $usernamePayload['Email'] ?? '')));
        $name = trim((string)($usernamePayload['name'] ?? $usernamePayload['FirstName'] ?? $email));

        try {
            $runtimeStatus = function_exists('decryptString') ? decryptString($row->approval_status, 'approval_status') : $row->approval_status;
        } catch (\Throwable $e) {
            $runtimeStatus = $row->approval_status;
        }
        $runtimeStatus = strtolower((string)$runtimeStatus);

        $isDynamic = false;
        $groupIdx = $runtimeGroupIndex[$groupId] ?? null;
        if ($groupIdx !== null && isset($ruleGroups[$groupIdx]['static_emails']) && $email !== '') {
            $isDynamic = !isset($ruleGroups[$groupIdx]['static_emails'][$email]);
        }

        $runtimeGroupsMap[$groupId]['approvers'][] = [
            'email' => $email,
            'name' => $name !== '' ? $name : $email,
            'runtime_status' => $runtimeStatus !== '' ? $runtimeStatus : 'pending',
            'runtime_flag' => (int)($row->flag ?? 0),
            'has_runtime_row' => true,
            'is_dynamic' => $isDynamic,
        ];
    }

    $groups = [];
    if (!empty($runtimeGroupOrder)) {
        foreach ($runtimeGroupOrder as $groupId) {
            $groups[] = $runtimeGroupsMap[$groupId];
        }
    } else {
        foreach ($ruleGroups as $groupIdx => $group) {
            $groups[] = [
                'group_id' => null,
                'approval_type' => $group['approval_type'] ?? 'sequential',
                'dynamic_approver_enabled' => (bool)($group['dynamic_approver_enabled'] ?? false),
                'approvers' => $group['approvers'] ?? [],
                'is_runtime' => false,
            ];
        }
    }

    if (!empty($contract->signatory)) {
        try {
            $signUser = \App\Models\AddUsers::withoutGlobalScopes()->select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))->where('id', $contract->signatory)->first();
            $signEmail = $signUser ? strtolower(trim((string)$signUser->Email)) : '';
            // Only show the default signatory group when the signatory does not already
            // have a runtime row (e.g. the actual signing step). Otherwise it would appear
            // twice — once as its runtime group and once as this default group.
            if ($signUser && !isset($mapByEmail[$signEmail])) {
                $groups[] = [
                    'group_id' => null,
                    'approval_type' => 'sequential',
                    'dynamic_approver_enabled' => false,
                    'approvers' => [[
                        'email' => strtolower(trim((string)$signUser->Email)),
                        'name' => (string)($signUser->FirstName ?? ''),
                        'has_runtime_row' => false,
                    ]],
                    'is_signatory' => true,
                    'is_runtime' => false,
                ];
            }
        } catch (\Throwable $e) {
            // ignore signatory lookup errors in chart view
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
                        <div class="approval-flow-timeline mt-4">
                            @foreach($groups as $groupIndex => $group)
                                @php
                                    $gType = strtolower((string)($group['approval_type'] ?? 'sequential'));
                                    $approvers = $group['approvers'] ?? [];
                                    $groupDynamicApproverEnabled = (bool)($group['dynamic_approver_enabled'] ?? false);
                                    $groupId = $group['group_id'] ?? null;
                                    $isRuntimeGroup = (bool)($group['is_runtime'] ?? false);
                                    $canAddDynamicApprover = !empty($userCanGate) && $userCanGate && $groupDynamicApproverEnabled && !empty($groupId) && empty($group['is_signatory']);
                                @endphp

                                <div class="row mb-4 align-items-stretch">
                                    <div class="col-auto text-center position-relative">
                                        <div class="timeline-marker-flow">
                                            <div class="timeline-dot bg-primary text-white fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                {{ $groupIndex + 1 }}
                                            </div>
                                            @if($groupIndex < count($groups) - 1)
                                                <div style="background: #dee2e6; margin: 0 auto;"></div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col">
                                        <div class="card h-100 border-start border-primary border-4">
                                            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                                                <div>
                                                    @if(!empty($group['is_signatory']))
                                                        <h6 class="mb-0">Signatory</h6>
                                                    @else
                                                        <h6 class="mb-0">Group {{ $groupIndex + 1 }}</h6>
                                                    @endif
                                                </div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                                    <span class="badge bg-primary">@if(!empty($group['is_signatory'])) SIGNATORY @else {{ strtoupper($gType[0]) . substr($gType, 1) }} @endif</span>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                @if($canAddDynamicApprover)
                                                    <form method="POST" action="{{ route('contracts.approval.group.approver.add', ['id' => $contract->id, 'groupId' => $groupId]) }}" class="row g-2 align-items-end mb-3">
                                                        @csrf
                                                        <div class="col-md-7 col-lg-6">
                                                            <label class="form-label mb-1">Add Dynamic Approver</label>
                                                            <select name="approver_id" class="form-select form-select-sm dynamic-approver-select2" data-placeholder="Select Approver" required>
                                                                <option value="">Select Approver</option>
                                                                @foreach(($dynamicApproverOptions ?? collect()) as $opt)
                                                                    <option value="{{ $opt['id'] }}">{{ $opt['name'] }} ({{ $opt['email'] }})</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-auto">
                                                            <button type="submit" class="btn btn-sm btn-primary">Add Approver</button>
                                                        </div>
                                                    </form>
                                                @endif

                                                @if($gType === 'parallel')
                                                    <div class="row g-2">
                                                        @foreach($approvers as $ap)
                                                            @php
                                                                $email = strtolower(trim((string)($ap['email'] ?? '')));
                                                                $runtimeStatus = strtolower((string)($ap['runtime_status'] ?? ''));
                                                                $runtimeFlag = (int)($ap['runtime_flag'] ?? 0);
                                                                $hasRuntimeRow = (bool)($ap['has_runtime_row'] ?? false);
                                                                $isDynamicApprover = (bool)($ap['is_dynamic'] ?? false);

                                                                if (!$hasRuntimeRow) {
                                                                    $lookup = $mapByEmail[$email] ?? null;
                                                                    if ($lookup) {
                                                                        $runtimeStatus = strtolower((string)($lookup['status'] ?? 'not-created'));
                                                                        $runtimeFlag = (int)(($lookup['row']->flag ?? 0));
                                                                        $hasRuntimeRow = true;
                                                                    }
                                                                }

                                                                if ($runtimeStatus === '') {
                                                                    $runtimeStatus = 'not-created';
                                                                }
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
                                                                        @if($hasRuntimeRow)
                                                                            @if($runtimeStatus === 'pending')
                                                                                <span class="badge bg-warning text-white">Pending</span>
                                                                            @elseif($runtimeStatus === 'approved')
                                                                                <span class="badge bg-success text-white">Approved</span>
                                                                            @elseif($runtimeStatus === 'rejected')
                                                                                <span class="badge bg-danger text-white">Rejected</span>
                                                                            @else
                                                                                <span class="badge bg-secondary text-white">{{ ucfirst($runtimeStatus) }}</span>
                                                                            @endif
                                                                            @if($runtimeFlag === 1 && $runtimeStatus === 'pending')
                                                                                <span class="badge bg-info text-white">Active</span>
                                                                            @endif
                                                                        @else
                                                                            @if(strtolower((string)($contract->contract_status ?? '')) === 'signing' && strtolower((string)($contract->substatus ?? '')) === 'approved')
                                                                                <span class="badge bg-warning text-white">Pending</span>
                                                                            @else
                                                                                <span class="badge bg-secondary text-white">Not created</span>
                                                                            @endif
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="list-group list-group-flush">
                                                        @foreach($approvers as $apIndex => $ap)
                                                            @php
                                                                $email = strtolower(trim((string)($ap['email'] ?? '')));
                                                                $runtimeStatus = strtolower((string)($ap['runtime_status'] ?? ''));
                                                                $runtimeFlag = (int)($ap['runtime_flag'] ?? 0);
                                                                $hasRuntimeRow = (bool)($ap['has_runtime_row'] ?? false);
                                                                $isDynamicApprover = (bool)($ap['is_dynamic'] ?? false);

                                                                if (!$hasRuntimeRow) {
                                                                    $lookup = $mapByEmail[$email] ?? null;
                                                                    if ($lookup) {
                                                                        $runtimeStatus = strtolower((string)($lookup['status'] ?? 'not-created'));
                                                                        $runtimeFlag = (int)(($lookup['row']->flag ?? 0));
                                                                        $hasRuntimeRow = true;
                                                                    }
                                                                }

                                                                if ($runtimeStatus === '') {
                                                                    $runtimeStatus = 'not-created';
                                                                }
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
                                                                    @if($hasRuntimeRow)
                                                                        @if($runtimeStatus === 'pending')
                                                                            <span class="badge bg-warning text-white">Pending</span>
                                                                        @elseif($runtimeStatus === 'approved')
                                                                            <span class="badge bg-success text-white">Approved</span>
                                                                        @elseif($runtimeStatus === 'rejected')
                                                                            <span class="badge bg-danger text-white">Rejected</span>
                                                                        @else
                                                                            <span class="badge bg-secondary text-white">{{ ucfirst($runtimeStatus) }}</span>
                                                                        @endif
                                                                        @if($runtimeFlag === 1 && $runtimeStatus === 'pending')
                                                                            <span class="badge bg-info text-white">Active</span>
                                                                        @endif
                                                                    @else
                                                                        @if(strtolower((string)($contract->contract_status ?? '')) === 'signing' && strtolower((string)($contract->substatus ?? '')) === 'approved')
                                                                            <span class="badge bg-warning text-white">Pending</span>
                                                                        @else
                                                                            <span class="badge bg-secondary text-white">Not created</span>
                                                                        @endif
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

                        <div class="mt-4 pt-3 border-top">
                            <h6 class="mb-2">Status Legend</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success text-white">Approved</span>
                                    <small class="text-muted">Completed approval</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-warning text-white">Pending</span>
                                    <small class="text-muted">Waiting for action</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-danger text-white">Rejected</span>
                                    <small class="text-muted">Approval declined</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-info text-white">Active</span>
                                    <small class="text-muted">Currently assigned</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary text-white">Not created</span>
                                    <small class="text-muted">Awaiting activation</small>
                                </div>
                            </div>
                        </div>

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
    .timeline-marker-flow {
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
