@extends('tasks::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('tasks.name') !!}</p>
@endsection
