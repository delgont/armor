<?php

namespace Delgont\Armor\Console\Commands;

use Delgont\Armor\Support\ActionRegistrars;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ActionsListCommand extends Command
{
    protected $signature = 'armor:actions:list
                            {--group= : Only this group (name or key)}
                            {--risk= : Only this risk level (low, medium, high, critical)}
                            {--json : Output as JSON}';

    protected $description = 'List system actions with risk, protection, undo support and audit usage';

    public function handle(): int
    {
        [$instances] = ActionRegistrars::resolve();

        // "key|registrar" => action keys declared in code
        $inCode = [];
        foreach ($instances as $class => $registrar) {
            $inCode[$registrar->getKey().'|'.$class] = ActionRegistrars::keys($registrar);
        }

        $groups = DB::table('system_action_groups')->get()->keyBy('id');

        $audit = Schema::hasTable('audit_logs')
            ? DB::table('audit_logs')->whereNotNull('action_id')->selectRaw('action_id, count(*) as total')->groupBy('action_id')->pluck('total', 'action_id')
            : collect();

        $query = DB::table('system_actions')->orderBy('system_action_group_id')->orderBy('order');

        if ($risk = $this->option('risk')) {
            $query->where('risk_level', $risk);
        }

        $rows = [];

        foreach ($query->get() as $action) {
            $group = $groups[$action->system_action_group_id] ?? null;

            if (($filter = $this->option('group')) && (! $group || ! in_array($filter, [$group->name, $group->key], true))) {
                continue;
            }

            $source = match (true) {
                ! $group                                                      => 'orphan',
                ! array_key_exists($group->key.'|'.$group->registrar, $inCode) => 'orphan group',
                in_array($action->action, $inCode[$group->key.'|'.$group->registrar], true) => 'code',
                default                                                       => 'stale',
            };

            $rows[] = [
                'group'  => $group->name ?? '-',
                'action' => $action->action,
                'risk'   => $action->risk_level,
                'otp'    => $action->otp_protected ? 'yes' : 'no',
                'undo'   => $action->supports_undo ? 'yes' : 'no',
                'source' => $source,
                'audit'  => $audit[$action->id] ?? 0,
            ];
        }

        if ($this->option('json')) {
            $this->line(json_encode($rows, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        if ($rows === []) {
            $this->warn('No actions found.');
            return self::SUCCESS;
        }

        $this->table(['Group', 'Action', 'Risk', 'OTP', 'Undo', 'Source', 'Audit entries'], $rows);

        $counts = array_count_values(array_column($rows, 'source'));
        $this->line('  '.count($rows).' action(s): '.implode(', ', array_map(fn ($s, $n) => "{$n} {$s}", array_keys($counts), $counts)));

        return self::SUCCESS;
    }
}