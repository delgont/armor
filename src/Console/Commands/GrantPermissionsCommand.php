<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Delgont\Armor\Armor;
use Delgont\Armor\Models\Permission;

class GrantPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'armor:grant-permissions
                            {modelId : The ID of the model}
                            {modelKey : The shorthand key or fully qualified class name of the model (e.g., "user" or App\\User)}
                            {permissions : Comma-separated list of permission names or IDs (e.g., can_view_users,33,can_edit_users)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant multiple permissions to a model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelKey = $this->argument('modelKey');
        $modelId = $this->argument('modelId');
        $permissionsInput = $this->argument('permissions');

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

        // Parse and validate permissions
        $permissions = collect(explode(',', $permissionsInput))
            ->map(function ($permission) {
                return is_numeric($permission)
                    ? Permission::find($permission)
                    : Permission::whereName($permission)->first();
            })
            ->filter(); // Remove nulls for non-existent permissions

        if ($permissions->isEmpty()) {
            $this->error("None of the provided permissions exist.");
            return Command::FAILURE;
        }

        // Grant the permissions
        $model->givePermissionTo($permissions);

        $this->info("Permissions granted successfully to [$modelClass] with ID [$modelId].");

        return Command::SUCCESS;
    }
}
