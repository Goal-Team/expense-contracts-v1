  // --- Helpers ---
  function findFieldById(id) {
    if (!window.CUSTOM_FIELDS || !Array.isArray(window.CUSTOM_FIELDS)) return null;
    var sid = String(id);
    for (var i = 0; i < window.CUSTOM_FIELDS.length; i++) {
      if (String(window.CUSTOM_FIELDS[i].custom_field_id) === sid) return window.CUSTOM_FIELDS[i];
    }
    return null;
  }

  function clearContainer($container) {
    $container.empty();
  }

  function parseOptions(field) {
    if (!field) return [];
    if (Array.isArray(field.field_options) && field.field_options.length) return field.field_options.slice();
    var dv = field.field_default_value;
    if (!dv) return [];
    try {
      var parsed = JSON.parse(dv);
      if (Array.isArray(parsed)) return parsed;
    } catch (e) { /* ignore */ }
    return dv.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
  }

  function createFieldWrapperId(id) {
    return 'cf-input-' + id;
  }

  function createInputGroup(labelText, $input) {
    var $wrapper = $('<div/>').addClass('cf-field-wrapper mb-3').attr('data-created', Date.now());
    if (labelText) {
      $wrapper.append($('<h6/>').addClass('mb-2').text(labelText));
    }
    $wrapper.append($input);
    return $wrapper;
  }

  // Renders form controls for a single custom field and returns the jQuery element to append.
  // Names are structured for server side as: customfield[{id}][...] so multiple selected fields won't clash.
  function renderInputsForField(field) {
    if (!field) return $();

    var required = String(field.required) === '1' || field.required === true;
    var fid = field.custom_field_id;
    var nameBase = 'customfield[' + fid + ']';
    var displayName = field.field_name || ('Field ' + fid);
    var type = (field.field_type || '').toLowerCase();

    var $container = $('<div/>').attr('id', createFieldWrapperId(fid));

    if (type === 'number' || type === 'decimal' || type === 'currency') {
      var step = '1';
      if (type === 'decimal') step = 'any';
      if (type === 'currency') step = '0.01';

      var $row = $('<div/>').addClass('row g-2');

      var $fromDiv = $('<div/>').addClass('col-md-6');
      $fromDiv.append($('<label/>').addClass('form-label').text(displayName + ' - From'));
      var $fromInput = $('<input/>', {
        type: 'number',
        class: 'form-control',
        name: nameBase + '[from]',
        id: nameBase + '_from',
        step: step,
        placeholder: 'From'
      });
      if (required) $fromInput.prop('required', true);
      $fromDiv.append($fromInput);

      var $toDiv = $('<div/>').addClass('col-md-6');
      $toDiv.append($('<label/>').addClass('form-label').text(displayName + ' - To'));
      var $toInput = $('<input/>', {
        type: 'number',
        class: 'form-control',
        name: nameBase + '[to]',
        id: nameBase + '_to',
        step: step,
        placeholder: 'To'
      });
      if (required) $toInput.prop('required', true);
      $toDiv.append($toInput);

      $row.append($fromDiv, $toDiv);
      $container.append(createInputGroup(displayName, $row));

    } else if (type === 'date') {
      var $dateInput = $('<input/>', {
        type: 'date',
        class: 'form-control',
        name: nameBase + '[value]',
        id: nameBase + '_date'
      });
      if (required) $dateInput.prop('required', true);
      $container.append(createInputGroup(displayName, $dateInput));

    } else if (type === 'select') {
      var options = parseOptions(field);
      var $sel = $('<select/>', { class: 'form-select', name: nameBase + '[value]', id: nameBase + '_select' });
      $sel.append($('<option/>', { value: '', text: 'Select ' + displayName }));
      if (options.length) {
        options.forEach(function (opt) {
          if (opt && typeof opt === 'object') {
            var val = opt.value !== undefined ? opt.value : (opt.id !== undefined ? opt.id : opt.label || '');
            var lab = opt.label !== undefined ? opt.label : opt.name !== undefined ? opt.name : String(val);
            $sel.append($('<option/>', { value: val, text: lab }));
          } else {
            $sel.append($('<option/>', { value: opt, text: opt }));
          }
        });
      } else {
        $container.append(createInputGroup(displayName, $('<input/>', { type: 'text', class: 'form-control', name: nameBase + '[value]', id: nameBase + '_text', placeholder: 'Enter ' + displayName })));
        return $container;
      }
      if (required) $sel.prop('required', true);
      $container.append(createInputGroup(displayName, $sel));

    } else if (type === 'checkbox') {
      var opts = parseOptions(field);
      var $group = $('<div/>').addClass('mb-2');

      if (opts.length) {
        $group.append($('<label/>').addClass('form-label d-block').text(displayName));
        opts.forEach(function (opt, idx) {
          var val, lab;
          if (opt && typeof opt === 'object') {
            val = opt.value !== undefined ? opt.value : (opt.id !== undefined ? opt.id : opt.label || '');
            lab = opt.label !== undefined ? opt.label : opt.name !== undefined ? opt.name : String(val);
          } else {
            val = opt;
            lab = opt;
          }
          var id = nameBase + '_opt_' + idx;
          var $wrap = $('<div/>').addClass('form-check form-check-inline');
          var $cb = $('<input/>', {
            class: 'form-check-input',
            type: 'checkbox',
            name: nameBase + '[options][]',
            id: id,
            value: val
          });
          var $lbl = $('<label/>', { class: 'form-check-label', for: id }).text(lab);
          $wrap.append($cb, $lbl);
          $group.append($wrap);
        });
      } else {
        var idSingle = nameBase + '_single';
        var $wrapSingle = $('<div/>').addClass('form-check');
        var $cbSingle = $('<input/>', {
          class: 'form-check-input',
          type: 'checkbox',
          name: nameBase + '[value]',
          id: idSingle,
          value: '1'
        });
        var $lblSingle = $('<label/>', { class: 'form-check-label', for: idSingle }).text(displayName);
        $wrapSingle.append($cbSingle, $lblSingle);
        $group.append($wrapSingle);
      }
      $container.append(createInputGroup(displayName, $group));

    } else {
      var $textInput = $('<input/>', {
        type: 'text',
        class: 'form-control',
        name: nameBase + '[value]',
        id: nameBase + '_text',
        placeholder: field.field_default_value || ('Enter ' + displayName)
      });
      if (required) $textInput.prop('required', true);
      $container.append(createInputGroup(displayName, $textInput));
    }

    return $container;
  }

  // --- Filtering logic for contract types ---
  function getSelectedContractTypes() {
    var sel = $('#contract_type');
    if (!sel.length) return [];
    var val = sel.val();
    if (!val) return [];
    return Array.isArray(val) ? val.map(String) : [String(val)];
  }

  function filterFieldsByContractTypes(selectedTypes) {
    if (!selectedTypes || selectedTypes.indexOf('0') !== -1) return (window.CUSTOM_FIELDS || []).slice();
    var out = [];
    (window.CUSTOM_FIELDS || []).forEach(function (f) {
      if (selectedTypes.indexOf(String(f.contract_type)) !== -1) out.push(f);
    });
    return out;
  }

  function rebuildCustomFieldsOptions() {
    var $customSelect = $('#customfields');
    if (!$customSelect.length) return;

    var selectedContractTypes = getSelectedContractTypes();
    var filtered = filterFieldsByContractTypes(selectedContractTypes);

    // remember previous selected values (array)
    var prevSelected = $customSelect.val() || [];

    // clear and re-add options
    $customSelect.empty();
    $customSelect.append($('<option/>', { value: '0', text: 'Not Applicable' }));

    filtered.forEach(function (f) {
      var $opt = $('<option/>', { value: f.custom_field_id, text: f.field_name });
      // if previously selected, keep it selected (we'll intersect later)
      if (prevSelected.indexOf(String(f.custom_field_id)) !== -1) $opt.prop('selected', true);
      $customSelect.append($opt);
    });

    // compute new selection as intersection of prevSelected and new option values
    var newSelected = [];
    var allOptionVals = $customSelect.find('option').map(function () { return String(this.value); }).get();
    prevSelected.forEach(function (v) {
      if (allOptionVals.indexOf(String(v)) !== -1) newSelected.push(String(v));
    });

    // If prev selection empty -> keep current (none). If newSelected empty, select '0' (Not Applicable)
    if (newSelected.length === 0) {
      newSelected = ['0'];
    }

    // Set the select's value
    $customSelect.val(newSelected);

    // Update Select2 if present, otherwise trigger change
    if ($customSelect.data('select2')) {
      // notify Select2 to update its displayed selections
      $customSelect.trigger('change.select2');
    } else {
      $customSelect.trigger('change');
    }
  }

  // --- Render for multiple selected custom fields ---
  function renderSelectedCustomFields() {
    var $container = $('#customfield-inputs');
    clearContainer($container);
    var $customSelect = $('#customfields');
    if (!$customSelect.length) return;

    var selected = $customSelect.val() || [];
    if (!Array.isArray(selected)) selected = [selected];

    // If '0' (Not Applicable) is selected, treat as no fields -> keep container cleared
    if (selected.indexOf('0') !== -1) {
      clearContainer($container);
      return;
    }

    selected.forEach(function (id) {
      var field = findFieldById(id);
      if (!field) return;
      var $block = renderInputsForField(field);
      if ($block && $block.length) {
        $container.append($block);
      }
    });
  }

  // --- Initialization & Event bindings ---
  $(document).ready(function(){


    // Bindings for contract_type (delegated, supports Select2)
    $(document).on('change', '#contract_type', function () {
      rebuildCustomFieldsOptions();
    });
    $(document).on('select2:select select2:unselect select2:clear', '#contract_type', function () {
      rebuildCustomFieldsOptions();
    });

    // Bindings for customfields (delegated, supports Select2)
    $(document).on('change', '#customfields', function () {
      renderSelectedCustomFields();
    });
    $(document).on('select2:select select2:unselect select2:clear', '#customfields', function () {
      // Select2 may fire select/unselect; one simple solution is to re-render the whole selection
      renderSelectedCustomFields();
    });

    // Initial population & render
    rebuildCustomFieldsOptions(); // populates options and preserves old selections if possible
    renderSelectedCustomFields(); // renders inputs for any preselected custom fields

    // Note: if you initialize Select2 on #contract_type or #customfields after this script runs,
    // call rebuildCustomFieldsOptions() and renderSelectedCustomFields() afterwards to ensure the UI syncs.
  });
