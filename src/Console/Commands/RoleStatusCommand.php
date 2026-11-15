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

use Delgont\Armor\Models\Role;
use Delgont\Armor\Models\RoleGroup;
use Delgont\Armor\RoleRegistrar;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;

class RoleStatusCommand extends Command
{
    protected $signature = 'armor:roles:status
                            {--json : Output the report as JSON}
                            {--strict : Also fail when the database has roles that are not defined in code}';

    protected $description = 'Compare code-defined roles with the database';

    public function handle(): int
    {
        $groups = RoleGroup::query()->get()->keyBy('name');
        $groupsById = $groups->keyBy('id');
        $roles = Role::query()->get();
        $rolesByName = $roles->keyBy('name');

        $report = [];
        $configured = [];
        $absent = [];

        foreach (array_filter((array) config('armor.role_registrars', []), 'is_string') as $class) {
            if (! class_exists($class) || ! is_subclass_of($class, RoleRegistrar::class)) {
                $absent[] = $class;
                continue;
            }

            $configured[] = $class;
            $instance = app($class);
            $groupName = $instance->groupName();
            $group = $groupName ? ($groups[$groupName] ?? null) : null;
            $issues = [];

            if ($groupName && ! $group) {
                $issues[] = ['missing_group', $groupName, 'group does not exist'];
            } elseif ($group && $group->registrar !== $class) {
                $issues[] = ['group_changed', $groupName, 'registrar is '.($group->registrar ?? 'null')];
            }

            $expected = $instance->roleNames();

            foreach ($expected as $name) {
                $role = $rolesByName[$name] ?? null;

                if (! $role) {
                    $issues[] = ['missing', $name, 'missing'];
                    continue;
                }

                $actual = $role->role_group_id ? ($groupsById[$role->role_group_id]->name ?? '#'.$role->role_group_id) : null;

                if ($actual !== $groupName) {
                    $issues[] = ['changed', $name, 'group is '.($actual ?? 'none').', expected '.($groupName ?? 'none')];
                }
            }

            if ($group) {
                foreach ($roles->where('role_group_id', $group->id) as $role) {
                    if (! in_array($role->name, $expected, true)) {
                        $issues[] = ['stale', $role->name, 'in database, not in code'];
                    }
                }
            }

            // Permissions the registrar declares for its roles.
            if ($instance->declaresPermissions()) {
                try {
                    $granted = array_keys($instance->grantedPermissions());

                    foreach ($expected as $name) {
                        if (! isset($rolesByName[$name])) {
                            continue;   // already reported as missing
                        }

                        $have = $rolesByName[$name]->permissions()->pluck('name')->all();

                        if ($missing = array_diff($granted, $have)) {
                            $issues[] = ['permissions_missing', $name, 'missing: '.implode(', ', $missing)];
                        }

                        // Extra permissions are normal in additive mode. In strict mode the sync would remove them.
                        if ($instance->isStrict() && ($extra = array_diff($have, $granted))) {
                            $issues[] = ['permissions_extra', $name, 'would be removed: '.implode(', ', $extra)];
                        }
                    }
                } catch (Throwable $e) {
                    $issues[] = ['invalid', class_basename($class), $e->getMessage()];
                }
            }

            $report[$class] = $issues;
        }

        $orphans = $groups
            ->filter(fn ($g) => $g->registrar !== null && ! in_array($g->registrar, $configured, true))
            ->map(fn ($g) => ['group' => $g->name, 'registrar' => $g->registrar])
            ->values()
            ->all();

        $blocking = count($absent);
        $stale = count($orphans);

        foreach ($report as $issues) {
            foreach ($issues as [$type]) {
                $type === 'stale' ? $stale++ : $blocking++;
            }
        }

        $fails = $blocking > 0 || ($this->option('strict') && $stale > 0);

        if ($this->option('json')) {
            $this->line(json_encode([
                'in_sync' => ! $fails, 'blocking' => $blocking, 'stale' => $stale,
                'registrars' => $report, 'missing_classes' => $absent, 'orphan_groups' => $orphans,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $fails ? self::FAILURE : self::SUCCESS;
        }

        $this->newLine();

        foreach ($report as $class => $issues) {
            $hard = array_filter($issues, fn ($i) => $i[0] !== 'stale');
            [$label, $style] = match (true) {
                $hard !== []   => ['DRIFT', 'fg=red;options=bold'],
                $issues !== [] => ['STALE', 'fg=yellow'],
                default        => ['IN SYNC', 'fg=green;options=bold'],
            };

            $this->line('  '.OutputFormatter::escape($class)." <{$style}>{$label}</>");

            foreach ($issues as [$type, $subject, $detail]) {
                $symbol = [
                    'missing' => '+', 'missing_group' => '+', 'permissions_missing' => '+',
                    'changed' => '~', 'group_changed' => '~',
                    'stale' => '-', 'permissions_extra' => '-',
                    'invalid' => '!',
                ][$type];
                $this->line("     {$symbol} ".OutputFormatter::escape($subject)." <fg=gray>{$detail}</>");
            }
        }

        foreach ($absent as $class) {
            $this->line('  '.OutputFormatter::escape($class).' <fg=red;options=bold>NOT FOUND</>');
        }

        foreach ($orphans as $o) {
            $this->line('  <fg=yellow>Group "'.OutputFormatter::escape($o['group']).'" belongs to '.OutputFormatter::escape($o['registrar']).', which is no longer configured.</>');
        }

        $this->newLine();

        if ($blocking > 0) {
            $this->error("Out of sync: {$blocking} difference(s). Run: php artisan armor:roles:sync --force");
        } elseif ($stale > 0 && $this->option('strict')) {
            $this->error("{$stale} stale item(s) exist in the database but not in code.");
        } else {
            $this->info('Roles are in sync.');
            $stale > 0 ? $this->line("  <fg=gray>{$stale} stale item(s) not in code (use --strict to fail on these).</>") : null;
        }

        return $fails ? self::FAILURE : self::SUCCESS;
    }
}