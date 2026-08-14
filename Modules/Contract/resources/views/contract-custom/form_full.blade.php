@php
  // Helpers local to this partial
  if (! function_exists('parse_json')) {
    function parse_json($val) {
      if ($val === null || $val === '') return [];
      if (is_array($val)) return $val;
      $decoded = @json_decode($val, true);
      return is_array($decoded) ? $decoded : [];
    }
  }

  use Carbon\Carbon;

  $party = optional($contract->contractPartyList->get(1));
  $partyEx = $party ? optional($party->partyDetailsEx) : null;
  $partyIn = $party ? optional($party->partyDetailsIn) : null;
  $customerName = $partyEx && ($partyEx->company_name ?? false)
                  ? (function_exists('decryptString') ? @decryptString($partyEx->company_name, 'company_name') : $partyEx->company_name)
                  : ($partyIn && ($partyIn->name ?? false) ? $partyIn->name : '');

  $scopeVals = parse_json($contract->contract_tags ?? null);
  if (empty($scopeVals) && is_string($contract->contract_tags) && trim($contract->contract_tags) !== '') {
    $scopeVals = array_map('trim', explode(',', $contract->contract_tags));
  }

  $startDateValue = $contract->fixed_date ?? $contract->signing_date ?? null;
  $startDateIso = $startDateValue ? Carbon::parse($startDateValue)->format('Y-m-d') : '';
  $startDateDisplay = $startDateValue ? Carbon::parse($startDateValue)->format('d M Y') : '—';
  $endDateDisplay = $contract->contract_end_date ? Carbon::parse($contract->contract_end_date)->format('d M Y') : '—';

  $isCreditCell = $isCreditCell ?? false;
  $confData = parse_json($contract->confidentialityagreement ?? null);

  // Build tests master price map so we can sum selected tests default price server-side in the form
  $testsMasterMap = [];
  if (!empty($tests) && is_iterable($tests)) {
    foreach ($tests as $t) {
      $tid = is_object($t) ? ($t->id ?? null) : ($t['id'] ?? null);
      $testsMasterMap[(string)$tid] = is_object($t) ? ($t->price ?? $t->default_price ?? 0) : ($t['price'] ?? $t['default_price'] ?? 0);
    }
  }
@endphp

