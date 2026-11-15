<?php

namespace Delgont\Armor\Console\Commands;

use Delgont\Armor\Armor;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;

class PermissionTree extends Command
{
    protected $signature = 'armor:permissions:tree
                            {--P|permissions : List each permission under its group}
                            {--json : Output the tree as JSON}';

    protected $description = 'Display the permission registrar hierarchy';

    public function handle(): int
    {
        $tree = Armor::getPermissionTree();

        if ($this->option('json')) {
            $this->line(json_encode($tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }

        if ($tree === []) {
            $this->warn('No permission registrars found.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->renderNodes($tree);
        $this->newLine();

        [$groups, $permissions, $depth] = $this->stats($tree);
        $this->line("  <fg=gray>{$groups} group(s), {$permissions} permission(s), {$depth} level(s) deep</>");

        $this->warnAboutUnreachable($tree);

        $this->newLine();

        return Command::SUCCESS;
    }

    protected function renderNodes(array $nodes, string $prefix = ''): void
    {
        $lastIndex = array_key_last($nodes);

        foreach ($nodes as $i => $node) {
            $isLast = $i === $lastIndex;
            $count  = count($node['permissions']);

            $label = '<fg=cyan;options=bold>'.OutputFormatter::escape($node['group']).'</>';

            // When the group name is the class name, don't print it twice.
            if ($node['group'] !== $node['registrar']) {
                $label .= ' <fg=gray>'.OutputFormatter::escape(class_basename($node['registrar'])).'</>';
            }

            $this->line($prefix.($isLast ? '└── ' : '├── ')."{$label} <fg=gray>({$count})</>");

            $childPrefix = $prefix.($isLast ? '    ' : '│   ');

            if ($this->option('permissions')) {
                foreach ($node['permissions'] as $permission) {
                    $this->line($childPrefix.'<fg=gray>·</> '.OutputFormatter::escape((string) $permission));
                }
            }

            if ($node['children'] !== []) {
                $this->renderNodes($node['children'], $childPrefix);
            }
        }
    }

    /**
     * @return array{0:int, 1:int, 2:int} groups, permissions, depth
     */
    protected function stats(array $nodes, int $level = 1): array
    {
        $groups = 0;
        $permissions = 0;
        $depth = $level;

        foreach ($nodes as $node) {
            $groups++;
            $permissions += count($node['permissions']);

            if ($node['children'] !== []) {
                [$g, $p, $d] = $this->stats($node['children'], $level + 1);
                $groups += $g;
                $permissions += $p;
                $depth = max($depth, $d);
            }
        }

        return [$groups, $permissions, $depth];
    }

    /**
     * Registrars in a circular parent chain have no root, so they never
     * appear in the tree. Compare against the configured list to catch that.
     */
    protected function warnAboutUnreachable(array $tree): void
    {
        $seen = [];
        $collect = function (array $nodes) use (&$collect, &$seen) {
            foreach ($nodes as $node) {
                $seen[$node['registrar']] = true;
                $collect($node['children']);
            }
        };
        $collect($tree);

        $missing = array_filter(
            array_unique(Armor::getPermissionRegistrars()),
            fn ($class) => class_exists($class) && ! isset($seen[$class])
        );

        if ($missing !== []) {
            $this->newLine();
            $this->warn('Not shown (circular parent chain?):');
            foreach ($missing as $class) {
                $this->line('  <fg=yellow>-</> '.OutputFormatter::escape($class));
            }
        }
    }
}