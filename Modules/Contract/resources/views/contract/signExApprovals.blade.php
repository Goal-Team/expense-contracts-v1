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
    
    #timelineFlows{
        display: block !important;
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
        <div class="col px-2 mt-3">
           <div class="form-check form-check-inline">
              <label class="form-check-label">
                 <input type="radio" class="attachmentstype form-check-input" name="attachments_type" value="Upload" data-div="signing" checked/>
                 Signing Process</label>
           </div>
           @if(env('enable_upload_signed_doc_ex'))
           <div class="form-check form-check-inline">
              <label class="form-check-label">
                 <input type="radio" class="attachmentstype form-check-input" name="attachments_type" value="template" data-div="upload"/>
                 Upload Signed Document</label>
           </div>
           @endif
        </div>
        @if(env('enable_upload_signed_doc_ex'))
        <div class="p-2 attachmentsdiv" id="attachments_type_upload" style="display: none;">
            <form id="executeFormSignedDoc">
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
                
                <div class="row">
                        <div class="col-md-6">
                            <div class="form-group files color">
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
                            </div>                                                               
                            <button type="submit"
                                class="btn btn-primary btn-sm float-end">Save & Execute</button>                            
                        </div>
                </div>            
            </form>
        </div>
        @endif
        <div class="attachmentsdiv" id="attachments_type_signing">
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
                        @if($approvalValues->signed_type == 'custom')
                            <button class="btn btn-primary me-sm-3 me-1 waves-effect waves-light step2 float-end"
                                        type="button" role="tab" aria-controls="attach-save" aria-selected="false">Next </button>   
                        @endif
                        <div class="col-md-6 col-12">
                      <ul class="nav nav-pills card-header-pills mb-3 justify-content-center" id="signatureOptionTabs" role="tablist">
                        @if($approvalValues->signed_type == 'custom')
                            <li class="nav-item me-2" role="presentation">
                              <button class="nav-link active btn rounded-pill btn-label-warning waves-effect" id="signature-draw-tab" data-bs-toggle="pill" data-bs-target="#signature-draw" type="button" role="tab" aria-controls="pills-cc" aria-selected="true"><i class="ti ti-writing-sign me-2"></i>Draw Sign</button>
                            </li>
                            <li class="nav-item me-2" role="presentation">
                              <button class="nav-link btn rounded-pill btn-label-warning waves-effect" id="signature-upload-tab" data-bs-toggle="pill" data-bs-target="#signature-upload" type="button" role="tab" aria-controls="pills-cc" aria-selected="true"><i class="ti ti-signature me-2"></i>Upload Sign</button>
                            </li>
                        @else
                            <li class="nav-item" role="presentation">
                              <button class="nav-link btn {{ $approvalValues->signed_type != 'custom' ? 'active' : '' }} rounded-pill btn-label-warning waves-effect" id="signature-esign-tab" data-bs-toggle="pill" data-bs-target="#signature-esign" type="button" role="tab" aria-controls="pills-cc" aria-selected="true"><i class="ti ti-signature me-2"></i>E-Sign</button>
                            </li>
                        @endif
                      </ul>
                      <div class="tab-content px-0" id="signatureOptionTabContent">
                        @if($approvalValues->signed_type == 'custom')
                        <!-- Custom Sign  -->
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
                        @else
                        <!-- Gift card -->
                        <div class="tab-pane fade {{ $approvalValues->signed_type != 'custom' ? 'show active' : '' }}" id="signature-esign" role="tabpanel" aria-labelledby="signature-esign-tab">
                          <div class="row g-3">
                            <div class="col-12">                        
                                <div class="text-center">
                                    <img src="{{asset('assets/supportimgs/aadhaar-esign-api.png')}}" class="" width="200"/><br/>
                                    <form action="{{url('contract/external/sign/'.$exId)}}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="contactId" placeholder="Enter Next Action" class="form-control" value="{{$exId}}" />
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
                        @endif
                      </div>
                    </div>
                    </div>
                    <div class="tab-pane fade" id="verify-signature" role="tabpanel" aria-labelledby="verify-signature-tab">
                        <button class="btn btn-success me-sm-3 me-1 waves-effect waves-light step3 float-end"
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
                                    <input type="hidden" name="contactId" placeholder="Enter Next Action" class="form-control" value="{{$exId}}" />
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
                                    </div>                                                               
                         
                                </div>                            
                            </div>                            
                            @csrf
                            <div class="form-group">
                                <input type="hidden" name="contactId" placeholder="Enter Next Action"
                                    class="form-control" value="{{$exId}}">
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
</div>