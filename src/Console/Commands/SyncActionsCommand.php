<?php

namespace Delgont\Armor\Console\Commands;

use Delgont\Armor\AuthManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Throwable;

class SyncActionsCommand extends Command
{
    protected $signature = 'armor:actions:sync {--f|force : Skip the confirmation prompt}';
    protected $description = 'Sync registered system actions with the database';

    public function handle(AuthManager $auth): int
    {
        if (! $this->option('force') && ! $this->confirm('Sync system actions from the registrars to the database?', true)) {
            $this->info('Action sync cancelled.');
            return self::SUCCESS;
        }

        $this->newLine();

        $processed = 0;
        $failures = 0;

        $message = $auth->syncActions(function (string $event, string $registrar, ...$args) use (&$processed, &$failures) {
            $name = OutputFormatter::escape($registrar);

            switch ($event) {
                case 'start':
                    $processed++;
                    break;

                case 'done':
                    $this->line("  {$name} <fg=green;options=bold>DONE</> <fg=gray>({$args[0]} action(s))</>");
                    break;

                case 'failed':
                    $failures++;
                    /** @var Throwable $e */
                    $e = $args[0];
                    $this->line("  {$name} <fg=red;options=bold>FAIL</>");
                    $this->line('     <fg=red>'.OutputFormatter::escape($e->getMessage()).'</>');
                    break;

                case 'skipped':
                    $this->line("  {$name} <fg=yellow>SKIPPED</> <fg=gray>({$args[0]})</>");
                    break;
            }
        });

        $this->newLine();

        if ($processed === 0) {
            $this->warn($message === 'There are no actions to sync' ? $message : 'No usable action registrars were found.');
            return self::SUCCESS;
        }

        $failures > 0 ? $this->error($message) : $this->info($message);

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}