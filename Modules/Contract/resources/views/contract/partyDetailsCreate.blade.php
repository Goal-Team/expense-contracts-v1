@php
$col6 = $colset ?? 6;
$horizontalForm = $horizontalForm ?? false;
@endphp

@if(!$horizontalForm)
@foreach($contractPartys as $index => $contractParty)
<div class="group-ry gropuid{{$index}}">
    <input type="hidden" name="Partygroup[party][{{$index}}][id]" value="{{$index}}">
    <div class="clearfix">
        <input type="hidden" class="hidden index" name="Partygroup[party][{{$index}}][index]" value="">
        <div class="col-sm-{{$col6}} partygroupwrap">
            <label for="staus">Party <span class="partyc">{{ $index + 1}}</span></label>

            <div class="col mt-2">
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="contract">
                        <input name="Partygroup[party][{{$index}}][mode]" class="form-check-input partygroup" type="radio" id="Internal" value="Internal" {{($contractParty['mode'] ?? 'Internal') == 'Internal' ? 'checked' : ''}} />
                        Internal</label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="contract">
                        <input name="Partygroup[party][{{$index}}][mode]" class="form-check-input partygroup" type="radio" id="External" {{($contractParty['mode'] ?? 'Internal') == 'External' ? 'checked' : ''}} value="External" />
                        External </label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="contract">
                        <input name="Partygroup[party][{{$index}}][mode]" class="form-check-input partygroup" type="radio" id="Intergroup" {{($contractParty['mode'] ?? 'Internal') == 'Intergroup' ? 'checked' : ''}} value="Intergroup" />
                        Inter-Group </label>
                </div>
            </div>
        </div>
    </div>

    <div class="clearfix Internal" style="{{ ($contractParty['mode'] ?? 'Internal') == 'External' ? 'display: none;' : '' }}">

        <div class="row mb-3 mt-2">
            @if(!env('entity_auto_choose'))
                <div class="col" style="{{ env('entity_auto_choose') ? 'display: none;' : ''}}">
                    <label for="sel1">Party Name</label>
                    <select class="form-select select2 contractname" name="Partygroup[party][{{$index}}][internal_name]">
                        @foreach ($entities as $entitie)
    
                        @if($entitie->id == ($contractParty['internal_name'] ?? 0) || $entitie->id = session()->get('contractSessionEntity'))
                        <option value="{{ $entitie->id }}" selected>{{ $entitie->Nameoftheentity }}</option>
                        @else
                        <option value="{{ $entitie->id }}">{{ $entitie->Nameoftheentity }}</option>
                        @endif
    
                        @endforeach
                    </select>
    
                </div>
            @else
                <input name="Partygroup[party][{{$index}}][internal_name]" class="contractname isinput" type="hidden" value="{{ session()->get('contractSessionEntity') }}" />
            @endif



            <div class="col-{{$col6}}">
                <label for="sel1">Location (Branch Address)</label>
                <select class="form-select select2 partycontracttype allBranch" name="Partygroup[party][{{$index}}][location_grp]" style="{{ ($contractParty['mode'] ?? 'Internal') == 'Internal' ? 'display: none;' : '' }}">
                    <option value="">-Select-</option>
                    @foreach ($branchs as $branch)


                    @if($branch->id == ($contractParty['location_grp'] ?? 0))
                    <option value="{{ $branch->id }}" selected>{{ $branch->LegalName}}</option>
                    @else
                    <option value="{{ $branch->id }}">{{ $branch->LegalName}}</option>
                    @endif


                    @endforeach
                </select>
                
                <select class="form-select select2 partycontracttype userBranch" name="Partygroup[party][{{$index}}][location]" style="{{ ($contractParty['mode'] ?? 'Internal') == 'Intergroup' ? 'display: none;' : '' }}">
                    <option value="">-Select-</option>
                    @foreach ($branchsUser as $branch)


                    @if($branch->id == ($contractParty['location'] ?? 0))
                    <option value="{{ $branch->id }}" selected>{{ $branch->LegalName}}</option>
                    @else
                    <option value="{{ $branch->id }}">{{ $branch->LegalName}}</option>
                    @endif


                    @endforeach
                </select>

            </div>
        </div>
    </div>
    <div class="clearfix External" style="{{ in_array(($contractParty['mode'] ?? 'Internal'), ['Internal','Intergroup']) ? 'display: none;' : '' }}">

        <div class="col-sm-{{$col6}} mt-2">
            <div class="row">
                <div class="col-4">
                    <label for="sel1">Type</label>
                    <select  name="Partygroup[party][{{$index}}][external_type]" id="partyExternal_{{ $index }}_type" class="form-select select2 partySubType" data-party-row="{{ $index }}" data-allow-clear="true">
                        <option value="">-Select-</option>
                        <option value="organization" {{ ($contractParty['external_type'] ?? '') == 'organization' ? 'selected' : '' }}>Organization</option>
                        @if(!env('hideIndividuals'))
                        <option value="individual" {{ ($contractParty['external_type'] ?? '') == 'individual' ? 'selected' : '' }}>Individual</option>
                        @endif
                    </select> 
                </div>
                <div class="col-8">
                    <label for="sel1">Party Name</label>
                    <select class="form-select select2 partyExternal search-select" id="partyExternal_{{ $index }}" name="Partygroup[party][{{$index}}][external_name]">
                        <option value="">-Select-</option>
                        @if(($contractParty['external_type'] ?? '') != "")
                          @foreach ($contractParties as $contractPartie)
                            @if( $contractPartie->party_sub_type == $contractParty['external_type'] && $contractParty['external_type'] == 'individual')
                                <option value="{{ $contractPartie->id }}" {{ $contractParty['external_name'] == $contractPartie->id ? 'selected' : '' }}>{{decryptString($contractPartie->company_name, 'company_name')}}</option>
                            @endif
                            @if( $contractPartie->party_sub_type != 'individual' && $contractParty['external_type'] != 'individual')
                                <option value="{{ $contractPartie->id }}" {{ $contractParty['external_name'] == $contractPartie->id ? 'selected' : '' }}>{{decryptString($contractPartie->company_name, 'company_name')}}</option>
                            @endif
                          @endforeach
                        @endif
                    </select>
                </div>
            </div>
             <div class="address-external mt-3">
                 <ul class="external-address-list" style="list-style-type: none;">
                     @foreach ($contractParties as $contractPartie)
                     <li id="{{ $contractPartie->id }}" style="display:{{ ($contractParty['external_name'] ?? '') == $contractPartie->id ? '' : 'none'}};">
                        <div class="row">
                            <div class="col-md-4">Building no : {{ $contractPartie->building_no}} </br></div>
                            <div class="col-md-4">Area name: {{ $contractPartie->area_name}}</br></div>
                            <div class="col-md-4">Landmark : {{ $contractPartie->landmark}}</br></div>
                            <div class="col-md-4">City: {{ $contractPartie->city}}</br></div>
                            <div class="col-md-4">State: {{ get_state($contractPartie->state)}}</br></div>
                            <div class="col-md-4">Pincode: {{ $contractPartie->pincode}}</br></div>
                            <div class="col-md-4">Country: {{ get_country($contractPartie->country)}}</br></div>

                        </div>
                         
                     </li>
                     @endforeach
                 </ul>
             </div>            
        </div>
    </div>

