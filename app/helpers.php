<?php

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

use App\Models\Country;
use App\Models\State;

use App\Models\CustomFieldsData;

use App\Models\FileStorage;

use App\Models\Contract; 

use Modules\Contract\Http\Controllers\GoogleDriveController;
use Modules\Contract\Http\Controllers\LocalDriveController;
use Modules\Contract\Http\Controllers\MicrosoftDriveController;
use Modules\Contract\Http\Controllers\ContractImportController;

use App\Models\AddUsers;

use App\Helpers\GraphHelper;

use App\Helpers\Helpers;

use Carbon\Carbon;


if (!function_exists('getUser')) {
    function getUser($id, $data=false)
    {
        $add_users = AddUsers::select('id',  decrypt_data('FirstName', 'AddUsers'),  decrypt_data('LastName', 'AddUsers'))
            ->where('id', $id)
            ->first();
        if($data){
            return $add_users;
        }
        return ($add_users->FirstName ?? 'User') . ' ' . ($add_users->LastName ?? 'Inactive/Deleted');
    }
}




if (!function_exists('fileViewUrl')) {
    function fileViewUrl($url, $restrict = false, $edit = false)
    {
        if (fileStorageType() == 'Google') {
            $fileController = new GoogleDriveController();
            $fileUrl = $fileController->getFileUrl($url, $edit);
            if (strpos(strtolower($fileUrl), 'error') !== false) {
                return url('invalidfile');
            }
            return $fileUrl;
        }
        if (fileStorageType() == 'Local') {
            if(!Storage::exists($url)) {
                return url('invalidfile');
            }
            if($restrict)
                return url('contractdocs/' . $url);
            else{
                return asset('storage/app/' . $url);
            }
        }
        if (fileStorageType() == 'Microsoft') {

            return GraphHelper::getFileUrl($url, 'view', '', false, $edit);
        }
        // return $controller;
    }
}

if (!function_exists('attachmentDummyUrl')) {
    function attachmentDummyUrl($url, $restrict = false)
    {
        if (fileStorageType() == 'Local') {
            if(!Storage::exists($url)) {
                return url('invalidfile');
            }            
            if($restrict)
                return url('contractdocs/' . $url);
            else{
                return asset('storage/app/' . $url);
            }
        }else{
    
            return url('contractfiles/' . $url);
        }
    }
}

if (!function_exists('fileStorageTypeController')) {
    function fileStorageTypeController()
    {
        if (fileStorageType() == 'Google') {
            $controller = new GoogleDriveController();
        }
        if (fileStorageType() == 'Local') {
            $controller = new LocalDriveController();
        }
        if (fileStorageType() == 'Microsoft') {
            $controller = new MicrosoftDriveController();
        }
        return $controller;
    }
}

if (!function_exists('fileStorageType')) {
    /**
     * Which drive the application stores contract files on.
     *
     * One row, read from many places. fileStorageTypeController() alone asks three times, and
     * the contract detail page asked nine times in one load. The row cannot change while a
     * request runs, so the answer is held for the life of the request. A null answer - no
     * row - is a real answer, so the test is array_key_exists and not isset.
     */
    function fileStorageType()
    {
        static $type = [];

        if (! array_key_exists(0, $type)) {
            $type[0] = FileStorage::where('id', 1)->value('type');
        }

        return $type[0];
    }
}

if (!function_exists('contractStatusKey')) {
    /**
     * Map a raw contract_status to the user-facing key used for status counts and
     * filters. "Pre-Approval" is an internal/technical status - a contract holding it
     * is sitting in the Review stage - so it is grouped under 'review' rather than
     * exposed as its own bucket.
     */
    function contractStatusKey($status)
    {
        $key = strtolower(trim((string) $status));

        if ($key === 'pre-approval' || $key === 'preapproval' || $key === 'pre approval') {
            return 'review';
        }

        return $key;
    }
}






