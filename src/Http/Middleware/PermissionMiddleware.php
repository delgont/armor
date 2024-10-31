<?php

namespace Delgont\Armor\Http\Middleware;

use Closure;
use Delgont\Armor\Exceptions\UnauthorizedException;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $permission, $guard = null)
    {
        $authenticated = app('auth')->guard($guard);
        $allow = false;

        if ($authenticated->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        // Normalize permissions into an array
        $permissions = is_array($permission) ? $permission : explode(config('armor.permission_delimiter', '|'), $permission);
        
        // Check if user has any of the required permissions
        $user = $authenticated->user();
        $hasPermission = $user->hasAnyPermission(...$permissions);

        if (!$hasPermission) {
            throw UnauthorizedException::forPermissions($permissions);
        }

        return $next($request);

        /** 
        $permissions = is_array($permission)
        ? $permission
        : explode(config('permissions.delimiter', '|'), $permission);

        foreach ($permissions as $permission) {
            if($authenticated->user()->hasPermissionTo($permission)){
                $allow = true;
            }else{
                $allow = false;
                throw UnauthorizedException::forPermissions($permissions);
            }
        }

        if ($allow) {
            return $next($request);
        }

        throw UnauthorizedException::forPermissions($permissions);
        */
        
    }
}
