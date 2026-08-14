@extends('layouts/layoutMaster')
@section('title', ' New Contract Form')
<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/quill/typography.scss',
'resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/quill/editor.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/dropzone/dropzone.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/tagify/tagify.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/quill/katex.js',
'resources/assets/vendor/libs/quill/quill.js',
'resources/assets/vendor/libs/cleavejs/cleave.js',
'resources/assets/vendor/libs/tagify/tagify.js',
'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
'resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/dropzone/dropzone.js',
'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
'resources/assets/vendor/libs/jquery-sticky/jquery-sticky.js'
])

<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
<link href="{{url('/')}}/Modules/Contract/resources/assets/sass/contractrep.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')

@vite(['resources/assets/js/forms-file-upload.js'])
@vite(['resources/assets/js/form-layouts.js'])

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/blob.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/filesaver.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/htmdocx.js"></script>
<script type="module" src="{{url('/')}}/Modules/ContractParties/resources/assets/js/scriptparty.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contractRep.js"></script>

@endsection
@section('content')

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
   <div class="d-flex flex-column justify-content-center">
      <h4 class="mb-1 mt-3">Add New Contract</h4>
   </div>
   <div class="d-flex align-content-center flex-wrap gap-3">
      <div class="align-right">
         <a href="{{url('/contracts/list/contract-custom')}}" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
      </div>
   </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger sticky-element">
        <h5 class="alert-heading mb-2">Errors Details</h5>
        <ul class="list-unstyled mb-0">
            @foreach ($errors->keys() as $field)
            
                <li class="text-dark {{$field}}"><i class="ti ti-exclamation-circle text-danger"></i> {!! ucwords($errors->first($field)) !!}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="container my-4" id="page_root">
  <h1 class="mb-3">Agreement Creation & Renewal</h1>

  <!-- Top-level control: New vs Renew Existing -->
  <div class="mb-4" role="group" aria-label="Agreement mode" id="mode_controls">
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="mode" id="mode-new" value="new" checked>
      <label class="form-check-label" for="mode-new">Create New Agreement</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="mode" id="mode-renew-upload" value="renew_upload">
      <label class="form-check-label" for="mode-renew-upload">Renew Existing Contract</label>
    </div>
  </div>

  <!-- Renew upload tabset -->
  <div id="renew-upload-tabs" class="mb-4" style="display:none;">
    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="old-contract-tab" data-bs-toggle="tab" data-bs-target="#old-contract" type="button" role="tab" aria-controls="old-contract" aria-selected="true">Existing Contract</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="new-contract-tab" data-bs-toggle="tab" data-bs-target="#new-contract-pane" type="button" role="tab" aria-controls="new-contract-pane" aria-selected="false">New Contract</button>
      </li>
    </ul>
    <div class="tab-content border p-3" id="renewTabsContent">
      <div class="tab-pane fade show active" id="old-contract" role="tabpanel" aria-labelledby="old-contract-tab">
        <form id="old-contract-form" novalidate enctype="multipart/form-data">
          <div class="row mb-2">
            <div class="col-md-6">
              <label for="old_agreement_name" class="form-label">Agreement Name <span class="text-danger">*</span></label>
              <input id="old_agreement_name" name="agreement_name" class="form-control" required>
              <div class="invalid-feedback">Agreement name is required.</div>
            </div>
            <div class="col-md-6 position-relative">
              <label for="old_customer" class="form-label">Customer <span class="text-danger">*</span></label>
              <input id="old_customer" class="form-control customer-search" aria-autocomplete="list" autocomplete="off" required placeholder="Enter Customer Name......">
              <div class="invalid-feedback">Customer is required.</div>
              <div class="list-group position-absolute z-50" id="old_customer_suggestions" style="display:none; max-height:200px; overflow:auto;"></div>
            </div>
          </div>

          <div class="row mb-2">
            <div class="col-md-4 d-none">
              <label for="old_scope" class="form-label">Scope</label>
              <select id="old_scope" class="form-select">
                <option value="">--auto--</option>
                <option value="domestic">Domestic</option>
                <option value="international">International</option>
              </select>
            </div>
            <div class="col-md-4 d-none">
              <label for="old_entity_type" class="form-label">Entity Type <span class="text-danger">*</span></label>
              <select id="old_entity_type" class="form-select" required>
                <option value="">Select entity</option>
              </select>
              <div class="invalid-feedback">Entity type required.</div>
            </div>

            <div class="col-md-4">
              <label for="old_locations_toggle" class="form-label">Locations <span class="text-danger">*</span></label>
              <div>
                <button class="btn btn-sm btn-outline-secondary collapse-toggle" type="button" id="toggle_old_locations_btn" data-bs-toggle="collapse" data-bs-target="#old-locations-collapse" aria-expanded="false" aria-controls="old-locations-collapse">
                  Locations (0 selected)
                </button>
              </div>
              <div class="collapse mt-2" id="old-locations-collapse">
                <div id="old_locations" class="compact-check" aria-live="polite">
                  <!-- filled by JS -->
                </div>
              </div>
            </div>
          </div>

          <!-- Scope of Services (old) -->
          <div class="row mb-3">
            <div class="col-12">
              <label class="form-label">Scope of Services <span class="text-danger">*</span></label>
              <span class="tooltip-helper" data-bs-toggle="tooltip" title="Select services. Discounts visible only when IP/OP/Others selected; Health Check Packages enables Health Check Packages block.">?</span>
              <div id="old_scope_of_services" class="form-check-group mt-2">
              <div class="form-check form-check-inline">
                <input class="form-check-input old-scope-service" type="checkbox" value="IP" id="old_svc-ip" disabled>
                <label class="form-check-label me-3" for="old_svc-ip">IP</label>
                  
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input old-scope-service" type="checkbox" value="OP" id="old_svc-op" disabled>
                <label class="form-check-label me-3" for="old_svc-op">OP</label>
                  
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input old-scope-service" type="checkbox" value="Others" id="old_svc-others" disabled>
                <label class="form-check-label me-3" for="old_svc-others">Others</label>
                  
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input old-scope-service" type="checkbox" value="Health Check" id="old_svc-health" disabled>
                <label class="form-check-label me-3" for="old_svc-health">Health Check Packages</label>
              </div>


              </div>
              <div class="invalid-feedback" id="old_scope_services_error" style="display:none;">Select at least one service.</div>
            </div>
          </div>

          <!-- Discounts (old) -->
          <div id="old_discounts_card" class="card mb-3" style="display:none;">
            <div class="card-body">
              <h6>Service Breakups</h6>
              <div id="old_discounts_container" class="mb-2">
                <!-- rows appended by JS -->
              </div>
              <button type="button" id="add_discount_old" class="btn btn-sm btn-outline-primary">Add Discount</button>
              <div class="form-text mt-2">Discounts apply to IP/OP/Others (not Health Check Packages).</div>
            </div>
          </div>

          <!-- Health Check Packages (old) -->
          <div id="old_health_card" class="card mb-3" style="display:none;">
            <div class="card-body">
              <h6>Health Check Packages</h6>
              <div id="old_health_rows" class="mb-3">
                <!-- health rows inserted by JS -->
              </div>
              <button type="button" id="add_health_row_old" class="btn btn-sm btn-outline-primary">Add Package Row</button>
            </div>
          </div>

          <!-- Tenure & Dates (old) -->
          <div class="card mb-3">
            <div class="card-body">
              <h6>Tenure & Dates</h6>
              <div class="row">
                <div class="col-md-4">
                  <label for="start_date_old" class="form-label">Start Date <span class="text-danger">*</span></label>
                  <input type="date" id="start_date_old" class="form-control" required>
                </div>
                <div class="col-md-4">
                  <label for="end_date_old" class="form-label">End Date <span class="text-danger">*</span></label>
                  <input type="date" id="end_date_old" class="form-control" required>
                </div>
              </div>
            </div>
          </div>

          <!-- Editor & Templates (old) -->

          <div class="mb-2">
            <label for="old_legacy_file" class="form-label">Upload Existing Contract <span class="text-danger">*</span></label>
            <input class="form-control" type="file" id="old_legacy_file" accept=".pdf,.doc,.docx">
            <div class="form-text">Optional: upload the existing contract file to attach to renewal.</div>
          </div>
          
          <!-- Editor & Templates -->
          <div class="card mb-4">
            <div class="card-body">
              <h5>Comments/Notes</h5>
    
                <div class="col-12">
                    <div class="mt-2">
                        <textarea id="contract_notes_old" class="form-control" name="contract_notes_old">{{ old('contract_notes_old') }}</textarea>
                    </div>
                </div>          
            </div>
          </div>          
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" id="confirm-old-contract">Submit Old Contract</button>
            <button type="button" class="btn btn-outline-secondary d-none" id="copy_values_old">Copy Values to New Contract</button>
          </div>
        </form>
      </div>

      <div class="tab-pane fade" id="new-contract-pane" role="tabpanel" aria-labelledby="new-contract-tab">
        <div id="new-contract-embedded">
          <!-- The single primary agreement form will be moved here dynamically when renew_upload is selected -->
        </div>
      </div>
    </div>
  </div>

  <!-- Main form container -->
  <div id="main-form-container">
    <form id="agreement-form" novalidate enctype="multipart/form-data">
      <div class="card mb-4">
        <div class="card-body">
          <h5>Agreement Details</h5>
          <div class="row mb-2">
            <div class="col-md-6">
              <label for="agreement_name" class="form-label">Agreement Name <span class="text-danger">*</span></label>
              <input id="agreement_name" name="agreement_name" class="form-control" required>
              <div class="invalid-feedback">Agreement name is required.</div>
            </div>
            <div class="col-md-6 position-relative">
              <label for="customer" class="form-label">Customer <span class="text-danger">*</span></label>
              <input id="customer" class="form-control customer-search" aria-autocomplete="list" autocomplete="off" required placeholder="Enter Customer Name......">
              <div class="invalid-feedback">Customer is required.</div>
              <div class="list-group position-absolute z-50" id="customer_suggestions" style="display:none; max-height:200px; overflow:auto;"></div>
            </div>
          </div>

          <div class="row mb-2">
            <div class="col-md-3 d-none">
              <label for="scope" class="form-label">Scope</label>
              <select id="scope" class="form-select">
                <option value="">--auto--</option>
                <option value="domestic">Domestic</option>
                <option value="international">International</option>
              </select>
            </div>
            <div class="col-md-3 d-none">
              <label for="entity_type" class="form-label">Entity Type <span class="text-danger">*</span></label>
              <select id="entity_type" class="form-select" required>
                <option value="">Select entity</option>
              </select>
              <div class="invalid-feedback">Entity type required.</div>
            </div>
            <div class="col-md-6">
              <label for="locations_toggle" class="form-label">Locations <span class="text-danger">*</span></label>
              <div>
                <button class="btn btn-sm btn-outline-secondary collapse-toggle" type="button" id="toggle_locations_btn" data-bs-toggle="collapse" data-bs-target="#locations-collapse" aria-expanded="false" aria-controls="locations-collapse">
                  Locations (0 selected)
                </button>
              </div>
              <div class="collapse mt-2" id="locations-collapse">
                <div id="locations_container" class="compact-check" aria-live="polite">
                  <!-- populated by JS -->
                </div>
              </div>
              <div class="form-text">Select at least one location.</div>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-md-12">
              <label class="form-label">Scope of Services <span class="text-danger">*</span></label>
              <span class="tooltip-helper" data-bs-toggle="tooltip" title="Select services. Discounts visible only when IP/OP/Others selected; Health Check Packages enables Health Check Packages block.">?</span>
              <div id="scope_of_services" class="form-check-group mt-2">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input scope-service" type="checkbox" value="IP" id="svc-ip" disabled>
                        <label class="form-check-label me-3" for="svc-ip">IP</label>
                    </div>
                    
                    <div class="form-check form-check-inline">
                        <input class="form-check-input scope-service" type="checkbox" value="OP" id="svc-op" disabled>
                        <label class="form-check-label me-3" for="svc-op">OP</label>
                    </div>
                    
                    <div class="form-check form-check-inline">
                        <input class="form-check-input scope-service" type="checkbox" value="Others" id="svc-others" disabled>
                        <label class="form-check-label me-3" for="svc-others">Others</label>
                    </div>
                    
                    <div class="form-check form-check-inline">
                        <input class="form-check-input scope-service" type="checkbox" value="Health Check" id="svc-health" disabled>
                        <label class="form-check-label me-3" for="svc-health">Health Check Packages</label>
                    </div>

              </div>
              <div class="invalid-feedback" id="scope_services_error" style="display:none;">Select at least one service.</div>
            </div>
          </div>

        </div>
      </div>

      <!-- Discounts -->
      <div id="discounts_card" class="card mb-4" style="display:none;">
        <div class="card-body">
          <h5>Service Breakups</h5>
          <div id="discounts_container" class="mb-2">
            <!-- discount rows appended here -->
          </div>
          <button type="button" id="add_discount" class="btn btn-sm btn-outline-primary">Add Discount</button>
          <div class="form-text mt-2">Discounts apply to IP/OP/Others (not Health Check Packages).</div>
        </div>
      </div>

      <!-- Health Check Packages -->
      <div id="health_card" class="card mb-4" style="display:none;">
        <div class="card-body">
          <h5>Health Check Packages</h5>
          <div id="health_rows" class="mb-3">
            <!-- health rows inserted via JS -->
          </div>
          <button type="button" id="add_health_row" class="btn btn-sm btn-outline-primary">Add Package Row</button>
        </div>
      </div>

      <!-- Tenure & Dates -->
      <div class="card mb-4">
        <div class="card-body">
          <h5>Tenure & Dates</h5>
          <div class="row">
            <div class="col-md-4">
              <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
              <input type="date" id="start_date" class="form-control" required>
              <div class="invalid-feedback">Start date is required.</div>
            </div>
            <div class="col-md-4">
              <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
              <input type="date" id="end_date" class="form-control" required>
              <div class="invalid-feedback">End date is required.</div>
            </div>
            <div class="col-md-4">
              <div class="form-check mt-2 mb-2">
                <input class="form-check-input" type="checkbox" id="duration_confirm">
                <label class="form-check-label" for="duration_confirm">Confirm longer-than-2-year duration</label>
              </div>
              <div class="form-check mt-0">
                <input class="form-check-input" type="checkbox" id="confirm_same_tenure" disabled>
                <label class="form-check-label" for="confirm_same_tenure">Confirm identical tenure to old contract</label>
              </div>
              <div class="form-text text-danger" id="duration_warning" style="display:none;">Selected duration exceeds 2 years. Confirm to proceed.</div>
              <div class="form-text text-danger" id="same_tenure_error" style="display:none;">New contract tenure is identical to old contract tenure. Change the dates or check "Confirm identical tenure" to proceed.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Editor & Templates -->
      <div class="card mb-4">
        <div class="card-body">
          <h5>Editor & Templates</h5>

            <div class="col-12" id="attachments_type_template">
                <div class="mt-2">
                    <div id="template-editor" style="display:none;"></div>
                    <textarea id="template_text" name="template_text" hidden></textarea>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="enable_upload_template">
                        <label class="form-check-label" for="enable_upload_template">Upload custom template</label>
                    </div>
                    <div id="upload_template_controls" class="d-flex gap-2 align-items-center mt-2" style="display:none;">
                        <input type="file" id="docxFile" name="docxFile" style="display:none;">
                        <button type="button" id="upload_template_btn" class="btn btn-sm btn-outline-secondary">Upload Template</button>
                        <small class="form-text ms-2" id="uploaded_template_name"></small>
                    </div>
                </div>
            </div>          
        </div>
      </div>
      <!-- Editor & Templates -->
      <div class="card mb-4">
        <div class="card-body">
          <h5>Comments/Notes</h5>

            <div class="col-6">
                <div class="mt-2">
                    <textarea id="contract_notes" class="form-control" name="contract_notes">{{ old('contract_notes') }}</textarea>
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
                <input class="form-check-input" type="checkbox" id="prevailing_hospital_tariff" name="prevailing_hospital_tariff"> 
                <label class="form-check-label" for="prevailing_hospital_tariff">Prevailing Hospital Tariff</label>
              </div>
            </div>
            <div class="col-md-6">
              <label for="prevailing_file" class="form-label">Tariff Document (Word)</label>
              <input type="file" id="prevailing_file" name="prevailing_file" accept=".doc,.docx" class="form-control" disabled />
              <div class="form-text small">Enable the <strong>Prevailing Hospital Tariff</strong> checkbox to upload a tariff document.</div>
            </div>
          </div>

          <div class="row g-2 mt-3">
            <div class="col-md-12">
              <label class="form-label">Communication & Documentation Protocol</label>
              <textarea id="communication_protocol" class="form-control"></textarea>
            </div>
          </div>

          <div class="row g-2 mt-3">
            <div class="col-md-6">
              <label class="form-label">Employees / Dependents</label>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="employees_dependants" id="employees_dependants_employees" value="employees" {{ old('employees_dependants') === 'employees' ? 'checked' : '' }}>
                <label class="form-check-label" for="employees_dependants_employees">Employees</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="employees_dependants" id="employees_dependants_dependants" value="dependants" {{ old('employees_dependants') === 'dependants' ? 'checked' : '' }}>
                <label class="form-check-label" for="employees_dependants_dependants">Dependents</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="employees_dependants" id="employees_dependants_both" value="both" {{ old('employees_dependants') === 'both' ? 'checked' : '' }}>
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
              <div id="sponsors_container"></div>
              <button type="button" id="add_sponsor" class="btn btn-sm btn-outline-secondary mt-2">Add Sponsor</button>
            </div>
          </div>

        </div>
      </div>

      <div class="mb-4">
        <button type="button" id="save_draft" class="btn btn-primary">Save As Draft</button>
        <button type="button" id="confirm_submit" class="btn btn-warning">Submit</button>
        <button type="button" id="reset_btn" class="btn btn-secondary">Reset</button>
      </div>
    </form>
  </div>

  <!-- response area (hidden until submission) -->
  <div id="response_viewer" style="display:none;">
    <h3>Server Response (HTML Template)</h3>
    <div id="response_html_container"></div>
    <h5>Extracted Keys</h5>
    <div id="response_extracted"></div>
    <div class="mt-3">
      <button id="back_to_form" class="btn btn-outline-primary">Back to Form</button>
    </div>
  </div>