if (!function_exists('encryptString')) {
    function encryptString($string, $key)
    {
        $finalKey = Config::get('app.APP_ENCRYPTION_KEY');

        $newEncrypter = new \Illuminate\Encryption\Encrypter($finalKey, 'AES-128-CBC');

        return $newEncrypter->encrypt($string);
                
    }
}


if (!function_exists('encryptStringx')) {
    /**
     * encryptString() with one exception: a column named in config('app.PLAINTEXT_COLUMNS') is
     * stored as readable text.
     *
     * Added beside encryptString() rather than changing it, so the two can be compared and the
     * old one is only removed once this is proven (CLAUDE.md).
     *
     * $key is 'table.column', not a bare column name. Four tables in this database have an
     * approval_status column - approval_contracts, approval_parties, financial_limit and
     * party_approval_rules - and only the first is meant to be plain, so a bare name would
     * convert the other three by accident. It was always ignored by encryptString(), which is
     * why eight call sites had drifted into passing an email there instead. They pass the
     * qualified column name now, because here it decides something.
     *
     * There is no matching decryptStringx(). decryptString() only decrypts a value starting
     * with 'ey' and returns anything else untouched, so every existing read site already copes
     * with a plain value and with a table that is half converted.
     *
     * See .scratch/contracts-dashboard-perf/issues/17-plain-columns-experiment.md
     */
    function encryptStringx($string, $key)
    {
        $plaintextColumns = (array) Config::get('app.PLAINTEXT_COLUMNS', []);

        if (in_array($key, $plaintextColumns, true)) {
            return $string;
        }

        return encryptString($string, $key);
    }
}


if (!function_exists('decryptString')) {
    function decryptString($string, $key)
    {
 
        $stringcheck = trim($string);

            if (strpos($stringcheck, 'ey') === 0) {

            $finalKey = Config::get('app.APP_ENCRYPTION_KEY');

    
            $newEncrypter = new \Illuminate\Encryption\Encrypter($finalKey, 'AES-128-CBC');
        

                return $newEncrypter->decrypt($stringcheck);
           }
           
           return $string;
 
    }
}

if (!function_exists('file_name')) {
    function file_name($file)
    {
        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->timestamp;
        $newFileName = $fileName . '_timestamp' . $timestamp . '.' . $extension;

        return $newFileName;
    }
}



if (!function_exists('dateImport')) {
    function dateImport($dateString)
    {
        $formats = [
            'd-M-Y',    // e.g., 29-Aug-2024
            'd/m/y',    // e.g., 17/06/24
            'd/m/Y',    // e.g., 05/06/2029
            'd-m-y',    // e.g., 05-06-29
            'd-m-Y',    // e.g., 05-06-2029
        ];
        
        $dateObject = strtotime($dateString);
        // echo date('Y-m-d', $dateObject);
        // die;
        $first_date = date('Y-m-d', $dateObject);
        if($first_date != '1970-01-01'){
            return $first_date;
        }
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            

            if ($date !== false) {
                return $date->format('Y-m-d');
            }

        }

        return $dateString;
    }
}


if (!function_exists('currency')) {
    function currency()
    {
        return array('INR', 'USD', 'EUR');
    }
}


