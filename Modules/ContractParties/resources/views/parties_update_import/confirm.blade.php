@extends('layouts.layoutMaster')

@section('title', 'Confirm Party Updates')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
<script type="module">
$(document).ready(function() {
    $('#changesTable').DataTable({
        order: [[0, 'asc']],
        pageLength: 25,
        language: {
            search: "Search changes:"
        }
    });

    $('#unmatchedTable').DataTable({
        order: [[0, 'asc']],
        pageLength: 25
    });
});
</script>
@endsection

@section('content')
<style>
    .summary-card {
        border-left: 4px solid;
    }
    .summary-card.matched { border-left-color: #198754; }
    .summary-card.unmatched { border-left-color: #dc3545; }
    .summary-card.total { border-left-color: #0d6efd; }
    .summary-number {
        font-size: 2rem;
        font-weight: 700;
    }
    .change-row {
        font-size: 13px;
    }
    .old-value {
        color: #dc3545;
        text-decoration: line-through;
    }
    .new-value {
        color: #198754;
        font-weight: 600;
    }
    .empty-value {
        color: #6c757d;
        font-style: italic;
    }
</style>

<div class="container shadow min-vh-100 py-2">
    <div class="container network_wrapper col-sm p-2">

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="ti ti-check-circle me-1"></i> Confirm Updates
                </h5>
            </div>
            <div class="card-body">

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card summary-card total">
                            <div class="card-body text-center">
                                <div class="summary-number text-primary">{{ number_format($totalRows) }}</div>
                                <div class="text-muted">Total Rows in Excel</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card summary-card matched">
                            <div class="card-body text-center">
                                <div class="summary-number text-success">{{ number_format(count($matchedParties)) }}</div>
                                <div class="text-muted">Parties Matched</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card summary-card unmatched">
                            <div class="card-body text-center">
                                <div class="summary-number text-danger">{{ number_format(count($unmatchedParties)) }}</div>
                                <div class="text-muted">Parties Not Found</div>
                            </div>
                        </div>
                    </div>
                </div>

                @if(count($matchedParties) > 0)
                <div class="alert alert-success">
                    <i class="ti ti-check me-1"></i>
                    <strong>{{ count($matchedParties) }}</strong> parties will be updated with the changes shown below.
                </div>

                <h6 class="mb-3">Changes to be made:</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm" id="changesTable">
                        <thead>
                            <tr>
                                <th>Party Name</th>
                                <th>Field</th>
                                <th>Old Value</th>
                                <th>New Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($matchedParties as $party)
                            @foreach($party['changes'] as $change)
                            <tr class="change-row">
                                <td><strong>{{ $party['party_name'] }}</strong></td>
                                <td><span class="badge bg-info">{{ $change['field_label'] }}</span></td>
                                <td>
                                    @if(empty($change['old_value']))
                                    <span class="empty-value">(empty)</span>
                                    @else
                                    <span class="old-value">{{ Str::limit($change['old_value'], 50) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if(empty($change['new_value']))
                                    <span class="empty-value">(empty)</span>
                                    @else
                                    <span class="new-value">{{ Str::limit($change['new_value'], 50) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-warning">
                    <i class="ti ti-alert-triangle me-1"></i>
                    No parties matched. Please check the party names in your Excel file.
                </div>
                @endif

                @if(count($unmatchedParties) > 0)
                <div class="alert alert-warning mt-3">
                    <i class="ti ti-alert-triangle me-1"></i>
                    <strong>{{ count($unmatchedParties) }}</strong> parties were not found in the system. These rows will be skipped.
                </div>

                <h6 class="mb-3 mt-4">Unmatched Parties:</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="unmatchedTable">
                        <thead>
                            <tr>
                                <th>Row #</th>
                                <th>Party Name (from Excel)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unmatchedParties as $unmatched)
                            <tr>
                                <td>{{ $unmatched['row_number'] }}</td>
                                <td>{{ $unmatched['party_name'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <form id="confirmForm" action="{{ route('parties.parties_update_import_execute') }}" method="POST" class="mt-4">
                    @csrf
                    <input type="hidden" name="batch_id" value="{{ $batchId }}">

                    @if(count($matchedParties) > 0)
                    <button type="submit" class="btn btn-success waves-effect waves-light" id="confirmBtn"
                            onclick="return confirm('Are you sure you want to update {{ count($matchedParties) }} parties? This action cannot be undone.');">
                        <i class="ti ti-check me-1"></i> Confirm & Update {{ count($matchedParties) }} Parties
                    </button>
                    @endif
                    <a href="{{ route('parties.parties_update_import_view') }}" class="btn btn-label-secondary waves-effect ms-2">
                        <i class="ti ti-x me-1"></i> Cancel
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
