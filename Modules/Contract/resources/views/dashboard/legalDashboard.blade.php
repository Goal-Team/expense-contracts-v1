@extends('layouts/layoutMaster')
@section('title', 'Legal Advisor Dashboard')

@section('vendor-style')
<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
<style>
    .legal-dashboard {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }
    .legal-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: #fff;
        box-shadow: 0 10px 40px rgba(40, 167, 69, 0.3);
    }
    .legal-header h1 {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0;
    }
    .legal-header p {
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
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
    .stat-icon-green { background: rgba(40, 167, 69, 0.15); color: #28a745; }
    .stat-icon-blue { background: rgba(102, 126, 234, 0.15); color: #667eea; }
    .stat-icon-orange { background: rgba(255, 193, 7, 0.15); color: #e6a700; }
    .stat-icon-red { background: rgba(220, 53, 69, 0.15); color: #dc3545; }
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
    .contracts-table-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .contracts-table-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .contracts-table-header i {
        font-size: 1.25rem;
        color: #28a745;
    }
    .contracts-table-header h5 {
        margin: 0;
        font-weight: 600;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .contracts-status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .badge-draft { background: #e7e7ff; color: #5e3fbf; }
    .badge-review { background: #fff3cd; color: #856404; }
    .badge-negotiation { background: #d1ecf1; color: #0c5460; }
    .badge-approval { background: #cfe2ff; color: #084298; }
    .badge-approved { background: #d1f0e4; color: #0f5132; }
    .badge-signing { background: #fce5ea; color: #842029; }
    .badge-executed { background: #d1f0e4; color: #0f5132; }
    .badge-active { background: #d1f0e4; color: #0f5132; }
    .badge-expired { background: #f8d7da; color: #842029; }
    .badge-pending { background: #fff3cd; color: #856404; }
    .badge-completed { background: #d1f0e4; color: #0f5132; }
    .action-btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.75rem;
        border-radius: 6px;
    }
    .filter-section {
        background: #fff;
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .filter-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #333;
        font-size: 0.9rem;
    }
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }
    .empty-state i {
        font-size: 3rem;
        opacity: 0.3;
        margin-bottom: 1rem;
    }
</style>
@endsection

@section('content')
<div class="legal-dashboard">
    <div class="container-xl">
        <!-- Header -->
        <div class="legal-header">
            <h1><i class="bx bx-briefcase"></i> Legal Advisor Dashboard</h1>
            <p>Manage and review contracts assigned to you for legal review</p>
        </div>

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card stat-card-primary">
                    <div class="stat-icon">
                        <i class="bx bx-list-check"></i>
                    </div>
                    <div class="stat-number">{{ $statusCounts['all'] }}</div>
                    <div class="stat-label">Total Contracts</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-blue">
                        <i class="bx bx-time"></i>
                    </div>
                    <div class="stat-number">{{ $statusCounts['approval'] }}</div>
                    <div class="stat-label">Pending Review</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-green">
                        <i class="bx bx-check-circle"></i>
                    </div>
                    <div class="stat-number">{{ $statusCounts['approved'] }}</div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-orange">
                        <i class="bx bx-play-circle"></i>
                    </div>
                    <div class="stat-number">{{ $statusCounts['executed'] }}</div>
                    <div class="stat-label">Executed</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row">
                <div class="col-md-6">
                    <div class="filter-label">Filter by Contract Type</div>
                    <select id="contractTypeFilter" class="form-control form-control-sm select2" multiple>
                        <option value="">All Types</option>
                        @foreach($contractTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->contract_type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="filter-label">Filter by Status</div>
                    <select id="contractStatusFilter" class="form-control form-control-sm">
                        <option value="all">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="review">Review</option>
                        <option value="negotiation">Negotiation</option>
                        <option value="approval">Approval</option>
                        <option value="approved">Approved</option>
                        <option value="signing">Signing</option>
                        <option value="executed">Executed</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button id="applyFilters" class="btn btn-sm btn-primary">
                        <i class="bx bx-filter"></i> Apply Filters
                    </button>
                    <button id="clearFilters" class="btn btn-sm btn-secondary">
                        <i class="bx bx-x"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Contracts Table -->
        <div class="contracts-table-card">
            <div class="contracts-table-header">
                <i class="bx bx-table"></i>
                <h5>Your Assigned Contracts</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="contractsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Contract Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Sub Status</th>
                            <th>Value</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="contractsTableBody">
                        @if(count($contracts) > 0)
                            @foreach($contracts as $contract)
                                <tr>
                                    <td>
                                        <strong>{{ decryptString($contract->contract_name, 'contract_name') }}</strong>
                                    </td>
                                    <td>
                                        @php
                                            $contractType = $contractTypes->where('id', $contract->contract_type_id)->first();
                                        @endphp
                                        {{ $contractType ? $contractType->contract_type : 'N/A' }}
                                    </td>
                                    <td>
                                        <span class="contracts-status-badge badge-{{ strtolower($contract->contract_status) }}">
                                            {{ $contract->contract_status }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($contract->substatus)
                                            <span class="contracts-status-badge badge-{{ strtolower($contract->substatus) }}">
                                                {{ $contract->substatus }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ !empty(decryptString($contract->currency_value, 'currency_value')) ? currency_formatter(decryptString($contract->currency, 'currency') ,decryptString($contract->currency_value, 'currency_value')) : '' }}
                                    </td>
                                    <td>
                                        {{ date('M d, Y', strtotime($contract->created_at)) }}
                                    </td>
                                    <td>
                                        <a href="{{ url('/') }}/contracts/{{ $contract->id }}/legal/view" class="btn btn-sm btn-info action-btn" title="View Contract">
                                            <i class="bx bx-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <div>
                                        <i class="bx bx-inbox"></i>
                                        <p class="mt-2">No contracts assigned to you yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Select2
        if(typeof $.fn.select2 !== 'undefined') {
            $('#contractTypeFilter').select2({
                placeholder: 'Select contract types',
                allowClear: true,
                width: '100%'
            });
        }

        // Apply Filters
        document.getElementById('applyFilters').addEventListener('click', function() {
            applyFilters();
        });

        // Clear Filters
        document.getElementById('clearFilters').addEventListener('click', function() {
            document.getElementById('contractTypeFilter').value = '';
            if(typeof $.fn.select2 !== 'undefined') {
                $('#contractTypeFilter').val(null).trigger('change');
            }
            document.getElementById('contractStatusFilter').value = 'all';
            location.reload();
        });

        // Enter key on filters
        document.getElementById('contractStatusFilter').addEventListener('keyup', function(e) {
            if(e.key === 'Enter') {
                applyFilters();
            }
        });
    });

    function applyFilters() {
        const contractTypes = Array.from(document.getElementById('contractTypeFilter').selectedOptions).map(opt => opt.value);
        const contractStatus = document.getElementById('contractStatusFilter').value;

        // For now, reload the page with filters
        // In production, you'd use AJAX to fetch filtered results
        let url = new URL(window.location.href);
        url.searchParams.set('contracttype', JSON.stringify(contractTypes));
        url.searchParams.set('contractstatus', contractStatus);
        window.location.href = url.toString();
    }
</script>
@endsection
