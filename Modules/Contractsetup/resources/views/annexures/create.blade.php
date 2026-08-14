@extends('layouts/layoutMaster')

@section('title', 'Add Annexure')

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
            jq('#contract_type').select2({
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
        <h4>Add Annexure</h4>
        <a href="{{ route('contract-setup.annexures.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back to List
        </a>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('contract-setup.annexures.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="annexure_name" class="form-label">Annexure Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('annexure_name') is-invalid @enderror"
                           id="annexure_name" name="annexure_name" value="{{ old('annexure_name') }}" required>
                    @error('annexure_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                           id="title" name="title" value="{{ old('title') }}">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="contract_type" class="form-label">Contract Type</label>
                    <select class="form-select select2 @error('contract_type') is-invalid @enderror"
                             id="contract_type" name="contract_type">
                        <option value="">All Contract Types</option>
                        @foreach($contractTypes as $contractType)
                            <option value="{{ $contractType->contract_type_id }}"
                                {{ (string) old('contract_type') === (string) $contractType->contract_type_id ? 'selected' : '' }}>
                                {{ $contractType->contract_type }}
                            </option>
                        @endforeach
                    </select>
                    @error('contract_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="sample_file" class="form-label">Sample File</label>
                    <input type="file" class="form-control @error('sample_file') is-invalid @enderror"
                           id="sample_file" name="sample_file" accept=".doc,.docx">
                    <small class="text-muted">Word documents only (.doc or .docx), up to 10 MB.</small>
                    @error('sample_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="status" name="status" value="1"
                               {{ old('status', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="status">Active</label>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Save Annexure
                </button>
                <a href="{{ route('contract-setup.annexures.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
