<?php

namespace Delgont\Armor\Concerns;

/**
 * Commands
 */

use Delgont\Armor\Console\Commands\GeneratePermissions;
use Delgont\Armor\Console\Commands\MakeUserType;
use Delgont\Armor\Console\Commands\MakeAddRoleIdToModelTable;
use Delgont\Armor\Console\Commands\SyncPermission;
use Delgont\Armor\Console\Commands\SyncRole;

use Delgont\Armor\Console\Commands\MakePermissionRegistrar;
use Delgont\Armor\Console\Commands\MakeRoleRegistrar;
use Delgont\Armor\Console\Commands\RoleCacheCommand;


trait RegistersCommands
{
    private function registerCommands() : void
    {
        $this->commands([
            GeneratePermissions::class,
            MakeUserType::class,
            MakeAddRoleIdToModelTable::class,
            SyncPermission::class,
            SyncRole::class,
            MakePermissionRegistrar::class,
            MakeRoleRegistrar::class,
            RoleCacheCommand::class
        ]);
    }
}