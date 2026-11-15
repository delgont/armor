<?php

namespace Delgont\Armor\Console\Commands;

use Delgont\Armor\Armor;
use Delgont\Armor\Models\Permission;
use Delgont\Armor\Models\PermissionGroup;
use Delgont\Armor\PermissionRegistrar;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Terminal;

class PermissionStatusCommand extends Command
{
    protected $signature = 'armor:permissions:status
                            {--json : Output the report as JSON}
                            {--strict : Also fail when the database has stale rows that are not defined in code}';

    protected $description = 'Compare code-defined permissions with the database';

    /** Issue types that make the command fail. */
    private const BLOCKING = ['missing_group', 'group_changed', 'missing', 'changed', 'conflict', 'registrar_invalid'];

    private const SYMBOLS = [
        'missing_group' => ['+', 'red'],
        'missing'       => ['+', 'red'],
        'group_changed' => ['~', 'yellow'],
        'changed'       => ['~', 'yellow'],
        'conflict'      => ['!', 'red'],
        'registrar_invalid' => ['!', 'red'],
        'stale'         => ['-', 'gray'],
    ];

    public function handle(): int
    {
        [$instances, $absent, $legacy] = $this->collectRegistrars();

        $groups = PermissionGroup::query()->get();
        $permissions = Permission::query()->get();

        $ctx = [
            'instances'           => $instances,
            'groups'              => $groups->keyBy('name')->all(),
            'groupsById'          => $groups->keyBy('id')->all(),
            'permissionsByName'   => $permissions->keyBy('name')->all(),
            'permissionsByGroup'  => $permissions->groupBy('permission_group_id')->all(),
        ];

        $report = [];
        foreach ($instances as $class => $instance) {
            $report[$class] = $this->inspect($instance, $ctx);
        }

        $orphans = $this->orphanGroups($groups, array_keys($instances), $ctx);
        $unmanaged = $groups->whereNull('registrar')->count();

        $blocking = 0;
        $stale = count($orphans);
        foreach ($report as $issues) {
            foreach ($issues as $issue) {
                in_array($issue['type'], self::BLOCKING, true) ? $blocking++ : $stale++;
            }
        }
        $blocking += count($absent);

        $fails = $blocking > 0 || ($this->option('strict') && $stale > 0);

        if ($this->option('json')) {
            $this->line(json_encode([
                'in_sync'    => ! $fails,
                'blocking'   => $blocking,
                'stale'      => $stale,
                'registrars' => $report,
                'missing_classes' => $absent,
                'orphan_groups'   => $orphans,
                'legacy_classes'  => $legacy,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $fails ? Command::FAILURE : Command::SUCCESS;
        }

        $this->render($report, $absent, $orphans, $legacy, $unmanaged);

        $this->newLine();

        if ($blocking > 0) {
            $this->error("Out of sync: {$blocking} difference(s). Run: php artisan armor:permissions:sync --force");
        } elseif ($stale > 0 && $this->option('strict')) {
            $this->error("{$stale} stale row(s) exist in the database but not in code.");
        } else {
            $this->info('Permissions are in sync.');
            if ($stale > 0) {
                $this->line("  <fg=gray>{$stale} stale row(s) not defined in code (use --strict to fail on these).</>");
            }
        }

        return $fails ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Resolve configured registrars plus any ancestors they declare.
     *
     * @return array{0: array<class-string, PermissionRegistrar>, 1: string[], 2: string[]}
     */
    protected function collectRegistrars(): array
    {
        $instances = [];
        $absent = [];
        $legacy = [];
        $queue = array_values(array_unique(Armor::getPermissionRegistrars()));

        while (($class = array_shift($queue)) !== null) {
            if (isset($instances[$class])) {
                continue;
            }

            if (! class_exists($class)) {
                $absent[$class] = $class;
                continue;
            }

            $instance = app($class);

            if (! $instance instanceof PermissionRegistrar) {
                $legacy[$class] = $class; // e.g. the older Permission class
                continue;
            }

            $instances[$class] = $instance;

            if ($instance->getParent() !== null) {
                $queue[] = $instance->getParent();
            }
        }

        return [$instances, array_values($absent), array_values($legacy)];
    }

    /**
     * @return array<int, array{type: string, subject: string, detail: string}>
     */
    protected function inspect(PermissionRegistrar $instance, array $ctx): array
    {
        $issues = [];
        $issue = function (string $type, string $subject, string $detail) use (&$issues) {
            $issues[] = compact('type', 'subject', 'detail');
        };

        $name = $instance->groupName();
        $group = $ctx['groups'][$name] ?? null;

        // Expected parent group name
        $expectedParent = null;
        if (($parentClass = $instance->getParent()) !== null) {
            $parentInstance = $ctx['instances'][$parentClass] ?? null;

            if ($parentInstance === null) {
                $issue('registrar_invalid', $name, "Parent {$parentClass} is not a valid registrar");
            } else {
                $expectedParent = $parentInstance->groupName();
            }
        }

        // Group-level checks
        if ($group === null) {
            $issue('missing_group', $name, 'group does not exist');
        } else {
            if ($group->registrar !== $instance::class) {
                $issue('group_changed', $name, 'registrar is '.($group->registrar ?? 'null'));
            }

            $actualParent = $group->parent_id
                ? ($ctx['groupsById'][$group->parent_id]->name ?? '#'.$group->parent_id)
                : null;

            if ($actualParent !== $expectedParent) {
                $issue(
                    'group_changed',
                    $name,
                    'parent is '.($actualParent ?? 'none').', expected '.($expectedParent ?? 'none')
                );
            }
        }

        // Permission-level checks
        $expected = $instance->permissionDefinitions();

        foreach ($expected as $permName => $definition) {
            $row = $ctx['permissionsByName'][$permName] ?? null;

            if ($row === null) {
                $issue('missing', $permName, 'missing');
                continue;
            }

            if ($group === null || (int) $row->permission_group_id !== (int) $group->id) {
                $owner = $row->permission_group_id
                    ? ($ctx['groupsById'][$row->permission_group_id]->name ?? '#'.$row->permission_group_id)
                    : 'no group';

                $issue('conflict', $permName, "already exists in {$owner}; sync would violate the unique name index");
                continue;
            }

            $diffs = [];
            if (($row->description ?? null) !== $definition['description']) {
                $diffs[] = 'description';
            }
            if ((int) $row->order !== $definition['order']) {
                $diffs[] = 'order';
            }

            if ($diffs !== []) {
                $issue('changed', $permName, implode(' and ', $diffs).' differ');
            }
        }

        // Stale permissions in this group
        if ($group !== null) {
            foreach ($ctx['permissionsByGroup'][$group->id] ?? [] as $row) {
                if (! isset($expected[$row->name])) {
                    $issue('stale', $row->name, 'in database, not in code');
                }
            }
        }

        return $issues;
    }

    /**
     * Groups claimed by a registrar class that is no longer configured.
     *
     * @return array<int, array{group: string, registrar: string, permissions: int}>
     */
    protected function orphanGroups($groups, array $knownClasses, array $ctx): array
    {
        return $groups
            ->filter(fn ($g) => $g->registrar !== null && ! in_array($g->registrar, $knownClasses, true))
            ->map(fn ($g) => [
                'group'       => $g->name,
                'registrar'   => $g->registrar,
                'permissions' => count($ctx['permissionsByGroup'][$g->id] ?? []),
            ])
            ->values()
            ->all();
    }

    protected function render(array $report, array $absent, array $orphans, array $legacy, int $unmanaged): void
    {
        $this->newLine();

        foreach ($report as $class => $issues) {
            $blocking = array_filter($issues, fn ($i) => in_array($i['type'], self::BLOCKING, true));

            [$status, $style] = match (true) {
                $blocking !== [] => ['DRIFT', 'fg=red;options=bold'],
                $issues !== []   => ['STALE', 'fg=yellow'],
                default          => ['IN SYNC', 'fg=green;options=bold'],
            };

            $this->renderLine($class, $status, $style);

            foreach ($issues as $issue) {
                [$symbol, $color] = self::SYMBOLS[$issue['type']];
                $this->line(sprintf(
                    '     <fg=%s>%s</> %s <fg=gray>%s</>',
                    $color,
                    $symbol,
                    OutputFormatter::escape($issue['subject']),
                    OutputFormatter::escape($issue['detail'])
                ));
            }
        }

        foreach ($absent as $class) {
            $this->renderLine($class, 'NOT FOUND', 'fg=red;options=bold');
        }

        if ($orphans !== []) {
            $this->newLine();
            $this->line('  <fg=yellow>Groups whose registrar is no longer configured:</>');
            foreach ($orphans as $o) {
                $this->line(sprintf(
                    '     <fg=gray>-</> %s <fg=gray>(%s, %d permission(s))</>',
                    OutputFormatter::escape($o['group']),
                    OutputFormatter::escape($o['registrar']),
                    $o['permissions']
                ));
            }
        }

        foreach ($legacy as $class) {
            $this->line('  <fg=gray>Skipped (not a PermissionRegistrar): '.OutputFormatter::escape($class).'</>');
        }

        if ($unmanaged > 0) {
            $this->line("  <fg=gray>{$unmanaged} group(s) have no registrar and are not managed by code.</>");
        }
    }

    protected function renderLine(string $registrar, string $status, string $style): void
    {
        $width = min((new Terminal())->getWidth(), 100);
        $dots = str_repeat('.', max(3, $width - mb_strlen($registrar) - mb_strlen($status) - 5));

        $this->line('  '.OutputFormatter::escape($registrar)." <fg=gray>{$dots}</> <{$style}>{$status}</>");
    }
}