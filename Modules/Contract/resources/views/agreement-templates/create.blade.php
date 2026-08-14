@extends('contract::layouts/layoutMaster')
@section('title', 'Create Agreement Template')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Create Agreement Template</h4>
        <a href="{{ route('agreement-templates.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('agreement-templates.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Template Name</label>
                        <input type="text" name="template_name" class="form-control" value="{{ old('template_name') }}" placeholder="e.g. Standard Corporate Agreement">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Contract Type</label>
                        <select name="contract_type" class="form-select">
                            <option value="">— Select —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->contract_type_id }}" {{ old('contract_type') == $cat->contract_type_id ? 'selected' : '' }}>
                                    {{ $cat->contract_type ?? $cat->contract_type_id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Published</option>
                            <option value="archived" {{ old('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Version No</label>
                        <input type="number" name="version_no" class="form-control" value="{{ old('version_no', 1) }}" min="1">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Template HTML <small class="text-muted">(optional)</small></label>
                        <textarea name="template_html" class="form-control" rows="10" placeholder="Paste HTML template content here...">{{ old('template_html') }}</textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Available Variables <small class="text-muted">(click to copy)</small></label>
                        @if(count($availableVariables) > 0)
                            <div class="border rounded" style="max-height: 260px; overflow-y: auto;">
                                <table class="table table-sm table-hover mb-0">
                                    <tbody>
                                    @foreach($availableVariables as $token => $meta)
                                        @php $tokenDisplay = '{'.'{'.$token.'}'.'}'; @endphp
                                        <tr>
                                            <td class="align-middle">
                                                <code class="small">{{ $tokenDisplay }}</code>
                                                <div class="text-muted small">{{ $meta['label'] ?? $token }}</div>
                                            </td>
                                            <td class="text-end align-middle" width="40">
                                                <button type="button" class="btn btn-sm btn-outline-secondary copy-variable-btn" data-token="{{ $tokenDisplay }}" title="Copy">
                                                    <i class="ti ti-copy"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted small mb-0">No variables available.</p>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Source DOC/DOCX <small class="text-muted">(optional)</small></label>
                        <input type="file" name="source_docx" class="form-control" accept=".doc,.docx">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="ti ti-device-floppy me-1"></i> Save Template
                    </button>
                    <a href="{{ route('agreement-templates.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.copy-variable-btn');
    if (! btn) return;
    var token = btn.getAttribute('data-token');
    navigator.clipboard.writeText(token).then(function () {
        var icon = btn.querySelector('i');
        icon.classList.remove('ti-copy');
        icon.classList.add('ti-check');
        setTimeout(function () {
            icon.classList.remove('ti-check');
            icon.classList.add('ti-copy');
        }, 1200);
    });
});
</script>
@endsection
