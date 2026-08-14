@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Not Authorized - Pages')

@section('page-style')
<!-- Page -->
@vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection


@section('content')
<!-- Not Authorized -->
<div class="container">
  <div class="text-center">
    <h2 class="mb-1 mx-2">You are not authorized!</h2>
    <p class="mb-4 mx-2">You do not have permission to view this page using the credentials that you have provided while login. <br> Please contact your administrator.</p>
    <a href="{{url('/logout')}}" class="btn btn-danger mb-4">Logout</a>
  </div>
</div>
<!-- /Not Authorized -->
@endsection