@if(isset($contract->contract_attachment_filename))
    
    @php
        $contractFileName = $contract->contract_attachment_filename;
        $filename = $contractFileName;
        $file_url = fileViewUrl($contract->contract_attachment);
        $filepath = $contract->contract_attachment;
        
        $extFile = pathinfo($filename, PATHINFO_EXTENSION);
        
        $showInGoogleDocs = ['doc', 'docx'];
        
        if(fileStorageType() != "Local"){
            $getUrl = get_google_drive_doc_link($contractFileName,$contract->contract_attachment,'view', 'test');
            if(!str_contains($getUrl, '/invalidfile')){
                echo '
                <div class="alert alert-danger mt-2">If below document Not Loaded Please <a href="'.$getUrl.'" target="blank">Click Here</a>. Because of some security reasons its not loaded.</div>
                <iframe src="'.$getUrl.'" height="500" width="100%"></iframe>';
            }else{
                echo '<div class="alert alert-danger mx-2 mt-3">Invalid User/File Access</div>';
            }              
        }
        else{
            echo '<iframe src="'.url('/').'/contracts/external/showdoc/'.$exId.'" height="500" width="100%"></iframe>';
        } 
    @endphp
@endif