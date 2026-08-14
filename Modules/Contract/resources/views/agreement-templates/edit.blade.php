@extends('contract::layouts/layoutMaster')
@section('title', 'Edit Agreement Template')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Edit Agreement Template #{{ $template->id }}</h4>
        <a href="{{ route('agreement-templates.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back to List
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

    {{-- ACTION BAR: Sync / Preview / Publish / Download --}}
    <div class="card mb-4">
        <div class="card-body">
            @if(!empty($unresolvedTokens))
                <div class="alert alert-warning mb-3">
                    <strong>Unresolved required variables:</strong> {{ implode(', ', $unresolvedTokens) }}
                </div>
            @endif

            <div class="d-flex flex-wrap gap-2">
                {{-- Sync Placeholders --}}
                <form action="{{ route('agreement-templates.sync-placeholders', $template->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-info">
                        <i class="ti ti-refresh me-1"></i> Sync Placeholders
                    </button>
                </form>

                {{-- Preview PDF --}}
                <form action="{{ route('agreement-templates.preview', $template->id) }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="values_json" value="{}">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="ti ti-eye me-1"></i> Preview PDF
                    </button>
                </form>

                {{-- Publish --}}
                @if($template->status !== 'published')
                    <form action="{{ route('agreement-templates.publish', $template->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Publish this template? Any existing published template for the same scope will be archived.')">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="ti ti-send me-1"></i> Publish
                        </button>
                    </form>
                @else
                    <span class="btn btn-outline-success disabled"><i class="ti ti-check me-1"></i> Published</span>
                @endif

                {{-- Download DOCX --}}
                @if($template->source_docx_path)
                    <a href="{{ route('agreement-templates.download', $template->id) }}" class="btn btn-outline-secondary">
                        <i class="ti ti-download me-1"></i> Download DOCX
                    </a>
                @endif

                {{-- Delete --}}
                <form action="{{ route('agreement-templates.destroy', $template->id) }}" method="POST" class="d-inline ms-auto" onsubmit="return confirm('Delete this template permanently?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="ti ti-trash me-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- VARIABLES TABLE --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Template Variables</h5>
        </div>
        <div class="card-body">
            @if($variables->count() > 0)
                <form action="{{ route('agreement-templates.variables.update', $template->id) }}" method="POST">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Variable Key</th>
                                    <th>Source</th>
                                    <th width="80">Required</th>
                                    <th>Default Value</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($variables as $variable)
                                <tr>
                                    <td><code>{{ $variable->variable_key }}</code></td>
                                    <td><span class="badge bg-label-{{ $variable->source === 'custom_var_docs' ? 'info' : 'secondary' }}">{{ $variable->source ?? 'manual' }}</span></td>
                                    <td class="text-center">
                                        <input type="checkbox" name="required[{{ $variable->id }}]" value="1" {{ $variable->required ? 'checked' : '' }} class="form-check-input">
                                    </td>
                                    <td>
                                        <input type="text" name="default_value[{{ $variable->id }}]" class="form-control form-control-sm" value="{{ $variable->default_value }}">
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-2">
                        <i class="ti ti-device-floppy me-1"></i> Save Variables
                    </button>
                </form>
            @else
                <p class="text-muted mb-0">No variables found. Upload a DOCX or add HTML content, then click "Sync Placeholders".</p>
            @endif
        </div>
    </div>

    {{-- EDIT FORM --}}
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
        <div class="card-header">
            <h5 class="card-title mb-0">Template Details</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('agreement-templates.update', $template->id) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Template Name</label>
                        <input type="text" name="template_name" class="form-control" value="{{ old('template_name', $template->template_name) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Contract Type</label>
                        <select name="contract_type" class="form-select">
                            <option value="">— Select —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->contract_type_id }}" {{ old('contract_type', $template->contract_type) == $cat->contract_type_id ? 'selected' : '' }}>
                                    {{ $cat->contract_type ?? $cat->contract_type_id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" {{ old('status', $template->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status', $template->status) == 'published' ? 'selected' : '' }}>Published</option>
                            <option value="archived" {{ old('status', $template->status) == 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Version No</label>
                        <input type="number" name="version_no" class="form-control" value="{{ old('version_no', $template->version_no) }}" min="1">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Template HTML <small class="text-muted">(optional)</small></label>
                        <textarea name="template_html" class="form-control" rows="10">{{ old('template_html', $template->template_html) }}</textarea>
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
                        <label class="form-label">Source DOC/DOCX <small class="text-muted">(optional — replaces existing)</small></label>
                        <input type="file" name="source_docx" class="form-control" accept=".doc,.docx">
                        @if($template->source_docx_filename)
                            <small class="text-muted">Current: {{ $template->source_docx_filename }}</small>
                        @endif
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> Update Template
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
