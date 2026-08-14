@extends('layouts/layoutMaster')

@section('title', 'Menu Configurations')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/tagify/tagify.scss'])
@endsection

@section('vendor-script')
    <link href="{{ url('/') }}/assets/css/custom.css" rel="stylesheet" />
    <link href="{{ url('/') }}/Modules/Contractsetup/resources/assets/css/customfields.css" rel="stylesheet" />
@endsection

@section('page-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/js/forms-selects.js'])
    <script type="module" src="{{ url('/') }}/Modules/Contractsetup/resources/assets/js/jquery-ui.js"></script>
    <script type="module" src="{{ url('/') }}/assets/js/jquery.validate.min.js"></script>
    <script type="module" src="{{ url('/') }}/Modules/Contractsetup/resources/assets/js/jquery.serialize-object.js">
    </script>
    <script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/menuConfig.js"></script>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Menu Configurations</h5>
    <div>
      <a href="javascript:void(0)" id="btnAddMenu" class="btn btn-primary btn-sm">Add Menu</a>
    </div>
  </div>
  <div class="card-body">
    <table class="table table-striped" id="menuConfigsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Menu Type</th>
          <th>Role</th>
          <th>Active</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($configs as $c)
        <tr>
          <td>{{ $c->id }}</td>
          <td>{{ $c->menu_type }}</td>
          <td>{{ $c->role }}</td>
          <td>{{ $c->active ? 'Yes' : 'No' }}</td>
          <td>
            <a href="javascript:void(0)" class="btn btn-sm btn-info btn-edit" data-id="{{ $c->id }}">Edit</a>
            <a href="javascript:void(0)" class="btn btn-sm btn-danger btn-delete" data-id="{{ $c->id }}">Delete</a>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

@include('contract-setup::admin.menus._modal')

@endsection