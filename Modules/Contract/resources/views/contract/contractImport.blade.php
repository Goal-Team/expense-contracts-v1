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
@vite(['resources/assets/js/form-layouts.js','resources/assets/js/app-ecommerce-product-add.js'])

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contract.js"></script>
@endsection
@section('content')
<!--<h4 class="py-3 mb-4"><span class="text-muted fw-light">Add New Rules</span></h4>-->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
   <div class="d-flex flex-column justify-content-center">
      <h4 class="mb-1 mt-3">Contract Import</h4>
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
    <div class="card">
        <form  id="createcontract" action="import-store" method="POST" enctype="multipart/form-data">
            @csrf 
           <div class="col mt-3">
                 
                    <label for="csv_file">Choose CSV File</label>
                    <input type="file" name="csv_file" id="csv_file" required>
                
                
           </div>
           <div class="col mt-3 mb-3">
               <button type="submit" class="btn btn-primary me-sm-3 me-1 waves-effect waves-light">Upload</button>
           </div>
            </form>
      <!--<h6> Collapsible Section </h6>-->
   </div>
</div>
@endsection