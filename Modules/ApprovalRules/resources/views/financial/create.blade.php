@extends('layouts/layoutMaster')

@section('title', 'Approver Rules Form')

<!-- Vendor Styles -->
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/quill/typography.scss',
'resources/assets/vendor/libs/quill/katex.scss',
'resources/assets/vendor/libs/quill/editor.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/dropzone/dropzone.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/tagify/tagify.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])

<!-- Page Scripts -->
@section('page-script')

<link href="{{url('/')}}/assets/css/custom.css" rel="stylesheet" />
<script type="module" src="{{url('/')}}/assets/js/jquery.validate.min.js"></script>
<style>
    .approvers-tab .nav-link:not([data-bs-toggle="tab"]){
        background-color: #827a7a2e;
    }
    .approvers-tab .nav-link:not([data-bs-toggle="tab"]):hover,
    .approvers-tab .nav-link:not([data-bs-toggle="tab"]):focus,
    .approvers-tab .nav-link:not([data-bs-toggle="tab"]):active{
        color: #FFFFFF;
    }
label.required:after {
    content: "*";
    color: red;
    font-size: 15px;
    font-weight: 900;
}    
</style>

  <style>
  .new-rules-builder{
          /* Group Card */
          .group-box {
              background: #fff;
              border-radius: 12px;
              padding: 20px;
              margin-top: 25px;
              box-shadow: 0 6px 18px rgba(0,0,0,0.08);
              position: relative;
              border-left: 6px solid #26a69a;
              display: grid;
              grid-template-rows: auto;
              gap: 15px;
          }

          /* .removeGroupBtn {
              position: absolute;
              top: 15px;
              right: 15px;
              background: #e53935;
              border: none;
              color: white;
              padding: 6px 12px;
              border-radius: 6px;
              cursor: pointer;
          } */

          /* Form Fields Grid */
          .form-grid {
              display: grid;
              grid-template-columns: repeat(3, 1fr);
              gap: 20px;
          }

          label {
              font-weight: 600;
              display: block;
              margin-bottom: 5px;
          }

          input, select {
              width: 100%;
              padding: 10px;
              border-radius: 8px;
              border: 1px solid #cfd8dc;
              font-size: 14px;
              box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
              transition: all 0.2s;
          }

          input:focus, select:focus {
              border-color: #26a69a;
              box-shadow: 0 0 5px rgba(38,166,154,0.4);
              outline: none;
          }

          /* Discount Rows Grid */
          .discount-grid {
              display: grid;
              grid-template-columns: repeat(3, 1fr); /* 3 fields + remove button */
              gap: 15px;
              align-items: center;
              margin-bottom: 16px;
          }

          /* Buttons */
          .btn-main, .addRowBtn,.removeGroupBtn {
              padding: 10px 18px;
              background: #28a745;
              color: #fff;
              border: none;
              border-radius: 8px;
              cursor: pointer;
              font-size: 14px;
              margin-top: 15px;
              transition: background 0.2s, transform 0.2s;
          }

          .btn-main:hover, .addRowBtn:hover,.removeGroupBtn:hover {
              background: #00796b;
              transform: scale(1.05);

          }
          div#groupsContainer {
              border: 1px solid grey;
              border-radius: 10px;
              padding: 40px;
              margin-top: 30px;
              position: relative;
          }
        .ifcondition {
              /* width: 25px;  */
              height: 30px;
              background: #f0f4f7;
              position: absolute;
              top: -16px;
              font-weight: 500;
              text-align: center;
              font-size: 20px;
              font-family: auto;
              display: flex;
              gap: 10px;
              align-items: center;
          }
          /* .removeRuleBtn {
              background: #e53935;
              color: #fff;
              border: none;
              padding: 8px 12px;
              border-radius: 6px;
              cursor: pointer;
          } */

          .removeRuleBtn {
              background: transparent;
              color: #fff;
              border: none;
              padding: 0;
              border-radius: 0;
              cursor: pointer;
              width: 1rem;
          }

          #submitForm {
              display: block;
              margin: 30px auto;
              width: 300px;
              padding: 12px;
              background: #00796b;
              color: white;
              border: none;
              border-radius: 8px;
              cursor: pointer;
              font-size: 16px;
          }
        .rowbtn {
              margin-top: 15px;
              text-align: left; 
          }
        .removebtn {
              margin-top: 15px;
              text-align: right; 
          }

          .addRowBtn, .removeGroupBtn {
              display: inline-block; 
              padding: 8px 16px;             
              font-size: 14px;
              cursor: pointer;
              
          }

          .addRowBtn:hover, .removeGroupBtn:hover {
              background-color: #00796b;
              transform: scale(1.05);  /* Slight hover effect */
          }
          /* Responsive */
          @media (max-width: 800px) {
              .form-grid, .discount-grid {
                  grid-template-columns: 1fr;
              }
          }

          .btn-add svg,
          .btn-remove svg {
              display: block;
          }


          .btn {
      width: 32px;
      height: 32px;
      font-size: 20px;
      font-weight: bold;
      border-radius: 6px;
      border: none;
      cursor: pointer;
  }

  .plus-btn {
      background: #28a745; /* green */
      color: white;
  }

  .minus-btn {
      background: #dc3545; /* red */
      color: white;
  }
  .removerowicon .removeRuleBtn {
      background-color: #d9534f;   /* Red button like screenshot */
      color: #fff;
      border: none;
      width: 32px;
      height: 32px;
      font-size: 18px;
      font-weight: bold;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      justify-content: center;
      align-items: center;
  }

  .removerowicon .removeRuleBtn:hover {
      background-color: #c9302c; /* Darker hover red */
  }
  .discountAccessTYpe {
      display: flex;
      align-items: center;
      gap: 10px;
  }
  }
  </style>