<form id="agreement-form" data-readonly="{{ $readonlyForm ? '1' : '0' }}">
  <div class=" mb-4">
    <div class="">
      <h5>Agreement Details </h5>

      @if(!empty($canViewForm) && $readonlyForm)
        <div class="alert alert-info py-2">
          @if(!empty($isOwner))
            <strong>Note:</strong> You are the contract owner. This form is view-only.
          @else
            <strong>Note:</strong> You may view this form but it is read-only because you are not the active approver/verifier.
          @endif
        </div>
      @endif
      <div class="card mb-4">
        <div class="card-body">
    
          <div class="row mb-2">
            <div class="col-md-6">
              <label for="agreement_name" class="form-label">Agreement Name <span class="text-danger">*</span></label>
              <input id="agreement_name" name="agreement_name" class="form-control" required
                     value="{{ old('agreement_name', $contract->contract_name_decrypted ?? $contract->contract_name ?? '') }}">
              <div class="invalid-feedback">Agreement name is required.</div>
            </div>
    
            <div class="col-md-6 position-relative">
              <label for="customer" class="form-label">Customer <span class="text-danger">*</span></label>
              <input id="customer" class="form-control customer-search" aria-autocomplete="list" autocomplete="off" required
                     value="{{ old('customer', $customerName) }}"
                     data-customer='@json($party ? $party->toArray() : null)'>
              <div class="invalid-feedback">Customer is required.</div>
              <div class="list-group position-absolute z-50" id="customer_suggestions" style="display:none; max-height:200px; overflow:auto;"></div>
              <input type="hidden" id="contractId" name="contractId" value="{{ $contract->id }}" />
              
            @if(isset($currentEntry) && ($currentEntry->approver_type_row === 'Owner'))
                <input type="hidden" name="current_approval" value="{{ old('current_approval', $currentEntry->id ?? 0) }}">
            @endif              
            </div>
          </div>
    
          <div class="row mb-2">
            <div class="col-md-3">
              <label for="scope" class="form-label">Scope</label>
              <select id="scope" class="form-select">
                <option value="">--auto--</option>
                <option value="domestic" {{ (string)($contract->custom_fields_data ?? '') === 'domestic' ? 'selected' : '' }}>Domestic</option>
                <option value="international" {{ (string)($contract->custom_fields_data ?? '') === 'international' ? 'selected' : '' }}>International</option>
              </select>
            </div>
    
            <div class="col-md-3">
              <label for="entity_type" class="form-label">Entity Type</label>
              <select id="entity_type" class="form-select">
                <option value="">Select entity</option>
                @foreach($entityTypesList as $et)
                  <option value="{{ $et['id'] }}" {{ $et['id'] == $contract->catgoery_id ? 'selected' : '' }}>{{ $et['name'] }}</option>
                @endforeach
              </select>
              <div class="invalid-feedback">Entity type required.</div>
            </div>
    
            <div class="col-md-6">
              <label for="locations_toggle" class="form-label">Locations <span class="text-danger">*</span></label>
              <div>
                <button class="btn btn-sm btn-outline-secondary collapse-toggle" type="button"
                        id="toggle_locations_btn" data-bs-toggle="collapse" data-bs-target="#locations-collapse"
                        aria-expanded="false" aria-controls="locations-collapse">
                  Locations ({{ $contract->contractLocations->count() }})
                </button>
              </div>
              <div class="collapse mt-2" id="locations-collapse">
                <div id="locations_container" class="compact-check" aria-live="polite">
                  @php
                    // Group locations by region for server-side rendering.
                    $locObjs = \App\Models\LocationMaster::select('id','location_name','region')->orderBy('region')->orderBy('location_name')->get();
                    $locGroups = [];
                    foreach ($locObjs as $l) {
                      $reg = $l->region ?? 'Unassigned';
                      if (!isset($locGroups[$reg])) $locGroups[$reg] = [];
                      $locGroups[$reg][$l->id] = $l->location_name;
                    }
                    $selectedLocIds = $contract->contractLocations->pluck('location_id')->toArray();
                  @endphp

                  @if(!empty($locGroups))
                    @foreach($locGroups as $region => $items)
                      <div class="region-group mb-2" data-region="{{ $region }}">
                        <div class="d-flex align-items-center mb-1">
                          <div class="form-check">
                            <input class="form-check-input region-checkbox" type="checkbox" id="region_{{ \Illuminate\Support\Str::slug($region) }}_locations" data-region="{{ $region }}">
                            <label class="form-check-label fw-bold" for="region_{{ \Illuminate\Support\Str::slug($region) }}_locations">{{ $region }}</label>
                          </div>
                          <div class="ms-auto small text-muted">Select all in region</div>
                        </div>

                        <div class="region-locations">
                          @foreach($items as $id => $name)
                            <div class="form-check ms-3">
                              <input class="form-check-input location-checkbox" type="checkbox" id="loc_{{ $id }}_locations" value="{{ $id }}" data-region="{{ $region }}" {{ in_array($id, $selectedLocIds) ? 'checked' : '' }}>
                              <label class="form-check-label" for="loc_{{ $id }}_locations">{{ $name }}</label>
                            </div>
                          @endforeach
                        </div>
                      </div>
                    @endforeach
                  @else
                    <div class="text-muted small">No locations available</div>
                  @endif
                </div>
              </div>
              <div class="form-text">Select at least one location.</div>
            </div>
          </div>
    
          <div class="row mt-3">
            <div class="col-md-12">
              <label class="form-label">Scope of Services <span class="text-danger">*</span></label>
              <span class="tooltip-helper" data-bs-toggle="tooltip" title="Select services.">?</span>
              <div id="scope_of_services" class="form-check-group mt-2">
                <div class="form-check form-check-inline">
                  <input class="form-check-input scope-service" type="checkbox" value="IP" id="svc-ip" {{ in_array('IP', $scopeVals) ? 'checked' : '' }}>
                  <label class="form-check-label" for="svc-ip">IP</label>
                </div>
    
                <div class="form-check form-check-inline">
                  <input class="form-check-input scope-service" type="checkbox" value="OP" id="svc-op" {{ in_array('OP', $scopeVals) ? 'checked' : '' }}>
                  <label class="form-check-label" for="svc-op">OP</label>
                </div>
    
                <div class="form-check form-check-inline">
                  <input class="form-check-input scope-service" type="checkbox" value="Others" id="svc-others" {{ in_array('Others', $scopeVals) ? 'checked' : '' }}>
                  <label class="form-check-label" for="svc-others">Others</label>
                </div>
    
                <div class="form-check form-check-inline">
                  <input class="form-check-input scope-service" type="checkbox" value="Health Check" id="svc-health" {{ $healthPackages->count() ? 'checked' : '' }}>
                  <label class="form-check-label" for="svc-health">Health Check Packages</label>
                </div>
              </div>
              <div class="invalid-feedback d-block" id="scope_services_error" style="display:none;">Select at least one service.</div>
            </div>
          </div>
    
        </div>
      </div>

      {{-- Discounts --}}
      <div id="discounts_card" class="card mb-4" style="{{ $discounts->count() ? '' : 'display:none;' }}">
        <div class="card-body">
          <h5>Service Breakups</h5>
          <div id="discounts_container" class="mb-2">
            @foreach($discounts as $idx => $d)
              @php
                $roomCharges = parse_json($d->room_charges ?? null);
                $initialSub = $d->subcategory ?? '';
              @endphp
              <div class="discount-row border rounded p-2 mb-2" data-index="{{ $idx }}" data-initial-sub="{{ $initialSub }}">
                <div class="row g-2 align-items-end">
                  <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select class="form-select discount-category" required>
                      <option value="">Choose</option>
                      <option value="IP" {{ ($d->category ?? '') == 'IP' ? 'selected' : '' }}>IP</option>
                      <option value="OP" {{ ($d->category ?? '') == 'OP' ? 'selected' : '' }}>OP</option>
                      <option value="Others" {{ ($d->category ?? '') == 'Others' ? 'selected' : '' }}>Others</option>
                    </select>
                  </div>
    
                  <div class="col-md-4">
                    <label class="form-label">Subcategory</label>
                    <div class="subcategory-wrapper">
                      @if($d->subcategory)
                        <select class="form-select discount-subcategory">
                          <option value="{{ $d->subcategory }}">{{ $d->subcategory }}</option>
                        </select>
                      @else
                        <select class="form-select discount-subcategory"><option value="">Choose</option></select>
                      @endif
                    </div>
                  </div>
    
                  <div class="col-md-3">
                    <label class="form-label discount-percent-label">Discount %</label>
                    <input class="form-control discount-amount" type="number" step="0.01" min="0" required value="{{ $d->discount_percent ?? '' }}">
                  </div>
    
                  <div class="col-md-2 text-end">
                    <button class="btn btn-danger btn-sm remove-discount" title="Remove">×</button>
                  </div>
                </div>
    
                @if(!empty($roomCharges))
                  <div class="room-charges-area mt-2">
                    <div class="room-charges-list">
                      @foreach($roomCharges as $rc)
                        <div class="d-flex gap-2 align-items-center room-charge-row mb-1">
                          @php
                            $name = is_array($rc) ? ($rc['name'] ?? $rc[0] ?? '') : ($rc['name'] ?? '');
                            $price = is_array($rc) ? ($rc['price'] ?? $rc[1] ?? '') : ($rc['price'] ?? '');
                          @endphp
                          <input class="form-control form-control-sm room-charge-name" placeholder="Room category" style="width:40%;" value="{{ $name }}">
                          <input class="form-control form-control-sm room-charge-price" placeholder="Price" type="number" min="0" step="0.01" style="width:30%;" value="{{ $price }}">
                          <button type="button" class="btn btn-sm btn-outline-danger remove-room-charge">Remove</button>
                        </div>
                      @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary add-room-charge">Add Room Charge</button>
                  </div>
                @endif
              </div>
            @endforeach
          </div>
    
          <button type="button" id="add_discount" class="btn btn-sm btn-outline-primary">Add Discount</button>
          <div class="form-text mt-2">Discounts apply to IP/OP/Others (not Health Check Packages).</div>
        </div>
      </div>
    
      {{-- Health packages --}}
      <div id="health_card" class="card mb-4" style="{{ $healthPackages->count() ? '' : 'display:none;' }}">
        <div class="card-body">
          <h5>Health Check Packages</h5>
          <div id="health_rows" class="mb-3">
            @foreach($healthPackages as $i => $hp)
              @php
                $selTests = array_map('strval', parse_json($hp->selected_test_ids ?? null));
                $selConsults = array_map('strval', parse_json($hp->selected_consultation_ids ?? null));
                $consultPrices = is_string($hp->consultation_prices) ? @json_decode($hp->consultation_prices, true) : ($hp->consultation_prices ?? []);
                                
                $othersConsulatation = array_values(
                    array_filter($selConsults, fn($value) => !is_numeric($value))
                );
                
                $othersPrices = array_filter(
                    $consultPrices,
                    fn($value, $key) => !is_numeric($key),
                    ARRAY_FILTER_USE_BOTH
                );                
                if (!is_array($consultPrices)) $consultPrices = [];
                $others = $othersConsulatation ?? [];
                $firstOther = count($others) ? $others[0] : null;
    
                // compute selected tests total using testsMasterMap
                $rowTestsTotal = 0;
                $rowNetTotal = 0;
                foreach ($selTests as $tid) {
                  $rowTestsTotal += floatval($testsMasterMap[(string)$tid] ?? 0);
                  $rowNetTotal += $testsMasterMap[(string)$tid];
                }

                // compute consultation total (selected consultations + others)
                $rowConsultsTotal = 0;
                foreach ($selConsults as $cid) {
                  $rowConsultsTotal += floatval($consultPrices[$cid] ?? 0);
                  $rowNetTotal += $consultPrices[$cid];
                }
                foreach ($others as $o) {
                  $rowConsultsTotal += floatval($o['price'] ?? $o['amount'] ?? 0);
                }
                
    
              @endphp
              <div class="health-row border rounded p-2 mb-2" data-rowid="hp-{{ $hp->id ?? $i }}">
                <div class="">
                  @if(!$isCurrentApproverIsApprover)
                    <div><label class="form-label fw-bold">Row {{ $i + 1 }} — {{ $hp->row_name ?? '' }}</label></div>
                  @else
                    @include('contract::contract-custom.health_packages', ['healthChecks'=>$hp,'consultations'=>$consultations,'tests'=>$tests,'viewTable'=>true, 'viewTestPrice'=>false, 'showSummaryCount'=>false])
                  @endif
                </div>
    
                <div class="row g-2 align-items-center">
                  <div class="col-md-4">
                    <label class="form-label">Package Name</label>
                    <input class="form-control health-row-name" type="text" name="health[{{ $i }}][row_name]" value="{{ $hp->row_name ?? '' }}">
                  </div>
    
                  <div class="col-md-2">
                    <label class="form-label">Package Price</label>
                    <input class="form-control health-row-price" type="number" {{ $isCurrentApproverIsApprover ? 'disabled' : '' }} name="health[{{ $i }}][package_price]" min="0" step="0.01" value="{{ $hp->package_price ?? 0 }}">
                  </div>
    
                  <div class="col-md-6 text-end">
                    <button class="btn btn-sm btn-outline-secondary toggle-components" type="button" data-bs-toggle="collapse" data-bs-target="#tests-hp-{{ $hp->id ?? $i }}" aria-expanded="false" aria-controls="tests-hp-{{ $hp->id ?? $i }}">
                      Components ({{ count($selTests) }} tests, {{ count($selConsults) + (count($others) ? 1 : 0) }} consults)
                    </button>
                  </div>
                </div>
    
                <div class="collapse mt-2 tests-collapse" id="tests-hp-{{ $hp->id ?? $i }}">
                  <div class="health-options">
                    <div class="components-row">
                      <div class="components-col">
                        <div class="components-heading">Tests</div>
                        @if(!empty($tests))
                          @foreach($tests as $t)
                            @php $tid = is_object($t) ? (string)$t->id : (string)($t['id'] ?? ''); $tname = is_object($t) ? $t->name : ($t['name'] ?? ''); @endphp
                            <div class="form-check">
                              <input class="form-check-input test-checkbox" type="checkbox" id="hp-{{ $hp->id ?? $i }}-test-{{ $tid }}" value="{{ $tid }}" {{ in_array($tid, $selTests) ? 'checked' : '' }}>
                              <label class="form-check-label" for="hp-{{ $hp->id ?? $i }}-test-{{ $tid }}">{{ $tname }} {!! $isCurrentApproverIsApprover ? '&nbsp;(<small>₹'. number_format(floatval($testsMasterMap[(string)$tid] ?? 0),2) .'</small>)' : '' !!}</label>
                            </div>
                          @endforeach
                        @else
                          <div class="small text-muted">No test master data available</div>
                        @endif
                      </div>
    
                      <div class="components-col">
                        <div class="consultation-subheading">Consultation</div>
                        @if(!empty($consultations))
                          @foreach($consultations as $cn)
                            @php $cid = is_object($cn) ? (string)$cn->id : (string)($cn['id'] ?? ''); $cname = is_object($cn) ? $cn->name : ($cn['name'] ?? ''); $checked = in_array($cid, $selConsults); $cprice = $checked ? ($consultPrices[$cid] ?? '') : ''; @endphp
                            <div class="consultation-row form-check d-flex align-items-center justify-content-between">
                              <div class="d-flex align-items-center gap-2">
                                <input class="form-check-input consultation-checkbox" type="checkbox" id="hp-{{ $hp->id ?? $i }}-consult-{{ $cid }}" value="{{ $cid }}" {{ $checked ? 'checked' : '' }}>
                                <label class="form-check-label" for="hp-{{ $hp->id ?? $i }}-consult-{{ $cid }}">{{ $cname }}</label>
                              </div>
                              <div class="consultation-price-wrap" style="{{ $checked ? '' : 'display:none;' }}">
                                <input class="form-control form-control-sm consultation-price" type="number" min="0" step="0.01" placeholder="Price" style="width:110px;" value="{{ $cprice }}">
                              </div>
                            </div>
                          @endforeach
                        @else
                          <div class="small text-muted">No consultation master data available</div>
                        @endif
    
                        @php
                          $otherChecked = !empty($firstOther) || (!empty($others) && count($others));
                          $otherDesc = $firstOther ?? '';
                          $otherPrice = $othersPrices[$firstOther] ?? '';
                        @endphp
                        <div class="consultation-others-row d-flex align-items-center justify-content-between mt-2">
                          <div class="d-flex align-items-center gap-2">
                            <input type="checkbox" class="form-check-input consultation-others-checkbox" id="hp-{{ $hp->id ?? $i }}-consult-others" {{ $otherChecked ? 'checked' : '' }}>
                            <label class="form-check-label" for="hp-{{ $hp->id ?? $i }}-consult-others">Others</label>
                          </div>
                          <div class="d-flex gap-2 align-items-center">
                            <input type="text" class="form-control form-control-sm consultation-others-text" placeholder="Description" style="{{ $otherChecked ? '' : 'display:none;' }}" value="{{ $otherDesc }}">
                            <input type="number" class="form-control form-control-sm consultation-others-price" placeholder="Price" min="0" step="0.01" style="{{ $otherChecked ? '' : 'display:none;' }}" value="{{ floatval($otherPrice) }}">
                          </div>
                        </div>
                        @if($isCurrentApproverIsApprover)
                        <div class="mt-2 d-none">
                          <label class="form-label">Override Allocation</label>
                          <input type="number" step="0.01" min="0" name="health[{{ $i }}][override_allocation]" class="form-control" value="{{ $consultPrices['override_allocation'] ?? '' }}">
                          <div class="form-text">Optional: override allocation amount for this package.</div>
                        </div>                    
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
    
                <input type="hidden" class="hp-selected-tests" value='@json($selTests)'>
                <input type="hidden" class="hp-selected-consults" value='@json($selConsults)'>
                <input type="hidden" class="hp-consultation-prices" value='@json($consultPrices)'>
                <input type="hidden" class="hp-selected-others" value='@json($others)'>
              </div>
            @endforeach
          </div>
    
          <button type="button" id="add_health_row" class="btn btn-sm btn-outline-primary">Add Package Row</button>
    
          <div class="mt-3 d-none"><strong>Health Check Net Total: </strong><span id="health_net_total">{{ number_format($overviewSummary['net_total'] ?? 0, 2) }}</span></div>
        </div>
      </div>

      {{-- Tenure & Dates --}}
      <div class="card mb-4">
        <div class="card-body">
          <h5>Tenure & Dates</h5>
          <div class="row">
            <div class="col-md-4">
              <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
              <input type="date" id="start_date" class="form-control" name="start_date" value="{{ old('start_date', $startDateIso) }}">
            </div>
            <div class="col-md-4">
              <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
              <input type="date" id="end_date" class="form-control" name="end_date" value="{{ old('end_date', $contract->contract_end_date ? Carbon::parse($contract->contract_end_date)->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-4">
              <div class="form-check mt-2 mb-2">
                  @php
                    $fixedDate = Carbon::parse($contract->fixed_date);
                    $endDate   = Carbon::parse($contract->contract_end_date);
                    
                    $over2Years = $fixedDate->diffInYears($endDate) >= 2;                  
                  @endphp
                <input class="form-check-input" type="checkbox" id="duration_confirm" name="duration_confirm" {{ (old('duration_confirm', $over2Years) ? 'checked' : '') }}>
                <label class="form-check-label" for="duration_confirm">Confirm longer-than-2-year duration</label>
              </div>
              <div class="form-text text-danger" id="duration_warning" style="display:none;">Selected duration exceeds 2 years. Confirm to proceed.</div>
              <div class="form-text text-danger" id="same_tenure_error" style="display:none;">New contract tenure is identical to old contract tenure. Change the dates or check "Confirm identical tenure".</div>
            </div>
          </div>
        </div>
      </div>
      @if(isset($currentEntry) && ($currentEntry->approver_type_row === 'Approver') && $partyEx->payment_type == 'credit')
        <div class="card mb-4">
          <div class="card-body">
            <div class="row g-2">
                @if($confData && !empty($contract->parentcontract))
                  <div class="col-md-6">
                        
                            <h5>Credit Cell Inputs</h5>
                            <table class="table table-striped">
                                @foreach($confData as $key => $value)
                                    @if(in_array($key, ['current_outstanding', 'recommended_credit_limit', 'recommendation_comments']))
                                        <tr>
                                            <td class="key">
                                                {{ ucwords(str_replace('_', ' ', $key)) }}
                                            </td>
                                            <td class="value">
                                                {{ $value }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </table>
                    </div>
                @endif
                <div class="col-md-6">
                <h5>Approver Credit Details</h5>
                <label for="credit_limit" class="form-label">Credit Limit (₹)</label>
                <input type="number" step="0.01" min="0" id="credit_limit" name="credit_limit" class="form-control" value="{{ old('credit_limit', $confData['credit_limit'] ?? ($confData['recommended_credit_limit'] ?? '') ) }}">
                <label for="credit_days" class="form-label mt-2">Credit Days</label>
                <input type="number" step="1" min="0" id="credit_days" name="credit_days" class="form-control" value="{{ old('credit_days', $confData['credit_days'] ?? '') }}">
                <div id="coc_block" style="display: none;">
                    <label for="coc_ip" class="form-label mt-2">CoC % (IP)</label>
                    <input type="number" step="0.01" min="0" id="coc_ip" name="coc_ip" class="form-control" value="{{ old('coc_ip', $confData['coc_ip'] ?? '') }}">

                    <label for="coc_op" class="form-label mt-2">CoC % (OP)</label>
                    <input type="number" step="0.01" min="0" id="coc_op" name="coc_op" class="form-control" value="{{ old('coc_op', $confData['coc_op'] ?? '') }}">

                    <label for="bank_guarantee" class="form-label mt-2">Bank Guarantee (Amount)</label>
                    <input type="number" step="1" min="0" id="bank_guarantee" name="bank_guarantee" class="form-control" value="{{ old('bank_guarantee', $confData['bank_guarantee'] ?? '') }}">
                </div>
              </div>
            </div>
          </div>
        </div>
      @endif
      
      <div class="card mb-4">
        <div class="card-body">
          <h5>Comments/Notes</h5>

            <div class="col-6">
                <div class="mt-2">
                    <textarea id="contract_notes" class="form-control" name="contract_notes">{{ old('contract_notes', $contract->contract_description) }}</textarea>
                </div>
            </div>          
        </div>
      </div>

      <!-- Prevailing Hospital Tariff & Protocols -->
      <div class="card mb-4">
        <div class="card-body">
          <h5>Prevailing Hospital Tariff & Protocols</h5>

          <div class="row g-2 mb-2">
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="prevailing_hospital_tariff" name="prevailing_hospital_tariff" {{ (old('prevailing_hospital_tariff', $confData['prevailing_hospital_tariff'] ?? false) ? 'checked' : '') }}> 
                <label class="form-check-label" for="prevailing_hospital_tariff">Prevailing Hospital Tariff</label>
              </div>
            </div>
            <div class="col-md-6">
              <label for="prevailing_file" class="form-label">Tariff Document (Word)</label>
              <input type="file" id="prevailing_file" name="prevailing_file" accept=".doc,.docx" class="form-control" {{ (old('prevailing_hospital_tariff', $confData['prevailing_hospital_tariff'] ?? false) ? '' : 'disabled') }} />
              <div class="form-text small">Enable the <strong>Prevailing Hospital Tariff</strong> checkbox to upload a tariff document.</div>
              @if(!empty($confData['prevailing_file_name']))
                <div class="form-text">Uploaded: <a href="{{ asset('storage/' . ($confData['prevailing_file'] ?? '')) }}" target="_blank">{{ $confData['prevailing_file_name'] }}</a></div>
              @endif
            </div>
          </div>

          <div class="row g-2 mt-3">
            <div class="col-md-12">
              <label class="form-label">Communication & Documentation Protocol</label>
              <textarea id="communication_protocol" class="form-control">{{ old('communication_protocol', $confData['communication_protocol'] ?? '') }}</textarea>
            </div>
          </div>

          <div class="row g-2 mt-3">
            <div class="col-md-6">
              @php
                $empDepSelection = old('employees_dependants', $confData['employees_dependants'] ?? null);
                if (!$empDepSelection) {
                  $hasEmployees = !empty($confData['employees'] ?? null);
                  $hasDependants = !empty($confData['dependants'] ?? null);
                  if ($hasEmployees && $hasDependants) $empDepSelection = 'both';
                  elseif ($hasEmployees) $empDepSelection = 'employees';
                  elseif ($hasDependants) $empDepSelection = 'dependants';
                }
              @endphp
              <label class="form-label">Employees / Dependents</label>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="employees_dependants" id="employees_dependants_employees" value="employees" {{ $empDepSelection === 'employees' ? 'checked' : '' }}>
                <label class="form-check-label" for="employees_dependants_employees">Employees</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="employees_dependants" id="employees_dependants_dependants" value="dependants" {{ $empDepSelection === 'dependants' ? 'checked' : '' }}>
                <label class="form-check-label" for="employees_dependants_dependants">Dependents</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="employees_dependants" id="employees_dependants_both" value="both" {{ $empDepSelection === 'both' ? 'checked' : '' }}>
                <label class="form-check-label" for="employees_dependants_both">Both</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="text-muted small mt-4">Select one option to indicate coverage.</div>
            </div>
          </div>

          <div class="row g-2 mt-3">
            <div class="col-12">
              <h6>Sponsors</h6>
              <div id="sponsors_container">
                @php $sponsors = $confData['sponsors'] ?? []; @endphp
                @if(is_array($sponsors) && count($sponsors))
                  @foreach($sponsors as $sp)
                    <div class="sponsor-row d-flex gap-2 mb-2">
                      <input class="form-control sponsor-name" placeholder="Name / Payor" value="{{ $sp['name'] ?? '' }}">
                      <input class="form-control sponsor-sublimit" placeholder="Sublimit (INR/USD)" value="{{ $sp['sublimit'] ?? '' }}">
                      <input class="form-control sponsor-validity" placeholder="Validity" value="{{ $sp['validity'] ?? '' }}">
                      <button type="button" class="btn btn-sm btn-outline-danger remove-sponsor">Remove</button>
                    </div>
                  @endforeach
                @endif
              </div>
              <button type="button" id="add_sponsor" class="btn btn-sm btn-outline-secondary mt-2">Add Sponsor</button>
            </div>
          </div>

        </div>
      </div>      
      
      @php $canEdit = !in_array(strtolower($contract->contract_status ?? ''), ['active','expired','completed','terminated']); @endphp
      <div class="mb-4">
        @if($canEdit)
            @if(isset($currentEntry) && ($currentEntry->approver_type_row === 'Owner') && strtolower($contract->contract_status) == 'draft' && strtolower($contract->substatus) == 'initial draft')
              <button type="button" id="save_draft" class="btn btn-outline-secondary">Update as Draft</button>
            @else
              <button type="button" id="confirm_submit" class="btn btn-outline-primary">Update</button>
            @endif
            @if(isset($currentEntry) && ($currentEntry->approver_type_row === 'Owner') && strtolower($contract->contract_status) == 'draft' && strtolower($contract->substatus) == 'initial draft') 
                <button type="button" id="preview_template_btn" class="btn btn-success">{{ !empty($contract->contract_attachment) ? 'Update' : 'Create' }} Template</button>
            @endif
            <button type="button" id="reset_btn" class="btn btn-secondary">Reset</button>
        @else
            <div class="alert alert-info">Contract cannot be modified in its current state ({{ $contract->contract_status }}).</div>
        @endif
      </div>

      {{-- Preview Modal --}}
      <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="previewModalLabel">Agreement Preview</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="preview_content" role="document">
              <!-- populated by JS -->
            </div>
            <div class="modal-footer">

            @if(isset($currentEntry) && ($currentEntry->approver_type_row === 'Owner') && strtolower($contract->contract_status) == 'draft' && strtolower($contract->substatus) == 'initial draft')
              <button type="button" id="save_draft" class="btn btn-outline-secondary">Update as Draft</button>
            @endif
            @if($canEdit)
              <button type="button" id="confirm_submit" class="btn btn-primary">Update</button>
            @else
              <button type="button" class="btn btn-secondary" disabled>Locked</button>
            @endif
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Edit</button>
            </div>
          </div>
        </div>
      </div>
      {{-- Template Preview Modal --}}
      <div class="modal fade" id="templatePreviewModal" tabindex="-1" aria-labelledby="templatePreviewLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="templatePreviewLabel">Template Preview</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="template_preview_body" role="document">
              <!-- populated by JS -->
            </div>
            <div class="modal-footer">
            @if(isset($currentEntry) && ($currentEntry->approver_type_row === 'Owner') && strtolower($contract->contract_status) == 'draft' && strtolower($contract->substatus) == 'initial draft')
              <button type="button" class="btn btn-primary confirm_approve">{{ !empty($contract->contract_attachment) ? 'Regenerate' : 'Create' }} Template</button>
            @endif                
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
      
      {{-- Response viewer --}}
      <div id="response_viewer" style="display:none;">
        <h3>Server Response (HTML Template)</h3>
        <div id="response_html_container"></div>
        <h5>Extracted Keys</h5>
        <div id="response_extracted"></div>
        <div class="mt-3">
          <button id="back_to_form" class="btn btn-outline-primary">Back to Form</button>
        </div>
      </div>
    
      {{-- Templates required by app.js --}}
      <script type="text/template" id="tpl_discount_row">
        @include('contract::contract-custom._tpl_discount_row')
      </script>
    
      <script type="text/template" id="tpl_health_row">
        @include('contract::contract-custom._tpl_health_row')
      </script>
    </div>
  </div>
</form>

@if(empty($canViewForm) || $isOwner)
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
@if($readonlyForm)
<script>
    const form = document.getElementById("agreement-form");
    
    form.querySelectorAll("input, textarea, select, button:not(.toggle-components,.collapse-toggle)").forEach(el => {
        el.disabled = true;
    });
</script>
@endif