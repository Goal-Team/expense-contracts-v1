{{--
    Add New Contract (AI) — V2 / optimised.

    Markup clone of contractCreateAi.blade.php. Every element id, name, class and
    the form action are unchanged, so contract.js / contractArti.js and the
    existing store endpoint behave exactly as they do on the original page.

    What changed, and why:
      * Vendor assets that this page never uses were removed: dropzone, tagify,
        cleave, moment and jquery-repeater, plus htmdocx.js (~470 KB), blob.js,
        filesaver.js and the jSignature CDN script. htmdocx/filesaver are only
        referenced by the #btn-doc-downloader handler, and that button does not
        exist on this page.
      * form-layouts.js and forms-file-upload.js are no longer loaded.
        forms-file-upload.js only initialises Dropzone (no dropzone here), and
        form-layouts.js built a select2 widget for every dropdown during load.
        contract-create-v2.js re-implements the parts still needed (sticky
        element, custom option check) and makes select2 lazy.
      * Page CSS moved from an inline <style> in the body to @section('page-style')
        so it is parsed with the rest of the stylesheets instead of mid-document.
      * Party and custom-field partials swapped for their V2 counterparts.
      * The five unbalanced </div> tags at the end of the original file are gone;
        they closed layout wrappers that this view does not own.
--}}
@extends('layouts/layoutMaster')
@section('title', ' New Contract Form')

<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/quill/typography.scss',
'resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/quill/editor.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/quill/katex.js',
'resources/assets/vendor/libs/quill/quill.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
'resources/assets/vendor/libs/jquery-sticky/jquery-sticky.js'
])
@endsection

