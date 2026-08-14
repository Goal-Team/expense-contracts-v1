@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Not Authorized - Files')

@section('page-style')
<!-- Page -->
@vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection


@section('content')
<!-- Not Authorized -->
<div class="container">
  <div class="text-center">
    <h2 class="mb-1 mx-2">Invalid File Access</h2>
    <p class="mb-4 mx-2">{{ Session::get('errorMsg') }}</p>
    <a href="{{url('/contracts/list')}}" class="btn btn-danger mb-4">Back To List</a>
  </div>
</div>
<!-- /Not Authorized -->
@endsection