@extends('layouts/layoutMaster')

@section('title', 'Custom Variable For Templates')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/tagify/tagify.scss'])
@endsection

@section('vendor-script')
    <link href="{{ url('/') }}/assets/css/custom.css" rel="stylesheet" />
    <link href="{{ url('/') }}/Modules/Contractsetup/resources/assets/css/customfields.css" rel="stylesheet" />
@endsection

@section('page-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/js/forms-selects.js'])
    <script type="module" src="{{ url('/') }}/Modules/Contractsetup/resources/assets/js/jquery-ui.js"></script>
    <script type="module" src="{{ url('/') }}/assets/js/jquery.validate.min.js"></script>
    <script type="module" src="{{ url('/') }}/Modules/Contractsetup/resources/assets/js/jquery.serialize-object.js">
    </script>
    <script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/health_packages.js"></script>
@endsection

@section('content')
<div class="page-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col">
                <h2><i class="fas fa-edit"></i> Edit Health Check</h2>
            </div>
            <div class="col-auto">
                <a href="{{ route('contract-setup.health-checks.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0">Edit Health Check Information</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('contract-setup.health-checks.update', $healthCheck->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="test_name" class="form-label">Test Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('test_name') is-invalid @enderror" 
                                   id="test_name" name="test_name" 
                                   value="{{ old('test_name', $healthCheck->test_name) }}" required>
                            @error('test_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="test_code" class="form-label">Test Code</label>
                            <input type="text" class="form-control @error('test_code') is-invalid @enderror" 
                                   id="test_code" name="test_code" 
                                   value="{{ old('test_code', $healthCheck->test_code) }}">
                            @error('test_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control @error('category') is-invalid @enderror" 
                                   id="category" name="category" 
                                   value="{{ old('category', $healthCheck->category) }}" 
                                   placeholder="e.g., Blood Tests, Radiology">
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="default_price" class="form-label">Default Price (₹)</label>
                            <input type="number" step="0.01" min="0" 
                                   class="form-control @error('default_price') is-invalid @enderror" 
                                   id="default_price" name="default_price" 
                                   value="{{ old('default_price', $healthCheck->default_price) }}">
                            @error('default_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="4">{{ old('description', $healthCheck->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="status" name="status" 
                                   value="1" {{ old('status', $healthCheck->status) ? 'checked' : '' }}>
                            <label class="form-check-label" for="status">
                                Active Status
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="{{ route('contract-setup.health-checks.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Update Health Check
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection