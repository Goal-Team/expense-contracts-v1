<style>
    .emptyattachemnt {
        background: red !important;
        color: #fff !important;
    }

    ..missing-data {
        background: red !important;
        color: #fff !important;
    }

    .error-row td{
        /*color: #FFF !important;*/
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

    .loading {
        position: fixed;
        background: #00000099;
        width: 100%;
        left: 0;
        z-index: 999999;
        top: 0;
        height: 100vh;
    }

    .loading i {
        color: #fff;
        font-size: 2rem;
        text-align: center;
        position: absolute;
        left: 50%;
        top: 50%;
        animation: rotateAnimation 2s linear infinite;
        /* 2-second animation, running infinitely */
    }

    /* Define the keyframes for rotation */
    @keyframes rotateAnimation {
        from {
            transform: rotate(0deg);
            /* Start at 0 degrees */
        }

        to {
            transform: rotate(360deg);
            /* Rotate full circle */
        }
    }
    
    #signApprovalProcessTab .nav-link.completed{
        box-shadow: 0 -2px #cbf2dc inset;
        background-color: var(--bs-card-bg);
        color: #28c76f !important;
        border-bottom: 1px solid #cbf2dc !important;        
    }
    
    #signature-data-tab .nav-link.active{
        color: #28c76f !important;
        background-color: #cbf2dc !important;
        border-color: transparent !important;
    }
    
    #signatureCanvas{
        width: 100%;
        height:200px;
        touch-action: none;
    }
    
    #signatureApprovalTabContent .tab-pane{
        min-height:300px;
    }
    
    .previewSignImgDiv{
        width:100%;
        height:200px;
    }
    #previewSignImg{
      height: 100px;
      object-fit: cover;
    }
    
