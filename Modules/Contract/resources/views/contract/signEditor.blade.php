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

<!-- Page Scripts -->
@section('page-script')

<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/signaturecheck.js"></script>

@endsection
@section('content')
 
 <div class="row">
     <div class="col-md-6">
        {!! $htmlDoc !!}
     </div>
     <div class="col-md-6 border-start">
         <h4>Signing Placeholder</h4>
         <img id="signMain" class="py-4" width="100" src="{{$currentSign}}" /><br/><br/>
         <button id="allPageSign" class="btn btn-sm btn-primary mt-4">Place Sign in All Pages</button>
     </div>
 </div>

@endsection