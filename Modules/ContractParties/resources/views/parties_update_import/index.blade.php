@extends('layouts.layoutMaster')

@section('title', 'Update Contract Parties via Excel')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
<link href="{{ url('/') }}/assets/css/custom.css" rel="stylesheet" />
@endsection

@section('page-script')
@endsection

@section('content')
<style>
    .files input[type="file"] {
        outline: 2px dashed #dbdade;
        outline-offset: -10px;
        -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
        transition: outline-offset .15s ease-in-out, background-color .15s linear;
        padding: 60px 0px 40px 25%;
        text-align: center !important;
        margin: 0;
        width: 100% !important;
    }
    .files input[type="file"]:focus { outline: 2px dashed #dbdade; outline-offset: -10px; }
    .files { position: relative; }
    .info-box {
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
        padding: 15px;
        margin-bottom: 20px;
    }
    .info-box ul {
        margin-bottom: 0;
        padding-left: 20px;
    }
</style>

<div class="container shadow min-vh-100 py-2">
    <div class="container network_wrapper col-sm p-2">

        @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
        @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {!! session('error') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Update Contract Parties via Excel</h5>
            </div>
            <div class="card-body">
                <div class="info-box">
                    <h6><i class="ti ti-info-circle me-1"></i> Instructions</h6>
                    <ul>
                        <li><strong>First column must be "Party Name"</strong> - This will be used to match existing parties</li>
                        <li>Remaining columns can be mapped to any party field (Vendor Code, Contact, Email, etc.)</li>
                        <li>Only existing parties will be updated - no new parties will be created</li>
                        <li>Supported file format: .xlsx (Excel)</li>
                    </ul>
                </div>

                <form id="updateImportForm" action="{{ route('parties.parties_update_import_upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Choose Excel File</label>
                        <input class="form-control" type="file" id="excel_file" name="file" accept=".xlsx,.xls" required>
                        <div class="form-text">Upload an Excel file with party names in the first column</div>
                    </div>
                    <button type="submit" class="btn btn-primary waves-effect waves-light" id="uploadBtn">
                        <i class="ti ti-upload me-1"></i> Upload & Map Columns
                    </button>
                    <a href="{{ route('parties.parties') }}" class="btn btn-label-secondary waves-effect ms-2">
                        <i class="ti ti-arrow-left me-1"></i> Back to Parties
                    </a>
                </form>

                @if ($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
