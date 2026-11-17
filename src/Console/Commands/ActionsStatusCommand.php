<?php

namespace Delgont\Armor\Console\Commands;

use Delgont\Armor\Support\ActionRegistrars;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Formatter\OutputFormatter;

class ActionsStatusCommand extends Command
{
    protected $signature = 'armor:actions:status
                            {--json : Output the report as JSON}
                            {--strict : Also fail on stale rows, orphan groups and unprotected high-risk actions}';

    protected $description = 'Compare code-defined actions with the database';

    /** Issue types that the sync fixes. These make the command fail. */
    private const BLOCKING = ['missing_group', 'missing', 'changed'];

    private const SYMBOLS = [
        'missing_group' => '+', 'missing' => '+',
        'changed' => '~', 'name' => '~',
        'stale' => '-', 'unprotected' => '!',
    ];

    public function handle(): int
    {
        [$instances, $absent, $invalid] = ActionRegistrars::resolve();

        $groups = DB::table('system_action_groups')->get();
        $byIdentity = $groups->keyBy(fn ($g) => $g->key.'|'.$g->registrar);
        $actions = DB::table('system_actions')->get()->groupBy('system_action_group_id');

        $report = [];

        foreach ($instances as $class => $registrar) {
            $name = ActionRegistrars::groupName($registrar);
            $keys = ActionRegistrars::keys($registrar);
            $group = $byIdentity[$registrar->getKey().'|'.$class] ?? null;
            $issues = [];

            if (! $group) {
                $report[$class] = [['missing_group', $name, 'group does not exist']];
                continue;
            }

            if ($group->name !== $name) {
                // Non-blocking: the sync does not update group names yet.
                $issues[] = ['name', $name, "database name is '{$group->name}'"];
            }

            $rows = $actions->get($group->id, collect())->keyBy('action');
            $described = $registrar->getDescriptions();
            $undoable = $registrar->getUndoable();

            foreach ($keys as $i => $key) {
                $row = $rows[$key] ?? null;

                if (! $row) {
                    $issues[] = ['missing', $key, 'missing'];
                    continue;
                }

                $differs = [];
                ($row->description ?? null) !== ($described[$key] ?? null) ? $differs[] = 'description' : null;
                (bool) $row->supports_undo !== in_array($key, $undoable, true) ? $differs[] = 'supports_undo' : null;
                (int) $row->order !== $i + 1 ? $differs[] = 'order' : null;

                $differs ? $issues[] = ['changed', $key, implode(' and ', $differs).' differ'] : null;

                // The sync never writes protection, so a high-risk action can start out unprotected.
                if (in_array($row->risk_level, ['high', 'critical'], true) && ! $row->otp_protected) {
                    $issues[] = ['unprotected', $key, "risk is {$row->risk_level} but OTP protection is off"];
                }
            }

            foreach ($rows->keys()->diff($keys) as $key) {
                $issues[] = ['stale', $key, 'in database, not in code'];
            }

            $report[$class] = $issues;
        }

        $orphans = $groups
            ->reject(fn ($g) => collect($instances)->contains(fn ($r, $c) => $r->getKey() === $g->key && $c === $g->registrar))
            ->map(fn ($g) => ['group' => $g->name, 'registrar' => $g->registrar, 'actions' => $actions->get($g->id, collect())->count()])
            ->values()
            ->all();

        $blocking = count($absent) + count($invalid);
        $other = count($orphans);

        foreach ($report as $issues) {
            foreach ($issues as [$type]) {
                in_array($type, self::BLOCKING, true) ? $blocking++ : $other++;
            }
        }

        $fails = $blocking > 0 || ($this->option('strict') && $other > 0);

        if ($this->option('json')) {
            $this->line(json_encode([
                'in_sync' => ! $fails, 'blocking' => $blocking, 'other' => $other,
                'registrars' => $report, 'missing_classes' => $absent, 'invalid_classes' => $invalid, 'orphan_groups' => $orphans,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $fails ? self::FAILURE : self::SUCCESS;
        }

        $this->newLine();

        foreach ($report as $class => $issues) {
            $hard = array_filter($issues, fn ($i) => in_array($i[0], self::BLOCKING, true));

            [$label, $style] = match (true) {
                $hard !== []   => ['DRIFT', 'fg=red;options=bold'],
                $issues !== [] => ['NOTES', 'fg=yellow'],
                default        => ['IN SYNC', 'fg=green;options=bold'],
            };

            $this->line('  '.OutputFormatter::escape($class)." <{$style}>{$label}</>");

            foreach ($issues as [$type, $subject, $detail]) {
                $this->line('     '.self::SYMBOLS[$type].' '.OutputFormatter::escape($subject)." <fg=gray>{$detail}</>");
            }
        }

        foreach ($absent as $class) {
            $this->line('  '.OutputFormatter::escape($class).' <fg=red;options=bold>NOT FOUND</>');
        }
        foreach ($invalid as $class) {
            $this->line('  '.OutputFormatter::escape($class).' <fg=red;options=bold>NOT A SystemActionRegistrar</>');
        }

        foreach ($orphans as $o) {
            $this->line("  <fg=yellow>Group \"".OutputFormatter::escape($o['group']).'" ('.OutputFormatter::escape($o['registrar'])."), {$o['actions']} action(s), is not in a configured registrar.</>");
        }

        $this->newLine();

        if ($blocking > 0) {
            $this->error("Out of sync: {$blocking} difference(s). Run: php artisan armor:actions:sync --force");
        } elseif ($fails) {
            $this->error("{$other} note(s) need attention (strict mode).");
        } else {
            $this->info('Actions are in sync.');
            $other > 0 ? $this->line("  <fg=gray>{$other} note(s) the sync cannot fix. Use --strict to fail on these.</>") : null;
        }

        return $fails ? self::FAILURE : self::SUCCESS;
    }
}