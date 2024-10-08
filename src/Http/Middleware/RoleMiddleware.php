<?php

namespace Delgont\Armor\Http\Middleware;


use Closure;
use Illuminate\Support\Facades\Auth;
use Delgont\Armor\Exceptions\UnauthorizedException;

class RoleMiddleware
{
    public function handle($request, Closure $next, $role, $guard = null)
    {
        $authGuard = Auth::guard($guard);

        if ($authGuard->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $roles = is_array($role) ? $role : explode('|', $role);
        
        if (!method_exists($authGuard->user(), 'hasAnyRole')) {
            if (! $authGuard->user()->hasRole($roles)) {
                abort(403, 'Your do not have the right roles to access this page.');
            }
        }else{
            if (! $authGuard->user()->hasAnyRole($roles)) {
                throw UnauthorizedException::forRoles($roles);
            }
        }

        return $next($request);
    }
}
