@if(isset($contract->contract_attachment_filename))

    @php
        $contractFileName = $contract->contract_attachment_filename;
        $filename = $contractFileName;
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
            // fileViewUrl() is read only here, so it stays here. On Google or Microsoft
            // storage it repeated the same permission round trip that
            // get_google_drive_doc_link() makes above - same file id, same email, same
            // $onlyView flag - and the page threw the answer away. 915 ms per load.
            $file_url = fileViewUrl($contract->contract_attachment, true);
            if(str_contains($file_url, '/invalidfile')){
               echo '<div class="alert alert-danger mx-2 mt-2">Invalid User/File Access</div>';
            }else{
               echo '<iframe src="'.url('/').'/showDocument/'.$contract->id.'" height="500" width="100%"></iframe>';
            }
        }
    @endphp
@endif
