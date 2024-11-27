<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Delgont\Armor\Models\Permission;

class ShowPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'armor:show-permissions
                            {--page=1 : The page number for paginated results}
                            {--per-page=10 : Number of permissions to display per page}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Displays permissions in a paginated table along with their groups';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get the pagination parameters
        $page = max((int) $this->option('page'), 1);
        $perPage = max((int) $this->option('per-page'), 1);

        // Fetch permissions with eager loading
        $permissions = Permission::with('permissionGroup')
            ->orderBy('id')
            ->get()
            ->forPage($page, $perPage);

        if ($permissions->isEmpty()) {
            $this->info('No permissions found for the specified page.');
            return 0;
        }

        // Prepare permissions data for table display
        $permissionsArray = $permissions->map(function ($permission) {
            return [
                'ID' => $permission->id,
                'Permission Name' => $permission->name,
                'Order' => $permission->order,
                'Group Name' => $permission->permissionGroup->name ?? 'No Group',
            ];
        })->toArray();

        // Display the table
        $this->line('');
        $this->info("Permissions (Page {$page}, showing {$perPage} per page)");
        $this->table(['ID', 'Permission Name', 'Order', 'Group Name'], $permissionsArray);
        $this->line('');

        return 0;
    }
}
