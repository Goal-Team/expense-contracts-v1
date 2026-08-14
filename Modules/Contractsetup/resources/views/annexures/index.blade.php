@extends('layouts/layoutMaster')

@section('title', 'Annexure Master')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var jq = window.jQuery;

        if (jq && jq.fn && jq.fn.select2) {
            jq('#filter_contract_type').select2({
                width: '100%',
                placeholder: 'All Contract Types',
                allowClear: true
            });
        }
    });
</script>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Annexures</h4>
        <a href="{{ route('contract-setup.annexures.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i> Add Annexure
        </a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <form method="GET" action="{{ route('contract-setup.annexures.index') }}" class="row g-3 align-items-end mb-3">
            <div class="col-md-4">
                <label for="filter_contract_type" class="form-label">Contract Type</label>
                <select class="form-select select2" id="filter_contract_type" name="contract_type">
                    <option value="">All Contract Types</option>
                    @foreach($contractTypes as $type)
                        <option value="{{ $type->contract_type_id }}"
                            {{ (string) $contractType === (string) $type->contract_type_id ? 'selected' : '' }}>
                            {{ $type->contract_type }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Filter</button>
                @if($contractType)
                    <a href="{{ route('contract-setup.annexures.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Annexure Name</th>
                        <th>Title</th>
                        <th>Contract Type</th>
                        <th>Sample File</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($annexures as $annexure)
                    <tr>
                        <td>{{ $annexure->id }}</td>
                        <td>{{ $annexure->annexure_name }}</td>
                        <td>{{ $annexure->title ?: '-' }}</td>
                        <td>{{ $annexure->contractType->contract_type ?? 'All' }}</td>
                        <td>
                            @if($annexure->sample_file)
                                <a href="{{ route('contract-setup.annexures.sample', $annexure->id) }}">
                                    <i class="ti ti-download ti-xs"></i>
                                    {{ \Illuminate\Support\Str::limit($annexure->sample_file_name, 30) }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($annexure->status)
                                <span class="badge bg-label-success">Active</span>
                            @else
                                <span class="badge bg-label-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a class="btn btn-sm btn-info" href="{{ route('contract-setup.annexures.edit', $annexure->id) }}">
                                <i class="ti ti-edit ti-xs"></i> Edit
                            </a>
                            <form action="{{ route('contract-setup.annexures.destroy', $annexure->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this annexure?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">
                                    <i class="ti ti-trash ti-xs"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No annexures found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($annexures->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                Showing {{ $annexures->firstItem() ?? 0 }} to {{ $annexures->lastItem() ?? 0 }} of {{ $annexures->total() }} entries
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $annexures->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
