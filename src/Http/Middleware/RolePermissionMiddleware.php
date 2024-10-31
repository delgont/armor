<?php

namespace Delgont\Armor\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Delgont\Armor\Exceptions\UnauthorizedException;

use Delgont\Armor\Models\Role;

class RolePermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $permission)
    {

        $authenticated = app('auth');

        // Check if the user is authenticated
        if ($authenticated->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $user = $authenticated->user();

        // Check if the user has a single role assigned to him
        if (!$user->hasRole($user->role_id)) {
            throw UnauthorizedException::forNoRoleAssigned();
        }

        // Normalize permissions into an array
        $permissions = is_array($permission) ? $permission : explode(config('armor.permission_delimiter', '|'), $permission);

        // Check if User Role has any of the required permissions
        $role = Role::whereId($user->role_id)->first();

        $hasPermission = $role->hasAnyPermission(...$permissions);

        if (!$hasPermission) {
            throw UnauthorizedException::forPermissions($permissions);
        }

        return $next($request);
    }
}
