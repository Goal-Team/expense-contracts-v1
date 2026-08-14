@extends('layouts/layoutMaster')

@section('title', 'Create Location')

@section('content')
<div class="card">
    <div class="card-header"><h4>Create Location</h4></div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('contract-setup.locations.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3">Location Details</h5>
                    <div class="mb-3">
                        <label class="form-label">Location Name <span class="text-danger">*</span></label>
                        <input type="text" name="location_name" class="form-control" value="{{ old('location_name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Region</label>
                        <input type="text" name="region" class="form-control" value="{{ old('region') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Enter full address">{{ old('address') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="{{ old('city') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-control" value="{{ old('state') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3">Regional Approvers</h5>
                    
                    {{-- Regional Verifier --}}
                    <div class="card mb-3 border">
                        <div class="card-header bg-light py-2">
                            <strong><i class="ti ti-user-check me-1"></i> Regional Verifier</strong>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Verifier Name</label>
                                <input type="text" name="regional_verifier_name" class="form-control" value="{{ old('regional_verifier_name') }}" placeholder="Enter verifier name">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Verifier Email</label>
                                <input type="email" name="regional_verifier_email" class="form-control" value="{{ old('regional_verifier_email') }}" placeholder="Enter verifier email">
                            </div>
                        </div>
                    </div>

                    {{-- Regional Approver --}}
                    <div class="card mb-3 border">
                        <div class="card-header bg-light py-2">
                            <strong><i class="ti ti-checkbox me-1"></i> Regional Approver</strong>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Approver Name</label>
                                <input type="text" name="regional_approver_name" class="form-control" value="{{ old('regional_approver_name') }}" placeholder="Enter approver name">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Approver Email</label>
                                <input type="email" name="regional_approver_email" class="form-control" value="{{ old('regional_approver_email') }}" placeholder="Enter approver email">
                            </div>
                        </div>
                    </div>

                    {{-- Regional Signatory --}}
                    <div class="card mb-3 border">
                        <div class="card-header bg-light py-2">
                            <strong><i class="ti ti-signature me-1"></i> Regional Signatory</strong>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Signatory Name</label>
                                <input type="text" name="regional_signatory_name" class="form-control" value="{{ old('regional_signatory_name') }}" placeholder="Enter signatory name">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Signatory Email</label>
                                <input type="email" name="regional_signatory_email" class="form-control" value="{{ old('regional_signatory_email') }}" placeholder="Enter signatory email">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <button class="btn btn-primary">Create</button>
            <a href="{{ route('contract-setup.locations.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection