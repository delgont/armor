<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Delgont\Armor\Models\Role;
use Delgont\Armor\Models\PermissionGroup;

class ShowRolePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'armor:show-role-permissions {--role= : Role name or ID} {--group= : Permission group name or ID (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Displays permissions assigned to a role, optionally filtered by permission group';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $roleInput = $this->option('role') ?? $this->ask('Enter role name or ID');
        $groupInput = $this->option('group');

        $role = is_numeric($roleInput)
            ? \Delgont\Armor\Models\Role::find($roleInput)
            : \Delgont\Armor\Models\Role::where('name', $roleInput)->first();

        if (!$role) {
            $this->error("Role not found: {$roleInput}");
            return 1;
        }

        $permissions = $role->permissions();

        if ($groupInput) {
            $group = is_numeric($groupInput)
                ? PermissionGroup::find($groupInput)
                : PermissionGroup::where('name', $groupInput)->first();

            if (!$group) {
                $this->warn("Permission group '{$groupInput}' not found. Showing all permissions.");
            } else {
                $permissions->where('permission_group_id', $group->id);
            }
        }

        $permissions = $permissions->get();

        if ($permissions->isEmpty()) {
            $this->warn("No permissions assigned to role '{$role->name}'" . ($groupInput ? " in the specified group." : "."));
            return 0;
        }

        $this->table(
            ['ID', 'Name', 'Guard', 'Description'],
            $permissions->map(function ($perm) {
                return [
                    'ID' => $perm->id,
                    'Name' => $perm->name,
                    'Guard' => $perm->guard_name ?? 'web',
                    'Description' => $perm->description ?? '-',
                ];
            })->toArray()
        );

        return 0;
    }
}
