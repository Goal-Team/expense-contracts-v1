/* Full JS for Agreement Creation & Renewal
   - Restores all previous functionality and fixes cloning behavior when "Add Package Row" is clicked.
   - When cloning, the previous row's selected tests, consultation selections and prices, and "Others" are reliably copied.
   - Works with server-rendered rows as well.
*/

(function($){
  const API = {
    customers: APP_URL + '/parties/parties-search',
    entity_types: APP_URL + '/parties/parties-get-entity-types',
    categories: APP_URL + '/assets/json/categories.json',
    subcategories: APP_URL + '/assets/json/subcategories.json',
    tests: APP_URL + '/tests-api/get-list',
    consultations: APP_URL + '/consultations-api/get-list',
    locations: APP_URL + '/contracts/api/locations/contract-custom',
    legacy: APP_URL + '/assets/json/legacy_agreements.json',
    submit: APP_URL + '/contracts/store/contract-custom/'
  };

  // master data (may be provided by server into window.__* or fetched)
    let customers = [], entityTypes = [], categories = [], subcategories = [], tests = [], consultations = [], locations = [], legacyAgreements = [], customerSearchTimer = null;

  // prefer server-injected masters if present
  if (window.__tests && Array.isArray(window.__tests)) tests = window.__tests;
  if (window.__consultations && Array.isArray(window.__consultations)) consultations = window.__consultations;
  if (window.__locations && Array.isArray(window.__locations)) locations = window.__locations;
  if (window.__entityTypes && Array.isArray(window.__entityTypes)) entityTypes = window.__entityTypes;

  const state = { mode: 'new', selectedCustomer: null, legacyData: null };

  let healthRowCounter = 0, oldHealthRowCounter = 0;

  function escapeHtml(text){ if (text === undefined || text === null) return ''; return String(text).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;'); }
  function escapeAttr(val){ if (val === undefined || val === null) return ''; return String(val).replaceAll('"','&quot;').replaceAll("'", '&#x27;'); }

  // ---------- Init ----------
  function init(){
    // attempt to fetch master lists but preserve server-injected arrays if fetch fails
    $.when(
      $.getJSON(API.customers).done(d=>customers=d).fail(()=>customers=[]),
      $.getJSON(API.entity_types).done(d=>entityTypes=d).fail(()=>{/* keep server-injected if present */}),
      $.getJSON(API.categories).done(d=>categories=d).fail(()=>categories=[]),
      $.getJSON(API.subcategories).done(d=>subcategories=d).fail(()=>subcategories=[]),
      $.getJSON(API.tests).done(d=>tests=d).fail(()=>{/* keep server-injected if present */}),
      $.getJSON(API.consultations).done(d=>consultations=d).fail(()=>{/* keep server-injected if present */}),
      $.getJSON(API.locations).done(d=>locations=d).fail(()=>{/* keep server-injected if present */}),
      $.getJSON(API.legacy).done(d=>legacyAgreements=d).fail(()=>legacyAgreements=[])
    ).always(() => {
      populateEntityTypeSelector(null, false);
      populateEntityTypeSelector(null, true);

      if ($('#locations_container').children().length === 0) renderLocations('#locations_container', locations);
      if ($('#old_locations').children().length === 0) renderLocations('#old_locations', locations);

      bindEvents();

      attachBehaviorToExistingDiscountRows();
      attachBehaviorToExistingHealthRows();

      healthRowCounter = $('#health_rows .health-row').length || 0;
      oldHealthRowCounter = $('#old_health_rows .health-row').length || 0;

      if ($('#health_rows .health-row').length === 0) createBlankHealthRow(false);
      if ($('#old_health_rows .health-row').length === 0) createBlankHealthRow(true);

      updateDiscountsAndHealthVisibility();
      updateDiscountRowCategories();
      updateConfirmSameTenureState();
      updateAllLocationCounts();
      computeHealthNetTotal();
      computeHealthNetTotalOld();

      // Initialize Approver 2 totals if needed
      initApprover2TotalsIfNeeded();
      
      // If editing an existing contract, set submit URL to agreementFormUpdate endpoint
      const contractId = $('#contractId').length ? $('#contractId').val() : null;
      if (contractId) {
        API.submit = APP_URL + '/contracts/update/contract-custom/' + contractId;
      }      
    });
  }

  // ---------- Event binding ----------
  function bindEvents(){
    $('input[name=mode]').on('change', function(){
      state.mode = $(this).val();
      if (state.mode === 'renew_upload') { $('#renew-upload-tabs').show(); moveAgreementForm(true); }
      else { moveAgreementForm(false); $('#renew-upload-tabs').hide(); }
      $('#agreement-form').toggle(state.mode === 'new' || state.mode === 'renew_upload');
      updateConfirmSameTenureState();
    });

    $(document).on('change', '.scope-service, .old-scope-service', function(){
      const allowedNew = getAllowedCategories(false);
      const allowedOld = getAllowedCategories(true);
      $('#discounts_container .discount-row').each(function(){
        const $r = $(this);
        const cat = $r.find('.discount-category').val();
        if (cat && allowedNew.length && allowedNew.indexOf(cat) === -1) $r.find('.discount-category').val('').trigger('change');
      });
      $('#old_discounts_container .discount-row').each(function(){
        const $r = $(this);
        const cat = $r.find('.discount-category').val();
        if (cat && allowedOld.length && allowedOld.indexOf(cat) === -1) $r.find('.discount-category').val('').trigger('change');
      });
      updateDiscountsAndHealthVisibility();
      updateDiscountRowCategories();
    });

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', updateConfirmSameTenureState);

    // Prevailing Hospital Tariff: enable file input only when checkbox checked
    $(document).on('change', '#prevailing_hospital_tariff', function(){
      const checked = $(this).is(':checked');
      $('#prevailing_file').prop('disabled', !checked);
      if (!checked) $('#prevailing_file').val('');
    });
    // Initial state
    $('#prevailing_file').prop('disabled', !$('#prevailing_hospital_tariff').is(':checked'));

    $(document).on('input', '.customer-search', function(){
      const $input = $(this);
      const q = ($input.val() || '').trim();
      const suggestionsEl = $input.is('#customer') ? $('#customer_suggestions') : $('#old_customer_suggestions');
      suggestionsEl.empty();
      if (!q) { suggestionsEl.hide(); return; }

      clearTimeout(customerSearchTimer);
      customerSearchTimer = setTimeout(() => {
        $.ajax({
          url: API.customers,
          method: 'GET',
          dataType: 'json',
          data: { q: q },
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '' },
          success: function(data){
            if (!Array.isArray(data) || data.length === 0) {
              const addNewBtn = $("<a href=\""+APP_URL+"/parties/contract-parties-org-add\" target=\"_blank\" class=\"list-group-item list-group-item-action text-center\">➕ Add new customer</a>");
              suggestionsEl.append(addNewBtn).show();
              return;
            }
            data.forEach(r=>{
              const item = $(`<button type="button" class="list-group-item list-group-item-action" data-id="${escapeAttr(r.id)}">${escapeHtml(r.name)} — ${escapeHtml(r.scope || '')}</button>`);
              item.on('click', ()=>{ $input.val(r.name); $input.data('customer', r); suggestionsEl.hide(); onCustomerSelected(r); });
              suggestionsEl.append(item);
            });
            suggestionsEl.show();
          },
          error: function(){ suggestionsEl.hide(); }
        });
      }, 250);
    });

    $(document).on('click', function(e){
      if (!$(e.target).closest('.customer-search, .list-group').length){
        $('#customer_suggestions,#old_customer_suggestions').hide();
      }
    });

    $('#scope, #old_scope').on('change', function(){
      const isOld = $(this).is('#old_scope');
      populateEntityTypeSelector($(this).val(), isOld);
      updateDiscountRowCategories();
      updateDiscountsAndHealthVisibility();
      updateConfirmSameTenureState();
    });
    $('#entity_type, #old_entity_type').on('change', function(){ updateDiscountRowCategories(); });

    $('#add_discount').on('click', addDiscountRow);
    $('#add_discount_old').on('click', addDiscountRowOld);

    $('#discounts_container, #old_discounts_container')
      .on('click', '.remove-discount', function(){ $(this).closest('.discount-row').remove(); })
      .on('click', '.add-room-charge', function(){ setupRoomChargeAdd($(this).closest('.discount-row')); })
      .on('click', '.remove-room-charge', function(){ $(this).closest('.room-charge-row').remove(); })
      .on('change', '.discount-category', function(){ const $row = $(this).closest('.discount-row'); const prevInitial = $row.data('initial-sub') || ''; populateSubcategoriesForRow($row, $(this).val(), prevInitial); });

    $(document).on('input change', '.discount-amount', function(){ toggleDiscountWarning($(this)); });

    $('#add_health_row').on('click', addHealthRow);
    $('#add_health_row_old').on('click', addHealthRowOld);

    $('#health_rows').on('click', '.remove-health-row', function(){ $(this).closest('.health-row').remove(); computeHealthNetTotal(); reindexHealthRows(); });
    $('#old_health_rows').on('click', '.remove-health-row', function(){ $(this).closest('.health-row').remove(); computeHealthNetTotalOld(); reindexHealthRowsOld(); });

    $(document).on('input change', '.health-row-price, .consultation-price, .consultation-others-price', function(){ computeHealthNetTotal(); computeHealthNetTotalOld(); });

    $(document).on('change', '#locations_container .location-checkbox, #old_locations .location-checkbox', function(){
      if ($(this).closest('#locations_container').length) updateLocationCountFor('#locations_container');
      if ($(this).closest('#old_locations').length) updateLocationCountFor('#old_locations');
      // update region checkbox state for the affected container
      if ($(this).closest('#locations_container').length) updateRegionCheckboxStates('#locations_container');
      if ($(this).closest('#old_locations').length) updateRegionCheckboxStates('#old_locations');
    });

    // Region-level select-all (only enabled children). Update counts once for performance and correctness.
    $(document).on('change', '#locations_container .region-checkbox, #old_locations .region-checkbox', function(){
      const $group = $(this).closest('.region-group');
      const desired = $(this).is(':checked');
      const $children = $group.find('.location-checkbox:not(:disabled)');
      if ($children.length) {
        $children.prop('checked', desired);
        const $container = $group.closest('#locations_container, #old_locations');
        if ($container.length) updateLocationCountFor('#' + $container.attr('id'));
        $children.first().trigger('change');
      }
    });

    $('#insert_template').on('click', function(){ insertTemplate($('#template_select').val(), false); });
    $('#insert_template_old').on('click', function(){ insertTemplate($('#template_select_old').val(), true); });

    $('#start_date, #end_date').on('change', function(){ validateDates(); updateConfirmSameTenureState(); });
    $('#start_date_old, #end_date_old').on('change', function(){ validateDatesOld(); updateConfirmSameTenureState(); });

    $('#preview_btn').on('click', onPreview);
    $('#preview_template_btn').on('click', onPreviewTemplate);
    $('#save_draft').on('click', (e) => onSubmitConfirmed(e, true));
    $('#confirm_submit').on('click', onSubmitConfirmed);
    $('.confirm_approve').on('click', onSubmitConfirmed);
    
    // Extend Agreement modal
    $('#extend_agreement_btn').on('click', function(){
      $('#extend_days').val('');
      $('#extend_end_date').val('');
      $('#extend_errors').text('');
      $('#extendAgreementModal').modal('show');
    });

    $('#extend_save').on('click', function(){
      const contractId = $('#contractExtendId').val();
      if(!contractId){ alert('No contract selected'); return; }
      const days = $('#extend_days').val();
      const endDate = $('#extend_end_date').val();
      if(!days && !endDate){ $('#extend_errors').text('Provide number of days or end date'); return; }
      $.ajax({
        url: APP_URL + `/contracts/extend/contract-custom/${contractId}/create`,
        method: 'POST',
        data: { days: days, end_date: endDate, _token: $('meta[name="csrf-token"]').attr('content') },
        success: function(res){
          if(res && res.status){
            alert(res.message || 'Contract extended');
            $('#extendAgreementModal').modal('hide');
            if(res.new_end_date) $('#end_date').val(res.new_end_date);
            window.location.href = APP_URL + '/contracts/list/contract-custom';
          } else {
            $('#extend_errors').text(res.message || 'Failed to extend');
          }
        },
        error: function(xhr){
          var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to extend';
          $('#extend_errors').text(msg);
        }
      });
    });    

    $('#reset_btn').on('click', resetForm);
    $(document).on('click', '#back_to_form', function(){ location.reload(); });

    $('#confirm-old-contract').on('click', function(){ confirmOldContract(true); });
    $('#copy_values_old').on('click', copyOldFormToNew);

    $(document).on('change', '.consultation-checkbox', function(){
      const $r = $(this).closest('.consultation-row');
      const show = $(this).is(':checked');
      $r.find('.consultation-price-wrap').toggle(show);
      if (!show) $r.find('.consultation-price').val('');
      computeHealthNetTotal(); computeHealthNetTotalOld();
    });
    $(document).on('change', '.consultation-others-checkbox', function(){
      const $p = $(this).closest('.consultation-others-row');
      const show = $(this).is(':checked');
      $p.find('.consultation-others-text, .consultation-others-price').toggle(show);
      if (!show) $p.find('.consultation-others-text, .consultation-others-price').val('');
      computeHealthNetTotal(); computeHealthNetTotalOld();
    });
  }

  // ---------- Discount helpers ----------
  function addDiscountRow(){
    const idx = $('#discounts_container .discount-row').length;
    const tpl = $('#tpl_discount_row').html();
    if (!tpl) { console.warn('Missing #tpl_discount_row'); return; }
    const $node = $(tpl.replace(/__IDX__/g, idx));
    $('#discounts_container').append($node);
    setupDiscountRowElement($node, false);
  }

  function addDiscountRowOld(){
    const idx = $('#old_discounts_container .discount-row').length;
    const tpl = $('#tpl_discount_row').html();
    if (!tpl) { console.warn('Missing #tpl_discount_row'); return; }
    const $node = $(tpl.replace(/__IDX__/g, idx));
    $('#old_discounts_container').append($node);
    setupDiscountRowElement($node, true);
  }

  function setupDiscountRowElement($row, isOld){
    const allowed = getAllowedCategories(isOld);
    const $catSel = $row.find('.discount-category');
    if ($catSel.length && $catSel.children().length <= 1){
      $catSel.empty().append('<option value="">Choose</option>');
      (allowed.length ? allowed : ['IP','OP','Others']).forEach(c => $catSel.append(`<option value="${c}">${c}</option>`));
    }
    const initialSub = $row.data('initial-sub') || '';
    populateSubcategoriesForRow($row, $catSel.val(), initialSub);
    toggleDiscountWarning($row.find('.discount-amount'));
  }

  function populateSubcategoriesForRow($row, category, desiredSub = ''){
    const isOldRow = $row.closest('#old_discounts_container').length > 0;
    const isInternational = isOldRow ? ($('#old_scope').val() === 'international') : ($('#scope').val() === 'international');
    const et = Number(isOldRow ? $('#old_entity_type').val() : $('#entity_type').val());
    const $wrapper = $row.find('.subcategory-wrapper');
    const prevSub = desiredSub || ($row.find('.discount-subcategory').val() || $row.data('initial-sub') || '');
    $wrapper.empty();
    if (!category) {
      $wrapper.append('<select class="form-select discount-subcategory"><option value="">Choose</option></select>');
      if (isInternational) {
        $row.find('.discount-amount').prop('disabled', true).hide();
        $row.find('.discount-percent-label').hide();
      } else {
        $row.find('.discount-amount').prop('disabled', true).show();
        $row.find('.discount-percent-label').show();
      }
      $row.find('.room-charges-area').hide();
      return;
    }
    if (category === 'IP') {
      const $sel = $(`<select class="form-select discount-subcategory"><option value="">Choose</option></select>`);
      ['Room charges','Investigation','OT','Professional Fee – Excl Consultation'].forEach(o => $sel.append(`<option value="${escapeAttr(o)}">${escapeHtml(o)}</option>`));
      if (et === 2) $sel.append(`<option value="Room Charges Custom">Room Charges Custom</option>`);
      $wrapper.append($sel);
      if (prevSub) {
        if ($sel.find(`option[value="${escapeAttr(prevSub)}"]`).length > 0) {
          $sel.val(prevSub).trigger('change');
        } else {
          $sel.append(`<option value="${escapeAttr(prevSub)}">${escapeHtml(prevSub)}</option>`);
          $sel.val(prevSub).trigger('change');
        }
        if (prevSub === 'Room Charges Custom') {
          $row.find('.room-charges-area').show();
          $row.find('.discount-amount').hide();
          $row.find('.discount-percent-label').hide();
        }
      }

      if (isInternational) {
        $row.find('.discount-amount').prop('disabled', true).hide();
        $row.find('.discount-percent-label').hide();
      } else {
        $row.find('.discount-amount').prop('disabled', false).show();
        $row.find('.discount-percent-label').show();
      }
      $row.find('.room-charges-area').hide();
    } else if (category === 'OP') {
      const $sel = $(`<select class="form-select discount-subcategory"><option value="">Choose</option></select>`);
      ['Investigation','Consultation'].forEach(o => $sel.append(`<option value="${escapeAttr(o)}">${escapeHtml(o)}</option>`));
      $wrapper.append($sel);
      if (prevSub) {
        if ($sel.find(`option[value="${escapeAttr(prevSub)}"]`).length > 0) {
          $sel.val(prevSub).trigger('change');
        } else {
          $sel.append(`<option value="${escapeAttr(prevSub)}">${escapeHtml(prevSub)}</option>`);
          $sel.val(prevSub).trigger('change');
        }
      }

      if (isInternational) {
        $row.find('.discount-amount').prop('disabled', true).hide();
        $row.find('.discount-percent-label').hide();
      } else {
        $row.find('.discount-amount').prop('disabled', false).show();
        $row.find('.discount-percent-label').show();
      }
      $row.find('.room-charges-area').hide();
    } else if (category === 'Others') {
      $wrapper.append(`<input class="form-control discount-subcategory-text" placeholder="Custom subcategory name">`);
      if (prevSub) $row.find('.discount-subcategory-text').val(prevSub);

      if (isInternational) {
        $row.find('.discount-amount').prop('disabled', true).hide();
        $row.find('.discount-percent-label').hide();
      } else {
        $row.find('.discount-amount').prop('disabled', false).show();
        $row.find('.discount-percent-label').show();
      }
      $row.find('.room-charges-area').hide();
    } else {
      $wrapper.append('<select class="form-select discount-subcategory"><option value="">Choose</option></select>');
      if (prevSub) {
        const $sel = $row.find('.discount-subcategory');
        if ($sel.find(`option[value="${escapeAttr(prevSub)}"]`).length === 0) $sel.append(`<option value="${escapeAttr(prevSub)}">${escapeHtml(prevSub)}</option>`);
        $sel.val(prevSub).trigger('change');
      }
      if (isInternational) {
        $row.find('.discount-amount').prop('disabled', true).hide();
      } else {
        $row.find('.discount-amount').prop('disabled', true).show();
      }
      $row.find('.room-charges-area').hide();
    }

    $row.find('.discount-subcategory').off('change').on('change', function(){
      const val = $(this).val();
      const isInternational = isOldRow ? ($('#old_scope').val() === 'international') : ($('#scope').val() === 'international');
      // Show room charges area when the selected subcategory is "Room Charges Custom" for IP category
      // (support edit cases where the value may be present even if entity type isn't 2)
      if (category === 'IP' && val === 'Room Charges Custom') {
        $row.find('.discount-amount').hide().prop('disabled', true);
        $row.find('.discount-percent-label').hide();
        $row.find('.room-charges-area').show();
      } else {
        if (isInternational) {
          $row.find('.discount-amount').hide().prop('disabled', true);
          $row.find('.discount-percent-label').hide();
        } else {
          $row.find('.discount-amount').show().prop('disabled', false);
          $row.find('.discount-percent-label').show();
        }
        $row.find('.room-charges-area').hide();
      }
    });
  }

  function setupRoomChargeAdd($row){
    const $list = $row.find('.room-charges-list');
    if ($list.length === 0) $row.find('.room-charges-area').prepend('<div class="room-charges-list"></div>');
    const rc = $(`
      <div class="d-flex gap-2 align-items-center room-charge-row mb-1">
        <input class="form-control form-control-sm room-charge-name" placeholder="Room category" style="width:40%;">
        <input class="form-control form-control-sm room-charge-price" placeholder="Price" type="number" min="0" step="0.01" style="width:30%;">
        <button type="button" class="btn btn-sm btn-outline-danger remove-room-charge">Remove</button>
      </div>`);
    $row.find('.room-charges-list').append(rc);
    $row.find('.room-charges-area').show();
  }

    function toggleDiscountWarning($input) {
        const val = parseFloat($input.val()) || 0;
        const $row = $input.closest('.discount-row');
    
        // Append warning only once
        let $warn = $row.find('.discount-warning');
        if (!$warn.length) {
            $warn = $('<div class="discount-warning text-danger small mt-1">Applied discount exceeds policy limits</div>');
            $row.append($warn);
        }
    
        // Toggle visibility
        if (val > 15) {
            $warn.show();
        } else {
            $warn.hide();
        }
    }

  function attachBehaviorToExistingDiscountRows(){
    $('#discounts_container .discount-row').each(function(){ setupDiscountRowElement($(this), false); });
    $('#old_discounts_container .discount-row').each(function(){ setupDiscountRowElement($(this), true); });
  }

  // ---------- Health package helpers ----------
  function addHealthRow(){ const $prev = $('#health_rows .health-row').last(); if ($prev.length) cloneHealthRowFrom($prev, false); else createBlankHealthRow(false); reindexHealthRows(); }
  function addHealthRowOld(){ const $prev = $('#old_health_rows .health-row').last(); if ($prev.length) cloneHealthRowFrom($prev, true); else createBlankHealthRow(true); reindexHealthRowsOld(); }

  function createBlankHealthRow(isOld){
    const rowId = (isOld ? `oh-${Date.now()}-${++oldHealthRowCounter}` : `h-${Date.now()}-${++healthRowCounter}`);
    const idx = (isOld ? $('#old_health_rows .health-row').length : $('#health_rows .health-row').length) + 1;
    const tpl = $('#tpl_health_row').html();
    if (!tpl) return null;
    const html = tpl.replace(/__ROWID__/g, rowId).replace(/__NUM__/g, idx);
    const $tpl = $(html);
    if (isOld) $('#old_health_rows').append($tpl); else $('#health_rows').append($tpl);
    renderHealthOptions($tpl, isOld);
    updateComponentsButton($tpl);
    computeHealthNetTotal(); computeHealthNetTotalOld();
    return $tpl;
  }

  function cloneHealthRowFrom($src, isOld){
    const srcRowName = $src.find('.health-row-name').val() || '';
    const srcRowPrice = $src.find('.health-row-price').val() || '';
    const selTests = $src.find('.test-checkbox:checked').map((i,el)=>String($(el).val())).get();
    const selConsults = $src.find('.consultation-row').map(function(){
      const $cr = $(this);
      if ($cr.find('.consultation-checkbox').is(':checked')) return { id: String($cr.find('.consultation-checkbox').val()), price: $cr.find('.consultation-price').val() || '' };
      return null;
    }).get().filter(Boolean);
    const selOthers = $src.find('.consultation-others-row').map(function(){
      const $or = $(this);
      if ($or.find('.consultation-others-checkbox').is(':checked')) return { description: $or.find('.consultation-others-text').val()||'', price: $or.find('.consultation-others-price').val()||'' };
      return null;
    }).get().filter(Boolean);

    const rowId = (isOld ? `oh-${Date.now()}-${++oldHealthRowCounter}` : `h-${Date.now()}-${++healthRowCounter}`);
    const container = isOld ? '#old_health_rows' : '#health_rows';
    const idx = $(`${container} .health-row`).length + 1;
    const tplHtml = $('#tpl_health_row').html().replace(/__ROWID__/g, rowId).replace(/__NUM__/g, idx);
    const $tpl = $(tplHtml);
    $(container).append($tpl);
    renderHealthOptions($tpl, isOld);

    let attempts = 0;
    const maxAttempts = 200;
    const poll = setInterval(() => {
      attempts++;
      const hasTests = $tpl.find('.test-checkbox').length > 0;
      const hasConsults = $tpl.find('.consultation-checkbox').length > 0 || $tpl.find('.consultation-others-row').length > 0;
      if ((hasTests || selTests.length === 0) && (hasConsults || selConsults.length === 0 || selOthers.length === 0) || attempts >= maxAttempts) {
        $tpl.find('.health-row-name').val(srcRowName);
        $tpl.find('.health-row-price').val(srcRowPrice);
        // copy overhead and approved cost if present on source
        const srcOverhead = $src.find('.overhead-input').val();
        if (srcOverhead !== undefined) $tpl.find('.overhead-input').val(srcOverhead);
        const srcApproved = $src.find('.approved-cost-input').val();
        if (srcApproved !== undefined) $tpl.find('.approved-cost-input').val(srcApproved);

        selTests.forEach(id => {
          const $cb = $tpl.find(`.test-checkbox[value="${id}"]`);
          if ($cb.length) $cb.prop('checked', true).trigger('change');
        });

        selConsults.forEach(c => {
          const $cb = $tpl.find(`.consultation-checkbox[value="${c.id}"]`);
          if ($cb.length) {
            $cb.prop('checked', true);
            $cb.closest('.consultation-row').find('.consultation-price').val(c.price);
            $cb.closest('.consultation-row').find('.consultation-price-wrap').show();
          }
        });

        if (selOthers.length) {
          const o = selOthers[0];
          const $othersRow = $tpl.find('.consultation-others-row');
          if ($othersRow.length) {
            $othersRow.find('.consultation-others-checkbox').prop('checked', true).trigger('change');
            $othersRow.find('.consultation-others-text').val(o.description || '');
            $othersRow.find('.consultation-others-price').val(o.price || '');
          }
        }

        updateComponentsButton($tpl);
        computeHealthNetTotal(); computeHealthNetTotalOld();
        clearInterval(poll);
      }
    }, 25);

    return $tpl;
  }

  function reindexHealthRows(){ $('#health_rows .health-row').each(function(i){ $(this).find('.fw-bold').first().text(`Row ${i+1}`); }); }
  function reindexHealthRowsOld(){ $('#old_health_rows .health-row').each(function(i){ $(this).find('.fw-bold').first().text(`Row ${i+1}`); }); }

  function renderHealthOptions($row, isOld){
    const rowId = $row.attr('data-rowid') || (isOld ? `oh-${Date.now()}-${++oldHealthRowCounter}` : `h-${Date.now()}-${++healthRowCounter}`);
    $row.attr('data-rowid', rowId);

    const $options = $row.find('.health-options');
    $options.empty();

    const $container = $('<div class="components-row"></div>');
    const $testsCol = $(`<div class="components-col"><div class="components-heading">Tests</div></div>`);
    const $consultCol = $(`<div class="components-col"><div class="consultation-subheading">Consultation</div></div>`);

    if (Array.isArray(tests) && tests.length) {
      tests.forEach(t => {
        const tid = t.id ?? t['id'];
        const id = `${isOld ? 'old' : 'new'}-test-${rowId}-${tid}`;
        const item = $(`
          <div class="form-check">
            <input class="form-check-input test-checkbox" type="checkbox" id="${id}" value="${tid}">
            <label class="form-check-label" for="${id}">${escapeHtml(t.name || t['name'] || '')}</label>
          </div>`);
        $testsCol.append(item);
      });
    } else {
      $testsCol.append('<div class="small text-muted">No tests available</div>');
    }

    if (Array.isArray(consultations) && consultations.length) {
      consultations.forEach(c => {
        const cid = c.id ?? c['id'];
        const id = `${isOld ? 'old' : 'new'}-consult-${rowId}-${cid}`;
        const crow = $(`
          <div class="consultation-row form-check d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <input class="form-check-input consultation-checkbox" type="checkbox" id="${id}" value="${cid}">
              <label class="form-check-label me-2" for="${id}">${escapeHtml(c.name || c['name'] || '')}</label>
            </div>
            <div class="consultation-price-wrap" style="display:none; margin-left:6px;">
              <input class="form-control form-control-sm consultation-price" type="number" min="0" step="0.01" placeholder="Price" style="width:110px;">
            </div>
          </div>`);
        $consultCol.append(crow);
      });
    } else {
      $consultCol.append('<div class="small text-muted">No consultations available</div>');
    }

    const othersHtml = $(`
      <div class="consultation-others-row d-flex align-items-center justify-content-between mt-2">
        <div class="d-flex align-items-center gap-2">
          <input type="checkbox" class="form-check-input consultation-others-checkbox" id="${isOld ? 'old' : 'new'}-consult-others-${rowId}">
          <label class="form-check-label" for="${isOld ? 'old' : 'new'}-consult-others-${rowId}">Others</label>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <input type="text" class="form-control form-control-sm consultation-others-text" placeholder="Description" style="width:220px; display:none;">
          <input type="number" class="form-control form-control-sm consultation-others-price" placeholder="Price" min="0" step="0.01" style="width:110px; display:none;">
        </div>
      </div>`);
    $consultCol.append(othersHtml);

    $container.append($testsCol).append($consultCol);
    $options.append($container);

    // wire events for this row
    $row.find('.test-checkbox').off('change').on('change', function(){ updateComponentsButton($row); computeHealthNetTotal(); computeHealthNetTotalOld(); });
    $row.find('.consultation-checkbox').off('change').on('change', function(){ const $r = $(this).closest('.consultation-row'); const show = $(this).is(':checked'); $r.find('.consultation-price-wrap').toggle(show); if (!show) $r.find('.consultation-price').val(''); updateComponentsButton($row); computeHealthNetTotal(); computeHealthNetTotalOld(); });
    $row.find('.consultation-price').off('input change').on('input change', function(){ computeHealthNetTotal(); computeHealthNetTotalOld(); });
    $row.find('.consultation-others-checkbox').off('change').on('change', function(){ const $p = $(this).closest('.consultation-others-row'); const show = $(this).is(':checked'); $p.find('.consultation-others-text, .consultation-others-price').toggle(show); if (!show) $p.find('.consultation-others-text, .consultation-others-price').val(''); updateComponentsButton($row); computeHealthNetTotal(); computeHealthNetTotalOld(); });
    $row.find('.consultation-others-price').off('input change').on('input change', function(){ computeHealthNetTotal(); computeHealthNetTotalOld(); });

    // restore server-provided selections if hidden inputs exist
    try {
      const selTestsHidden = $row.find('.hp-selected-tests').val();
      const selConsultsHidden = $row.find('.hp-selected-consults').val();
      const consultPricesHidden = $row.find('.hp-consultation-prices').val();
      const selOthersHidden = $row.find('.hp-selected-others').val();

      const selTests = selTestsHidden ? JSON.parse(selTestsHidden) : [];
      const selConsults = selConsultsHidden ? JSON.parse(selConsultsHidden) : [];
      const consultPrices = consultPricesHidden ? JSON.parse(consultPricesHidden) : {};
      const selOthers = selOthersHidden ? JSON.parse(selOthersHidden) : [];

      selTests.forEach(id => { $row.find(`.test-checkbox[value="${id}"]`).prop('checked', true); });
      selConsults.forEach(id => {
        const $cb = $row.find(`.consultation-checkbox[value="${id}"]`);
        if ($cb.length) {
          $cb.prop('checked', true);
          $cb.closest('.consultation-row').find('.consultation-price').val(consultPrices && consultPrices[id] !== undefined ? consultPrices[id] : '');
          $cb.closest('.consultation-row').find('.consultation-price-wrap').show();
        }
      });
      if (Array.isArray(selOthers) && selOthers.length) {
        const o = selOthers[0];
        const $othersRow = $row.find('.consultation-others-row');
        if ($othersRow.length) {
          $othersRow.find('.consultation-others-checkbox').prop('checked', true).trigger('change');
          $othersRow.find('.consultation-others-text').val(o.description || o.desc || '');
          $othersRow.find('.consultation-others-price').val(o.price || o.amount || '');
        }
      }
    } catch(e){ /* ignore parse errors */ }

    updateComponentsButton($row);
  }

  function attachBehaviorToExistingHealthRows(){
    $('#health_rows .health-row').each(function(){ const $r = $(this); if ($r.find('.health-options').length === 0) renderHealthOptions($r, false); else { $r.find('.consultation-checkbox').off('change').on('change', function(){ const $row = $(this).closest('.consultation-row'); $row.find('.consultation-price-wrap').toggle($(this).is(':checked')); }); $r.find('.consultation-others-checkbox').off('change').on('change', function(){ const $p = $(this).closest('.consultation-others-row'); $p.find('.consultation-others-text, .consultation-others-price').toggle($(this).is(':checked')); }); } });
    $('#old_health_rows .health-row').each(function(){ const $r = $(this); if ($r.find('.health-options').length === 0) renderHealthOptions($r, true); else { $r.find('.consultation-checkbox').off('change').on('change', function(){ const $row = $(this).closest('.consultation-row'); $row.find('.consultation-price-wrap').toggle($(this).is(':checked')); }); $r.find('.consultation-others-checkbox').off('change').on('change', function(){ const $p = $(this).closest('.consultation-others-row'); $p.find('.consultation-others-text, .consultation-others-price').toggle($(this).is(':checked')); }); } });
  }

  // ---------- Components & totals ----------
  function updateComponentsButton($row){
    if (!$row || $row.length === 0) return;
    const testsSelected = $row.find('.test-checkbox:checked').length;
    const consultSelected = $row.find('.consultation-checkbox:checked').length + ($row.find('.consultation-others-checkbox:checked').length ? 1 : 0);
    const $btn = $row.find('.toggle-components');
    if ($btn.length) $btn.text(`Components (${testsSelected} tests, ${consultSelected} consults)`);
  }

  function computeHealthNetTotal(){
    let net = 0.0;
    $('#health_rows .health-row').each(function(){ net += parseFloat($(this).find('.health-row-price').val()) || 0; });
    //$('#health_net_total').text(net.toFixed(2));
  }

  function computeHealthNetTotalOld(){
    let net = 0.0;
    $('#old_health_rows .health-row').each(function(){ net += parseFloat($(this).find('.health-row-price').val()) || 0; });
    //$('#old_health_net_total').text(net.toFixed(2));
  }

  // ---------- Locations (grouped by region + region-select all support) ----------
  function renderLocations(containerSelector, list){
    const $c = $(containerSelector);
    $c.empty();
    const groups = {};
    (list || []).forEach(loc => {
      const region = (loc && (loc.region || 'Unassigned'));
      if (!groups[region]) groups[region] = [];
      groups[region].push(loc);
    });

    // Debug: log grouping to help diagnose missing DOM elements
    try { if (window && window.console && typeof console.debug === 'function') console.debug('renderLocations:', containerSelector, 'locations:', (list || []).length, 'regions:', Object.keys(groups)); } catch (e) {}

    Object.keys(groups).sort().forEach(region => { 
      const regionId = `region_${region.replace(/[^a-z0-9]/gi,'')}_${containerSelector.replace(/[^a-z]/g,'')}`;
      const $group = $(`<div class="region-group mb-2" data-region="${escapeAttr(region)}"></div>`);
      const $header = $(
        `<div class="d-flex align-items-center mb-1">
          <div class="form-check">
            <input class="form-check-input region-checkbox" type="checkbox" id="${regionId}" data-region="${escapeAttr(region)}">
            <label class="form-check-label fw-bold" for="${regionId}">${escapeHtml(region)}</label>
          </div>
          <div class="ms-auto small text-muted">Select all in region</div>
        </div>`
      );
      $group.append($header);

      const $listDiv = $('<div class="region-locations"></div>');
      groups[region].forEach(loc => {
        const id = `loc_${loc.id}_${containerSelector.replace(/[^a-z]/g,'')}`;
        const item = $(
          `<div class="form-check ms-3">
            <input class="form-check-input location-checkbox" type="checkbox" id="${id}" value="${loc.id}" data-region="${escapeAttr(region)}">
            <label class="form-check-label" for="${id}">${escapeHtml(loc.name)}</label>
          </div>`
        );
        $listDiv.append(item);
      });
      $group.append($listDiv);
      $c.append($group);
    });

    // ensure region checkbox states reflect children selection
    updateRegionCheckboxStates(containerSelector);

    // If there were no regions/locations, append a helpful placeholder so the DOM is predictable
    if (Object.keys(groups).length === 0) {
      $c.append('<div class="text-muted small">No locations available</div>');
    }

    updateLocationCountFor(containerSelector);
  }

  function updateRegionCheckboxStates(containerSelector){
    $(`${containerSelector} .region-group`).each(function(){
      const $g = $(this);
      const $children = $g.find('.location-checkbox');
      const total = $children.length;
      const checked = $children.filter(':checked').length;
      const $regionCb = $g.find('.region-checkbox');
      if (checked === 0) { $regionCb.prop('checked', false).prop('indeterminate', false); }
      else if (checked === total) { $regionCb.prop('checked', true).prop('indeterminate', false); }
      else { $regionCb.prop('checked', false).prop('indeterminate', true); }
    });
  }

  function updateLocationCountFor(containerSelector){
    const selected = $(`${containerSelector} .location-checkbox:checked`).length;
    if (containerSelector === '#locations_container') $('#toggle_locations_btn').text(`Locations (${selected} selected)`);
    else if (containerSelector === '#old_locations') $('#toggle_old_locations_btn').text(`Locations (${selected} selected)`);

    // keep region checkboxes in sync
    updateRegionCheckboxStates(containerSelector);
  }

  function updateAllLocationCounts(){ updateLocationCountFor('#locations_container'); updateLocationCountFor('#old_locations'); }

  // ---------- Entity type & scope ----------
  function populateEntityTypeSelector(scopeOverride, isOld=false, cb){
    const scope = scopeOverride || (isOld ? $('#old_scope').val() : $('#scope').val());
    const target = isOld ? '#old_entity_type' : '#entity_type';
    const $target = $(target);
    const previousSelected = $target.val() || '';
    $target.empty().append('<option value="">Select entity</option>');
    (Array.isArray(entityTypes) ? entityTypes.filter(e => (e.scope ?? '') === (scope ?? '')) : []).forEach(e => $target.append(`<option value="${e.id}">${escapeHtml(e.name)}</option>`));
    if (previousSelected && $target.find(`option[value="${previousSelected}"]`).length) {
      $target.val(previousSelected);
    }
    if (typeof cb === 'function') cb();
  }

  function insertTemplate(key, isOld){
    const editor = isOld ? $('#editor_old') : $('#editor');
    const val = editor.val() || '';
    let ins = '';
    if (key === 'tpl_greeting') ins = "Dear Customer,\n\nThank you for choosing our services.\n\n";
    if (key === 'tpl_scope_summary') ins = "Scope of services includes: [IP], [OP], [Health Check Packages].\n\n";
    editor.val(val + ins);
    editor.focus();
  }

  // ---------- Collect & validate ----------
  function extractDiscountsFrom(containerSelector){
    const discounts = [];
    const scopeSelector = containerSelector.includes('old') ? '#old_scope' : '#scope';
    //if ($(scopeSelector).val() === 'international') return [];

    $(`${containerSelector} .discount-row`).each(function(){
      const $r = $(this);
      const cat = $r.find('.discount-category').val();
      const $subSel = $r.find('.discount-subcategory');
      const sub = ($subSel.length && $subSel.is('select')) ? $subSel.val() : $r.find('.discount-subcategory-text').val();
      const amt = parseFloat($r.find('.discount-amount').val()) || 0;
      const roomCharges = [];
      $r.find('.room-charge-row').each(function(){ roomCharges.push({ name: $(this).find('.room-charge-name').val(), price: parseFloat($(this).find('.room-charge-price').val()) || 0 }); });
      if (cat) discounts.push({ category: cat, subcategory: sub, discount_percent: amt, room_charges: roomCharges });
    });
    return discounts;
  }

  function extractHealthRowsFrom(containerSelector){
    const rows = [];
    $(`${containerSelector} .health-row`).each(function(){
      const $r = $(this);
      const rowName = $r.find('.health-row-name').val() || '';
      const selected_tests = $r.find('.test-checkbox:checked').map((i,el)=>Number($(el).val())).get();
      const package_price = parseFloat($r.find('.health-row-price').val()) || 0;
      const selected_consultation_ids = [];
      const prices = {};
      $r.find('.consultation-row').each(function(){
        const $cr = $(this);
        if ($cr.find('.consultation-checkbox').is(':checked')) {
          const id = $cr.find('.consultation-checkbox').val();
          const priceVal = parseFloat($cr.find('.consultation-price').val()) || 0;
          selected_consultation_ids.push(Number(id));
          prices[id] = priceVal;
        }
      });
      const selected_others = [];
      $r.find('.consultation-others-row').each(function(){
        const $or = $(this);
        if ($or.find('.consultation-others-checkbox').is(':checked')) {
          const desc = $or.find('.consultation-others-text').val() || '';
          const priceVal = parseFloat($or.find('.consultation-others-price').val()) || 0;
          selected_others.push({ description: desc, price: priceVal });
        }
      });

      // overhead and approved cost
      const overheadRaw = $r.find('.overhead-input').val();
      const overhead_allocation = overheadRaw === undefined || overheadRaw === '' ? 0 : (parseFloat(overheadRaw) || 0);
      const approvedRaw = $r.find('.approved-cost-input').val();
      const approved_cost = (approvedRaw === undefined || approvedRaw === '') ? null : (parseFloat(approvedRaw) || 0);

      rows.push({ row_name: rowName, selected_test_ids: selected_tests, package_price, overhead_allocation, approved_cost, selected_consultation_ids, prices, selected_others });
    });
    return rows;
  }

  function collectFormData(){
    const mode = state.mode;
    const isRenewUpload = (mode === 'renew_upload');

    function collectAgreementFields(isOld){
      const agreement_name = isOld ? $('#old_agreement_name').val() : $('#agreement_name').val();
      const customerObj = isOld ? $('#old_customer').data('customer') : $('#customer').data('customer');
      const customer_id = customerObj ? customerObj.contract_party_exe_id : null;
      const scope = isOld ? $('#old_scope').val() : $('#scope').val();
      const entity_type_id = isOld ? $('#old_entity_type').val() : $('#entity_type').val();
      const scope_of_services = $(`${isOld ? '#old_scope_of_services' : '#scope_of_services'} .${isOld ? 'old-scope-service' : 'scope-service'}:checked`).map((i,el)=>$(el).val()).get();
      const discounts = extractDiscountsFrom(isOld ? '#old_discounts_container' : '#discounts_container');
      const health_check_rows = extractHealthRowsFrom(isOld ? '#old_health_rows' : '#health_rows');
      const locations_selected = $(`${isOld ? '#old_locations' : '#locations_container'} .location-checkbox:checked`).map((i,el)=>Number($(el).val())).get();
      const start_date = isOld ? $('#start_date_old').val() : $('#start_date').val();
      const end_date = isOld ? $('#end_date_old').val() : $('#end_date').val();
      const duration_confirmed = isOld ? $('#duration_confirm_old').is(':checked') : $('#duration_confirm').is(':checked');
      const editor_text = isOld ? $('#editor_old').val() : $('#editor').val();
      const credit_limit = isOld ? null : $('#credit_limit').val();
      const credit_days = isOld ? null : $('#credit_days').val();
      const coc_ip = isOld ? null : $('#coc_ip').val();
      const coc_op = isOld ? null : $('#coc_op').val();
      const bank_guarantee = isOld ? null : $('#bank_guarantee').val();
      const contract_notes = isOld ? null : $('#contract_notes').val();

      // Additional fields
      const prevailing_hospital_tariff = isOld ? false : $('#prevailing_hospital_tariff').is(':checked');
      const communication_protocol = isOld ? null : $('#communication_protocol').val();
      const employees_dependants = isOld ? null : ($('input[name="employees_dependants"]:checked').val() || null);

      const sponsors = [];
      $('#sponsors_container .sponsor-row').each(function(){
        sponsors.push({
          name: $(this).find('.sponsor-name').val(),
          sublimit: $(this).find('.sponsor-sublimit').val(),
          validity: $(this).find('.sponsor-validity').val()
        });
      });

      return {
        agreement_name, customer_id,
        scope,
        entity_type_id: entity_type_id ? Number(entity_type_id) : null,
        scope_of_services,
        discounts,
        health_check_rows,
        locations: locations_selected,
        start_date, end_date, duration_confirmed,
        editor_text,
        credit_limit : credit_limit,
        credit_days : credit_days,
        coc_ip : coc_ip,
        coc_op : coc_op,
        bank_guarantee : bank_guarantee,
        contract_notes,
        prevailing_hospital_tariff,
        communication_protocol,
        employees_dependants,
        sponsors
      };
    }

    const newContract = collectAgreementFields(false);
    if (!isRenewUpload) return { renew: false, new_contract: newContract, legacy_files: state.legacyData ? (state.legacyData.files || []) : [] };
    const oldContract = collectAgreementFields(true);
    return { renew: true, old_contract: oldContract, new_contract: newContract, legacy_files: state.legacyData ? (state.legacyData.files || []) : [] };
  }

  // ---------- Validation ----------
  function validateDates(){
    const s = $('#start_date').val(), e = $('#end_date').val();
    if (!s || !e) return true;
    const start = new Date(s), end = new Date(e);
    if (start > end) { $('#end_date')[0].setCustomValidity('End date must be after start date'); $('#end_date').addClass('is-invalid'); return false; }
    $('#end_date')[0].setCustomValidity(''); $('#end_date').removeClass('is-invalid');
    const maxEnd = new Date(start); maxEnd.setFullYear(maxEnd.getFullYear() + 2);
    if (end > maxEnd && !$('#duration_confirm').is(':checked')) { $('#duration_warning').show(); return false; }
    $('#duration_warning').hide(); return true;
  }
  function validateDatesOld(){
    const s = $('#start_date_old').val(), e = $('#end_date_old').val();
    if (!s || !e) return true;
    const start = new Date(s), end = new Date(e);
    if (start > end) { $('#end_date_old')[0].setCustomValidity('End date must be after start date'); $('#end_date_old').addClass('is-invalid'); return false; }
    $('#end_date_old')[0].setCustomValidity(''); $('#end_date_old').removeClass('is-invalid');
    const maxEnd = new Date(start); maxEnd.setFullYear(maxEnd.getFullYear() + 2);
    if (end > maxEnd && !$('#duration_confirm_old').is(':checked')) { $('#duration_warning_old').show(); return false; }
    $('#duration_warning_old').hide(); return true;
  }

  function validateForm(){
    let ok = true;
    $('.is-invalid').removeClass('is-invalid');

    if (!($('#agreement_name').val() || $('#old_agreement_name').val())) { $('#agreement_name').addClass('is-invalid'); ok = false; } else $('#agreement_name').removeClass('is-invalid');
    const cust = $('#customer').data('customer') || $('#old_customer').data('customer');
    if (!cust) { $('#customer').addClass('is-invalid'); ok = false; } else $('#customer').removeClass('is-invalid');
    if (!$('#entity_type').val() && !$('#old_entity_type').val()) { $('#entity_type').addClass('is-invalid'); ok = false; } else $('#entity_type').removeClass('is-invalid');
    const scopeNewVal = $('#scope').val();
    const scopeOldVal = $('#old_scope').val();
    const needNewService = (scopeNewVal !== 'international');
    const needOldService = (scopeOldVal !== 'international' && state.mode === 'renew_upload');
    const newHas = $('#scope_of_services .scope-service:checked').length > 0;
    const oldHas = $('#old_scope_of_services .old-scope-service:checked').length > 0;
    if ((needNewService && !newHas) || (needOldService && !oldHas)) { $('#scope_services_error').show(); ok = false; } else $('#scope_services_error').hide();
    if ($('.location-checkbox:checked').length === 0) { $('#locations_container').addClass('border border-danger p-2'); ok = false; } else $('#locations_container').removeClass('border border-danger p-2');
    if (!$('#start_date').val() || !$('#end_date').val()) { if (!$('#start_date').val()) $('#start_date').addClass('is-invalid'); else $('#start_date').removeClass('is-invalid'); if (!$('#end_date').val()) $('#end_date').addClass('is-invalid'); else $('#end_date').removeClass('is-invalid'); ok = false; } else { $('#start_date,#end_date').removeClass('is-invalid'); if (!validateDates()) ok = false; }
    const oldStart = $('#start_date_old').val(), oldEnd = $('#end_date_old').val(), newStart = $('#start_date').val(), newEnd = $('#end_date').val();
    if (oldStart && oldEnd && newStart && newEnd && oldStart === newStart && oldEnd === newEnd) { if (!$('#confirm_same_tenure').is(':checked')) { $('#same_tenure_error').show(); ok = false; } else $('#same_tenure_error').hide(); } else $('#same_tenure_error').hide();

    // Validate health rows and provide detailed errors
    const errors = [];
    $('#health_rows .health-row').each(function(idx){
      const $r = $(this);
      const rowNum = idx + 1;
      const testsSel = $r.find('.test-checkbox:checked').length;
      const packagePrice = parseFloat($r.find('.health-row-price').val()) || 0;
      if (testsSel > 0 && packagePrice <= 0) {
        errors.push(`Row ${rowNum}: package price must be > 0 because tests are selected.`);
        $r.find('.health-row-price').addClass('is-invalid');
      }
      $r.find('.consultation-row').each(function(){
        const $cr = $(this);
        if ($cr.find('.consultation-checkbox').is(':checked')) {
          const cp = parseFloat($cr.find('.consultation-price').val()) || 0;
          const label = $cr.find('.form-check-label').first().text().trim() || 'Consultation';
          if (cp <= 0) {
            errors.push(`Row ${rowNum}: consultation "${label}" must have a positive price.`);
            $cr.find('.consultation-price').addClass('is-invalid');
          }
        }
      });
      $r.find('.consultation-others-row').each(function(){
        const $or = $(this);
        if ($or.find('.consultation-others-checkbox').is(':checked')) {
          const desc = ($or.find('.consultation-others-text').val() || '').trim();
          const p = parseFloat($or.find('.consultation-others-price').val()) || 0;
          if (!desc) {
            errors.push(`Row ${rowNum}: "Others" must include a description.`);
            $or.find('.consultation-others-text').addClass('is-invalid');
          }
          if (p <= 0) {
            errors.push(`Row ${rowNum}: "Others" must include a positive price.`);
            $or.find('.consultation-others-price').addClass('is-invalid');
          }
        }
      });
    });

    if (errors.length) {
      const $firstInvalid = $('.is-invalid').first();
      if ($firstInvalid && $firstInvalid.length) {
        $('html,body').animate({ scrollTop: $firstInvalid.offset().top - 100 }, 300);
      }
      const message = `Please fix the following validation errors:\n\n- ${errors.join('\n- ')}`;
      alert(message);
      return false;
    }

    return ok;
  }

  // ---------- Confirm same tenure state ----------
  function updateConfirmSameTenureState(){
    const isRenewUpload = ($('input[name=mode]:checked').val() === 'renew_upload');
    const newTabIsShown = $('#new-contract-pane').hasClass('show') || ($('#new-contract-embedded').find('#agreement-form').length && isRenewUpload);
    const oldStart = $('#start_date_old').val();
    const oldEnd = $('#end_date_old').val();
    const newStart = $('#start_date').val();
    const newEnd = $('#end_date').val();
    const datesEqual = oldStart && oldEnd && newStart && newEnd && (oldStart === newStart) && (oldEnd === newEnd);
    const shouldEnable = isRenewUpload && newTabIsShown && datesEqual;
    $('#confirm_same_tenure').prop('disabled', !shouldEnable);
    if (!shouldEnable) { $('#confirm_same_tenure').prop('checked', false); $('#same_tenure_error').hide(); }
  }

  // ---------- Discount & Health visibility ----------
  function getAllowedCategories(isOld){
    const selector = isOld ? '#old_scope_of_services' : '#scope_of_services';
    const checked = $(`${selector} .${isOld ? 'old-scope-service' : 'scope-service'}:checked`).map((i,el)=>$(el).val()).get();
    return checked.filter(v => v === 'IP' || v === 'OP' || v === 'Others');
  }

  function updateDiscountsAndHealthVisibility(){
    const scopeNewVal = $('#scope').val();
    const scopeOldVal = $('#old_scope').val();

    // New contract: if international, keep category/subcategory visible but disable discount inputs
    if (scopeNewVal === 'international') {
      // keep scope options visible for reference
      $('#scope_of_services .scope-service').closest('.form-check').show();
      // Keep discounts card visible so category/subcategory can be selected, but hide/disable the amount inputs
      $('#discounts_card').show();
      $('#add_discount').prop('disabled', false);
      // hide/disable discount inputs on existing rows (edit case)
      $('#discounts_container .discount-row').each(function(){
        $(this).find('.discount-amount').prop('disabled', true).hide();
        $(this).find('.discount-percent-label').hide();
        // also hide room-charges area if present
        $(this).find('.room-charges-area').hide();
      });
      // Show COC IP/OP inputs and set Credit label to USD (match create behavior)
      $('#coc_block').show();
      $('label[for="credit_limit"]').text('Credit Limit (USD)');
    } else {
      $('#scope_of_services .scope-service').closest('.form-check').show();
      const allowedNew = getAllowedCategories(false);
      if (allowedNew && allowedNew.length > 0) {
        $('#discounts_card').show();
        $('#add_discount').prop('disabled', false);
        $('#discounts_container .discount-row').each(function(){
          $(this).find('.discount-amount').prop('disabled', false).show();
          $(this).find('.discount-percent-label').show();
        });
      } else {
        $('#discounts_card').hide();
        $('#add_discount').prop('disabled', true);
      }
      // Hide COC block and set credit label to INR for non-international
      $('#coc_block').hide();
      $('label[for="credit_limit"]').text('Credit Limit (₹)');
    }

    // Old contract: if international, keep category/subcategory visible but disable discount inputs
    if (scopeOldVal === 'international') {
      $('#old_scope_of_services .old-scope-service').closest('.form-check').show();
      $('#old_discounts_card').show();
      $('#add_discount_old').prop('disabled', false);
      $('#old_discounts_container .discount-row').each(function(){
        $(this).find('.discount-amount').prop('disabled', true).hide();
        $(this).find('.discount-percent-label').hide();
        $(this).find('.room-charges-area').hide();
      });
    } else {
      $('#old_scope_of_services .old-scope-service').closest('.form-check').show();
      const allowedOld = getAllowedCategories(true);
      if (allowedOld && allowedOld.length > 0) {
        $('#old_discounts_card').show();
        $('#add_discount_old').prop('disabled', false);
        $('#old_discounts_container .discount-row').each(function(){
          $(this).find('.discount-amount').prop('disabled', false).show();
          $(this).find('.discount-percent-label').show();
        });
      } else {
        $('#old_discounts_card').hide();
        $('#add_discount_old').prop('disabled', true);
      }
    }

    const healthNew = $('#scope_of_services .scope-service[value="Health Check"]').is(':checked');
    const healthOld = $('#old_scope_of_services .old-scope-service[value="Health Check"]').is(':checked');
    $('#health_card').toggle(Boolean(healthNew));
    $('#old_health_card').toggle(Boolean(healthOld));
  }

  function updateDiscountRowCategories(){
    const allowedNew = getAllowedCategories(false);
    const allowedOld = getAllowedCategories(true);
    $('#discounts_container .discount-row').each(function(){
      const $cat = $(this).find('.discount-category');
      const current = $cat.val();
      const opts = (allowedNew.length ? allowedNew : ['IP','OP','Others']);
      $cat.empty().append('<option value="">Choose</option>');
      opts.forEach(o => $cat.append(`<option value="${o}">${o}</option>`));
      if (current && opts.indexOf(current) !== -1) $cat.val(current);
      else if (current) { $cat.val('').trigger('change'); }
    });
    $('#old_discounts_container .discount-row').each(function(){
      const $cat = $(this).find('.discount-category');
      const current = $cat.val();
      const opts = (allowedOld.length ? allowedOld : ['IP','OP','Others']);
      $cat.empty().append('<option value="">Choose</option>');
      opts.forEach(o => $cat.append(`<option value="${o}">${o}</option>`));
      if (current && opts.indexOf(current) !== -1) $cat.val(current);
      else if (current) { $cat.val('').trigger('change'); }
    });
  }

  // ---------- Preview & submit ----------
  function onPreview(){
    if (!validateForm()) return;
    const data = collectFormData();
    $('#preview_content').html(buildPreviewHtml(data));
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
  }
  
  // ---------- Preview & submit ----------
  function onPreviewTemplate(){
    if (!validateForm()) return;
    let contractId = $('#contractId').val();
    const $btn = $('#preview_template_btn');
    $btn.prop('disabled', true).text('Preparing Template...');    
    $.ajax({
      url: APP_URL + `/contracts/approval/contract-custom/${contractId}/preview-template`,
      method: 'POST',
      contentType: 'application/json',
      data: {},
      headers: getAjaxHeaders(),
      success: function(res){
        const modal = new bootstrap.Modal(document.getElementById('templatePreviewModal'));
        $('#template_preview_body').html(res.message);
        modal.show();
        $btn.prop('disabled', false).text('Preview Template');
      },
      error: function(xhr){
        $btn.prop('disabled', false).text('Preview Template');
      }
    });    
  }  

  function buildPreviewHtml(data){
    const lines = [];
    if (data.renew && data.old_contract) {
      lines.push(`<h4>Renewal - New & Old Contracts</h4>`);
      lines.push(`<h5>Old Contract: ${escapeHtml(data.old_contract.agreement_name || '')}</h5>`);
      lines.push(`<p><strong>Old Tenure:</strong> ${escapeHtml(data.old_contract.start_date)} to ${escapeHtml(data.old_contract.end_date)}</p>`);
      lines.push(`<hr/>`);
      lines.push(`<h5>New Contract: ${escapeHtml(data.new_contract.agreement_name || '')}</h5>`);
      lines.push(`<p><strong>New Tenure:</strong> ${escapeHtml(data.new_contract.start_date)} to ${escapeHtml(data.new_contract.end_date)}</p>`);
    } else {
      lines.push(`<h4>${escapeHtml(data.new_contract.agreement_name || '')}</h4>`);
    }
    const cust = customers.find(c=>c.id === (data.new_contract ? data.new_contract.customer_id : data.customer_id));
    lines.push(`<p><strong>Customer:</strong> ${cust ? escapeHtml(cust.name) : '—'}</p>`);
    lines.push(`<p><strong>Scope (new):</strong> ${escapeHtml(data.new_contract.scope || '')}</p>`);
    const ent = entityTypes.find(e=>e.id === (data.new_contract ? data.new_contract.entity_type_id : data.entity_type_id));
    lines.push(`<p><strong>Entity Type:</strong> ${ent ? escapeHtml(ent.name) : '—'}</p>`);
    lines.push(`<p><strong>Locations (new):</strong> ${locations.filter(l=>data.new_contract.locations.includes(l.id)).map(l=>escapeHtml(l.name)).join(', ')}</p>`);

    lines.push(`<h5>Scope of Services (new)</h5><ul>`);
    (data.new_contract.scope_of_services || []).forEach(s => lines.push(`<li>${escapeHtml(s)}</li>`));
    lines.push(`</ul>`);

    // Discounts (new)
    if (data.new_contract && data.new_contract.discounts && data.new_contract.discounts.length) {
      lines.push(`<h5>Discounts (new)</h5>`);
      data.new_contract.discounts.forEach((d, idx) => {
        lines.push(`<div class="border rounded p-2 mb-2"><strong>Discount ${idx+1}</strong>`);
        lines.push(`<p><strong>Category:</strong> ${escapeHtml(d.category || '')}</p>`);
        lines.push(`<p><strong>Subcategory:</strong> ${escapeHtml(d.subcategory || '')}</p>`);
        lines.push(`<p><strong>Discount %:</strong> ${Number(d.discount_percent || 0).toFixed(2)}%</p>`);
        if (d.room_charges && d.room_charges.length) {
          lines.push(`<p><strong>Room Charges:</strong> ${escapeHtml(d.room_charges.map(rc => rc.name + ' @ ' + Number(rc.price || 0).toFixed(2)).join('; '))}</p>`);
        }
        lines.push(`</div>`);
      });
    }

    if (data.old_contract) {
      lines.push(`<h5>Old Contract Summary</h5>`);
      lines.push(`<p><strong>Old Contract Name:</strong> ${escapeHtml(data.old_contract.agreement_name || '')}</p>`);
      lines.push(`<p><strong>Locations (old):</strong> ${locations.filter(l=>data.old_contract.locations.includes(l.id)).map(l=>escapeHtml(l.name)).join(', ')}</p>`);
      
      // Old discounts
      if (data.old_contract.discounts && data.old_contract.discounts.length) {
        lines.push(`<h6>Old Discounts</h6>`);
        data.old_contract.discounts.forEach((d, idx) => {
          lines.push(`<div class="border rounded p-2 mb-2"><strong>Old Discount ${idx+1}</strong>`);
          lines.push(`<p><strong>Category:</strong> ${escapeHtml(d.category || '')}</p>`);
          lines.push(`<p><strong>Subcategory:</strong> ${escapeHtml(d.subcategory || '')}</p>`);
          lines.push(`<p><strong>Discount %:</strong> ${Number(d.discount_percent || 0).toFixed(2)}%</p>`);
          if (d.room_charges && d.room_charges.length) {
            lines.push(`<p><strong>Room Charges:</strong> ${escapeHtml(d.room_charges.map(rc => rc.name + ' @ ' + Number(rc.price || 0).toFixed(2)).join('; '))}</p>`);
          }
          lines.push(`</div>`);
        });
      }
      
      lines.push(`<h6>Old Health Rows</h6>`);
      (data.old_contract.health_check_rows || []).forEach((r,i)=>{
        lines.push(`<div class="border rounded p-2 mb-2"><strong>Old Row ${i+1}</strong>`);
        if (r.row_name) lines.push(`<p>Row name: ${escapeHtml(r.row_name)}</p>`);
        if ((r.selected_test_ids || []).length) {
          const names = (r.selected_test_ids || []).map(id => (tests.find(t=>t.id===id)||{name:'?' }).name);
          lines.push(`<p>Tests: ${escapeHtml(names.join(', '))}</p>`);
          lines.push(`<p>Package Price: ${Number(r.package_price||0).toFixed(2)}</p>`);
        }
        if ((r.selected_consultation_ids || []).length) {
          const names = (r.selected_consultation_ids || []).map(id => (consultations.find(c=>c.id===id)||{name:'?' }).name);
          lines.push(`<p>Consultations: ${escapeHtml(names.join(', '))}</p>`);
        }
        if ((r.selected_others || []).length) {
          lines.push(`<p>Others: ${escapeHtml(r.selected_others.map(o=>o.description + ' @ ' + Number(o.price).toFixed(2)).join('; '))}</p>`);
        }
        lines.push(`</div>`);
      });
      lines.push(`<hr/>`);
    }

    if (data.new_contract && data.new_contract.health_check_rows && data.new_contract.health_check_rows.length){
      lines.push(`<h5>New Health Check Rows</h5>`);
      data.new_contract.health_check_rows.forEach((r,i) => {
        lines.push(`<div class="border rounded p-2 mb-2"><strong>Row ${i+1}</strong>`);
        if (r.row_name) lines.push(`<p>Row name: ${escapeHtml(r.row_name)}</p>`);
        if ((r.selected_test_ids || []).length) {
          const names = (r.selected_test_ids || []).map(id => (tests.find(t=>t.id===id)||{name:'?' }).name);
          lines.push(`<p>Tests: ${escapeHtml(names.join(', '))}</p>`);
          lines.push(`<p>Package Price: ${Number(r.package_price||0).toFixed(2)}</p>`);
        }
        if ((r.selected_consultation_ids || []).length) {
          const names = (r.selected_consultation_ids || []).map(id => (consultations.find(c=>c.id===id)||{name:'?' }).name);
          lines.push(`<p>Consultations: ${escapeHtml(names.join(', '))}</p>`);
        }
        if ((r.selected_others || []).length) {
          lines.push(`<p>Others: ${escapeHtml(r.selected_others.map(o=>o.description + ' @ ' + Number(o.price).toFixed(2)).join('; '))}</p>`);
        }
        lines.push(`</div>`);
      });
      //lines.push(`<p><strong>Health Check Net Total:</strong> ${$('#health_net_total').text()}</p>`);
    }

    lines.push(`<h5>Agreement Text (new)</h5><pre>${escapeHtml($('#editor').val()||'')}</pre>`);
    return lines.join('\n');
  }
  
  function getAjaxHeaders(){
    return {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || ''
    };
  }  

  function onSubmitConfirmed(e, isDraft=false){
    if (!validateForm()) return;
    const payload = collectFormData();
    payload.save_as_draft = isDraft;
    const $btn = $('#confirm_submit');
    $btn.prop('disabled', true).text(isDraft ? 'Saving' : 'Sending...');
    const $btn1 = $('.confirm_approve');
    $btn1.prop('disabled', true).text(isDraft ? 'Saving' : 'Creating...');

    // Build FormData to support file uploads (docx template and prevailing file)
    const formData = new FormData();
    formData.append('payload', JSON.stringify(payload));

    const docxInput = $('#docxFile')[0];
    if (docxInput && docxInput.files && docxInput.files.length) {
      formData.append('docxFile', docxInput.files[0]);
    }
    const prevInput = $('#prevailing_file')[0];
    if (prevInput && prevInput.files && prevInput.files.length) {
      formData.append('prevailing_file', prevInput.files[0]);
    }

    $.ajax({
      url: API.submit,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: getAjaxHeaders(),
      success: function(res){
        try { const modalEl = document.getElementById('previewModal'); const modalInst = bootstrap.Modal.getInstance(modalEl); if (modalInst) modalInst.hide(); } catch(e){}
        $btn.prop('disabled', false).text('Update');
        if (res && res.html_template) {
          showResponseTemplate(res.html_template);
          const extracted = extractKeysFromHtml(res.html_template);
          displayExtractedKeys(extracted);
          return;
        }
        if (typeof res === 'string' && res.trim().startsWith('<')) {
          showResponseTemplate(res);
          const extracted = extractKeysFromHtml(res);
          displayExtractedKeys(extracted);
          return;
        }
        showResponseTemplate('<pre>' + escapeHtml(JSON.stringify(res, null, 2)) + '</pre>');
        displayExtractedKeys({});
        
        if(res.success){
            window.location.reload();
        }        
      },
      error: function(xhr){
        try { const modalEl = document.getElementById('previewModal'); const modalInst = bootstrap.Modal.getInstance(modalEl); if (modalInst) modalInst.hide(); } catch(e){}
        $btn.prop('disabled', false).text('Update & Submit');
        let htmlBody = '';
        try {
          htmlBody = xhr.responseJSON && xhr.responseJSON.html_template ? xhr.responseJSON.html_template : (xhr.responseText || '');
        } catch(e) { htmlBody = xhr.responseText || ''; }
        if (htmlBody && htmlBody.trim().startsWith('<')) {
          showResponseTemplate(htmlBody);
          const extracted = extractKeysFromHtml(htmlBody);
          displayExtractedKeys(extracted);
        } else {
          alert('Submission failed (network or server). Check console for details.');
          console.error('Submission error', xhr);
        }
      }
    });
  }

  function showResponseTemplate(html){
    $('#page_root > *').not('#response_viewer').hide();
    $('#response_html_container').html(html);
    $('#response_viewer').show();
    window.scrollTo({ top: $('#response_viewer').offset().top, behavior: 'smooth' });
  }

  function extractKeysFromHtml(html){
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    const nodes = wrapper.querySelectorAll('[data-key]');
    const extracted = {};
    nodes.forEach(n => {
      const key = n.getAttribute('data-key');
      if (!key) return;
      let value = '';
      if (n.tagName === 'INPUT' || n.tagName === 'SELECT' || n.tagName === 'TEXTAREA') value = n.value;
      else value = n.textContent.trim();
      if (extracted.hasOwnProperty(key)) {
        if (!Array.isArray(extracted[key])) extracted[key] = [extracted[key]];
        extracted[key].push(value);
      } else extracted[key] = value;
    });
    return extracted;
  }

  function displayExtractedKeys(obj){ $('#response_extracted').text(JSON.stringify(obj, null, 2)); console.info('Extracted keys from template:', obj); }

  function resetForm(){ location.reload(); }

  // Sponsors add/remove handlers
  $(document).on('click', '#add_sponsor', function(){
    const row = `<div class="sponsor-row d-flex gap-2 mb-2">
      <input class="form-control sponsor-name" placeholder="Name / Payor">
      <input class="form-control sponsor-sublimit" placeholder="Sublimit (INR/USD)">
      <input class="form-control sponsor-validity" placeholder="Validity">
      <button type="button" class="btn btn-sm btn-outline-danger remove-sponsor">Remove</button>
    </div>`;
    $('#sponsors_container').append(row);
  });
  $(document).on('click', '.remove-sponsor', function(){ $(this).closest('.sponsor-row').remove(); });

  // ---------- Renew helpers ----------
  function confirmOldContract(autoCopy){
    const name = $('#old_agreement_name').val();
    const cust = $('#old_customer').data('customer');
    if (!name || !cust) { alert('Please fill agreement name and select a customer for old contract before confirming.'); return; }
    const matched = legacyAgreements.find(l => l.agreement_name === name || l.customer_id === cust.id);
    state.legacyData = matched || { agreement_name: name, customer_id: cust.id, files: [] };
    const fileInput = $('#old_legacy_file')[0];
    if (fileInput && fileInput.files && fileInput.files.length) {
      const f = fileInput.files[0];
      state.legacyData.files = state.legacyData.files || [];
      state.legacyData.files.push({ filename: f.name, size: f.size });
    }
    if (autoCopy) {
      $('input[name=mode][value="renew_upload"]').prop('checked', true).trigger('change');
      copyOldFormToNew(function(){
        const newTabBtn = document.querySelector('#new-contract-tab');
        const tab = new bootstrap.Tab(newTabBtn);
        tab.show();
        updateConfirmSameTenureState();
      });
    } else {
      $('#copy_values_old').show();
      alert('Old contract confirmed. You may now go to New Contract tab and copy legacy values.');
      const newTabBtn = document.querySelector('#new-contract-tab');
      const tab = new bootstrap.Tab(newTabBtn);
      tab.show();
      updateConfirmSameTenureState();
    }
  }

  function copyOldFormToNew(cb){
    const newHasValues = ($('#agreement_name').val() || $('#customer').data('customer') || $('#discounts_container .discount-row').length > 0 || $('#health_rows .health-row').length > 0);
    if (newHasValues) {
      if (!confirm('Copying values from Old Contract will overwrite some fields in the New Contract form. Proceed?')) {
        if (typeof cb === 'function') cb(false);
        return;
      }
    }

    $('#agreement_name').val($('#old_agreement_name').val());
    const oldCust = $('#old_customer').data('customer');
    if (oldCust) { $('#customer').val(oldCust.name).data('customer', oldCust); onCustomerSelected(oldCust); }

    const oldScope = $('#old_scope').val();
    $('#scope').val(oldScope).trigger('change');
    const oldEntity = $('#old_entity_type').val();
    if (oldEntity) populateEntityTypeSelector(oldScope || $('#scope').val(), false, ()=>$('#entity_type').val(oldEntity).trigger('change'));

    $('#locations_container .location-checkbox').prop('checked', false);
    $('#old_locations .location-checkbox:checked').each(function(){ const v = $(this).val(); $(`#locations_container .location-checkbox[value="${v}"]`).prop('checked', true); });
    updateLocationCountFor('#locations_container');

    $('.scope-service').prop('checked', false);
    $('.old-scope-service:checked').each(function(){ const val = $(this).val(); $(`.scope-service[value="${val}"]`).prop('checked', true); });

    $('#discounts_container').empty();
    $('#old_discounts_container .discount-row').each(function(){
      const $src = $(this);
      const category = $src.find('.discount-category').val();
      const $srcSub = $src.find('.discount-subcategory');
      const srcSubVal = $srcSub.length ? $srcSub.val() : ($src.find('.discount-subcategory-text').val() || '');
      const amt = $src.find('.discount-amount').val();
      const srcRoomCharges = [];
      $src.find('.room-charge-row').each(function(){ srcRoomCharges.push({ name: $(this).find('.room-charge-name').val(), price: $(this).find('.room-charge-price').val() }); });

    // If target scope is international, remove any copied discounts
    if ($('#scope').val() === 'international') {
      $('#discounts_container').empty();
    }

      addDiscountRow();
      const $dst = $('#discounts_container .discount-row').last();
      $dst.find('.discount-category').val(category).trigger('change');

      setTimeout(()=>{
        const $dstSub = $dst.find('.discount-subcategory');
        const $dstText = $dst.find('.discount-subcategory-text');
        if ($dstSub.length && $dstSub.is('select')) {
          if (srcSubVal && $dstSub.find(`option[value="${srcSubVal}"]`).length === 0) $dstSub.append(`<option value="${escapeAttr(srcSubVal)}">${escapeHtml(srcSubVal)}</option>`);
          if (srcSubVal) $dstSub.val(srcSubVal).trigger('change');
        } else if ($dstText.length) {
          $dstText.val(srcSubVal);
        }
        $dst.find('.discount-amount').val(amt);
        if (srcRoomCharges.length) {
          $dst.find('.room-charges-list').empty();
          srcRoomCharges.forEach(rc => { setupRoomChargeAdd($dst); const $last = $dst.find('.room-charge-row').last(); $last.find('.room-charge-name').val(rc.name); $last.find('.room-charge-price').val(rc.price); });
          $dst.find('.room-charges-area').show();
        }
        toggleDiscountWarning($dst.find('.discount-amount'));
      }, 80);
    });

    $('#health_rows').empty();
    $('#old_health_rows .health-row').each(function(){
      const $src = $(this);
      const name = $src.find('.health-row-name').val();
      const price = $src.find('.health-row-price').val();
      addHealthRow();
      const $dst = $('#health_rows .health-row').last();
      $dst.find('.health-row-name').val(name);
      $dst.find('.health-row-price').val(price);
      // copy overhead and approved cost
      $dst.find('.overhead-input').val($src.find('.overhead-input').val() || '0.00');
      $dst.find('.approved-cost-input').val($src.find('.approved-cost-input').val() || '');
      setTimeout(()=> {
        $src.find('.test-checkbox:checked').each(function(){ const val = $(this).val(); $dst.find(`.test-checkbox[value="${val}"]`).prop('checked', true).trigger('change'); });
        $src.find('.consultation-row').each(function(){
          const $sr = $(this);
          if ($sr.find('.consultation-checkbox').is(':checked')) {
            const val = $sr.find('.consultation-checkbox').val();
            const price = $sr.find('.consultation-price').val();
            const $dstCb = $dst.find(`.consultation-checkbox[value="${val}"]`);
            if ($dstCb.length) {
              $dstCb.prop('checked', true).trigger('change');
              $dstCb.closest('.consultation-row').find('.consultation-price').val(price);
            }
          }
        });
        const $srcOthers = $src.find('.consultation-others-row');
        if ($srcOthers.length && $srcOthers.find('.consultation-others-checkbox').is(':checked')) {
          const desc = $srcOthers.find('.consultation-others-text').val();
          const price = $srcOthers.find('.consultation-others-price').val();
          $dst.find('.consultation-others-checkbox').prop('checked', true).trigger('change');
          $dst.find('.consultation-others-text').val(desc);
          $dst.find('.consultation-others-price').val(price);
        }
        updateComponentsButton($dst);
        computeHealthNetTotal();
      }, 150);
    });

    $('#start_date').val($('#start_date_old').val());
    $('#end_date').val($('#end_date_old').val());
    $('#duration_confirm').prop('checked', $('#duration_confirm_old').is(':checked'));
    $('#editor').val($('#editor_old').val());

    updateDiscountsAndHealthVisibility();
    updateDiscountRowCategories();
    updateConfirmSameTenureState();
    updateAllLocationCounts();

    if (typeof cb === 'function') setTimeout(()=>cb(true), 350);
  }

  function onCustomerSelected(r){
    state.selectedCustomer = r;
    if (r.scope) $('#scope, #old_scope').val(r.scope).trigger('change');
    if (r.entityTypeId) {
      populateEntityTypeSelector(r.scope || $('#scope').val(), false, () => $('#entity_type').val(r.entityTypeId).trigger('change'));
      populateEntityTypeSelector(r.scope || $('#old_scope').val(), true, () => $('#old_entity_type').val(r.entityTypeId).trigger('change'));
    } else {
      populateEntityTypeSelector(r.scope || $('#scope').val(), false);
      populateEntityTypeSelector(r.scope || $('#old_scope').val(), true);
    }
    $('.scope-service').prop('disabled', false);
    $('.old-scope-service').prop('disabled', false);
    updateDiscountsAndHealthVisibility();
    updateDiscountRowCategories();
    updateConfirmSameTenureState();
  }

  function moveAgreementForm(toTab){
    const $form = $('#agreement-form');
    if (toTab) {
      if ($('#new-contract-embedded').find('#agreement-form').length === 0) {
        $('#new-contract-embedded').append($form);
      }
      $('#agreement-form').show();
    } else {
      if ($('#main-form-container').find('#agreement-form').length === 0) {
        $('#main-form-container').append($form);
      }
      $('#agreement-form').show();
    }
  }

  // ---------- Approver 2 totals init ----------
  function initApprover2TotalsIfNeeded(){
    // If no rows or no selected-tests-total elements, skip
    if ($('.selected-tests-total').length === 0 && $('.health-row').length === 0) return;
    if (typeof initApprover2Totals === 'function') initApprover2Totals();
  }

  function initApprover2Totals(){
    if (!window || !window.__tests) return;
    function parseNum(v){ v = parseFloat(v); return isNaN(v) ? 0 : v; }
    function recomputeRowTotals($row){
      let testsTotal = 0;
      $row.find('.test-checkbox:checked').each(function(){
        const tid = String($(this).val());
        const t = (window.__tests || []).find(x => String(x.id) === tid);
        const p = t ? (t.price ?? t.default_price ?? 0) : 0;
        testsTotal += parseNum(p);
      });
      let consultTotal = 0;
      $row.find('.consultation-row').each(function(){
        const $cr = $(this);
        if ($cr.find('.consultation-checkbox').is(':checked')) {
          consultTotal += parseNum($cr.find('.consultation-price').val());
        }
      });
      $row.find('.consultation-others-row').each(function(){
        const $or = $(this);
        if ($or.find('.consultation-others-checkbox').is(':checked')) {
          consultTotal += parseNum($or.find('.consultation-others-price').val());
        }
      });
      $row.find('.selected-tests-total').text('₹' + testsTotal.toFixed(2));
      $row.find('.selected-consult-total').text('₹' + consultTotal.toFixed(2));
    }
    $('.health-row').each(function(){ recomputeRowTotals($(this)); });
    $(document).on('change', '.test-checkbox', function(){ recomputeRowTotals($(this).closest('.health-row')); });
    $(document).on('input change', '.consultation-price, .consultation-others-price', function(){ recomputeRowTotals($(this).closest('.health-row')); });
    $(document).on('change', '.consultation-checkbox', function(){ const $r = $(this).closest('.health-row'); const $cr = $(this).closest('.consultation-row'); if ($(this).is(':checked')) $cr.find('.consultation-price-wrap').show(); else $cr.find('.consultation-price-wrap').hide().find('.consultation-price').val(''); recomputeRowTotals($r); });
    $(document).on('change', '.consultation-others-checkbox', function(){ const $r = $(this).closest('.health-row'); const $or = $(this).closest('.consultation-others-row'); if ($(this).is(':checked')) $or.find('.consultation-others-text, .consultation-others-price').show(); else $or.find('.consultation-others-text, .consultation-others-price').hide().val(''); recomputeRowTotals($r); });
  }

  // ---------- Start ----------
  $(document).ready(init);

})(jQuery);