<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Closure;

class XssMiddleware
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next)
  {
    $userInput = $request->all();
    $rules = [];
    $rules = $this->walk($userInput, $rules);

    if(count($rules) > 0){
      $validator = Validator::make($userInput, $rules[0], $rules[1]);
      return back()->withInput()->withErrors($validator);
    }

    $request->merge($request->all());

    return $next($request);
  }


  public function walk($input, $rules, $existArrKey = ""){
    array_walk($input, function(&$input, $key) use (&$rules, &$existArrKey) {
        
        if(!is_array($input)){
            $bfuserInput = $input;
            $afuserInput = strip_tags($input);
            if(strlen($bfuserInput) != strlen($afuserInput)){
              $finalKey = "\"".$existArrKey.".".$key."\"";
              if(str_contains((strtolower($existArrKey)), 'customfields')){
                $finalKey = "CustomFields";
                $finalKey = $this->keyChecker($rules[0] ?? [],$finalKey);           
              }else{
                $finalKey = $key;
              }              
              $rules[0][$finalKey] = 'required';
              $rules[1][$finalKey] = "Invalid Characters Violation in :attribute";  
            }            
        }else{
          $newKey = $existArrKey != "" ? $existArrKey.".".$key : $key;          
          $rules = $this->walk($input, $rules, $newKey);
        }
    });

    return $rules;
  }

  //For Existing Key Check in Ruleses
  public function keyChecker($arr,$finalKey){
    if(isset($arr[$finalKey])){
      $lastKey = substr($finalKey, -1, 1);
      $remainKey = substr($finalKey, 0, -1);
      $finalKey = $remainKey.(is_numeric($lastKey) ? $lastKey + 1 : $lastKey."1");

      if(isset($arr[$finalKey])){
        $finalKey = $this->keyChecker($arr,$finalKey);
      }
    }else{
      return $finalKey;
    }
    return $finalKey;
  }
}
