@extends('layouts/layoutMaster')
@section('title', ' Agreement List')
<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/quill/typography.scss',
'resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/quill/editor.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/dropzone/dropzone.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/tagify/tagify.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/quill/katex.js',
'resources/assets/vendor/libs/quill/quill.js',
'resources/assets/vendor/libs/cleavejs/cleave.js',
'resources/assets/vendor/libs/tagify/tagify.js',
'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
'resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/dropzone/dropzone.js',
'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
'resources/assets/vendor/libs/jquery-sticky/jquery-sticky.js'
])

<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
<link href="{{url('/')}}/Modules/Contract/resources/assets/sass/contractrep.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')

@vite(['resources/assets/js/forms-file-upload.js'])
@vite(['resources/assets/js/form-layouts.js'])

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/blob.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/filesaver.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/htmdocx.js"></script>
<script type="module" src="{{url('/')}}/Modules/ContractParties/resources/assets/js/scriptparty.js"></script>
<!--<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contractRep.js"></script>-->

@endsection
@section('content')

   @if(Session::has('message'))
  <p class="alert {{ Session::get('alert-class', 'alert-info') }} alert-dismissible mb-2">{!! Session::get('message') !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </p>
   @endif

<div class="page-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col">
                <h2><i class="fas fa-file-contract"></i> Revenue Contracts</h2>
                <p class="text-muted mb-0">List of contracts with quick actions and filters.</p>
            </div>
            <div class="col-auto">
                <a href="{{ url('/contracts/create/contract-custom') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Contract
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <form method="GET" action="{{ url('/contracts/list/contract-custom') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search contract name or ID"
                       value="{{ request('search') }}">
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Renewed" {{ request('status') === 'Renewed' ? 'selected' : '' }}>Renewed</option>
                    <option value="Expired" {{ request('status') === 'Expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>

            <div class="col-md-2">
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>

            <div class="col-md-2">
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>

            <div class="col-md-3 text-end">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="{{ url('/contracts/list/contract-custom') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <div class="card-body">
        <form id="bulkDeleteForm" method="POST" action="{{ url('/contracts/bulk-delete') }}">
            @csrf

            <div class="mb-3 d-flex align-items-center">
                <button type="button" id="bulkDeleteBtn" class="btn btn-danger btn-sm me-2" disabled>
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
                <span id="selectedCount" class="text-muted"></span>
                <div class="ms-auto">
                    <label class="me-2">Per page:</label>
                    <select id="perPage" class="form-select form-select-sm d-inline-block" style="width: auto;"
                            onchange="changePerPage(this.value)">
                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="30">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Contract ID</th>
                            <th>Contract Name</th>
                            <th>Customer</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th width="170">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contracts as $contract)
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $contract->id }}" class="row-checkbox">
                            </td>

                            <td>#{{ $contract->contract_unique_id ?? $contract->id }}</td>

                            <td>
                                <a href="{{ url('/contracts/show/contract-custom/'.$contract->id) }}" class="fw-semibold">
                                    {{ decryptString($contract->contract_name, 'contract_name') ?? '—' }}
                                </a>
                                @if($contract->parentcontract)
                                    <div><small class="text-muted">Parent: #{{ optional($contract->contractParent)->contract_unique_id ?? $contract->parentcontract }}</small></div>
                                @endif
                            </td>

                            <td>
                                @if(optional($contract->contractPartyList->get(1))->partyDetailsEx)
                                    {{ decryptString($contract->contractPartyList->get(1)->partyDetailsEx->company_name, 'company_name') }}
                                @else
                                    -
                                @endif
                            </td>

                            <td>{{ optional($contract->fixed_date ? \Carbon\Carbon::parse($contract->fixed_date) : null)->format('d M Y') ?? '-' }}</td>

                            <td>{{ optional($contract->contract_end_date ? \Carbon\Carbon::parse($contract->contract_end_date) : null)->format('d M Y') ?? '-' }}</td>
                            
                            <td>
                                @php
                                    $isActive = (strtolower($contract->contract_status) === 'executed' || strtolower($contract->status) === 'active');
                                    $codesMissing = $isActive && (empty($contract->mm_code) || empty($contract->oracle_code));
                                @endphp
                                @if($isActive)
                                    <span class="badge bg-success">Active</span>
                                    @if($codesMissing)
                                        <span class="text-warning ms-2" title="MM Code or Oracle Code missing">⚠️</span>
                                    @endif
                                @elseif(strtolower($contract->contract_status) === 'renewed')
                                    <span class="badge bg-primary">Renewed</span>
                                @elseif(optional($contract->contract_end_date) && \Carbon\Carbon::parse($contract->contract_end_date)->isPast())
                                    <span class="badge bg-secondary">Expired</span>
                                @else
                                    <span class="badge bg-light text-dark text-capitalize">{{ $contract->contract_status ?? ($contract->status ?? '—') }}</span>
                                @endif
                            </td>

                            <td>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                                    <a href="{{ url('contracts/show/contract-custom/'.$contract->id) }}" class="btn btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @php $isActive = (strtolower($contract->contract_status) === 'executed' || strtolower($contract->status) === 'active'); @endphp
                                    <button type="button" class="btn btn-outline-secondary mm-oracle-btn" data-id="{{ $contract->id }}" title="Codes" @if(!$isActive) disabled @endif>
                                        <i class="fas fa-cogs"></i> Actions
                                    </button>
                                </div>

                                <form id="delete-form-{{ $contract->id }}" action="{{ url('/contracts/'.$contract->id) }}" method="POST" style="display:none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No contracts found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                Showing {{ $contracts->firstItem() ?? 0 }} to {{ $contracts->lastItem() ?? 0 }} of {{ $contracts->total() }} entries
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $contracts->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection

