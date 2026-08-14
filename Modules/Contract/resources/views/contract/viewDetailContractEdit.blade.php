<style>
    .accordion-item.has-error {
    border: 1px solid red !important;
}

.buy-now .btn-buy-now {
    box-shadow: 0 1px 20px 1px #3035a9 !important;
}
</style>

@if ($errors->any())
    <div class="alert alert-danger mt-2 sticky-element">
        <h5 class="alert-heading mb-2">Errors Details</h5>
        <ul class="list-unstyled mb-0">
            @foreach ($errors->all() as $error)
                <li class="text-dark"><i class="ti ti-exclamation-circle text-danger"></i> {!! ucwords($error) !!}</li>
            @endforeach
        </ul>
    </div>
@endif


@if (strtolower($contract->contract_status) == 'executed' && !$errors->any())
<div class="modal animate__animated animate__bounce show" id="editAlert" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
         <div class="modal-header border-0">
             <h5 class="modal-title"><i class="ti ti-exclamation-circle text-danger ti-md"></i> Warning</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            </button>
         </div>
         <div class="modal-body">
            @if (strtolower($contract->substatus) == 'renewed')
                <h6>Modification is not permitted; the contract has been renewed.</h6>
            @else
                <h6>Please note that any changes made will require approval. Ensure everything is confirmed before submit.</h6>
            @endif             
         </div>
        <div class="modal-footer">
            <a role="button" class="btn btn-label-secondary waves-effect" href="{{ url('contracts/'.$contract->id) }}">Cancel</a>
            <button type="button" class="btn btn-success waves-effect waves-light" data-bs-dismiss="modal">Okay</button>
          </div>        
      </div>
   </div>
</div>
<script>
  window.addEventListener('DOMContentLoaded', function () {
    const editAlert = new bootstrap.Modal(document.getElementById('editAlert'), {
      backdrop: 'static',
      keyboard: false
    });
    editAlert.show();
  });
</script>
@endif            
<div class="row my-4">
    <div class="col">
        <form class="row" id="createcontract" action="update/contract" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="contractid" value="{{$contract->id}}">
            <div class="col-md mb-4 mb-md-2">
                @include('contract::contract.editRenew',['renewContract' => false])
            </div>
        @if (!(strtolower($contract->substatus) == 'renewed'))
            <div class="buy-now">
                <button type="submit" class="btn-buy-now btn btn-primary me-sm-3 me-1 waves-effect waves-light">Submit</button>
            </div>
        @endif
        </form>
    </div>
</div>
</div>
</div>
</div>
<!--<h6> Collapsible Section </h6>-->
</div>
</div>
</div>
</div>
</form>
</div>
</div>