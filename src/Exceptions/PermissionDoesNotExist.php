<?php

namespace Delgont\Armor\Exceptions;

use InvalidArgumentException;

class PermissionDoesNotExist extends InvalidArgumentException
{
    public static function create(string $permissionName)
    {
        return new static("There is no permission named `{$permissionName}`");
    }

    public static function withId(int $permissionId, string $guardName = '')
    {
        return new static("There is no [permission] with id `{$permissionId}` for guard `{$guardName}`.");
    }
}
