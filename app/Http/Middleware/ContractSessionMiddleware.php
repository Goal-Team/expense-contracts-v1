<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Closure;

use App\Models\GeographicalHierarchy;
use App\Models\UserCredentials;
use App\Models\AddUsers;

use App\Http\Controllers\Controller;
use App\Helpers\Helpers;

use Storage;

class ContractSessionMiddleware
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next)
  {
    // Locale is enabled and allowed to be change
        if(!session()->has('contractSession')){
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            
            if(isset($_SESSION['authtoken']) && isset($_SESSION['username'])){
                session()->put('contractUserToken', $_SESSION['authtoken']);
                session()->put('contractSession', true);
                session()->put('contractSessionUser', $_SESSION['username']);
                session()->put('contractSessionUserRole', $_SESSION['logrole']);
                session()->put('contractSessionEntity', $_SESSION['entityid'] ?? env('default_entity_id'));
                session()->put('contractSessionUserAccessType', $_SESSION['AccessType'] ?? null);
                //session()->save();
            }
        }
        if(!env('default_entity_id') && session()->has('contractSession')){
            return $next($request);
        }        
    //For Maintenance    
    if(env('enable_maintenance') &&  (strpos(session()->get('contractSessionUser'), 'legalitysimplified.com') == false)){
        return redirect('/upgrade');
    }
    if(session()->has('contractSession')){
        $authtoken = session()->get('contractUserToken'); 
        
        //Check All Required Tables Installed
        $allSet = (new Controller)->checkTablesConfiguration();

        if($allSet !== true){
            return redirect('/misconfig')->with('misconfig',$allSet);
        }        

        // Same row, same four decrypted columns, as Helpers::getEntityBranches() reads. One
        // read per request now, shared through Helpers::authTokenUser().
        $checkUserCredentials = Helpers::authTokenUser($authtoken);
        if($checkUserCredentials){
            
            $username = $checkUserCredentials->username;
            
            $add_users = AddUsers::select('id',  decrypt_data('AccessScope', 'AddUsers'))
                        ->where(decrypt_datas('UserName', 'AddUsers'), $username)
                        ->where('Customer', session()->get('contractSessionEntity'))
                        ->first();
            if($add_users){
                $haystack = $add_users->AccessScope;
                $needle = "Contracts";
                $pos = strpos($haystack, $needle);

                if ($pos !== false || $username == 'admin@legalitysimplified.com') {
                    
                    session()->put('contractUserId', $add_users->id);
                    //session()->save();
                    //Create Custom Temp Folder
                    $tempFolderPath = "contracts/tempDocs";
                    if (!Storage::exists($tempFolderPath)) {
            
                        Storage::makeDirectory($tempFolderPath);
                    }                    
                    //Check All Required Tables Installed
                    $allSet = (new Controller)->checkTablesConfiguration();
                    
                    $storageAvl = (new Controller)->storageAvailableCheck();
                    
                    if(!$storageAvl){
                        return redirect('/storage/error');
                    }
                    
                    
                    if($allSet === true){
                        return $next($request);
                    }else{
                        return redirect('/misconfig')->with('misconfig',$allSet);
                    }
                }
            }
        }else{
            session()->invalidate();
            return redirect()->away(env('authMainUrl'));
        }         
    }else{
        return redirect()->away(env('authMainUrl'));
    }
    
    if(!session()->has('contractSession')){
        return redirect()->away(env('authMainUrl'));
    }else{
        return redirect('/noaccess');
    }
  }
}