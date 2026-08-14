<?php

namespace App\Http\Controllers;

use DateTime;

use App\Models\Contract; 
use App\Models\ContractHistory;
use App\Models\AddUsers;
use App\Models\ReminderSettings;
use Carbon\Carbon;

use Illuminate\Support\Facades\Storage;

use Modules\Contract\Http\Controllers\ContractNotificationController;

class CronController extends Controller
{
    //Change Contract Status Based On Dates
    public function cronStatusChangeActions(){
        
        $contracts = Contract::select("*")->orderBy('id', 'desc')
        ->where('status', 1)
        ->where('contract_status', 'executed')
        ->where('substatus', 'active')
        ->get();
        echo "<pre>";

        foreach($contracts as $contract){
            $end_date_of_contract = $contract->contract_end_date;
            $cur_date = date("Y-m-d");
            $checkeDate = DateTime::createFromFormat("Y-m-d", $end_date_of_contract);            
            if($checkeDate !== false 
                && strtotime($cur_date) > strtotime($end_date_of_contract) 
                && strtolower($contract->substatus) != 'expired' 
                && strtolower($contract->substatus) != 'renewed' 
                && strtolower($contract->contract_status) == 'executed'
            ){
                $contract_sub_status = 'expired';
                $updateCurrentcon = Contract::where('id', $contract->id)->first();
                $updateCurrentcon->update([
                    'substatus' => $contract_sub_status
                ]);
                
                echo $updateCurrentcon->contract_unique_id."<br/>";
                
                //Take Initiator Email To Push Notification
                $existInitiator = AddUsers::select('id', decrypt_data('Email','AddUsers'), decrypt_data('FirstName', 'AddUsers') , decrypt_data('LastName', 'AddUsers'))->where('id', $contract->owner)->get();
                if($existInitiator){
                    $emailTrigger = new ContractNotificationController();
                    $notifierEmail = $existInitiator[0]->Email ?? false;
                    if($notifierEmail){
                        $MailSent = $emailTrigger->sendEmail($contract->id, '', '', $notifierEmail, 'Expired', '',  [], 'statusChange');
                    }
                }
            }            
        }
    }
    
