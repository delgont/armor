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
    protected $description = 'Cache roles and its permissions';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $roles = Role::with(['permissions'])->get();

        Cache::put('role:all', $roles);

        foreach ($roles as $role) {
            $this->info($role->name);
            Cache::put('role:'.$role.':permissions', $role->permissions);
        }
        $this->info('Role and permissions have been cached ...');
    }

    private function getRoles()
    {
        
    }
}
