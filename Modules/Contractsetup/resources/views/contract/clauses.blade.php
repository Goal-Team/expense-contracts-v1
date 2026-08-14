@extends('layouts/layoutMaster')

@section('title', 'Clauses Configuration')

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
    <script type="module" src="{{url('/')}}/Modules/Contractsetup/resources/assets/js/clausesetup.js"></script>
@endsection

@section('content')

<style>
    .clauses-section{
        /*height: 65vh;*/
        /*overflow-x: hidden;*/
        /*overflow-y: scroll;*/
    }

    .clauses-section .bg-clause-title{
        background-color: #be843a;
        text-transform: capitalize;
        border-color: #be843a;
    }
    
    .clauses-section {
        scrollbar-width: none;
        scrollbar-color: #c5c5c5 #f6f6f6;
    }

    /* Works on Chrome, Edge, and Safari */
    .clauses-section::-webkit-scrollbar {
        display: none;
    }

    .clauses-section::-webkit-scrollbar-track {
        background: #f6f6f6;
    }

    .clauses-section::-webkit-scrollbar-thumb {
        background-color: #c5c5c5;
        border-radius: 20px;
        border: 3px solid #f6f6f6;
    }
    
    .select2-container {
      width: 300px !important;
    }    
    
    .select2-selection--single .select2-selection__rendered {
      display: block;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    
</style>

<h4 class="py-3 mb-0">
    <span class="text-muted fw-light">Clauses /</span><span class="fw-medium"> Add/Edit Clauses</span>
    </h4>
<div class="row">

        <div class="card mb-4 pt-4">

            <div class="row">
                        
                <div class="col-sm-4 col-12 mb-3">
                    <div>
                        <h4>Add a Clause Title</h4>                            
                        <form id="createCustom">
                            @if (!empty($categorys))
                                <div class="col-12 mb-3">
                                    <input type="hidden" name="contracttype" value="0"/>
                                    <label class="form-label">Clause Title<span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-0">
                                            <select class="form-select truncate-select w-100" name="category" id="category">
                                                <option value="">-Select Clause Title-</option>
                                                @foreach ($categorys as $category)
                                                    <option data-title-required="{{ $category->required }}" value="{{ $category->category_id }}">
                                                        {{ $category->category_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="ms-2">
                                            <button type="button" id="editClauseBtn" class="btn btn-warning d-none editClauseBtn" title="Edit Selected">
                                                <i class="ti ti-edit"></i>
                                            </button>                                            
                                        </div>
                                    </div>

                                </div>
                            @endif
    
                            <!-- Field Name Input -->
                            <div class="col-12 mb-3">
                                <label class="form-label">Title Description <span class="text-danger">*</span></label>
                                <input type="hidden" name="label" class="form-control" id="label">
                                <textarea name="val" class="form-control" id="val" rows="7"></textarea>
                            </div>
                            <!-- Field Type Selection -->
                            <div class="mb-3 d-none">
                                <label class="form-label">Field Type <span class="text-danger">*</span></label>
                                <select class="select2 form-control" name="type" id="type">
                                    <option value="text">Text</option>
                                    <option selected value="textarea">Textarea</option>
                                    <option value="date">Date</option>
                                    <option value="number">Number</option>
                                    <option value="select">Select</option>
                                    <option value="currency">Currency</option>
                                </select>
                            </div>
                            <!-- Required Checkbox -->
                            <div class="mb-3">
                                <label class="switch switch-danger">
                                    <input type="checkbox" class="switch-input" name="required" value="1" id="required" />
                                    <span class="switch-toggle-slider">
                                      <span class="switch-on">
                                        <i class="icon-base ti tabler-check"></i>
                                      </span>
                                      <span class="switch-off">
                                        <i class="icon-base ti tabler-x"></i>
                                      </span>
                                    </span>
                                    <span class="switch-label">Required</span>
                                </label>                                
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary mb-4">+ Add Clause Sub Sections</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-sm-8 col-12">
                    <div class="border-left-dashed ps-2">
                        <h4>Clauses</h4>
                        <div id="form-list" class="clauses-section"></div>
                        <!--<button class="btn btn-primary mt-3 mb-4 formdata float-end">Submit</button>-->
                        <div class="buy-now">
                            <button class="btn-buy-now btn btn-primary formdata me-sm-3 me-1 waves-effect waves-light">Submit</button>
                         </div>                        
                    </div>
                </div>
                
                <div id="toast-container" class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3" style="z-index: 1055;">
                  <!-- Toasts will be dynamically inserted here -->
                </div>
            </div>
            
            <div class="modal-onboarding modal fade animate__animated" id="cluaseTitleAdd" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content text-center">
                        <div class="modal-header border-0">
                            <h6 class="modal-title mb-0">Add New Clause Title</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="cluaseTitleAddForm" class="mt-0">
                                <input type="text" id="clauseTitle" class="form-control"/>
                                <label class="switch switch-danger mt-2">
                                    <input type="checkbox" class="switch-input" name="required_title" value="1" name="required_title"/>
                                    <span class="switch-toggle-slider">
                                      <span class="switch-on">
                                        <i class="icon-base ti tabler-check"></i>
                                      </span>
                                      <span class="switch-off">
                                        <i class="icon-base ti tabler-x"></i>
                                      </span>
                                    </span>
                                    <span class="switch-label">Required</span>
                                </label>                                
                                <div class="text-danger" id="titleAlert" style="display: none;"></div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary saveClauseTitle">Save</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
