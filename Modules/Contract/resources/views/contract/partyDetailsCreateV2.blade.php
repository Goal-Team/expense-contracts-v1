{{--
    Party details (create) — V2 / optimised.

    Differences vs. partyDetailsCreate.blade.php:
      * Horizontal (table) layout only — that is the only layout the AI create page uses.
      * The "Party Name" dropdowns are NOT pre-filled with every contract party. They start
        empty (plus the currently selected option, if any) and are populated on demand by
        select2's AJAX adapter in contract-create-v2.js. The old file rendered the full
        party list once per row *and* once in the row template, which is what made the
        HTML enormous and select2 initialisation slow.
      * Branch / entity <option> markup is built once and reused instead of being looped
        (and decrypted) again for every row.

    Expected variables:
      $contractPartys    array of party rows (old input)
      $branchs           branch list  (Intergroup locations)
      $branchsUser       branch list  (Internal locations)
      $entities          entity list
      $partyNameOptions  [id => label] for pre-selected external parties only (optional)
--}}
@php
    $partyNameOptions = $partyNameOptions ?? [];

    // Build the repeated <option> blocks once. They are identical for every row, so looping
    // them per row only burns CPU — the markup itself is compressed away on the wire.
    $branchUserOptions = '';
    foreach ($branchsUser as $branch) {
        $branchUserOptions .= '<option value="' . e($branch->id) . '">' . e($branch->LegalName) . '</option>';
    }

    $branchGroupOptions = '';
    foreach ($branchs as $branch) {
        $branchGroupOptions .= '<option value="' . e($branch->id) . '">' . e($branch->LegalName) . '</option>';
    }

    $entityOptions = '';
    foreach ($entities as $entitie) {
        $entityOptions .= '<option value="' . e($entitie->id) . '">' . e($entitie->Nameoftheentity) . '</option>';
    }

    $sessionEntity = session('contractSessionEntity');
    $hideIndividuals = env('hideIndividuals');

    // NOTE: this branch is intentionally kept the same way round as the original horizontal
    // layout so behaviour does not change between the old and new page.
    $entityAutoChoose = env('entity_auto_choose');

    /**
     * Marks the selected <option> inside a prebuilt option string.
     */
    $withSelected = function (string $options, $selected) {
        if ($selected === null || $selected === '') {
            return $options;
        }
        return str_replace(
            '<option value="' . e($selected) . '">',
            '<option value="' . e($selected) . '" selected>',
            $options
        );
    };
@endphp

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Party</th>
            <th>Mode</th>
            <th>Type / Location</th>
            <th>Party Name</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody id="party-table-body">
    @foreach($contractPartys as $index => $contractParty)
        @php
            $mode         = $contractParty['mode'] ?? 'Internal';
            $location     = $contractParty['location'] ?? null;
            $locationGrp  = $contractParty['location_grp'] ?? null;
            $internalName = $contractParty['internal_name'] ?? $sessionEntity;
            $externalType = $contractParty['external_type'] ?? '';
            $externalName = $contractParty['external_name'] ?? '';
        @endphp
        <tr class="group-ry gropuid{{ $index }}" data-index="{{ $index }}">
            <input type="hidden" name="Partygroup[party][{{ $index }}][id]" value="{{ $index }}">
            <input type="hidden" name="Partygroup[party][{{ $index }}][index]" value="">

            {{-- Party label --}}
            <td>Party {{ $index + 1 }}</td>

            {{-- Mode radio buttons --}}
            <td>
                <div class="form-check form-check-inline">
                    <label class="form-check-label">
                        <input name="Partygroup[party][{{ $index }}][mode]" class="form-check-input partygroup" type="radio" value="Internal" {{ $mode === 'Internal' ? 'checked' : '' }}> Internal
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label">
                        <input name="Partygroup[party][{{ $index }}][mode]" class="form-check-input partygroup" type="radio" value="External" {{ $mode === 'External' ? 'checked' : '' }}> External
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label">
                        <input name="Partygroup[party][{{ $index }}][mode]" class="form-check-input partygroup" type="radio" value="Intergroup" {{ $mode === 'Intergroup' ? 'checked' : '' }}> Inter-Group
                    </label>
                </div>
            </td>

            {{-- Type / Location --}}
            <td>
                <div class="location-wrap">
                    <select class="form-select select2 mt-1" name="Partygroup[party][{{ $index }}][location]">
                        <option value="">-Select-</option>
                        {!! $withSelected($branchUserOptions, $location) !!}
                    </select>
                </div>

                <div class="location-grp-wrap mt-1">
                    <select class="form-select select2" name="Partygroup[party][{{ $index }}][location_grp]">
                        <option value="">-Select-</option>
                        {!! $withSelected($branchGroupOptions, $locationGrp) !!}
                    </select>
                </div>

                <div class="external-type-wrap mt-1 tt">
                    <select name="Partygroup[party][{{ $index }}][external_type]" class="form-select select2 partySubType" data-party-row="{{ $index }}" id="partyExternal_{{ $index }}_type">
                        <option value="organization" {{ $externalType == 'organization' ? 'selected' : '' }}>Organization</option>
                        @if(!$hideIndividuals)
                            <option value="individual" {{ $externalType == 'individual' ? 'selected' : '' }}>Individual</option>
                        @endif
                    </select>
                </div>
            </td>

            {{-- Party Name --}}
            <td>
                <div class="internal-name-wrap">
                    @if(!$entityAutoChoose)
                        <input type="hidden" name="Partygroup[party][{{ $index }}][internal_name]" value="{{ $sessionEntity }}">
                    @else
                        <select class="form-select select2" name="Partygroup[party][{{ $index }}][internal_name]">
                            {!! $withSelected($entityOptions, $internalName) !!}
                        </select>
                    @endif
                </div>

                {{-- Options are fetched on demand (select2 ajax). Only the current
                     selection is rendered so the form can be repopulated after a
                     validation failure. --}}
                <div class="external-name-wrap mt-1">
                    <select class="form-select select2 partyExternal party-name-remote" name="Partygroup[party][{{ $index }}][external_name]" id="partyExternal_{{ $index }}">
                        <option value="">-Select-</option>
                        @if($externalName !== '' && isset($partyNameOptions[$externalName]))
                            <option value="{{ $externalName }}" selected>{{ $partyNameOptions[$externalName] }}</option>
                        @endif
                    </select>
                </div>
            </td>

            <td>
               @if($index > 1)
                    <button type="button" class="btn btn-danger delete-party-row">Delete</button>
               @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<button type="button" class="btn btn-sm btn-primary me-sm-3 me-1" id="add-party-row">+Add more
 parties</button>

