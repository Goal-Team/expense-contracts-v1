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
    <h2 class="mb-1 mx-2">Configuration Error!</h2>
    <a href="{{url('/contracts/list')}}" class="btn btn-warning mb-4">Retry</a>
    <a href="{{url('/logout')}}" class="btn btn-danger mb-4">Logout</a>
    @if(session('misconfig'))
    <div class="card mb-4">
        <h5 class="card-header text-start">Following Tables or Data Missing</h5>    
        <div class="table-responsive text-nowrap">
            <table class="table">
                <tr>
                    <th>S.No</th>
                    <th>Table Name</th>
                </tr>
                <tbody class="table-border-bottom-0">
                @foreach(session('misconfig') as $key => $txt)
                    <tr><td>{{$key+1}}</td><td>{!! $txt !!}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
  </div>
</div>
<!-- /Not Authorized -->
@endsection