if (!function_exists('currency_formatter')) {
    function currency_formatter($cur, $amount, $showOrg=false)
    {
        if(is_numeric($amount)){
            if(!$showOrg){
                $INR = $amount;
                $ext = "";
                
                $INR_THOUSAND = 1000;
                $INR_LAKH = 100 * $INR_THOUSAND;
                $INR_CRORE = 100 * $INR_LAKH;
                
                if ($amount > $INR_CRORE)
                {
                    $INR = $amount / $INR_CRORE;
                    $ext = $INR == 1 ? "Cr" : "Cr";
                    $INR = number_format(($amount / $INR_CRORE),2) . ' ' . $ext;
                }
                else if ($amount > $INR_LAKH)
                {
                    $INR = $amount / $INR_LAKH;
                    $ext = $INR == 1 ? "Lakh" : "Lakhs";
                    $INR = number_format(($amount / $INR_LAKH),2) . ' ' . $ext;
                }
                else if ($amount > $INR_THOUSAND)
                {
                    $INR = $amount / $INR_THOUSAND;
                    $ext = $INR == 1 ? "Lakh" : "K";
                    $INR = number_format(($amount / $INR_THOUSAND),2) . ' ' . $ext;
                }    
                else
                {
                    $INR = number_format(($amount),2);
                }
            
                return $cur." ".$INR;
            }else{
                $number = $amount;
                if($cur == 'INR'){
                    $decimal = (string)($number - floor($number));
                    $money = floor($number);
                    $length = strlen($money);
                    $delimiter = '';
                    $money = strrev($money);
            
                    for($i=0;$i<$length;$i++){
                        if(( $i==3 || ($i>3 && ($i-1)%2==0) )&& $i!=$length){
                            $delimiter .=',';
                        }
                        $delimiter .=$money[$i];
                    }
            
                    $result = strrev($delimiter);
                    $decimal = preg_replace("/0\./i", ".", $decimal);
                    $decimal = substr($decimal, 0, 3);
            
                    if( $decimal != '0'){
                        $result = $result.$decimal;
                    }
                    
                    $extraDecimal = ".00";
                    if (str_contains($result, '.')){
                        $extraDecimal = "";
                    }
                    $number = $cur." ".$result.$extraDecimal;
                }
        
                return $number;
            }
        }
    }
}

if (!function_exists('amountInINR')) {
    
    function amountInINR($amount)
    {
        $INR = $amount;
        $ext = "";
        
        $INR_THOUSAND = 1000;
        $INR_LAKH = 100 * $INR_THOUSAND;
        $INR_CRORE = 100 * $INR_LAKH;
        
        if ($amount > $INR_CRORE)
        {
            $INR = $amount / $INR_CRORE;
            $ext = $INR == 1 ? "cr" : "cr";
            $INR = ($amount / $INR_CRORE) . ' ' . $ext;
        }
        else if ($amount > $INR_LAKH)
        {
            $INR = $amount / $INR_LAKH;
            $ext = $INR == 1 ? "lakh" : "lakhs";
            $INR = ($amount / $INR_LAKH) . ' ' . $ext;
        }
        else if ($amount > $INR_THOUSAND)
        {
            $INR = $amount / $INR_THOUSAND;
            $ext = $INR == 1 ? "lakh" : "K";
            $INR = ($amount / $INR_THOUSAND) . ' ' . $ext;
        }    
        else
        {
            $INR = ($amount);
        }
    
        return $INR;
    }
}

if (!function_exists('normalizeDate')) {
    function normalizeDate($dateString)
    {
        $targetFormat = 'Y-m-d';
        $date = DateTime::createFromFormat('!Y-m-d|d/m/Y|d-M-Y|d-M-y|Y.m.d|d F Y|m/d/Y|F d, Y|d M Y|Ymd|d-m-Y', $dateString);

        if ($date) {
            return $date->format($targetFormat);
        } else {
            return "Invalid date format: $dateString";
        }
    }
}



if (!function_exists('dataCustomFields')) {
    function dataCustomFields($contractId, $customFieldId)
    {

        $customField = CustomFieldsData::where('custom_field_group_id', $contractId)
            ->where('custom_field_id', $customFieldId)
            ->where('custom_field_group', 'contracts')
            ->latest('id')
            ->first();
        return isset($customField->custom_field_value) ? $customField->custom_field_value : null;
    }
}


if (!function_exists('dataCustomFieldsParty')) {
    function dataCustomFieldsParty($contractId, $customFieldId)
    {
        $customField = CustomFieldsData::where('custom_field_group_id', $contractId)
            ->where('custom_field_id', $customFieldId)
            ->where('custom_field_group', 'parties')
            ->latest('id')
            ->first();


        return isset($customField->custom_field_value) ? $customField->custom_field_value : null;
    }
}

if (!function_exists('decrypt_data')) {
    function decrypt_data($column, $key)
    {

        $ekeycom = Config::get('app.APP_LEGACY_KEY');
        
        return DB::raw("AES_DECRYPT($column, '{$ekeycom}.$key') as $column");
    }
}


if (!function_exists('decrypt_datas')) {
    function decrypt_datas($column, $key)
    {

        $ekeycom = Config::get('app.APP_LEGACY_KEY');
        
        return DB::raw("AES_DECRYPT($column, '{$ekeycom}.$key') ");
    }
}




if (!function_exists('get_country')) {
    /**
     * The country name for an id.
     *
     * Called once per party address, so the same country comes back several times in one page
     * load - three times on the contract detail page. A country name cannot change inside one
     * request, so each id is read once and held.
     */
    function get_country($id)
    {
        static $names = [];

        if ($id > 0) {
            if (! array_key_exists($id, $names)) {
                $country = Country::where('id', $id)->first();
                $names[$id] = $country ? $country->Name : 0;
            }

            return $names[$id];
        }
        return 0;
    }
}


if (!function_exists('get_state')) {
    function get_state($id)
    {
        if ($id > 0) {
            $state = State::where('id', $id)->first();
            return $state ? $state->name : 0;
        }
        return 0;
    }
}

if (!function_exists('getConsultationName')) {
    function getConsultationName($id, $consultations)
        {
            foreach ($consultations as $consult) {
                if ($consult['id'] == $id) {
                    return $consult['name'];
                }
            }
            return 'Unknown Consultation';
        }
}


if (!function_exists('getDriveAccessEmail')) {
    function getDriveAccessEmail($id = null)
    {
        $emails = [];
        $contracts = Contract::where('id', $id)->first();
        $owner = AddUsers::select(decrypt_data('email', 'AddUsers'))
            ->where('id', $contracts->owner)
            ->first();
        $signatory = AddUsers::select(decrypt_data('email', 'AddUsers'))
            ->where('id', $contracts->signatory)
            ->first();
        return $emails;
    }
}

if (!function_exists('get_google_drive_doc_link')) {
    function get_google_drive_doc_link($filename, $filepath, $accessType = "preview", $email="")
    {
        $fileInfo = pathinfo($filename);
        
        $fileExtension = $fileInfo['extension'];
        if (fileStorageType() == 'Google') {
            //For Add Permission those having access on contracts
            $fileUrl = fileStorageTypeController()->changePermission($filepath, Helpers::userInfo()->email ?? '', '', true);

            if (strpos(strtolower($fileUrl), 'error') !== false) {
                return url('invalidfile');
            }
            if( $accessType != "preview"){
                switch ($fileExtension) {
                  case 'docx':
                      return 'https://docs.google.com/document/d/'.$filepath.'/'.$accessType;
                      break;
                  case 'doc':
                      return 'https://docs.google.com/document/d/'.$filepath.'/'.$accessType;
                      break;
                  
                  default:
                    return 'https://drive.google.com/file/d/' . $filepath . '/preview';
                    break;
                }    
            }else{
                return 'https://drive.google.com/file/d/' . $filepath . '/view';
            }
        }
        
        if (fileStorageType() == 'Microsoft') {
            switch ($fileExtension) {
              case 'docx':
                  return GraphHelper::getFileUrl($filepath, $accessType, $email);
                  break;
              case 'doc':
                  return GraphHelper::getFileUrl($filepath, $accessType, $email);
                  break;
              
              default:
                return GraphHelper::getFileUrl($filepath, $accessType, $email);
                break;
            }    
        }
        
        return "";
    }
    
    if (!function_exists('generateRandomChar')) {    
        function generateRandomChar($length = 6) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
        
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[random_int(0, $charactersLength - 1)];
            }
        
            return $randomString;
        }
    }
    
    if (!function_exists('getAlphabet')) {
        function getAlphabet($number) {
            if ($number < 1 || $number > 26) {
                return null; // out of range
            }
            return chr(64 + $number); // 65 = A, 66 = B, ...
        }
    }

    if (!function_exists('getContractDuplicates')) {
        function getContractDuplicates($sheetName,$sheetData) {
            $contractImportController = new ContractImportController();
            return $contractImportController->checkContractDuplicates($sheetName,$sheetData);
        }
    }

    if (!function_exists('admin_setting')) {
        function admin_setting(string $key, $default = null)
        {
            return \App\Models\AdminSettings::getValue($key, $default);
        }
    }
    
    if (!function_exists('reminder_alert_parts')) {
        /**
         * Split a stored reminder value into its three parts.
         *
         * The columns reminder_first_alertMeOn, reminder_second_alertMeOn,
         * reminder_escalation_alertMeOn and reminder_escalation_alertMeOn_after hold three words
         * separated by spaces - '30 days prior'. They are NULL on any contract that never had a
         * reminder set, and the edit form used to explode() the value and read index 1 and 2 with
         * no guard, which threw 'Undefined array key 1' and stopped the whole page rendering.
         *
         * Returns [$day, $unit, $direction]. Missing parts come back as '', 'days' and '', so an
         * empty reminder shows an empty day box with Days selected and neither Prior nor After
         * forced.
         *
         * $column is the column the value came from, passed straight to decryptString() so the
         * call site still says which column it is reading.
         */
        function reminder_alert_parts($storedValue, string $column): array
        {
            $parts = explode(' ', trim((string) decryptString((string) $storedValue, $column)));

            return [
                $parts[0] ?? '',
                ($parts[1] ?? '') !== '' ? $parts[1] : 'days',
                $parts[2] ?? '',
            ];
        }
    }

    if (!function_exists('contract_detail_current_tab')) {
        /**
         * Say which tab the contract detail page opens.
         *
         * One place owns this rule. ContractController::viewContract() calls it to skip the work a
         * tab does not need, and viewDetailContract.blade.php calls it to pick the open tab. The
         * URL carries the tab in ?tab=, and a load with no ?tab= opens Pre-Approval or Timeline.
         *
         * A contract in Pre-Approval cannot open Timeline, and a contract that is not in
         * Pre-Approval cannot open Pre-Approval. The two forcing rules below do that.
         *
         * $contract is the contract row the page shows. It is a Contract or a ContractHistory.
         */
        function contract_detail_current_tab($contract): string
        {
            $isPreApproval = (($contract->contract_status ?? null) === 'Pre-Approval')
                || !empty($contract->preapproval_stage);

            $tab = $_GET['tab'] ?? ($isPreApproval ? 'pre-approval' : 'timeline');

            if ($isPreApproval && $tab === 'timeline') {
                $tab = 'pre-approval';
            }

            if (!$isPreApproval && $tab === 'pre-approval') {
                $tab = 'timeline';
            }

            return $tab;
        }
    }

    if (!function_exists('contract_detail_shows_related_contracts')) {
        /**
         * Say if the open tab renders the Related Contracts region.
         *
         * viewDetailContract.blade.php builds one body block for each tab in the list below. Any
         * other tab value falls through to the last branch, and only that branch holds the four
         * Related Contracts tables - the category table, the parent table, the subsequent table
         * and the shared-party table. The Details tab is the one that reaches it.
         *
         * The controller calls this to decide if it runs the three whole-table scans that fill
         * those tables. The blade calls it on the last branch of the same chain, so the rule
         * lives here and nowhere else.
         */
        function contract_detail_shows_related_contracts(string $currentTab): bool
        {
            $tabsWithOwnBody = [
                'timeline',
                'pre-approval',
                'timelineedit',
                'history',
                'flow',
                'edit',
                'attachment',
                'e-stamp',
                'obligation',
            ];

            return !in_array($currentTab, $tabsWithOwnBody, true);
        }
    }

    if (!function_exists('get_table_data')) {
        function get_table_data(string $key, string $text)
        {
            $modelClass = "\\App\\Models\\{$key}";
        
            if (class_exists($modelClass)) {
                return $modelClass::select('*',decrypt_data($text, 'branch'))->get();
            }
        
            throw new \Exception("Model {$modelClass} not found");
        }
    }    

}
