<?php

namespace Delgont\Armor\Console\Commands;


use Illuminate\Console\Command;

use Delgont\Armor\Models\Permission;


class GiveAllPermissionsToUser extends Command
{
    protected $signature = 'permissions:give-all {userId} {model?}';
    protected $description = 'Give all permissions to the specified user by ID and model';

    public function handle()
    {
        $modelClass = $this->argument('model') ?? config('armor.user', App\User::class);

        // Check if the model class exists
        if (!class_exists($modelClass)) {
            $this->error('Model class not found: ' . $modelClass);
            return 1; // Return error code
        }

        // Retrieve the user by ID
        $userId = $this->argument('userId');

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
