<?php

namespace Modules\Contract\Http\Controllers;

use App\Http\Controllers\Controller;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Models\Contract;
use Exception;
use App\Models\ContractType;
use App\Models\ContractPartyData;
use App\Models\BranchUser;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Abstractions\ApiException;
use Microsoft\Kiota\Authentication\Oauth\AuthorizationCodeContext;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAuthenticationProvider;
use App\Helpers\GraphHelper;

class MicrosoftDriveController extends Controller
{
    private $client;

    public function __construct()
    {
        if (Controller::checkCurrentAuth("Contracts") != 1) {
            return abort('404');
        }
    }
    
    public function storeFile($file, $id=0, $cid=0, $fileName=""){
        
        
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

        // Get or create the main Location Folder
        $mainFolderId = GraphHelper::getOrCreateFolder('contracts');
        
        if($cid > 0){
            // Get or create the main Location Folder
            $locationFolderId = GraphHelper::getOrCreateFolder($internal_first_location, $mainFolderId);
            
            //Create Contract Type Folder
            $folderId = GraphHelper::getOrCreateFolder($contractType, $locationFolderId);
    
            // Get or create the subfolder using the contract id
            $subfolderId = GraphHelper::getOrCreateFolder((string)$cid, $folderId);
            
            $statusFolderArray = config('app.APPROVAL_FOLDER_STRCTURE');
            
            $extraFolder = $statusFolderArray[strtolower($contracts->contract_status)] ?? '';
            
            if($extraFolder != ""){
                // Get or create the subfolder using the contract status
                $subfolderId = GraphHelper::getOrCreateFolder($extraFolder, $subfolderId);
            }
        }else{
            //others/forms/utaking
            
            $locationfolderId = GraphHelper::getOrCreateFolder( 'others', $mainFolderId);
            
            $folderId = GraphHelper::getOrCreateFolder( 'forms', $locationfolderId);                
            
            $subfolderId = GraphHelper::getOrCreateFolder( 'utaking', $folderId);                
        }

        return GraphHelper::storeFiles($file, $subfolderId, $fileName);
    }
    
    public function copyFile($cid,$copyFile){
        
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

        // Get or create the main Location Folder
        $mainFolderId = GraphHelper::getOrCreateFolder('contracts');
        
        // Get or create the main Location Folder
        $locationFolderId = GraphHelper::getOrCreateFolder($internal_first_location, $mainFolderId);
        
        //Create Contract Type Folder
        $folderId = GraphHelper::getOrCreateFolder($contractType, $locationFolderId);

        // Get or create the subfolder using the contract id
        $subfolderId = GraphHelper::getOrCreateFolder((string)$cid, $folderId);
        
        $statusFolderArray = config('app.APPROVAL_FOLDER_STRCTURE');
        
        $extraFolder = $statusFolderArray[strtolower($contracts->contract_status)] ?? '';
        
        if($extraFolder != ""){
            // Get or create the subfolder using the contract status
            $subfolderId = GraphHelper::getOrCreateFolder($extraFolder, $subfolderId);
        }         

        return GraphHelper::copyFiles($subfolderId, $copyFile, $contracts->contract_attachment_filename);
    }
    
    public function storeContent($filePath, $parentId, $fileName){
        return GraphHelper::storeContent($filePath, $parentId, $fileName);
    }

    /**
     * Replace the CONTENT of an existing drive item (same item id) so OneDrive keeps it
     * as a new version of the same file, preserving version history / change tracking.
     * Returns the item id on success or an 'Error...' string on failure.
     */
    public function updateFileContent($fileId, $localFilePath){
        return GraphHelper::updateContentById($fileId, $localFilePath);
    }
    
    public function get_file_path($cid){
        
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

        // Get or create the main Location Folder
        $mainFolderId = GraphHelper::getOrCreateFolder('contracts');
        
        // Get or create the main Location Folder
        $locationFolderId = GraphHelper::getOrCreateFolder($internal_first_location, $mainFolderId);
        
        //Create Contract Type Folder
        $folderId = GraphHelper::getOrCreateFolder($contractType, $locationFolderId);

        // Get or create the subfolder using the contract id
        $subfolderId = GraphHelper::getOrCreateFolder((string)$cid, $folderId);
        
        $statusFolderArray = config('app.APPROVAL_FOLDER_STRCTURE');
        
        $extraFolder = $statusFolderArray[strtolower($contracts->contract_status)] ?? '';
        
        if($extraFolder != ""){
            // Get or create the subfolder using the contract status
            $subfolderId = GraphHelper::getOrCreateFolder($extraFolder, $subfolderId);
        }         

        return $subfolderId;
    }    
    
    public function getOrCreateFolder($folderName, $parentId=""){
        return GraphHelper::getOrCreateFolder($folderName, $parentId);
    }

    public function getFileId($fileName){
        return GraphHelper::getFileIdByName($fileName);
    }

    public function downloadUrl($url, $file_name=""){
        return GraphHelper::getFileUrl($url, 'view', '', true);
    }
    
    public function changePermission($fileid="", $prev_email="", $current_email="")
    {
        GraphHelper::setOrChangePermission($fileid, $prev_email, $current_email);
    }

    /**
     * Set a single user's cloud-file permission to a specific level.
     * Microsoft Graph invite roles are limited to read/write, so 'commentator'
     * falls back to read-only (no native comment sharing role on OneDrive).
     * Level maps: readonly->read, editor->write, commentator->read.
     */
    public function setFilePermission($fileid, $email, $level = 'editor')
    {
        $email = trim((string) $email);
        if (empty($fileid) || empty($email)) {
            return;
        }
        $role = strtolower((string) $level) === 'editor' ? 'write' : 'read';
        GraphHelper::inviteRole($fileid, $email, $role);
    }

}



