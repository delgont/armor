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

use Delgont\Armor\AuthManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Throwable;

class SyncRolesCommand extends Command
{
    protected $signature = 'armor:roles:sync {--f|force : Skip the confirmation prompt}';
    protected $description = 'Sync roles with the system';

    public function handle(AuthManager $auth): int
    {
        if (! $this->option('force') && ! $this->confirm('Sync roles from the registrars to the database?', true)) {
            $this->info('Role sync cancelled.');
            return self::SUCCESS;
        }

        $this->newLine();
        $failures = 0;

        $message = $auth->syncRoles(function (string $event, string $registrar, ...$args) use (&$failures) {
            $name = OutputFormatter::escape($registrar);

            switch ($event) {
                case 'done':
                    $this->line("  {$name} <fg=green;options=bold>DONE</> <fg=gray>({$args[0]} role(s))</>");
                    break;

                case 'failed':
                    $failures++;
                    /** @var Throwable $e */
                    $e = $args[0];
                    $this->line("  {$name} <fg=red;options=bold>FAIL</>");
                    $this->line('     <fg=red>'.OutputFormatter::escape($e->getMessage()).'</>');
                    break;

                case 'skipped':
                    $this->line("  {$name} <fg=yellow>SKIPPED</> <fg=gray>(class does not exist)</>");
                    break;
            }
        });

        $this->newLine();
        $failures > 0 ? $this->error($message) : $this->info($message);

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}