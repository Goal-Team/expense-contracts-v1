/**
 * contract-create-v2.js
 * -----------------------------------------------------------------------------
 * Performance layer for the V2 "Add New Contract" (AI) page.
 *
 * This file deliberately contains NO business logic. contract.js and
 * contractArti.js remain the owners of everything the page does; this script
 * only removes the hot spots that made the original page slow:
 *
 *   1. Mass select2 initialisation.  form-layouts.js used to build a select2
 *      widget for every `.select2` on the page during load — on this form that
 *      is dozens of dropdowns, some with thousands of options. V2 does not load
 *      form-layouts.js; dropdowns become select2 the first time the user
 *      actually touches them.
 *   2. select2 instance stacking.  Several call sites re-run `.select2()` on an
 *      element that is already a select2 without destroying the old instance.
 *      Each pass leaves another widget and another set of listeners behind until
 *      opening a dropdown locks the tab up.  `$.fn.select2` is patched here to
 *      destroy first.
 *   3. Validation on every keystroke.  contract.js binds
 *      `$('#createcontract').on('change input', 'input, select, textarea', ...)`
 *      which runs jQuery Validate across the field on each character typed.
 *      That handler is replaced with a debounced equivalent.
 *   4. Party dropdown payloads.  The party name lists are no longer rendered
 *      into the page; they are fetched from a cached endpoint on demand.  The
 *      `ajax:done` event and the real `<option>` elements are still produced,
 *      because contractArti.js relies on both to auto-fill parties from the AI
 *      document scan.
 *
 * Load order matters: this file must come after contract.js and contractArti.js
 * so their `$(document).ready` callbacks have already registered when ours runs.
 */

