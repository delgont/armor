<?php
namespace Delgont\Armor\Console\Commands;


use Illuminate\Console\Command;

use Delgont\Armor\Models\Role;
use Delgont\Armor\Models\Permission;

class GiveAllPermissionsToRole extends Command
{
    protected $signature = 'armor:permissions:give-all-to-role {roleId}';
    protected $description = 'Give all permissions to the specified role by id';

    public function handle()
    {
        // Retrieve the role by name
        $roleId = $this->argument('roleId');
        $role = Role::whereId($roleId)->orWhere('name', $roleId)->first();


        if (!$role) {
            $this->error('Role not found: ' . $roleId);
            return 1; // Return error code
        }

        // Get all permissions
        $permissions = Permission::all();

        if (!count($permissions) > 0) {
            # code...
            $this->info('No permissions to sync ....');
            return;
        }


        // Assign all permissions to the role
        $role->givePermissionTo($permissions);

        $this->info('All permissions have been assigned to the role ' . $role->name . ' successfully.');

        return 0; // Return success code
    }
}