</div>

@endforeach

@else
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
        <tr class="group-ry gropuid{{ $index }}" data-index="{{ $index }}">
            <input type="hidden" name="Partygroup[party][{{ $index }}][id]" value="{{ $index }}">
            <input type="hidden" name="Partygroup[party][{{ $index }}][index]" value="">
        
            {{-- Party label --}}
            <td>Party {{ $index + 1 }}</td>
        
            {{-- Mode radio buttons --}}
            <td>
                <div class="form-check form-check-inline">
                    <label class="form-check-label">
                        <input name="Partygroup[party][{{ $index }}][mode]" class="form-check-input partygroup" type="radio" value="Internal" {{ ($contractParty['mode'] ?? 'Internal') === 'Internal' ? 'checked' : '' }}> Internal
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label">
                        <input name="Partygroup[party][{{ $index }}][mode]" class="form-check-input partygroup" type="radio" value="External" {{ ($contractParty['mode'] ?? 'Internal') === 'External' ? 'checked' : '' }}> External
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label">
                        <input name="Partygroup[party][{{ $index }}][mode]" class="form-check-input partygroup" type="radio" value="Intergroup" {{ ($contractParty['mode'] ?? 'Internal') === 'Intergroup' ? 'checked' : '' }}> Inter-Group
                    </label>
                </div>
            </td>
        
            {{-- Type / Location --}}
            <td>
                <div class="location-wrap">
                    <select class="form-select select2 mt-1" name="Partygroup[party][{{ $index }}][location]">
                        <option value="">-Select-</option>
                        @foreach($branchsUser as $branch)
                            <option value="{{ $branch->id }}" {{ $branch->id == ($contractParty['location'] ?? 0) ? 'selected' : '' }}>
                                {{ $branch->LegalName }}
                            </option>
                        @endforeach
                    </select>
                </div>
        
                <div class="location-grp-wrap mt-1">
                    <select class="form-select select2" name="Partygroup[party][{{ $index }}][location_grp]">
                        <option value="">-Select-</option>
                        @foreach($branchs as $branch)
                            <option value="{{ $branch->id }}" {{ $branch->id == ($contractParty['location_grp'] ?? 0) ? 'selected' : '' }}>
                                {{ $branch->LegalName }}
                            </option>
                        @endforeach
                    </select>
                </div>
        
                <div class="external-type-wrap mt-1 tt">
                    <select name="Partygroup[party][{{ $index }}][external_type]" class="form-select select2 partySubType" data-party-row="{{ $index }}" id="partyExternal_{{ $index }}_type">
                        <option value="organization" {{ ($contractParty['external_type'] ?? '') == 'organization' ? 'selected' : '' }}>Organization</option>
                        @if(!env('hideIndividuals'))
                            <option value="individual" {{ ($contractParty['external_type'] ?? '') == 'individual' ? 'selected' : '' }}>Individual</option>
                        @endif
                    </select>
                </div>
            </td>
        
            {{-- Party Name --}}
            <td>
                <div class="internal-name-wrap">
                    @if(!env('entity_auto_choose'))
                        <input type="hidden" name="Partygroup[party][{{ $index }}][internal_name]" value="{{ session('contractSessionEntity') }}">
                    @else
                        <select class="form-select select2" name="Partygroup[party][{{ $index }}][internal_name]">
                            @foreach($entities as $entitie)
                                <option value="{{ $entitie->id }}" {{ $entitie->id == ($contractParty['internal_name'] ?? session('contractSessionEntity')) ? 'selected' : '' }}>
                                    {{ $entitie->Nameoftheentity }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
        
                <div class="external-name-wrap mt-1 ">
                    <select class="form-select select2 partyExternal" name="Partygroup[party][{{ $index }}][external_name]" id="partyExternal_{{ $index }}">
                        <option value="">-Select-</option>
                        @foreach ($contractParties as $contractPartie)
                            <option data-ttt="{{ $contractParty['external_name'] ?? '' }}" value="{{ $contractPartie->id }}" {{ ($contractParty['external_name'] ?? '') == $contractPartie->id ? 'selected' : '' }}>
                                {{ decryptString($contractPartie->company_name, 'company_name') }}
                            </option>
                        @endforeach
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
@endif
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
                        @foreach($branchsUser as $branch)
                            <option value="{{ $branch->id }}">
                                {{ $branch->LegalName }}
                            </option>
                        @endforeach
                    </select>
                </div>
        
                <div class="location-grp-wrap mt-1">
                    <select class="form-select select2" name="Partygroup[party][__INDEX__][location_grp]">
                        <option value="">-Select-</option>
                        @foreach($branchs as $branch)
                            <option value="{{ $branch->id }}">
                                {{ $branch->LegalName }}
                            </option>
                        @endforeach
                    </select>
                </div>
        
                <div class="external-type-wrap mt-1 iii">
                    <select name="Partygroup[party][__INDEX__][external_type]" class="form-select select2 partySubType subTypeNew" id="partyExternal___INDEX___type" data-party-row="__INDEX__">
                        <option value="">-Select-</option>
                        <option value="organization">Organization</option>
                        @if(!env('hideIndividuals'))
                            <option value="individual">Individual</option>
                        @endif
                    </select>
                </div>
            </td>
        
            {{-- Party Name --}}
            <td>
                <div class="internal-name-wrap">
                    @if(!env('entity_auto_choose'))
                        <input type="hidden" name="Partygroup[party][__INDEX__][internal_name]" value="{{ session('contractSessionEntity') }}">
                    @else
                        <select class="form-select select2" name="Partygroup[party][__INDEX__][internal_name]">
                            @foreach($entities as $entitie)
                                <option value="{{ $entitie->id }}" {{ $entitie->id == session('contractSessionEntity') ? 'selected' : '' }}>
                                    {{ $entitie->Nameoftheentity }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
        
                <div class="external-name-wrap mt-1">
                    <select class="form-select select2 partyExternal" name="Partygroup[party][__INDEX__][external_name]" id="partyExternal___INDEX__">
                        <option value="">-Select-</option>
                    </select>
                </div>
            </td>
        
            <td>
                <button type="button" class="btn btn-danger delete-party-row">Delete</button>
            </td>
        </tr>
</template>