<!-- Page Styles -->
@section('page-style')
<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
<style>
  .accordion-item.has-error {
      border: 1px solid red !important;
   }

   .files input {
      outline: 2px dashed #dbdade;
      outline-offset: -10px;
      -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
      transition: outline-offset .15s ease-in-out, background-color .15s linear;
      padding: 120px 0px 85px 35%;
      text-align: center !important;
      margin: 0;
      width: 100% !important;
   }

   .files input:focus {
      outline: 2px dashed #dbdade;
      outline-offset: -10px;
      -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
      transition: outline-offset .15s ease-in-out, background-color .15s linear;
   }

   .files {
      position: relative
   }

   .files:after {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-upload' width='24' height='24' viewBox='0 0 24 24' stroke-width='2' stroke='%235d596c' fill='none' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath stroke='none' d='M0 0h24v24H0z' fill='none'/%3E%3Cpath d='M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2' /%3E%3Cpolyline points='7 9 12 4 17 9' /%3E%3Cline x1='12' y1='4' x2='12' y2='16' /%3E%3C/svg%3E") !important;
      background: #4b465c14;
      content: "";
      border-radius: 8px;
      position: absolute;
      top: 3rem;
      left: calc(50% - 23px);
      display: inline-block;
      height: 48px;
      width: 48px;
      background-repeat: no-repeat !important;
      background-position: center !important;
   }

   .color input {
      background: #fff;
   }

   .files:before {
      position: absolute;
      bottom: 10px;
      left: 0;
      pointer-events: none;
      width: 100%;
      right: 0;
      height: 57px;
      content: "Drop files here or click to upload";
      display: block;
      margin: 0 auto;
      font-weight: 600;
      text-transform: capitalize;
      text-align: center;
   }

   .unRequiredFields{
       display: none;
   }

    #contract_response .key {
        font-weight: bold;
        color: #2a4d8f;
    }

    #contract_response .null {
        color: #888;
        font-style: italic;
    }

    #contract_response .toggle {
        cursor: pointer;
        color: #444;
    }

    #contract_response .hidden {
        display: none;
    }

    #contract_response .value {
        cursor: pointer;
        color: #000;
    }

    #contract_response .value.copied {
        background-color: #e0f7fa;
        color: #00796b;
        transition: background-color 0.3s ease;
    }

    #contract_response .copied {
        background-color: #e0f7fa;
        color: #00796b;
    }

    #contract_response .copy-msg {
        font-size: 0.85em;
        color: green;
        margin-left: 8px;
        opacity: 0.85;
    }

    /* Floating toggle card */
    .float-toggle {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.12);
      padding: 10px 12px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    /* ===== Accordion Styling ===== */

    #analysisAccordion .accordion-item {
        border-color: #ddd !important;
    }

    /* Overall Risk UI */
    .risk-container {
        background: #fff;
        border-left: 5px solid #e74c3c;
        padding: 20px;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .risk-header {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 10px;
        color: #e74c3c;
    }
    .risk-summary {
        font-size: 15px;
        margin-bottom: 15px;
        line-height: 1.5;
    }
    .risk-list {
        padding-left: 20px;
    }
    .risk-list li {
        margin-bottom: 6px;
    }

    /* Custom style for bottom border only */
    .input-underline {
      border: none;
      border-bottom: 2px solid #ccc;
      border-radius: 0;
      outline: none;
      box-shadow: none;
      background-color: transparent;
    }
    .input-underline:focus {
      border-bottom-color: #007bff;
      box-shadow: none;
    }

    .ai-disclaimer {
      font-size: 0.8em;
      color: #555;
      font-style: italic;
    }

    /* Dropdowns are upgraded to select2 on first interaction. Until then the
       native <select> should still look like the rest of the form. */
    select.select2:not(.select2-hidden-accessible):not(.form-select):not(.form-control) {
      display: block;
      width: 100%;
      padding: 0.422rem 0.875rem;
      font-size: 0.9375rem;
      line-height: 1.375;
      color: var(--bs-body-color, #6f6b7d);
      background-color: var(--bs-body-bg, #fff);
      border: 1px solid var(--bs-border-color, #dbdade);
      border-radius: 0.375rem;
    }
</style>
@endsection

<!-- Page Scripts -->
@section('page-script')
<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contract.js"></script>
<script type="module" src="{{url('/')}}/Modules/ContractParties/resources/assets/js/scriptparty.js"></script>
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contractArti.js"></script>
{{-- Must load last: it rebinds handlers registered by the scripts above. --}}
<script type="module" src="{{url('/')}}/Modules/Contract/resources/assets/js/contract-create-v2.js"></script>
@endsection

@section('content')

@php
    // Grouped once here instead of walking $customFields inside each of the four
    // createCustomFieldV2 includes.
    $customFieldsByCategory = collect($customFields)->groupBy('category');
@endphp

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
   <div class="d-flex flex-column justify-content-center">
      <h4 class="mb-1 mt-3">Add New Contract <span class="badge bg-label-info align-middle fs-tiny">Beta</span></h4>
   </div>
   <div class="d-flex align-content-center flex-wrap gap-3">
      <div class="align-right">
         <a href="{{url('/contracts/list')}}" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
          <!-- Floating toggle -->
          <div class="float-toggle mt-2">
            <div class="form-check form-switch show-error-switch form-check-inline">
                <input class="form-check-input" type="checkbox" role="switch" id="showAllFields" checked>
                <label class="form-check-label fw-bold" for="showAllFields">Show All Fields</label>
            </div>
               </div>
               <div class="mt-2">
                  <label class="form-label d-block mb-1">Legal Information Sharing</label>
                  <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#contactLegalCreateModal">Contact Group Legal Advisor</button>
                  <small class="d-block text-muted mt-1">Optional. If provided, legal request is sent immediately after contract creation.</small>
               </div>
      </div>
   </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger sticky-element">
        <h5 class="alert-heading mb-2">Errors Details</h5>
        <ul class="list-unstyled mb-0">
            @foreach ($errors->keys() as $field)
                <li class="text-dark {{$field}}"><i class="ti ti-exclamation-circle text-danger"></i> {!! ucwords($errors->first($field)) !!}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row mt-2 mb-4">
   <div class="col">

      <form class="row createcontractnew" id="createcontract" action="{{ url('contracts/store/contract') }}" method="POST" enctype="multipart/form-data">
         @csrf
         <div class="modal fade" id="contractModal" tabindex="-1" aria-labelledby="contractModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="contractModalLabel">Choose Basic Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>

                          <div class="modal-body">
                            <!-- Alert placeholder -->
                            <div id="contractModalAlert" class="alert alert-danger d-none" role="alert">
                              Please fill all mandatory fields (marked with *) before closing the dialog.
                            </div>

                            <!-- NOTE: No <form> here. The page has a parent form; this modal only contains the modal fields. -->
                            <div class="card accordion-item active">
                              <div class="card-header">
                                <label class="form-check-label w-100">Contract
                                </label>
                                <div class="col mt-2">
                                  <div class="form-check form-check-inline">
                                    <label class="form-check-label">
                                      <input type="radio" class="contractmode form-check-input" name="contractMode" value="new"
                                             {{ old('contractMode', $defVals['contractMode']) == 'new' ? 'checked' : '' }}>
                                      New</label>
                                  </div>
                                  <div class="form-check form-check-inline">
                                    <label class="form-check-label">
                                      <input type="radio" class="contractmode form-check-input" name="contractMode" value="old"
                                             {{ old('contractMode', $defVals['contractMode']) == 'old' ? 'checked' : '' }}>
                                      Legacy/Executed Contracts </label>
                                  </div>
                                </div>
                              </div>

                              <h2 class="accordion-header d-flex align-items-center">
                                <button type="button" class="accordion-button px-4" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-1"
                                        aria-expanded="false">
                                  Basic Contract Information
                                </button>
                              </h2>

                              <div id="accordionWithIcon-1" class="accordion-collapse collapse show px-4">
                                <div class="accordion-body">
                                  <hr class="mt-1" />
                                  <div class="row g-3">

                                    <div class="col-md-6">
                                      <label class="form-label" for="contracttype">Contract Type <span class="text-danger">*</span></label>
                                      <select class="form-select select2 contracttype" name="BasicContract[contractType]" id="contracttype" required>
                                        <option value="">--Select Contract Type--</option>
                                        @foreach ($contractTypes as $contractType)
                                          <option data-catid="{{ $contractType->categoryId }}"
                                                  {{ old('BasicContract.contractType') == $contractType->contract_type_id ? 'selected' : '' }}
                                                  data-detid="{{ $contractType->departmentId }}"
                                                  value="{{ $contractType->contract_type_id }}">
                                            {{ $contractType->contract_type }}
                                          </option>
                                        @endforeach
                                      </select>
                                    </div>

                                    <div class="col-md-6">
                                      <label class="form-label" for="DepartmentType">Department <span class="text-danger">*</span></label>
                                      <select id="DepartmentType" name="BasicContract[DepartmentType]" class="form-select select2"
                                              data-allow-clear="true" required>
                                        <option value="">Select Department</option>
                                        @foreach($ent as $en)
                                          <option value="{{$en->id}}" {{ old('BasicContract.DepartmentType') == $en->id ? 'selected' : '' }}>{{$en->name}}</option>
                                        @endforeach
                                      </select>
                                    </div>

                                    <div class="col-md-6">
                                      <label class="form-label" for="catgoeryType">Category <span class="text-danger">*</span></label>
                                      <select id="catgoeryType" name="BasicContract[catgoeryType]" class="form-select select2"
                                              data-allow-clear="true" required>
                                        @foreach($catego as $en)
                                          <option value="{{$en->id}}" {{ old('BasicContract.catgoeryType') == $en->id ? 'selected' : '' }}>{{$en->name}}</option>
                                        @endforeach
                                      </select>
                                    </div>

                                    <div class="col-md-6">
                                      <label class="form-label" for="Exclusivity">Exclusivity</label>
                                      <select name="BasicContract[Exclusivity]" class="form-select select2" data-allow-clear="true">
                                        <option {{ old('BasicContract.Exclusivity') == 'Exclusivity to Company' ? 'selected' : '' }} value="Exclusivity to Company">Exclusive to Company</option>
                                        <option {{ old('BasicContract.Exclusivity') == 'Exclusive to Contracting Party' ? 'selected' : '' }} value="Exclusive to Contracting Party">Exclusive to Contracting Party</option>
                                        <option {{ old('BasicContract.Exclusivity') == 'Mutually Exclusive' ? 'selected' : '' }} value="Mutually Exclusive">Mutually Exclusive</option>
                                        <option {{ old('BasicContract.Exclusivity', $defVals['Exclusivity']) == 'Non Exclusive' ? 'selected' : '' }} value="Non Exclusive">Non Exclusive</option>
                                      </select>
                                    </div>

                                    <div class="col-md-6">
                                      <label class="form-label" for="contractDescription">Contract Description</label>
                                      <textarea class="form-control" id="contractDescription" name="BasicContract[contractDescription]" rows="5">{{old('BasicContract.contractDescription')}}</textarea>
                                    </div>

                                    <div class="col-md-6">
                                      <label class="form-label" for="contracttypetags">Other Scopes</label>
                                      <select class="form-select select2 contracttypetags" name="BasicContract[contractTypeTags][]" id="contracttypetags" multiple>
                                        <option value="">--Select Tags--</option>
                                        @foreach ($contractTypes as $contractType)
                                          <option data-catid="{{ $contractType->categoryId }}" {{ in_array($contractType->contract_type_id, old('BasicContract.contractTypeTags', [])) ? 'selected' : '' }} value="{{ $contractType->contract_type_id }}">
                                            {{ $contractType->contract_type }}
                                          </option>
                                        @endforeach
                                      </select>
                                    </div>

                                    @if(env('enable_contract_priority'))
                                      <div class="col-md-6 unRequiredFields">
                                        <label class="form-label" for="priority">Priority:</label>
                                        <select class="select2 form-select" id ="priority" name = "priority">
                                          <option selected>Choose Priority</option>
                                          <option value="low" {{ (old('priority', 'medium') == 'low' ? 'selected' : '' ) }}>Low</option>
                                          <option value="medium" {{ (old('priority', 'medium') == 'medium' ? 'selected' : '' ) }}>Medium</option>
                                          <option value="high" {{ (old('priority', 'medium') == 'high' ? 'selected' : '' ) }}>High</option>
                                        </select>
                                      </div>
                                    @else
                                      <input type="hidden" value="{{ old('priority', 'medium') }}"/>
                                    @endif

                                    <div class="row mb-3">
                                      @include('contract::contract.createCustomFieldV2', ['categoryId' => 1])
                                    </div>

                                  </div>
                                </div>
                              </div>
                            </div>
                            <!-- End modal content -->
                          </div>

                          <div class="modal-footer">
                            <button type="button" id="contractModalCancel" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="contractModalSave" class="btn btn-primary">Save &amp; Continue</button>
                          </div>
                        </div>
                    </div>
               </div>
         <div class="col-md mb-4 mb-md-2">
            <div class="accordion mt-3" id="accordionWithIcon">
               <div id="contractAccordionContainer"></div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-6" aria-expanded="false">
                        Contract Attachments
                     </button>
                  </h2>
                  <div id="accordionWithIcon-6" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <hr class="mt-0" />
                        <div class="row">
                            <div class="col-12 attachmentsdiv" id="attachments_type_upload" style="display: {{ old('attachments_type', 'Upload') == 'template' ? 'none' : ''}}">
                               <div class="col-12">
                                  <div class="card mb-4">
                                     <div class="card-body">
                                        <div class="form-group files color">
                                           <input type="file" name="file" id="ai-docs" class="form-control">
                                           <input type="hidden" name="aiTokenTemp" id="aiTokenTemp" value="{{ old('aiTokenTemp', $encTempContId) }}" />
                                        </div>
                                        <p class="ai-disclaimer">
                                            <strong>Disclaimer:</strong> Allowed only less than 30 page documents.
                                        </p>
                                     </div>
                                  </div>
                               </div>
                            </div>
                            <div class="col-12 attachmentsdiv" id="attachments_type_template" style="display: {{ old('attachments_type', 'Upload') == 'Upload' ? 'none' : ''}}">
                                <div class="mt-2">
                                    <div id="template-editor">
                                        -
                                    </div>
                                    <textarea id="template_text" name="template_text" hidden>{{ old('template_text') }}</textarea>
                                    <input type="file" hidden id="docxFile" name="docxFile" />
                                </div>
                            </div>
                        <div class="col-12 col-lg-12 d-none contract-ai-loader">
                            <div class="card p-0 mb-2">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                          <h5 class="card-title mb-0">AI Findings <i class="ms-2 ti ti-md ti-writing"></i></h5>
                          <div class="meta d-none">
                            <span class="badge rounded-pill bg-label-primary me-1">Contract Type</span>
                            <span class="badge rounded-pill bg-label-success">Tags</span>
                            <span class="badge rounded-pill bg-label-warning">Party</span>
                            <span class="badge rounded-pill bg-label-danger">Terms</span>
                          </div>
                        </div>
                        <div class="card-body">
                          <div class="mb-2" id="contract_response_head_reading">
                              <h6>Reading Contract data's <span class="text-primary ti ti-flashing ti-book"></span></h6>
                          </div>
                          <div class="mb-2" id="contract_response_head">
                              <h6>
                                <button type="button" class="btn btn-primary btn-show-ai-chat">Ask Our AI</button>
                                <span class="btn-show-ai-response cursor-pointer text-primary ti ti-json float-end" title="Show Response"></span>
                              </h6>
                          </div>
                          <div class="offcanvas offcanvas-end" id="aiResponseOffcanvas" aria-hidden="true">
                              <div class="offcanvas-header border-bottom">
                                <h5 class="offcanvas-title"><span id="">AI Response Summary</span></h5>
                                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                              </div>
                              <div class="offcanvas-body flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center flex-wrap" id="contract_response">
                                </div>
                              </div>
                          </div>
                          <div class="offcanvas offcanvas-end" id="aiResponseChatOffcanvas" aria-hidden="true" data-bs-scroll="true">
                              <div class="offcanvas-header border-bottom">
                                <h5 class="offcanvas-title"><span id="">ONTRACK AI Chat</span></h5>
                                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                              </div>
                              <div class="offcanvas-body flex-grow-1">
                                  <input type="hidden" id="contractDetails" value=""/>
                                  <textarea class="form-control" rows="8" id="promptAiContract" placeholder="Ask Anything About this contract"></textarea>
                                  <p class="ai-disclaimer">
                                    <strong>Disclaimer:</strong> The response provided is generated by an AI system and may contain inaccuracies or incomplete information. Users are responsible for verifying the content before making decisions or taking action.
                                  </p>
                                  <div id="controls" class="mt-2">
                                    <button id="send" type="button" class="btn btn-primary">Send</button>
                                    <button id="clear" type="reset" class="btn btn-secondary">Clear</button>
                                  </div>

                                  <h5 class="mt-2">Response Summary</h5>
                                  <div id="response">No response yet.</div>
                                  <div id="meta" class="muted"></div>
                              </div>
                          </div>
                          <p class="ai-disclaimer">
                            <strong>Disclaimer:</strong> The response provided is generated by an AI system and may contain inaccuracies or incomplete information. Users are responsible for verifying the content before making decisions or taking action.
                          </p>
                        </div>
                      </div>
                          <div class="accordion" id="analysisAccordion">
                              <!-- Risk Analysis -->
                              <div class="accordion-item">
                                <h2 class="accordion-header" id="headingRisk">
                                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRisk" aria-expanded="false" aria-controls="collapseRisk">
                                    Risk Evaluation
                                  </button>
                                </h2>
                                <div id="collapseRisk" class="accordion-collapse collapse" aria-labelledby="headingRisk" data-bs-parent="#analysisAccordion">
                                  <div class="accordion-body" id="riskBody">
                                  </div>
                                </div>
                              </div>

                              <!-- Clause Analysis -->
                              <div class="accordion-item">
                                <h2 class="accordion-header" id="headingClause">
                                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseClause" aria-expanded="false" aria-controls="collapseClause">
                                    Clause Evaluation
                                  </button>
                                </h2>
                                <div id="collapseClause" class="accordion-collapse collapse" aria-labelledby="headingClause" data-bs-parent="#analysisAccordion">
                                  <div class="accordion-body table-responsive" id="clauseBody">
                                  </div>
                                </div>
                              </div>
                              <!-- ask Ai -->
                              <div class="accordion-item">
                                <h2 class="accordion-header" id="headingAskAi">
                                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAskAi" aria-expanded="false" aria-controls="collapseAskAi">
                                    Contract Assistance – Ask Your Question
                                  </button>
                                </h2>
                                <div id="collapseAskAi" class="accordion-collapse collapse" aria-labelledby="headingAskAi" data-bs-parent="#analysisAccordion">
                                  <div class="accordion-body table-responsive" id="askAiBody">
                                        <div class="input-group">
                                          <input type="text" id="ai-user-input" class="form-control input-underline" placeholder="Ask about clauses, terms, or agreement details…">
                                          <span class="input-group-text bg-transparent border-0">
                                            <i class="ti ti-search text-secondary"></i>
                                          </span>
                                        </div>
                                  </div>
                                </div>
                              </div>
                           </div>
                        </div>
                        </div>
                     </div>
                  </div>
               </div>

               <div class="accordion-item card mt-4 d-none">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-ai" aria-expanded="false">
                        AI Suggessionts <i class="ms-2 ti ti-md ti-writing"></i>
                     </button>
                  </h2>
                  <div id="accordionWithIcon-ai" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <hr class="mt-1" />

                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-2" aria-expanded="false">
                        Party Details <i class="ti ti-help-circle text-warning" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="The system allows only one Internal party.
If you need to add another party from the same organization or branch group, please select Inter-Group."></i>
                     </button>
                  </h2>
                  <div id="accordionWithIcon-2" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <hr class="mt-1" />
                        <div id="messageContainer">
                        </div>
                        <div class="list-group mb-4" id="non-existing-list">
                        </div>
                        <div class="row g-3">
                           @include('contract::contract.partyDetailsCreateV2', ['contractPartys'=>old('Partygroup.party', [[],['mode'=>'External']])])
                           <div class="panel-body">
                              <div class="party-group">
                              </div>
                           </div>

                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-3" aria-expanded="false">
                        Contract Duration
                     </button>
                  </h2>
                  <div id="accordionWithIcon-3" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <hr class="mt-1" />
                        <div class="row g-3">

                           <div class="col">
                              @include('contract::contract.contractDuration')
                           </div>
                           <div class="unRequiredFields">
                                @include('contract::contract.createCustomFieldV2', ['categoryId' => 2])
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-4" aria-expanded="false">
                        Contract Value
                     </button>
                  </h2>
                  <div id="accordionWithIcon-4" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <hr class="mt-0" />
                        <div class="row g-3">

                           <div class="card-body">

                              <div class="row mb-3">
                                 <div class="col-md-2">
                                    <label class="form-label" for="ContractValue">Contract Value <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="The total monetary value of the contract."></i></label>
                                    <select id="ContractValue" name="ContractValue[currency]" class="form-select select2" data-allow-clear="true">
                                       @foreach (currency() as $currency)
                                       <option value="{{ $currency }}" {{ old('ContractValue.currency') == $currency ? 'selected' : '' }}>{{ $currency }}</option>
                                       @endforeach
                                    </select>
                                 </div>
                                 <div class="col-md-4">
                                    <label class="form-label" for="formValidationSelect2">Billing Frequency <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Frequency at which invoices are issued (e.g., monthly, quarterly, annually)."></i></label>
                                    <select id="BillingFrequency" name="ContractValue[billingFrequency]" class="form-select select2 calculateBilling" data-allow-clear="true">
                                       <option {{ old('ContractValue.billingFrequency') == 'Weekly' ? 'selected' : '' }} value="Weekly">Weekly</option>
                                       <option {{ old('ContractValue.billingFrequency') == 'Monthly' ? 'selected' : '' }} value="Monthly">Monthly</option>
                                       <option {{ old('ContractValue.billingFrequency') == 'Quarterly' ? 'selected' : '' }} value="Quarterly">Quarterly</option>
                                       <option {{ old('ContractValue.billingFrequency') == 'Annually' ? 'selected' : '' }} value="Annually">Annually</option>
                                       <option {{ old('ContractValue.billingFrequency') == 'Onetime' ? 'selected' : '' }} value="Onetime">One Time</option>
                                    </select>
                                 </div>
                                 <div class="col-md-4">
                                     <label class="form-label" for="ContractBillingValue">Billing Value <span class="required-field-old text-danger" style="display:{{ old('contractMode', $defVals['contractMode']) == 'old' ? 'inline-block' : 'none'}}">*</span></label>
                                    <input type="number" class="form-control calculateBilling" placeholder="" name="ContractValue[billingvalue]" id="ContractBillingValue" value="{{ old('ContractValue.billingvalue') }}">
                                 </div>
                              </div>
                              <div class="row mb-3">
                                 <div class="col-md-6 annualValueDiv d-none"><label class="form-label" for="ContractValueAnnual">Annual Contract Value</label>
                                    <label class="btn btn-label-warning btn-sm mt-xl-6 waves-effect"><span class="align-middle" id="ContractValAnnText"></span></label>
                                    <input type="hidden" readonly class="form-control" placeholder="" name="ContractValue[value]" id="ContractValueAnnual" value="{{ old('ContractValue.value') }}">
                                 </div>
                                 <div class="col-md-6 totalValueDiv d-none"><label class="form-label" for="totalContractValue">Total Contract Value</label>
                                    <label class="btn btn-label-warning btn-sm mt-xl-6 waves-effect"><span class="align-middle" id="totContValText"></span></label>
                                    <input type="hidden" readonly class="form-control" placeholder="" name="ContractValue[totalvalue]" id="totalContractValue" value="{{ old('ContractValue.totalvalue') }}">
                                 </div>
                              </div>
                              <hr class="mt-3 unRequiredFields" />
                              <div class="row mb-3 unRequiredFields">
                                 <div class="col-md-6">
                                    <label class="form-label" for="PaymentSchedule">Payment Schedule <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Details of payment milestones, amounts, and due dates."></i></label>
                                    <textarea class="form-control" id="PaymentSchedule" name="ContractValue[paymentSchedule]" rows="3">{{ old('ContractValue.paymentSchedule') }}</textarea>

                                 </div>
                                 <div class="col-md-6">
                                    <label class="form-label" for="paymentTerms">Payment Terms <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Terms and conditions governing payments, including payment methods any late payment
                                                    penalties."></i></label>
                                    <textarea class="form-control" id="paymentTerms" name="ContractValue[paymentTerms]" rows="3">{{ old('ContractValue.paymentTerms') }}</textarea>
                                 </div>
                              </div>
                              <hr class="mt-3 unRequiredFields" />
                              <div class="row mb-3 unRequiredFields">
                                 <div class="col-md-4">
                                    <label class="form-label" for="Currencycontract">Taxes and Fees <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Any applicable taxes, fees, or surcharges associated with the contract."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="Taxes" name="ContractValue[taxes]" value="{{ old('ContractValue.taxes') }}">
                                 </div>
                              </div>
                              <hr class="mt-3 unRequiredFields" />
                              <div class="row mb-3 unRequiredFields">
                                 <div class="col-md-6">
                                    <label class="form-label" for="Currencycontract">Escalation Clauses <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Provisions for adjusting contract prices over time based on predetermined factors such as
                                                    inflation or market fluctuations."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="EscalationClauses" name="ContractValue[escalationClauses]" value="{{ old('ContractValue.escalationClauses') }}">
                                 </div>
                                 <div class="col-md-4">
                                    <label class="form-label" for="Currencycontract">Discounts or Rebates <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Any discounts or rebates applied to the contract."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="Discounts" name="ContractValue[discounts]" value="{{ old('ContractValue.discounts') }}">
                                 </div>
                              </div>
                              <hr class="mt-3 unRequiredFields" />
                              <div class="row mb-3 unRequiredFields">
                                 <div class="col-md-6">
                                    <label class="form-label" for="Currencycontract">Retention or Holdbacks <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Amounts withheld from payments as retention or holdbacks pending completion of certain
                                                    milestones or obligations."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="Retention" name="ContractValue[retention]" value="{{ old('ContractValue.retention') }}">
                                 </div>
                                 <div class="col-md-4">
                                    <label class="form-label" for="Currencycontract">Payment Escrow <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Details of any funds held in escrow for payment security or dispute resolution purposes."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="Payment" name="ContractValue[payment_escrow]" value="{{ old('ContractValue.payment_escrow') }}">
                                 </div>
                              </div>
                              <hr class="mt-3 unRequiredFields" />
                              <div class="row mb-3 unRequiredFields">
                                 <div class="col-md-6">
                                    <label class="form-label" for="Currencycontract">Financial Guarantees or Bonds <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Information about any financial guarantees or bonds required under the contract."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="Financial Guarantees" name="ContractValue[financialGuarantees]" value="{{ old('ContractValue.financialGuarantees') }}">
                                 </div>
                                 <div class="col-md-4 d-none">
                                    <label class="form-label" for="Currencycontract">Currency Conversion <i class="ti ti-help-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Terms for currency conversion if the contract involves transactions in multiple currencies."></i></label>
                                    <input type="text" class="form-control" placeholder="" id="CurrencyConversion" name="ContractValue[currencyConversion]" value="{{ old('ContractValue.currencyConversion') }}">
                                 </div>
                              </div>
                           </div>
                           <div class="row mb-3">
                              @include('contract::contract.createCustomFieldV2', ['categoryId' => 3])
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="accordion-item card mt-4 unRequiredFields">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-5" aria-expanded="false">
                        Contract Custom Fileds / Miscelleneous
                     </button>
                  </h2>
                  <div id="accordionWithIcon-5" class="accordion-collapse collapse">
                     <div class="accordion-body">
                        <div class="row g-3">

                           <div class="card-body">

                              <div class="panel panel-default">

                                 <div class="panel-collapse">
                                    <div class="panel-body">
                                       <div class="col-sm-12">
                                          @include('contract::contract.createCustomFieldV2', ['categoryId' => 4])
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>

                        </div>
                     </div>
                  </div>
               </div>

               <div class="accordion-item card mt-4">
                  <h2 class="accordion-header d-flex align-items-center">
                     <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionOwnership" aria-expanded="false">
                        Ownership
                     </button>
                  </h2>
                  <div id="accordionOwnership" class="accordion-collapse collapse show">
                     <div class="accordion-body">
                        <hr class="mt-1" />
                        <div class="row g-3">
                           <div class="col-md-6">
                              <label class="form-label" for="owner">Owner/Inititor <span class="text-danger">*</span></label>
                              <select class="form-select" name="owner" id="ownership">
                                 <option value="">-Co-ordinator-</option>
                                 @foreach ($usersSel as $user)
                                 <option value="{{ $user->id }}" {{ old('owner', $owner_initiator_id) == $user->id ? 'selected' : '' }}>
                                   {{ $user->Salutation }}
                                    {{ $user->FirstName }}
                                    {{ $user->LastName }}
                                    ({{ $user->Email }})
                                 </option>
                                 @endforeach
                              </select>
                           </div>
                           <div class="col-md-6">
                              <label class="form-label" for="ownership-signatory">Signatory <span class="text-danger">*</span></label>
                              <select class="form-select" name="signatory" id="ownership-signatory" disabled>
                                 <option value="">-Select Signatory-</option>
                                 @foreach ($users as $user)
                                 <option value="{{ $user->id }}" {{ old('signatory') == $user->id ? 'selected' : '' }}>
                                   {{ $user->Salutation }}
                                    {{ $user->FirstName }}
                                    {{ $user->LastName }}
                                    ({{ $user->Email }})
                                 </option>
                                 @endforeach
                              </select>
                           </div>
                           <div class="col-md-6">
                              <label class="form-label" for="users-notify">Users To Notify</label>
                              <select class="form-select" name="userNotify[]" id="users-notify" multiple>
                                 @foreach ($usersSel as $user)
                                 <option value="{{ $user->id }}" {{ in_array($user->id, old('userNotify', [])) == $user->id ? 'selected' : '' }}>
                                   {{ $user->Salutation }}
                                    {{ $user->FirstName }}
                                    {{ $user->LastName }}
                                    ({{ $user->Email }})
                                 </option>
                                 @endforeach
                              </select>
                           </div>
                          <div class="col-md-6">
                              <div class="form-group signing_date" style="display:{{ old('contractMode', $defVals['contractMode']) == 'old' ? 'block' : 'none'}}">
                                 <label class="form-label">Signing Date <span class="required-field-old text-danger" style="display:{{ old('contractMode', $defVals['contractMode']) == 'old' ? 'inline-block' : 'none'}}">*</span></label>
                              <input type="date" name="Duration[signingDate]" class="form-control flatpickr" placeholder="Signing Date" value="{{ old('Duration.signingDate') }}"/>

                                 <div class="clearfix">
                                    <small class="form-text text-muted">The date on which the contract is signed by all parties involved. This may or may not be the same as the effective date, depending on the terms of the contract.
                                    </small>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="buy-now">
            <button type="button" id="createContractButton" class="btn-buy-now btn btn-primary me-sm-3 me-1 waves-effect waves-light">Submit</button>
         </div>

         <div class="modal fade" id="contactLegalCreateModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
               <div class="modal-content">
                  <div class="modal-header">
                     <h5 class="modal-title">Contact Group Legal Advisor</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                     @php
                        $defaultLegalAdvisorId = old('legal_advisor_id');
                        if (empty($defaultLegalAdvisorId)) {
                           $defaultLegalAdvisorId = optional(($legalAdvisors ?? collect())->first())->id;
                        }
                     @endphp
                     <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="contactLegalNow" name="contact_legal_now" value="1" {{ old('contact_legal_now') ? 'checked' : '' }}>
                        <label class="form-check-label" for="contactLegalNow">Send request to legal after contract is created</label>
                     </div>
                     <div class="d-none">
                        <label class="form-label" for="create_legal_advisor_id">Legal Advisor</label>
                        <select class="form-select" name="legal_advisor_id" id="create_legal_advisor_id">
                           <option value="">-Select Legal Advisor-</option>
                           @foreach (($legalAdvisors ?? collect()) as $advisor)
                           <option value="{{ $advisor->id }}" {{ (string) $defaultLegalAdvisorId === (string) $advisor->id ? 'selected' : '' }}>
                              {{ $advisor->name }}{{ $advisor->designation ? ' - ' . $advisor->designation : '' }} ({{ $advisor->email_id }})
                           </option>
                           @endforeach
                        </select>
                     </div>
                     <div class="mb-0">
                        <label class="form-label" for="create_legal_comment">Comment</label>
                        <textarea class="form-control" name="legal_contact_comment" id="create_legal_comment" rows="5" placeholder="Share context for legal advisor.">{{ old('legal_contact_comment') }}</textarea>
                     </div>
                  </div>
                  <div class="modal-footer">
                     <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                     <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Save</button>
                  </div>
               </div>
            </div>
         </div>
      </form>

   </div>
</div>

<!-- Form with Image horizontal Modal -->
<div class="modal-onboarding modal fade animate__animated" id="onboardHorizontalImageModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content text-center">
         <div class="modal-header border-0">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            </button>
         </div>
         <div class="modal-body onboarding-horizontal p-0">
             <div class="popap">

             </div>
         </div>

      </div>
   </div>
</div>

<script>
    function form_modal_submit(idForm){
        $(`#${idForm}`).submit();
    }
</script>

@endsection
