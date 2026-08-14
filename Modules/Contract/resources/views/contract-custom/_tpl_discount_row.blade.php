<div class="discount-row border rounded p-2 mb-2" data-index="__IDX__" data-initial-sub="">
  <div class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label">Category</label>
      <select class="form-select discount-category" required>
        <option value="">Choose</option>
        <option value="IP">IP</option>
        <option value="OP">OP</option>
        <option value="Others">Others</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Subcategory</label>
      <div class="subcategory-wrapper">
        <select class="form-select discount-subcategory"><option value="">Choose</option></select>
      </div>
    </div>
    <div class="col-md-3">
      <label class="form-label discount-percent-label">Discount %</label>
      <input class="form-control discount-amount" type="number" step="0.01" min="0" required>
    </div>
    <div class="col-md-2 text-end">
      <button class="btn btn-danger btn-sm remove-discount" title="Remove">×</button>
    </div>
  </div>

  <div class="room-charges-area mt-2" style="display:none;">
    <div class="room-charges-list"></div>
    <button type="button" class="btn btn-sm btn-outline-secondary add-room-charge">Add Room Charge</button>
  </div>
</div>