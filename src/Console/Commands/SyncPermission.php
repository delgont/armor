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
use Symfony\Component\Console\Terminal;
use Throwable;

class SyncPermission extends Command
{

    protected $description = 'Sync permissions with the system';

   protected $signature = 'armor:permissions:sync
                        {--f|force : Skip the confirmation prompt}';

    public function handle(AuthManager $auth): int
    {
        if (! $this->option('force')) {
            $this->info('You are about to sync the permissions to your system.');

            if (! $this->confirm('Do you wish to proceed with syncing the permissions?', true)) {
                $this->info('Permission sync operation has been canceled.');
                return Command::SUCCESS;
            }
        }


        $this->newLine();

        $live = $this->output->isDecorated(); // in-place updates only on a real terminal
        $processed = 0;
        $failures = 0;

        $message = $auth->syncPermissions(function (string $event, string $registrar, ...$args) use (
            $live, &$processed, &$failures
        ) {
            switch ($event) {
                case 'start':
                    $processed++;
                    if ($live) {
                        $this->renderLine($registrar, '0%', 'fg=yellow', false);
                    }
                    break;

                case 'progress':
                    [$done, $total] = $args;
                    if ($live) {
                        $percent = $total > 0 ? (int) floor($done / $total * 100) : 100;
                        $this->renderLine($registrar, "{$percent}%", 'fg=yellow', false);
                    }
                    break;

                case 'done':
                    $this->renderLine($registrar, 'DONE', 'fg=green;options=bold', true);
                    break;

                case 'failed':
                    $failures++;
                    $this->renderLine($registrar, 'FAIL', 'fg=red;options=bold', true);
                    /** @var Throwable $e */
                    $e = $args[0];
                    $this->line('     <fg=red>'.OutputFormatter::escape($e->getMessage()).'</>');
                    break;

                case 'skipped':
                    $this->renderLine($registrar, 'SKIPPED', 'fg=yellow', true);
                    $this->line('     <fg=gray>Class does not exist.</>');
                    break;
            }
        });

        $this->newLine();

        if ($processed === 0) {
            $this->warn($message);
            return Command::SUCCESS;
        }

        $failures > 0 ? $this->error($message) : $this->info($message);

        return $failures > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Draw "  Registrar ........ STATUS". Non-final lines overwrite themselves.
     */
    protected function renderLine(string $registrar, string $status, string $style, bool $final): void
    {
        $width = min((new Terminal())->getWidth(), 100);
        $dots = str_repeat('.', max(3, $width - mb_strlen($registrar) - mb_strlen($status) - 5));

        $line = '  '.OutputFormatter::escape($registrar)." <fg=gray>{$dots}</> <{$style}>{$status}</>";

        if ($final) {
            // "\r\033[2K" returns to column 0 and clears any in-progress line.
            $this->output->write("\r\033[2K");
            $this->output->writeln($line);
            return;
        }

        $this->output->write("\r\033[2K".$line);
    }
}