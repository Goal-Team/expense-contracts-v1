@extends('layouts/layoutMaster')
@section('title', 'Legal Advisors')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if(session('message'))
    <div class="alert {{ session('alert-class', 'alert-success') }}">{!! session('message') !!}</div>
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

  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0">Create Legal Advisor</h5>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('legal-advisors.store') }}">
        @csrf
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email_id" class="form-control" value="{{ old('email_id') }}" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Legal Name</label>
            <input type="text" name="legal_name" class="form-control" value="{{ old('legal_name') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">Designation</label>
            <input type="text" name="designation" class="form-control" value="{{ old('designation') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">Contact</label>
            <input type="text" name="contact" class="form-control" value="{{ old('contact') }}" maxlength="50">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary">Create</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Legal Advisors</h5>
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Legal Name</th>
            <th>Designation</th>
            <th>Contact</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($legalAdvisors as $advisor)
            <tr>
              <td>{{ $advisor->name }}</td>
              <td>{{ $advisor->email_id }}</td>
              <td>{{ $advisor->legal_name }}</td>
              <td>{{ $advisor->designation }}</td>
              <td>{{ $advisor->contact }}</td>
              <td>
                <span class="badge {{ (int)$advisor->status === 1 ? 'bg-label-success' : 'bg-label-secondary' }}">
                  {{ (int)$advisor->status === 1 ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td>
                <details>
                  <summary class="cursor-pointer">Edit</summary>
                  <form method="POST" action="{{ route('legal-advisors.update', ['id' => $advisor->id]) }}" class="mt-2">
                    @csrf
                    @method('PUT')
                    <div class="row g-2">
                      <div class="col-12"><input type="text" name="name" class="form-control" value="{{ $advisor->name }}" required></div>
                      <div class="col-12"><input type="email" name="email_id" class="form-control" value="{{ $advisor->email_id }}" required></div>
                      <div class="col-12"><input type="text" name="legal_name" class="form-control" value="{{ $advisor->legal_name }}" placeholder="Legal Name"></div>
                      <div class="col-12"><input type="text" name="designation" class="form-control" value="{{ $advisor->designation }}" placeholder="Designation"></div>
                      <div class="col-12"><input type="text" name="contact" class="form-control" value="{{ $advisor->contact }}" placeholder="Contact"></div>
                      <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                      </div>
                    </div>
                  </form>
                </details>
                <form method="POST" action="{{ route('legal-advisors.status', ['id' => $advisor->id]) }}" class="mt-2">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="btn btn-sm btn-outline-secondary">{{ (int)$advisor->status === 1 ? 'Deactivate' : 'Activate' }}</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center">No legal advisors found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-body">
      {{ $legalAdvisors->links() }}
    </div>
  </div>
</div>
@endsection
