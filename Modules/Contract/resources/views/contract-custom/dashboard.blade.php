@extends('layouts/layoutMaster')
@section('title', 'Contract Dashboard')

@section('vendor-style')
<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
<style>
    .clm-dashboard {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }
    .clm-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: #fff;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    }
    .clm-header h1 {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0;
    }
    .clm-header p {
        opacity: 0.9;
        margin: 0.5rem 0 0;
        font-size: 0.95rem;
    }
    .stat-card {
        background: #fff;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    .stat-card-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
    }
    .stat-card-primary .stat-icon {
        background: rgba(255,255,255,0.2);
        color: #fff;
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    .stat-icon-blue { background: rgba(102, 126, 234, 0.15); color: #667eea; }
    .stat-icon-green { background: rgba(40, 167, 69, 0.15); color: #28a745; }
    .stat-icon-orange { background: rgba(255, 193, 7, 0.15); color: #e6a700; }
    .stat-icon-red { background: rgba(220, 53, 69, 0.15); color: #dc3545; }
    .stat-icon-purple { background: rgba(118, 75, 162, 0.15); color: #764ba2; }
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 0.25rem;
    }
    .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    .stat-card-primary .stat-label {
        color: rgba(255,255,255,0.85);
    }
    .status-list-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .status-list-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .status-list-header i {
        font-size: 1.25rem;
        color: #667eea;
    }
    .status-list-header h5 {
        margin: 0;
        font-weight: 600;
        font-size: 1rem;
        color: #333;
    }
    .status-list-body {
        padding: 0;
    }
    .status-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s ease;
    }
    .status-item:last-child {
        border-bottom: none;
    }
    .status-item:hover {
        background: #f8f9fa;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
        font-size: 0.9rem;
    }
    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }
    .status-dot-draft { background: #6c757d; }
    .status-dot-review { background: #ffc107; }
    .status-dot-signing { background: #17a2b8; }
    .status-dot-executed { background: #28a745; }
    .status-dot-expired { background: #dc3545; }
    .status-dot-default { background: #adb5bd; }
    .status-count {
        background: #f0f0f0;
        color: #333;
        font-weight: 700;
        padding: 0.35rem 0.85rem;
        border-radius: 20px;
        font-size: 0.875rem;
    }
    .pending-card {
        border-left: 4px solid #667eea;
    }
    .quick-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .quick-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.875rem;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .quick-action-btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border: none;
    }
    .quick-action-btn-primary:hover {
        color: #fff;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        transform: translateY(-2px);
    }
    .quick-action-btn-outline {
        background: #fff;
        color: #667eea;
        border: 2px solid #667eea;
    }
    .quick-action-btn-outline:hover {
        background: #667eea;
        color: #fff;
    }
    .empty-state {
        text-align: center;
        padding: 2rem 1rem;
        color: #999;
    }
    .empty-state i {
        font-size: 2.5rem;
        margin-bottom: 0.75rem;
        opacity: 0.5;
    }
    .empty-state p {
        margin: 0;
        font-size: 0.9rem;
    }
</style>
@endsection

@section('content')
<div class="clm-dashboard">
    <div class="container-fluid px-4">
        <!-- Header -->
        <div class="clm-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1><i class="ti ti-file-text me-2"></i>Contract Lifecycle Dashboard</h1>
                <p>Manage and monitor your contracts efficiently</p>
            </div>
            <div class="quick-actions">
                <a href="{{ url('/contracts/list/contract-custom') }}" class="quick-action-btn quick-action-btn-outline" style="background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.5); color: #fff;">
                    <i class="ti ti-list"></i> View All Contracts
                </a>
                <a href="{{ url('/contracts/create/contract-custom') }}" class="quick-action-btn" style="background: rgba(255,255,255,0.25); color: #fff; border: none;">
                    <i class="ti ti-plus"></i> Create Contract
                </a>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row g-4 mb-4">
            <!-- Total Contracts -->
            <div class="col-12 col-sm-6 col-lg-3">
                <a href="{{ url('/contracts/list/contract-custom') }}" class="text-decoration-none">
                    <div class="stat-card stat-card-primary">
                        <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                            <i class="ti ti-files"></i>
                        </div>
                        <div class="stat-number">{{ number_format($totalContracts ?? 0) }}</div>
                        <div class="stat-label">Total Contracts</div>
                    </div>
                </a>
            </div>

            @php
                $draftCount = $statusCounts['Draft'] ?? 0;
                $reviewCount = $statusCounts['Review'] ?? 0;
                $signingCount = $statusCounts['Signing'] ?? 0;
                $executedCount = $statusCounts['Executed'] ?? ($statusCounts['executed'] ?? 0);
            @endphp

            <!-- Draft -->
            <div class="col-12 col-sm-6 col-lg-3">
                <a href="{{ url('/contracts/list/contract-custom?status=Draft') }}" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-blue">
                            <i class="ti ti-file-pencil"></i>
                        </div>
                        <div class="stat-number text-primary">{{ number_format($draftCount) }}</div>
                        <div class="stat-label">Draft</div>
                    </div>
                </a>
            </div>

            <!-- In Review -->
            <div class="col-12 col-sm-6 col-lg-3">
                <a href="{{ url('/contracts/list/contract-custom?status=Review') }}" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-orange">
                            <i class="ti ti-eye-check"></i>
                        </div>
                        <div class="stat-number" style="color: #e6a700;">{{ number_format($reviewCount) }}</div>
                        <div class="stat-label">In Review</div>
                    </div>
                </a>
            </div>

            <!-- Signing -->
            <div class="col-12 col-sm-6 col-lg-3">
                <a href="{{ url('/contracts/list/contract-custom?status=Signing') }}" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-purple">
                            <i class="ti ti-writing-sign"></i>
                        </div>
                        <div class="stat-number" style="color: #764ba2;">{{ number_format($signingCount) }}</div>
                        <div class="stat-label">Awaiting Signature</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Detailed Section -->
        <div class="row g-4">
            <!-- Status Breakdown -->
            <div class="col-12 col-lg-6">
                <div class="status-list-card h-100">
                    <div class="status-list-header">
                        <i class="ti ti-chart-pie"></i>
                        <h5>Contract Status Breakdown</h5>
                    </div>
                    <div class="status-list-body">
                        @if(!empty($statusCounts))
                            @foreach($statusCounts as $status => $count)
                                @php
                                    $statusLower = strtolower($status);
                                    $dotClass = 'status-dot-default';
                                    if ($statusLower === 'draft') $dotClass = 'status-dot-draft';
                                    elseif ($statusLower === 'review') $dotClass = 'status-dot-review';
                                    elseif ($statusLower === 'signing') $dotClass = 'status-dot-signing';
                                    elseif ($statusLower === 'executed' || $statusLower === 'active') $dotClass = 'status-dot-executed';
                                    elseif ($statusLower === 'expired' || $statusLower === 'terminated') $dotClass = 'status-dot-expired';
                                @endphp
                                <a href="{{ url('/contracts/list/contract-custom?status=' . $status) }}" class="status-item text-decoration-none">
                                    <span class="status-badge text-dark">
                                        <span class="status-dot {{ $dotClass }}"></span>
                                        {{ ucfirst($status) }}
                                    </span>
                                    <span class="status-count">{{ $count }}</span>
                                </a>
                            @endforeach
                        @else
                            <div class="empty-state">
                                <i class="ti ti-folder-off d-block"></i>
                                <p>No contracts found</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- My Pending Approvals -->
            <div class="col-12 col-lg-6">
                <div class="status-list-card pending-card h-100">
                    <div class="status-list-header">
                        <i class="ti ti-clock-hour-4" style="color: #dc3545;"></i>
                        <h5>Your Pending Approvals</h5>
                    </div>
                    <div class="status-list-body">
                        @if(!empty($myPendingCounts))
                            @foreach($myPendingCounts as $status => $count)
                                @php
                                    $statusLower = strtolower($status);
                                    $dotClass = 'status-dot-default';
                                    if ($statusLower === 'draft') $dotClass = 'status-dot-draft';
                                    elseif ($statusLower === 'review') $dotClass = 'status-dot-review';
                                    elseif ($statusLower === 'signing') $dotClass = 'status-dot-signing';
                                    elseif ($statusLower === 'executed' || $statusLower === 'active') $dotClass = 'status-dot-executed';
                                    elseif ($statusLower === 'expired' || $statusLower === 'terminated') $dotClass = 'status-dot-expired';
                                @endphp
                                <a href="{{ url('/contracts/list/contract-custom?status=' . $status) }}" class="status-item text-decoration-none" style="cursor: pointer;">
                                    <span class="status-badge text-dark">
                                        <span class="status-dot {{ $dotClass }}"></span>
                                        {{ ucfirst($status) }}
                                    </span>
                                    <span class="status-count" style="background: #667eea; color: #fff;">{{ $count }}</span>
                                </a>
                            @endforeach
                        @else
                            <div class="empty-state">
                                <i class="ti ti-checkbox d-block" style="color: #28a745;"></i>
                                <p>No pending approvals assigned to you</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="row mt-4">
            <div class="col-12">
                <p class="text-muted small text-center mb-0">
                    <i class="ti ti-info-circle me-1"></i>
                    Pending approvals are based on your account's registered email matching the approval workflow assignments.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection