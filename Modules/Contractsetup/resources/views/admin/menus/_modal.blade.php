<div class="modal fade" id="menuModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Menu Configuration</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="menuForm">
        <input type="hidden" id="menu_id" name="id" />
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Menu Type</label>
            <select id="menu_type" name="menu_type" class="form-control">
              <option value="Vertical">Vertical</option>
              <option value="Horizontal">Horizontal</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <input id="role" name="role" class="form-control" />
          </div>
          <div class="mb-3">
            <label class="form-label">Menu JSON</label>
            <textarea id="menu_json" name="menu_json" class="form-control" rows="12"></textarea>
            <small class="text-muted">Paste the JSON structure for the menu. It should match the format expected by the menu partial.</small>
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="active" name="active" checked value=1>
            <label class="form-check-label" for="active">Active</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
