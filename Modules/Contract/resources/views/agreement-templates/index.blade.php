@extends('contract::layouts/layoutMaster')
@section('title', 'Agreement Templates')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Agreement Templates</h4>
        <a href="{{ route('agreement-templates.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i> Add Template
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- FILTERS --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('agreement-templates.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Template Name</label>
                        <input type="text" name="template_name" class="form-control" value="{{ $filters['template_name'] ?? '' }}" placeholder="Search...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Contract Type</label>
                        <select name="contract_type" class="form-select">
                            <option value="">All</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->contract_type_id }}" {{ ($filters['contract_type'] ?? '') == $cat->contract_type_id ? 'selected' : '' }}>
                                    {{ $cat->contract_type ?? $cat->contract_type_id }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment Type</label>
                        <select name="payment_type" class="form-select">
                            <option value="">All</option>
                            @foreach($paymentTypes as $pt)
                                <option value="{{ $pt }}" {{ ($filters['payment_type'] ?? '') == $pt ? 'selected' : '' }}>{{ $pt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Entity Type</label>
                        <select name="entity_type_id" class="form-select">
                            <option value="">All</option>
                            @foreach($entityTypes as $et)
                                <option value="{{ $et->id }}" {{ ($filters['entity_type_id'] ?? '') == $et->id ? 'selected' : '' }}>
                                    {{ $et->entity_type_name ?? $et->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="draft" {{ ($filters['status'] ?? '') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ ($filters['status'] ?? '') == 'published' ? 'selected' : '' }}>Published</option>
                            <option value="archived" {{ ($filters['status'] ?? '') == 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Template Name</th>
                        <th>Contract Type</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Version</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($templates as $template)
                    <tr>
                        <td>{{ $template->id }}</td>
                        <td>{{ $template->template_name ?? '—' }}</td>
                        <td>{{ $template->contract_type ?? '—' }}</td>
                        <td>{{ $template->payment_type ?? '—' }}</td>
                        <td>
                            @if($template->status === 'published')
                                <span class="badge bg-success">Published</span>
                            @elseif($template->status === 'archived')
                                <span class="badge bg-secondary">Archived</span>
                            @else
                                <span class="badge bg-warning">Draft</span>
                            @endif
                        </td>
                        <td>{{ $template->version_no }}</td>
                        <td>
                            <a href="{{ route('agreement-templates.edit', $template->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-edit"></i>
                            </a>
                            @if($template->source_docx_path)
                                <a href="{{ route('agreement-templates.download', $template->id) }}" class="btn btn-sm btn-outline-info">
                                    <i class="ti ti-download"></i>
                                </a>
                            @endif
                            <form action="{{ route('agreement-templates.destroy', $template->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No templates found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($templates->hasPages())
            <div class="card-footer">
                {{ $templates->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
