<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Delgont\Armor\Models\Role;

class RoleCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache roles and their permissions';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('This will overwrite cached roles and permissions. Do you wish to continue?', true)) {
            $this->info('Command aborted.');
            return Command::SUCCESS;
        }

        $roles = $this->fetchRoles();

        $this->cacheRoles($roles);

        $this->displayRoles($roles);

        $this->info('Roles and permissions have been successfully cached.');
        return Command::SUCCESS;
    }

    /**
     * Fetch all roles with their permissions.
     */
    private function fetchRoles()
    {
        return Role::with('permissions')->get();
    }

    /**
     * Cache the roles and their permissions.
     */
    private function cacheRoles($roles)
    {
        Cache::put('role:all', $roles);

        foreach ($roles as $role) {
            Cache::put('role:' . $role->id . ':permissions', $role->permissions);
        }
    }

    /**
     * Display the roles and permissions in a table format.
     */
    private function displayRoles($roles)
    {
        $tableData = $roles->map(function ($role) {
            return [
                'Role Name' => $role->name,
                'Permissions' => $role->permissions->pluck('name')->implode(', '),
            ];
        });

        $this->table(['Role Name', 'Permissions'], $tableData->toArray());
    }
}
