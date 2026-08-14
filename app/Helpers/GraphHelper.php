<?php

namespace App\Helpers;

use Microsoft\Graph\Graph;
use Microsoft\Graph\Http;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Graph\Model;
use Microsoft\Graph\Model\DriveItem;
use Microsoft\Graph\Model\Folder;
use Microsoft\Graph\Model\DriveRecipient;
use Microsoft\Graph\DriveItem\Invite\InvitePostRequestBody;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use App\Helpers\Helpers;

class GraphHelper {
    // <UserAuthConfigSnippet>
    private static $tokenClient;
    private static $clientId = '';
    private static $authTenant = '';
    private static $graphUserScopes = '';
    private static $clientSecret = '';
    private static $userClient;
    private static $userToken;

    public static function initializeGraphForUserAuth() {
        GraphHelper::$tokenClient = new Client();
        GraphHelper::$clientId = env('CLIENT_ID_MS');
        GraphHelper::$clientSecret = env('CLIENT_SECRET_MS');
        GraphHelper::$authTenant = env('AUTH_TENANT_MS');
        GraphHelper::$graphUserScopes = env('GRAPH_USER_SCOPES_MS');
        GraphHelper::$userClient = new Graph();
        
        $token = GraphHelper::getUserToken();
        
        if(str_contains(strtolower($token),'error')){
            return redirect('/storage/error');
            exit;
        }
        
        GraphHelper::$userClient->setAccessToken($token);         
    }
    // </UserAuthConfigSnippet>

    // <GetUserTokenSnippet>
    public static function getUserToken(): string {
        // If we already have a user token, just return it
        // Tokens are valid for one hour, after that it needs to be refreshed
        if (isset(GraphHelper::$userToken)) {
            return GraphHelper::$userToken;
        }
        else {
            $newToken = GraphHelper::requestNewAccessToken(env('MSONE_DRIVE_REFRESH_TOKEN'));
            GraphHelper::$userToken = $newToken;
            return GraphHelper::$userToken;
        }

    }
    
    public static function storeFiles($file,$parentId, $fileName=""){
        GraphHelper::initializeGraphForUserAuth();

        try {

            $filePath = $file->getPathname();
            $fileType = $file->getExtension();
            
            if($fileName == ""){
                $fileName = rawurlencode(file_name($file));
            }

            $createFileUrl = "https://graph.microsoft.com/v1.0/me/drive/items/$parentId:/$fileName:/content";
            $requestBody = new DriveItem();
            $requestBody->setName($fileName);

            if($fileType == 'pdf11'){
                // Read the file content
                $content = file_get_contents($filePath);
                $requestBody->setContent($content);                
                $drive = GraphHelper::$userClient->createRequest('PUT',$createFileUrl)->attachBody($requestBody)->execute();
            }else{
                $drive = GraphHelper::$userClient->createRequest('PUT',$createFileUrl)->attachBody($requestBody)->upload($filePath);
            }

            $responseBody = $drive->getBody();

            return $responseBody['id'];
    
        } catch (IdentityProviderException $ex) {
            return false;
        }catch(ConnectException $ex){
            return false;
        }catch (ClientException $ex){
           return false;
        }
    }
    
    
    public static function storeContent($filePath, $parentId, $fileName){
        GraphHelper::initializeGraphForUserAuth();

        try {

            //$checkInFile = "https://graph.microsoft.com/v1.0/me/drive/items/$parentId:/$fileName:/checkin";
            //$checkInFileExe = GraphHelper::$userClient->createRequest('POST',$checkInFile)->execute();
            
            $createFileUrl = "https://graph.microsoft.com/v1.0/me/drive/items/$parentId:/$fileName:/content?@microsoft.graph.conflictBehavior=replace";
            $requestBody = new DriveItem();
            $requestBody->setName($fileName);

            $drive = GraphHelper::$userClient->createRequest('PUT',$createFileUrl)->attachBody($requestBody)->upload($filePath);

            $responseBody = $drive->getBody();

            return $responseBody['id'];
    
        } catch (IdentityProviderException $ex) {
            echo "id issue". $ex->getMessage();
            return false;
        }catch(ConnectException $ex){
            echo "conn issue". $ex->getMessage();
            return false;
        }catch (Exception $ex){
            echo "ex issue". $ex->getMessage();
            return false;
        }
        catch (ClientException $ex){
           echo "clie issue". $ex->getMessage();
           return false;
        }
    }    
    