@endsection

  @section('content')

    <div class="row my-4">
        <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
           <div class="d-flex flex-column justify-content-center">
              <h4 class="mb-1">Add New Approver Rules</h4>
           </div>
           <div class="d-flex align-content-center flex-wrap gap-3">
              <div class="d-flex gap-3">
                 <a href="{{url('/')}}/contract-setup/approval-rules" style="color: #FFF;text-decoration: none;"><button type="button" class="btn btn-label-primary">Back</button></a>
              </div>
           </div>
        </div>
      <div class="row align-items-center">
        <div class="col-md-12">
          <div class="">
            <!-- <h5 class="card-title">Entity Details</h5> -->
              @if ($message = Session::get('success'))
                  <div class="alert alert-success alert-dismissible fade show" role="alert">
                      <b>{{ $message }}</b>
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
              @endif

              @if ($errors->any())
                  <div class="alert alert-danger">
                      <ul>
                          @foreach ($errors->all() as $error)
                              <li>{{ $error }}</li>
                          @endforeach
                      </ul>
                  </div>
              @endif
          </div>
        </div>          
      </div>
      
      
        <div class="col">
          <form class="row" id="financial_form" action="{{url('/')}}/contract-setup/financial-add" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="rule_builder_data" id="rule_builder_data" value="{{ old('rule_builder_data') }}">
            @csrf
             <div class="col-md mb-4 mb-md-2">
                    <label for="approval_name" class="select2 form-label required">Name/Title</label>
                    <input type="text" id="approval_name" class="form-control" name="approval_name" value="{{ old('approval_name') }}" required />
                    <div class="accordion mt-3" id="accordionWithIcon">
                        <div class="accordion-item card mt-4 active">
                          <h2 class="accordion-header d-flex align-items-center">
                             <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-1" aria-expanded="true">
                             IF
                             </button>
                          </h2>
                          <div id="accordionWithIcon-1" class="accordion-collapse collapse show">
                             <div class="accordion-body">
                                <hr class="mt-1" />
                                <div class="new-rules-builder">
                                    <!-- New Rule Block Start -->
                                        <button type="button" id="addGroupBtn" class="btn-main">Add Group</button>

                                        <div id="groupsContainer">
                                            <div class="ifcondition"><p>IF</p><div id="groupAccessType"></div></div>
                                        </div>                 
                                    <!-- New Rule Block End -->                                     
                                </div>
                             </div>
                          </div>
                       </div>
                        <div class="accordion-item card mt-4">
                          <h2 class="accordion-header d-flex align-items-center">
                             <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionWithIcon-2" aria-expanded="false">
                             THEN
                             </button>
                          </h2>
                            <div id="accordionWithIcon-2" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                  <hr class="mt-1" />
                                  <div class="container">
                                    <div class="nav-align-left nav-tabs-shadow mb-4 mt-4 approvers-tab">
                                      <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-left-default" aria-controls="navs-left-home" aria-selected="false">New</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button type="button" class="nav-link" role="tab" {{ (old('sameAsNewApproval', null) == 'on') ? '':'data-bs-toggle=tab' }} data-bs-target="#navs-left-default-edit" aria-controls="navs-left-messages" aria-selected="true">Edit</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button type="button" class="nav-link" role="tab" {{ (old('sameAsNewApproval', null) == 'on') ? '':'data-bs-toggle=tab' }} data-bs-target="#navs-left-legacy" aria-controls="navs-left-profile" aria-selected="false">Legacy</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button type="button" class="nav-link" role="tab" {{ (old('sameAsNewApproval', null) == 'on') ? '':'data-bs-toggle=tab' }} data-bs-target="#navs-left-legacy-edit" aria-controls="navs-left-messages" aria-selected="true">Legacy Edit</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button type="button" class="nav-link" role="tab" {{ (old('sameAsNewApproval', null) == 'on') ? '':'data-bs-toggle=tab' }} data-bs-target="#navs-left-renewed" aria-controls="navs-left-messages" aria-selected="true">Renew</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button type="button" class="nav-link" role="tab" {{ (old('sameAsNewApproval', null) == 'on') ? '':'data-bs-toggle=tab' }} data-bs-target="#navs-left-addendum" aria-controls="navs-left-messages" aria-selected="true">Addendum</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                          <button type="button" class="nav-link" role="tab" {{ (old('sameAsNewApproval', null) == 'on') ? '':'data-bs-toggle=tab' }} data-bs-target="#navs-left-terminate" aria-controls="navs-left-messages" aria-selected="true">Terminate</button>
                                        </li>
                                      </ul>
                                      <div class="tab-content pt-3">
                                        <div class="tab-pane fade show active ms-4" id="navs-left-default" role="tabpanel">
                                            @include('contract-setup::financial.users-section', ['appType' => '', 'title'=>'New Contracts'])
                                        </div>
                                        <div class="tab-pane fade show ms-4" id="navs-left-default-edit" role="tabpanel">
                                            @include('contract-setup::financial.users-section', ['appType' => 'edit', 'title'=>'Edit Executed'])
                                        </div>                                
                                        <div class="tab-pane fade show ms-4" id="navs-left-legacy" role="tabpanel">
                                            @include('contract-setup::financial.users-section', ['appType' => 'legacy', 'title'=>'Legacy'])
                                        </div>
                                        <div class="tab-pane fade show ms-4" id="navs-left-legacy-edit" role="tabpanel">
                                            @include('contract-setup::financial.users-section', ['appType' => 'legacy_edit', 'title'=>'Legacy Edit'])
                                        </div>
                                        <div class="tab-pane fade show ms-4" id="navs-left-renewed" role="tabpanel">
                                            @include('contract-setup::financial.users-section', ['appType' => 'renewed', 'title'=>'Renewal'])
                                        </div>
                                        <div class="tab-pane fade show ms-4" id="navs-left-addendum" role="tabpanel">
                                            @include('contract-setup::financial.users-section', ['appType' => 'addendum', 'title'=>'Addendum'])
                                        </div>
                                        <div class="tab-pane fade show ms-4" id="navs-left-terminate" role="tabpanel">
                                            @include('contract-setup::financial.users-section', ['appType' => 'terminate', 'title'=>'Terminate'])
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
               <button type="submit" class="btn-buy-now btn btn-primary me-sm-3 me-1 waves-effect waves-light">Submit</button>
            </div>
          </form>
        </div>
    </div>
    </div>
    <!--<script  type="module" src="{{ asset('assets/js/jquery-1.10.2.js') }}"></script>-->
    <script type="module" src="{{url('/')}}/Modules/ApprovalRules/resources/assets/js/jquery-1.10.2.js"></script>
    <script type="module" src="{{url('/')}}/Modules/ApprovalRules/resources/assets/js/customfieldcontrol.js"></script>
    <script>window.CUSTOM_CONTRACTS_TYPE_ID = {!! json_encode(admin_setting('custom_contracts_type_id')) !!};</script>
    <script type="module" src="{{url('/')}}/Modules/ApprovalRules/resources/assets/js/rule-builder.js"></script>
    <script type="module" src="{{url('/')}}/Modules/ApprovalRules/resources/assets/js/script.js"></script>
    <script  type="module">
    window.CUSTOM_FIELDS = {!! json_encode($customFields, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!};
      $(document).ready(function() {
        $(document).on('click', '.repeater .btn-delete', function(e) {
      Swal.fire({
        title: 'Are you sure?',
        text: "you want to delete this element?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        customClass: {
          confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
          cancelButton: 'btn btn-label-secondary waves-effect waves-light'
        },
        buttonsStyling: false
      }).then(function(result) {
        if (result.value) {
          $(e.target).closest('.repeater').slideUp(400, function() {
            $(this).remove()
          });
        }
      });

    });
    });
    </script>
 @endsection  
   