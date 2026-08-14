 
 @section('vendor-style')
@vite('resources/assets/vendor/libs/flatpickr/flatpickr.scss')
@endsection



 <div class="group-ry">
     <div class="clearfix">
         <input type="hidden" class="hidden index" name="Partygroup[party][][index]" value="">
         <div class="col-sm-6 partygroupwrap">
             <label for="staus">Party <span class="partyc"></span></label>
             <div class="col mt-2">
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="contract">
                    <input name="Partygroup[party][][mode]" class="form-check-input partygroup" type="radio"  id="Internal" value="Internal" />
                    Internal</label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="contract">
                    <input name="Partygroup[party][][mode]" class="form-check-input partygroup" type="radio"  id="External" value="External" checked />
                     External </label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="contract">
                        <input name="Partygroup[party][][mode]" class="form-check-input partygroup" type="radio" id="Intergroup" value="Intergroup" />
                        Inter-Group </label>
                </div>                
            </div>
         </div>
         <button class="btn btn-danger removerow" style="float: right;">Delete</button>
     </div>
     
     <div class="clearfix Internal" style="display: none">
         
         <div class="row mb-3">
            @if(!env('entity_auto_choose'))
             <div class="col-6" style="{{ env('entity_auto_choose') ? 'display: none;' : ''}}">
                 <label for="sel1">Party Name</label>
                 <select class="form-select select2 contractname"  name="Partygroup[party][][internal_name]">
                     @foreach ($entities as $entitie)
                     <option value="{{ $entitie->id }}">{{ $entitie->Nameoftheentity}}</option>
                     @endforeach
                 </select>
                 
             </div>
             
            @else
                <input name="Partygroup[party][][internal_name]" class="contractname isinput" type="hidden" value="{{ session()->get('contractSessionEntity') }}" />
            @endif             
             
             <div class="col-6">
                 <label for="sel1">Location (Branch Address)</label>
                 <select class="form-select select2 partycontracttype allBranch" name="Partygroup[party][][location_grp]">
                     <option value="">-Select-</option>
                    @foreach ($branchs as $key => $branch)
                        <option value="{{ $branch->id }}">
                            {{ $branch->LegalName }}
                        </option>
                    @endforeach

                 </select>
                 <select class="form-select select2 partycontracttype userBranch" name="Partygroup[party][][location]">
                     <option value="">-Select-</option>
                    @foreach ($branchsUser as $key => $branch)
                        <option value="{{ $branch->id }}">
                            {{ $branch->LegalName }}
                        </option>
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
     <div class="clearfix External">
         <div class="col-sm-6 mt-2">
            <div class="row">
                <div class="col-4">
                    <label for="sel1">Type</label>
                    <select  name="Partygroup[party][][external_type]" class="form-select select2 partySubType" data-party-row="" data-allow-clear="true">
                        <option value="">-Select-</option>
                        <option value="organization">Organization</option>
                        @if(!env('hideIndividuals'))
                        <option value="individual">Individual</option>
                        @endif
                    </select> 
                </div>
                <div class="col-8">
                    <label for="sel1">Party Name</label>
                    <select class="form-select select2 partyExternal search-select" id="partyExternal" name="Partygroup[party][][external_name]">
                        <option value="">-Select-</option>
                    </select>
                </div>
            </div>
             
             
             
             <div class="address-external mt-3">
                 <ul class="external-address-list" style="list-style-type: none;">
                     @foreach ($contractParties as $contractPartie)
                     <li id="{{ $contractPartie->id }}" style="display:none;">
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

             {{-- V3: pre-filled from the selected party master by contractV3.js and read-only,
                  so they can only be changed on the party master. readonly (not disabled) so
                  the values still post.
                  The empty name index is renumbered by addMorePartis() in contract.js. --}}
             <div class="row mt-2 party-v3-fields">
                 <div class="col-md-4">
                     <label>Vendor Code</label>
                     <input type="text" class="form-control party-vendor-code" name="Partygroup[party][][vendor_code]">
                 </div>
                 <div class="col-md-4">
                     <label>Contact Details</label>
                     <input type="text" class="form-control party-contact-details" name="Partygroup[party][][contact_details]" readonly>
                 </div>
                 <div class="col-md-4">
                     <label>Address</label>
                     <textarea class="form-control party-address" rows="2" name="Partygroup[party][][party_address]" readonly></textarea>
                 </div>
             </div>

         </div>
     </div>
 </div> 