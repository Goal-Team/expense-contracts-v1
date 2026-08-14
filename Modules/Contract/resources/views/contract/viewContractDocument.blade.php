@if(isset($contract->contract_attachment_filename))
    
    @php
        $contractFileName = $contract->contract_attachment_filename;
        $filename = $contractFileName;
        $file_url = fileViewUrl($contract->contract_attachment, true);
        $filepath = $contract->contract_attachment;
        
        $extFile = pathinfo($filename, PATHINFO_EXTENSION);
        
        $showInGoogleDocs = ['doc', 'docx'];
        
        if(fileStorageType() != "Local"){
            $getUrl = get_google_drive_doc_link($contractFileName,$contract->contract_attachment,'view', 'test');
            if(!str_contains($getUrl, '/invalidfile')){
                $docAlertBox = Helper::getDocumentDisplaySection($getUrl);
                echo $docAlertBox;
            }else{
                echo '<div class="alert alert-danger mx-2 mt-3">Invalid User/File Access</div>';
            }              
        }
        else{
            if(str_contains($file_url, '/invalidfile')){
               echo '<div class="alert alert-danger mx-2 mt-2">Invalid User/File Access</div>'; 
            }else{
               echo '<iframe src="'.url('/').'/showDocument/'.$contract->id.'" height="500" width="100%"></iframe>';
            }
        } 
    @endphp
@endif