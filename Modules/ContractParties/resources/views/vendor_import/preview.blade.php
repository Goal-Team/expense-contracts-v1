@php
    $matchBadges = [
        'exact'      => '<span class="badge bg-success">PAN + Name</span>',
        'pan_only'   => '<span class="badge bg-primary">PAN Only</span>',
        'name_only'  => '<span class="badge bg-warning">Name Only</span>',
        'fuzzy_name' => '<span class="badge bg-info">Fuzzy Name</span>',
    ];

    // Client-side pagination settings
    $perPage = 100;
    $matchedTotal = count($matched);
    $unmatchedTotal = count($unmatched);
    $matchedPages = max(1, ceil($matchedTotal / $perPage));
    $unmatchedPages = max(1, ceil($unmatchedTotal / $perPage));
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="card-title">
            Vendor Import Results
            <small class="text-muted">({{ number_format($matchedTotal + $unmatchedTotal) }} total rows)</small>
        </h5>
        <ul class="nav nav-tabs card-header-tabs" id="vendorImportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="matched-tab" data-bs-toggle="tab"
                    data-bs-target="#matched-panel" type="button" role="tab" aria-controls="matched-panel"
                    aria-selected="true">
                    Matched <span class="badge bg-label-success ms-1">{{ number_format($matchedTotal) }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="unmatched-tab" data-bs-toggle="tab"
                    data-bs-target="#unmatched-panel" type="button" role="tab" aria-controls="unmatched-panel"
                    aria-selected="false">
                    Unmatched <span class="badge bg-label-danger ms-1">{{ number_format($unmatchedTotal) }}</span>
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">

            {{-- Tab 1: Matched Vendors --}}
            <div class="tab-pane fade show active" id="matched-panel" role="tabpanel" aria-labelledby="matched-tab">
                @if ($matchedTotal > 0)
                <div class="mb-3 d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-success" id="validateSelectedBtn" disabled>
                        <i class="ti ti-check me-1"></i> Validate Selected (<span id="selectedCount">0</span>)
                    </button>
                    <button type="button" class="btn btn-outline-success" id="validateAllPageBtn">
                        <i class="ti ti-checks me-1"></i> Validate All on Page
                    </button>
                    <button type="button" class="btn btn-outline-success" id="validateAllViewingBtn">
                        <i class="ti ti-list-check me-1"></i> Validate All Viewing
                    </button>
                    <span class="text-muted ms-2" id="matchedPageInfo"></span>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label mb-1">Match Type</label>
                        <select id="matchTypeFilter" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="exact">PAN + Name</option>
                            <option value="pan_only">PAN Only</option>
                            <option value="name_only">Name Only</option>
                            <option value="fuzzy_name">Fuzzy Name</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Location</label>
                        <select id="locationFilter" class="form-select form-select-sm">
                            <option value="">All</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">Contracts Count</label>
                        <select id="contractCountFilter" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="0">0</option>
                            <option value="1">1</option>
                            <option value="2_5">2 to 5</option>
                            <option value="6_plus">6+</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-label-secondary btn-sm w-100" id="resetMatchedFiltersBtn">Reset</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm" id="matchedTable">
                        <thead>
                            <tr>
                                <th style="width:30px;"><input type="checkbox" id="selectAllMatched" class="form-check-input"></th>
                                <th>S.No</th>
                                <th>Vendor Code</th>
                                <th>Active V.Code</th>
                                <th>Vendor Name (Excel)</th>
                                <th>PAN (Excel)</th>
                                <th>Party Name (System)</th>
                                <th>PAN (System)</th>
                                <th>Match Type</th>
                                <th>Location</th>
                                <th>Contracts</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="matchedBody"></tbody>
                    </table>
                </div>
                {{-- Matched Pagination --}}
                <nav id="matchedPagination" class="mt-3">
                    <ul class="pagination pagination-sm flex-wrap" id="matchedPageLinks"></ul>
                </nav>
                @else
                <div class="alert alert-warning">No matched vendors found.</div>
                @endif
            </div>

            {{-- Tab 2: Unmatched Vendors --}}
            <div class="tab-pane fade" id="unmatched-panel" role="tabpanel" aria-labelledby="unmatched-tab">
                @if ($unmatchedTotal > 0)
                <div class="mb-3">
                    <form action="{{ route('parties.vendor_import_export') }}" method="POST" style="display:inline;">
                        @csrf
                        <input type="hidden" name="batch_id" value="{{ $batchId }}">
                        <button type="submit" class="btn btn-warning">
                            <i class="ti ti-download me-1"></i> Export Unmatched as Excel
                        </button>
                    </form>
                    <span class="text-muted ms-2" id="unmatchedPageInfo"></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm" id="unmatchedTable">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Vendor Code</th>
                                <th>Active Vendor Code</th>
                                <th>Vendor Name</th>
                                <th>PAN</th>
                            </tr>
                        </thead>
                        <tbody id="unmatchedBody"></tbody>
                    </table>
                </div>
                {{-- Unmatched Pagination --}}
                <nav id="unmatchedPagination" class="mt-3">
                    <ul class="pagination pagination-sm flex-wrap" id="unmatchedPageLinks"></ul>
                </nav>
                @else
                <div class="alert alert-info">All vendors were matched successfully!</div>
                @endif
            </div>

        </div>

        <div class="mt-3">
            <a href="{{ route('parties.vendor_import_view') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Upload Another File
            </a>
            <a href="{{ route('parties.parties') }}" class="btn btn-label-secondary ms-2">Back to Parties</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const validateUrl = "{{ route('parties.vendor_import_validate') }}";
    const csrfToken = "{{ csrf_token() }}";
    const PER_PAGE = {{ $perPage }};

    // Data passed from server (embedded as JSON)
    const matchedData = (@json($matched) || []).map(function (item, idx) {
        item.__sourceIdx = idx;
        return item;
    });
    const unmatchedData = @json($unmatched);
    let filteredMatchedData = matchedData.slice();

    const matchBadges = {
        'exact':      '<span class="badge bg-success">PAN + Name</span>',
        'pan_only':   '<span class="badge bg-primary">PAN Only</span>',
        'name_only':  '<span class="badge bg-warning">Name Only</span>',
        'fuzzy_name': '<span class="badge bg-info">Fuzzy Name</span>',
    };

    let matchedPage = 1;
    let unmatchedPage = 1;

    const matchTypeFilter = qs('#matchTypeFilter');
    const locationFilter = qs('#locationFilter');
    const contractCountFilter = qs('#contractCountFilter');
    const resetMatchedFiltersBtn = qs('#resetMatchedFiltersBtn');

    function normalizeLocation(value) {
        return String(value || '').trim().toLowerCase();
    }

    function getItemLocations(item) {
        const arr = Array.isArray(item.locations) ? item.locations : [];
        return arr.map(function (v) { return String(v || '').trim(); }).filter(function (v) { return v !== ''; });
    }

    function contractCountBucketMatches(bucket, count) {
        const c = Number(count || 0);
        if (!bucket) return true;
        if (bucket === '0') return c === 0;
        if (bucket === '1') return c === 1;
        if (bucket === '2_5') return c >= 2 && c <= 5;
        if (bucket === '6_plus') return c >= 6;
        return true;
    }

    function populateLocationFilterOptions() {
        if (!locationFilter) return;
        const allLocations = {};
        matchedData.forEach(function (item) {
            if (Number(item.already_valid || 0) !== 0) return;
            getItemLocations(item).forEach(function (loc) {
                allLocations[normalizeLocation(loc)] = loc;
            });
        });

        const keys = Object.keys(allLocations).sort();
        let html = '<option value="">All</option>';
        keys.forEach(function (k) {
            html += '<option value="' + escHtml(k) + '">' + escHtml(allLocations[k]) + '</option>';
        });
        locationFilter.innerHTML = html;
    }

    function applyMatchedFilters() {
        const matchTypeVal = matchTypeFilter ? String(matchTypeFilter.value || '').trim() : '';
        const locationVal = locationFilter ? String(locationFilter.value || '').trim() : '';
        const contractsVal = contractCountFilter ? String(contractCountFilter.value || '').trim() : '';

        filteredMatchedData = matchedData.filter(function (item) {
            if (Number(item.already_valid || 0) !== 0) {
                return false;
            }

            if (matchTypeVal && String(item.match_type || '') !== matchTypeVal) {
                return false;
            }

            if (locationVal) {
                const normalizedLocations = getItemLocations(item).map(normalizeLocation);
                if (normalizedLocations.indexOf(locationVal) === -1) {
                    return false;
                }
            }

            if (!contractCountBucketMatches(contractsVal, item.contracts)) {
                return false;
            }

            return true;
        });

        renderMatchedPage(1);
    }

    // ---- Matched Table Rendering with Pagination ----
    function renderMatchedPage(page) {
        matchedPage = page;
        const start = (page - 1) * PER_PAGE;
        const total = filteredMatchedData.length;
        const end = Math.min(start + PER_PAGE, total);
        const slice = filteredMatchedData.slice(start, end);

        let html = '';
        if (slice.length === 0) {
            html = '<tr><td colspan="12" class="text-center text-muted py-3">No matched vendors found for selected filters.</td></tr>';
        }

        slice.forEach(function (item, i) {
            const globalIdx = item.__sourceIdx;
            const r = item.excel_row;
            const alreadyValid = item.already_valid == 1;
            const locationText = getItemLocations(item).join(', ');
            html += '<tr data-idx="' + globalIdx + '" data-party-id="' + item.party_id + '"'
                + ' data-vendor-code="' + escHtml(r.vendor_code) + '"'
                + ' data-active-vendor-code="' + escHtml(r.active_vendor_code) + '"'
                + ' data-pan="' + escHtml(r.pan) + '">';
            html += '<td>';
            if (alreadyValid) {
                html += '<span class="badge bg-label-secondary">Done</span>';
            } else {
                html += '<input type="checkbox" class="form-check-input row-check" value="' + globalIdx + '">';
            }
            html += '</td>';
            html += '<td>' + (r.s_no || (globalIdx + 1)) + '</td>';
            html += '<td>' + escHtml(r.vendor_code) + '</td>';
            html += '<td>' + escHtml(r.active_vendor_code) + '</td>';
            html += '<td>' + escHtml(r.vendor_name) + '</td>';
            html += '<td>' + escHtml(r.pan) + '</td>';
            html += '<td>' + escHtml(item.party_name) + '</td>';
            html += '<td>' + escHtml(item.party_pan) + '</td>';
            html += '<td>' + (matchBadges[item.match_type] || item.match_type) + '</td>';
            html += '<td>' + (locationText ? escHtml(locationText) : '<span class="text-muted">-</span>') + '</td>';
            html += '<td>';
            if (item.contracts > 0) {
                html += '<span class="badge bg-label-primary me-1">' + item.contracts + '</span>';
                html += '<a class="btn btn-xs btn-label-info" target="_blank" rel="noopener" href="' + APP_URL + '/contracts/list?party_id=' + encodeURIComponent(item.party_id) + '">View</a>';
            } else {
                html += '<span class="text-muted me-1">None</span>';
                html += '<a class="btn btn-xs btn-label-info" target="_blank" rel="noopener" href="' + APP_URL + '/contracts/list?party_id=' + encodeURIComponent(item.party_id) + '">View</a>';
            }
            html += '</td>';
            html += '<td>';
            if (alreadyValid) {
                html += '<button class="btn btn-sm btn-label-secondary" disabled><i class="ti ti-check"></i> Already Valid</button>';
            } else {
                html += '<button class="btn btn-sm btn-success validate-single-btn"'
                    + ' data-party-id="' + item.party_id + '"'
                    + ' data-vendor-code="' + escHtml(r.vendor_code) + '"'
                    + ' data-active-vendor-code="' + escHtml(r.active_vendor_code) + '"'
                    + ' data-pan="' + escHtml(r.pan) + '">'
                    + '<i class="ti ti-check"></i> Valid</button>';
            }
            html += '</td></tr>';
        });
        qs('#matchedBody').innerHTML = html;
        const selectAllMatched = qs('#selectAllMatched');
        if (selectAllMatched) {
            selectAllMatched.checked = false;
        }
        updateSelectedCount();
        renderPagination('#matchedPageLinks', total, page, function (p) { renderMatchedPage(p); });
        const info = qs('#matchedPageInfo');
        if (info) {
            if (total === 0) {
                info.textContent = 'Showing 0 of 0';
            } else {
                info.textContent = 'Showing ' + (start + 1).toLocaleString() + '-' + end.toLocaleString() + ' of ' + total.toLocaleString();
            }
        }
    }

    // ---- Unmatched Table Rendering with Pagination ----
    function renderUnmatchedPage(page) {
        unmatchedPage = page;
        const start = (page - 1) * PER_PAGE;
        const end = Math.min(start + PER_PAGE, unmatchedData.length);
        const slice = unmatchedData.slice(start, end);

        let html = '';
        slice.forEach(function (item, i) {
            const r = item.excel_row;
            html += '<tr>';
            html += '<td>' + (r.s_no || (start + i + 1)) + '</td>';
            html += '<td>' + escHtml(r.vendor_code) + '</td>';
            html += '<td>' + escHtml(r.active_vendor_code) + '</td>';
            html += '<td>' + escHtml(r.vendor_name) + '</td>';
            html += '<td>' + escHtml(r.pan) + '</td>';
            html += '</tr>';
        });
        qs('#unmatchedBody').innerHTML = html;
        renderPagination('#unmatchedPageLinks', unmatchedData.length, page, function (p) { renderUnmatchedPage(p); });
        const info = qs('#unmatchedPageInfo');
        if (info) {
            info.textContent = 'Showing ' + (start + 1).toLocaleString() + '-' + end.toLocaleString() + ' of ' + unmatchedData.length.toLocaleString();
        }
    }

    // ---- Pagination Renderer ----
    function renderPagination(selector, totalItems, currentPage, callback) {
        const totalPages = Math.ceil(totalItems / PER_PAGE);
        const container = qs(selector);
        if (!container) return;
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        html += '<li class="page-item ' + (currentPage === 1 ? 'disabled' : '') + '"><a class="page-link" href="#" data-page="' + (currentPage - 1) + '">&laquo;</a></li>';

        // Show max 10 page links around current page
        let startP = Math.max(1, currentPage - 5);
        let endP = Math.min(totalPages, startP + 9);
        if (endP - startP < 9) startP = Math.max(1, endP - 9);

        if (startP > 1) html += '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li><li class="page-item disabled"><span class="page-link">...</span></li>';

        for (let p = startP; p <= endP; p++) {
            html += '<li class="page-item ' + (p === currentPage ? 'active' : '') + '"><a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
        }

        if (endP < totalPages) html += '<li class="page-item disabled"><span class="page-link">...</span></li><li class="page-item"><a class="page-link" href="#" data-page="' + totalPages + '">' + totalPages + '</a></li>';

        html += '<li class="page-item ' + (currentPage === totalPages ? 'disabled' : '') + '"><a class="page-link" href="#" data-page="' + (currentPage + 1) + '">&raquo;</a></li>';

        container.innerHTML = html;
        container.onclick = function (e) {
            const link = e.target.closest('a.page-link');
            if (!link) return;
            e.preventDefault();
            const p = parseInt(link.dataset.page, 10);
            if (p >= 1 && p <= totalPages) callback(p);
        };
    }

    function escHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function updateSelectedCount() {
        const cnt = qsa('.row-check:checked').length;
        const selectedCount = qs('#selectedCount');
        if (selectedCount) {
            selectedCount.textContent = String(cnt);
        }
        const validateSelectedBtn = qs('#validateSelectedBtn');
        if (validateSelectedBtn) {
            validateSelectedBtn.disabled = cnt === 0;
        }
    }

    // ---- Events ----
    const selectAllMatched = qs('#selectAllMatched');
    if (selectAllMatched) {
        selectAllMatched.addEventListener('change', function () {
            qsa('.row-check').forEach(function (el) {
                el.checked = selectAllMatched.checked;
            });
            updateSelectedCount();
        });
    }

    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('row-check')) return;
        updateSelectedCount();
        if (!e.target.checked && selectAllMatched) selectAllMatched.checked = false;
        const allChecks = qsa('.row-check');
        const checked = qsa('.row-check:checked');
        if (selectAllMatched && checked.length === allChecks.length && allChecks.length > 0) {
            selectAllMatched.checked = true;
        }
    });

    // Single validate
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.validate-single-btn');
        if (!btn) return;
        const items = [{
            party_id: btn.dataset.partyId,
            vendor_code: btn.dataset.vendorCode,
            active_vendor_code: btn.dataset.activeVendorCode,
            pan: btn.dataset.pan
        }];
        sendValidation(items, function () {
            const tr = btn.closest('tr');
            // Update data
            const idx = tr ? Number(tr.dataset.idx) : -1;
            if (matchedData[idx]) {
                matchedData[idx].already_valid = 1;
            }
            applyMatchedFilters();
        });
    });

    // Bulk validate selected
    const validateSelectedBtn = qs('#validateSelectedBtn');
    if (validateSelectedBtn) {
        validateSelectedBtn.addEventListener('click', function () {
        const items = [];
        const rows = [];
        qsa('.row-check:checked').forEach(function (checkbox) {
            const row = checkbox.closest('tr');
            items.push({
                party_id: row.dataset.partyId,
                vendor_code: row.dataset.vendorCode,
                active_vendor_code: row.dataset.activeVendorCode,
                pan: row.dataset.pan
            });
            rows.push(row);
        });
        if (items.length === 0) return;

        sendValidation(items, function () {
            rows.forEach(function (row) {
                const idx = Number(row.dataset.idx);
                if (matchedData[idx]) matchedData[idx].already_valid = 1;
            });
            if (selectAllMatched) selectAllMatched.checked = false;
            applyMatchedFilters();
        });
    });
    }

    // Validate all on current page
    const validateAllPageBtn = qs('#validateAllPageBtn');
    if (validateAllPageBtn) {
        validateAllPageBtn.addEventListener('click', function () {
        const items = [];
        const rows = [];
        qsa('#matchedBody tr').forEach(function (row) {
            const chk = row.querySelector('.row-check');
            if (chk && !chk.disabled) {
                items.push({
                    party_id: row.dataset.partyId,
                    vendor_code: row.dataset.vendorCode,
                    active_vendor_code: row.dataset.activeVendorCode,
                    pan: row.dataset.pan
                });
                rows.push(row);
            }
        });
        if (items.length === 0) { alert('No rows to validate on this page.'); return; }

        sendValidation(items, function () {
            rows.forEach(function (row) {
                const idx = Number(row.dataset.idx);
                if (matchedData[idx]) matchedData[idx].already_valid = 1;
            });
            if (selectAllMatched) selectAllMatched.checked = false;
            applyMatchedFilters();
        });
    });
    }

    // Validate all rows currently viewing (current filtered result across pages)
    const validateAllViewingBtn = qs('#validateAllViewingBtn');
    if (validateAllViewingBtn) {
        validateAllViewingBtn.addEventListener('click', function () {
            const items = filteredMatchedData.map(function (item) {
                const r = item.excel_row || {};
                return {
                    party_id: item.party_id,
                    vendor_code: r.vendor_code || '',
                    active_vendor_code: r.active_vendor_code || '',
                    pan: r.pan || ''
                };
            }).filter(function (item) {
                return Number(item.party_id || 0) > 0;
            });

            if (items.length === 0) {
                alert('No rows available in current view to validate.');
                return;
            }

            const ok = window.confirm('You are about to validate ' + items.length.toLocaleString() + ' row(s) from current view. Continue?');
            if (!ok) {
                return;
            }

            const idxList = filteredMatchedData.map(function (item) {
                return Number(item.__sourceIdx);
            });

            sendValidation(items, function () {
                idxList.forEach(function (idx) {
                    if (matchedData[idx]) {
                        matchedData[idx].already_valid = 1;
                    }
                });
                if (selectAllMatched) {
                    selectAllMatched.checked = false;
                }
                applyMatchedFilters();
            });
        });
    }

    if (matchTypeFilter) {
        matchTypeFilter.addEventListener('change', applyMatchedFilters);
    }

    if (locationFilter) {
        locationFilter.addEventListener('change', applyMatchedFilters);
    }

    if (contractCountFilter) {
        contractCountFilter.addEventListener('change', applyMatchedFilters);
    }

    if (resetMatchedFiltersBtn) {
        resetMatchedFiltersBtn.addEventListener('click', function () {
            if (matchTypeFilter) matchTypeFilter.value = '';
            if (locationFilter) locationFilter.value = '';
            if (contractCountFilter) contractCountFilter.value = '';
            applyMatchedFilters();
        });
    }

    async function sendValidation(items, onSuccess) {
        // Chunk validation requests for large batches
        const VALIDATE_CHUNK = 200;
        if (items.length > VALIDATE_CHUNK) {
            let totalSuccess = 0;
            async function sendBatch(start) {
                const batch = items.slice(start, start + VALIDATE_CHUNK);
                if (batch.length === 0) {
                    if (typeof window.toastr !== 'undefined') {
                        window.toastr.success(totalSuccess + ' vendor(s) validated successfully.');
                    } else {
                        alert(totalSuccess + ' vendor(s) validated successfully.');
                    }
                    onSuccess();
                    return;
                }
                try {
                    const resp = await requestJson(validateUrl, {
                        items: batch,
                    });
                    if (resp.status) {
                        totalSuccess += batch.length;
                        await sendBatch(start + VALIDATE_CHUNK);
                    } else {
                        alert(resp.message || 'Validation failed.');
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                }
            }
            await sendBatch(0);
            return;
        }

        try {
            const resp = await requestJson(validateUrl, {
                items: items,
            });
            if (resp.status) {
                if (typeof window.toastr !== 'undefined') {
                    window.toastr.success(resp.message);
                } else {
                    alert(resp.message);
                }
                onSuccess();
            } else {
                alert(resp.message || 'Validation failed.');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async function requestJson(url, payload) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json().catch(function () { return {}; });
        if (!res.ok) {
            throw new Error(data.message || 'Something went wrong.');
        }
        return data;
    }

    function qs(selector) {
        return document.querySelector(selector);
    }

    function qsa(selector) {
        return Array.from(document.querySelectorAll(selector));
    }

    // Initial render
    if (matchedData.length > 0) {
        populateLocationFilterOptions();
        applyMatchedFilters();
    }
    if (unmatchedData.length > 0) renderUnmatchedPage(1);
});
</script>
