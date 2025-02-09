<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Delgont\Armor\Models\Role;

class ShowRolesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'armor:show-roles
                            {--page=1 : The page number for paginated results}
                            {--per-page=10 : Number of roles to display per page}';

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

        // Fetch roles with eager loading
        $roles = Role::with('group')
            ->orderBy('id')
            ->get()
            ->forPage($page, $perPage);

        if ($roles->isEmpty()) {
            $this->info('No roles found for the specified page.');
            return 0;
        }

        // Prepare roles data for table display
        $rolesArray = $roles->map(function ($role) {
            return [
                'ID' => $role->id,
                'Role Name' => $role->name,
                'Order' => $role->order,
                'Group Name' => $role->group->name ?? 'No Group',
            ];
        })->toArray();

        // Display the table
        $this->line('');
        $this->info("Roles (Page {$page}, showing {$perPage} per page)");
        $this->table(['ID', 'Role Name', 'Order', 'Group Name'], $rolesArray);
        $this->line('');

        return 0;
    }
}
