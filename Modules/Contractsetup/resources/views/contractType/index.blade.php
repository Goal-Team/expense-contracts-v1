@extends('layouts/layoutMaster')

@section('content')
<div class="panel-group" id="accordion">
    <div id="state" class="alert alert-success" style="display:none">Contract created successfully</div>
    <h1>
        Contract Type <a href="{{ '/contracts/contract-setup/contract-type-create'}}">Create</a>
    </h1>
    <div class="row">
        <table class="table">
            <thead>
                <tr>
                    <td>ID</td>
                    <td>Name</td>
                    <td>Action</td>
                </tr>
            </thead>
            <tbody>
                @foreach($contractTypes as $type)
                <tr>
                    <td>{{$type->contract_type_id}}</td>
                    <td>{{$type->contract_type}}</td>
                    <td>
                        <a href="contract-type-edit/{{$type->contract_type_id}}">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection

@section('footer')

@endsection