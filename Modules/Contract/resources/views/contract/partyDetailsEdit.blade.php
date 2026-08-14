@foreach($contractPartys as $index => $contractParty)
<div class="group-ry">
    <input type="hidden" name="Partygroup[party][{{$index}}][id]" value="{{$contractParty->id}}">
    <div class="clearfix">
        <input type="hidden" class="hidden index" name="Partygroup[party][{{$index}}][index]" value="">
        <div class="col-sm-6 partygroupwrap">
            <label for="staus">Party <span class="partyc"></span></label>

            <div class="col mt-2">
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="contract">
                        <input name="Partygroup[party][{{$index}}][mode]" class="form-check-input partygroup" type="radio" id="Internal" value="Internal" {{$contractParty->contract_party_type == 'Internal' ? 'checked' : ''}} />
                        Internal</label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="contract">
                        <input name="Partygroup[party][{{$index}}][mode]" class="form-check-input partygroup" type="radio" id="External" {{$contractParty->contract_party_type == 'External' ? 'checked' : ''}} value="External" />
                        External </label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="contract">
                        <input name="Partygroup[party][{{$index}}][mode]" class="form-check-input partygroup" type="radio" id="Intergroup" {{$contractParty->contract_party_type == 'Intergroup' ? 'checked' : ''}} value="Intergroup" />
                        Inter-Group </label>
                </div>                
            </div>
        </div>
    </div>





    <div class="clearfix Internal" style="{{ $contractParty->contract_party_type == 'External' ? 'display: none;' : '' }}">


        <div class="row mb-3 mt-2">
            @if(!env('entity_auto_choose'))
            <div class="col-6">
                <label for="sel1">Party Name</label>
                <select class="form-select select2 contractname" name="Partygroup[party][{{$index}}][internal_name]">
                    @foreach ($entities as $entitie)

                    @if($entitie->id == $contractParty->contract_party_id)
                    <option value="{{ $entitie->id }}" selected>{{ $entitie->Nameoftheentity}}</option>
                    @else
                    <option value="{{ $entitie->id }}">{{ $entitie->Nameoftheentity}}</option>
                    @endif

                    @endforeach
                </select>

            </div>
            @else
                <input name="Partygroup[party][{{$index}}][internal_name]" type="hidden" value="{{ $contractParty->contract_party_id }}" />
            @endif  



            <div class="col-6">
                <label for="sel1">Location (Branch Address)</label>
                <select class="form-select select2 partycontracttype allBranch" name="Partygroup[party][{{$index}}][location_grp]" {{ $contractParty->contract_party_type == 'Internal' ? 'display: none;' : '' }}>
                    <option value="">-Select-</option>
                    @foreach ($branchsAll as $branch)


                    @if($branch->id == $contractParty->contract_party_location_id && $contractParty->contract_party_type == 'Intergroup')
                    <option value="{{ $branch->id }}" selected>{{ $branch->LegalName}}</option>
                    @else
                    <option value="{{ $branch->id }}">{{ $branch->LegalName}}</option>
                    @endif


                    @endforeach
                </select>
                <select class="form-select select2 partycontracttype userBranch" name="Partygroup[party][{{$index}}][location]" {{ $contractParty->contract_party_type == 'Intergroup' ? 'display: none;' : '' }}>
                    <option value="">-Select-</option>
                    @foreach ($branchs as $branch)


                    @if($branch->id == $contractParty->contract_party_location_id && $contractParty->contract_party_type == 'Internal')
                    <option value="{{ $branch->id }}" selected>{{ $branch->LegalName}}</option>
                    @else
                    <option value="{{ $branch->id }}">{{ $branch->LegalName}}</option>
                    @endif


                    @endforeach
                </select>


                <div class="address-external">
                    <ul class="external-address-list">
                        @foreach ($contractParties as $contractPartie)
                         <li id="{{ $contractPartie->id }}" style="display:none">
                            Building no : {{ $contractPartie->building_no}} </br>
                            Area name: {{ $contractPartie->area_name}}</br>
                            Landmark : {{ $contractPartie->landmark}}</br>
                            City: {{ $contractPartie->city}}</br>
                            State: {{ get_state($contractPartie->state)}}</br>
                            Pincode: {{ $contractPartie->pincode}}</br>
                            Country: {{ get_country($contractPartie->country)}}</br>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="clearfix External" style="{{ in_array($contractParty->contract_party_type , ['Internal','Intergroup']) ? 'display: none;' : '' }}">

        <div class="col-sm-6 mt-2">
            <div class="row">
                <div class="col-4">
                    <label for="sel1">Type</label>
                    <select  name="Partygroup[party][{{$index}}][external_type]" id="partyExternal_{{ $index}}_type" class="form-select select2 partySubType" data-party-row="{{ $index }}" data-allow-clear="true">
                        <option value="">-Select-</option>
                        <option value="organization" {{ ($contractParty->party_sub_type ?? 'organization') == 'organization' ? 'selected' : '' }}>Organization</option>
                        @if(!env('hideIndividuals'))
                        <option value="individual" {{ $contractParty->party_sub_type == 'individual' ? 'selected' : '' }}>Individual</option>
                        @endif
                    </select> 
                </div>
                <div class="col-8">
                    <label for="sel1">Party Name</label>
                    <select class="form-select select2 partyExternal search-select" id="partyExternal_{{ $index}}" name="Partygroup[party][{{$index}}][external_name]">
                        <option value="">-Select-</option>
                          @foreach ($contractParties as $contractPartie)
                            @if( $contractPartie->party_sub_type == 'individual')
                                <option value="{{ $contractPartie->id }}" {{ $contractPartie->id == $contractParty->contract_party_exe_id ? 'selected' : '' }}>{{decryptString($contractPartie->company_name, 'company_name')}}</option>
                            @endif
                            @if( $contractPartie->party_sub_type != 'individual')
                                <option value="{{ $contractPartie->id }}" {{ $contractPartie->id == $contractParty->contract_party_exe_id ? 'selected' : '' }}>{{decryptString($contractPartie->company_name, 'company_name')}}</option>
                            @endif
                          @endforeach
                    </select>
                </div>
            </div>            
            <div class="address-external mt-3">
                <ul class="external-address-list">
                    @foreach ($contractParties as $contractPartie)

                    @if($contractPartie->id == $contractParty->contract_party_exe_id)
                    <li id="{{ $contractPartie->id }}">
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
                    @endif
                    @endforeach
                </ul>
            </div>

        </div>
    </div>

</div>

@endforeach