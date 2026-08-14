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
                        <input name="Partygroup[party][{{$index}}][mode]"
                            {{ $contractParty->contract_party_type == 'Internal' ? 'checked' : '' }}
                            class="form-check-input partygroup"
                            type="radio" disabled value="internal" {{$contractParty->contract_party_type == 'internal' ? 'checked' : ''}} />
                        Internal</label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="contract">
                        <input name="Partygroup[party][{{$index}}][mode]" class="form-check-input partygroup"
                            {{ $contractParty->contract_party_type == 'External' ? 'checked' : '' }}
                            disabled type="radio" {{$contractParty->contract_party_type == 'External' ? 'checked' : ''}} value="External" />
                        External </label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="contract">
                        <input name="Partygroup[party][{{$index}}][mode]" class="form-check-input partygroup"
                            {{ $contractParty->contract_party_type == 'Intergroup' ? 'checked' : '' }}
                            disabled type="radio" {{$contractParty->contract_party_type == 'Intergroup' ? 'checked' : ''}} value="Intergroup" />
                        Inter-Group </label>
                </div>
            </div>
        </div>
    </div>


    <div class="clearfix Internal" {!! $contractParty->contract_party_type == 'External' ? 'style="display: none"' : '' !!}>

        <div class="row mb-3 mt-2">
            @if(!env('entity_auto_choose'))
                <div class="col">
                    <h6 class="mb-1">Party Name</h6>
                    @foreach ($entities as $entitie)
                        @if($entitie->id == $contractParty->contract_party_id)
                        {{ $entitie->Nameoftheentity}}
                        @endif
                    @endforeach
    
                </div>
            @endif



            <div class="col">
                <h6 class="mb-1">Location (Branch Address)</h6>
                    @foreach ($branchsAll as $branch)
                        @if($branch->id == $contractParty->contract_party_location_id)
                            {{ $branch->LegalName}}
                        @endif
                    @endforeach

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
    <div class="clearfix External" {!! in_array($contractParty->contract_party_type , ['Internal','Intergroup']) ? 'style="display: none"' : '' !!}>
        <div class="col-sm-6 mt-2">
            @foreach ($contractParties as $contractPartie)
                @if($contractPartie->id == $contractParty->contract_party_exe_id)            
                    <div class="row">
                        <div class="col-4">
                            <label for="sel1">Type</label>
                            <p>{{ $contractPartie->party_sub_type != 'individual' ? 'Organization' : 'Individual' }}</p>
                        </div>
                        <div class="col-8">
                        
                            <label class="mb-2">Party Name</label><br/>
            
                                    {{ decryptString($contractPartie->company_name, 'company_name') }}
            
            
                      </div>
                    <ul class="external-address-list mt-2" style="list-style-type: none;">
                        <li id="{{ $contractPartie->id }}">
                            <div class="row">
                                <div class="col-md-4 mt-2">Building no : {{ $contractPartie->building_no}} </br></div>
                                <div class="col-md-4 mt-2">Area name: {{ $contractPartie->area_name}}</br></div>
                                <div class="col-md-4 mt-2">Landmark : {{ $contractPartie->landmark}}</br></div>
                                <div class="col-md-4 mt-2">City: {{ $contractPartie->city}}</br></div>
                                <div class="col-md-4 mt-2">State: {{ get_state($contractPartie->state)}}</br></div>
                                <div class="col-md-4 mt-2">Pincode: {{ $contractPartie->pincode}}</br></div>
                                <div class="col-md-4 mt-2">Country: {{ get_country($contractPartie->country)}}</br></div>
    
                            </div>
    
                        </li>
                    </ul>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
    <hr>
</div>


@endforeach