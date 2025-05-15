<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Delgont\Armor\Models\Role;

class AddRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'armor:add-role';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a new unique role to the system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->ask('Enter the role name');
        $description = $this->ask('Enter the role description (optional)');
        $roleGroupId = $this->ask('Enter the role group ID (optional)');

        if (Role::where('name', $name)->exists()) {
            $this->error("A role with the name '{$name}' already exists.");
            return 1;
        }

        $role = Role::create([
            'name' => $name,
            'description' => $description ?: null,
            'role_group_id' => $roleGroupId ?: null,
        ]);

        $this->info("Role '{$role->name}' created successfully.");
        return 0;
    }
}