</div>

<!-- Templates for client-side injection -->
<script id="tpl_discount_row" type="text/template">
  <div class="discount-row border rounded p-2 mb-2" data-index="__IDX__">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label">Category</label>
        <select class="form-select discount-category" required>
          <option value="">Choose</option>
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
</script>

<script id="tpl_health_row" type="text/template">
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
      <!-- Toggle shows counts updated by JS -->
      <button class="btn btn-sm btn-outline-secondary toggle-components" type="button" data-bs-toggle="collapse" data-bs-target="#tests-__ROWID__" aria-expanded="false" aria-controls="tests-__ROWID__">
        Components (0 tests, 0 consults)
      </button>

      <div class="collapse mt-2 tests-collapse" id="tests-__ROWID__">
        <div class="health-options">
          <!-- tests + consultation lists injected by JS -->
        </div>
      </div>
    </div>
  </div>
</script>

<!-- Preview modal -->
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
        <button type="button" id="save_draft1" class="btn btn-primary">Save as Draft</button>
        <!-- Requirement 1: change button text -->
        <button type="button" id="confirm_submit1" class="btn btn-warning">Submit</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Edit</button>
      </div>
    </div>
  </div>
</div>


<!-- Form with Image horizontal Modal -->
<div class="modal-onboarding modal fade animate__animated" id="onboardHorizontalImageModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content text-center">
         <div class="modal-header border-0">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            </button>
         </div>
         <div class="modal-body onboarding-horizontal p-0">
             <div class="popap">
               
             </div>
         </div>

      </div>
   </div>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- Inline upload/template script moved to `contractRep.js` for maintainability -->

<script>
document.addEventListener('DOMContentLoaded', function() {
  function toggleConfirmSameTenure() {
    var modeNew = document.getElementById('mode-new');
    var isNew = modeNew && modeNew.checked;
    var checkbox = document.getElementById('confirm_same_tenure');
    if (!checkbox) return;
    // find the nearest wrapper (.form-check) to hide/show label+checkbox
    var wrapper = checkbox.closest('.form-check');
    if (wrapper) {
      wrapper.style.display = isNew ? 'none' : '';
    }
  }

  // Initialize on load
  toggleConfirmSameTenure();

  // Toggle whenever the mode radio changes
  document.querySelectorAll('input[name="mode"]').forEach(function(radio){
    radio.addEventListener('change', toggleConfirmSameTenure);
  });
});
</script>

@endsection