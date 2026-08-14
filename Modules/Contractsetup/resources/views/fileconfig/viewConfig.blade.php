@extends('contract::layouts/layoutMaster')

@section('title', ' Contracts Storage')
<!-- Vendor Styles -->
@section('vendor-style')
@vite(['resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/tagify/tagify.scss'])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite(['resources/assets/vendor/libs/quill/katex.js', 'resources/assets/vendor/libs/quill/quill.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/dropzone/dropzone.js', 'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js'])

<link href="{{ url('/') }}/assets/css/custom.css" rel="stylesheet" />
@endsection
<!-- Page Scripts -->
@section('page-script')
@vite(['resources/assets/js/forms-file-upload.js'])
@vite(['resources/assets/js/form-layouts.js'])

<script type="module" src="{{ url('/') }}/assets/js/jquery.validate.min.js"></script>
<script type="module" src="{{ url('/') }}/Modules/Contract/resources/assets/js/contract.js"></script>

<script type="module">
    $(document).ready(function() {
        //$('#fileConfigUpdateForm').
    });
</script>
@endsection
@section('content')

<div class="container shadow min-vh-100 py-2">
      @if (Session::get('response'))
                        <div class="alert alert-{{Session::get('response')}} alert-dismissible fade show" role="alert">
                            <b>{{ Session::get('message') }}</b>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
    <div class="container network_wrapper col-sm p-2">
        @if(Helper::userInfo()->email == 'admin@legalitysimplified.com' || env('allow_admin_menu'))
        <form class="row createcontractnew" id="fileConfigUpdateForm" action="file-config-update" method="POST" enctype="multipart/form-data" novalidate="novalidate">
             @csrf
            <div class="mt-2">
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="local">
                        <input name="fileStorage" id="local" class="form-check-input" type="radio" value="Local" {{ $file == 'Local' ? 'checked' : '' }}>

                        Local
                    </label>
                    <p>Files will store in local server</p>
                </div>
            </div>
            <div class="mt-2">
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="google">
                        <input name="fileStorage" id="google" class="form-check-input" type="radio" value="Google" {{ $file == 'Google' ? 'checked' : '' }}>
                        Google Drive
                    </label>
                    <p>Files will store in Google Drive</p>
                </div>
            </div>
            <div class="mt-2">
                <div class="form-check form-check-inline">
                    <label class="form-check-label" for="microsoft">
                        <input name="fileStorage" id="microsoft"  class="form-check-input" type="radio" value="Microsoft" {{ $file == 'Microsoft' ? 'checked' : '' }}>
                        Microsoft
                    </label>
                    <p>Files will store in Microsoft one Drive</p>
                </div>
            </div>
            
                <div class="mt-3 col-2">
                <button type="submit" class="btn btn-primary me-sm-3 me-1 waves-effect waves-light">Save</button>
                </div>
            @else
                <span class="alert alert-warning">Oops you dont have access for this page!</span>
            @endif
        </form>
    </div>
</div>
@endsection