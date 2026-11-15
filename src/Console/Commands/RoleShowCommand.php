<?php
/**
 * Delgont Armor  (https://delgont.co.ug).
 *
 * @link https://github.com/delgont/armor source repository
 *
 * @copyright Copyright (c) 2024. Delgont Technologies Co. Ltd (https://delgont.co.ug)
 *
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace Delgont\Armor\Console\Commands;

use Delgont\Armor\Armor;
use Delgont\Armor\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RoleShowCommand extends Command
{
    protected $signature = 'armor:role:show {name : The role name}';
    protected $aliases = ['armor:role:status'];
    protected $description = 'Show one role: group, source, permissions and members';

    public function handle(): int
    {
        $role = Role::query()->with('group')->where('name', $this->argument('name'))->first();

        if (! $role) {
            $this->error('Role not found: '.$this->argument('name'));
            return self::FAILURE;
        }

        $permissions = $role->permissionNames();
        $members = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->selectRaw('model_type, count(*) as total')
            ->groupBy('model_type')
            ->pluck('total', 'model_type');

        $source = in_array($role->name, Armor::definedRoleNames(), true)
            ? 'defined in code'
            : ($role->group?->registrar ? 'stale (group is managed, role is not in code)' : 'created manually');

        $this->newLine();
        $this->line('<options=bold>'.$role->name.'</>');
        $this->line('  uuid:    '.($role->uuid ?? '-'));
        $this->line('  group:   '.($role->group?->name ?? 'none'));
        $this->line('  source:  '.$source);
        $this->newLine();

        $this->line('  Permissions ('.count($permissions).')');
        foreach ($permissions as $permission) {
            $this->line('    - '.$permission);
        }
        count($permissions) === 0 ? $this->line('    <fg=gray>none</>') : null;

        $this->newLine();
        $this->line('  Members');
        foreach ($members as $type => $total) {
            $this->line("    - {$type}: {$total}");
        }
        $members->isEmpty() ? $this->line('    <fg=gray>nobody holds this role</>') : null;

        $this->newLine();

        return self::SUCCESS;
    }
}