<?php

namespace Delgont\Armor\Concerns;

trait RegistersCommands
{
    private function registerCommands() : void
    {
        $this->commands([
            \Delgont\Armor\Console\Commands\GeneratePermissions::class,
            \Delgont\Armor\Console\Commands\MakeAddRoleIdToModelTable::class,
            \Delgont\Armor\Console\Commands\SyncPermission::class,
            \Delgont\Armor\Console\Commands\SyncRole::class,
            \Delgont\Armor\Console\Commands\MakePermissionRegistrar::class,
            \Delgont\Armor\Console\Commands\MakeRoleRegistrar::class,
            \Delgont\Armor\Console\Commands\RoleCacheCommand::class,
            \Delgont\Armor\Console\Commands\GiveAllPermissionsToUser::class,
            \Delgont\Armor\Console\Commands\DenyAllPermissionsFromUser::class,
            \Delgont\Armor\Console\Commands\GiveAllPermissionsToRole::class,
            \Delgont\Armor\Console\Commands\DenyAllPermissionsFromRole::class,
            \Delgont\Armor\Console\Commands\MakeModulePermissionRegistrar::class,
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
            \Delgont\Armor\Console\Commands\MakeSystemActionRegistrar::class,
            

            \Delgont\Armor\Console\Commands\PermissionTree::class,
            \Delgont\Armor\Console\Commands\PermissionStatusCommand::class,
            \Delgont\Armor\Console\Commands\CacheWarmCommand::class,
            \Delgont\Armor\Console\Commands\CacheCheckCommand::class,
            \Delgont\Armor\Console\Commands\DoctorCommand::class,
            \Delgont\Armor\Console\Commands\PermissionsCleanCommand::class,

            \Delgont\Armor\Console\Commands\SyncRolesCommand::class,
            \Delgont\Armor\Console\Commands\RoleStatusCommand::class,
            \Delgont\Armor\Console\Commands\RolesCleanCommand::class,
            \Delgont\Armor\Console\Commands\RolesListCommand::class,
            \Delgont\Armor\Console\Commands\RoleShowCommand::class,
        ]);
    }
}
