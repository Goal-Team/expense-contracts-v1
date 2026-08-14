@extends('layouts/layoutMaster')
@section('title', ' Contracts')
<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/quill/typography.scss',
'resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/quill/editor.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/dropzone/dropzone.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/tagify/tagify.scss'
])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/quill/katex.js',
'resources/assets/vendor/libs/quill/quill.js',
'resources/assets/vendor/libs/cleavejs/cleave.js',
'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
'resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/dropzone/dropzone.js',
'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js'
])

<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')

@vite(['resources/assets/js/forms-file-upload.js'])
@vite(['resources/assets/js/form-layouts.js'])

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contractlist.js"></script>
@endsection
@section('content')




<style>
   .accordion-item.has-error {
      border: 1px solid red !important;
   }

   .files input {
      outline: 2px dashed #dbdade;
      outline-offset: -10px;
      -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
      transition: outline-offset .15s ease-in-out, background-color .15s linear;
      padding: 120px 0px 85px 35%;
      text-align: center !important;
      margin: 0;
      width: 100% !important;
   }

   .files input:focus {
      outline: 2px dashed #dbdade;
      outline-offset: -10px;
      -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
      transition: outline-offset .15s ease-in-out, background-color .15s linear;
   }

   .files {
      position: relative
   }

   .files:after {
      /* pointer-events: none;
      position: absolute;
      top: 62px;
      left: 5;
      width: 50px;
      right: 0;
      height: 56px;
      content: "";
      background-image: url(https://image.flaticon.com/icons/png/128/109/109612.png);
      display: block;
      margin: 0 auto;
      background-size: 100%;
      background-repeat: no-repeat; */

      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-upload' width='24' height='24' viewBox='0 0 24 24' stroke-width='2' stroke='%235d596c' fill='none' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath stroke='none' d='M0 0h24v24H0z' fill='none'/%3E%3Cpath d='M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2' /%3E%3Cpolyline points='7 9 12 4 17 9' /%3E%3Cline x1='12' y1='4' x2='12' y2='16' /%3E%3C/svg%3E") !important;
      background: #4b465c14;
      content: "";
      border-radius: 8px;
      position: absolute;
      top: 3rem;
      left: calc(50% - 23px);
      display: inline-block;
      height: 48px;
      width: 48px;
      background-repeat: no-repeat !important;
      background-position: center !important;
   }

   .color input {
      background: #fff;
   }

   .files:before {
      position: absolute;
      bottom: 10px;
      left: 0;
      pointer-events: none;
      width: 100%;
      right: 0;
      height: 57px;
      content: "Drop files here or click to upload";
      display: block;
      margin: 0 auto;
      font-weight: 600;
      text-transform: capitalize;
      text-align: center;
   }
</style>



<!--<h4 class="py-3 mb-4"><span class="text-muted fw-light">Add New Rules</span></h4>-->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
   <div class="d-flex flex-column justify-content-center">
      <h4 class="mb-1 mt-3">Contract Request</h4>
   </div>
   <div class="d-flex align-content-center flex-wrap gap-3">
      <div class="d-flex gap-3">
         <a href="{{url('/')}}" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
      </div>
   </div>
</div>
<!-- Multi Column with Form Separator -->
<!-- Collapsible Section -->
<div class="row my-4">
   <div class="col">
      <form class="" id="createcontract" action="{{ url('contracts/contractRequest') }}" method="POST" 
                enctype="multipart/form-data">
         @csrf
         <div class="col-md mb-4 mb-md-2">
            <div class="accordion mt-3" id="accordionWithIcon">
               <div class="card accordion-item active">
                  <div id="accordionWithIcon-1" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <div class="row g-3">
                           <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sel1">Location (Branch Address)</label>
                                    <select class="form-select select2" name="requestLocation">
                                        <option value="">-Select-</option>
                                        @foreach ($branchs as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->LegalName}}</option>
                                        @endforeach
                                    </select>
                                </div>
                           </div>
                           <div class="col-md-6">
                                <div class="form-group">
                                    <label >Request Description</label>
                                    <textarea class="form-control" id="requestDescription" 
                                            name="requestDescription" rows="4"></textarea>
                                </div>
                           </div>
                           <div class="col-md-6">
                                <div class="form-group">
                                    <label >Any Other Remarks</label>
                                    <textarea class="form-control" id="requestRemarks" 
                                            name="requestRemarks" rows="4"></textarea>
                                </div>
                           </div>
                           <div class="col-md-6">
                                <div class="form-group">
                                    <label >Request Attachment</label>
                                    <input type="file" id="requestDocument" name="requestDocument" class="form-control" />
                                </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <div class="buy-now">
            <button type="submit" id ="btnTermination" class="btn-buy-now btn btn-primary me-sm-3 me-1 waves-effect waves-light">Submit</button>
         </div>
      </form>



      <!--<h6> Collapsible Section </h6>-->
   </div>
</div>

<!-- Horizontal Onboarding modals -->


<!-- Form with Image horizontal Modal -->

</div>

</div>
</div>
</div>
</div>



@endsection