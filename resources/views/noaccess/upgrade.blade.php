@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Maintenance Mode')

@section('page-style')
<!-- Page -->
@vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection


@section('content')
<!-- Not Authorized -->
<div class="container">
  <div class="text-center">
    <h2 class="mb-1 mx-2">Sorry!</h2>
    <p class="mb-4 mx-2">File Upgradation In Progress Please Try After Some Time or contact your administrator.</p>
    <a href="{{url('/contracts/list')}}" class="btn btn-warning mb-4">Retry</a>
    <a href="{{url('/logout')}}" class="btn btn-danger mb-4">Logout</a>
  </div>
</div>
<!-- /Not Authorized -->
@endsection