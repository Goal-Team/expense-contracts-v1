/**
 * Contract Create V3 page behaviour.
 *
 * Three concerns, all additive to contract.js (which is still loaded alongside this file):
 *   1. Tenure  - derived, read-only, recomputed from the contract start/end dates.
 *   2. Party    - vendor code / contact / address pre-filled from the party master.
 *   3. Annexures - repeater rows for annexures not present in the master list.
 */
$(function () {

    /* ------------------------------------------------------------------ *
     * 1. Tenure
     * ------------------------------------------------------------------ */

    // The duration inputs carry no ids, so they are addressed by name. Only one of the
    // three end-date fields is visible at a time, depending on the effectiveDate radio.
    var START_DATE_NAME = 'Duration[fixedDate]';
    var END_DATE_NAMES = [
        'Duration[onetimeEndDateofContract]',
        'Duration[fixedtimeEndDateofContract]',
        'Duration[terminationDate]'
    ];

    function endDateSelector() {
        return END_DATE_NAMES.map(function (name) {
            return '[name="' + name + '"]';
        }).join(', ');
    }

    /**
     * Human readable difference between two dates, e.g. "2 years 3 months 5 days".
     * Each unit is subtracted before measuring the next so the parts do not overlap.
     */
    function humanTenure(start, end) {
        var years = end.diff(start, 'years');
        var afterYears = start.clone().add(years, 'years');

        var months = end.diff(afterYears, 'months');
        var afterMonths = afterYears.clone().add(months, 'months');

        var days = end.diff(afterMonths, 'days');

        var parts = [];
        if (years > 0) {
            parts.push(years + (years === 1 ? ' year' : ' years'));
        }
        if (months > 0) {
            parts.push(months + (months === 1 ? ' month' : ' months'));
        }
        if (days > 0) {
            parts.push(days + (days === 1 ? ' day' : ' days'));
        }

        // Same start and end date is a valid single-day contract, not an empty tenure.
        return parts.length ? parts.join(' ') : '0 days';
    }

    function visibleEndDateValue() {
        var value = '';

        $(endDateSelector()).each(function () {
            var raw = $(this).val();
            if (!raw) {
                return;
            }
            // Skip fields inside a hidden branch of the duration accordion.
            if (!$(this).closest('#onetimeContract, #fixedTerm, #termination').is(':visible')) {
                return;
            }
            value = raw;
            return false;
        });

        return value;
    }

    function recalculateTenure() {
        var $tenure = $('#tenure');
        if (!$tenure.length || typeof moment === 'undefined') {
            return;
        }

        var startRaw = $('[name="' + START_DATE_NAME + '"]').val();
        var endRaw = visibleEndDateValue();

        if (!startRaw || !endRaw) {
            $tenure.val('');
            return;
        }

        // flatpickr submits Y-m-d regardless of the d-m-Y display format.
        var start = moment(startRaw, 'YYYY-MM-DD', true);
        var end = moment(endRaw, 'YYYY-MM-DD', true);

        if (!start.isValid() || !end.isValid() || end.isBefore(start)) {
            $tenure.val('');
            return;
        }

        $tenure.val(humanTenure(start, end));
    }

    // change covers both typed input and flatpickr selection (flatpickr fires a native
    // change on the underlying input), and the radios swap which end date is in play.
    $(document).on('change',
        '[name="' + START_DATE_NAME + '"], ' + endDateSelector() + ', [name="Duration[effectiveDate]"], [name="Duration[commencementDate]"]',
        recalculateTenure);

    // Populate on load so a validation bounce keeps showing the tenure.
    recalculateTenure();

    /* ------------------------------------------------------------------ *
     * 2. Party vendor code / contact / address
     * ------------------------------------------------------------------ */

    var partyMasterData = {};

    try {
        var rawPartyData = $('#partyMasterData').text();
        if (rawPartyData) {
            partyMasterData = JSON.parse(rawPartyData);
        }
    } catch (e) {
        partyMasterData = {};
    }

    /**
     * Fill a party row from the master record. The fields are read-only and owned by the
     * party master, so they are always overwritten - otherwise picking a different party
     * would leave the previous party's values behind with no way to correct them.
     */
    function fillPartyFromMaster($row, partyId) {
        var master = partyMasterData[partyId];

        // No party selected (or an unknown one): clear rather than keep stale values.
        if (!master) {
            $row.find('.party-vendor-code, .party-contact-details, .party-address').val('');
            return;
        }

        var mapping = [
            ['.party-vendor-code', master.vendor_code],
            ['.party-contact-details', master.contact],
            ['.party-address', master.address]
        ];

        mapping.forEach(function (pair) {
            $row.find(pair[0]).val(pair[1] || '');
        });
    }

    // .partyExternal is the party-name select; it is re-initialised as select2 by
    // contract.js on every "Add more parties" click, so this is delegated.
    $(document).on('change', '.partyExternal', function () {
        fillPartyFromMaster($(this).closest('.group-ry'), $(this).val());
    });

    // Pre-fill rows that already have a party selected (validation bounce / defaults).
    $('.partyExternal').each(function () {
        var partyId = $(this).val();
        if (partyId) {
            fillPartyFromMaster($(this).closest('.group-ry'), partyId);
        }
    });

    /* ------------------------------------------------------------------ *
     * 3. Custom annexure rows
     * ------------------------------------------------------------------ */

    // Indices only have to be unique within the POST, so a monotonic counter seeded past
    // any server-rendered rows is enough - no renumbering on remove.
    var customAnnexureIndex = $('#customAnnexureRows .custom-annexure-row').length;

    $('#addCustomAnnexure').on('click', function () {
        var template = $('#customAnnexureTemplate').html();
        if (!template) {
            return;
        }

        $('#customAnnexureRows').append(
            template.replace(/__INDEX__/g, customAnnexureIndex)
        );
        customAnnexureIndex++;
    });

    $(document).on('click', '.removeCustomAnnexure', function () {
        $(this).closest('.custom-annexure-row').remove();
    });

    /* ------------------------------------------------------------------ *
     * 4. Contract-type based annexure master rows
     * ------------------------------------------------------------------ */

    function annexureSampleUrl(annexureId) {
        var base = window.ANNEXURE_SAMPLE_BASE_URL || '';
        if (!base) {
            return '';
        }
        return base.replace(/\/+$/, '') + '/' + annexureId + '/sample';
    }

    function annexureRowHtml(annexure) {
        var annexureId = annexure.id;
        var titleHtml = annexure.title
            ? '<div class="text-muted small mb-1">' + $('<div/>').text(annexure.title).html() + '</div>'
            : '';

        var sampleHtml = annexure.sample_file
            ? '<a class="btn btn-sm btn-label-primary" href="' + annexureSampleUrl(annexureId) + '"><i class="ti ti-download ti-xs me-1"></i> Download Sample</a>'
            : '<span class="text-muted small">No sample available</span>';

        return '' +
            '<div class="row g-3 align-items-end mb-3 pb-3 border-bottom master-annexure-row" data-annexure-id="' + annexureId + '" data-contract-type="' + (annexure.contract_type || '') + '">' +
                '<div class="col-md-5">' +
                    '<label class="form-label" for="annexure-file-' + annexureId + '">' + $('<div/>').text(annexure.annexure_name || '').html() + '</label>' +
                    titleHtml +
                    '<input type="hidden" name="annexures[' + annexureId + '][annexure_master_id]" value="' + annexureId + '">' +
                    '<input type="file" id="annexure-file-' + annexureId + '" name="annexures[' + annexureId + '][file]" class="form-control" accept=".doc,.docx">' +
                '</div>' +
                '<div class="col-md-4">' +
                    sampleHtml +
                '</div>' +
            '</div>';
    }

    function renderAnnexureRows(items) {
        var $container = $('#annexureMasterRows');
        if (!$container.length) {
            return;
        }

        $container.empty();

        if (!items || !items.length) {
            $container.html(
                '<div class="alert alert-info mb-0" id="no-annexures-message">' +
                    'No annexures configured for the selected contract type.' +
                '</div>'
            );
            return;
        }

        var rowsHtml = '';
        items.forEach(function (annexure) {
            rowsHtml += annexureRowHtml(annexure);
        });

        $container.html(rowsHtml);
    }

    function toggleAnnexureVisibility(contractTypeId) {
        var hasContractType = !!contractTypeId;
        $('#annexureSelectHint').toggleClass('d-none', hasContractType);
        $('#annexureFields').toggleClass('d-none', !hasContractType);
    }

    function loadAnnexuresByContractType(contractTypeId) {
        var url = window.ANNEXURE_LIST_URL || '';
        toggleAnnexureVisibility(contractTypeId);

        if (!contractTypeId) {
            renderAnnexureRows([]);
            return;
        }

        if (!url) {
            return;
        }

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            data: {
                contract_type: contractTypeId || ''
            }
        }).done(function (items) {
            renderAnnexureRows(items || []);
        }).fail(function () {
            renderAnnexureRows([]);
        });
    }

    $('#contracttype').on('change', function () {
        loadAnnexuresByContractType($(this).val());
    });

    if ($('#annexureMasterRows').length) {
        loadAnnexuresByContractType($('#contracttype').val());
    }
});
