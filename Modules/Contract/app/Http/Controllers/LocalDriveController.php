<?php



namespace Modules\Contract\Http\Controllers;



use App\Http\Controllers\Controller;



use Google_Client;

use Google_Exception;

use Google_Service_Drive;

use Google_Service_Drive_DriveFile;

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

use Storage;



class LocalDriveController extends Controller

{

    private $client;



    public function __construct()

    {

      if(Controller::checkCurrentAuth("Contracts") != 1){
          return abort('404');
      }

    }



    public function storeFile($file, $id = 0, $cid = 0, $fileName="")

    {

        

         // Get file details

        // $fileName = $file->getClientOriginalName();
        if($fileName == ""){
            $fileName = file_name($file);
        }
        
        if($cid > 0){
            $fileType = $file->getMimeType();
    
            $contracts = Contract::where('id', $cid)->first();
            
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
            
            $contractType = ContractType::where('contract_type_id',  $contracts->contract_type)->pluck('contract_type')->first();
    
            $folderPath = 'contracts/'. $internal_first_location . "/" . $contractType . '/' . $cid;
            
            //Logic For Status Based Folder
            
            $statusFolderArray = config('app.APPROVAL_FOLDER_STRCTURE');
            
            $extraFolder = $statusFolderArray[strtolower($contracts->contract_status)] ?? '';
            
            if($extraFolder != ""){
                $folderPath .= '/'.$extraFolder;
            }
        }else{
            $folderPath = 'contracts/others/forms/utaking';
        }

        if (!Storage::exists($folderPath)) {

            Storage::makeDirectory($folderPath);
        }


        $storedFilePath = $file->storeAs($folderPath, $fileName, 'local');



        return $storedFilePath;

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

        $folderPath = 'contracts/'. $internal_first_location . "/" . $contractType . '/' . $cid;
        
        //Logic For Status Based Folder
        
        $statusFolderArray = config('app.APPROVAL_FOLDER_STRCTURE');
        
        $extraFolder = $statusFolderArray[strtolower($contracts->contract_status)] ?? '';
        
        if($extraFolder != ""){
            $folderPath .= '/'.$extraFolder;
        }

        if (!Storage::exists($folderPath)) {

            Storage::makeDirectory($folderPath);
            $copiedFileName = $contracts->contract_status."_".$contracts->contract_attachment_filename;
            $storedFilePath = Storage::copy($copyFile, "$folderPath/$copiedFileName");
            return [$copiedFileName,"$folderPath/$copiedFileName"];
        }


        return [];


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

        $folderPath = 'contracts/'. $internal_first_location . "/" . $contractType . '/' . $cid;
        
        //Logic For Status Based Folder
        
        $statusFolderArray = config('app.APPROVAL_FOLDER_STRCTURE');
        
        $extraFolder = $statusFolderArray[strtolower($contracts->contract_status)] ?? '';
        
        if($extraFolder != ""){
            $folderPath .= '/'.$extraFolder;
        }

        if (!Storage::exists($folderPath)) {

            Storage::makeDirectory($folderPath);
        }


        return $folderPath;        
    }


    public function changePermission($fileid, $prev_email="", $current_email="")
    {
        //return true;
    }

    // Local storage has no per-user sharing model; no-op to satisfy the common interface.
    public function setFilePermission($fileid, $email, $level = 'editor')
    {
        //return true;
    }

    // Overwrite the existing local file at $fileId (its storage-relative path) in place,
    // keeping the same path so the attachment reference is unchanged.
    public function updateFileContent($fileId, $localFilePath)
    {
        if (empty($fileId) || !is_file($localFilePath)) {
            return false;
        }
        Storage::disk('local')->put($fileId, file_get_contents($localFilePath));
        return $fileId;
    }
    
    // Get Comments
    public function getComments($fileid)
    {
        
    }
 

}
