{{--
    One party's address, as the <li> the external-address-list holds.

    The create pages used to render this for every party and hide all but one - 10,032 hidden
    <li> and 7.6 MB of the 8.9 MB document on contracts/create-v3. They now render only the
    selected party (if any) and fetch the rest from contracts/create/party-address on pick.

    The markup lives here so the blade and the endpoint cannot drift apart. The pages that still
    pre-render the whole list - partyDetails, partyDetailsEdit, partyDetailsView - are other
    pages and were left alone.

    Expects: $contractPartie (a ContractParties row), $show (bool).
--}}
<li id="{{ $contractPartie->id }}" style="display:{{ ($show ?? false) ? '' : 'none' }};">
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
