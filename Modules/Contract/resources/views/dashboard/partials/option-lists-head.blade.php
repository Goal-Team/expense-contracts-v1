{{--
  Starts the option-list fetch as early as the page can start it (spec.md section 8,
  names.md section 5). Include this in <head>, via @section('head-prefetch').

  Why it exists: option-lists-js.blade.php runs inside $(function(){}), so its fetch
  could not start until jQuery and every other vendor script had loaded. This file has
  no dependency on anything - plain script, no jQuery - so the request leaves while the
  stylesheets are still downloading.

  No race is possible. This file does not touch the DOM and does not hold the result in
  a variable that something has to be watching. It stores the *promise* on
  window.contractOptionListsPromise, and option-lists-js.blade.php calls .then() on it
  later. Calling .then() on a promise that already finished hands the value over at
  once, so "the fetch finished before the HTML was ready" is not a case that needs
  handling - it is the normal case, and it works.

  If this partial is not included, option-lists-js.blade.php falls back to fetching for
  itself, so the two files are independent.
--}}
<script>
(function () {
    // Guard against the partial being included twice - one fetch, not two.
    if (window.contractOptionListsPromise) { return; }

    var url = "{{ route('contractOptionLists') }}" + '?lists=' + encodeURIComponent('contractTypes,branches');

    window.contractOptionListsPromise = fetch(url, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    }).then(function (response) {
        if (!response.ok) {
            throw new Error('option-lists HTTP ' + response.status);
        }

        return response.json();
    });

    // A rejected promise with nothing attached to it yet logs an unhandled-rejection
    // warning in the console. The real handling is in option-lists-js.blade.php; this
    // only stops the browser complaining in the gap between the two.
    window.contractOptionListsPromise.catch(function () {});
})();
</script>
