@extends('layouts/layoutMaster')
@section('title', ' Contracts')
<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/quill/typography.scss',
'resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/quill/editor.scss'
])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/quill/katex.js',
'resources/assets/vendor/libs/quill/quill.js',
'resources/assets/vendor/libs/select2/select2.js', 
'resources/assets/js/forms-selects.js'])

<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/blob.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/filesaver.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/htmdocx.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/inline-comment.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/clauseTemplate.js"></script>
<script type="module">
function allowDrop(ev) {
  ev.preventDefault();
}

function drag(ev) {
  ev.dataTransfer.setData("text", ev.target.textContent);
  console.log("template none drag"+ ev.target.textContent);
}

function drop(ev) {
  ev.preventDefault();
  var data = ev.dataTransfer.getData("text");
  var selection = quill.getSelection(true);
}    
</script>

@endsection
@section('content')

<style>
body {
	background: #fff
}

#quill-editor {
	position: relative;
}

.ql-editor{
    max-height: 300px !important;
}

.inline-comment {
	background-color: #fff;
	border: 1px solid #ccc;
	box-shadow: 0 0 5px #ddd;
	color: #444;
	padding: 5px 12px;
	white-space: nowrap
}

.commentText {
	border: none;
	display: block;
	resize: none
}

.commentText:focus {
	outline: none
}

.inline-comment-bottom {
	margin-top: 15px;
	text-align: right
}

.annotation {
	background: #a5caf2
}

.clause-selection-section .bg-clause-title {
	background-color: #604a9e;
	text-transform: capitalize;
	border-color: #604a9e;
}

.ql-formvariables.ql-picker {
	width: 145px;
}
</style>


<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
   <div class="d-flex">
        <h4 class="mb-0 mt-0 align-self-center">
          Contract Template For <span id="contractTypeText" class="text-primary"></span> 
        </h4>
        <div class="demo-inline-spacing align-self-center cursor-pointer">
            <span class="badge rounded-pill bg-warning bg-glow mb-0 mt-0 ms-2" data-bs-toggle="modal" data-bs-target="#contractTypeSelector">
            Change
            </span>
        </div>
   </div>
   <div class="d-flex align-content-center flex-wrap gap-3">
      <div class="d-flex gap-3">
         <a href="{{url('/')}}" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
      </div>
   </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="list-unstyled mb-0">
            @foreach ($errors->all() as $error)
                <li class="text-dark"><i class="ti ti-exclamation-circle text-danger"></i> {{ ucwords($error) }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="row my-4">
   <div class="col">
        <div class="row">
            <div class="col-md-7">
                <div class="standalone-container position-relative">
                    <div id="custom-toolbar" class="btn-group mb-2">
                        <button id="btn-html-undo" type="button" class="btn btn-sm btn-warning">Undo</button>
                        <button id="btn-html-redo" type="button" class="btn btn-sm btn-primary">Redo</button>
                        <button id="btn-doc-downloader" type="button" class="btn btn-sm btn-success">Download</button>
                    </div>
                    <div id="snow-container">
                    </div>
                    <div class="alert-section mt-2">
                    <!-- Success and Error Messages -->
                        <div id="create_alert" class="alert alert-success" style="display:none">Template Created successfully</div>
                        <div id="error" class="alert alert-danger" style="display:none">Template Deleted successfully</div>
                        <div id="update_alert" class="alert alert-warning" style="display:none">Template Updated successfully</div>                    
                    </div>
                    <form>
                        <input type="hidden" name="contractTypeSelected" id="contractTypeSelected" value="0"/>
                        <button type="button" id="save-contract-template" class="btn btn-primary waves-effect mt-2 float-end">Save</button>
                    </form>                    
                </div>
            </div>
            <div class="col-md-5">
                <h5>Available Clauses</h5>
                <div id="form-list" class="mt-3 clause-selection-section"></div>
            </div>            
        </div>
   </div>
</div>
<div class="modal fade" id="contractTypeSelector">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Choose Contract Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col">
            <select class="form-select" name="contracttype" id="contracttype">
                <option value="">-</option>
                @foreach ($contractTypes as $contractType)
                    <option value="{{ $contractType->contract_type_id }}">{{ $contractType->contract_type }}
                    </option>
                @endforeach
            </select>
          </div>
          <div class="col mt-2">
            <select class="form-select" name="payment_type" id="payment_type">
                <option value="">- Payment Type -</option>
                <option value="Cash">Cash</option>
                <option value="Credit">Credit</option>
            </select>
          </div>
          <div class="col mt-2">
            <select class="form-select" name="entity_type_id" id="entity_type_id">
                <option value="">- Entity Type -</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary waves-effect" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection