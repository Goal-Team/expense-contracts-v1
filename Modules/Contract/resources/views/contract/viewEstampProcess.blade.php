@if(!(strtolower($contract->contract_status) == 'executed'))
<div class="card">
    <div class="card-header">
        <h5 class="card-title">E-Stamp Request Process</h5>
        <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">

            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="upload-data-tab" data-bs-toggle="tab"
                    data-bs-target="#upload-data" type="button" role="tab" aria-controls="upload-data"
                    aria-selected="true">1. Download Documents</button>
            </li>
            <li class="nav-item " role="presentation">
                <button class="nav-link" id="verify-info-tab" data-bs-toggle="tab" data-bs-target="#verify-info"
                    type="button" role="tab" aria-controls="verify-info" aria-selected="false">
                    2. Upload Filled Documents
                </button>
            </li>

            <li class="nav-item" id="uplod" role="presentation">
                <button class="nav-link" id="attach-save-tab" data-bs-toggle="tab" data-bs-target="#attach-save"
                    type="button" role="tab" aria-controls="attach-save" aria-selected="false">3. Verify and Request E-Stamp</button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade active show" id="upload-data" role="tabpanel"
                aria-labelledby="upload-data-tab">
                    <h6 class="card-title">Step 1: Download the required documents and fill in the data.</h6>
                    <h6 class="card-title">Step 2: Upload All Filled Documents.</h6>
                    <h6 class="card-title">Step 3: Request E-Stamp.</h6>
                    <div class="row my-4">
                        <div class="col">
                            <h5 class="card-title">Application Forms<span class="text-danger">*</span></h5>
                            
                                @csrf
                                <div class="col-md-6 mb-3">
                                    <ul class="list-group">
                                        <li class="list-group-item"><i class="ti ti-xs ti-clipboard-text me-3"></i>Application Form <a href="{{asset('assets/supportdocs/ontrackestampapplication.pdf')}}" download target="new"> Click </a> to download.</li>
                                        <li class="list-group-item"><i class="ti ti-xs ti-notes me-3"></i>NOC Form <a href="{{asset('assets/supportdocs/ontrackestampchallan.pdf')}}" download target="new"> Click </a> to download.</li>
                                        <li class="list-group-item"><i class="ti ti-xs ti-building-bank me-3"></i>Bank Challan <a href="{{asset('assets/supportdocs/ontrackestampnoc.pdf')}}" download target="new"> Click </a> to download.</li>                                                
                                    </ul>
                                   
                                </div>

                            <script>
                                document.getElementById('csv_file').addEventListener('change', function() {
                                    // Submit the form
                                    //document.getElementById('createcontractview').submit();

                                    // Show the loading spinner/message
                                    //document.querySelector('.loading').style.display = 'block';
                                });
                            </script>
                        </div>
                    </div>

            </div>
            <div class="tab-pane fade " id="verify-info" role="tabpanel"
                aria-labelledby="verify-info-tab">
                <form id="createcontractview" action="{{url('contracts/builk-import/upload')}}"
                method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                    <h5>Check List</h5>
                    <h6>1. E-stamp application</h6> 
                    <h6>2. NOC letter </h6>
                    <h6>3. Self attested PAN copy of firm</h6>
                    <h6>4. Authorization letter for bearer if you necessary</h6>
                    <h6>5. Payment acknowledgement for transferring e-stamp amount to bank account of Provider</h6>
                    
                    <label for="csv_file" class="form-label">Choose Files</label>
                    <input class="form-control" type="file" id="csv_file" name="file[]" multiple
                    accept=".pdf,.png,.jpeg" required>
                    </div>
                    <button type="button"
                    class="btn btn-primary me-sm-3 me-1 waves-effect waves-light">Upload
                    Files</button>
                </form>
            </div>                    
            <div class="tab-pane fade " id="attach-save" role="tabpanel"
                aria-labelledby="attach-save-tab">
                <form id="createcontractview" action="{{url('contracts/builk-import/upload')}}"
                method="POST" enctype="multipart/form-data">
                    @csrf
                    <button type="button"
                    class="btn btn-primary me-sm-3 me-1 waves-effect waves-light">Request E-Stamp</button>
                </form>
            </div>                    
        </div>
    </div>
</div>
@else
<div class="row mt-4">
<div class="col-12 col-lg-6">
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="card-title m-0">E-Stamp Details</h5>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-start align-items-center mb-4">
<span class="avatar rounded-circle bg-label-primary me-3 d-flex align-items-center justify-content-center"><i class="ti ti-hash ti-xs"></i></span>
            <div class="d-flex flex-column">
              <span class="fs-6 fw-bold">{{date('Y')."/".$contract->contract_unique_id}}</span>
            </div>
          </div>
          <div class="d-flex justify-content-start align-items-center mb-4">
            <span class="avatar rounded-circle bg-label-warning me-3 d-flex align-items-center justify-content-center"><i class="ti ti-calendar ti-xs"></i></span>
            <h6 class="text-nowrap mb-0">{{ date('d-M-Y h:i:s A', strtotime($contract->updated_at)) }}</h6>
          </div>
          <div class="d-flex justify-content-between">
            <h6 class="mb-1">Document Details</h6>
          </div>
          <p class=" mb-1"><a href="{{fileViewUrl($contract->contract_attachment)}}">Link</a></p>
        </div>
      </div>
    </div>    
</div>
@endif