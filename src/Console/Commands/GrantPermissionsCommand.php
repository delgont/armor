<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Delgont\Armor\Armor;
use Delgont\Armor\Models\Permission;

class GrantPermissionsCommand extends Command
{
    protected $signature = 'armor:grant-permissions
                            {--modelId= : The ID of the model}
                            {--modelKey= : The shorthand key or fully qualified class name of the model}
                            {permissions : Comma-separated list of permission names or IDs (e.g., can_view_users,33,can_edit_users)}';

    protected $description = 'Grant multiple permissions to a model using --modelId, --modelKey and a trailing permissions argument';

    public function handle()
    {
        $modelId = $this->option('modelId');
        $modelKey = $this->option('modelKey');
        $permissionsInput = $this->argument('permissions');

        if (!$modelId || !$modelKey || !$permissionsInput) {
            $this->error('Missing required inputs: --modelId, --modelKey and permissions argument.');
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

        $permissions = collect(explode(',', $permissionsInput))
            ->map(function ($p) {
                return is_numeric($p)
                    ? Permission::find($p)
                    : Permission::whereName($p)->first();
            })
            ->filter();

        if ($permissions->isEmpty()) {
            $this->error("None of the provided permissions exist.");
            return Command::FAILURE;
        }

        $model->givePermissionTo($permissions);

        $names = $permissions->pluck('name')->join(', ');
        $this->info("✅ Permissions [$names] granted to [{$modelClass}] ID [$modelId].");

        return Command::SUCCESS;
    }
}
