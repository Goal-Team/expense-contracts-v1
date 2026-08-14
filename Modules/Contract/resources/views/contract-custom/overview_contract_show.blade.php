@php
    $isSign = $isSignatory ?? false;

@endphp

<div class="row gy-3">
    <div class="col-12">
        {{-- Contract header summary common --}}

        {{-- Credit cell block shown only when renewal (controller sets showCreditCellInputs) --}}
        @if($isCreditUser)
            <div class="card mb-3">
                <div class="card-header bg-warning"><h5 class="mb-0 text-white">Credit Cell Inputs Required</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ url('/contracts/approval/contract-custom/'.$contract->id.'/approve') }}" class="mt-3">
                        @csrf
                        <div class="mb-3">
                            <label>Current Outstanding *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="current_outstanding" class="form-control" step="0.01" min="0" value="{{ old('current_outstanding', $creditCellData['current_outstanding'] ?? '') }}" required>
                                <input type="hidden" name="current_approval" value="{{ old('current_approval', $currentEntry->id ?? 0) }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Recommended Allowable Credit Limit *</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="recommended_credit_limit" class="form-control" step="0.01" min="0" value="{{ old('recommended_credit_limit', $creditCellData['recommended_credit_limit'] ?? '') }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Recommendation and Comments to Finance *</label>
                            <textarea name="recommendation_comments" class="form-control" rows="3" required>{{ old('recommendation_comments', $creditCellData['recommendation_comments'] ?? '') }}</textarea>
                        </div>
                        <div class="d-flex gap-2"><button type="submit" class="btn btn-success">Save</button></div>
                    </form>
                </div>
            </div>
        @endif
        @if((empty($canViewForm) || $isSign) && !$isCreditUser)
        <div class="card mb-3">
            <div class="card-header"><strong>Uploaded Contract File</strong></div>
            <div class="card-body">
                @if(!empty($contract->contract_attachment))
                    @if(isset($contract->contract_attachment_filename))
                        @if(fileStorageType() != 'Local')
                            @php 
                                $getFinalUrl = get_google_drive_doc_link($contract->contract_attachment_filename,$contract->contract_attachment, 'edit', 'openfile');
                                $getFinalUrlNew = get_google_drive_doc_link($contract->contract_attachment_filename,$contract->contract_attachment, 'edit', 'openfile');
                            @endphp
                            <div class="alert alert-danger mx-2">If below document Not Loaded Please <a href="{{$getFinalUrlNew}}" target="blank">Click Here</a>. Because of some security reasons its not loaded.</div>
                            <iframe src="{{ $getFinalUrl }}" height="500" width="100%"></iframe>
                        @else
                            @include('contract::contract.viewContractDocument')
                        @endif   
                    @endif
                @else
                    <div class="text-muted">No contract file uploaded.</div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>