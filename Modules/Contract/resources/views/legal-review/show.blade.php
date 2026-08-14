@extends('layouts/layoutMaster')
@section('title', 'Legal Advice')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Contract Information</h5>
          <span class="badge bg-label-primary">Contract #{{ $contract->contract_unique_id ?? $contract->id }}</span>
        </div>
        <div class="card-body">
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <p class="mb-1"><strong>Contract Name:</strong></p>
              <p class="mb-0">{{ decryptString($contract->contract_name, 'contract_name') }}</p>
            </div>
            <div class="col-md-6">
              <p class="mb-1"><strong>Status:</strong></p>
              <p class="mb-0">{{ $contract->contract_status }}{{ !empty($contract->substatus) ? ' / ' . $contract->substatus : '' }}</p>
            </div>
            <div class="col-md-6">
              <p class="mb-1"><strong>Type:</strong></p>
              <p class="mb-0">{{ $contract->contract_type }}</p>
            </div>
            <div class="col-md-6">
              <p class="mb-1"><strong>Current Legal Status:</strong></p>
              <span class="badge {{ $legalRequest['status'] === 'responded' ? 'bg-label-success' : ($legalRequest['status'] === 'contacted' ? 'bg-label-warning' : 'bg-label-secondary') }}">{{ ucfirst(str_replace('_', ' ', $legalRequest['status'])) }}</span>
            </div>
          </div>

          @if($attachmentUrl)
            <a href="{{ $attachmentUrl }}" target="_blank" class="btn btn-outline-primary">
              Open Contract Attachment
            </a>
          @else
            <div class="alert alert-warning mb-0">No contract attachment is available.</div>
          @endif
        </div>
      </div>

      <div class="card mt-4">
        <div class="card-header">
          <h5 class="mb-0">Requester Information</h5>
        </div>
        <div class="card-body">
          <p class="mb-2"><strong>Requested By:</strong> {{ $legalRequest['requested_by_name'] ?: '-' }}</p>
          <p class="mb-2"><strong>Requester Email:</strong> {{ $legalRequest['requested_by_email'] ?: '-' }}</p>
          <p class="mb-2"><strong>Requested At:</strong> {{ $legalRequest['requested_at'] ? date('d M Y H:i', strtotime($legalRequest['requested_at'])) : '-' }}</p>
          <div class="mb-0">
            <strong>Requester Comment:</strong>
            <div class="border rounded p-3 mt-2 bg-label-secondary">{{ $legalRequest['request_comment'] ?: '-' }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Legal Advice Response</h5>
        </div>
        <div class="card-body">
          <p class="mb-2"><strong>Advisor:</strong> {{ $advisor->name ?? '-' }}</p>
          <p class="mb-3"><strong>Email:</strong> {{ $advisor->email_id ?? $contract->legal_advisor_email }}</p>

          @if(!empty($legalRequest['response_comment']))
            <div class="alert alert-success">
              <strong>Latest Advice:</strong><br>
              {{ $legalRequest['response_comment'] }}
              <hr class="my-2">
              <small>
                By {{ $legalRequest['responded_by_name'] ?: 'Legal Advisor' }}
                @if(!empty($legalRequest['responded_at']))
                  on {{ date('d M Y H:i', strtotime($legalRequest['responded_at'])) }}
                @endif
              </small>
            </div>
          @endif

          <form method="POST" action="{{ route('contracts.legal.respond', ['id' => $contract->id]) }}">
            @csrf
            <div class="mb-3">
              <label class="form-label" for="response_comment">Response Comment</label>
              <textarea class="form-control" id="response_comment" name="response_comment" rows="6" placeholder="Share your legal advice.">{{ old('response_comment', $legalRequest['response_comment']) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Advice</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
