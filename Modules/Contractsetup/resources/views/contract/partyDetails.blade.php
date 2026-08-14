 <div class="group-ry">
     <div class="clearfix">
         <input type="hidden" class="hidden index" name="Partygroup[party][][index]" value="">
         <div class="col-sm-6 partygroupwrap">
             <label for="staus">Party <span class="partyc"></span></label>
             <div class="form-group">
                 <label class="radio-inline">
                     <input type="radio" class="partygroup" name="Partygroup[party][][mode]" value="Internal" checked>Internal
                 </label>
                 <label class="radio-inline">
                     <input type="radio" class="partygroup" name="Partygroup[party][][mode]" value="External">External
                 </label>

             </div>
         </div>
     </div>
     <div class="clearfix Internal">
         <div class="col-sm-6">
             <div class="form-group">
                 <label for="sel1">Party name</label>
                 <select class="form-control contractname"  name="Partygroup[party][][internal_name]">
                     @foreach ($entities as $entitie)
                     <option value="{{ $entitie->id }}">{{ $entitie->Nameoftheentity}}</option>
                     @endforeach
                 </select>
             </div>
         </div>
         <div class="col-sm-6">
             <div class="form-group">
                 <label for="sel1">Location (Branch Address)</label>
                 <select class="form-control partycontracttype" name="Partygroup[party][][location]">
                     <option value="">-Select-</option>
                     @foreach ($branchs as $branch)
                     <option value="{{ $branch->id }}">{{ $branch->BranchName}}</option>
                     @endforeach
                 </select>
             </div>
             <div class="address">
                 <ul class="address-list">
                     @foreach ($branchs as $branch)
                     <li id="{{ $branch->id }}" style="display:none">
                         Doorno : {{ $branch->Doorno}} </br>
                         StreetName: {{ $branch->StreetName}}</br>
                         AreaName : {{ $branch->AreaName}}</br>
                         Landmark: {{ $branch->Landmark}}</br>
                         PinCode: {{ $branch->PinCode}}</br>
                     </li>
                     @endforeach
                 </ul>
             </div>
         </div>
     </div>
     <div class="clearfix External" style="display: none">
         <div class="col-sm-6">
             <div class="form-group">
                 <label for="sel1">Party name</label>
                 <select class="form-control col-sm-12 partyExternal"  name="Partygroup[party][][external_name]">
                     <option value="">-Select-</option>
                     @foreach ($contractParties as $contractPartie)
                     <option value="{{ $contractPartie->id }}">{{ $contractPartie->company_name}}</option>
                     @endforeach
                 </select>
             </div>
             <div class="address-external">
                 <ul class="external-address-list">
                     @foreach ($contractParties as $contractPartie)
                     <li id="{{ $contractPartie->id }}" style="display:none">
                         Building no : {{ $contractPartie->building_no}} </br>
                         Area name: {{ $contractPartie->area_name}}</br>
                         Landmark : {{ $contractPartie->landmark}}</br>
                         City: {{ $contractPartie->city}}</br>
                         State: {{ $contractPartie->state}}</br>
                         Pincode: {{ $contractPartie->pincode}}</br>
                         Country: {{ $contractPartie->country}}</br>
                     </li>
                     @endforeach
                 </ul>
             </div>
         </div>
     </div>
 </div>