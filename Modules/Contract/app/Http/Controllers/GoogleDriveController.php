<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;

use Google_Client;
use Google_Exception;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Google_Service_Drive_CommentList;
use Google_Service_Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Google_Service_Drive_Permission;
use Google_Service_Docs_Request;
use Google_Service_Docs;
use Google_Service_Docs_ReplaceAllTextRequest;
use App\Models\Contract;
use Exception;
use Google_Service_Docs_BatchUpdateDocumentRequest;
use App\Models\ContractType;
use App\Models\ContractPartyData;
use App\Models\BranchUser;
use App\Helpers\Helpers;

class GoogleDriveController extends Controller
{
    private $client;

    public function __construct()
    {
        if (Controller::checkCurrentAuth("Contracts") != 1) {
            return abort('404');
        }
    }
    
    public function setConfig(){
        $config = [];
        $configArr = [
                "client_id" => env('CLIENT_ID_GO'),
                "project_id" => env('PROJECT_ID_GO'),
                "auth_uri" => env('AUTH_URI_GO'),
                "token_uri" => env('TOKEN_URI_GO'),
                "auth_provider_x509_cert_url" => env('AUTH_CERT_GO'),
                "client_secret" => env('CLIENT_SECRET_GO'),
                "redirect_uris" => [
                    env('AUTH_REDIRECT_GO')
                ]
            ];
        $config['web'] = $configArr;
        
        return $config;
    }

    public function storeFile($file, $id = 0, $cid = 0, $fileName="")
    {
        // Prepare the file path and metadata
        $filePath = $file->getPathname();
        //$fileName = $file->getClientOriginalName();
        if($fileName == ""){
            $fileName = file_name($file);
        }        
        $fileType = $file->getMimeType();
        // Initialize Google Client
        $clientSecretPath = $this->setConfig();
        $client = new Google_Client();
        $client->setAuthConfig($clientSecretPath);
        $client->addScope(Google_Service_Drive::DRIVE);

        // Check and refresh access token if expired
        if ($client->isAccessTokenExpired()) {
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
        }
        
        if($cid > 0){
            // Fetch contract details
            $contracts = Contract::find($cid);
            if (!$contracts) {
                return 'Contract not found';
            }
    
            $contractType = ContractType::where('contract_type_id', $contracts->contract_type)
                ->pluck('contract_type')
                ->first();
                
            $internal_first_location = "";
            foreach($contracts->contractPartyList as $partyLoc){
                
                if($partyLoc->contract_party_location_id == !null && $partyLoc->contract_party_type == "Internal"){
                    $internal_first_location = $partyLoc;
                    break;
                }
            } 
            
    
            $branchs = BranchUser::select(
                'id',
                decrypt_data('BranchName', 'branch'),
                decrypt_data('branchstatus', 'branch'),
                decrypt_data('Doorno', 'branch'),
                decrypt_data('StreetName', 'branch'),
                decrypt_data('AreaName', 'branch'),
                decrypt_data('Landmark', 'branch'),
                decrypt_data('PinCode', 'branch'),
                decrypt_data('ContactNumber', 'branch'),
                decrypt_data('branchheadname', 'branch'),
                decrypt_data('departments', 'branch')
            )->where('id', $internal_first_location->contract_party_location_id)->first();
            
            if($branchs){
                $internal_first_location = $branchs->BranchName;
            }            
        }
        $service = new Google_Service_Drive($client);
        try {
            // Get or create the main contract folder
            $mainFolderId = $this->getOrCreateFolder($service, 'contracts');
            
            if($cid > 0){
                
                $locationfolderId = $this->getOrCreateFolder($service, $internal_first_location, $mainFolderId);
                
                $folderId = $this->getOrCreateFolder($service, $contractType, $locationfolderId);
    
                // Get or create the subfolder using the custom field ID
                $subfolderId = $this->getOrCreateFolder($service, (string)$cid, $folderId);
                
                $statusFolderArray = config('app.APPROVAL_FOLDER_STRCTURE');
                
                $extraFolder = $statusFolderArray[strtolower($contracts->contract_status)] ?? '';
                
                if($extraFolder != ""){
                    // Get or create the subfolder using the custom field ID
                    $subfolderId = $this->getOrCreateFolder($service, $extraFolder, $subfolderId);
                }
            }else{
                //others/forms/utaking
                
                $locationfolderId = $this->getOrCreateFolder($service, 'others', $mainFolderId);
                
                $folderId = $this->getOrCreateFolder($service, 'forms', $locationfolderId);                
                
                $subfolderId = $this->getOrCreateFolder($service, 'utaking', $folderId);                
            }
            
            // Prepare file metadata
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $fileName,
                'parents' => [$subfolderId]
            ]);

