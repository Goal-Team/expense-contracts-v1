/* Full JS for Agreement Creation & Renewal (updated)
   - Populates form from server window.SHOW_DATA when present:
     - discounts, health packages, locations, agreement name, dates, scope, customer
   - Keeps existing behavior (masters fetched from assets/* JSON)
   - Ensures population occurs after master lists are loaded
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
    submit: APP_URL + '/contracts/store/contract-custom' // placeholder - AJAX post target
  };

  function getAjaxHeaders(){
    return {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || ''
    };
  }

  let customers = [];
  let entityTypes = [];
  let categories = [];
  let subcategories = [];
  let tests = [];
  let consultations = [];
  let locations = [];
  let legacyAgreements = [];
  let customerSearchTimer = null;

  const state = {
    mode: 'new',
    selectedCustomer: null,
    legacyData: null
  };

  let healthRowCounter = 0;
  let oldHealthRowCounter = 0;
  var quill;

  function init(){
      
  // Full Toolbar
  // --------------------------------------------------------------------
  const fullToolbar = [
    [
      {
        font: []
      },
      {
        size: []
      }
    ],
    ['bold', 'italic', 'underline', 'strike'],
    [
      {
        color: []
      },
      {
        background: []
      }
    ],
    [
      {
        script: 'super'
      },
      {
        script: 'sub'
      }
    ],
    [
      {
        header: '1'
      },
      {
        header: '2'
      },
      'blockquote',
      'code-block'
    ],
    [
      {
        list: 'ordered'
      },
      {
        list: 'bullet'
      },
      {
        indent: '-1'
      },
      {
        indent: '+1'
      }
    ],
    [{ direction: 'rtl' }],
    ['link', 'image', 'video', 'formula'],
    ['clean']
  ];
  if($('#template-editor').length){
      quill = new Quill('#template-editor', {
        bounds: '#template-editor',
        placeholder: '',
        readOnly: true,
        modules: {
            toolbar: false
        },
        theme: 'bubble',
    });
    
    $('#btn-html-shower').on('click', () => {
        // Get HTML content
        var html = quill.root.innerHTML;
        console.log(html);
    
    }); 
    $('#btn-html-undo').on('click', () => {
        quill.history.undo();
        console.log('undo');
    
    }); 
    $('#btn-html-redo').on('click', () => { 
        quill.history.redo();
    }); 
    $('#btn-doc-downloader').on('click', () => {
        // Get HTML content
        var html = quill.root.innerHTML;
        var converted = htmlDocx.asBlob(('<!DOCTYPE html>' + html));
        saveAs(converted, 'test.docx');
        
    });
    
}
   
   
    // Use $.ajax for each resource so we can add headers
    const requests = [
      $.ajax({ url: API.customers, method: 'GET', dataType: 'json', headers: getAjaxHeaders() }).done(data=>customers=data).fail(()=>customers=[]),
      $.ajax({ url: API.entity_types, method: 'GET', dataType: 'json', headers: getAjaxHeaders() }).done(data=>entityTypes=data).fail(()=>entityTypes=[]),
      $.ajax({ url: API.categories, method: 'GET', dataType: 'json', headers: getAjaxHeaders() }).done(data=>categories=data).fail(()=>categories=[]),
      $.ajax({ url: API.subcategories, method: 'GET', dataType: 'json', headers: getAjaxHeaders() }).done(data=>subcategories=data).fail(()=>subcategories=[]),
      $.ajax({ url: API.tests, method: 'GET', dataType: 'json', headers: getAjaxHeaders() }).done(data=>tests=data).fail(()=>tests=[]),
      $.ajax({ url: API.consultations, method: 'GET', dataType: 'json', headers: getAjaxHeaders() }).done(data=>consultations=data).fail(()=>consultations=[]),
      $.ajax({ url: API.locations, method: 'GET', dataType: 'json', headers: getAjaxHeaders() }).done(data=>locations=data).fail(()=>locations=[]),
      $.ajax({ url: API.legacy, method: 'GET', dataType: 'json', headers: getAjaxHeaders() }).done(data=>legacyAgreements=data).fail(()=>legacyAgreements=[])
    ];

    $.when(...requests).always(() => {
      populateEntityTypeSelector(null, false);
      populateEntityTypeSelector(null, true);
      renderLocations('#locations_container', locations);
      renderLocations('#old_locations', locations);
      bindEvents();

      healthRowCounter = $('#health_rows .health-row').length || 0;
      oldHealthRowCounter = $('#old_health_rows .health-row').length || 0;

      if ($('#health_rows .health-row').length === 0) addHealthRow();
      if ($('#old_health_rows .health-row').length === 0) addHealthRowOld();

      updateDiscountsAndHealthVisibility();
      updateDiscountRowCategories();
      updateConfirmSameTenureState();
      updateAllLocationCounts();
      // Setup template upload UI (moved from inline Blade script)
      setupTemplateUploadUI();
    });
  }
  
      // Fetch Template
    function getTemplateForContract(getOldData=false) {
        if($('#template-editor').length > 0){
            $.ajax({
           url: APP_URL + '/contract-setup/clause/list/template/default',
           type: 'POST',
           data: {contracttype: 13},
           headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           },
           success: function(response) {
                var delta = quill.clipboard.convert(response) ?? "";
                //quill.setContents(delta, 'silent');   
                //quill.root.innerHTML = response;
                
                $.ajax({
                       url: APP_URL + '/contract-setup/customvarlist',
                       type: 'GET',
                       headers: {
                           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                       },
                       success: function(response_) {
                           $('.ql-toolbar').remove();
                           var toolbarOptions_ = {
                            container: [
                              ['bold', 'italic', 'underline', 'strike'],
                              ['blockquote', 'code-block'],
                              [{ 'header': 1 }, { 'header': 2 }],
                              [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                              [{ 'script': 'sub' }, { 'script': 'super' }],
                              [{ 'indent': '-1' }, { 'indent': '+1' }],
                              [{ 'direction': 'rtl' }],
                              [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                              [{ 'color': [] }, { 'background': [] }],
                              [{ 'font': [] }],
                              [{ 'align': [] }],
                              ['clean'],
                              ['link', 'image', 'video'],
                              [{'formvariables': Object.values(response_.data)}],
                            ],
                            handlers: {
                                "formvariables": function (value, elm) { 
                                    if (value) {
                                        const formVarOptions = document.querySelectorAll('.ql-formvariables .ql-picker-item');
                                        var textChoosed = false;
                                        formVarOptions.forEach(function(elmm){
                                            if(elmm.textContent == value){
                                                textChoosed = elmm.dataset.value;
                                            }
                                        });
                                        if(textChoosed){
                                            const cursorPosition = this.quill.getSelection().index;
                                            quill.insertText(cursorPosition, " ");
                                            quill.insertText(cursorPosition + 1, textChoosed, {'customQuillClass': 'highlightQuillCustomVar' });
                                            quill.insertText(cursorPosition + 1 + textChoosed.length, " ");
                                            quill.setSelection(cursorPosition + 1 + textChoosed.length + 1, 0);
                                            //quill.scrollSelectionIntoView();
                                        }
                                    }
                                }
                            }
                          };
                        // const Inline = Quill.import('blots/inline');
                        
                        // class customQuillClass extends Inline {
                        //   static create(value) {
                        //     const node = super.create();
                        //     node.classList.add(value);
                        //     return node;
                        //   }
                        
                        //   static formats(node) {
                        //     return node.className;
                        //   }
                        // }
                        
                        // customQuillClass.blotName = 'customQuillClass';
                        // customQuillClass.tagName = 'span';
                        
                        // Quill.register(customQuillClass);                      
                        quill = new Quill("#template-editor", {
                            modules: {
                              toolbar: false
                            },
                            theme: "snow",
                            readOnly: true
                        });
                        
                        // get html content
                        quill.getHTML = () => {
                          return quill.root.innerHTML;
                        };
                        
                        quill.on('text-change', () => {
                            //console.log('get html', quill.getHTML());
                        });         
                                    
                        // We need to manually supply the HTML content of our custom dropdown list
                        // const placeholderPickerItems = Array.prototype.slice.call(document.querySelectorAll('.ql-formvariables .ql-picker-item'));
                        // const optionCustVars = Object.keys(response_.data);
                        // placeholderPickerItems.forEach((item, $idx) => {item.textContent = item.dataset.value; item.dataset.value = optionCustVars[$idx]});
                        // document.querySelector('.ql-formvariables .ql-picker-label').innerHTML = 'Custom Variable' + document.querySelector('.ql-formvariables .ql-picker-label').innerHTML; 
                        
                        if(getOldData){
                            response = decodeHTML($('#template_text').text());
                        }
                        var delta = quill.clipboard.convert(response);
                        quill.setContents(delta, 'silent');
                
                       },
                       error: function(xhr, status, error) {
                           console.error(xhr.responseText);
                       }
                    });            
           },
           error: function(xhr, status, error) {
               console.error(xhr.responseText);
           }
       });
        }
    } 

  function bindEvents(){
    $('input[name=mode]').on('change', function(){
      state.mode = $(this).val();
      if (state.mode === 'renew_upload') {
        $('#renew-upload-tabs').show();
        moveAgreementForm(true);
      } else {
        moveAgreementForm(false);
        $('#renew-upload-tabs').hide();
      }
      $('#agreement-form').toggle(state.mode === 'new' || state.mode === 'renew_upload');
      updateConfirmSameTenureState();
    });

    $(document).on('change', '.scope-service, .old-scope-service', function(){
      updateDiscountsAndHealthVisibility();
      updateDiscountRowCategories();
    });

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(){ updateConfirmSameTenureState(); });

    $('.customer-search').on('input', function () {
      const $input = $(this);
      const q = ($input.val() || '').trim();
      const suggestionsEl = $input.is('#customer') ? $('#customer_suggestions') : $('#old_customer_suggestions');
      suggestionsEl.empty();

      if (!q) {
        suggestionsEl.hide();
        return;
      }

      // debounce remote search
      clearTimeout(customerSearchTimer);
      customerSearchTimer = setTimeout(() => {
        $.ajax({
          url: API.customers,
          method: 'GET',
          dataType: 'json',
          data: { q: q },
          headers: getAjaxHeaders(),
          success: function(data){
            if (!Array.isArray(data) || data.length === 0) {
              const addNewBtn = $("<a href=\""+APP_URL+"/parties/contract-parties-org-add\" target=\"_blank\" class=\"list-group-item list-group-item-action text-center\">➕ Add new customer</a>");
              suggestionsEl.append(addNewBtn).show();
              return;
            }

            data.forEach(r => {
              const item = $("<button type=\"button\" class=\"list-group-item list-group-item-action\" data-id=\"" + escapeAttr(r.id) + "\">" + escapeHtml(r.name) + " — " + escapeHtml(r.scope || '') + "</button>");
              item.on('click', () => { $input.val(r.name); $input.data('customer', r); suggestionsEl.hide(); onCustomerSelected(r); });
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
      const scope = $(this).val();
      const isOld = $(this).is('#old_scope');
      populateEntityTypeSelector(scope, isOld);
      updateDiscountRowCategories();
      updateDiscountsAndHealthVisibility();
      updateConfirmSameTenureState();
    });

    // Prevailing Hospital Tariff: enable file input only when checkbox checked
    $(document).on('change', '#prevailing_hospital_tariff', function(){
      const checked = $(this).is(':checked');
      $('#prevailing_file').prop('disabled', !checked);
      if (!checked) $('#prevailing_file').val('');
    });
    // Initial state
    $('#prevailing_file').prop('disabled', !$('#prevailing_hospital_tariff').is(':checked'));
    $('#entity_type, #old_entity_type').on('change', function(){
      updateDiscountRowCategories();
      getTemplateForContract();
    });

    $('#add_discount').on('click', function(){ addDiscountRow(); });
    $('#discounts_container').on('click', '.remove-discount', function(){ $(this).closest('.discount-row').remove(); });
    $('#discounts_container').on('change', '.discount-category', function(e, desiredSub){ const $row = $(this).closest('.discount-row'); const prev = typeof desiredSub !== 'undefined' ? desiredSub : ''; populateSubcategoriesForRow($row, $(this).val(), prev); });

    $('#add_discount_old').on('click', function(){ addDiscountRowOld(); });
    $('#old_discounts_container').on('click', '.remove-discount', function(){ $(this).closest('.discount-row').remove(); });
    $('#old_discounts_container').on('change', '.discount-category', function(e, desiredSub){ const $row = $(this).closest('.discount-row'); const prev = typeof desiredSub !== 'undefined' ? desiredSub : ''; populateSubcategoriesForRow($row, $(this).val(), prev); });

    $('#add_health_row').on('click', function(){ addHealthRow(); });
    $('#health_rows').on('click', '.remove-health-row', function(){ $(this).closest('.health-row').remove(); computeHealthNetTotal(); reindexHealthRows(); });
    $('#health_rows').on('input change', '.health-row-price, .consultation-price, .consultation-others-price', computeHealthNetTotal);

    $('#add_health_row_old').on('click', function(){ addHealthRowOld(); });
    $('#old_health_rows').on('click', '.remove-health-row', function(){ $(this).closest('.health-row').remove(); computeHealthNetTotalOld(); reindexHealthRowsOld(); });
    $('#old_health_rows').on('input change', '.health-row-price, .consultation-price, .consultation-others-price', computeHealthNetTotalOld);

    // location checkbox changes -> update the counts on corresponding buttons
    $(document).on('change', '#locations_container .location-checkbox, #old_locations .location-checkbox', function(){
      const $chk = $(this);
      if ($chk.closest('#locations_container').length) updateLocationCountFor('#locations_container');
      if ($chk.closest('#old_locations').length) updateLocationCountFor('#old_locations');
      // keep region checkbox states in sync
      if ($chk.closest('#locations_container').length) updateRegionCheckboxStates('#locations_container');
      if ($chk.closest('#old_locations').length) updateRegionCheckboxStates('#old_locations');
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
        // Fire a single change so other handlers run if they expect it
        $children.first().trigger('change');
      }
    });

    // room charge handlers attached when row created
    $(document).on('click', '.add-room-charge', function(){ const $row = $(this).closest('.discount-row'); setupRoomChargeAdd($row); });
    $(document).on('click', '.remove-room-charge', function(){ $(this).closest('.room-charge-row').remove(); });

    $('#insert_template').on('click', function(){ insertTemplate($('#template_select').val(), false); });
    $('#insert_template_old').on('click', function(){ insertTemplate($('#template_select_old').val(), true); });

    $('#start_date, #end_date').on('change', function(){ validateDates(); updateConfirmSameTenureState(); });
    $('#start_date_old, #end_date_old').on('change', function(){ validateDatesOld(); updateConfirmSameTenureState(); });

    $('#preview_btn').on('click', onPreview);
    
        // Save as Draft button
    $('#save_draft').on('click', (e) => onSubmitConfirmed(e, true));
    // preview modal Save as Draft button (if present)
    $('#save_draft1').on('click', (e) => onSubmitConfirmed(e, true));

    // The preview modal confirm button triggers the same submit handler
    $('#confirm_submit').on('click', onSubmitConfirmed);

    $('#reset_btn').on('click', resetForm);

    $('#confirm-old-contract').on('click', function(){ confirmOldContract(true); });
    $('#copy_values_old').on('click', copyOldFormToNew);

    // Back from response viewer -> reload page
    $(document).on('click', '#back_to_form', function(){
      window.location.href = APP_URL + '/contracts/list/contract-custom';
    });
  }

  function setupRoomChargeAdd($row){
    const $list = $row.find('.room-charges-list');
    const rc = $(`
      <div class="d-flex gap-2 align-items-center room-charge-row mb-1">
        <input class="form-control form-control-sm room-charge-name" placeholder="Room category" style="width:40%;">
        <input class="form-control form-control-sm room-charge-price" placeholder="Price" type="number" min="0" step="0.01" style="width:30%;">
        <button type="button" class="btn btn-sm btn-outline-danger remove-room-charge">Remove</button>
      </div>`);
    $list.append(rc);
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

  function getAllowedCategories(isOld){
    const selector = isOld ? '#old_scope_of_services' : '#scope_of_services';
    const checked = $(`${selector} .${isOld ? 'old-scope-service' : 'scope-service'}:checked`).map((i,el)=>$(el).val()).get();
    return checked.filter(v => v === 'IP' || v === 'OP' || v === 'Others');
  }

  function updateDiscountsAndHealthVisibility(){
    const scopeNewVal = $('#scope').val();
    const scopeOldVal = $('#old_scope').val();

    // New contract: always show scope options; show discounts card only when IP/OP/Others are selected
    $('#scope_of_services .scope-service').closest('.form-check').show();
    const allowedNew = getAllowedCategories(false);
    if (allowedNew && allowedNew.length > 0) {
      $('#discounts_card').show();
      $('#add_discount').prop('disabled', false);
      if (scopeNewVal === 'international') {
        // Hide and disable amount inputs for international scope
        $('#discounts_container .discount-row').each(function(){
          $(this).find('.discount-amount').prop('disabled', true).hide();
          $(this).find('.discount-percent-label').hide();
          $(this).find('.room-charges-area').hide();
        });
        // Show COC IP/OP inputs and set Credit label to USD
        $('#coc_block').show();
        $('label[for="credit_limit"]').text('Credit Limit (USD)');
      } else {
        // Enable amount inputs for domestic or other scopes
        $('#discounts_container .discount-row').each(function(){
          $(this).find('.discount-amount').prop('disabled', false).show();
          $(this).find('.discount-percent-label').show();
        });
        // Hide COC block and set credit label to INR
        $('#coc_block').hide();
        $('label[for="credit_limit"]').text('Credit Limit (₹)');
      }
    } else {
      $('#discounts_card').hide();
      $('#add_discount').prop('disabled', true);
    }

    // Old contract: always show scope options. Show discounts card only when IP/OP/Others are selected.
    $('#old_scope_of_services .old-scope-service').closest('.form-check').show();
    const allowedOld = getAllowedCategories(true);
    if (allowedOld && allowedOld.length > 0) {
      $('#old_discounts_card').show();
      $('#add_discount_old').prop('disabled', false);
      if (scopeOldVal === 'international') {
        // For international old contracts, keep category/subcategory usable but hide/disable amount inputs
        $('#old_discounts_container .discount-row').each(function(){
          $(this).find('.discount-amount').prop('disabled', true).hide();
          $(this).find('.discount-percent-label').hide();
          $(this).find('.room-charges-area').hide();
        });
      } else {
        // Enable amount inputs for domestic or other old scopes
        $('#old_discounts_container .discount-row').each(function(){
          $(this).find('.discount-amount').prop('disabled', false).show();
          $(this).find('.discount-percent-label').show();
        });
      }
    } else {
      $('#old_discounts_card').hide();
      $('#add_discount_old').prop('disabled', true);
    }

    const healthNew = $('#scope_of_services .scope-service[value="Health Check"]').is(':checked');
    const healthOld = $('#old_scope_of_services .old-scope-service[value="Health Check"]').is(':checked');
    $('#health_card').toggle(Boolean(healthNew));
    $('#old_health_card').toggle(Boolean(healthOld));
  }

  function updateDiscountRowCategories(){
    const allowedNew = getAllowedCategories(false);
    const allowedOld = getAllowedCategories(true);
    function rebuild($select, allowed){
      const current = $select.val();
      $select.empty().append('<option value="">Choose</option>');
      if (allowed.length) { allowed.forEach(c => $select.append(`<option value="${c}">${c}</option>`)); } else { ['IP','OP','Others'].forEach(c => $select.append(`<option value="${c}">${c}</option>`)); }
      if (allowed.includes(current)) $select.val(current); else $select.val('');
    }
    $('#discounts_container .discount-row').each(function(){ const $row = $(this); const $cat = $row.find('.discount-category'); rebuild($cat, allowedNew); const prevSub = $row.find('.discount-subcategory').length ? $row.find('.discount-subcategory').val() : ($row.find('.discount-subcategory-text').length ? $row.find('.discount-subcategory-text').val() : ($row.data('initial-sub') || '')); populateSubcategoriesForRow($row, $cat.val(), prevSub); });
    $('#old_discounts_container .discount-row').each(function(){ const $row = $(this); const $cat = $row.find('.discount-category'); rebuild($cat, allowedOld); const prevSub = $row.find('.discount-subcategory').length ? $row.find('.discount-subcategory').val() : ($row.find('.discount-subcategory-text').length ? $row.find('.discount-subcategory-text').val() : ($row.data('initial-sub') || '')); populateSubcategoriesForRow($row, $cat.val(), prevSub); });
  }

  function populateSubcategoriesForRow($row, category, desiredSub = ''){
    const isOldRow = $row.closest('#old_discounts_container').length > 0;
    const isInternational = isOldRow ? ($('#old_scope').val() === 'international') : ($('#scope').val() === 'international');
    const entityTypeVal = isOldRow ? $('#old_entity_type').val() : $('#entity_type').val();
    const et = Number(entityTypeVal);
    const $wrapper = $row.find('.subcategory-wrapper');
    const prevSub = desiredSub || ($row.find('.discount-subcategory').val() || $row.find('.discount-subcategory-text').val() || $row.data('initial-sub') || '');
    $wrapper.empty();
    if (!category) {
      $wrapper.append('<select class="form-select discount-subcategory"><option value="">Choose</option></select>');
      // preserve previous subcategory if we have one
      if (prevSub) {
        const $sel = $wrapper.find('select.discount-subcategory');
        if ($sel.find(`option[value="${escapeAttr(prevSub)}"]`).length === 0) $sel.append(`<option value="${escapeAttr(prevSub)}">${escapeHtml(prevSub)}</option>`);
        $sel.val(prevSub).trigger('change');
      }
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
      $sel.append(`<option value="Room charges">Room charges</option>`);
      $sel.append(`<option value="Investigation">Investigation</option>`);
      $sel.append(`<option value="OT">OT</option>`);
      $sel.append(`<option value="Professional Fee – Excl Consultation">Professional Fee – Excl Consultation</option>`);
      if (et === 2) $sel.append(`<option value="Room Charges Custom">Room Charges Custom</option>`);
      $wrapper.append($sel);

      // if we already have a desired previous subcategory, select it (or append it if missing)
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
      if (!(prevSub === 'Room Charges Custom')) $row.find('.room-charges-area').hide();
    } else if (category === 'OP') {
      const $sel = $(`<select class="form-select discount-subcategory"><option value="">Choose</option></select>`);
      $sel.append(`<option value="Investigation">Investigation</option>`);
      $sel.append(`<option value="Consultation">Consultation</option>`);
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
      const $input = $(`<input class="form-control discount-subcategory-text" placeholder="Custom subcategory name">`);
      $wrapper.append($input);
      if (prevSub) $input.val(prevSub);

      if (isInternational) {
        $row.find('.discount-amount').prop('disabled', true).hide();
        $row.find('.discount-percent-label').hide();
      } else {
        $row.find('.discount-amount').prop('disabled', false).show();
        $row.find('.discount-percent-label').show();
      }
      $row.find('.room-charges-area').hide();
    } else {
      const $sel = $('<select class="form-select discount-subcategory"><option value="">Choose</option></select>');
      $wrapper.append($sel);
      if (prevSub) {
        $sel.append(`<option value="${escapeAttr(prevSub)}">${escapeHtml(prevSub)}</option>`);
        $sel.val(prevSub).trigger('change');
      }
      if (isInternational) {
        $row.find('.discount-amount').prop('disabled', true).hide();
      } else {
        $row.find('.discount-amount').prop('disabled', true).show();
      }
      $row.find('.room-charges-area').hide();
    }

    $row.find('.discount-subcategory').on('change', function(){
      const val = $(this).val();
      const isInternational = isOldRow ? ($('#old_scope').val() === 'international') : ($('#scope').val() === 'international');
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

  function addDiscountRow(){
    const idx = $('#discounts_container .discount-row').length;
    const tpl = $($('#tpl_discount_row').html().replace(/__IDX__/g, idx));
    setupDiscountRow(tpl, false, getAllowedCategories(false));
    $('#discounts_container').append(tpl);
  }

  function addDiscountRowOld(){
    const idx = $('#old_discounts_container .discount-row').length;
    const tpl = $($('#tpl_discount_row').html().replace(/__IDX__/g, idx));
    setupDiscountRow(tpl, true, getAllowedCategories(true));
    $('#old_discounts_container').append(tpl);
  }

  function setupDiscountRow($row, isOld, allowedCats){
    const $catSel = $row.find('.discount-category');
    $catSel.empty().append('<option value="">Choose</option>');
    if (allowedCats && allowedCats.length) { allowedCats.forEach(c => $catSel.append(`<option value="${c}">${c}</option>`)); } else ['IP','OP','Others'].forEach(c => $catSel.append(`<option value="${c}">${c}</option>`));
    populateSubcategoriesForRow($row, $catSel.val());
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


  function addHealthRow(){ const $prev = $('#health_rows .health-row').last(); if ($prev.length) cloneHealthRowFrom($prev, false); else createBlankHealthRow(false); reindexHealthRows(); }
  function addHealthRowOld(){ const $prev = $('#old_health_rows .health-row').last(); if ($prev.length) cloneHealthRowFrom($prev, true); else createBlankHealthRow(true); reindexHealthRowsOld(); }

  function createBlankHealthRow(isOld){
    const rowId = (isOld ? `oh-${Date.now()}-${++oldHealthRowCounter}` : `h-${Date.now()}-${++healthRowCounter}`);
    const idx = (isOld ? $('#old_health_rows .health-row').length : $('#health_rows .health-row').length) + 1;
    const tplHtml = $('#tpl_health_row').html().replace(/__ROWID__/g, rowId).replace(/__NUM__/g, idx);
    const $tpl = $(tplHtml);
    $tpl.find('.health-row-name').val('');
    $tpl.find('.health-row-price').val('0.00');
    if (isOld) $('#old_health_rows').append($tpl); else $('#health_rows').append($tpl);
    renderHealthOptions($tpl, isOld);
    updateComponentsButton($tpl);
    if (isOld) computeHealthNetTotalOld(); else computeHealthNetTotal();
  }

  function cloneHealthRowFrom($src, isOld){
    const rowId = (isOld ? `oh-${Date.now()}-${++oldHealthRowCounter}` : `h-${Date.now()}-${++healthRowCounter}`);
    const container = isOld ? '#old_health_rows' : '#health_rows';
    const idx = $(`${container} .health-row`).length + 1;
    const tplHtml = $('#tpl_health_row').html().replace(/__ROWID__/g, rowId).replace(/__NUM__/g, idx);
    const $tpl = $(tplHtml);
    if (isOld) $('#old_health_rows').append($tpl); else $('#health_rows').append($tpl);

    const srcRowName = $src.find('.health-row-name').val() || '';
    const srcRowPrice = $src.find('.health-row-price').val() || '';

    renderHealthOptions($tpl, isOld);

    $tpl.find('.health-row-name').val(srcRowName);
    $tpl.find('.health-row-price').val(srcRowPrice);

    // copy selected tests
    $src.find('.test-checkbox:checked').each(function(){ const val = $(this).val(); $tpl.find(`.test-checkbox[value="${val}"]`).prop('checked', true); });

    // copy consultations & their prices
    $src.find('.consultation-row').each(function(){
      const $sr = $(this);
      if ($sr.find('.consultation-checkbox').is(':checked')) {
        const val = $sr.find('.consultation-checkbox').val();
        const price = $sr.find('.consultation-price').val();
        const $dstCb = $tpl.find(`.consultation-checkbox[value="${val}"]`);
        if ($dstCb.length) { $dstCb.prop('checked', true).trigger('change'); $dstCb.closest('.consultation-row').find('.consultation-price').val(price); }
      }
    });

    // copy others
    $src.find('.consultation-others-row').each(function(){
      const $sor = $(this);
      const checked = $sor.find('.consultation-others-checkbox').is(':checked');
      if (!checked) return;
      const desc = $sor.find('.consultation-others-text').val();
      const price = $sor.find('.consultation-others-price').val();
      const $dstOthers = $tpl.find('.consultation-others-row');
      if ($dstOthers.length) {
        $dstOthers.find('.consultation-others-checkbox').prop('checked', true).trigger('change');
        $dstOthers.find('.consultation-others-text').val(desc);
        $dstOthers.find('.consultation-others-price').val(price);
      }
    });

    setTimeout(()=> {
      updateComponentsButton($tpl);
      if (isOld) computeHealthNetTotalOld(); else computeHealthNetTotal();
    }, 80);
  }

  function reindexHealthRows(){ $('#health_rows .health-row').each(function(i){ const $label = $(this).find('.fw-bold').first(); if ($label.length) $label.text(`Row ${i+1}`); }); }
  function reindexHealthRowsOld(){ $('#old_health_rows .health-row').each(function(i){ const $label = $(this).find('.fw-bold').first(); if ($label.length) $label.text(`Row ${i+1}`); }); }

  // Render grouped components: tests (left column) and consultations (right column) + "Others"
  function renderHealthOptions($row, isOld){
    const rowId = $row.attr('data-rowid') || $row.data('rowid') || (isOld ? `oh-${Date.now()}-${++oldHealthRowCounter}` : `h-${Date.now()}-${++healthRowCounter}`);
    $row.attr('data-rowid', rowId);

    const $options = $row.find('.health-options');
    $options.empty();

    // Create two-column layout container
    const $container = $(`<div class="components-row"></div>`);
    const $testsCol = $(`<div class="components-col"><div class="components-heading">Tests</div></div>`);
    const $consultCol = $(`<div class="components-col"><div class="consultation-subheading">Consultation</div></div>`);

    // Tests list (left)
    tests.forEach(t => {
      const id = `${isOld ? 'old' : 'new'}-test-${rowId}-${t.id}`;
      const item = $(`
        <div class="form-check">
          <input class="form-check-input test-checkbox" type="checkbox" id="${id}" value="${t.id}">
          <label class="form-check-label" for="${id}" title="Price: ${parseFloat(t.price || 0).toFixed(2)}">${t.name}</label>
        </div>`);
      // when test toggled update components button and totals
      item.find('.test-checkbox').on('change', function(){
        updateComponentsButton($row);
        if (isOld) computeHealthNetTotalOld(); else computeHealthNetTotal();
      });
      $testsCol.append(item);
    });

    // Consultations list (right)
    consultations.forEach(c => {
      const id = `${isOld ? 'old' : 'new'}-consult-${rowId}-${c.id}`;
      const crow = $(`
        <div class="consultation-row form-check d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <input class="form-check-input consultation-checkbox" type="checkbox" id="${id}" value="${c.id}">
            <label class="form-check-label me-2" for="${id}">${c.name}</label>
          </div>
          <div class="consultation-price-wrap" style="display:none; margin-left:6px;">
            <input class="form-control form-control-sm consultation-price" type="number" min="0" step="0.01" placeholder="Price" style="width:110px;">
          </div>
        </div>`);
      crow.find('.consultation-checkbox').on('change', function(){
        const $r = $(this).closest('.consultation-row');
        const show = $(this).is(':checked');
        $r.find('.consultation-price-wrap').toggle(show);
        if (!show) $r.find('.consultation-price').val('');
        updateComponentsButton($row);
        if (isOld) computeHealthNetTotalOld(); else computeHealthNetTotal();
      });
      crow.find('.consultation-price').on('input change', function(){ if (isOld) computeHealthNetTotalOld(); else computeHealthNetTotal(); });
      $consultCol.append(crow);
    });

    // Append an "Others" consultation row with description + price when checked
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
    // Handler for others toggle
    othersHtml.find('.consultation-others-checkbox').on('change', function(){
      const $parent = $(this).closest('.consultation-others-row');
      const checked = $(this).is(':checked');
      $parent.find('.consultation-others-text, .consultation-others-price').toggle(checked);
      if (!checked) $parent.find('.consultation-others-text, .consultation-others-price').val('');
      updateComponentsButton($row);
      if (isOld) computeHealthNetTotalOld(); else computeHealthNetTotal();
    });
    othersHtml.find('.consultation-others-price').on('input change', function(){ if (isOld) computeHealthNetTotalOld(); else computeHealthNetTotal(); });

    $consultCol.append(othersHtml);

    $container.append($testsCol).append($consultCol);
    $options.append($container);

    // initialize collapse toggles
    const collapseId = `tests-${rowId}`;
    $row.find('.toggle-components').attr('data-bs-target', `#${collapseId}`).attr('aria-controls', collapseId);
    $row.find('[id^="tests-__ROWID__"]').attr('id', collapseId);
    const collapseEl = document.getElementById(collapseId);
    if (collapseEl && !bootstrap.Collapse.getInstance(collapseEl)) new bootstrap.Collapse(collapseEl, {toggle:false});

    // Hook row price -> totals
    $row.find('.health-row-price').off('input change').on('input change', function(){ if (isOld) computeHealthNetTotalOld(); else computeHealthNetTotal(); });

    // ensure components button shows counts initially
    updateComponentsButton($row);
  }

  // Update the Components toggle button label with selected counts for that row
  function updateComponentsButton($row){
    if (!$row || $row.length === 0) return;
    const testsSelected = $row.find('.test-checkbox:checked').length;
    const consultSelected = $row.find('.consultation-checkbox:checked').length + ($row.find('.consultation-others-checkbox:checked').length ? 1 : 0);
    const $btn = $row.find('.toggle-components');
    if ($btn.length) {
      const base = 'Components';
      const suffix = `(${testsSelected} tests, ${consultSelected} consults)`;
      $btn.text(`${base} ${suffix}`);
    }
  }

  function computeHealthNetTotal(){
    let net = 0.0;
    $('#health_rows .health-row').each(function(){
      const $row = $(this);
      const hasTest = $row.find('.test-checkbox:checked').length > 0;
      if (hasTest) net += parseFloat($row.find('.health-row-price').val()) || 0;
      $row.find('.consultation-row').each(function(){
        const $cr = $(this);
        if ($cr.find('.consultation-checkbox').is(':checked')) {
          const price = parseFloat($cr.find('.consultation-price').val()) || 0;
          net += price;
        }
      });
      // others
      $row.find('.consultation-others-row').each(function(){
        const $or = $(this);
        if ($or.find('.consultation-others-checkbox').is(':checked')) {
          const price = parseFloat($or.find('.consultation-others-price').val()) || 0;
          net += price;
        }
      });
    });
    //$('#health_net_total').text(net.toFixed(2));
  }

  function computeHealthNetTotalOld(){
    let net = 0.0;
    $('#old_health_rows .health-row').each(function(){
      const $row = $(this);
      const hasTest = $row.find('.test-checkbox:checked').length > 0;
      if (hasTest) net += parseFloat($row.find('.health-row-price').val()) || 0;
      $row.find('.consultation-row').each(function(){
        const $cr = $(this);
        if ($cr.find('.consultation-checkbox').is(':checked')) {
          const price = parseFloat($cr.find('.consultation-price').val()) || 0;
          net += price;
        }
      });
      $row.find('.consultation-others-row').each(function(){
        const $or = $(this);
        if ($or.find('.consultation-others-checkbox').is(':checked')) {
          const price = parseFloat($or.find('.consultation-others-price').val()) || 0;
          net += price;
        }
      });
    });
    //$('#old_health_net_total').text(net.toFixed(2));
  }

  // RENDER LOCATIONS grouped by region + region-select-all
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
      const $group = $(`<div class="region-group mb-2" data-region="${escapeHtml(region)}"></div>`);
      const $header = $(
        `<div class="d-flex align-items-center mb-1">
          <div class="form-check">
            <input class="form-check-input region-checkbox" type="checkbox" id="${regionId}" data-region="${escapeHtml(region)}">
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
            <input class="form-check-input location-checkbox" type="checkbox" id="${id}" value="${loc.id}" data-region="${escapeHtml(region)}">
            <label class="form-check-label" for="${id}">${escapeHtml(loc.name)}</label>
          </div>`
        );
        $listDiv.append(item);
      });
      $group.append($listDiv);
      $c.append($group);
    });
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

  // Update the location count text for a container (#locations_container or #old_locations)
  function updateLocationCountFor(containerSelector){
    const selected = $(`${containerSelector} .location-checkbox:checked`).length;
    if (containerSelector === '#locations_container') {
      $('#toggle_locations_btn').text(`Locations (${selected} selected)`);
    } else if (containerSelector === '#old_locations') {
      $('#toggle_old_locations_btn').text(`Locations (${selected} selected)`);
    }
    updateRegionCheckboxStates(containerSelector);
  }

  function updateAllLocationCounts(){
    updateLocationCountFor('#locations_container');
    updateLocationCountFor('#old_locations');
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

  function populateEntityTypeSelector(scopeOverride, isOld=false, cb){
    const scope = scopeOverride || (isOld ? $('#old_scope').val() : $('#scope').val());
    const target = isOld ? '#old_entity_type' : '#entity_type';
    $(target).empty().append('<option value="">Select entity</option>');
    (Array.isArray(entityTypes) ? entityTypes.filter(e => e.scope === scope) : []).forEach(e => $(target).append(`<option value="${e.id}">${e.name}</option>`));
    if (typeof cb === 'function') cb();
  }

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

      rows.push({ row_name: rowName, selected_test_ids: selected_tests, package_price: package_price, selected_consultation_ids, prices, selected_others });
    });
    return rows;
  }
  
  function encodeHTML(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

  function collectFormData(){
    const mode = state.mode;
    const isRenewUpload = (mode === 'renew_upload');
    
    let html = quill.root.innerHTML;
    
     $('#template_text').text(encodeHTML(quill.root.innerHTML));
    

    function collectAgreementFields(isOld){
      const agreement_name = isOld ? $('#old_agreement_name').val() : $('#agreement_name').val();
      const customerObj = isOld ? $('#old_customer').data('customer') : $('#customer').data('customer');
      const customer_id = customerObj ? customerObj.id : null;
      const scope = isOld ? $('#old_scope').val() : $('#scope').val();
      const entity_type_id = isOld ? $('#old_entity_type').val() : $('#entity_type').val();
      const scope_of_services = $(`${isOld ? '#old_scope_of_services' : '#scope_of_services'} .${isOld ? 'old-scope-service' : 'scope-service'}:checked`).map((i,el)=>$(el).val()).get();
      const discounts = extractDiscountsFrom(isOld ? '#old_discounts_container' : '#discounts_container');
      const health_check_rows = extractHealthRowsFrom(isOld ? '#old_health_rows' : '#health_rows');
      const locations_selected = $(`${isOld ? '#old_locations' : '#locations_container'} .location-checkbox:checked`).map((i,el)=>Number($(el).val())).get();
      const start_date = isOld ? $('#start_date_old').val() : $('#start_date').val();
      const end_date = isOld ? $('#end_date_old').val() : $('#end_date').val();
      const duration_confirmed = isOld ? $('#duration_confirm_old').is(':checked') : $('#duration_confirm').is(':checked');
      const editor_text = isOld ? $('#editor_old').val() : $('#template_text').text();
      const contract_notes = isOld ? $('#contract_notes_old').val() : $('#contract_notes').val();

      // Additional fields
      const credit_limit = isOld ? null : $('#credit_limit').val();
      const credit_days = isOld ? null : $('#credit_days').val();
      const coc_ip = isOld ? null : $('#coc_ip').val();
      const coc_op = isOld ? null : $('#coc_op').val();
      const bank_guarantee = isOld ? null : $('#bank_guarantee').val();

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
        contract_notes,
        credit_limit,
        credit_days,
        coc_ip,
        coc_op,
        bank_guarantee,
        prevailing_hospital_tariff,
        communication_protocol,
        employees_dependants,
        sponsors
      };
    }

    const newContract = collectAgreementFields(false);
    if (!isRenewUpload) return { renew: false, new_contract: newContract, legacy_files: [] };
    const oldContract = collectAgreementFields(true);
    
    const oldFileInput = $('#old_legacy_file')[0];
    const oldFiles = oldFileInput ? oldFileInput.files[0] : [];
    
    return { renew: true, old_contract: oldContract, new_contract: newContract, legacy_files: oldFiles };
  }

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
    if ($('.location-checkbox:checked').length === 0) { $('#toggle_locations_btn').addClass('border border-danger p-2'); ok = false; } else $('#toggle_locations_btn').removeClass('border border-danger p-2');
    if (!$('#start_date').val() || !$('#end_date').val()) { if (!$('#start_date').val()) $('#start_date').addClass('is-invalid'); else $('#start_date').removeClass('is-invalid'); if (!$('#end_date').val()) $('#end_date').addClass('is-invalid'); else $('#end_date').removeClass('is-invalid'); ok = false; } else { $('#start_date,#end_date').removeClass('is-invalid'); if (!validateDates()) ok = false; }
    const oldStart = $('#start_date_old').val(), oldEnd = $('#end_date_old').val(), newStart = $('#start_date').val(), newEnd = $('#end_date').val();
    if (oldStart && oldEnd && newStart && newEnd && oldStart === newStart && oldEnd === newEnd) { if (!$('#confirm_same_tenure').is(':checked')) { $('#same_tenure_error').show(); ok = false; } else $('#same_tenure_error').hide(); } else $('#same_tenure_error').hide();

    // Validate health rows: package price > 0 if tests selected; consultation checked must have price > 0; others require description + price>0
    let priceOk = true;
    $('#health_rows .health-row').each(function(){
      const $r = $(this);
      const testsSel = $r.find('.test-checkbox:checked').length;
      if (testsSel > 0) {
        const p = parseFloat($r.find('.health-row-price').val()) || 0;
        if (p <= 0) priceOk = false;
      }
      $r.find('.consultation-row').each(function(){
        const $cr = $(this);
        if ($cr.find('.consultation-checkbox').is(':checked')) {
          const cp = parseFloat($cr.find('.consultation-price').val()) || 0;
          if (cp <= 0) priceOk = false;
        }
      });
      $r.find('.consultation-others-row').each(function(){
        const $or = $(this);
        if ($or.find('.consultation-others-checkbox').is(':checked')) {
          const desc = $or.find('.consultation-others-text').val() || '';
          const p = parseFloat($or.find('.consultation-others-price').val()) || 0;
          if (!desc.trim() || p <= 0) priceOk = false;
        }
      });
    });
    if (!priceOk) {
      alert('Please enter positive package prices for selected tests, positive prices for selected consultations, and provide description & price for "Others".');
      ok = false;
    }

    return ok;
  }

  function onPreview(){ if (!validateForm()) return; const data = collectFormData(); $('#preview_content').html(buildPreviewHtml(data)); const modal = new bootstrap.Modal(document.getElementById('previewModal')); modal.show(); }

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

    //lines.push(`<h5>Agreement Text (new)</h5><pre>${escapeHtml($('#editor').val()||'')}</pre>`);
    return lines.join('\n');
  }

  // Submit: send payload to API.submit. On success: hide preview modal and show response; Back -> reload page
  function onSubmitConfirmed(e, isDraft=false){
    // Prevent saving draft when a custom template is selected/uploaded
    if (isDraft) {
      const uploadChecked = $('#enable_upload_template').is(':checked');
      const fileInput = $('#docxFile')[0];
      const fileSelected = fileInput && fileInput.files && fileInput.files.length;
      if (uploadChecked || fileSelected) {
        alert('Cannot save as draft while a custom template is selected. Uncheck "Upload custom template" or remove the file to save as draft.');
        return;
      }
    }

    if (!validateForm()) return;
    const payload = collectFormData();
    payload.save_as_draft = isDraft;
    const $btn = $('#confirm_submit');
    $btn.prop('disabled', true).text(isDraft ? 'Saving' : 'Sending...');
    
    const formData = new FormData();
    formData.append('payload', JSON.stringify(payload));
    if (payload && payload.legacy_files) formData.append('old_legacy_file', payload.legacy_files);
    // Attach uploaded template (.docx) if present so server receives the file
    const docxInput = $('#docxFile')[0];
    if (docxInput && docxInput.files && docxInput.files.length) {
      formData.append('docxFile', docxInput.files[0]);
    const prevInput = $('#prevailing_file')[0];
    if (prevInput && prevInput.files && prevInput.files.length) {
      formData.append('prevailing_file', prevInput.files[0]);
    }
    }
    $.ajax({
      url: API.submit,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: getAjaxHeaders(),
      success: function(res){
        // hide preview modal on success
        try { const modalEl = document.getElementById('previewModal'); const modalInst = bootstrap.Modal.getInstance(modalEl); if (modalInst) modalInst.hide(); } catch(e){}
        $btn.prop('disabled', false).text('Confirm & Save');

        // If server returned success=false, show SweetAlert with details
        if (res && res.success === false) {
          let message = res.message || '';
          if (res.errors) {
            const errs = Object.values(res.errors).map(v => Array.isArray(v) ? v.join('<br/>') : v).join('<br/>');
            message = message ? (message + '<br/>' + errs) : errs;
          }
          Swal.fire({ icon: 'error', title: 'Submission failed', html: message || 'Server returned an error.' });
          return;
        }

        if (res && res.html_template) {
          //showResponseTemplate(res.html_template);
          const extracted = extractKeysFromHtml(res.html_template);
          //displayExtractedKeys(extracted);
          return;
        }
        if (typeof res === 'string' && res.trim().startsWith('<')) {
          showResponseTemplate(res);
          const extracted = extractKeysFromHtml(res);
          //displayExtractedKeys(extracted);
          return;
        }
        // fallback: display JSON
        //showResponseTemplate('<pre>' + escapeHtml(JSON.stringify(res, null, 2)) + '</pre>');
        if(res && res.success){
            window.location.href = APP_URL + '/contracts/list/contract-custom';
            return;
        }

        // Unexpected response - show info
        Swal.fire({ icon: 'info', title: 'Notice', text: 'Received unexpected response from server. Check console for details.' });
        console.info('Server response (unexpected):', res);
        displayExtractedKeys({});
      },
      error: function(xhr){
        // hide preview modal if possible
        try { const modalEl = document.getElementById('previewModal'); const modalInst = bootstrap.Modal.getInstance(modalEl); if (modalInst) modalInst.hide(); } catch(e){}
        $btn.prop('disabled', false).text('Confirm & Save');

        // Validation errors (422)
        if (xhr && xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          const messages = [];
          Object.keys(xhr.responseJSON.errors).forEach(k => { messages.push(xhr.responseJSON.errors[k].join('<br/>')); });
          Swal.fire({ icon: 'error', title: 'Validation Error', html: messages.join('<br/>') });
          return;
        }

        // Server replied with success:false in JSON
        if (xhr && xhr.responseJSON && xhr.responseJSON.success === false) {
          const msg = xhr.responseJSON.message || JSON.stringify(xhr.responseJSON);
          Swal.fire({ icon: 'error', title: 'Error', text: msg });
          return;
        }

        // html template fallback
        let htmlBody = '';
        try {
          htmlBody = xhr.responseJSON && xhr.responseJSON.html_template ? xhr.responseJSON.html_template : (xhr.responseText || '');
        } catch(e) { htmlBody = xhr.responseText || ''; }
        if (htmlBody && htmlBody.trim().startsWith('<')) {
          showResponseTemplate(htmlBody);
          const extracted = extractKeysFromHtml(htmlBody);
          displayExtractedKeys(extracted);
          return;
        }

        Swal.fire({ icon: 'error', title: 'Submission failed', text: 'Submission failed (network or server). Check console for details.' });
        console.error('Submission error', xhr);
      }
    });
  }

  // show returned html_template in response viewer; hide form and tabs
  function showResponseTemplate(html){
    // hide main UI (but keep response_viewer available)
    $('#page_root > *').not('#response_viewer').hide();
    $('#response_html_container').html(html);
    $('#response_viewer').show();
    // scroll to viewer
    window.scrollTo({ top: $('#response_viewer').offset().top, behavior: 'smooth' });
  }

  // Extract key-values from returned HTML template by searching for data-key attributes
  function extractKeysFromHtml(html){
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    const nodes = wrapper.querySelectorAll('[data-key]');
    const extracted = {};
    nodes.forEach(n => {
      const key = n.getAttribute('data-key');
      if (!key) return;
      let value = '';
      if (n.tagName === 'INPUT' || n.tagName === 'SELECT' || n.tagName === 'TEXTAREA') {
        value = n.value;
      } else {
        value = n.textContent.trim();
      }
      if (extracted.hasOwnProperty(key)) {
        if (!Array.isArray(extracted[key])) extracted[key] = [extracted[key]];
        extracted[key].push(value);
      } else {
        extracted[key] = value;
      }
    });
    return extracted;
  }

  function displayExtractedKeys(obj){
    $('#response_extracted').text(JSON.stringify(obj, null, 2));
    console.info('Extracted keys from template:', obj);
  }

  function resetForm(){
    // reload to ensure consistent state
    location.reload();
  }

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

  function confirmOldContract(autoCopy){
    const name = $('#old_agreement_name').val();
    const cust = $('#old_customer').data('customer');
    if (!name || !cust) { alert('Please fill agreement name and select a customer for old contract before confirming.'); return; }
    const matched = legacyAgreements.find(l => l.agreement_name === name || l.customer_id === cust.id);
    state.legacyData = matched || { agreement_name: name, customer_id: cust.id, files: [] };
    const fileInput = $('#old_legacy_file')[0];
    if (fileInput && fileInput.files && fileInput.files.length) {
      const f = fileInput.files[0];
      state.legacyData.files = f || [];
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

  // COPY FROM OLD -> NEW (preserving discounts, locations, others)
  function copyOldFormToNew(cb){
    const newHasValues = ($('#agreement_name').val() || $('#customer').data('customer') || $('#discounts_container .discount-row').length > 0 || $('#health_rows .health-row').length > 0);
    if (newHasValues) {
      if (!confirm('Copying values from Old Contract will overwrite some fields in the New Contract form. Proceed?')) {
        if (typeof cb === 'function') cb(false);
        return;
      }
    }

    // Agreement name + Customer
    $('#agreement_name').val($('#old_agreement_name').val());
    const oldCust = $('#old_customer').data('customer');
    if (oldCust) { $('#customer').val(oldCust.name).data('customer', oldCust); onCustomerSelected(oldCust); }

    // Scope & entity type
    const oldScope = $('#old_scope').val();
    $('#scope').val(oldScope).trigger('change');
    const oldEntity = $('#old_entity_type').val();
    if (oldEntity) populateEntityTypeSelector(oldScope || $('#scope').val(), false, ()=>$('#entity_type').val(oldEntity).trigger('change'));

    // Locations: copy checked old -> new and update counts
    $('#locations_container .location-checkbox').prop('checked', false);
    $('#old_locations .location-checkbox:checked').each(function(){
      const v = $(this).val();
      $(`#locations_container .location-checkbox[value="${v}"]`).prop('checked', true);
    });
    updateLocationCountFor('#locations_container');

    // Scope of services
    $('.scope-service').prop('checked', false);
    $('.old-scope-service:checked').each(function(){ const val = $(this).val(); $(`.scope-service[value="${val}"]`).prop('checked', true); });

    // Discounts: copy
    $('#discounts_container').empty();
    $('#old_discounts_container .discount-row').each(function(){
      const $src = $(this);
      const category = $src.find('.discount-category').val();
      const $srcSubSel = $src.find('.discount-subcategory');
      const srcSubIsSelect = ($srcSubSel.length && $srcSubSel.is('select'));
      const srcSubValue = srcSubIsSelect ? $srcSubSel.val() : $src.find('.discount-subcategory-text').val();
      const amt = $src.find('.discount-amount').val();
      const srcRoomCharges = [];
      $src.find('.room-charge-row').each(function(){ srcRoomCharges.push({ name: $(this).find('.room-charge-name').val(), price: $(this).find('.room-charge-price').val() }); });

      addDiscountRow();
      const $dst = $('#discounts_container .discount-row').last();

      // preserve initial subcategory value so populateSubcategoriesForRow can use it when options are built
      if (srcSubValue) $dst.data('initial-sub', srcSubValue);

      // Set category & trigger population of subcategory control (pass srcSubValue so handler can use it immediately)
      $dst.find('.discount-category').val(category).trigger('change', [srcSubValue]);

      const setDstSubcategory = (attemptsLeft = 12) => {
        const $dstSubSel = $dst.find('.discount-subcategory');
        const $dstText = $dst.find('.discount-subcategory-text');
        if ($dstSubSel.length && $dstSubSel.is('select')) {
          if (!srcSubValue) return;
          if ($dstSubSel.find(`option[value="${srcSubValue}"]`).length > 0) {
            $dstSubSel.val(srcSubValue).trigger('change');
            if (srcSubValue === 'Room Charges Custom') {
              $dst.find('.room-charges-list').empty();
              srcRoomCharges.forEach(rc => {
                $dst.find('.add-room-charge').trigger('click');
                const $last = $dst.find('.room-charge-row').last();
                $last.find('.room-charge-name').val(rc.name);
                $last.find('.room-charge-price').val(rc.price);
              });
              $dst.find('.room-charges-area').show();
              $dst.find('.discount-amount').hide();
              $dst.find('.discount-percent-label').hide();
            }
            return;
          }
          $dstSubSel.append(`<option value="${escapeAttr(srcSubValue)}">${escapeHtml(srcSubValue)}</option>`);
          $dstSubSel.val(srcSubValue).trigger('change');
          if (srcSubValue === 'Room Charges Custom') {
            $dst.find('.room-charges-list').empty();
            srcRoomCharges.forEach(rc => {
              $dst.find('.add-room-charge').trigger('click');
              const $last = $dst.find('.room-charge-row').last();
              $last.find('.room-charge-name').val(rc.name);
              $last.find('.room-charge-price').val(rc.price);
            });
            $dst.find('.room-charges-area').show();
            $dst.find('.discount-amount').hide();
            $dst.find('.discount-percent-label').hide();
          }
          return;
        } else if ($dstText.length) {
          $dstText.val(srcSubValue);
          return;
        } else {
          if (attemptsLeft > 0) setTimeout(()=>setDstSubcategory(attemptsLeft - 1), 80);
          else $dst.find('.subcategory-wrapper').empty().append(`<input class="form-control discount-subcategory-text" value="${escapeAttr(srcSubValue)}">`);
        }
      };
      setDstSubcategory();

      // set discount percent
      $dst.find('.discount-amount').val(amt);
    });

    // Health rows copy
    $('#health_rows').empty();
    $('#old_health_rows .health-row').each(function(){
      const $src = $(this);
      addHealthRow();
      const $dst = $('#health_rows .health-row').last();
      $dst.find('.health-row-name').val($src.find('.health-row-name').val());
      $dst.find('.health-row-price').val($src.find('.health-row-price').val());
      setTimeout(()=> {
        $src.find('.test-checkbox:checked').each(function(){ const val = $(this).val(); $dst.find(`.test-checkbox[value="${val}"]`).prop('checked', true); });
        $src.find('.consultation-row').each(function(){
          const $sr = $(this);
          if ($sr.find('.consultation-checkbox').is(':checked')) {
            const val = $sr.find('.consultation-checkbox').val();
            const price = $sr.find('.consultation-price').val();
            const $dstCb = $dst.find(`.consultation-checkbox[value="${val}"]`);
            $dstCb.prop('checked', true).trigger('change');
            $dstCb.closest('.consultation-row').find('.consultation-price').val(price);
          }
        });
        // copy others
        const $srcOthers = $src.find('.consultation-others-row');
        const $dstOthers = $dst.find('.consultation-others-row');
        if ($srcOthers.length && $dstOthers.length) {
          if ($srcOthers.find('.consultation-others-checkbox').is(':checked')) {
            const desc = $srcOthers.find('.consultation-others-text').val();
            const price = $srcOthers.find('.consultation-others-price').val();
            $dstOthers.find('.consultation-others-checkbox').prop('checked', true).trigger('change');
            $dstOthers.find('.consultation-others-text').val(desc);
            $dstOthers.find('.consultation-others-price').val(price);
          }
        }
        updateComponentsButton($dst);
        computeHealthNetTotal();
      }, 150);
    });

    // Dates & editor
    $('#start_date').val($('#start_date_old').val());
    $('#end_date').val($('#end_date_old').val());
    $('#duration_confirm').prop('checked', $('#duration_confirm_old').is(':checked'));
    $('#editor').val($('#editor_old').val());
    
    // Preserve copied discounts even if target scope is international.
    // Discount amounts will be hidden/disabled by updateDiscountsAndHealthVisibility() when appropriate.

    updateDiscountsAndHealthVisibility();
    updateDiscountRowCategories();
    updateConfirmSameTenureState();
    updateAllLocationCounts();

    if (typeof cb === 'function') setTimeout(()=>cb(true), 350);
  }

  function escapeHtml(text){ if (text === undefined || text === null) return ''; return String(text).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;'); }
  function escapeAttr(val){ if (val === undefined || val === null) return ''; return String(val).replaceAll('"','&quot;').replaceAll("'", '&#x27;'); }

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

  // Helper to submit forms from modals
  function form_modal_submit(idForm){
    $(`#${idForm}`).submit();
  }

  // Move the inline template upload UI handling here so Blade is clean
  function setupTemplateUploadUI(){
    const uploadBtn = document.getElementById('upload_template_btn');
    const fileInput = document.getElementById('docxFile');
    const nameEl = document.getElementById('uploaded_template_name');
    const checkbox = document.getElementById('enable_upload_template');
    const controls = document.getElementById('upload_template_controls');
    const saveDraftBtn = document.getElementById('save_draft');
    const saveDraftModalBtn = document.getElementById('save_draft1');

    function setControlsVisible(visible){
      if (!controls) return;
      // Use bootstrap helper class to hide/show so specificity of btn classes doesn't interfere
      if (visible) {
        controls.classList.remove('d-none');
      } else {
        controls.classList.add('d-none');
      }
      if (uploadBtn) {
        if (visible) uploadBtn.classList.remove('d-none'); else uploadBtn.classList.add('d-none');
      }
      if (!visible) { if (fileInput) fileInput.value = ''; if (nameEl) nameEl.textContent = ''; }
    }

    function updateDraftAvailability(){
      const hide = checkbox && checkbox.checked;
      if (saveDraftBtn) { if (hide) saveDraftBtn.classList.add('d-none'); else saveDraftBtn.classList.remove('d-none'); }
      if (saveDraftModalBtn) { if (hide) saveDraftModalBtn.classList.add('d-none'); else saveDraftModalBtn.classList.remove('d-none'); }
    }

    if (checkbox && controls) {
      setControlsVisible(checkbox.checked);
      updateDraftAvailability();
      checkbox.addEventListener('change', function(){ setControlsVisible(this.checked); updateDraftAvailability(); });
    } else {
      setControlsVisible(false);
      updateDraftAvailability();
    }

    if (uploadBtn && fileInput) {
      uploadBtn.addEventListener('click', () => { if (checkbox && !checkbox.checked) return; fileInput.click(); });
      fileInput.addEventListener('change', function(){
        if (this.files && this.files[0]) {
          const fname = this.files[0].name;
          if (nameEl) nameEl.textContent = fname;
          updateDraftAvailability();
        } else {
          if (nameEl) nameEl.textContent = '';
          updateDraftAvailability();
        }
      });
    }
  }

  $(document).ready(init);
  
  $(document).on('input change', '.discount-amount', function(){ toggleDiscountWarning($(this)); });

})(jQuery);