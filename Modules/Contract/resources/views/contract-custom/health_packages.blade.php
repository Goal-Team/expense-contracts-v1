@php
    $existingHc = ($healthChecks instanceof \Illuminate\Support\Collection
    ? $healthChecks
    : collect([$healthChecks])) ?? collect();
    $isSignatory = $isSignatory ?? false;
    $isEditor = $isEditor ?? false;
    $packagesCount = $packagesCount ?? 0;
    $viewTable = $viewTable;
    $viewTestPrice = $viewTestPrice; 
    $showSummaryCount = $showSummaryCount;
    $isCreditUser = $isCreditUser ?? false;
@endphp

<style>
.proposed-price--warn{
    color: #ffa500 !important;
    font-weight: 600;
}
.package-details.collapsed { display: none !important; }
.package-header { cursor: pointer; }
.toggle-indicator { font-weight: 700; margin-left: 8px; }
</style>

<div class="card mb-3">
    @if(($isSignatory && !$isEditor) || $isCreditUser)
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Health Check Packages <span class="badge badge-center text-bg-info">{{$packagesCount}}</span></h5>
        @if(!$isCreditUser)
        <button type="button" id="hp-show-details-btn" class="btn btn-sm btn-outline-secondary" data-hidden="1">Show details</button>
        @endif
    </div>
    @endif

    <div class="card-body">
        @if($existingHc->count())
            @if(($isSignatory && !$isEditor) || $isCreditUser)
                {{-- Simplified list visible by default; full details hidden, can be toggled --}}
                <div class="list-group mb-3">
                    @foreach($existingHc as $hc)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div><strong>{{ $hc->row_name }}</strong></div>
                            <div>₹{{ number_format($hc->package_price ?? 0, 2) }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- Full details, initially hidden, toggled by button above --}}
                <div class="hp-full-details" style="display:none;">
                    @foreach($existingHc as $hcIndex => $hc)
                        @php
                            $testIds = is_string($hc->selected_test_ids) ? json_decode($hc->selected_test_ids, true) : $hc->selected_test_ids;
                            $consultIds = is_string($hc->selected_consultation_ids) ? json_decode($hc->selected_consultation_ids, true) : $hc->selected_consultation_ids;
                            $prices = is_string($hc->consultation_prices) ? json_decode($hc->consultation_prices, true) : $hc->consultation_prices;
                            $testsCollection = method_exists($hc,'tests') ? $hc->tests()->get() : collect();
                        @endphp
                        <div class="health-package-wrapper">
                        <div class="package-header mb-2">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $hc->row_name }}</strong>
                                    <div class="text-muted small">Package #{{ $hcIndex + 1 }} — Base: ₹{{ number_format($hc->package_price,2) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="package-details collapsed" data-test-subtotal="{{ $testSubtotal }}" data-consultation-subtotal="{{ $consultationSubtotal }}" data-proposed-price="{{ $hc->package_price }}">
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered">
                                <thead><tr><th>#</th><th>Description</th><th>Type</th><th class="text-end">Amount (₹)</th></tr></thead>
                                <tbody>
                                    @php $rowNo = 1; $testSubtotal = 0; $consultationSubtotal = 0; @endphp
                                    @forelse($testsCollection as $t)
                                        @php $testSubtotal += floatval($t->price ?? 0); @endphp
                                        <tr>
                                            <td>{{ $rowNo++ }}</td>
                                            <td>{{ $t->name }}</td>
                                            <td><span class="badge bg-primary">Diagnostics</span></td>
                                            <td class="text-end">{{  $isSignatory ? '-' : number_format($t->price,2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted">No diagnostics</td></tr>
                                    @endforelse

                                    @if(!empty($consultIds))
                                        @foreach($consultIds as $cid)
                                            @php
                                                $consultationName = 'Consultation #' . $cid;
                                                $consultationPrice = 0;
                                                if(isset($consultations) && is_array($consultations)) {
                                                    foreach($consultations as $c) {
                                                        if(isset($c['id']) && $c['id'] == $cid) { $consultationName = $c['name'] ?? $c['consultation_name'] ?? $consultationName; break; }
                                                    }
                                                }
                                                if(!empty($prices) && isset($prices[$cid])) { $consultationPrice = floatval($prices[$cid]); $consultationSubtotal += $consultationPrice; }
                                            @endphp
                                            <tr>
                                                <td>{{ $rowNo++ }}</td>
                                                <td>{{ $consultationName }}</td>
                                                <td><span class="badge bg-success">Consultation</span></td>
                                                <td class="text-end">{{ number_format($consultationPrice,2) }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Non-signatory or editor sees full detailed view (or editor will have editable controls inside included partial) --}}
                @if($isCurrentApproverIsApproverOrVerifier)
                    @foreach($existingHc as $hcIndex => $hc)
                        @php
                            $testIds = is_string($hc->selected_test_ids) ? json_decode($hc->selected_test_ids, true) : $hc->selected_test_ids;
                            $consultIds = is_string($hc->selected_consultation_ids) ? json_decode($hc->selected_consultation_ids, true) : $hc->selected_consultation_ids;
                            $prices = is_string($hc->consultation_prices) ? json_decode($hc->consultation_prices, true) : $hc->consultation_prices;
                            $testsCollection = method_exists($hc,'tests') ? $hc->tests()->get() : collect();
                            $testSubtotal = 0;
                            foreach($testsCollection as $t) { $testSubtotal += floatval($t->price ?? 0); }
                            $consultationSubtotal = 0;
                            if (!empty($consultIds) && !empty($prices)) { foreach($consultIds as $cid) { if(isset($prices[$cid])) $consultationSubtotal += floatval($prices[$cid]); } }
                            $packageSubTotal = $testSubtotal + $consultationSubtotal;
                        @endphp
                        <div class="health-package-wrapper">
                        <div class="package-header mb-2">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $hc->row_name }}</strong>
                                    <div class="text-muted small">Package #{{ $hcIndex + 1 }} — Proposed Price: ₹{{ number_format($hc->package_price,2) }}</div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary view-package-btn">View Health Package <span class="toggle-indicator">▶</span></button>
                                </div>
                            </div>
                        </div>
                        <div class="package-details collapsed">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-3">
                                <thead>
                                    <tr><th>#</th><th>Description</th><th>Type</th><th class="text-end">Amount (₹)</th></tr>
                                </thead>
                                <tbody>
                                    @php $rowNo = 1; @endphp
                                    @forelse($testsCollection as $t)
                                        <tr>
                                            <td>{{ $rowNo++ }}</td>
                                            <td>{{ $t->name }}</td>
                                            <td><span class="badge bg-primary">Test</span></td>
                                            <td class="text-end">{{ number_format($t->price,2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted">No tests</td></tr>
                                    @endforelse

                                    @if(!empty($consultIds))
                                        @foreach($consultIds as $cid)
                                            @php
                                                $consultationName = 'Consultation #' . $cid;
                                                $consultationPrice = 0;
                                                if(isset($consultations) && is_array($consultations)) {
                                                    foreach($consultations as $c) {
                                                        if(isset($c['id']) && $c['id'] == $cid) { $consultationName = $c['name'] ?? $c['consultation_name'] ?? $consultationName; break; }
                                                    }
                                                }
                                                if(!empty($prices) && isset($prices[$cid])) $consultationPrice = floatval($prices[$cid]);
                                            @endphp
                                            <tr>
                                                <td>{{ $rowNo++ }}</td>
                                                <td>{{ $consultationName }}</td>
                                                <td><span class="badge bg-success">Consultation</span></td>
                                                <td class="text-end">{{ number_format($consultationPrice,2) }}</td>
                                            </tr>
                                        @endforeach
                                    @endif

                                    <tr class="subtotal-row {{ $isSignatory ? 'd-none': '' }}"><td colspan="3" class="text-end">Cost of Health Check Component</td><td class="text-end package-test-subtotal">₹{{ number_format($testSubtotal,2) }}</td></tr>
                                    <tr class="subtotal-row {{ $isSignatory ? 'd-none': '' }}"><td colspan="3" class="text-end">Cost of Consultation</td><td class="text-end package-consultation-subtotal">₹{{ number_format($consultationSubtotal,2) }}</td></tr>
                                    <tr class="overhead-row {{ $isSignatory ? 'd-none': '' }}"><td colspan="3" class="text-end">Overhead Allocation</td><td class="text-end"><input type="number" min="0" step="0.01" value="{{ isset($hc->overhead_allocation) ? $hc->overhead_allocation : '0.00' }}" class="form-control form-control-sm overhead-input" style="width:120px; display:inline-block;"> <small class="text-muted">₹</small></td></tr>

                                    <tr class="total-row {{ $isSignatory ? 'd-none': '' }}"><td colspan="3" class="text-end">Total Health Check Package Cost</td><td class="text-end package-total">₹<span class="value">{{ number_format($testSubtotal + $consultationSubtotal + (isset($hc->overhead_allocation) ? floatval($hc->overhead_allocation) : 0),2) }}</span></td></tr>

                                    <tr><td colspan="3" class="text-end">Proposed Price</td><td class="text-end proposed-price">₹{{ number_format($hc->package_price,2) }}</td></tr>
                                    <tr><td colspan="3" class="text-end">Package Cost</td><td class="text-end package-cost">₹<span class="value">{{ number_format($testSubtotal + $consultationSubtotal + (isset($hc->overhead_allocation) ? floatval($hc->overhead_allocation) : 0),2) }}</span></td></tr>
                                    <tr><td colspan="3" class="text-end">Approved Cost</td><td class="text-end"><input type="number" min="0" step="0.01" class="form-control form-control-sm approved-cost-input" style="width:120px; display:inline-block;" value="{{ isset($hc->approved_cost) ? $hc->approved_cost : '0.00' }}"></td></tr>

                                    <tr><td colspan="4" class="text-muted small">Cost data is strictly confidential and for internal use only.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        </div>
                        </div>
                    @endforeach
                @endif
            @endif
        @else
            <div class="text-muted">No health check packages attached.</div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // Guard against double-initialization if partial is included more than once
    if (window.__hpInitialized) return;
    window.__hpInitialized = true;

    // ─── Helper: find the .package-details for a given element inside a wrapper ───
    function findPackageDetails(el) {
        var wrapper = el.closest('.health-package-wrapper');
        return wrapper ? wrapper.querySelector('.package-details') : null;
    }

    // ─── "Show details" toggle (signatory / credit-user section) ───
    var showBtn = document.getElementById('hp-show-details-btn');
    if (showBtn) {
        showBtn.addEventListener('click', function(){
            var fullDetails = document.querySelectorAll('.hp-full-details');
            var hidden = showBtn.getAttribute('data-hidden') === '1';
            fullDetails.forEach(function(wrapper){
                wrapper.style.display = hidden ? '' : 'none';
                wrapper.querySelectorAll('.health-package-wrapper').forEach(function(pkgWrapper){
                    var pkg = pkgWrapper.querySelector('.package-details');
                    if (!pkg) return;
                    if (hidden) { pkg.classList.remove('collapsed'); } else { pkg.classList.add('collapsed'); }
                    var ind = pkgWrapper.querySelector('.toggle-indicator');
                    if (ind) ind.textContent = pkg.classList.contains('collapsed') ? '▶' : '▼';
                });
            });
            showBtn.textContent = hidden ? 'Hide details' : 'Show details';
            showBtn.setAttribute('data-hidden', hidden ? '0' : '1');
        });
    }

    // ─── Event delegation for "View Health Package" buttons & clickable headers ───
    document.addEventListener('click', function(e){
        // Check if user clicked on a .view-package-btn (or child like .toggle-indicator)
        var btn = e.target.closest('.view-package-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            var pkg = findPackageDetails(btn);
            if (pkg) {
                var isCollapsed = pkg.classList.toggle('collapsed');
                var ind = btn.querySelector('.toggle-indicator');
                if (ind) ind.textContent = isCollapsed ? '▶' : '▼';
            }
            return;
        }

        // Check if user clicked on a .package-header (without a button inside)
        var header = e.target.closest('.package-header');
        if (header && !header.querySelector('.view-package-btn')) {
            var pkg2 = findPackageDetails(header);
            if (pkg2) {
                var isCollapsed2 = pkg2.classList.toggle('collapsed');
                var ind2 = header.querySelector('.toggle-indicator');
                if (ind2) ind2.textContent = isCollapsed2 ? '▶' : '▼';
            }
        }
    });

    // ─── Rename remaining 'Test' → 'Diagnostics' labels ───
    document.querySelectorAll('.badge').forEach(function(b){
        if (b.textContent.trim() === 'Test') b.textContent = 'Diagnostics';
    });
    document.querySelectorAll('td.text-center.text-muted').forEach(function(td){
        if (td.textContent.trim() === 'No tests') td.textContent = 'No diagnostics';
    });

    // Replace subtotal labels if necessary
    document.querySelectorAll('tr.subtotal-row td:first-child, tr.total-row td:first-child').forEach(function(td){
        var txt = td.textContent.trim();
        if (txt === 'Tests Subtotal') td.textContent = 'Cost of Health Check Component';
        if (txt === 'Consultations Subtotal') td.textContent = 'Cost of Consultation';
        if (txt === 'Package Total') td.textContent = 'Total Health Check Package Cost';
    });

    // ─── Calculation helpers ───
    function formatCurrency(num) { return Number(num).toFixed(2); }

    function recalcPackage(pkg) {
        var testSub = parseFloat(pkg.dataset.testSubtotal || 0);
        var consultSub = parseFloat(pkg.dataset.consultationSubtotal || 0);

        // fallback: parse from DOM
        if (!testSub) {
            var el = pkg.querySelector('.package-test-subtotal');
            testSub = el ? parseFloat((el.textContent || '').replace(/[^0-9.-]+/g,'')) : 0;
        }
        if (!consultSub) {
            var el2 = pkg.querySelector('.package-consultation-subtotal');
            consultSub = el2 ? parseFloat((el2.textContent || '').replace(/[^0-9.-]+/g,'')) : 0;
        }

        var overheadInput = pkg.querySelector('.overhead-input');
        var overhead = overheadInput ? parseFloat(overheadInput.value) : 0;
        if (isNaN(overhead) || overhead < 0) { overhead = 0; if (overheadInput) overheadInput.value = formatCurrency(overhead); }

        var packageCost = testSub + consultSub + overhead;

        var totalEl = pkg.querySelector('.package-total .value');
        if (totalEl) totalEl.textContent = formatCurrency(packageCost);
        var costEl = pkg.querySelector('.package-cost .value');
        if (costEl) costEl.textContent = formatCurrency(packageCost);

        var proposedEl = pkg.querySelector('.proposed-price');
        var proposedVal = proposedEl ? parseFloat((proposedEl.textContent || '').replace(/[^0-9.-]+/g,'')) : 0;
        if (proposedVal > 0 && proposedVal < packageCost) {
            if (proposedEl) {
                proposedEl.classList.add('proposed-price--warn');
                proposedEl.setAttribute('title', 'Proposed price is less than the calculated Package Cost.');
            }
        } else {
            if (proposedEl) {
                proposedEl.classList.remove('proposed-price--warn');
                proposedEl.removeAttribute('title');
            }
        }
    }

    // ─── Initialize calculations for every .package-details block ───
    document.querySelectorAll('.package-details').forEach(function(pkg){
        var overhead = pkg.querySelector('.overhead-input');
        var approved = pkg.querySelector('.approved-cost-input');
        if (overhead) overhead.value = Number(overhead.value || 0).toFixed(2);
        if (approved && approved.value) approved.value = Number(approved.value).toFixed(2);

        pkg.querySelectorAll('.overhead-input, .approved-cost-input').forEach(function(inp){
            inp.addEventListener('input', function(){
                if (parseFloat(inp.value) < 0) inp.value = '0.00';
                if (inp.classList.contains('overhead-input')) recalcPackage(pkg);
            });
            inp.addEventListener('blur', function(){
                if (inp.value === '') inp.value = '0.00';
                inp.value = Number(inp.value).toFixed(2);
            });
        });

        recalcPackage(pkg);
    });
});
</script>