(function () {
  'use strict';

  var PARTY_LIST_URL = '/contracts/create/partylist-v2';

  function appUrl(path) {
    return (typeof APP_URL !== 'undefined' ? APP_URL : '') + path;
  }

  function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content');
  }

  function escapeHtml(str) {
    return String(str === null || str === undefined ? '' : str).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /* ---------------------------------------------------------------------------
   * 1. select2 double-initialisation guard
   * ------------------------------------------------------------------------ */

  function patchSelect2() {
    if (!window.jQuery || !$.fn.select2 || $.fn.select2.__v2Patched) {
      return false;
    }

    var original = $.fn.select2;

    var patched = function (options) {
      // Only an init call (no args, or an options object) can stack instances.
      // Method calls such as .select2('destroy') / .select2('open') pass a string.
      if (options === undefined || $.isPlainObject(options)) {
        this.each(function () {
          if (this.classList.contains('select2-hidden-accessible')) {
            original.call($(this), 'destroy');
          }
        });
      }
      return original.apply(this, arguments);
    };

    // Preserve select2's statics (defaults, amd, ...).
    for (var key in original) {
      if (Object.prototype.hasOwnProperty.call(original, key)) {
        patched[key] = original[key];
      }
    }
    patched.__v2Patched = true;

    $.fn.select2 = patched;
    return true;
  }

  /**
   * select2 is delivered through the vendor bundle. Under the Vite dev server the
   * bundle can still be in flight when this module is evaluated, so retry briefly.
   */
  function whenSelect2Ready(callback) {
    if (patchSelect2()) {
      callback();
      return;
    }
    var attempts = 0;
    var timer = setInterval(function () {
      attempts++;
      if (patchSelect2() || attempts > 100) {
        clearInterval(timer);
        callback();
      }
    }, 50);
  }

  /* ---------------------------------------------------------------------------
   * 2. Lazy select2
   * ------------------------------------------------------------------------ */

  function isInitialised($el) {
    return $el.hasClass('select2-hidden-accessible');
  }

  function destroySelect2($el) {
    if (isInitialised($el)) {
      $el.select2('destroy');
    }
  }

  /**
   * Options for the external party name dropdowns. The row index is read at call
   * time rather than captured, because rows are re-indexed when parties are added
   * or removed.
   */
  function remotePartyOptions($el) {
    return {
      language: {
        searching: function () {
          return 'Searching...';
        },
        noResults: function () {
          var currentRow = ($el.attr('id') || '').replace('partyExternal_', '');
          return (
            'No Party Name Found Click to create new   ' +
            '<button type="button" class="badge bg-primary cusocli" data-exdd="' +
            escapeHtml(currentRow) +
            '" data-bs-toggle="modal" data-bs-target="#onboardHorizontalImageModal">Create</button>'
          );
        }
      },
      escapeMarkup: function (markup) {
        return markup;
      }
    };
  }

  /**
   * Builds the select2 widget for a single dropdown, matching what
   * form-layouts.js used to do for every dropdown on load.
   */
  function initSelect2($el) {
    if (isInitialised($el)) {
      return;
    }

    if (!$el.parent().hasClass('position-relative')) {
      $el.wrap('<div class="position-relative"></div>');
    }

    var options = {
      placeholder: 'Select value',
      dropdownParent: $el.parent()
    };

    if ($el.hasClass('party-name-remote')) {
      $.extend(options, remotePartyOptions($el));
    }

    $el.select2(options);
  }

  function bindLazySelect2() {
    var selector = 'select.select2:not(.select2-hidden-accessible)';

    // Multi-selects render as a tall native listbox, which looks nothing like the
    // rest of the form, so those are built up front. Anything else can opt in
    // with data-s2-eager.
    $('select.select2[multiple], select.select2[data-s2-eager]').each(function () {
      initSelect2($(this));
    });

    // Pointer users: swallow the native dropdown, build select2, open it.
    $(document).on('mousedown', selector, function (e) {
      e.preventDefault();
      var $el = $(this);
      initSelect2($el);
      $el.select2('open');
    });

    // Keyboard users: build select2 and hand focus to its container so tab order
    // is not broken by the original <select> being hidden.
    $(document).on('focusin', selector, function () {
      var $el = $(this);
      initSelect2($el);
      var instance = $el.data('select2');
      if (instance && instance.$container) {
        instance.$container.find('.select2-selection').trigger('focus');
      }
    });
  }

  /* ---------------------------------------------------------------------------
   * 3. Debounced field validation
   * ------------------------------------------------------------------------ */

  function bindDebouncedValidation() {
    var $form = $('#createcontract');
    if (!$form.length) {
      return;
    }

    // Drop contract.js's per-keystroke handler. It is the only change/input
    // handler bound directly to the form element.
    $form.off('change input');

    function validate(el) {
      if (!$.fn.valid) {
        return;
      }
      try {
        $(el).valid();
      } catch (err) {
        /* jQuery Validate is cosmetic here — never let it break the form. */
      }
    }

    $form.on('change', 'input, select, textarea', function () {
      validate(this);
    });

    $form.on('blur', 'input, textarea', function () {
      validate(this);
    });

    var timer = null;
    $form.on('input', 'input, textarea', function () {
      var el = this;
      clearTimeout(timer);
      timer = setTimeout(function () {
        validate(el);
      }, 400);
    });
  }

  /* ---------------------------------------------------------------------------
   * 4. On-demand party name lists
   * ------------------------------------------------------------------------ */

  function bindPartySubTypeHandler() {
    // Replace contract.js's handler. Same contract (populate the <option> list,
    // then fire `ajax:done` so contractArti.js can select the AI-matched party),
    // but it hits the cached V2 endpoint and leaves select2 construction to the
    // lazy initialiser instead of rebuilding a widget over every option.
    $(document).off('change', '.partySubType');

    $(document).on('change', '.partySubType', function () {
      // Read the attribute rather than .data(): rows are re-indexed with
      // .attr('data-party-row'), and jQuery's .data() cache would keep the stale
      // index and populate the wrong row's dropdown.
      var currentRow = $(this).attr('data-party-row');
      var subType = $(this).val();
      var $nameSelect = $('#partyExternal_' + currentRow);

      if (!$nameSelect.length) {
        return;
      }

      destroySelect2($nameSelect);

      // No type chosen -> just clear the name list, no request needed.
      if (!subType) {
        $nameSelect.html('<option value="">- Select -</option>');
        return;
      }

      $.ajax({
        method: 'POST',
        url: appUrl(PARTY_LIST_URL),
        data: { partySubType: subType },
        headers: {
          'X-CSRF-TOKEN': csrfToken()
        },
        success: function (response) {
          var results = (response && response.results) || [];
          var html = ['<option value="">- Select -</option>'];

          for (var i = 0; i < results.length; i++) {
            html.push(
              '<option value="' + escapeHtml(results[i].id) + '">' + escapeHtml(results[i].text) + '</option>'
            );
          }

          destroySelect2($nameSelect);
          // Single DOM write for the whole list.
          $nameSelect.html(html.join(''));
          $nameSelect.trigger('ajax:done');
        },
        error: function (xhr) {
          console.error(xhr.responseText);
        }
      });
    });
  }

  /* ---------------------------------------------------------------------------
   * 5. Bits of form-layouts.js this page still needs
   * ------------------------------------------------------------------------ */

  function initStickyAndHelpers() {
    if (window.Helpers && typeof window.Helpers.initCustomOptionCheck === 'function') {
      window.Helpers.initCustomOptionCheck();
    }

    var $stickyEl = $('.sticky-element');
    if (!$stickyEl.length || !$.fn.sticky) {
      return;
    }

    var topSpacing = 0;
    if (window.Helpers && typeof window.Helpers.isNavbarFixed === 'function' && Helpers.isNavbarFixed()) {
      topSpacing = $('.layout-navbar').height() + 7;
    }

    $stickyEl.sticky({ topSpacing: topSpacing, zIndex: 9 });
  }

  /* ------------------------------------------------------------------------ */

  $(function () {
    whenSelect2Ready(function () {
      bindLazySelect2();
      bindPartySubTypeHandler();
      bindDebouncedValidation();
      initStickyAndHelpers();
    });
  });
})();
