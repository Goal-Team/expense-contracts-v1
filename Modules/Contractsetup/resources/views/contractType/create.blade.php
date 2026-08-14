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

<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/contracttype.js"></script>
@endsection

@section('content')

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
   <div class="d-flex flex-column justify-content-center">
      <h4 class="mb-1 mt-3">Create Contract Type</h4>
   </div>
   <div class="d-flex align-content-center flex-wrap gap-3">
      <div class="d-flex gap-3">
         <a href="{{url('/')}}/contract-setup/contract-type/list" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
      </div>
   </div>
</div>
<div class="panel-group" id="accordion">
            <form action="contract-type-store" method="POST" enctype="multipart/form-data">

               @csrf
               @if ($message = Session::get('success'))
               <p class="alert alert-success">{{ $message }}</p>
               @endif
               @if ($message = Session::get('error'))
               <p class="alert alert-success">{{ $message }}</p>
               @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="list-unstyled mb-0">
                        @foreach ($errors->all() as $error)
                            <li class="text-dark"><i class="ti ti-exclamation-circle text-danger"></i> {{ ucwords($error) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif               
               <div class="row">
                  <div class="col-md-6">
                     <label class="form-label" for="GroupName">Category Group <span class="text-danger">*</span></label>
                     <select id="GroupName" name="GroupName" class="form-select select2" data-allow-clear="true">
                        <option value="">Select Group</option>
                         @foreach($catGroup as $cG)
                         <option value="{{$cG->id}}" {{ old('GroupName') == $cG->id ? 'selected' : '' }}>{{$cG->name}}</option>
                         @endforeach
                     </select>
                  </div>
                  <div class="col-sm-6">
                     <div class="form-group">
                        <label for="contractName">Contract Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control contractName" name="contractTypeName" value="{{old('contractTypeName')}}" required>
                     </div>
                  </div>
                  <div class="col-sm-6">
                     <div class="form-group">
                        <label for="contractName">Contract Short Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="contractShortName" value="{{old('contractShortName')}}" required>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label" for="DepartmentType">Department <span class="text-danger">*</span></label>
                     <select id="DepartmentType" name="DepartmentType" class="form-select select2" data-allow-clear="true" required>
                        <option value="">Select Department</option>
                         @foreach($ent as $en)
                         <option value="{{$en->id}}" {{ old('DepartmentType') == $en->id ? 'selected' : '' }}>{{$en->name}}</option>
                         @endforeach
                     </select>
                  </div>
                  <div class="col-md-6">
                     <label class="form-label" for="catgoeryType">Category <span class="text-danger">*</span></label>
                     <select id="catgoeryType" name="catgoeryType[]" class="form-select select2 DepartmentType" data-allow-clear="true" multiple required>
                         <option value="">Select Category</option>
                         @foreach($catego as $en)
                         <option value="{{$en->id}}" {{ in_array($en->id, old('catgoeryType',[])) ? 'selected' : '' }}>{{$en->name}}</option>
                         @endforeach
                     </select>
                  </div>

               </div>
               <button class="btn mt-4 btn-primary me-sm-3 me-1 waves-effect waves-light" type="submit">Submit</button>
            </form>

</div>


<div class="modal-onboarding modal fade animate__animated" id="categoryTypeAdd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content text-center">
            <div class="modal-header border-0">
                <h6 class="modal-title mb-0">Add New Category</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <form id="cluaseTitleAddForm" class="mt-0">
                    <input type="text" id="categoryTitle" class="form-control"/>
                    <span class="text-danger" id="titleAlert" style="display: none;"></span>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary saveCategoryTitle">Save</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer')

@endsection