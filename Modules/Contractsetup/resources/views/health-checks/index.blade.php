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
                <h2><i class="fas fa-notes-medical"></i> Health Checks Master</h2>
            </div>
            <div class="col-auto">
                <a href="{{ route('contract-setup.health-checks.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Health Check
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <form method="GET" action="{{ route('contract-setup.health-checks.index') }}" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by name, code..." 
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                            {{ $category }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="{{ route('contract-setup.health-checks.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>
    
    <div class="card-body">
        <form id="bulkDeleteForm" method="POST" action="{{ route('contract-setup.health-checks.bulk-destroy') }}">
            @csrf
            
            <div class="mb-3">
                <button type="button" id="bulkDeleteBtn" class="btn btn-danger btn-sm" disabled>
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
                <span id="selectedCount" class="text-muted ms-2"></span>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="healthChecksTable">
                    <thead class="table-light">
                        <tr>
                            <th width="30">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Test Name</th>
                            <th>Test Code</th>
                            <th>Category</th>
                            <th>Default Price</th>
                            <th>Status</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($healthChecks as $healthCheck)
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $healthCheck->id }}" class="row-checkbox">
                            </td>
                            <td><strong>{{ $healthCheck->test_name }}</strong></td>
                            <td>{{ $healthCheck->test_code ??  '-' }}</td>
                            <td>
                                @if($healthCheck->category)
                                    <span class="badge bg-info">{{ $healthCheck->category }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>₹{{ number_format($healthCheck->default_price, 2) }}</td>
                            <td>
                                @if($healthCheck->status)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('contract-setup.health-checks.show', $healthCheck->id) }}" 
                                       class="btn btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('contract-setup.health-checks.edit', $healthCheck->id) }}" 
                                       class="btn btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger" 
                                            onclick="deleteHealthCheck({{ $healthCheck->id }})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                
                                <form id="delete-form-{{ $healthCheck->id }}" 
                                      action="{{ route('contract-setup.health-checks.destroy', $healthCheck->id) }}" 
                                      method="POST" style="display: none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                No health checks found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                Showing {{ $healthChecks->firstItem() ??  0 }} to {{ $healthChecks->lastItem() ?? 0 }} 
                of {{ $healthChecks->total() }} entries
            </div>
            <div>
                {{ $healthChecks->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Select all checkboxes
    $('#selectAll').on('change', function() {
        $('. row-checkbox').prop('checked', this.checked);
        updateBulkDeleteButton();
    });
    
    // Individual checkbox change
    $('.row-checkbox').on('change', function() {
        updateBulkDeleteButton();
    });
    
    // Update bulk delete button state
    function updateBulkDeleteButton() {
        const checked = $('.row-checkbox:checked'). length;
        $('#bulkDeleteBtn'). prop('disabled', checked === 0);
        $('#selectedCount').text(checked > 0 ? `${checked} item(s) selected` : '');
    }
    
    // Bulk delete
    $('#bulkDeleteBtn').on('click', function() {
        if (confirmDelete('Are you sure you want to delete selected health checks?')) {
            $('#bulkDeleteForm').submit();
        }
    });
    
    // Delete single item
    function deleteHealthCheck(id) {
        if (confirmDelete('Are you sure you want to delete this health check?')) {
            document.getElementById('delete-form-' + id).submit();
        }
    }
</script>
@endsection