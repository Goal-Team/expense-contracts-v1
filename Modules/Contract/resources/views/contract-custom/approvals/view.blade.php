@extends('layouts/layoutMaster')
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

@endsection

@section('title', 'Approval Step - Contract #' . ($contract->id ?? ''))

@section('styles')
<style>
    .approval-iframe { width:100%; height:420px; border:1px solid #ddd; border-radius:4px; }
    .comments-box { min-height:120px; max-height:300px; overflow:auto; background:#f8f9fa; padding:10px; border-radius:4px; border:1px solid #e9ecef; }
</style>
@endsection

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ url('/contracts/approval/contract-custom/'.$contract->id.'?tab=approvals') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Approvals</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- For Approver2 or Signatory show compact overview summary at top --}}
            @if((isset($isApproverLevel2) && $isApproverLevel2) || (isset($isSignatory) && $isSignatory))
                <div class="card mb-3">
                    <div class="card-header"><strong>Overview Summary</strong></div>
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <div><strong>Contract:</strong> {{ $contract->contract_name_decrypted ?? $contract->contract_name }}</div>
                            <div class="small text-muted">ID: #{{ $contract->id }}</div>
                        </div>
                        <div class="text-end">
                            <div><strong>Packages:</strong> {{ $overviewSummary['packages_count'] ?? 0 }}</div>
                            <div><strong>NET TOTAL:</strong> ₹{{ number_format($overviewSummary['net_total'] ?? 0,2) }}</div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Approval Step</strong>
                        <div class="small text-muted">Contract #{{ $contract->id }} — {{ $contract->contract_name_decrypted ?? $contract->contract_name ?? '—' }}</div>
                    </div>
                    <div class="text-end">
                        <div class="small">Approver:</div>
                        <strong>@php
                            if (!empty($username)) { echo e($username); }
                            else if (function_exists('decryptString') && !empty($approval->username)) {
                                try {
                                    $ud = json_decode(decryptString($approval->username, 'username'), true);
                                    echo e($ud['name'] ?? $ud['email'] ?? $approval->username);
                                } catch (\Throwable $e) {
                                    echo e($approval->username);
                                }
                            } else {
                                echo e($approval->username ?? '—');
                            }
                        @endphp</strong>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table table-borderless approval-meta mb-3">
                        <tbody>
                            <tr><th>Order</th><td>{{ $approval->orderval ?? '—' }}</td></tr>
                            <tr><th>Approval Status</th><td>{{ $approval_status ?? ($approval->approval_status ?? '—') }}</td></tr>
                            <tr><th>Current Step Status</th><td>{{ $approval->status_decrypted ?? $approval->status ?? '-' }}</td></tr>
                        </tbody>
                    </table>

                    <div class="mb-3">
                        <label class="form-label"><strong>Uploaded Contract</strong></label>
                        @if(!empty($attachmentUrl))
                            <iframe class="approval-iframe" src="{{ $attachmentUrl }}" sandbox></iframe>
                        @else
                            <div class="text-muted">No file attached.</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>Previous Comments</strong></label>
                        <div class="comments-box">{!! nl2br(e($approval->next_action_description ?? '-')) !!}</div>
                    </div>

                    @if($isCurrentApprover)
                        <form method="POST" action="{{ url('/contracts/approval/contract-custom/'.$contract->id.'/approval/'.$approval->id.'/respond') }}">
                            @csrf
                            <div class="mb-3">
                                <label>Your Comments</label>
                                <textarea name="comments" class="form-control" rows="4" maxlength="2000"></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="action" value="approve" class="btn btn-success"><i class="fas fa-check"></i> Approve</button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Reject and return to owner?')"><i class="fas fa-times"></i> Reject</button>
                                <a href="{{ url('/contracts/approval/contract-custom/'.$contract->id.'?tab=approvals') }}" class="btn btn-outline-secondary ms-auto">Back</a>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-info">Only the active approver may take action on this step.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><strong>Approval Details</strong></div>
                <div class="card-body">
                    <p><strong>Step ID:</strong> {{ $approval->id }}</p>
                    <p><strong>Flag:</strong> {{ $approval->flag == 1 ? 'Active/Pending' : 'Completed' }}</p>
                    <p><strong>Created By:</strong>
                        @if(is_string($approval->created_by))
                            @php $cb = @json_decode($approval->created_by, true); @endphp
                            {{ $cb['name'] ?? $cb['email'] ?? $approval->created_by }}
                        @else
                            {{ $approval->created_by ?? '-' }}
                        @endif
                    </p>
                    <p><strong>Updated On:</strong> {{ $approval->updated_on ? \Carbon\Carbon::parse($approval->updated_on)->format('d M Y, h:i A') : ($approval->updated_at ? $approval->updated_at->format('d M Y, h:i A') : '-') }}</p>
                    <p><strong>Updated By:</strong>
                        @if(!empty($approval->updated_by))
                            @php
                                try {
                                    $ub = function_exists('decryptString') ? @json_decode(decryptString($approval->updated_by, 'updated_by'), true) : @json_decode($approval->updated_by, true);
                                } catch (\Throwable $e) { $ub = null; }
                            {{ $ub['name'] ?? $ub['email'] ?? $approval->updated_by }}
                        @else
                            -
                        @endif
                    </p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><strong>Notify Owner</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ url('/contracts/approval/contract-custom/'.$contract->id.'/approval/notify') }}">@csrf
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-bell"></i> Notify Owner</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><strong>Quick Links</strong></div>
                <div class="card-body">
                    <a href="{{ url('/contracts/'.$contract->id) }}" class="d-block mb-2"><i class="fas fa-eye"></i> View Contract</a>
                    <a href="{{ url('/contracts/approval/contract-custom/'.$contract->id.'?tab=approvals') }}" class="d-block"><i class="fas fa-list"></i> Back to Approvals List</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection