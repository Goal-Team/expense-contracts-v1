<?php

namespace App\Helpers;

use Config;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Session;

use App\Models\AddUsers;
use App\Models\GeographicalHierarchy;
use App\Models\Branch;
use App\Models\UserCredentials;

class Helpers
{
  public static function appClasses()
  {

    $data = config('custom.custom');


    // default data array
    $DefaultData = [
      'myLayout' => 'vertical',
      'myTheme' => 'theme-default',
      'myStyle' => 'light',
      'myRTLSupport' => true,
      'myRTLMode' => true,
      'hasCustomizer' => true,
      'showDropdownOnHover' => true,
      'displayCustomizer' => true,
      'contentLayout' => 'compact',
      'headerType' => 'fixed',
      'navbarType' => 'fixed',
      'menuFixed' => true,
      'menuCollapsed' => false,
      'footerFixed' => false,
      'customizerControls' => [
      'rtl',
      'style',
      'headerType',
      'contentLayout',
      'layoutCollapsed',
      'showDropdownOnHover',
      'layoutNavbarOptions',
      'themes'
      ],
      //   'defaultLanguage'=>'en',
    ];

    // if any key missing of array from custom.php file it will be merge and set a default value from dataDefault array and store in data variable
    $data = array_merge($DefaultData, $data);

    // All options available in the template
    $allOptions = [
      'myLayout' => ['vertical', 'horizontal', 'blank', 'front'],
      'menuCollapsed' => [true, false],
      'hasCustomizer' => [true, false],
      'showDropdownOnHover' => [true, false],
      'displayCustomizer' => [true, false],
      'contentLayout' => ['compact', 'wide'],
      'headerType' => ['fixed', 'static'],
      'navbarType' => ['fixed', 'static', 'hidden'],
      'myStyle' => ['light', 'dark', 'system'],
      'myTheme' => ['theme-default', 'theme-bordered', 'theme-semi-dark'],
      'myRTLSupport' => [true, false],
      'myRTLMode' => [true, false],
      'menuFixed' => [true, false],
      'footerFixed' => [true, false],
      'customizerControls' => [],
      // 'defaultLanguage'=>array('en'=>'en','fr'=>'fr','de'=>'de','ar'=>'ar'),
    ];

    //if myLayout value empty or not match with default options in custom.php config file then set a default value
    foreach ($allOptions as $key => $value) {
      if (array_key_exists($key, $DefaultData)) {
        if (gettype($DefaultData[$key]) === gettype($data[$key])) {
          // data key should be string
          if (is_string($data[$key])) {
            // data key should not be empty
            if (isset($data[$key]) && $data[$key] !== null) {
              // data key should not be exist inside allOptions array's sub array
              if (!array_key_exists($data[$key], $value)) {
                // ensure that passed value should be match with any of allOptions array value
                $result = array_search($data[$key], $value, 'strict');
                if (empty($result) && $result !== 0) {
                  $data[$key] = $DefaultData[$key];
                }
              }
            } else {
              // if data key not set or
              $data[$key] = $DefaultData[$key];
            }
          }
        } else {
          $data[$key] = $DefaultData[$key];
        }
      }
    }
    $styleVal = $data['myStyle'] == "dark" ? "dark" : "light";
    if(isset($_COOKIE['mode'])){
      if($_COOKIE['mode'] === "system"){
        if(isset($_COOKIE['colorPref'])) {
          $styleVal = Str::lower($_COOKIE['colorPref']);
        }
      }
      else {
        $styleVal = $_COOKIE['mode'];
      }
    }
    isset($_COOKIE['theme']) ? $themeVal = $_COOKIE['theme'] : $themeVal = $data['myTheme'];
    //layout classes
    $layoutClasses = [
      'layout' => $data['myLayout'],
      'theme' => $themeVal,
      'themeOpt' => $data['myTheme'],
      'style' => $styleVal,
      'styleOpt' => $data['myStyle'],
      'rtlSupport' => $data['myRTLSupport'],
      'rtlMode' => $data['myRTLMode'],
      'textDirection' => $data['myRTLMode'],
      'menuCollapsed' => $data['menuCollapsed'],
      'hasCustomizer' => $data['hasCustomizer'],
      'showDropdownOnHover' => $data['showDropdownOnHover'],
      'displayCustomizer' => $data['displayCustomizer'],
      'contentLayout' => $data['contentLayout'],
      'headerType' => $data['headerType'],
      'navbarType' => $data['navbarType'],
      'menuFixed' => $data['menuFixed'],
      'footerFixed' => $data['footerFixed'],
      'customizerControls' => $data['customizerControls'],
    ];

    // sidebar Collapsed
    if ($layoutClasses['menuCollapsed'] == true) {
      $layoutClasses['menuCollapsed'] = 'layout-menu-collapsed';
    }

    // Header Type
    if ($layoutClasses['headerType'] == 'fixed') {
      $layoutClasses['headerType'] = 'layout-menu-fixed';
    }
    // Navbar Type
    if ($layoutClasses['navbarType'] == 'fixed') {
      $layoutClasses['navbarType'] = 'layout-navbar-fixed';
    } elseif ($layoutClasses['navbarType'] == 'static') {
      $layoutClasses['navbarType'] = '';
    } else {
      $layoutClasses['navbarType'] = 'layout-navbar-hidden';
    }

    // Menu Fixed
    if ($layoutClasses['menuFixed'] == true) {
      $layoutClasses['menuFixed'] = 'layout-menu-fixed';
    }


    // Footer Fixed
    if ($layoutClasses['footerFixed'] == true) {
      $layoutClasses['footerFixed'] = 'layout-footer-fixed';
    }

    // RTL Supported template
    if ($layoutClasses['rtlSupport'] == true) {
      $layoutClasses['rtlSupport'] = '/rtl';
    }

    // RTL Layout/Mode
    if ($layoutClasses['rtlMode'] == true) {
      $layoutClasses['rtlMode'] = 'rtl';
      $layoutClasses['textDirection'] = 'rtl';
    } else {
      $layoutClasses['rtlMode'] = 'ltr';
      $layoutClasses['textDirection'] = 'ltr';
    }

    // Show DropdownOnHover for Horizontal Menu
    if ($layoutClasses['showDropdownOnHover'] == true) {
      $layoutClasses['showDropdownOnHover'] = true;
    } else {
      $layoutClasses['showDropdownOnHover'] = false;
    }

    // To hide/show display customizer UI, not js
    if ($layoutClasses['displayCustomizer'] == true) {
      $layoutClasses['displayCustomizer'] = true;
    } else {
      $layoutClasses['displayCustomizer'] = false;
    }

    return $layoutClasses;
  }

