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
'resources/assets/vendor/libs/quill/quill.js'
])

<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')

<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/blob.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/filesaver.js"></script>
<script type="text/javascript" src="{{url('/')}}/Modules/Contract/resources/assets/js/htmdocx.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/inline-comment.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/fileversion.js"></script>

@endsection
@section('content')

<style>
    body{background:#fff}#quill-editor{position:relative}.inline-comment{background-color:#fff;border:1px solid #ccc;box-shadow:0 0 5px #ddd;color:#444;padding:5px 12px;white-space:nowrap}.commentText{border:none;display:block;resize:none}.commentText:focus{outline:none}.inline-comment-bottom{margin-top:15px;text-align:right}.annotation{background:#a5caf2}
    .ql-toolbar {
            position: sticky;
            top: 0;
            z-index: 9999; 
            background: white;  
    }    
</style>


<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
   <div class="d-flex flex-column justify-content-center">
      <h4 class="mb-1 mt-3">File Versioning</h4>
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
@if(fileStorageType() == 'Google')
<iframe src="https://docs.google.com/document/d/1jzLndWyL4ZjeN_ALTNyY2ngEg6vxzdNs/" height="500" width="100%"></iframe>
@elseif(fileStorageType() == 'Local')
<div class="row my-4">
   <div class="col">
        <div class="row">
            <div class="col-md-9">
                <div class="standalone-container position-relative">
                    <div id="custom-toolbar" class="btn-group mb-2">
                        <button type="button" id="comment-button" class="btn btn-sm btn-info">Comment</button>
                        <button id="btn-html-undo" type="button" class="btn btn-sm btn-warning">Undo</button>
                        <button id="btn-html-redo" type="button" class="btn btn-sm btn-primary">Redo</button>                    
                    </div>
                    <div id="snow-container">
                    </div>
                </div>
            </div>
            <div class="col-md-3 d-none">
                Comments
                <ul class="list-group" id="comments-container">
                </ul>
            </div>
            <div class="col-md-3">
                <label class="form-label">Contract Type <span class="text-danger">*</span></label>
                <select class="form-control" name="contracttype" id="contracttype">
                    <option value="">-Select Contract Type-</option>
                    @foreach ($contractTypes as $contractType)
                        <option value="{{ $contractType->contract_type_id }}">{{ $contractType->contract_type }}
                        </option>
                    @endforeach
                </select><br/>                
                Clauses
                <div id="form-list" class="mt-3"></div>
            </div>            
        </div>
   </div>
</div>
@endif



@endsection