<script>
    // // Select all toggle
    // $('#selectAll').on('change', function() {
    //     $('.row-checkbox').prop('checked', this.checked);
    //     updateBulkState();
    // });

    // $(document).on('change', '.row-checkbox', function() {
    //     updateBulkState();
    // });

    // function updateBulkState() {
    //     const checked = $('.row-checkbox:checked').length;
    //     $('#bulkDeleteBtn').prop('disabled', checked === 0);
    //     $('#selectedCount').text(checked > 0 ? `${checked} selected` : '');
    // }

    // // Bulk delete action
    // $('#bulkDeleteBtn').on('click', function() {
    //     if (!confirm('Are you sure you want to delete selected contracts?')) return;
    //     $('#bulkDeleteForm').submit();
    // });

    // // Single delete
    // function deleteContract(id) {
    //     if (!confirm('Are you sure you want to delete this contract?')) return;
    //     document.getElementById('delete-form-' + id).submit();
    // }

    // // Change per page and reload
    // function changePerPage(value) {
    //     const params = new URLSearchParams(window.location.search);
    //     params.set('per_page', value);
    //     window.location.search = params.toString();
    // }

    // MM/Oracle Codes modal handling
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.mm-oracle-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                if (!id) return;
                // fetch current codes
                fetch(APP_URL + '/contracts/approval/contract-custom/' + id + '/codes', { headers: { 'Accept': 'application/json' } })
                    .then(function(res) { return res.json(); })
                    .then(function(json) {
                        if (!json || !json.success) {
                            alert(json.message || 'Failed to fetch codes');
                            return;
                        }
                        document.getElementById('mm_contract_id').value = id;
                        document.getElementById('mm_mm_code').value = json.data.mm_code || '';
                        document.getElementById('mm_oracle_code').value = json.data.oracle_code || '';
                        // show modal (Bootstrap 5)
                        var modalEl = document.getElementById('mmOracleModal');
                        var bsModal = new bootstrap.Modal(modalEl);
                        bsModal.show();
                    }).catch(function() { alert('Failed to fetch codes'); });
            });
        });

        document.getElementById('mm_save_btn')?.addEventListener('click', function() {
            var id = document.getElementById('mm_contract_id').value;
            var mm = document.getElementById('mm_mm_code').value;
            var oracle = document.getElementById('mm_oracle_code').value;
            if (!id) return;
            var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch(APP_URL + '/contracts/approval/contract-custom/' + id + '/codes', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ mm_code: mm, oracle_code: oracle })
            }).then(function(res) { return res.json(); })
              .then(function(json) {
                if (json && json.success) {
                    // close modal and reload to reflect status icon changes
                    var modalEl = document.getElementById('mmOracleModal');
                    var bsModal = bootstrap.Modal.getInstance(modalEl);
                    if (bsModal) bsModal.hide();
                    location.reload();
                } else {
                    alert(json.message || 'Failed to save codes');
                }
              }).catch(function() { alert('Failed to save codes'); });
        });
    });
</script>

<!-- MM/Oracle Codes Modal -->
<div class="modal fade" id="mmOracleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">MM Code & Oracle Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="mm_contract_id" value="">
        <div class="mb-3">
          <label class="form-label">MM Code</label>
          <input class="form-control" id="mm_mm_code" />
        </div>
        <div class="mb-3">
          <label class="form-label">Oracle Code</label>
          <input class="form-control" id="mm_oracle_code" />
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="mm_save_btn">Save</button>
      </div>
    </div>
  </div>
</div>
