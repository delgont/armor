<?php
namespace Delgont\Armor\Console\Commands;


use Illuminate\Console\Command;

use Delgont\Armor\Models\Role;
use Delgont\Armor\Models\Permission;

class DenyAllPermissionsFromRole extends Command
{
    protected $signature = 'armor:permissions:deny-all-from-role {roleId}';
    protected $description = 'Give all permissions to the specified role by id';

    public function handle()
    {
        // Retrieve the role by name
        $roleId = $this->argument('roleId');
        $role = Role::whereId($roleId)->first();

        if (!$role) {
            $this->error('Role not found: ' . $roleName);
            return 1; // Return error code
        }

        // Get all permissions
        $permissions = Permission::all();

        // Assign all permissions to the role
        $role->withdrawPermissionsTo($permissions);

        $this->info('All permissions have been denied to the role ' . $role->name . ' successfully.');

        return 0; // Return success code
    }
}