</style>
<div class="container network_wrapper col-sm p-2">
    <div class="loading" style="display:none;"><i class="ti ti-loader-2 mb-2"></i></div>
    @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif
    
    <div class="card">
        <h5 class="p-2 mt-2">
            Signing Pending With {{ json_decode($approvalValues->username)->name ." (". json_decode($approvalValues->username)->email .")" }} 
            @php
                // rules_id is a JSON text column and it holds a list of rules. It is NULL on a
                // contract with no approval rules, and then $approvalsDetails[0] threw and the
                // page did not render. contractFlow.blade.php guards the same read the same way.
                $approvalsDetails = is_string($contract->rules_id) ? json_decode($contract->rules_id) : null;
                $signatoryJson = is_array($approvalsDetails) ? ($approvalsDetails[0]->signatory ?? null) : null;
                $signUtFormPath = is_string($signatoryJson) ? (json_decode($signatoryJson)->signutform ?? null) : null;
            @endphp
            @if($signUtFormPath != null)
                <a href="{{fileViewUrl($signUtFormPath)}}" class="text-warning float-end fs-6" target="new" alt="View Form"><i class="ti ti-file ti-xs"></i> Undertaking Form Click to view</a>
            @endif             
        </h5>
        <div class="col px-2 mt-3">
          @if(env('enable_sign_pad') && decryptString($contract->contract_mode, 'contract_mode')  == 'new')
           <div class="form-check form-check-inline">
              <label class="form-check-label">
                 <input type="radio" class="attachmentstype form-check-input" name="attachments_type" value="Upload" data-div="signing" {{ env('enable_sign_pad') && decryptString($contract->contract_mode, 'contract_mode') == 'new' ? 'checked' : '' }}/>
                 Signing Process</label>
           </div>
           @endif
           <div class="form-check form-check-inline">
              <label class="form-check-label">
                 <input type="radio" class="attachmentstype form-check-input" name="attachments_type" value="template" data-div="upload" {{ (!env('enable_sign_pad') || decryptString($contract->contract_mode, 'contract_mode')  == 'old') ? 'checked' : '' }}/>
                 Upload Signed Document</label>
           </div>
        </div>
        <div class="p-2 attachmentsdiv" id="attachments_type_upload" style="display: {{ env('enable_sign_pad') && decryptString($contract->contract_mode, 'contract_mode') == 'new' ? 'none' : '' }};">
            <h6 class="text-danger">Please ensure this is the final version of the document. Once uploaded, it cannot be modified and the contract will be executed as is.</h6>
                            
            <form id="executeFormSignedDoc">
                @csrf
                <div class="form-check form-check-inline">
                    <h6 for="noChangesUpdate" class="d-none">
                        No Changes in Documents</h6>
                        <input name="noChangesUpdate" checked id="noChangesUpdate" class="form-check-input" type="hidden" value="1"/>
                </div>                
                <div class="form-group">
                    <input type="hidden" name="contactId" placeholder="Enter Next Action"
                        class="form-control" value="{{$contract->id}}">
                </div>
                <div class="form-group">
                    <input type="hidden" name="appId" class="form-control" value="{{$approvalValues->id}}">
                </div>
                <div class="form-group">
                    <input type="hidden" name="indexId" id="indexId" class="form-control"
                        value="{{ $lindex }}">
                </div>
                <div class="form-group">
                    <input type="hidden" name="appType" id="appType{{ $lindex }}" class="form-control" value="approved">
                </div>
                <div class="form-group">
                    <input type="hidden" name="appStatus" id="appStatus" class="form-control"
                        value="{{ $approvalValues->status }}">
                </div>
                <div class="form-group">
                    <input type="hidden" name="appPreStatus" id="appPreStatus" class="form-control"
                        value="{{ $approvalValues->previous_status }}">
                </div>
                <div class="form-group">
                    <input type="hidden" name="orderval" id="orderval" class="form-control"
                        value="{{ $approvalValues->orderval }}">
                </div>
                <div class="form-group">
                    <input type="hidden" name="unique_id" id="unique_id" class="form-control"
                        value="{{ $approvalValues->unique_id }}">
                </div>
                
                <div class="row">
                        <div class="col-12">
                            @if(isset($contract->contract_attachment_filename))
                                @if(fileStorageType() != 'Local')
                                    @php 
                                        $getFinalUrl = get_google_drive_doc_link($contract->contract_attachment_filename,$contract->contract_attachment, 'edit', 'dfdfdh');
                                        $getFinalUrlNew = get_google_drive_doc_link($contract->contract_attachment_filename,$contract->contract_attachment, 'edit', 'gfhdgfdhg');
                                        
                                        $docAlertBox = Helper::getDocumentDisplaySection($getFinalUrl);
                                    @endphp
                                    {!! $docAlertBox !!}
                                @else
                                    @include('contract::contract.viewContractDocument')
                                @endif  
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-warning my-2">Or else Upload Final Document</h6>
                            <div class="form-group files color mb-2">
                                <input type="file" name="photos[]" class="form-control signFile" />
                                <input type="hidden" id="fileTypeDocSign" value="contract" />
                            </div>
                        </div>
                        <div class="col-md-6">
                            
                        <button type="submit"
                            class="btn btn-primary btn-sm float-end">Save & Execute</button>                            
                            <div class="misgtable my-4">
                                @php
                                    $disableSubmit1 = 0;
                                @endphp
                                @foreach ($reqfieldsVal as $key => $vals)
                                    @php
                                    $buttonDisable = false;
                                    $orgVal = $reqfieldsVals[$key];
                                    $inpVal = $reqfieldsVals[$key];
                                    if($reqfieldsInpType[$key] == 'date' && $inpVal == $key){
                                        $orgVal = "";
                                        $inpVal = date('Y-m-d');
                                    }
                                    @endphp                    
                                    @if(!$vals)
                                        @php
                                            $buttonDisable = true;
                                            $disableSubmit1++;
                                        @endphp
                                    @endif
                                      <div id="{{$key.'-section-id'}}">
                                        <label for="{{$key.'-id'}}" class="form-label">{{ $reqfieldsText[$key] }}</label>
                                        <div class="input-group">
                                            @if($reqfieldsInpType[$key] != 'radio')
                                                <input type="{{$reqfieldsInpType[$key]}}" id="{{$key.'-id'}}" name="{{$reqfieldsInpField[$key]}}[{{$key}}]" class="form-control {{ empty($inpVal) ? 'mandateField' : '' }}" placeholder="Enter {{ $reqfieldsText[$key] }}" value="{{ $inpVal }}" {{ empty($orgVal) ? 'required':'disabled'}}>
                                            @else
                                            @php
                                                $inpOptions = explode(',', $reqFieldsOptions['value'][$key]);
                                                $inpOptionText = explode(',', $reqFieldsOptions['text'][$key]);
                                            @endphp
                                             @foreach($inpOptions as $ke => $inopt)
                                                <label class="form-check-inline form-check">
                                                    <input type="{{$reqfieldsInpType[$key]}}" name="{{$reqfieldsInpField[$key]}}[{{$key}}]" class="form-check-input {{ empty($inpVal) ? 'mandateField' : '' }}" {{ $inpVal == $inopt ? 'checked' : ''}} value="{{ $inopt }}"/>
                                                    {{$inpOptionText[$ke]}}
                                                </label>
                                             @endforeach
                                            @endif
                                           @if($reqfieldsInpEdit[$key])
                                                <button type="button" class="btn btn-primary btn-icon editInputApprovals" data-enableedit="{{$key.'-id'}}"><i class="ti ti-edit"></i></button>
                                            @endif
                                        </div>
                                        @if(empty($inpVal))
                                        <span class="text-danger d-block">Required</span>
                                        @endif
                                      </div>
                                @endforeach
                            </div>                                                               
                            <button type="submit"
                                class="btn btn-primary btn-sm float-end">Save & Execute</button>                            
                        </div>
                </div>            
            </form>
            
        </div>
         @if(env('enable_sign_pad') && decryptString($contract->contract_mode, 'contract_mode')  == 'new')
        <div class="attachmentsdiv pb-2" id="attachments_type_signing">
         <div class="py-3 px-2 w-100">
            <button type="button" class="btn btn-success btn-md float-start" id="showSignPad">To Sign</button>
         </div>
         <div id="attachments_type_signing_pad" style="display: none">
            <div class="card-header">
 
                <ul class="nav nav-tabs card-header-tabs" id="signApprovalProcessTab" role="tablist">
    
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="signature-tab" data-bs-toggle="tab"
                            data-bs-target="#signature-data-tab" type="button" role="tab" aria-controls="signature-data-tab"
                            aria-selected="true">1. Affix Signature</button>
                    </li>
                    <li class="nav-item " role="presentation">
                        <button class="nav-link" id="verify-signature-tab" data-bs-toggle="tab" data-bs-target="#verify-signature"
                            type="button" role="tab" aria-controls="verify-signature" aria-selected="false">
                            2. Verify Signature
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="execute-save-tab" data-bs-toggle="tab" data-bs-target="#execute-save"
                            type="button" role="tab" aria-controls="execute-save" aria-selected="false">3. Execute Contract</button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="signatureApprovalTabContent">
                    <div class="tab-pane fade show active" id="signature-data-tab" role="tabpanel"
                    aria-labelledby="signature-tab">
                            <button class="btn btn-primary me-sm-3 me-1 waves-effect waves-light step2 float-end"
                                        type="button" role="tab" aria-controls="attach-save" aria-selected="false">Next </button>                    
                        <div class="col-md-6 col-12">
                      <ul class="nav nav-pills card-header-pills mb-3 justify-content-center" id="signatureOptionTabs" role="tablist">
                        <li class="nav-item me-2" role="presentation">
                          <button class="nav-link active btn rounded-pill btn-label-warning waves-effect" id="signature-draw-tab" data-bs-toggle="pill" data-bs-target="#signature-draw" type="button" role="tab" aria-controls="pills-cc" aria-selected="true"><i class="ti ti-writing-sign me-2"></i>Draw Sign</button>
                        </li>
                        <li class="nav-item me-2" role="presentation">
                          <button class="nav-link btn rounded-pill btn-label-warning waves-effect" id="signature-upload-tab" data-bs-toggle="pill" data-bs-target="#signature-upload" type="button" role="tab" aria-controls="pills-cc" aria-selected="true"><i class="ti ti-signature me-2"></i>Upload Sign</button>
                        </li>
                        <li class="nav-item" role="presentation">
                          <button class="nav-link btn rounded-pill btn-label-warning waves-effect" id="signature-esign-tab" data-bs-toggle="pill" data-bs-target="#signature-esign" type="button" role="tab" aria-controls="pills-cc" aria-selected="true"><i class="ti ti-signature me-2"></i>E-Sign</button>
                        </li>
                      </ul>
                      <div class="tab-content px-0" id="signatureOptionTabContent">
                        <!-- Credit card -->
                        <div class="tab-pane fade show active" id="signature-draw" role="tabpanel" aria-labelledby="signature-draw-tab">
                          <div class="row g-3">
                            <div class="col-12">
                                <canvas id="signatureCanvas" class="border border-primary rounded"></canvas>
                                <br>
                                <button type="button" class="btn btn-sm btn-warning float-end" id="clearButton">Clear</button>                             
                            </div>
                          </div>
                        </div>
        
                        <!-- COD -->
                        <div class="tab-pane fade" id="signature-upload" role="tabpanel" aria-labelledby="signature-upload-tab">
                            <div class="form-group files color">
                                <input type="file" name="files[]" class="form-control" onchange="setSignature(event)" />
                            </div>
                        </div>
        
                        <!-- Gift card -->
                        <div class="tab-pane fade" id="signature-esign" role="tabpanel" aria-labelledby="signature-esign-tab">
                          <div class="row g-3">
                            <div class="col-12"> 
                                <div class="text-center">
                                    <img src="{{asset('assets/supportimgs/aadhaar-esign-api.png')}}" class="" width="200"/><br/>
                                </div>
                                <form action="{{url('contract/sign/'.$contract->id)}}" method="POST">
                                    @csrf
                                    <div class="misgtable my-4">
                                        @php
                                            $disableSubmit1 = 0;
                                        @endphp
                                        @foreach ($reqfieldsVal as $key => $vals)
                                            @php
                                            $buttonDisable = false;
                                            $orgVal = $reqfieldsVals[$key];
                                            $inpVal = $reqfieldsVals[$key];
                                            if($reqfieldsInpType[$key] == 'date' && $inpVal == $key){
                                                $orgVal = "";
                                                $inpVal = date('Y-m-d');
                                            }
                                            @endphp                    
                                            @if(!$vals)
                                                @php
                                                    $buttonDisable = true;
                                                    $disableSubmit1++;
                                                @endphp
                                            @endif
                                              <div id="{{$key.'-section-id'}}">
                                                <label for="{{$key.'-id'}}" class="form-label">{{ $reqfieldsText[$key] }}</label>
                                                <div class="input-group">
                                                    @if($reqfieldsInpType[$key] != 'radio')
                                                        <input type="{{$reqfieldsInpType[$key]}}" id="{{$key.'-id'}}" name="{{$reqfieldsInpField[$key]}}[{{$key}}]" class="form-control {{ empty($inpVal) ? 'mandateField' : '' }}" placeholder="Enter {{ $reqfieldsText[$key] }}" value="{{ $inpVal }}" {{ empty($orgVal) ? 'required':'disabled'}}>
                                                    @else
                                                    @php
                                                        $inpOptions = explode(',', $reqFieldsOptions['value'][$key]);
                                                        $inpOptionText = explode(',', $reqFieldsOptions['text'][$key]);
                                                    @endphp
                                                     @foreach($inpOptions as $ke => $inopt)
                                                        <label class="form-check-inline form-check">
                                                            <input type="{{$reqfieldsInpType[$key]}}" name="{{$reqfieldsInpField[$key]}}[{{$key}}]" class="form-check-input {{ empty($inpVal) ? 'mandateField' : '' }}" {{ $inpVal == $inopt ? 'checked' : ''}} value="{{ $inopt }}"/>
                                                            {{$inpOptionText[$ke]}}
                                                        </label>
                                                     @endforeach
                                                    @endif
                                                   @if($reqfieldsInpEdit[$key])
                                                        <button type="button" class="btn btn-primary btn-icon editInputApprovals" data-enableedit="{{$key.'-id'}}"><i class="ti ti-edit"></i></button>
                                                    @endif
                                                </div>
                                                @if(empty($inpVal))
                                                <span class="text-danger d-block">Required</span>
                                                @endif
                                              </div>
                                        @endforeach
                                    </div>                                     
                                    <div class="form-group">
                                        <input type="hidden" name="appId" class="form-control" value="{{$approvalValues->id}}">
                                    </div>
                                    <div class="form-group">
                                        <input type="hidden" name="indexId" id="indexId" class="form-control"
                                            value="{{ $lindex }}">
                                    </div>
                                    <div class="form-group">
                                        <input type="hidden" name="appType" id="appType{{ $lindex }}" class="form-control" value="approved">
                                    </div>
                                    <div class="form-group">
                                        <input type="hidden" name="appStatus" id="appStatus" class="form-control"
                                            value="{{ $approvalValues->status }}">
                                    </div>
                                    <div class="form-group">
                                        <input type="hidden" name="appPreStatus" id="appPreStatus" class="form-control"
                                            value="{{ $approvalValues->previous_status }}">
                                    </div>
                                    <div class="form-group">
                                        <input type="hidden" name="orderval" id="orderval" class="form-control"
                                            value="{{ $approvalValues->orderval }}">
                                    </div>
                                    <div class="form-group">
                                        <input type="hidden" name="unique_id" id="unique_id" class="form-control"
                                            value="{{ $approvalValues->unique_id }}">
                                    </div>                                    
                                    <button type="submit" class="btn btn-sm btn-success mt-2">Sign Now</button>
                                </form>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    </div>
                    <div class="tab-pane fade" id="verify-signature" role="tabpanel" aria-labelledby="verify-signature-tab">
                        <button class="btn btn-success me-sm-3 me-1 waves-effect waves-light step3 float-end d-none"
                            type="button" role="tab">Next </button>
                        <button class="btn btn-primary me-sm-3 me-1 waves-effect waves-light step1 float-end"
                            type="button" role="tab">Prev </button>
                            <div class="col-md-6 col-12">
                                <div class="text-left previewSignImgDiv border border-primary rounded mb-4 text-center">                        
                                    <img id="previewSignImg" alt="ONTRACK"/>
                                </div>                        
                                <form id="approvalSignatureForm" method="POST" enctype="multipart/form-data">
                                    <div class="OtpSection d-none">
                                        <div class="form-group">
                                            
                                            <input type="text" name="nextOtp" id="nextOtp" placeholder="Enter OTP"
                                                class="form-control">
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" id="btn_verify_otp"
                                                class="btn btn-success btn-sm pull-right">Verify</button>
                                            <button type="button" id="btn_resend_otp"
                                                class="btn btn-warning btn-sm pull-right">Resend</button>
                                                
                                            <input type="hidden" name="otpActionType" id="otpActionType" value="otp">                                                            
                                        </div>                                                    
                                    </div>                           
                                    <input type="hidden" name="contactId" placeholder="Enter Next Action" class="form-control" value="{{$contract->id}}" />
                                    <input type="hidden" name="currentSign" id="currentSign"/>
                                    <div id="signatureActionDiv">
                                        <button type="submit"
                                            class="btn btn-primary btn-sm pull-right" id="sendOtp">Sent OTP</button>
                                        <button type="button"
                                            class="btn btn-danger btn-sm pull-right">Cancel</button>
                                    </div>                            
                                </form>
                            </div>
                    </div>
                    <div class="tab-pane fade" id="execute-save" role="tabpanel" aria-labelledby="execute-save-tab">
                        <h3>Document Previewer</h3>
                        <form id="executeContractForm">
                            <div class="row">
                                <div class="col-md-6 text-center py-3 border border-secondary rounded" id="documentHtmlViewer">
                                </div>
                                <div class="col-md-6">
                                <button type="submit"
                                    class="btn btn-primary btn-sm float-end">Save & Execute</button>                            
                                    <div class="misgtable my-4">
                                        @php
                                            $disableSubmit1 = 0;
                                        @endphp
                                        @foreach ($reqfieldsVal as $key => $vals)
                                            @php
                                            $buttonDisable = false;
                                            $orgVal = $reqfieldsVals[$key];
                                            $inpVal = $reqfieldsVals[$key];
                                            if($reqfieldsInpType[$key] == 'date' && $inpVal == $key){
                                                $orgVal = "";
                                                $inpVal = date('Y-m-d');
                                            }
                                            @endphp                    
                                            @if(!$vals)
                                                @php
                                                    $buttonDisable = true;
                                                    $disableSubmit1++;
                                                @endphp
                                            @endif
                                              <div id="{{$key.'-section-id'}}">
                                                <label for="{{$key.'-id'}}" class="form-label">{{ $reqfieldsText[$key] }}</label>
                                                <div class="input-group">
                                                    @if($reqfieldsInpType[$key] != 'radio')
                                                        <input type="{{$reqfieldsInpType[$key]}}" id="{{$key.'-id'}}" name="{{$reqfieldsInpField[$key]}}[{{$key}}]" class="form-control {{ empty($inpVal) ? 'mandateField' : '' }}" placeholder="Enter {{ $reqfieldsText[$key] }}" value="{{ $inpVal }}" {{ empty($orgVal) ? 'required':'disabled'}}>
                                                    @else
                                                    @php
                                                        $inpOptions = explode(',', $reqFieldsOptions['value'][$key]);
                                                        $inpOptionText = explode(',', $reqFieldsOptions['text'][$key]);
                                                    @endphp
                                                     @foreach($inpOptions as $ke => $inopt)
                                                        <label class="form-check-inline form-check">
                                                            <input type="{{$reqfieldsInpType[$key]}}" name="{{$reqfieldsInpField[$key]}}[{{$key}}]" class="form-check-input {{ empty($inpVal) ? 'mandateField' : '' }}" {{ $inpVal == $inopt ? 'checked' : ''}} value="{{ $inopt }}"/>
                                                            {{$inpOptionText[$ke]}}
                                                        </label>
                                                     @endforeach
                                                    @endif
                                                   @if($reqfieldsInpEdit[$key])
                                                        <button type="button" class="btn btn-primary btn-icon editInputApprovals" data-enableedit="{{$key.'-id'}}"><i class="ti ti-edit"></i></button>
                                                    @endif
                                                </div>
                                                @if(empty($inpVal))
                                                <span class="text-danger d-block">Required</span>
                                                @endif
                                              </div>
                                        @endforeach
                                    </div>                                                               
                                    <button type="submit"
                                        class="btn btn-primary btn-sm float-end">Save & Execute</button>                            
                                </div>                            
                            </div>                            
                            @csrf
                            <div class="form-group">
                                <input type="hidden" name="contactId" placeholder="Enter Next Action"
                                    class="form-control" value="{{$contract->id}}">
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="appId" class="form-control" value="{{$approvalValues->id}}">
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="indexId" id="indexId" class="form-control"
                                    value="{{ $lindex }}">
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="appType" id="appType{{ $lindex }}" class="form-control" value="approved">
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="appStatus" id="appStatus" class="form-control"
                                    value="{{ $approvalValues->status }}">
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="appPreStatus" id="appPreStatus" class="form-control"
                                    value="{{ $approvalValues->previous_status }}">
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="orderval" id="orderval" class="form-control"
                                    value="{{ $approvalValues->orderval }}">
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="unique_id" id="unique_id" class="form-control"
                                    value="{{ $approvalValues->unique_id }}">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
         </div>
        </div>
        @endif
    </div>
    

