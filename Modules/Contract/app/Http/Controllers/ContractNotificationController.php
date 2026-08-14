<?php

namespace Modules\Contract\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


use DateTime;
use Carbon\Carbon;
use LaravelFileViewer;
use stdClass;

use App\Models\AddUsers;
use App\Models\ApprovalContracts;
use App\Models\Branch;
use App\Models\BranchUser;
use App\Models\GeographicalHierarchy;
use App\Models\Category;
use App\Models\ContractParties;
use App\Models\ContractType;
use App\Models\CustomFields;
use App\Models\CustomFieldsHistory;
use App\Models\CustomFieldsTimeline;
use App\Models\Contract;
use App\Models\ContractHistory;
use App\Models\CustomFieldsData;
use App\Models\ContractPartyData;
use App\Models\ContractPartyDataHistory;
use App\Models\ContractCategories;
use App\Models\EntityBusiness;
use App\Models\OtpActions;
use App\Models\ExternalTempUser;
use App\Models\Country;
use App\Models\ContractPartiesLabel;
use App\Models\State;
use App\Models\ContractsStatus;
use App\Models\FinancialLimit;
use App\Models\FlowActivity;
use App\Models\Tasks;

use App\Helpers\Helpers;
use App\Mail\MyMail;
use App\Http\Controllers\Controller;
use Modules\Contract\Http\Controllers\GoogleDriveController;
use Modules\Contract\Http\Controllers\LocalDriveController;


class ContractNotificationController extends Controller
{

    public function __construct()
    {
        if (Controller::checkCurrentAuth("Contracts") != 1) {
            return abort('404');
        }
    }