  public static function updatePageConfig($pageConfigs)
  {
    $demo = 'custom';
    if (isset($pageConfigs)) {
      if (count($pageConfigs) > 0) {
        foreach ($pageConfigs as $config => $val) {
          Config::set('custom.' . $demo . '.' . $config, $val);
        }
      }
    }
  }
  
  
  
  
  public static function encryptString($string, $key)
  {
      
        $enkey = env('ENCRYPTION_KEY');
        // Generate a random IV
        
          $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        // Encrypt the string
        $encrypted = openssl_encrypt($string, 'aes-256-cbc', $key.$enkey, 0, $iv);
        // Encode the result with the IV appended
        return base64_encode($encrypted . '::' . $iv);
  } 
  
  
   public static function decryptString($string, $key)
    {
        $enkey = env('ENCRYPTION_KEY');
        
        $decoded = base64_decode($string);
        // Check if the decoded string contains the delimiter '::'
        if (strpos($decoded, '::') === false) {
            return null; // Invalid format
        }

        list($encrypted_data, $iv) = explode('::', $decoded, 2);
        // Decrypt the string
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key.$enkey, 0, $iv);
    }
    
    /**
     * The logged-in user's row.
     *
     * 76 call sites ask for it, and the query decrypts UserName in the WHERE, so it reads and
     * decrypts all 1,605 user rows every time. The contract detail page asked six times in one
     * load, and that was the most expensive shape left on the page. The session cannot change
     * inside one request, so the row is read once and held.
     *
     * Nothing in the repo writes through this result - checked for `userInfo()->x =`, ->save(),
     * ->update(), ->fill() - so returning the same model to every caller is safe.
     */
    public static function userInfo()
    {
        $cacheKey = implode('|', [
            (string) session()->get('contractSessionExUser'),
            (string) session()->get('contractSessionUser'),
            (string) session()->get('contractSessionEntity'),
        ]);

        if (array_key_exists($cacheKey, static::$userInfoCache)) {
            return static::$userInfoCache[$cacheKey];
        }

        return static::$userInfoCache[$cacheKey] = static::resolveUserInfo();
    }

    /**
     * The rows already read in this request, keyed by the session values the answer depends on.
     * A false answer - no session user - is a real answer, so reads test array_key_exists.
     */
    protected static array $userInfoCache = [];

    /**
     * Drop the request cache. For tests, and for any code that changes the session user inside
     * one request.
     */
    public static function forgetUserInfo(): void
    {
        static::$userInfoCache = [];
    }

    /**
     * The body of userInfo(). Split out so the cache above has one thing to wrap and every
     * return path below stays exactly as it was.
     */
    protected static function resolveUserInfo()
    {

    if(session()->has('contractSessionExUser') && session()->get('contractSessionExUser')){
        $userObject = new \stdClass();
        $userObject->email = session()->get('contractSessionExUser');
        $userObject->FirstName = session()->get('contractSessionExUser');
        return $userObject;
    }
    
    if (session()->has('contractSessionUser')) {
            $username = session()->get('contractSessionUser');
            $add_users = AddUsers::select('id','AccessLevel', 'branchhead', decrypt_data('email', 'AddUsers'),decrypt_data('FirstName', 'AddUsers'),  decrypt_data('UserName', 'AddUsers'))
                ->where(decrypt_datas('UserName', 'AddUsers'), $username)
                ->where('Customer', session()->get('contractSessionEntity'))
                ->first(); 
            return $add_users;
        } else {
            return false;
        }
      
    }
    
    public static function accessInfo($currentEmail, $onlyAdminRole = true)
    {
          
        $logRole = session()->get('contractSessionUserRole');
        $logUser = session()->get('contractSessionUser');
  
        if ($logRole =='Super Admin' || $logRole =='Admin'  || $logRole=='Branch Head' || strtolower($currentEmail) === strtolower($logUser)) {
                if(strtolower($currentEmail) === strtolower($logUser) && !$onlyAdminRole){
                    return true;
                }else if(strtolower($currentEmail) !== strtolower($logUser) && !$onlyAdminRole){
                    return false;
                }

                return true;
            } else {
                return false;
            }
      
    }
     
    public static function checkAccess($string)
    {
        

// return $_SESSION;
        
        return session('_token');
        return true;
    }
    
    
     public static function getDepartment($string)
    {
        return true;
    }
    
    /**
     * The answers already worked out in this request, keyed by the arguments and by the session
     * values the answer depends on.
     *
     * BranchScope, DepartmentScope and UserBranchScope each call getEntityBranches() every time
     * they build a query, and every call runs the same three or four reads: the UserCredential
     * row for the auth token, the ContractUsers row for that username, then the branch walk. On
     * the contract detail page that came to 41 identical queries in one load.
     *
     * The session cannot change while one request runs, so the answer cannot either. A null
     * entry is a real answer, so reads test array_key_exists and not isset.
     */
    protected static array $entityBranchesCache = [];

    /**
     * Drop the request cache. For tests, and for any code that changes the session user inside
     * one request.
     */
    public static function forgetEntityBranches(): void
    {
        static::$entityBranchesCache = [];
    }

    public static function getEntityBranches($accessLevel='Head Office', $accessDepartment=0){

        $cacheKey = implode('|', [
            $accessLevel,
            $accessDepartment,
            (string) session()->get('contractUserToken'),
            (string) session()->get('contractSessionEntity'),
            (string) session()->get('contractSessionUserRole'),
        ]);

        if (array_key_exists($cacheKey, static::$entityBranchesCache)) {
            return static::$entityBranchesCache[$cacheKey];
        }

        return static::$entityBranchesCache[$cacheKey] = static::resolveEntityBranches($accessLevel, $accessDepartment);
    }

    /**
     * The rows already read for an auth token in this request.
     */
    protected static array $authTokenUserCache = [];

    /**
     * The UserCredential row for an auth token, read once per token per request.
     *
     * Two places asked for it with the same four decrypted columns - here and
     * ContractSessionMiddleware - and getEntityBranches() is called with two different argument
     * pairs, so the contract detail page read it three times in one load. The row cannot change
     * inside one request. A null answer is a real answer, so the test is array_key_exists.
     */
    public static function authTokenUser($authtoken)
    {
        $key = (string) $authtoken;

        if (! array_key_exists($key, static::$authTokenUserCache)) {
            static::$authTokenUserCache[$key] = UserCredentials::select('id', decrypt_data('username', 'UserCredential'), decrypt_data('name', 'UserCredential'), decrypt_data('Salutation', 'UserCredential'), decrypt_data('issuper', 'UserCredential'))
                ->where('authtoken', $authtoken)
                ->first();
        }

        return static::$authTokenUserCache[$key];
    }

    /**
     * The rows already read for a username in this request.
     */
    protected static array $accessUserCache = [];

    /**
     * The AddUsers row that carries a username's access level, branch head and business
     * functions, read once per username per request.
     *
     * getEntityBranches() is called with two different argument pairs, and each call read this
     * row again. The query decrypts UserName inside the WHERE, so it reads and decrypts all
     * 1,605 user rows every time.
     */
    public static function accessUser($username)
    {
        $key = (string) $username;

        if (! array_key_exists($key, static::$accessUserCache)) {
            static::$accessUserCache[$key] = AddUsers::select('id', 'AccessLevel', 'branchhead', decrypt_data('email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'), decrypt_data('UserName', 'AddUsers'), decrypt_data('BusinessFunctionAccess', 'AddUsers'))
                ->where(decrypt_datas('UserName', 'AddUsers'), $username)
                ->where('Customer', session()->get('contractSessionEntity'))
                ->first();
        }

        return static::$accessUserCache[$key];
    }

    /**
     * The body of getEntityBranches(). Split out so the cache above has one thing to wrap and
     * every return path below stays exactly as it was.
     */
    protected static function resolveEntityBranches($accessLevel='Head Office', $accessDepartment=0){

        if(session()->get('contractUserToken')){

            $authtoken = session()->get('contractUserToken');
            $userLogRole = session()->get('contractSessionUserRole');

            $checkUserCredentials = static::authTokenUser($authtoken);

            if($checkUserCredentials){

              $username = $checkUserCredentials->username;
            
              // Same row for both argument pairs this method is called with, and the query
              // decrypts UserName in the WHERE, so it reads every user row. Read once per
              // username per request.
              $add_users = static::accessUser($username);


              if($add_users){
                  
                  //If Super Admin User
                  if($checkUserCredentials->issuper == 'yes' || $userLogRole =='Super Admin' || $userLogRole =='Admin'){
                      return [];
                  }

                  if($accessDepartment == 1){
                      if($add_users->BusinessFunctionAccess){
                          $deserializeBF = unserialize($add_users->BusinessFunctionAccess);
                          if(count($deserializeBF) > 0){
                              return $deserializeBF;
                          }
                      }
                      
                      return [];
                  }
                  
                  //If access level 1 get all region or extra/ else pass only branches
                  if($add_users->AccessLevel){
                      $accessLevel = $add_users->AccessLevel;
                  }elseif($add_users->branchhead){
                    return explode(",",$add_users->branchhead);
                  }else{

                      return [];
                  }
              
                  $entityid = session()->get('contractSessionEntity');
                
                  $entityBranch = explode(",",$accessLevel);
                  

                  
                  $branchesList = NULL;
                  $parentIdNotFound = array();
              
              
                      foreach($entityBranch as $ent){
              
                      if($ent != ''){
                        //Get All Child Geographs
                          $getGeoGraphyLists = "SELECT GROUP_CONCAT(lv SEPARATOR ',') as gpList FROM (
                          SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',') FROM GeographicalHierarchy 
                          WHERE FIND_IN_SET(parent, @pv)) AS lv FROM GeographicalHierarchy 
                          JOIN
                          (SELECT @pv:=".$ent.") tmp
                          ) a";
                          
                        $branchesListColl = DB::select($getGeoGraphyLists);
                
                        
                          foreach ($branchesListColl as $brLiCo) {
                          
                              if($brLiCo->gpList != "" && $brLiCo->gpList !== NULL){
                                $branchesList .= $brLiCo->gpList;
                              }else{
              
                                //$parentIdNotFound[] = $ent;
                                array_push($parentIdNotFound, $ent);
                              
                              }
                          }           
                      }
                          
                      }
              

                      $retrievedBrOptions = explode(",", $branchesList) ?? [];
              
                      $finalHierarchyList = array_merge($parentIdNotFound,$retrievedBrOptions);
              
                      $allBranchesList = GeographicalHierarchy::select('id','type','name')->whereIn('id',$finalHierarchyList)->where('entityid', session()->get('contractSessionEntity'))->pluck('id');
                      $finalBranchesList = Branch::select('id')->whereIn('city', $allBranchesList)->whereIn('Cluster', $allBranchesList, 'or')->pluck('id');

                      return $finalBranchesList;            
              }
            }
            
        }

        return [];

    }
    
    public static function arraySearchPartial($keyword, $arr) {
        foreach($arr as $index => $string) {
            if (strpos($string, $keyword) !== FALSE)
                return $string;
        }
        return false;
    }
    
     public static function getDocumentDisplaySection($docUrl)
    {
        $elemAlert = '<div class="alert alert-danger mx-2 mt-2">If, Due to security reasons, you are not seeing the agreement below? <a href="' . $docUrl . '" target="blank">Click here </a>to access the document.</div><iframe src="' . $docUrl . '" height="500" width="100%"></iframe>';
        return $elemAlert;
    }    
}