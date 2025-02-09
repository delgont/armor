<?php

namespace Delgont\Armor\Console\Commands;


use Illuminate\Console\Command;

use Delgont\Armor\Models\Permission;
use Delgont\Armor\Armor;


class GiveAllPermissionsToUser extends Command
{
    protected $signature = 'armor:permissions:give-all-to {userId} {modelKey : The shorthand key or fully qualified class name of the model (e.g., "user" or App\\User)}';
    protected $description = 'Give all permissions to the specified user by ID and model';

    public function handle()
    {
        $modelKey = $this->argument('modelKey');


        // Resolve the model class using the Armor utility
        $modelClass = Armor::resolvePermissionable($modelKey);

        if (!$modelClass) {
            $this->error("The model key [$modelKey] is not registered as a permissionable class.");
            return Command::FAILURE;
        }

        // Fetch the model
        $userId = $this->argument('userId');

        // Retrieve the user by ID

        $user = $modelClass::find($userId);

        if (!$user) {
            $this->error('User not found.');
            return 1; // Return error code
        }


        // Get all permissions for the specified model
        $permissions = Permission::all();

        // Assign all permissions to the user
        $user->givePermissionTo($permissions);


        $this->info('All permissions have been assigned to the user '.$user->email.' successfully.');

        return 0; // Return success code
    }
}
