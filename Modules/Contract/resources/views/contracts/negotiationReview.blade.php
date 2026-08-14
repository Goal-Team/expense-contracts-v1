@extends('layouts/blankLayout')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Contract Review & Negotiation</h3>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('success') || !empty($successMessage ?? null))
                        <div class="alert alert-success">
                            <h4>{{ session('success') ?? $successMessage }}</h4>
                            <p>Thank you for your response. The contract will now proceed according to the configured approval flow.</p>
                        </div>
                    @elseif (isset($tokenExpired) && $tokenExpired)
                        <div class="alert alert-danger">
                            <strong>Access Expired</strong><br>
                            This access link has expired. Please contact the contract owner for a new link.
                        </div>
                    @else
                        <!-- <div class="mb-4">
                            <h5>Contract Details</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <td><strong>Contract Name:</strong></td>
                                    <td>{{ $contractName ?? $contract->contract_unique_id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Contract ID:</strong></td>
                                    <td>{{ $contract->contract_unique_id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>{{ $contract->contract_status }}</td>
                                </tr>
                            </table>
                        </div> -->

                        <div class="mb-5">
                            <h5>Current Contract Attachment</h5>
                            @if ($attachmentUrl)
                                <p>
                                    <a href="{{ $attachmentUrl }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View Attachment
                                    </a>
                                </p>
                            @else
                                <p class="text-muted">No attachment available</p>
                            @endif
                        </div>

                        <form id="negotiationForm" action="{{ route('contract.negotiationRespond', $accessSlug) }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            {{-- External "Upload Modified Contract" option — temporarily hidden, to be re-enabled in future. Change @if(false) to @if(true) to restore. --}}
                            @if(false)
                            <div class="mb-4">
                                <h5>Upload Modified Contract (Optional)</h5>
                                <p class="text-muted small">If you have suggested changes, upload the modified contract file here:</p>
                                <div class="form-group">
                                    <input type="file" id="attachmentFile" name="attachment_file" class="form-control" accept=".pdf,.doc,.docx,.xlsx,.xls">
                                    <small class="form-text text-muted">Supported formats: PDF, DOC, DOCX, XLSX, XLS</small>
                                </div>
                            </div>
                            @endif

                            <div class="mb-4">
                                <h5>Comments (Optional)</h5>
                                <div class="form-group">
                                    <textarea name="comments" id="comments" class="form-control" rows="4" placeholder="Add any comments or notes about your review..."></textarea>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h5>Your Decision</h5>
                                <div class="form-group">
                                    <div class="form-check mb-2">
                                        <input type="radio" name="action" id="actionAccept" class="form-check-input" value="accept" required>
                                        <label class="form-check-label" for="actionAccept">
                                            <strong>Accept</strong> - Approve this contract for the next stage
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="action" id="actionReject" class="form-check-input" value="reject" required>
                                        <label class="form-check-label" for="actionReject">
                                            <strong>Reject</strong> - Return this contract for further revision
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-success btn-lg" id="submitBtn" disabled>
                                    <i class="fas fa-check"></i> Submit Response
                                </button>
                                <button type="reset" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-redo"></i> Clear
                                </button>
                            </div>
                        </form>

                        <hr>
                        <p class="text-muted small">
                            <strong>Note:</strong> This access link will expire on <strong>{{ \Carbon\Carbon::parse($expiryDate)->format('M d, Y') }}</strong>.
                            Please ensure your response is submitted before the expiration date.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('negotiationForm');
    const submitBtn = document.getElementById('submitBtn');
    const actionRadios = document.querySelectorAll('input[name="action"]');

    function updateSubmitButton() {
        const isActionSelected = Array.from(actionRadios).some(radio => radio.checked);
        submitBtn.disabled = !isActionSelected;
    }

    actionRadios.forEach(radio => {
        radio.addEventListener('change', updateSubmitButton);
    });

    form.addEventListener('submit', function(e) {
        const isActionSelected = Array.from(actionRadios).some(radio => radio.checked);
        if (!isActionSelected) {
            e.preventDefault();
            alert('Please select Accept or Reject');
        }
    });
});
</script>

<style>
    .card-header {
        padding: 1.5rem;
    }
    .table {
        margin-bottom: 0;
    }
    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1.1rem;
    }
</style>
@endsection