            // Read the file content
            $content = file_get_contents($filePath);

            // Upload the file to Google Drive
            $uploadedFile = $service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $fileType,
                'uploadType' => 'multipart'
            ]);

            return $uploadedFile->id; // Return the uploaded file ID
        } catch (\Exception $e) {
            return 'Error uploading file: ' . $e->getMessage(); // Return error message
        }
    }

    public function get_file_path($cid)
    {

        $clientSecretPath = $this->setConfig();
        $client = new Google_Client();
        $client->setAuthConfig($clientSecretPath);
        $client->addScope(Google_Service_Drive::DRIVE);

        // Check and refresh access token if expired
        if ($client->isAccessTokenExpired()) {
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
        }

        // Fetch contract details
        $contracts = Contract::find($cid);
        if (!$contracts) {
            return 'Contract not found';
        }

        $contractType = ContractType::where('contract_type_id', $contracts->contract_type)
            ->pluck('contract_type')
            ->first();
            
        $internal_first_location = "";
        foreach($contracts->contractPartyList as $partyLoc){
            
            if($partyLoc->contract_party_location_id == !null && $partyLoc->contract_party_type == "Internal"){
                $internal_first_location = $partyLoc;
                break;
            }
        } 
        

        $branchs = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch')
        )->where('id', $internal_first_location->contract_party_location_id)->first();
        
        if($branchs){
            $internal_first_location = $branchs->BranchName;
        }            

        $service = new Google_Service_Drive($client);
        try {
            // Get or create the main contract folder
            $mainFolderId = $this->getOrCreateFolder($service, 'contracts');
            
            $locationfolderId = $this->getOrCreateFolder($service, $internal_first_location, $mainFolderId);
            
            $folderId = $this->getOrCreateFolder($service, $contractType, $locationfolderId);

            // Get or create the subfolder using the custom field ID
            $subfolderId = $this->getOrCreateFolder($service, (string)$cid, $folderId);
            
            $statusFolderArray = config('app.APPROVAL_FOLDER_STRCTURE');
            
            $extraFolder = $statusFolderArray[strtolower($contracts->contract_status)] ?? '';
            
            if($extraFolder != ""){
                // Get or create the subfolder using the custom field ID
                $subfolderId = $this->getOrCreateFolder($service, $extraFolder, $subfolderId);
            }

            return $subfolderId; // Return folder id
            
        } catch (\Exception $e) {
            return 'Error uploading file: ' . $e->getMessage(); // Return error message
        }
    }
    
    public function storeContent($filePath, $parentId, $fileName){
        
        // Initialize Google Client
        $clientSecretPath = $this->setConfig();
        $client = new Google_Client();
        $client->setAuthConfig($clientSecretPath);
        $client->addScope(Google_Service_Drive::DRIVE);

        // Check and refresh access token if expired
        if ($client->isAccessTokenExpired()) {
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
        }

        $service = new Google_Service_Drive($client);
        try {

            // Prepare file metadata
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $fileName,
                'parents' => [$parentId]
            ]);

            //Get File Type
            $fileType = mime_content_type($filePath);
            
            // Read the file content
            $content = file_get_contents($filePath);

            // Upload the file to Google Drive
            $uploadedFile = $service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $fileType,
                'uploadType' => 'multipart'
            ]);

            return $uploadedFile->id; // Return the uploaded file ID
        }catch (\Exception $e) {
            return 'Error permission file changes: ' . $e->getMessage(); // Return error message
        }
    }

    /**
     * Replace the CONTENT of an existing Drive file (keeps the same file id) so Google
     * Drive retains it as a new revision of the same document — preserving version /
     * change history instead of creating a disconnected new file. Returns the same
     * file id on success, or an 'Error...' string on failure.
     */
    public function updateFileContent($fileId, $localFilePath){

        $clientSecretPath = $this->setConfig();
        $client = new Google_Client();
        $client->setAuthConfig($clientSecretPath);
        $client->addScope(Google_Service_Drive::DRIVE);

        if ($client->isAccessTokenExpired()) {
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
        }

        $service = new Google_Service_Drive($client);

        try {
            if (!is_file($localFilePath)) {
                return 'Error updating file: source file not found';
            }
            $content = file_get_contents($localFilePath);
            $mime = mime_content_type($localFilePath) ?: 'application/octet-stream';

            // Empty metadata object: only the content is replaced, name/parents untouched.
            $emptyMeta = new Google_Service_Drive_DriveFile();
            $updated = $service->files->update($fileId, $emptyMeta, [
                'data' => $content,
                'mimeType' => $mime,
                'uploadType' => 'multipart',
                'keepRevisionForever' => false,
            ]);

            return $updated->id;
        } catch (\Exception $e) {
            return 'Error updating file: ' . $e->getMessage();
        }
    }

    public function copyFile($cid, $copyFile)
    {

        // Initialize Google Client
        $clientSecretPath = $this->setConfig();
        $client = new Google_Client();
        $client->setAuthConfig($clientSecretPath);
        $client->addScope(Google_Service_Drive::DRIVE);

        // Check and refresh access token if expired
        if ($client->isAccessTokenExpired()) {
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
        }

        // Fetch contract details
        $contracts = Contract::find($cid);
        if (!$contracts) {
            return 'Contract not found';
        }

        $contractType = ContractType::where('contract_type_id', $contracts->contract_type)
            ->pluck('contract_type')
            ->first();
            
        $internal_first_location = "";
        foreach($contracts->contractPartyList as $partyLoc){
            
            if($partyLoc->contract_party_location_id == !null && $partyLoc->contract_party_type == "Internal"){
                $internal_first_location = $partyLoc;
                break;
            }
        } 
        

        $branchs = BranchUser::select(
            'id',
            decrypt_data('BranchName', 'branch'),
            decrypt_data('branchstatus', 'branch'),
            decrypt_data('Doorno', 'branch'),
            decrypt_data('StreetName', 'branch'),
            decrypt_data('AreaName', 'branch'),
            decrypt_data('Landmark', 'branch'),
            decrypt_data('PinCode', 'branch'),
            decrypt_data('ContactNumber', 'branch'),
            decrypt_data('branchheadname', 'branch'),
            decrypt_data('departments', 'branch')
        )->where('id', $internal_first_location->contract_party_location_id)->first();
        
        if($branchs){
            $internal_first_location = $branchs->BranchName;
        }            

        $service = new Google_Service_Drive($client);
        try {
            // Get or create the main contract folder
            $mainFolderId = $this->getOrCreateFolder($service, 'contracts');
            
            $locationfolderId = $this->getOrCreateFolder($service, $internal_first_location, $mainFolderId);
            
            $folderId = $this->getOrCreateFolder($service, $contractType, $locationfolderId);

            // Get or create the subfolder using the custom field ID
            $subfolderId = $this->getOrCreateFolder($service, (string)$cid, $folderId);
            
            $statusFolderArray = config('app.APPROVAL_FOLDER_STRCTURE');
            
            $extraFolder = $statusFolderArray[strtolower($contracts->contract_status)] ?? '';
            
            if($extraFolder != ""){
                // Get or create the subfolder using the custom field ID
                $subfolderId = $this->getOrCreateFolder($service, $extraFolder, $subfolderId);
            }
             
            $parentExist = $this->checkParentFolder($copyFile,$subfolderId);
            
            if($parentExist !== true){
                $copiedFileName =  now()->timestamp."_copy_".$contracts->contract_attachment_filename;
                // Prepare file metadata
                $fileNew = new Google_Service_Drive_DriveFile([
                    'name' => $copiedFileName,
                    'parents' => [$subfolderId]
                ]);
    
                // Prepare file metadata
                $fileMetadata = new Google_Service_Drive_DriveFile([
                    'resource' => $fileNew,
                    'fileId' => $copyFile
                ]);
    
                // Upload the file to Google Drive
                $uploadedFile = $service->files->copy($copyFile, $fileNew);
    
                return [$copiedFileName, $uploadedFile->id]; // Return the uploaded file ID
            }
            return [$copyFile];

        } catch (\Exception $e) {
            return ['Error uploading file: ' . $e->getMessage()]; // Return error message
        }
    }
    
    public function checkParentFolder($fromFile,$toFolder){
        
        // Initialize Google Client
        $clientSecretPath = $this->setConfig();
        $client = new Google_Client();
        $client->setAuthConfig($clientSecretPath);
        $client->addScope(Google_Service_Drive::DRIVE);

        // Check and refresh access token if expired
        if ($client->isAccessTokenExpired()) {
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
        }
        
        $OptParams = array(
            'fields' => 'parents'
        );        
        
        $service = new Google_Service_Drive($client);
        
        
        try{
            $Reponse = $service->files->get($fromFile, $OptParams);
            $parentId = $Reponse->getParents();
            if (count($parentId) > 0 && $parentId[0] == $toFolder) {
                return true;
            }else{
                return false;
            }
        }catch (\Exception $e) {
            return 'Error Check file exist: ' . $e->getMessage(); // Return error message
        }
    }
    
    public function downloadUrl($fileID, $fileName){
        // Initialize Google Client
        $clientSecretPath = $this->setConfig();
        $client = new Google_Client();
        $client->setAuthConfig($clientSecretPath);
        $client->addScope(Google_Service_Drive::DRIVE);

        // Check and refresh access token if expired
        if ($client->isAccessTokenExpired()) {
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
        }

        $service = new Google_Service_Drive($client);
        try {

            // Prepare file metadata
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'fileid' => $fileID
            ]);

            // Upload the file to Google Drive
            $uploadedFile = $service->files->get($fileID, ['alt' => 'media']);
            
            return $uploadedFile->getBody();

            //return $uploadedFile->response['downloadUri']; // Return the uploaded file ID   
        }catch (\Exception $e) {
            return 'Error permission file changes: invalid' . $e->getMessage(); // Return error message
        }
    }    
    public function getFileUrl($fileid, $edit = false)
    {
        $loggedEmail = Helpers::userInfo()->email ?? "";

        $fileUrl = $this->changePermission($fileid,$loggedEmail, "", true);

        if (strpos(strtolower($fileUrl), 'error') !== false) {
            return url('invalidfile');
        }

        // Open the document in editable mode when requested (Drive keeps revisions of the
        // same file); otherwise the read-only preview link.
        return 'https://drive.google.com/file/d/' . $fileid . ($edit ? '/edit' : '/view');
    }
    public function changePermission($fileid, $prev_email="", $current_email="", $onlyView=false)
    {
        
        // Initialize Google Client
        $clientSecretPath = $this->setConfig();
        $client = new Google_Client();
        $client->setAuthConfig($clientSecretPath);
        $client->addScope(Google_Service_Drive::DRIVE);

        // Check and refresh access token if expired
        if ($client->isAccessTokenExpired()) {
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
        }
        
        $OptParams = array(
            'fields' => '*'
        );        
        
        $service = new Google_Service_Drive($client);
        
        
        try{
            
            $Reponse = $service->files->get($fileid, $OptParams);
            
            $permExist = [];
          
            if(!is_array($prev_email)){
              $prev_email = [$prev_email]; 
            }
            
            if(!is_array($current_email)){
              $current_email = [$current_email]; 
            }
            
            $prev_mail_unique = array_unique($prev_email);
            
            $prev_email = array_diff($prev_mail_unique, $current_email);
            
            $falseValues = array_map(function($element) { return false; }, $prev_email);
            $permExist = array_combine($prev_email, $falseValues);                

            $falseValues = array_map(function($element) { return false; }, $current_email);
            $permExist = array_merge($permExist, array_combine($current_email, $falseValues));                
            
            //$permExist = [$prev_email => false, $current_email => false];
            
            foreach ($Reponse->getPermissions() as $PermissionValue) {
                $pushEmailAccess = false;
                if(env('owner_g_mail') != $PermissionValue->emailAddress){
                    if(in_array($PermissionValue->emailAddress,$prev_email)){
                        $PermissionId = $PermissionValue->getId();
                        if(!$onlyView){
                            $updatedPerm = new Google_Service_Drive_Permission();
                            $updatedPerm->setRole("reader");
                            $result = $service->permissions->update($fileid,$PermissionId,$updatedPerm);
                        }
                        $permExist[$PermissionValue->emailAddress] = true;
                    }else{
                        $pushEmailAccess = true;
                    }
                    
                    if(in_array($PermissionValue->emailAddress,$current_email)){
                        $PermissionId = $PermissionValue->getId();
                        if(!$onlyView){
                            $updatedPerm = new Google_Service_Drive_Permission();
                            $updatedPerm->setRole("writer");
                            $result = $service->permissions->update($fileid,$PermissionId,$updatedPerm);
                        }
                        $permExist[$PermissionValue->emailAddress] = true;
                    }else{
                        $pushEmailAccess = true;
                    }
                }
    
            }

            foreach($permExist as $emailperm => $permEx){
                if($permEx === false && !empty($emailperm) && env('owner_g_mail') != $emailperm){
                    $role = 'writer';
                    if(in_array($emailperm,$prev_email)){
                        $role = 'reader';
                    }
                    $permission = new Google_Service_Drive_Permission([
                        'type' => 'user',
                        'role' => $role,
                        'emailAddress' => $emailperm
                    ]);
                    $service->permissions->create($fileid, $permission);        
                }
                
            }
           
        }catch (\Exception $e) {

            // echo 'Error permission file changes: ' . $e->getMessage() . "\n";
            // echo "Line: " . $e->getLine() . "\n";
            // // print_r($prev_email);
            // // print_r($current_email);
            // // print_r($permExist);
            // // die;
            return 'Error permission file changes: ' . $e->getMessage(); // Return error message
        }
    }

    /**
     * Set a single user's cloud-file permission to a specific level.
     * Level maps: readonly->reader, editor->writer, commentator->commenter.
     * The drive owner (env('owner_g_mail')) is never modified.
     */
    public function setFilePermission($fileid, $email, $level = 'editor')
    {
        $email = trim((string) $email);
        if (empty($fileid) || empty($email) || env('owner_g_mail') == $email) {
            return;
        }

        $roleMap = ['readonly' => 'reader', 'editor' => 'writer', 'commentator' => 'commenter'];
        $role = $roleMap[strtolower((string) $level)] ?? 'writer';

        // Initialize Google Client
        $clientSecretPath = $this->setConfig();
        $client = new Google_Client();
        $client->setAuthConfig($clientSecretPath);
        $client->addScope(Google_Service_Drive::DRIVE);

        if ($client->isAccessTokenExpired()) {
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
        }

        $service = new Google_Service_Drive($client);

        try {
            $response = $service->files->get($fileid, ['fields' => '*']);

            $existingPermissionId = null;
            foreach ($response->getPermissions() as $permission) {
                if (env('owner_g_mail') != $permission->emailAddress && $permission->emailAddress == $email) {
                    $existingPermissionId = $permission->getId();
                    break;
                }
            }

            if ($existingPermissionId) {
                $updatedPerm = new Google_Service_Drive_Permission();
                $updatedPerm->setRole($role);
                $service->permissions->update($fileid, $existingPermissionId, $updatedPerm);
            } else {
                $permission = new Google_Service_Drive_Permission([
                    'type' => 'user',
                    'role' => $role,
                    'emailAddress' => $email,
                ]);
                $service->permissions->create($fileid, $permission);
            }
        } catch (\Exception $e) {
            return 'Error permission file changes: ' . $e->getMessage();
        }
    }

    // Helper method to get or create a folder
    private function getOrCreateFolder($service, $folderName, $parentId = null)
    {
        // Construct the query to find the folder
        $query = "mimeType = 'application/vnd.google-apps.folder' and name = '$folderName'";
        if ($parentId) {
            $query .= " and '$parentId' in parents"; // Add parent folder if specified
        } else {
            $query .= " and 'root' in parents"; // Root folder if no parent
        }

        // Check if the folder exists
        $response = $service->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
            'pageSize' => 1
        ]);

        // If it exists, return its ID; otherwise, create it
        if (count($response->files) > 0) {
            return $response->files[0]->id; // Return existing folder ID
        } else {
            // Create the folder if it doesn't exist
            $folderMetadata = new Google_Service_Drive_DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => $parentId ? [$parentId] : [] // Set parent if provided
            ]);

            $folder = $service->files->create($folderMetadata, [
                'fields' => 'id'
            ]);

            return $folder->id; // Return new folder ID
        }
    }
    
    // Get Comments
    public function getComments($fileid)
    {
        // Initialize Google Client
        $clientSecretPath = $this->setConfig();
        $client = new Google_Client();
        $client->setAuthConfig($clientSecretPath);
        $client->addScope(Google_Service_Drive::DRIVE);

        // Check and refresh access token if expired
        if ($client->isAccessTokenExpired()) {
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
        }
        
        $OptParams = array(
            'fields' => '*'
        );        
        
        $service = new Google_Service_Drive($client);
        
        try{
            echo $fileid;
            $response = $service->comments->listComments($fileid, $OptParams);
            echo "TEst somments <pre>";
            print_r($response);
            
        }catch (\Exception $e) {
            return 'Error permission file changes: ' . $e->getMessage(); // Return error message
        }
        
        die;
    }

    public function storeFileBypath($fileName, $id = 0)
    {
        // Prepare the file path and metadata
        $filePath = storage_path('app/templates/' . basename($fileName));
        $fileName = basename($filePath);
        $fileType = mime_content_type($filePath); // Get MIME type from the correct file path

        // Initialize Google Client
        $clientSecretPath = $this->setConfig();
        $client = new Google_Client();
        $client->setAuthConfig($clientSecretPath);
        $client->addScope(Google_Service_Drive::DRIVE);
        $docsService = new Google_Service_Docs($client);

        // Check and refresh access token if expired
        if ($client->isAccessTokenExpired()) {
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN'); // Use environment variable for security
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
        }

        $service = new Google_Service_Drive($client);

        try {
            // Create folder in Google Drive
            $folderMetadata = new Google_Service_Drive_DriveFile([
                'name' => (string)$id, // Ensure ID is treated as a string
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);

            $folder = $service->files->create($folderMetadata, [
                'fields' => 'id' // Get the folder ID
            ]);

            // Create file metadata
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $fileName,
                'parents' => [$folder->id] // Set parent folder ID
            ]);

            // Read file content
            $content = file_get_contents($filePath);

            // Upload the file to Google Drive
            $file = $service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $fileType,
                'uploadType' => 'multipart'
            ]);

            // Generate file URL
            $fileId = $file->id;
            $fileUrl = "https://drive.google.com/file/d/{$fileId}/view?usp=sharing";

            // Prepare replacement variables
            $variables = ['p1name' => '']; // Add more variables as needed
            $requests = []; // Initialize requests array for batch update

            // Replace variables in the document
            foreach ($variables as $variable => $value) {
                $requests[] = new Google_Service_Docs_Request([
                    'replaceAllText' => [
                        'containsText' => [
                            'text' => '{{' . $variable . '}}',
                            'matchCase' => true
                        ],
                        'replaceText' => $value
                    ]
                ]);
            }

            // Batch update the document with replaced text
            if (!empty($requests)) {
                $batchUpdateRequest = new Google_Service_Docs_BatchUpdateDocumentRequest([
                    'requests' => $requests
                ]);
                $docsService->documents->batchUpdate($fileId, $batchUpdateRequest);
            }

            return $fileUrl; // Return the generated file URL
        } catch (\Exception $e) {
            return 'Error uploading file: ' . $e->getMessage(); // Catch and return error message
        }
    }
}