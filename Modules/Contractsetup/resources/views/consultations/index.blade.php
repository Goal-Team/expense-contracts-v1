@extends('layouts/layoutMaster')

@section('title', 'Consultation Master')

@section('page-script')
<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Consultations</h4>
        <a href="{{ route('contract-setup.consultations.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i> Add Consultation
        </a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consultations as $consultation)
                    <tr>
                        <td>{{ $consultation->id }}</td>
                        <td>{{ $consultation->name }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($consultation->description, 50) }}</td>
                        <td>{{ $consultation->price ? number_format($consultation->price, 2) : '-' }}</td>
                        <td>
                            @if($consultation->status)
                                <span class="badge bg-label-success">Active</span>
                            @else
                                <span class="badge bg-label-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a class="btn btn-sm btn-info" href="{{ route('contract-setup.consultations.edit', $consultation->id) }}">
                                <i class="ti ti-edit ti-xs"></i> Edit
                            </a>
                            <form action="{{ route('contract-setup.consultations.destroy', $consultation->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this consultation?');">
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
                        <td colspan="6" class="text-center text-muted">No consultations found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($consultations->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                Showing {{ $consultations->firstItem() ?? 0 }} to {{ $consultations->lastItem() ?? 0 }} of {{ $consultations->total() }} entries
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $consultations->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
