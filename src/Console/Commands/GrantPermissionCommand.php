<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Delgont\Armor\Armor;
use Delgont\Armor\Models\Permission;

class GrantPermissionCommand extends Command
{
    protected $signature = 'armor:grant-permission
                            {--modelId= : The ID of the model}
                            {--modelKey= : The shorthand or class name of the model}
                            {permission : The permission name or ID to grant}';

    protected $description = 'Grant a permission to a model using --modelId, --modelKey and permission argument';

    public function handle()
    {
        $modelId = $this->option('modelId');
        $modelKey = $this->option('modelKey');
        $permission = $this->argument('permission');

        if (!$modelId || !$modelKey || !$permission) {
            $this->error('Missing required inputs: --modelId, --modelKey and permission argument');
            return Command::FAILURE;
        }

        $modelClass = Armor::resolvePermissionable($modelKey);
        if (!$modelClass) {
            $this->error("The model key [$modelKey] is not registered as a permissionable class.");
            return Command::FAILURE;
        }

        $model = $modelClass::find($modelId);
        if (!$model) {
            $this->error("No [$modelClass] found with ID [$modelId].");
            return Command::FAILURE;
        }

        $permissionModel = is_numeric($permission)
            ? Permission::find($permission)
            : Permission::whereName($permission)->first();

        if (!$permissionModel) {
            $this->error("The permission [$permission] does not exist.");
            return Command::FAILURE;
        }

        $model->givePermissionTo($permissionModel);

        $this->info("✅ Permission [{$permissionModel->name}] granted to [{$modelClass}] ID [{$modelId}].");

        return Command::SUCCESS;
    }
}
