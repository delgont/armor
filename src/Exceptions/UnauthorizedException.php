<?php

namespace Delgont\Armor\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedException extends HttpException
{
  
    public static function forPermissions(array $permissions): self
    {
        $message = 'You do not have the right permissions to access this resource.';

        if (config('permission.display_permission_in_exception', true)) {
            $permStr = implode(', ', $permissions);
            $message = $message.' Necessary permissions are '.$permStr;
        }

        $exception = new static(403, $message, null, []);
        $exception->requiredPermissions = $permissions;

        return $exception;
    }

    public static function notLoggedIn(): self
    {
        return new static(403, 'User is not logged in.', null, []);
    }

    private $requiredRoles = [];

    private $requiredPermissions = [];

    public static function forRoles(array $roles): self
    {
        $message = 'User does not have the right roles.';

        if (config('permission.display_permission_in_exception')) {
            $permStr = implode(', ', $roles);
            $message = 'User does not have the right roles. Necessary roles are '.$permStr;
        }

        $exception = new static(403, $message, null, []);
        $exception->requiredRoles = $roles;

        return $exception;
    }

    public static function forSingleRole(): self
    {
        $message = 'User does not have the right role.';

        $exception = new static(403, $message, null, []);
        return $exception;
    }

    public static function forNoRoleAssigned(): self
    {
        $message = 'User does not have the right role to access this resource, no role assigned';

        $exception = new static(403, $message, null, []);
        return $exception;
    }



    public static function forRolesOrPermissions(array $rolesOrPermissions): self
    {
        $message = 'User does not have any of the necessary access rights.';

        if (config('permission.display_permission_in_exception') && config('permission.display_role_in_exception')) {
            $permStr = implode(', ', $rolesOrPermissions);
            $message = 'User does not have the right permissions. Necessary permissions are '.$permStr;
        }

        $exception = new static(403, $message, null, []);
        $exception->requiredPermissions = $rolesOrPermissions;

        return $exception;
    }

}
