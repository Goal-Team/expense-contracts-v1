<div class="health-row border rounded p-2 mb-2" data-rowid="__ROWID__">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div>
      <label class="form-label fw-bold">Row __NUM__</label>
    </div>
    <div>
      <button class="btn btn-danger btn-sm remove-health-row">Remove</button>
    </div>
  </div>

  <div class="row g-2 align-items-center">
    <div class="col-md-3">
      <label class="form-label">Package Components</label>
      <div class="form-text">Select components below</div>
    </div>
    <div class="col-md-5">
      <label class="form-label">Package Name</label>
      <input class="form-control health-row-name" type="text" placeholder="Package name" value="">
    </div>
    <div class="col-md-3">
      <label class="form-label">Package Price</label>
      <input class="form-control health-row-price" type="number" min="0" step="0.01" value="0.00">
    </div>
  </div>

  <div class="mt-2">
    <button class="btn btn-sm btn-outline-secondary toggle-components" type="button" data-bs-toggle="collapse" data-bs-target="#tests-__ROWID__" aria-expanded="false" aria-controls="tests-__ROWID__">
      Components (0 tests, 0 consults)
    </button>

    <div class="collapse mt-2 tests-collapse" id="tests-__ROWID__">
      <div class="health-options">
        <!-- tests + consultation lists injected by JS -->
      </div>
    </div>
  </div>

  <input type="hidden" class="hp-selected-tests" value='[]'>
  <input type="hidden" class="hp-selected-consults" value='[]'>
  <input type="hidden" class="hp-consultation-prices" value='{}'>
  <input type="hidden" class="hp-selected-others" value='[]'>
</div>