@extends('layouts/layoutMaster')

@section('title', 'Test Master')

@section('page-script')
<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Tests</h4>
        <a href="{{ route('contract-setup.tests.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i> Add Test
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
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tests as $test)
                    <tr>
                        <td>{{ $test->id }}</td>
                        <td>{{ $test->name }}</td>
                        <td>{{ $test->price ? '₹ ' . number_format($test->price, 2) : '-' }}</td>
                        <td>
                            @if($test->status)
                                <span class="badge bg-label-success">Active</span>
                            @else
                                <span class="badge bg-label-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a class="btn btn-sm btn-info" href="{{ route('contract-setup.tests.edit', $test->id) }}">
                                <i class="ti ti-edit ti-xs"></i> Edit
                            </a>
                            <form action="{{ route('contract-setup.tests.destroy', $test->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this test?');">
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
                        <td colspan="5" class="text-center text-muted">No tests found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tests->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                Showing {{ $tests->firstItem() ?? 0 }} to {{ $tests->lastItem() ?? 0 }} of {{ $tests->total() }} entries
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $tests->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
