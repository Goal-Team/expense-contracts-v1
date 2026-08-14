@extends('layouts/layoutMaster')
@section('title', 'Approval Entries Backfill')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0">Missed Approval Entries Backfill</h5>
        <small class="text-muted">Executed contracts without approval entries</small>
      </div>
      <form id="insertAllBackfillForm" method="POST" action="{{ route('contracts.approval.backfill.insert-all') }}" style="display:none;">
        @csrf
        <input type="hidden" name="preview_token" id="insertAllPreviewToken" value="" />
      </form>
      <button type="button" class="btn btn-danger" onclick="previewAllContracts();">Preview All Missing</button>
    </div>

    <div class="card-body">
      @if(session('message'))
        <div class="alert {{ session('alert-class', 'alert-info') }}" role="alert">
          {{ session('message') }}
        </div>
      @endif

      @php $summary = session('backfill_summary'); @endphp
      @if(!empty($summary) && !empty($summary['errors']))
        <div class="alert alert-warning" role="alert">
          <strong>Errors:</strong>
          <ul class="mb-0 mt-2">
            @foreach(array_slice($summary['errors'], 0, 10) as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form id="selectedBackfillForm" method="POST" action="{{ route('contracts.approval.backfill.insert-selected') }}" style="display:none;">
        @csrf
        <input type="hidden" name="preview_token" id="selectedPreviewToken" value="" />
      </form>

      <form method="GET" action="{{ route('contracts.approval.backfill.index') }}" class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label">Location</label>
          <select class="form-select" name="location_id">
            <option value="">All</option>
            @foreach(($locationOptions ?? []) as $option)
              <option value="{{ $option['id'] }}" {{ (int) ($locationId ?? 0) === (int) $option['id'] ? 'selected' : '' }}>{{ $option['name'] }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-8 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="{{ route('contracts.approval.backfill.index') }}" class="btn btn-label-secondary">Reset</a>
        </div>
      </form>

      <div class="mb-3 d-flex justify-content-between align-items-center">
        <div class="text-muted">Total missing contracts: {{ $rows->count() }}</div>
        <button type="button" class="btn btn-primary" onclick="previewSelectedContracts();">Preview Selected</button>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead>
              <tr>
                <th style="width: 40px;">
                  <input type="checkbox" id="selectAllRows" />
                </th>
                <th>S.No</th>
                <th>Contract ID</th>
                <th>Contract Type</th>
                <th>Value</th>
                <th>Location</th>
                <th>Approval Entries (Email ID)</th>
                <th style="width: 210px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($rows as $row)
                <tr>
                  <td>
                    <input type="checkbox" class="row-checkbox" name="contract_ids[]" value="{{ $row['id'] }}" />
                  </td>
                  <td>{{ $row['s_no'] }}</td>
                  <td>{{ $row['contract_id_display'] }}</td>
                  <td>{{ $row['contract_type'] }}</td>
                  <td>{{ $row['value'] }}</td>
                  <td>{{ $row['location'] }}</td>
                  <td>{{ $row['approval_entries'] }}</td>
                  <td>
                    <form id="insertOneForm_{{ $row['id'] }}" method="POST" action="{{ route('contracts.approval.backfill.insert-one', ['contractId' => $row['id']]) }}" class="m-0">
                      @csrf
                      <input type="hidden" name="preview_token" id="insertOnePreviewToken_{{ $row['id'] }}" value="" />
                      <button type="button" class="btn btn-sm btn-outline-primary preview-one-btn" data-contract-id="{{ $row['id'] }}">Preview & Insert</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center py-4">No executed contracts are missing approval entries.</td>
                </tr>
              @endforelse
            </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="backfillPreviewModal" tabindex="-1" aria-labelledby="backfillPreviewLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="backfillPreviewLabel">Backfill DOA Match Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="previewSummary" class="mb-3"></div>
        <div id="previewWarnings"></div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead>
              <tr>
                <th style="width:40px;">
                  <input type="checkbox" id="previewSelectAllRows" />
                </th>
                <th>Contract</th>
                <th>Rule ID</th>
                <th>Rule Name</th>
                <th>Approval Type</th>
                <th>Fallback</th>
                <th>Used Values</th>
                <th>Warnings / Errors</th>
              </tr>
            </thead>
            <tbody id="previewRows"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Back</button>
        <button type="button" class="btn btn-primary" id="confirmBackfillInsertBtn">Confirm Insert</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    var selectAll = document.getElementById('selectAllRows');
    var checkboxes = document.querySelectorAll('.row-checkbox');

    if (selectAll) {
      selectAll.addEventListener('change', function () {
        checkboxes.forEach(function (checkbox) {
          checkbox.checked = selectAll.checked;
        });
      });
    }

    checkboxes.forEach(function (checkbox) {
      checkbox.addEventListener('change', function () {
        if (!selectAll) return;
        var allChecked = true;
        checkboxes.forEach(function (cb) {
          if (!cb.checked) allChecked = false;
        });
        selectAll.checked = allChecked;
      });
    });

    document.querySelectorAll('.preview-one-btn').forEach(function (button) {
      button.addEventListener('click', function () {
        var contractId = parseInt(button.getAttribute('data-contract-id') || '0', 10);
        if (!contractId) {
          alert('Invalid contract id.');
          return;
        }
        previewOneContract(contractId);
      });
    });
  })();

  var pendingInsert = {
    action: null,
    contractIds: [],
    previewToken: ''
  };

  function csrfToken() {
    return '{{ csrf_token() }}';
  }

  function previewOneContract(contractId) {
    var url = '{{ route("contracts.approval.backfill.preview-one", ["contractId" => "__ID__"]) }}'.replace('__ID__', String(contractId));
    requestPreview(url, {}, 'single', [contractId]);
  }

  function previewSelectedContracts() {
    var checked = document.querySelectorAll('.row-checkbox:checked');
    if (checked.length === 0) {
      alert('Please select at least one contract.');
      return;
    }

    var ids = Array.prototype.map.call(checked, function (checkbox) {
      return parseInt(checkbox.value, 10);
    }).filter(function (id) {
      return !!id;
    });

    requestPreview('{{ route("contracts.approval.backfill.preview-selected") }}', { contract_ids: ids }, 'selected', ids);
  }

  function previewAllContracts() {
    requestPreview('{{ route("contracts.approval.backfill.preview-all") }}', {}, 'all', []);
  }

  function requestPreview(url, payload, action, contractIds) {
    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload || {})
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data || !data.status) {
          alert((data && data.message) ? data.message : 'Failed to generate preview.');
          return;
        }

        var preview = data.data || {};
        if (!preview.items || preview.items.length === 0) {
          alert('No contracts found for preview.');
          return;
        }

        pendingInsert.action = action;
        pendingInsert.contractIds = contractIds;
        pendingInsert.previewToken = preview.preview_token || '';

        renderPreview(preview);
      })
      .catch(function () {
        alert('Failed to generate preview.');
      });
  }

  function renderPreview(preview) {
    var summary = preview.summary || {};
    var summaryHtml = '<div class="alert alert-info mb-0">'
      + 'Total: <strong>' + (summary.total || 0) + '</strong> '
      + '| OK: <strong>' + (summary.ok || 0) + '</strong> '
      + '| With Warnings: <strong>' + (summary.warnings || 0) + '</strong> '
      + '| Failed: <strong>' + (summary.failed || 0) + '</strong>'
      + '</div>';
    document.getElementById('previewSummary').innerHTML = summaryHtml;

    var rowsEl = document.getElementById('previewRows');
    rowsEl.innerHTML = '';

    (preview.items || []).forEach(function (item) {
      var contractId = parseInt(item.contract_id || '0', 10);
      var rule = item.selected_rule || {};
      var used = item.used_values || {};
      var warningList = [];
      (item.warnings || []).forEach(function (w) { warningList.push(String(w)); });
      (item.errors || []).forEach(function (e) { warningList.push(String(e)); });
      var shouldCheck = pendingInsert.contractIds.indexOf(contractId) !== -1;

      var usedValuesText = [
        'Location: ' + (used.location ?? '-'),
        'Department: ' + (used.department ?? '-'),
        'Category: ' + (used.category ?? '-'),
        'Contract Type: ' + (used.contract_type ?? '-'),
        'Contract Value: ' + (used.contract_value ?? '-')
      ].join('<br>');

      var tr = document.createElement('tr');
      tr.setAttribute('data-contract-id', String(contractId));
      tr.innerHTML = ''
        + '<td><input type="checkbox" class="preview-row-checkbox" value="' + contractId + '" ' + (shouldCheck ? 'checked' : '') + '></td>'
        + '<td>' + (item.contract_id_display || item.contract_id || '-') + '</td>'
        + '<td>' + (rule.id || 0) + '</td>'
        + '<td>' + (rule.approval_name || '-') + '</td>'
        + '<td>' + (rule.approval_type || '-') + '</td>'
        + '<td>' + ((rule.is_default_fallback ? 'Yes' : 'No')) + '</td>'
        + '<td>' + usedValuesText + '</td>'
        + '<td>' + (warningList.length ? warningList.join('<br>') : '-') + '</td>';
      rowsEl.appendChild(tr);
    });

    var previewSelectAll = document.getElementById('previewSelectAllRows');
    var previewChecks = rowsEl.querySelectorAll('.preview-row-checkbox');
    if (previewSelectAll) {
      var allCheckedInitially = previewChecks.length > 0;
      previewChecks.forEach(function (cb) {
        if (!cb.checked) {
          allCheckedInitially = false;
        }
      });
      previewSelectAll.checked = allCheckedInitially;
      previewSelectAll.onchange = function () {
        previewChecks.forEach(function (cb) {
          cb.checked = previewSelectAll.checked;
        });
      };
    }

    previewChecks.forEach(function (cb) {
      cb.addEventListener('change', function () {
        if (!previewSelectAll) return;
        var allChecked = true;
        previewChecks.forEach(function (el) {
          if (!el.checked) {
            allChecked = false;
          }
        });
        previewSelectAll.checked = allChecked;
      });
    });

    var modalEl = document.getElementById('backfillPreviewModal');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }

  document.getElementById('confirmBackfillInsertBtn').addEventListener('click', function () {
    if (!pendingInsert.previewToken) {
      alert('Missing preview token. Please preview again.');
      return;
    }

    var checkedPreviewRows = Array.prototype.map.call(
      document.querySelectorAll('.preview-row-checkbox:checked'),
      function (checkbox) {
        return parseInt(checkbox.value || '0', 10);
      }
    ).filter(function (id) {
      return !!id;
    });

    if (checkedPreviewRows.length === 0) {
      alert('Please keep at least one contract selected in preview.');
      return;
    }

    if (pendingInsert.action === 'single' && checkedPreviewRows.length === 1) {
      var contractId = checkedPreviewRows[0];
      var tokenInput = document.getElementById('insertOnePreviewToken_' + contractId);
      var form = document.getElementById('insertOneForm_' + contractId);
      if (!tokenInput || !form) {
        alert('Unable to submit insert form.');
        return;
      }
      tokenInput.value = pendingInsert.previewToken;
      form.submit();
      return;
    }

    var form = document.getElementById('selectedBackfillForm');
    Array.from(form.querySelectorAll('input[name="contract_ids[]"]')).forEach(function (input) {
      input.remove();
    });

    checkedPreviewRows.forEach(function (id) {
      var hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'contract_ids[]';
      hidden.value = id;
      form.appendChild(hidden);
    });

    document.getElementById('selectedPreviewToken').value = pendingInsert.previewToken;
    form.submit();
  });
</script>
@endsection