    //Reminder Cron
    public function cronSendRemiderEmails(){
        
        $contracts = Contract::select("*")->orderBy('id', 'desc')->where('status', 1)->whereIn('substatus', ['active','expired'])->get();
        
        $contractIds = [];
        
        $today = date("d-m-Y");
        
        foreach($contracts as $con){
            $contractIds[] = $con['id'];
        }
        
        $alertCols = [
            'reminder_[alertType]_alert_[after]',
            'reminder_[alertType]_alertMeOn_[after]', 
            'reminder_[alertType]_alert_repeats_[after]', 
        ];
        
        $alertTypes = ['first', 'second', 'escalation', 'escalation|_after'];
        $alertColumns = [];
        
        $finalAlerts = [];
        $alertTypGroup = [];
        
        foreach($alertTypes as $alTyp){
            $befAfter = explode('|', $alTyp);
            $individualAlert = [];
            foreach($alertCols as $alCol){
                $alCol = str_replace('[alertType]', $befAfter[0] , $alCol);
                $alCol = str_replace('_[after]', $befAfter[1] ?? '' , $alCol);
                $finalAlerts[] = $alCol;
            }
            $alertColumns[] = $befAfter[0].($befAfter[1] ?? '');
        }
        
        
       
        $emailReminders = Contract::select("*")->whereIn('id', $contractIds)->get()->map(function ($task) use ($finalAlerts) {
            foreach($finalAlerts as $fiAl){
                $task->$fiAl = decryptString($task->$fiAl, $fiAl);
            }
            $task->reminder_enable = decryptString($task->reminder_enable, 'reminder_enable');
            $task->renewal_type = decryptString($task->renewal_type, 'renewal_type');
          return $task;
        });
 
        $emailReminderSettings =ReminderSettings::where('reminder_severity', 'medium')->get()
        ->map(function ($task) use ($finalAlerts) {
            foreach($finalAlerts as $fiAl){
                $task->$fiAl = decryptString($task->$fiAl, $fiAl);
            }
          return $task;
        }); 
        

        $remaindersFinalEmail = [];
        foreach($emailReminders as $erem){
            $remainderDataArr = [];
            foreach($alertTypes as $key => $alTyp){
                $befAfter = explode('|', $alTyp);
                $alType = $befAfter[0];
                $afterTxt = ($befAfter[1] ?? '');
                $remaindersArr[$alType.$afterTxt] = '0';
                $firstComDate = $erem["contract_end_date"];
                $finalComDate = $erem["contract_end_date"];
                $remDefSettings = $emailReminderSettings[0] ?? false;
                if($remDefSettings){
                    $remRenewalDate = $remDefSettings["reminder_{$alType}_alert{$afterTxt}"];
                    $remAlertmeOn = $remDefSettings["reminder_{$alType}_alertMeOn{$afterTxt}"];
                    $remAlertRepeats = $remDefSettings["reminder_{$alType}_alert_repeats{$afterTxt}"];
                }
                if($erem->reminder_enable == 'off'){
                    $remRenewalDate = $erem["reminder_{$alType}_alert{$afterTxt}"];
                    $remAlertmeOn = $erem["reminder_{$alType}_alertMeOn{$afterTxt}"];
                    $remAlertRepeats = $erem["reminder_{$alType}_alert_repeats{$afterTxt}"];                    
                }
                if($remRenewalDate=='Renewal Date'){
                    if($erem['renewal_type'] == 'automaticrenewal'){
                        if($erem["auto_renewal_date"] && !empty($erem["auto_renewal_date"])){
                            $firstComDate = $erem["auto_renewal_date"];
                        }
                    }else{
                        if($erem["manual_renewal_date"] && !empty($erem["manual_renewal_date"])){
                            $firstComDate = $erem["manual_renewal_date"];
                        }                        
                    }
                }
    
                $fisrtComDays= explode (" ", $remAlertmeOn)[0];
                $fisrtComBeAft= explode (" ", $remAlertmeOn)[2];
                
                $fisrtComRepeat = $remAlertRepeats;
                
                if(!$fisrtComDays || $fisrtComDays == ''){
                     $fisrtComDays = 15;
                }


                if(strlen($firstComDate)>0 && strtotime($firstComDate)>0){

                  if($fisrtComBeAft != 'after'){
                      if(strpos($firstComDate,"/") > -1){
                         $tempfirstComDateArray = explode("/",$firstComDate);
                         $tempfirstComDate=$tempfirstComDateArray[0].'-'.$tempfirstComDateArray[1].'-'.$tempfirstComDateArray[2];
                         $finalComDate= date('d-m-Y', strtotime('-'.$fisrtComDays.' day', strtotime($tempfirstComDate)));
                    
                      }else{
                          $finalComDate= date('d-m-Y', strtotime('-'.$fisrtComDays.' day', strtotime($firstComDate)));
                      }
                  }else{
                     
                      if(strpos($firstComDate,"/") > -1){
                         $tempfirstComDateArray = explode("/",$firstComDate);
                         $tempfirstComDate=$tempfirstComDateArray[0].'-'.$tempfirstComDateArray[1].'-'.$tempfirstComDateArray[2];
                         $finalComDate= date('d-m-Y', strtotime('+'.$fisrtComDays.' day', strtotime($tempfirstComDate)));
                    
                      }else{
                          $finalComDate= date('d-m-Y', strtotime('+'.$fisrtComDays.' day', strtotime($firstComDate)));
                      }
                  }
                   
                  if(strtotime($today) >= strtotime($finalComDate) && strtotime($today) <= strtotime($firstComDate)){
                    if(strtotime($today) == strtotime($firstComDate)){
                            $remaindersArr[$alType.$afterTxt] = 'Today';  
                    }elseif($fisrtComRepeat=='Daily'){
                        $remaindersArr[$alType.$afterTxt] = 'Daily';
                    }elseif($fisrtComRepeat=='Every 3 days'){
                         $diff = strtotime($today) - strtotime($finalComDate);
                         $diffDays=abs(round($diff / 86400)); 
                         if($diffDays % 3 == 0){
                            $remaindersArr[$alType.$afterTxt] = 'Every 3 Days';  
                         }
                    }elseif($fisrtComRepeat=='Weekly'){
                         $diff = strtotime($today) - strtotime($finalComDate);
                         $diffDays=abs(round($diff / 86400)); 
                         if($diffDays % 7 == 0){
                            $remaindersArr[$alType.$afterTxt] = 'weekly';  
                         }
                    }elseif($fisrtComRepeat=='Fortnightly'){
                         $diff = strtotime($today) - strtotime($finalComDate);
                         $diffDays=abs(round($diff / 86400)); 
                         if($diffDays % 14 == 0){
                            $remaindersArr[$alType.$afterTxt] = 'Fortnightly';  
                         }
                    }elseif($fisrtComRepeat=='Monthly'){
                         $diff = strtotime($today) - strtotime($finalComDate);
                         $diffDays=abs(round($diff / 86400)); 
                         if($diffDays % 30 == 0){
                            $remaindersArr[$alType.$afterTxt] = 'Monthly';  
                         }
                    }
                }
                  

                

                }
            }

            $remainderDataArr['contract_number'] = $erem->contract_unique_id;
            $remainderDataArr['start_date'] = $erem->fixed_date;
            $remainderDataArr['end_date'] = $erem->contract_end_date;
            $remainderDataArr['actions'] = url('contracts/'.$erem->id);             
            $remainderDataArr['firstRemain'] = 0;             
            $remainderDataArr['secondRemain'] = 0;             
            $remainderDataArr['escalationRemain'] = 0;             
            $remainderDataArr['escalationRemainAfter'] = 0;             
            $remainderDataArr['ccmails'] = [];    
           
            if($remaindersArr['first'] != 0){
                $userEmail = AddUsers::select('id',decrypt_data('Email', 'AddUsers'))
                        ->where('id', $erem->owner)->first();
                if($userEmail){
			   $remainderDataArr['firstRemain'] = 1;
                   $remaindersFinalEmail[$userEmail->Email][$erem->contract_unique_id] = $remainderDataArr; 
                }
            }
            
            if($remaindersArr['second'] != 0){
                // Send to first approval group only
                $approvers = json_decode($erem->rules_id);
                $emailIds = [];
                
                if(!empty($approvers) && isset($approvers[0]) && !empty($approvers[0]->approver)){
                    $firstGroupApprovers = json_decode($approvers[0]->approver);

                    // If the rule contains a list of approval groups, use the first group's approvers.
                    if(is_array($firstGroupApprovers) && isset($firstGroupApprovers[0]->approvers)){
                        $firstGroupApprovers = $firstGroupApprovers[0]->approvers;
                    }

                    if(is_array($firstGroupApprovers)){
                        foreach($firstGroupApprovers as $appr){
                            if(isset($appr->id) && $appr->id){
                                $emailIds[] = $appr->id;
                            }
                        }
                    }
                    
                    if(!empty($emailIds)){
                        $emailApprovers = AddUsers::select('id',decrypt_data('Email', 'AddUsers'))
                            ->whereIn('id', $emailIds)->get()->pluck('Email')->toArray(); 
                        foreach($emailApprovers as $eml){
                            $remaindersFinalEmail[$eml][$erem->contract_unique_id] = $remainderDataArr;
                        }
                    }
                }                
            }
            
            /*if($remaindersArr['escalation'] != 0){
                $approvers = json_decode($erem->rules_id);
                
                $emailIds = [];
                if(isset($approvers[0]) && $approvers[0]->approver){
                    $approversArr = [];
                    foreach(json_decode($approvers[0]->approver) as $appr){
                        $emailIds[] = $appr->id;
                    }
                }
                
                $finalMergeArray = array_unique(array_merge([$erem->owner, $erem->signatory], $emailIds));
                $ownerSigAppEmails = AddUsers::select('id',decrypt_data('Email', 'AddUsers'))
                        ->whereIn('id', $finalMergeArray)->get()->pluck('Email')->toArray();
                        
                foreach($ownerSigAppEmails as $eml){
                    $remaindersFinalEmail[$eml][$erem->contract_unique_id] = $remainderDataArr;
                }                
                
            }
            
            if($remaindersArr['escalation_after'] != 0){
                $approvers = json_decode($erem->rules_id);
                
                $emailIds = [];
                if(isset($approvers[0]) && $approvers[0]->approver){
                    $approversArr = [];
                    foreach(json_decode($approvers[0]->approver) as $appr){
                        $emailIds[] = $appr->id;
                    }
                }
                
                $branchHeadMails = [];
                
                if($erem->location_branch != '-'){
                    $branchHeadMails = AddUsers::select('id',decrypt_data('Email', 'AddUsers'))
                        ->where(decrypt_datas('Role', 'AddUsers'), 'Branch Head')
                        ->whereIn('branchhead', $erem->location_branch ?? [])->get()->pluck('Email')->toArray();
                }
                
                $finalMergeArray = array_unique(array_merge([$erem->owner, $erem->signatory], $emailIds));
                
                $ownerSigAppEmails = AddUsers::select('id',decrypt_data('Email', 'AddUsers'))
                        ->whereIn('id', $finalMergeArray)->get()->pluck('Email')->toArray();
                $finalMailArray = array_unique(array_merge($ownerSigAppEmails, $branchHeadMails));
                foreach($finalMailArray as $eml){
                    $remaindersFinalEmail[$eml][$erem->contract_unique_id] = $remainderDataArr;
                }              
            }*/
        }
         $emailTrigger = new ContractNotificationController();         

         if(env('enable_contract_reminders')){
            $MailSent = $emailTrigger->sendEmail('', '', '', '', $remaindersFinalEmail, [], [], 'reminder');
         }

         return view('contract::contract.reminderList', compact('remaindersFinalEmail'));
    }  
    
    public function cronStorageTokenExpiryCheck(){
        $goingToExpiry = $this->storageAvailableCheck(80, true);
        $details = [];
        $expiredDays = $goingToExpiry ;
        $details['storage'] = fileStorageType();
        $details['clientName'] = env('APP_NAME');
        
        if($goingToExpiry !== true){

            $expiredMsg = ($goingToExpiry >= 10 ? ' already expired' : ($goingToExpiry < 10  ? 'will expire in '.(10-$expiredDays). ' days' : ''));
            
            $details['expireDays'] = $expiredMsg;
            
            $emailTrigger = new ContractNotificationController();
            
            $MailSent = $emailTrigger->sendEmail('', '', '', '', $details, '',  [], 'storageExpired');
            
            echo $expiredMsg;
        }else{
            echo "Token Not Expired..";
        }
    }
}