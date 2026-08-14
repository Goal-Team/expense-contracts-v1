@extends('layouts/layoutMaster')

@section('title', 'Contracts')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/tagify/tagify.scss'])
@endsection

@section('vendor-script')


    <link href="{{ url('/') }}/assets/css/custom.css" rel="stylesheet" />
    <link href="{{ url('/') }}/Modules/Contractsetup/resources/assets/css/customfields.css" rel="stylesheet" />
@endsection

@section('page-script')


    <!--<script type="module" src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>-->


    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/js/forms-selects.js'])



    <script type="module" src="{{ url('/') }}/Modules/Contractsetup/resources/assets/js/jquery-ui.js"></script>


    <script type="module" src="{{ url('/') }}/assets/js/jquery.validate.min.js"></script>



    <script type="module" src="{{ url('/') }}/Modules/Contractsetup/resources/assets/js/jquery.serialize-object.js">
    </script>




    <script type="module" src="{{ url('/') }}/Modules/Contractsetup/resources/assets/js/customfields.js"></script>



@endsection

@section('content')
    <h4 class="py-3 mb-0">
        <span class="text-muted fw-light">Custom Fields /</span><span class="fw-medium"> Add Custom Fields</span>
    </h4>



    <div class="row">

        <div class="card mb-4">

            <div class="row">
                <!--<h1>Custom Field</h1>-->
                <br />
                <form id="createCustom">
                    <div class="row">
                        <!-- Contract Type Selection -->
                        <div class="col-sm-6 mb-3 mt-4">
                            <label class="form-label">Contract Type <span class="text-danger">*</span></label>
                            <select class="form-control" name="contracttype" id="contracttype">
                                <option value="">-Select Contract Type-</option>
                                @foreach ($contractTypes as $contractType)
                                    <option value="{{ $contractType->contract_type_id }}">{{ $contractType->contract_type }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="subtype" value="contract" />
                        </div>
                    </div>
                    <br />
                    <h4>Add a Custom Field</h4>
                    <div class="row">
                        <!-- Category Selection -->
                        @if (!empty($categorys))
                            <div class="col-sm-6 mb-3">
                                <label class="form-label">Select Section<span class="text-danger">*</span></label>
                                <select class="form-control" name="category" id="category">
                                    <option value="">-Select Section-</option>
                                    @foreach ($categorys as $category)
                                        <option value="{{ $category->category_id }}">{{ $category->category_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <div class="row">
                        <!-- Field Name Input -->
                        <div class="col-sm-4 mb-3">
                            <label class="form-label">Field Name <span class="text-danger">*</span></label>
                            <input type="text" name="label" class="form-control" id="label">
                        </div>
                        <input type="hidden" name="val" class="form-control" id="val">
                        <!-- Field Type Selection -->
                        <div class="col-sm-2 mb-3">
                            <label class="form-label">Field Type <span class="text-danger">*</span></label>
                            <select class="select2 form-control" name="type" id="type">
                                <option selected value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="date">Date</option>
                                <option value="number">Number</option>
                                <option value="select">Select</option>
                                <option value="currency">Currency</option>
                                <option value="tablename">From DB</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Required Checkbox -->
                        <div class="col-sm-2 mb-3">
                            <label class="form-label">Required</label>
                            <input type="checkbox" name="required" value="1" id="required">
                        </div>
                        <!-- Generic field: additionally shown for every contract type -->
                        <div class="col-sm-4 mb-3">
                            <label class="form-label" for="is_generic">Applies to all contract types</label>
                            <input type="checkbox" name="is_generic" value="1" id="is_generic">
                            <small class="d-block text-muted">Shows this field on every contract type, in addition to the
                                one selected above.</small>
                        </div>
                    </div>
                    <a class="openselctopino" href="javascript:vodi(0)" style="display:none" data-bs-toggle="modal"
                        data-bs-target="#slectOption">Edit Options</a>
                    <div class="">
                        <button type="submit" class="btn btn-primary mb-4">+ Add Custom Fields</button>
                    </div>
                </form>

                <form id="groupcreateCustom">
                    <div id="form-list"></div>

                </form>

                <!-- Success and Error Messages -->
                <div id="state" class="alert alert-success" style="display:none">Created successfully</div>
                <div id="dstate" class="alert alert-danger" style="display:none">Deleted successfully</div>
                <div id="ustate" class="alert alert-warning" style="display:none">Updated successfully</div>
            </div>

            <div class="mt-4">
                <h4>Section</h4>
                <button class="btn btn-primary mt-3 mb-4 formdata">Submit</button>
                <div id="form-list"></div>
            </div>


            <div class="modal-onboarding modal fade animate__animated" id="slectOption" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content text-center">
                        <div class="modal-header border-0">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="selectoptions">
                                <div class="dropdownoptions row" id="dropdownOptions"></div>
                                {{-- <div class="row">
                                    <div class="col-2"> <a href="#" class="btn addoption">Add option</a></div>
                                    <div class="col-2">
                                        <button type="button" class="btn-label-primary btn" data-bs-dismiss="modal"
                                            aria-label="Close">Close</button>
                                    </div>
                                    <div class="col-2">
                                        <button type="button" data-dismiss="modal" style="display: none;"
                                            class="btn btn-primary saveselct ">Save changes</button>
                                    </div>
                                </div> --}}

                                <a href="#" class="btn addoption">Add option</a>
                            </form>
                        </div>


                        <div class="modal-footer">
                            <div class="saveselct-wrap" style="display: none"> 
                                <button type="button" data-dismiss="modal" 
                                    class="btn btn-primary saveselct">Save changes</button>
                            </div>
                            <div class="saveselctupdate-wrap" style="display: none">
                                <button type="button" data-dismiss="modal" 
                                    class="btn btn-primary saveselctupdate">Save changes</button>
                            </div>


                        </div>
                    </div>
                </div>
            </div>

            <!--<div class="">-->
            <!--    <button class="btn btn-primary mb-4 formdata">Submit</button>-->
            <!--    <div id="form-list"></div>-->
            <!--</div>-->
        </div>




    </div>

    <!-- Modal for Editing Select Options -->
    </div>

@endsection
