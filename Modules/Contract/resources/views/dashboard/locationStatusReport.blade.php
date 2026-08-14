@extends('layouts/layoutMaster')

@section('title', 'Location Status Report')

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/select2/select2.scss',
])
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/select2/select2.js',
])
@endsection

@section('content')

<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Dashboard /</span> Location Status Report
</h4>

{{-- ── Summary cards ──────────────────────────────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="badge bg-label-primary rounded p-2">
                    <i class="ti ti-files ti-xl"></i>
                </span>
                <div>
                    <h5 class="mb-0">{{ $totals['total'] }}</h5>
                    <small class="text-muted">Total Executed</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="badge bg-label-success rounded p-2">
                    <i class="ti ti-file-like ti-xl"></i>
                </span>
                <div>
                    <h5 class="mb-0">{{ $totals['active'] }}</h5>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="badge bg-label-danger rounded p-2">
                    <i class="ti ti-file-time ti-xl"></i>
                </span>
                <div>
                    <h5 class="mb-0">{{ $totals['expired'] }}</h5>
                    <small class="text-muted">Expired</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="badge bg-label-warning rounded p-2">
                    <i class="ti ti-clock-exclamation ti-xl"></i>
                </span>
                <div>
                    <h5 class="mb-0">{{ $totals['expiring_soon'] }}</h5>
                    <small class="text-muted">Going to Expire (Next 90 Days)</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Filters + Export ───────────────────────────────────────────────────── --}}
{{-- NOTE: export form is intentionally OUTSIDE the filter form – nested forms are invalid HTML --}}
<form id="exportForm"
      action="{{ url('/contracts/dashboard/location-status/export') }}"
      method="POST"
      style="display:none">
    @csrf
    {{-- Hidden inputs populated by JS when Export Excel is clicked --}}
</form>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filters</h5>
    </div>
    <div class="card-body">
        <form id="filterForm"
              action="{{ url('/contracts/dashboard/location-status') }}"
              method="POST"
              enctype="multipart/form-data">
            @csrf
            <div class="row align-items-end g-3">
                <div class="col-lg-5 col-md-6">
                    <label class="form-label">Internal First-Party Location</label>
                    <select class="form-select select2"
                            multiple
                            name="contractlocs[]"
                            id="contractlocs">
                        @foreach ($branchs as $branch)
                            <option value="{{ $branch->id }}"
                                {{ in_array($branch->id, $sellocal) ? 'selected' : '' }}>
                                {{ $branch->LegalName ?? $branch->BranchName }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter me-1"></i> Apply Filter
                    </button>
                    <a href="{{ url('/contracts/dashboard/location-status') }}"
                       class="btn btn-outline-secondary ms-2">
                        <i class="ti ti-x me-1"></i> Clear
                    </a>
                </div>
                <div class="col-auto ms-auto">
                    <button type="button" id="exportBtn" class="btn btn-success">
                        <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── Data table ─────────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Contracts by Location &amp; Status</h5>
        <small class="text-muted">Executed contracts only · expiry window = 90 days</small>
    </div>
    <div class="card-body px-0 pt-0">

        @if (count($locationData) === 0)
            <div class="p-4 text-center text-muted">
                No executed contracts found for the selected location(s).
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Location (Internal First-Party)</th>
                            <th class="text-center text-success">
                                <i class="ti ti-file-like me-1"></i> Active
                            </th>
                            <th class="text-center text-danger">
                                <i class="ti ti-file-time me-1"></i> Expired
                            </th>
                            <th class="text-center text-warning">
                                <i class="ti ti-clock-exclamation me-1"></i>
                                Going to Expire<br><small class="fw-normal">(Next 90 Days)</small>
                            </th>
                            <th class="text-center">Total Executed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowIndex = 1; @endphp
                        @foreach ($locationData as $locId => $data)
                            <tr>
                                <td>{{ $rowIndex++ }}</td>
                                <td>{{ $data['location_name'] }}</td>
                                <td class="text-center">
                                    @if ($data['active'] > 0)
                                        <span class="badge bg-label-success">{{ $data['active'] }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($data['expired'] > 0)
                                        <span class="badge bg-label-danger">{{ $data['expired'] }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($data['expiring_soon'] > 0)
                                        <span class="badge bg-label-warning">{{ $data['expiring_soon'] }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center fw-semibold">{{ $data['total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end">Grand Total</td>
                            <td class="text-center text-success">{{ $totals['active'] }}</td>
                            <td class="text-center text-danger">{{ $totals['expired'] }}</td>
                            <td class="text-center text-warning">{{ $totals['expiring_soon'] }}</td>
                            <td class="text-center">{{ $totals['total'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const exportForm = document.getElementById('exportForm');
    const locsSelect = document.getElementById('contractlocs');

    document.getElementById('exportBtn').addEventListener('click', function () {
        // Remove any previously-added location inputs
        exportForm.querySelectorAll('input[name="contractlocs[]"]').forEach(el => el.remove());

        // Mirror current select2 selections into the export form
        Array.from(locsSelect.selectedOptions).forEach(function (opt) {
            const hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = 'contractlocs[]';
            hidden.value = opt.value;
            exportForm.appendChild(hidden);
        });

        exportForm.submit();
    });
});
</script>

@endsection