    public function sendEmail($id, $desc, $shortDesc, $toUser, $appDataStatus, $filename, $fileurl, $mailType="notiMail")
    {

        if(admin_setting('enablemails')){

            if($id != ""){

                $contract =  Contract::where(['id' => $id])->first();
                
                $viewurl = [];
                
                $viewpath = false;
                
                $attachUrl = [];
                
                if(isset($fileurl) && count($fileurl) > 0){
                    foreach($fileurl as $fyle){
                        // A caller may pass an already-downloaded local file path (e.g. the
                        // negotiation email attaches the actual document, not just a link).
                        if (is_string($fyle) && $fyle !== '' && @is_file($fyle)) {
                            $attachUrl[] = $fyle;
                            continue;
                        }
                        $viewurlFile = fileViewUrl($fyle);
                        $viewurl[] = $viewurlFile;
                         if (preg_match('/app\/.*/', $viewurlFile, $matches)) {
                            $result = $matches[0];
                            $attachUrl[] = storage_path($result);
                        }
                    }

                    $viewpath = true;
                }
            
                if (fileStorageType() == 'Google' ||  fileStorageType() == 'Microsoft') {
                      //$viewurl = [];
                      $viewpath = true;
                 }
        
                $details = [
                    'contractname' =>  $contract->contract_unique_id,
                    'contractid' => $id,
                    'contraclink' => url("/contracts/$id?tab=timeline"),
                    'shortDesc' => $shortDesc,
                    'appDataStatus' => $appDataStatus,
                    'path'=> $viewurl,
                    'pathLink'=> $viewpath,
                    'fileName'=> $filename,
                    'mode'=>fileStorageType(),
                    'message' =>  $desc
                ];
                
                
                switch ($mailType) {
                    case 'newContract':
                        
                        $bccEmails = [];
                        $ccEmails = [];
                        
                        //For Owner
                        if($contract->signatory){
                            $bccEmails = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->where('id', $contract->signatory)->pluck('Email')->toArray();            
                        }
                        
                        $approvers = json_decode($contract->rules_id);
                        
                        $approversArr = [];
                        // if(isset($approvers[0]) && $approvers[0]->approver){
                        //     foreach(json_decode($approvers[0]->approver) as $appr){
                        //         $approversArr[] = $appr->id;
                        //     }
                        // }
                        
                        
                        // //For Getting Notifiers List
                        // if(isset($approvers[0]) && $approvers[0]->signatory){
                        //     $additionalNotifiers = json_decode($approvers[0]->signatory);
                            
                        //     $finalNotifiers = $additionalNotifiers->notify;
                        
                        //     $approversArr = array_merge($approversArr, $finalNotifiers);
                            
                        //     // Remove duplicate values
                        //     $approversArr = array_unique($approversArr);

                        // }
                        
                        //For Approvers
                        if(count($approversArr) > 0){
                            $ccEmails = AddUsers::select('id',  decrypt_data('Salutation', 'AddUsers'),decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->whereIn('id', $approversArr)->pluck('Email')->toArray();            
                        }
                        
                        $details['contraclink'] = url("/contracts/$id");
                        
                        $toUser_ = env('email_on_test');
                        if($toUser_ == ""){
                            $toUser_ = $toUser;
                        }                        
                        
                        $approvalSubject = $this->buildApprovalSubject($contract, $toUser, $shortDesc, $appDataStatus, $mailType);

                        Mail::to($toUser_)
                        ->cc($ccEmails)
                        ->bcc($bccEmails)            
                        ->send(new MyMail($details, $approvalSubject, $attachUrl, $mailType));
                
                        break;
                    case 'statusChange':
                        $toUser_ = env('email_on_test');
                        if($toUser_ == ""){
                            $toUser_ = $toUser;
                        }
                        echo "Mail Send TO ".$toUser_;
                        Mail::to($toUser_)->bcc('admin@legalitysimplified.com')->send(new MyMail($details, 'Contract Status Change Alert', '', $mailType));
                        break;
                    case 'OTPSign':
                        $currentOTP = random_int(100000, 999999);
                        $checkOTPExist = OtpActions::create([
                            'otp_number' => $currentOTP,
                            'otp_ref' => $id,
                            'otp_type' => 'signing'
                        ]);
                        $details['otp_number'] = $currentOTP;
                        $toUser_ = env('email_on_test');
                        if($toUser_ == ""){
                            $toUser_ = $toUser;
                        } 
                        //$toUser_ = $toUser;
                        return Mail::to($toUser_)->send(new MyMail($details, 'OTP Request For Signing', $attachUrl, $mailType));
                        break;
                    case 'notiMail':
                        $toUser_ = env('email_on_test');
                        if($toUser_ == ""){
                            $toUser_ = $toUser;
                        } 
                        //$toUser_ = $toUser;
                        $approvalSubject = $this->buildApprovalSubject($contract, $toUser, $shortDesc, $appDataStatus, $mailType);
                        Mail::to($toUser_)->send(new MyMail($details, $approvalSubject, $attachUrl, $mailType));
                        break;
                    case 'externalApproval':
                        // $toUser_ = env('email_on_test');
                        // if($toUser_ == ""){
                        //     $toUser_ = $toUser;
                        // }
                        $toUser_ = $toUser['email'];
                        //echo "Mail Send TO ".$toUser_;
                        $encryptId = encryptString("$id||$toUser_", 'externalApproval');
                        $pass = encryptString("$id||$toUser_", 'externalApproval');
                        $today = strtotime(date('Y-m-d'));
                        $finalDate = date("Y-m-d", strtotime("+1 month", $today));
                        $tfaPass = generateRandomChar(5);
                        ExternalTempUser::create([
                            'contract_id' => $id,
                            'accessSlug' => $encryptId,
                            'email' => $toUser_,
                            'name' => $toUser['name'],
                            'accessExpiryDate' => $finalDate,
                            'password' => $tfaPass,
                            '2FA' => env('enable_2fa_external_sign') ? 1 : 0
                        ]);                        
                        $details['externalLink'] = url("/contracts/external/approval/$encryptId");
                        $details['username'] = $toUser_;
                        $details['passw'] = $tfaPass;
                        $details['emailExTrackLink'] = url("contracts/external/emailDeliveryStatus/$encryptId");
                        $approvalSubject = $this->buildApprovalSubject($contract, $toUser, $shortDesc, $appDataStatus, $mailType);
                        return Mail::to($toUser_)->send(new MyMail($details, $approvalSubject, $attachUrl, $mailType));
                        break;                        
                    case 'reSendexternalApproval':
                        $toUser_ = $toUser;

                        $encryptId = $desc->accessSlug;
                        $pass = $desc->password;

                        $details['externalLink'] = url("/contracts/external/approval/$encryptId");
                        $details['username'] = $toUser_;
                        $details['passw'] = $pass;
                        //$details['emailExTrackLink'] = url("contracts/external/emailDeliveryStatus/$encryptId");
                        $approvalSubject = $this->buildApprovalSubject($contract, $toUser, $shortDesc, $appDataStatus, $mailType);
                        return Mail::to($toUser_)->send(new MyMail($details, $approvalSubject, $attachUrl, 'externalApproval'));
                        break;
                    case 'negotiationApproval':
                        $toUser_ = $toUser['email'];
                        $encryptId = encryptString("$id||$toUser_", 'negotiationApproval');
                        $today = strtotime(date('Y-m-d'));
                        $finalDate = date("Y-m-d", strtotime("+7 days", $today));
                        ExternalTempUser::create([
                            'contract_id' => $id,
                            'accessSlug' => $encryptId,
                            'email' => $toUser_,
                            'name' => $toUser['name'],
                            'accessExpiryDate' => $finalDate,
                            'password' => '',
                            'is_active' => 1,
                            'access_type' => 'negotiation',
                            '2FA' => 0
                        ]);
                        $details['externalLink'] = url("/contracts/negotiation/$encryptId");
                        $details['emailExTrackLink'] = url("contracts/external/emailDeliveryStatus/$encryptId");
                        $approvalSubject = $this->buildApprovalSubject($contract, $toUser, $shortDesc, $appDataStatus, $mailType);
                        return Mail::to($toUser_)->send(new MyMail($details, $approvalSubject, $attachUrl, $mailType));
                        break;
                }

            }else{
                if($mailType == 'reminder'){
                    foreach($appDataStatus as $key => $mailData){
                        $toUser = env('email_on_test');
                        if($toUser == ""){
                            $toUser = $key;
                        }
                        $details['mailData'] = $mailData;
                        Mail::to($toUser)->bcc('admin@legalitysimplified.com')->send(new MyMail($details, 'Contract Reminder For Owner and Reviewer', '', $mailType));              
                    }
                }
                
                if($mailType == 'storageExpired'){
                    $toUser = env('email_on_test');
                    if($toUser == ""){
                        $toUser = env('support_mail');
                    }
                    $details = $appDataStatus;
                    Mail::to($toUser)->bcc('admin@legalitysimplified.com')->send(new MyMail($details, 'Token Expiry Notification – Action Required', '', 'storageExpired'));              
                }
            }
        }
    }

    protected function buildApprovalSubject($contract, $toUser, $shortDesc, $appDataStatus, $mailType = 'notiMail'): string
    {
        $contractCode = (string) ($contract->contract_unique_id ?: $contract->id ?: 'NA');
        $firstName = $this->extractFirstName($toUser, $shortDesc);

        switch ($mailType) {
            case 'notiMail':
                $subjectLabel = 'Approval Update';
                break;
            case 'newContract':
                $subjectLabel = 'New Contract Alert';
                break;
            case 'externalApproval':
            case 'reSendexternalApproval':
                $subjectLabel = 'External Approval Request';
                break;
            case 'negotiationApproval':
                $subjectLabel = 'Contract Review & Negotiation Request';
                break;
            case 'statusChange':
                $subjectLabel = 'Contract Status Change Alert';
                break;
            case 'OTPSign':
                $subjectLabel = 'OTP Request For Signing';
                break;
            case 'reminder':
                $subjectLabel = 'Contract Reminder For Owner and Reviewer';
                break;
            case 'storageExpired':
                $subjectLabel = 'Token Expiry Notification - Action Required';
                break;
            default:
                $subjectLabel = 'Contract Notification';
                break;
        }

        return '[Contract ' . $contractCode . '] ' . $subjectLabel . ' - ' . $firstName;
    }

    protected function extractFirstName($toUser, $fallback = ''): string
    {
        if (is_array($toUser) && !empty($toUser['name'])) {
            return $this->firstToken($toUser['name']);
        }

        if (is_object($toUser) && !empty($toUser->name)) {
            return $this->firstToken($toUser->name);
        }

        if (is_string($toUser) && strpos($toUser, '@') !== false) {
            return $this->firstToken(str_replace(['.', '_', '-'], ' ', strstr($toUser, '@', true)));
        }

        if (!empty($fallback) && is_string($fallback)) {
            return $this->firstToken($fallback);
        }

        $user = Helpers::userInfo();
        if (!empty($user->first_name)) {
            return $this->firstToken($user->first_name);
        }

        if (!empty($user->name)) {
            return $this->firstToken($user->name);
        }

        return 'Approver';
    }

    protected function firstToken(string $value): string
    {
        $parts = preg_split('/\s+/', trim($value));
        return !empty($parts[0]) ? ucfirst($parts[0]) : 'Approver';
    }
}
