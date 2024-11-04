<?php

namespace Delgont\Armor\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class UserTypeMiddleware
{ 
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $userType, $guard = null)
    {
        if ($user = auth($guard)->user()) {
            
            $userableTypeNamespace = explode('\\', $user->user_type);

            $types = is_array($userType) ? $userType : explode(config('armor.delimiter', '|'), $userType);

            //Check if the user belongs to any of the user type or user types provided
            if(in_array(Str::lower(end($userableTypeNamespace)), $types)){
                return $next($request);
            }else{
                //check if the user type actually exits
            }
        }
        abort(403);
    }
}