<!-- Activity Time Line Template -->
@include('contract::contract.activitytimeline')

</div>
@section('page-script')
<script>
    checkGPS();
        //CheckGps
    async function checkGPS() {
        const geoPresent = await getLocation();
    
        if (!geoPresent.success) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'GPS Not Enabled Please Enable It And Try Refresh',
                customClass: {
                    confirmButton: 'btn btn-danger waves-effect waves-light'
                },
                didClose: () => {
                    $('button').attr('disabled', true);
                }
            });
        }
    }

    function getLocation() {
        return new Promise((resolve, reject) => {
            // The Geolocation API only works in a secure context (HTTPS or localhost).
            // On a plain-HTTP origin the browser blocks it regardless of the user's
            // location permission, so getCurrentPosition would always fail. Don't hard
            // block signing in that case — treat it as available with an unknown location.
            if (typeof window !== 'undefined' && window.isSecureContext === false) {
                console.warn('Geolocation requires HTTPS; skipping GPS check on insecure origin.');
                resolve(locationJson("Geolocation unavailable on insecure (HTTP) origin.", true));
                return;
            }
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        resolve(locationJson(
                            "Location access granted.\nLatitude: " + position.coords.latitude +
                            "\nLongitude: " + position.coords.longitude,
                            true
                        ));
                    },
                    (error) => {
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                resolve(locationJson("Location permission denied."));
                                break;
                            case error.POSITION_UNAVAILABLE:
                                resolve(locationJson("Location information is unavailable."));
                                break;
                            case error.TIMEOUT:
                                resolve(locationJson("Location request timed out."));
                                break;
                            default:
                                resolve(locationJson("An unknown error occurred."));
                                break;
                        }
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                );
            } else {
                resolve(locationJson("Geolocation is not supported by this browser."));
            }
        });
    }
    
    function locationJson(locMessage, success = false) {
        return {
            message: locMessage,
            success: success
        };
    }
</script>
@endsection