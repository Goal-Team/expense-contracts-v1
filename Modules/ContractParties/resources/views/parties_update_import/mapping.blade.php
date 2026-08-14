@extends('layouts.layoutMaster')

@section('title', 'Map Columns to Party Fields')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
<link href="{{ url('/') }}/assets/css/custom.css" rel="stylesheet" />
@endsection

@section('page-script')
<script type="module">
$(document).ready(function() {
    $('.select2-mapping').select2({
        placeholder: 'Select field to map',
        allowClear: true
    });
});
</script>
@endsection

@section('content')
<style>
    .preview-table {
        font-size: 13px;
    }
    .preview-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .mapping-section {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
    }
    .mapping-row {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        padding: 10px;
        background-color: #fff;
        border-radius: 5px;
    }
    .mapping-row:last-child {
        margin-bottom: 0;
    }
    .column-name {
        font-weight: 600;
        min-width: 200px;
        color: #333;
    }
    .arrow-icon {
        margin: 0 15px;
        color: #6c757d;
    }
    .sample-data {
        font-size: 12px;
        color: #6c757d;
        margin-top: 5px;
    }
</style>

<div class="container shadow min-vh-100 py-2">
    <div class="container network_wrapper col-sm p-2">

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="ti ti-table me-1"></i> Map Excel Columns to Party Fields
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="ti ti-info-circle me-1"></i>
                    <strong>File uploaded successfully!</strong> Found {{ count($columns) }} columns and {{ number_format($totalRows) }} rows.
                    Please map each column to the corresponding party field.
                </div>

                <h6 class="mb-3">Preview (first {{ count($sampleData) }} rows):</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm preview-table">
                        <thead>
                            <tr>
                                @foreach($columns as $col)
                                <th>{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sampleData as $row)
                            <tr>
                                @foreach($columns as $idx => $col)
                                <td>{{ $row[$idx] ?? '' }}</td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <form id="mappingForm" action="{{ route('parties.parties_update_import_preview') }}" method="POST">
                    @csrf
                    <input type="hidden" name="batch_id" value="{{ $batchId }}">

                    <div class="mapping-section">
                        <h6 class="mb-3"><i class="ti ti-settings me-1"></i> Column Mapping</h6>

                        @foreach($columns as $idx => $col)
                        <div class="mapping-row">
                            <div class="column-name">
                                <span class="badge bg-primary me-2">{{ $idx + 1 }}</span>
                                {{ $col }}
                                @if($idx === 0)
                                <span class="badge bg-success ms-2">Party Name (Required)</span>
                                @endif
                            </div>
                            <div class="arrow-icon">
                                <i class="ti ti-arrow-right"></i>
                            </div>
                            <div class="flex-grow-1">
                                @if($idx === 0)
                                <input type="text" class="form-control" value="Used for matching only (not updated)" disabled>
                                @else
                                <select name="mapping[{{ $idx }}]" class="form-select select2-mapping">
                                    <option value="">-- Skip this column --</option>
                                    @foreach($availableFields as $fieldKey => $fieldLabel)
                                    <option value="{{ $fieldKey }}">{{ $fieldLabel }}</option>
                                    @endforeach
                                </select>
                                @if(isset($sampleData[0][$idx]) && !empty($sampleData[0][$idx]))
                                <div class="sample-data">Sample: {{ Str::limit($sampleData[0][$idx], 50) }}</div>
                                @endif
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary waves-effect waves-light">
                            <i class="ti ti-eye me-1"></i> Preview Update
                        </button>
                        <a href="{{ route('parties.parties_update_import_view') }}" class="btn btn-label-secondary waves-effect ms-2">
                            <i class="ti ti-arrow-left me-1"></i> Upload Different File
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
