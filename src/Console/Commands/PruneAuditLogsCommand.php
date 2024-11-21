<?php
namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Delgont\Armor\Models\AuditLog;

class PruneAuditLogsCommand extends Command
{
    protected $signature = 'audit:prune {--days=30 : Number of days to retain logs}';
    protected $description = 'Prune old audit logs';

    public function handle()
    {
        $days = $this->option('days');
        $pruned = AuditLog::where('performed_at', '<', now()->subDays($days))->delete();

        $this->info("Pruned $pruned audit logs older than $days days.");
    }
}
