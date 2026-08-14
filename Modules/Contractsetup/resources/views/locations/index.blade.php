@extends('layouts/layoutMaster')

@section('title', 'Location Master')

@section('page-script')
<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Locations</h4>
        <a href="{{ route('contract-setup.locations.create') }}" class="btn btn-primary">Add Location</a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Region</th>
                        <th>Address</th>
                        <th>City</th>
                        <th>State</th>
                        <th>Regional Approvers</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($locations as $loc)
                    <tr>
                        <td>{{ $loc->id }}</td>
                        <td>{{ $loc->location_name }}</td>
                        <td>{{ $loc->region }}</td>
                        <td>{{ $loc->address }}</td>
                        <td>{{ $loc->city }}</td>
                        <td>{{ $loc->state }}</td>
                        <td>
                            @if($loc->regional_verifier_email || $loc->regional_approver_email || $loc->regional_signatory_email)
                                <small>
                                    @if($loc->regional_verifier_email)
                                        <span class="badge bg-label-info mb-1" title="{{ $loc->regional_verifier_email }}">
                                            <i class="ti ti-user-check ti-xs"></i> {{ $loc->regional_verifier_name ?: 'Verifier' }}
                                        </span>
                                    @endif
                                    @if($loc->regional_approver_email)
                                        <span class="badge bg-label-warning mb-1" title="{{ $loc->regional_approver_email }}">
                                            <i class="ti ti-checkbox ti-xs"></i> {{ $loc->regional_approver_name ?: 'Approver' }}
                                        </span>
                                    @endif
                                    @if($loc->regional_signatory_email)
                                        <span class="badge bg-label-success mb-1" title="{{ $loc->regional_signatory_email }}">
                                            <i class="ti ti-signature ti-xs"></i> {{ $loc->regional_signatory_name ?: 'Signatory' }}
                                        </span>
                                    @endif
                                </small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $loc->status ? 'Active' : 'Inactive' }}</td>
                        <td>
                            <a class="btn btn-sm btn-info" href="{{ route('contract-setup.locations.edit', $loc->id) }}">Edit</a>
                            <form action="{{ route('contract-setup.locations.destroy', $loc->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this location?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                Showing {{ $locations->firstItem() ?? 0 }} to {{ $locations->lastItem() ?? 0 }} of {{ $locations->total() }} entries
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $locations->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection