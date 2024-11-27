<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Delgont\Armor\Armor;
use Delgont\Armor\Models\Permission;

class GrantPermissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'armor:grant-permission
                            {modelId : The ID of the model}
                            {modelKey : The shorthand key or fully qualified class name of the model (e.g., "user" or App\\User)}
                            {permission : The permission name or ID to grant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant a single permission to a model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelKey = $this->argument('modelKey');
        $modelId = $this->argument('modelId');
        $permission = $this->argument('permission');

        // Resolve the model class using the Armor utility
        $modelClass = Armor::resolvePermissionable($modelKey);

        if (!$modelClass) {
            $this->error("The model key [$modelKey] is not registered as a permissionable class.");
            return Command::FAILURE;
        }

        // Fetch the model
        $model = $modelClass::find($modelId);
        if (!$model) {
            $this->error("No [$modelClass] found with ID [$modelId].");
            return Command::FAILURE;
        }

        // Fetch the permission by name or ID
        $permissionModel = is_numeric($permission)
            ? Permission::find($permission)
            : Permission::whereName($permission)->first();

        if (!$permissionModel) {
            $this->error("The permission [$permission] does not exist.");
            return Command::FAILURE;
        }

        // Grant the permission
        $model->givePermissionTo($permissionModel);

        $this->info("Permission [{$permissionModel->name}] granted successfully to [$modelClass] with ID [$modelId].");

        return Command::SUCCESS;
    }
}