<template id="party-row-template">
    <tr class="group-ry gropuid__INDEX__" data-index="__INDEX__">

            {{-- Party label --}}
            <td>Party __NUMBER__</td>

            {{-- Mode radio buttons --}}
            <td>
                <div class="form-check form-check-inline">
                    <label class="form-check-label">
                        <input name="Partygroup[party][__INDEX__][mode]" class="form-check-input partygroup" type="radio" value="Internal"> Internal
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label">
                        <input name="Partygroup[party][__INDEX__][mode]" class="form-check-input partygroup" type="radio" value="External" checked> External
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label">
                        <input name="Partygroup[party][__INDEX__][mode]" class="form-check-input partygroup" type="radio" value="Intergroup"> Inter-Group
                    </label>
                </div>
            </td>

            {{-- Type / Location --}}
            <td>
                <div class="location-wrap">
                    <select class="form-select select2 mt-1" name="Partygroup[party][__INDEX__][location]">
                        <option value="">-Select-</option>
                        {!! $branchUserOptions !!}
                    </select>
                </div>

                <div class="location-grp-wrap mt-1">
                    <select class="form-select select2" name="Partygroup[party][__INDEX__][location_grp]">
                        <option value="">-Select-</option>
                        {!! $branchGroupOptions !!}
                    </select>
                </div>

                <div class="external-type-wrap mt-1 iii">
                    <select name="Partygroup[party][__INDEX__][external_type]" class="form-select select2 partySubType subTypeNew" id="partyExternal___INDEX___type" data-party-row="__INDEX__">
                        <option value="">-Select-</option>
                        <option value="organization">Organization</option>
                        @if(!$hideIndividuals)
                            <option value="individual">Individual</option>
                        @endif
                    </select>
                </div>
            </td>

            {{-- Party Name --}}
            <td>
                <div class="internal-name-wrap">
                    @if(!$entityAutoChoose)
                        <input type="hidden" name="Partygroup[party][__INDEX__][internal_name]" value="{{ $sessionEntity }}">
                    @else
                        <select class="form-select select2" name="Partygroup[party][__INDEX__][internal_name]">
                            {!! $withSelected($entityOptions, $sessionEntity) !!}
                        </select>
                    @endif
                </div>

                <div class="external-name-wrap mt-1">
                    <select class="form-select select2 partyExternal party-name-remote" name="Partygroup[party][__INDEX__][external_name]" id="partyExternal___INDEX__">
                        <option value="">-Select-</option>
                    </select>
                </div>
            </td>

            <td>
                <button type="button" class="btn btn-danger delete-party-row">Delete</button>
            </td>
        </tr>
</template>
