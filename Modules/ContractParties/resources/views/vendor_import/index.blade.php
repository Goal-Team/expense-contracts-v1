@extends('layouts.layoutMaster')

@section('title', 'Vendor Import & Validation')

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
</style>

<div class="container shadow min-vh-100 py-2">
    <div class="container network_wrapper col-sm p-2">

        @if (session('success'))
        <div class="alert alert-success">{!! session('success') !!}</div>
        @endif
        @if (session('error'))
        <div class="alert alert-danger">{!! session('error') !!}</div>
        @endif

        @if ($preview)
            @include('parties::vendor_import.preview', ['matched' => $matched, 'unmatched' => $unmatched, 'batchId' => $batchId])
        @else
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Vendor Import & Validation</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Expected Excel Columns:</strong>
                    <code>vendor_code</code> | <code>active_vendor_code</code> | <code>vendor_name</code> | <code>pan</code> | <code>s_no</code>
                </div>

                {{-- Upload Form --}}
                <div id="uploadSection">
                    <form id="vendorUploadForm" action="{{ route('parties.vendor_import_upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="vendor_file" class="form-label">Choose Excel File</label>
                            <input class="form-control" type="file" id="vendor_file" name="file" accept=".xlsx,.xls" required>
                        </div>
                        <button type="submit" class="btn btn-primary waves-effect waves-light" id="uploadBtn">
                            <i class="ti ti-upload me-1"></i> Upload & Match
                        </button>
                        <a href="{{ route('parties.parties') }}" class="btn btn-label-secondary waves-effect ms-2">Back to Parties</a>
                    </form>
                </div>

                {{-- Progress Section (hidden initially) --}}
                <div id="progressSection" style="display:none;">
                    <h6 class="mb-3" id="progressTitle">Processing...</h6>
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" id="progressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <p class="text-muted" id="progressDetail">Uploading file and parsing data...</p>
                    <p class="text-muted" id="progressStats" style="display:none;">
                        Matched: <span id="statMatched" class="badge bg-success">0</span>
                        Unmatched: <span id="statUnmatched" class="badge bg-danger">0</span>
                    </p>
                </div>

            </div>
        </div>
        @endif

    </div>
</div>

@if (!$preview)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const uploadUrl = "{{ route('parties.vendor_import_upload') }}";
    const chunkUrl = "{{ route('parties.vendor_import_process_chunk') }}";
    const previewBase = "{{ route('parties.vendor_import_view') }}";
    const csrfToken = "{{ csrf_token() }}";
    const CHUNK_SIZE = 500;

    const uploadForm = document.getElementById('vendorUploadForm');
    const uploadSection = document.getElementById('uploadSection');
    const progressSection = document.getElementById('progressSection');
    const progressTitle = document.getElementById('progressTitle');
    const progressDetail = document.getElementById('progressDetail');
    const progressStats = document.getElementById('progressStats');
    const progressBar = document.getElementById('progressBar');
    const statMatched = document.getElementById('statMatched');
    const statUnmatched = document.getElementById('statUnmatched');

    if (!uploadForm) return;

    uploadForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const fileInput = document.getElementById('vendor_file');
        if (!fileInput || !fileInput.files.length) return;

        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('_token', csrfToken);

        uploadSection.style.display = 'none';
        progressSection.style.display = 'block';
        progressTitle.textContent = 'Step 1/2: Uploading & parsing file...';
        progressDetail.textContent = 'This may take a moment for large files...';
        setProgress(5);

        try {
            const resp = await request(uploadUrl, {
                method: 'POST',
                body: formData,
                timeoutMs: 300000,
            });

            if (!resp.status) {
                showError(resp.message || 'Upload failed.');
                return;
            }

            setProgress(15);
            progressTitle.textContent = 'Step 2/2: Matching vendors (' + Number(resp.total_rows).toLocaleString() + ' rows)...';
            progressDetail.textContent = 'Processing in chunks of ' + CHUNK_SIZE + '...';
            progressStats.style.display = 'block';

            await processChunks(resp.batch_id, resp.total_rows, 0);
        } catch (err) {
            showError(err.message || 'Upload failed. The file may be too large or the server timed out.');
        }
    });

    async function processChunks(batchId, totalRows, offset) {
        const resp = await request(chunkUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ batch_id: batchId, offset: offset, limit: CHUNK_SIZE }),
            timeoutMs: 60000,
        });

        if (!resp.status) {
            showError(resp.message || 'Processing failed.');
            return;
        }

        const pct = 15 + Math.round((resp.processed / totalRows) * 85);
        setProgress(pct);
        progressDetail.textContent = 'Processed ' + Number(resp.processed).toLocaleString() + ' of ' + Number(totalRows).toLocaleString() + ' rows...';
        statMatched.textContent = resp.matched_count || 0;
        statUnmatched.textContent = resp.unmatched_count || 0;

        if (resp.done) {
            progressBar.classList.remove('progress-bar-animated');
            setProgress(100, '100% - Complete!');
            progressTitle.textContent = 'Processing complete!';
            progressDetail.textContent = 'Redirecting to results...';
            setTimeout(function () {
                window.location.href = previewBase + '?batch=' + encodeURIComponent(batchId);
            }, 1000);
            return;
        }

        await processChunks(batchId, totalRows, resp.processed);
    }

    function setProgress(value, label) {
        progressBar.style.width = value + '%';
        progressBar.textContent = label || (value + '%');
        progressBar.setAttribute('aria-valuenow', String(value));
    }

    async function request(url, options) {
        const controller = new AbortController();
        const timeout = setTimeout(function () { controller.abort(); }, options.timeoutMs || 60000);
        try {
            const res = await fetch(url, {
                method: options.method || 'GET',
                headers: Object.assign({
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }, options.headers || {}),
                body: options.body,
                signal: controller.signal,
            });
            const data = await res.json().catch(function () { return {}; });
            if (!res.ok) {
                throw new Error(data.message || 'Request failed');
            }
            return data;
        } finally {
            clearTimeout(timeout);
        }
    }

    function showError(message) {
        progressSection.style.display = 'none';
        uploadSection.style.display = 'block';

        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible';
        alert.innerHTML = escapeHtml(message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        uploadSection.prepend(alert);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = String(str || '');
        return div.innerHTML;
    }
});
</script>
@endif
@endsection
