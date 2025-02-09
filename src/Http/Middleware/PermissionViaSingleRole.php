<?php

namespace Delgont\Armor\Http\Middleware;

use Closure;
use Delgont\Armor\Exceptions\UnauthorizedException;

class PermissionViaSingleRole
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

      $permissions = is_array($permission)
      ? $permission
      : explode(config('armor.permissions_delimiter', '|'), $permission);

      $role = $authenticated->user()->role()->first();

      if ($role) {
        foreach ($permissions as $hello) {
          if (($role->hasPermissionTo($hello))) {
            $allow = true;
          }else{
            $allow = false;
            throw UnauthorizedException::forPermissions($permissions);
          }
        }

        if ($allow) {
          return $next($request);
        }

      }else{
        throw UnauthorizedException::forSingleRole();
      }

       throw UnauthorizedException::forPermissions($permissions);
    }
}