    /**
     * Replace the content of an existing drive item by its id. OneDrive/SharePoint keeps
     * the prior content as a previous version (change tracking) rather than creating a
     * disconnected new file. Returns the item id on success or false on failure.
     */
    public static function updateContentById($fileId, $localFilePath){
        GraphHelper::initializeGraphForUserAuth();

        try {
            if (empty($fileId) || !is_file($localFilePath)) {
                return false;
            }

            $updateUrl = "https://graph.microsoft.com/v1.0/me/drive/items/$fileId/content";
            $requestBody = new DriveItem();

            $drive = GraphHelper::$userClient->createRequest('PUT', $updateUrl)
                ->attachBody($requestBody)
                ->upload($localFilePath);

            $responseBody = $drive->getBody();
            return $responseBody['id'] ?? $fileId;

        } catch (IdentityProviderException $ex) {
            return false;
        } catch (ConnectException $ex) {
            return false;
        } catch (ClientException $ex) {
            return false;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public static function copyFiles($toFolder, $fromFile, $fileName){
        $folderNotExist = GraphHelper::checkParentFolder($fromFile,$toFolder);
        if($folderNotExist){
            $fileName_ = GraphHelper::copyFilesToFolder($toFolder, $fromFile, $fileName);
            $fileId_ = GraphHelper::getAllFiles($toFolder,$fileName_, false);
            if($fileId_){
                return [$fileName_,$fileId_];
            }
            return $fileId_;
        }
    }

    public static function checkParentFolder($fromFile,$toFolder){
        
        GraphHelper::initializeGraphForUserAuth();
        
        try {
            $createFileUrl = "https://graph.microsoft.com/v1.0/me/drive/items/$fromFile?select=parentReference";

            $drive = GraphHelper::$userClient->createRequest('GET',$createFileUrl)->execute();

            $responseBody = $drive->getBody();
          
            if(isset($responseBody['parentReference']['id']) && $responseBody['parentReference']['id'] != $toFolder){
                
                return true;
            }
    
        } catch (IdentityProviderException $ex) {
            echo "folder issue". $ex->getMessage();
            return false;
        }catch(ConnectException $ex){
            echo "folder issue". $ex->getMessage();
            return false;
        }
    }
    
    // Get File Url
    public static function getFileUrl($fileID, $accessType="view", $email="", $download=false, $forceEdit=false){

        GraphHelper::initializeGraphForUserAuth();


        $loggedEmail = Helpers::userInfo()->email ?? "";

        $curmaildomain = substr($loggedEmail, strpos($loggedEmail, '@') + 1);

        $selfdomains = [env('selfmaildomain')];

        //Get File URL

        $type = new \stdClass();

        $postType = "POST";
        if(!$download){
            $accessType = GraphHelper::setOrChangePermission($fileID, $loggedEmail, "", true);
            // Force an editable share link when requested (OneDrive versions the same item).
            if($forceEdit){
                $accessType = "edit";
            }
            $selfDomain = false;
            
            if(in_array($curmaildomain, $selfdomains)){
                $searchFolderURL = "https://graph.microsoft.com/v1.0/me/drive/items/$fileID?select=webUrl";
                $selfDomain = true;
            }else{
                $searchFolderURL = "https://graph.microsoft.com/v1.0/me/drive/items/$fileID/createLink";
                if($accessType == "edit"){
                    $type->type = 'edit';
                }else if($accessType ==  "view"){
                    $type->type = 'view';   
                }else{
                    return url("invalidfile");
                }
                $type->scope = "users";
            }
        }else{
            $searchFolderURL = "https://graph.microsoft.com/v1.0/me/drive/items/$fileID/?select=webUrl,@microsoft.graph.downloadUrl";
            $postType = "POST";
        }
    
        
        try{
            $driveItemResp = GraphHelper::$userClient->createRequest($postType,$searchFolderURL)->attachBody($type)->execute();
            $responseBody = $driveItemResp->getBody();
            if(!$download){
                if($selfDomain){
                    return $responseBody['webUrl'];
                }else{
                   return $responseBody['link']['webUrl']; 
                }
            }else{
                $client = new \GuzzleHttp\Client();
                
                $response = $client->request('GET', $responseBody['@microsoft.graph.downloadUrl']);
                
                $contentDocx = $response->getBody(); 
                
                return $contentDocx;
            }

        }
        catch (Exception $ex){
            return url("invalidfile");
        }
        catch (ClientException $ex){
           return url("invalidfile");
        }
        catch (IdentityProviderException $ex){
           return url("invalidfile");
        }
        catch(ConnectException $ex){
            return url("invalidfile");
        }
    }
    
    //Retieve Files 
    public static function getAllFiles($tofolder, $fileSearch, $onlyList=true){
        
        GraphHelper::initializeGraphForUserAuth();
        
        try {
            if(!empty($tofolder)){
                $searchFileUrl = "https://graph.microsoft.com/v1.0/me/drive/items/$tofolder/children?select=name,id,webUrl,parentReference";
    
                $driveSearch = GraphHelper::$userClient->createRequest('GET',$searchFileUrl)->execute();
    
                $responseBodySearch = $driveSearch->getBody();
                
                if(!$onlyList){
                    foreach($responseBodySearch['value'] as $filesAvl){
                        if($filesAvl['name'] == $fileSearch){
                            return $filesAvl['id'];
                        }
                    }
                }
            }
            return false;
            
        } catch (IdentityProviderException $ex) {
            echo "folder issue". $ex->getMessage();
            return false;
        }catch(ConnectException $ex){
            echo "folder issue". $ex->getMessage();
            return false;
        }            
    }
    
        //Copy Files 
    public static function getFileIdByName($tofolder,$fileNameFinal){
        
        GraphHelper::initializeGraphForUserAuth();
        
        try {        

            $searchFileUrl = "https://graph.microsoft.com/v1.0/me/drive/root/search(q='$fileNameFinal')?select=name,id";

            $driveSearch = GraphHelper::$userClient->createRequest('GET',$searchFileUrl)->execute();

            $responseBodySearch = $driveSearch->getBody();

            //return [$responseBodySearch['value'][0]['id'],$fileNameFinal]; 
            // print_r($responseBodySearch);
            // die;
            
        } catch (IdentityProviderException $ex) {
            echo "folder issue". $ex->getMessage();
            return false;
        }catch(ConnectException $ex){
            echo "folder issue". $ex->getMessage();
            return false;
        }            
    }
    
    // Get Driver Details
    public static function getOrCreateFolder($folderName, $parentId=""){
        
        GraphHelper::initializeGraphForUserAuth();

        
        //Check Parent Folder Exists
        if($parentId == ""){
            $searchFolderURL = "https://graph.microsoft.com/v1.0/me/drive/root/search(q='$folderName')?select=name,id,webUrl,parentReference";
        }else{
            $searchFolderURL = "https://graph.microsoft.com/v1.0/me/drive/items/$parentId/search(q='$folderName')?select=name,id,webUrl,parentReference";
        }
        
        // echo $searchFolderURL;
        
        // //die;
        
        try{
        
            $driveItemID = GraphHelper::$userClient->createRequest('GET',$searchFolderURL)->execute();
            
            if(isset($driveItemID->getBody()['value']) && count($driveItemID->getBody()['value']) > 0){
                foreach($driveItemID->getBody()['value'] as $driveFolder){
                    if($driveFolder['name'] == $folderName &&  $parentId != "" && $driveFolder['parentReference']['id'] == $parentId){
                        return $driveFolder['id'];
                    }
                    
                    if($driveFolder['name'] == $folderName){
                        return $driveFolder['id'];
                    }
                }
            }
            // echo $parentId."----><pre>".$folderName;
            
            // print_r($driveItemID->getBody()['value']);
            // die;
            //doesnt satisfy any condition above new folder will be created
            if($parentId != ""){
                $createFolderUrl = "https://graph.microsoft.com/v1.0/me/drive/items/$parentId/children";
            }else{
                $createFolderUrl = "https://graph.microsoft.com/v1.0/me/drive/root/children";
            }
            
            $requestBody = new DriveItem();
            $requestBody->setName($folderName);
            $folder = new \stdClass();
            $requestBody->setFolder($folder);
    
            try{
                $drive = GraphHelper::$userClient->createRequest('POST',$createFolderUrl)->attachBody($requestBody)->execute();
                return $drive->getBody()['id'];   
            }
            catch (Exception $ex){
                //echo "Folder Name Issue 1: ".$folderName;
                //print_r($ex->getMessage());
                //die;
                return false;
            }
            catch (ClientException $ex){
                //echo "Folder Name Issue 2: ".$folderName;
                // print_r($ex->getMessage());
                // die;
                return false;
            }
            catch (IdentityProviderException $ex){
                //echo "Folder Name Issue 3: ".$folderName;
                //print_r($ex->getMessage());
                //die;
                return false;
            }catch(ConnectException $ex){
                //echo "Folder Name Issue: ";
                return false;
            }        
        
        }catch (Exception $ex){
            //echo "Folder Name Issue 1: ".$folderName;
            //print_r($ex->getMessage());
            //die;
            return false;
        }
        catch (ClientException $ex){
            //echo "Folder Name Issue 2: ".$folderName;
            // print_r($ex->getMessage());
            // die;
            return false;
        }
        catch (IdentityProviderException $ex){
            //echo "Folder Name Issue 3: ".$folderName;
            //print_r($ex->getMessage());
            //die;
            return false;
        }catch(ConnectException $ex){
            //echo "Folder Name Issue: ";
            return false;
        }
        

    }
    
    public static function copyFilesToFolder($toFolder, $fromFile, $fileName){

        GraphHelper::initializeGraphForUserAuth();
        
        try {

            $createFileUrl = "https://graph.microsoft.com/v1.0/me/drive/items/$fromFile/copy";
            
            $requestBody = new \StdClass();
            
            $requestBody->parentReference = new \StdClass();
            
            $requestBody->parentReference->id = $toFolder;
            
            $fileNameFinal = now()->timestamp."_copy_".$fileName;
            
            $requestBody->name = $fileNameFinal;
    
            $drive = GraphHelper::$userClient->createRequest('POST',$createFileUrl)->attachBody($requestBody)->execute();
            
            return $fileNameFinal;

            
        } catch (IdentityProviderException $ex) {
            echo "folder issue". $ex->getMessage();
            die;
        }catch(ConnectException $ex){
            echo "folder issue". $ex->getMessage();
            die;
        }
    }

    public static function requestNewAccessToken($refreshToken){
        if (!isset(GraphHelper::$userClient)) {
            return;
        }

        // https://docs.microsoft.com/azure/active-directory/develop/v2-oauth2-client-creds-grant-flow
        $tokenRequestUrl = 'https://login.microsoftonline.com/'.GraphHelper::$authTenant.'/oauth2/v2.0/token';


        try{
            // POST to the /token endpoint
            $tokenResponse = GraphHelper::$tokenClient->post($tokenRequestUrl, [
                'form_params' => [
                    'client_id' => GraphHelper::$clientId,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                    'client_secret' => GraphHelper::$clientSecret,
                    'scope' =>  GraphHelper::$graphUserScopes
                ],
                // These options are needed to enable getting
                // the response body from a 4xx response
                'http_errors' => false,
                'curl' => [
                    CURLOPT_FAILONERROR => false
                ]
            ]);
    
            if ($tokenResponse->getStatusCode() == 200) {
                // Return the access_token
                //global $conn;
                $responseBody = json_decode($tokenResponse->getBody()->getContents());
    
                return $responseBody->access_token;
    
            } else if ($tokenResponse->getStatusCode() == 400) {
                // Check the error in the response body
                $responseBody = json_decode($tokenResponse->getBody()->getContents());
                return "Error";
                if (isset($responseBody->error)) {
                    $error = $responseBody->error;
                    var_dump($responseBody);
                    return $error;
                }
            }
        }catch(ConnectException $ex){
            echo "Access Token Issue ";
        }  
    }
    
    /**
     * Invite a single recipient to a drive item with a specific role ("read" or
     * "write"). Thin wrapper around the Graph invite endpoint used to apply a
     * per-group cloud-file permission; existing permissions for other users are
     * left untouched (unlike setOrChangePermission).
     */
    public static function inviteRole($fileid, $email, $role = "read"){
        if (empty($fileid) || empty($email)) {
            return;
        }

        GraphHelper::initializeGraphForUserAuth();

        $role = ($role === "write") ? "write" : "read";

        try {
            $recipient = new DriveRecipient();
            $recipient->setEmail($email);

            $body = array(
                "recipients" => [$recipient],
                "requireSignIn" => true,
                "sendInvitation" => env('SEND_INVITE_MS'),
                "roles" => [$role],
                "message" => ($role === "write" ? "Edit" : "Read only") . " access enabled for you",
            );

            $inviteUrl = "https://graph.microsoft.com/v1.0/me/drive/items/$fileid/invite";
            GraphHelper::$userClient->createRequest("POST", $inviteUrl)
                ->attachBody($body)
                ->execute();
        }
        catch (Exception $ex){
            return "invalid".$ex->getMessage();
        }
        catch (ClientException $ex){
            return "invalid".$ex->getMessage();
        }
        catch (IdentityProviderException $ex){
            return "invalid".$ex->getMessage();
        }catch(ConnectException $ex){
            return "invalid".$ex->getMessage();
        }
    }

    public static function setOrChangePermission($fileid, $prev_email = "", $current_email ="", $checkpermission = false){

        GraphHelper::initializeGraphForUserAuth();
        
        $newPermission = true;
        
        //?select=id,roles,grantedToIdentities
        $getPermissionUrl = "https://graph.microsoft.com/v1.0/me/drive/items/$fileid/permissions?select=id,roles,grantedToIdentities,grantedTo";
        
        try{
            $driveItemPermissions = GraphHelper::$userClient->createRequest("GET", $getPermissionUrl)->execute();
            
            $allPermissions = $driveItemPermissions->getBody()['value'];
            
            $readPersons = [];
            $writePersons = []; 
            $readEmailExist = "";
            $writeEmailExist = "";
            
            $viewType = 'view';
            if($prev_email != ""){
                if(!is_array($prev_email)){
                   $readPersons[] = $prev_email; 
                }else{
                    $readPersons = $prev_email;
                }
            }
            
            if($current_email != ""){
                if($current_email != "" && !is_array($current_email)){
                   $writePersons[] = $current_email; 
                }else{
                    $writePersons = $current_email;
                }
            }
            
            // echo "<pre>";
            // var_dump($current_email);
            // var_dump($writePersons);
            // die;
            
            if(count($allPermissions) > 0){
                foreach ($allPermissions as $PermissionValue) {
                $grantPermIdentityUserArr = [];
                if(isset($PermissionValue["grantedToIdentities"])){
                    $grantPermIdentityUserArr = $PermissionValue["grantedToIdentities"];
                }elseif(isset($PermissionValue["grantedTo"])){
                    $grantPermIdentityUserArr = [$PermissionValue["grantedTo"]];
                }
                $permId = $PermissionValue["id"];
                $permRole = $PermissionValue['roles'][0];
                if(count($grantPermIdentityUserArr) > 0){
                    if($permRole != 'owner'){
                        foreach($grantPermIdentityUserArr as $users){
                            if(isset($users['user']['email'])){
                                if($permRole == 'read'){
                                    if(in_array(strtolower($users['user']['email']),$writePersons)){
                                        $writeEmailExist = $current_email;
                                    }else if(in_array(strtolower($users['user']['email']), $readPersons)){
                                        $readEmailExist = $prev_email;
                                    }else{
                                        $readPersons[] = $users['user']['email'];
                                    }
                                    if($checkpermission && $users['user']['email'] == $prev_email){
                                        $newPermission = false;
                                    }
                                }
                                if($permRole == 'write'){
                                    if(in_array(strtolower($users['user']['email']), $readPersons)){
                                        $readEmailExist = $prev_email;
                                    }else if(in_array(strtolower($users['user']['email']),$writePersons)){
                                        $writeEmailExist = $current_email;
                                        //$readPersons[] = $users['user']['email'];
                                    }else{
                                        $readPersons[] = $users['user']['email'];
                                    }

                                    if($checkpermission && $users['user']['email'] == $prev_email){
                                        $newPermission = false;
                                        $viewType = 'edit';
                                    }                                    
                                }
                            }
                        }
                    }else{
                        foreach($grantPermIdentityUserArr as $users){
                            if(in_array(strtolower($users['user']['email']), $readPersons)){
                                return "view";
                            }
                        }
                    }
                }
                    
                // echo "<pre>";
                // var_dump($readPersons);
                // var_dump($writePersons);
                // var_dump($checkpermission);
                // var_dump($newPermission);
                // die;
                if(!$checkpermission && $newPermission){
                    $deletePermissionUrl = "https://graph.microsoft.com/v1.0/me/drive/items/$fileid/permissions/$permId";
            		$driveItemPermissions = GraphHelper::$userClient->createRequest("DELETE", $deletePermissionUrl)->execute();
                }else if($newPermission && $checkpermission){
                    
                    $readPersons = [];
                    $writePersons = [];
                    
                    $readPersons[] = $prev_email;
                }else if(!$newPermission && $checkpermission){
                    $readPersons = [];
                    $writePersons = [];                        
                }
            }
            }
    
            if(count($readPersons) > 0){
                    
                    $recipientsArr = [];
                    foreach($readPersons as $mail){
                    	$recipient = new DriveRecipient();
                    	$recipient->setEmail($mail);
                    	$recipientsArr[] = $recipient;
                    }
            
                	$roles = ["read"];
    
                	$body = array(
                		"recipients" => $recipientsArr, 
                		"requireSignIn" => true,
                		"sendInvitation" => env('SEND_INVITE_MS'),
                		"roles" => $roles, 
                		"message" => "Read only access enabled for you");
            
                    $getPermissionUrl = "https://graph.microsoft.com/v1.0/me/drive/items/$fileid/invite";
                	$inviteCollection = GraphHelper::$userClient->createRequest("POST", $getPermissionUrl)
                							          ->attachBody($body)
                							          ->execute();
                }
                
            if(count($writePersons) > 0){
                    
                    $recipientsArr = [];
                    foreach($writePersons as $mail){
                    	$recipient = new DriveRecipient();
                    	$recipient->setEmail($mail);
                    	
                    	$recipientsArr[] = $recipient;
                    }
            
                	$roles = ["write"];
    
                	$body = array(
                		"recipients" => $recipientsArr, 
                		"requireSignIn" => true,
                		"sendInvitation" => env('SEND_INVITE_MS'),
                		"roles" => $roles, 
                		"message" => "Write access enabled for you");
            
                    $getPermissionUrl = "https://graph.microsoft.com/v1.0/me/drive/items/$fileid/invite";
                	$inviteCollection = GraphHelper::$userClient->createRequest("POST", $getPermissionUrl)
                							          ->attachBody($body)
                							          ->execute();
                }
               
            if($checkpermission){
                return $viewType;
            } 
        }
        catch (Exception $ex){
            return "invalid".$ex->getMessage();
        }
        catch (ClientException $ex){
            return "invalid".$ex->getMessage();
        }
        catch (IdentityProviderException $ex){
            return "invalid".$ex->getMessage();
        }catch(ConnectException $ex){
            return "invalid".$ex->getMessage();
        }

    }
}
?>
