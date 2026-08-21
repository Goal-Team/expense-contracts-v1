{{--
  Fills the two dashboard filter selects from the shared option-list endpoint
  (spec.md section 8, names.md section 5) instead of inlining 136 <option> tags
  into the page HTML.

  One fetch, one JSON object, both lists. select2 stays a static select2 - no
  ajax: mode, no pagination.

  The fetch itself is normally started earlier, by
  partials/option-lists-head.blade.php in <head>. This file picks up the promise that
  partial left on window.contractOptionListsPromise, so the request is already in
  flight - or already finished - by the time this runs. If that partial is missing,
  this file fetches for itself and behaves exactly as it did before.

  Include this once, after the filter form and after the select2 init in
  Modules/Contract/resources/assets/js/dashboard.js has run.

  Previously selected values: put them on the select as a JSON array in
  data-selected, e.g.
      <select id="contracttype" data-selected="{{ json_encode($selcontype) }}" ...>
  and this script re-applies them once the options arrive. No attribute means
  nothing preselected.
--}}
<script type="module">
$(function () {
    var TARGETS = [
        { list: 'contractTypes', selector: '#contracttype', label: 'contract types' },
        { list: 'branches',      selector: '#contractlocs', label: 'locations' }
    ];

    // Mark the selects as loading so a filter control is never a blank box with no
    // explanation while the fetch is in flight.
    TARGETS.forEach(function (target) {
        var $el = $(target.selector);
        if (!$el.length) { return; }
        $el.prop('disabled', true).attr('data-option-state', 'loading');
        refreshSelect2($el);
    });

    // Prefer the fetch that partials/option-lists-head.blade.php already started in
    // <head>. It stores a promise, not a result, so it does not matter whether the
    // response has arrived yet: .then() on a finished promise fires immediately, and
    // .then() on one still in flight fires when it lands. Either way the options are
    // never delivered to nobody.
    //
    // No head partial on the page? Fetch here instead, exactly as before, so this file
    // still works on its own.
    var pending = window.contractOptionListsPromise || fetchOptionLists();

    pending
        .then(function (payload) {
            if (!payload || payload.ok !== true || !payload.lists) {
                throw new Error('option-lists returned no lists');
            }

            TARGETS.forEach(function (target) {
                fill(target, payload.lists[target.list]);
            });
        })
        .catch(function (error) {
            // Visible but quiet. The select stays empty and disabled and says why, so
            // it can never look populated when the fetch failed. Nothing is written to
            // the page beyond the placeholder, and nothing sensitive is logged.
            TARGETS.forEach(function (target) {
                var $el = $(target.selector);
                if (!$el.length) { return; }
                $el.empty()
                    .prop('disabled', true)
                    .attr('data-option-state', 'failed')
                    .attr('data-placeholder', 'Could not load ' + target.label + ' - reload the page');
                refreshSelect2($el);
            });

            if (window.console && console.warn) {
                console.warn('Filter options did not load:', error && error.message ? error.message : error);
            }
        });

    // The fallback path, used only when the <head> partial is absent.
    function fetchOptionLists() {
        var wanted = TARGETS.map(function (target) { return target.list; }).join(',');
        var url = "{{ route('contractOptionLists') }}" + '?lists=' + encodeURIComponent(wanted);

        return fetch(url, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('option-lists HTTP ' + response.status);
            }

            return response.json();
        });
    }

    function fill(target, options) {
        var $el = $(target.selector);
        if (!$el.length) { return; }

        if (!Array.isArray(options)) {
            // Asked for this list, did not get it. Treat it as a failure for this one
            // select rather than leaving an empty, enabled box.
            $el.empty()
                .prop('disabled', true)
                .attr('data-option-state', 'failed')
                .attr('data-placeholder', 'Could not load ' + target.label + ' - reload the page');
            refreshSelect2($el);
            return;
        }

        var selected = readSelected($el);

        $el.empty();

        options.forEach(function (option) {
            var el = document.createElement('option');
            el.value = option.id;
            el.textContent = option.text;
            if (selected.indexOf(String(option.id)) !== -1) {
                el.selected = true;
            }
            $el[0].appendChild(el);
        });

        $el.prop('disabled', false).attr('data-option-state', 'ready');
        refreshSelect2($el);
    }

    function readSelected($el) {
        var raw = $el.attr('data-selected');
        if (!raw) { return []; }

        try {
            var parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) { return []; }
            return parsed.map(function (value) { return String(value); });
        } catch (e) {
            return [];
        }
    }

    // select2 caches the rendered list, so it needs telling after the options change.
    // Harmless on a select select2 has not been initialised on yet.
    function refreshSelect2($el) {
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.trigger('change.select2');
        }
    }
});
</script>
