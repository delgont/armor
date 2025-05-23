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

use Delgont\Armor\Console\Commands\GiveAllPermissionsToUser;
use Delgont\Armor\Console\Commands\DenyAllPermissionsFromUser;

use Delgont\Armor\Console\Commands\GiveAllPermissionsToRole;
use Delgont\Armor\Console\Commands\DenyAllPermissionsFromRole;

use Delgont\Armor\Console\Commands\MakeModulePermissionRegistrar;



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
            RoleCacheCommand::class,
            GiveAllPermissionsToUser::class,
            DenyAllPermissionsFromUser::class,
            GiveAllPermissionsToRole::class,
            DenyAllPermissionsFromRole::class,
            MakeModulePermissionRegistrar::class,
            \Delgont\Armor\Console\Commands\InstallArmorCommand::class,
            \Delgont\Armor\Console\Commands\SynchronizePermissions::class,
            \Delgont\Armor\Console\Commands\PruneAuditLogsCommand::class,
            \Delgont\Armor\Console\Commands\GrantPermissionCommand::class,
            \Delgont\Armor\Console\Commands\GrantPermissionsCommand::class,
            \Delgont\Armor\Console\Commands\ShowPermissionsCommand::class,
            \Delgont\Armor\Console\Commands\ShowRolesCommand::class,
            \Delgont\Armor\Console\Commands\ShowPermissionables::class,
            \Delgont\Armor\Console\Commands\ArmorCachePermissionsCommand::class,
            \Delgont\Armor\Console\Commands\AddRole::class,
            \Delgont\Armor\Console\Commands\ShowPermissionRegistrars::class,
            \Delgont\Armor\Console\Commands\ShowRolePermissions::class,
            \Delgont\Armor\Console\Commands\ShowPermissionGroups::class,
        ]);
    }
}
