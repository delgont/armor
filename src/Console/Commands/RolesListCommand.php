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

class RolesListCommand extends Command
{
    protected $signature = 'armor:roles:list {--json : Output as JSON}';
    protected $description = 'List roles with their group, permission count and member count';

    public function handle(): int
    {
        $defined = Armor::definedRoleNames();

        $rows = Role::query()
            ->with('group:id,name,registrar')
            ->withCount(['permissions'])
            ->get()
            ->map(fn ($role) => [
                'name'        => $role->name,
                'group'       => $role->group?->name ?? '-',
                'source'      => in_array($role->name, $defined, true) ? 'code' : ($role->group?->registrar ? 'stale' : 'manual'),
                'permissions' => $role->permissions_count,
                'members'     => \DB::table('model_has_roles')->where('role_id', $role->id)->count(),
            ])
            ->all();

        if ($this->option('json')) {
            $this->line(json_encode($rows, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $rows === []
            ? $this->warn('No roles found.')
            : $this->table(['Role', 'Group', 'Source', 'Permissions', 'Members'], $rows);

        return self::SUCCESS;
    }
}