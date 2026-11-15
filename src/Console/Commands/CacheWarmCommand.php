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

use Delgont\Armor\Jobs\WarmPermissionCacheJob;
use Illuminate\Console\Command;

class CacheWarmCommand extends Command
{
    protected $signature = 'armor:cache:warm
                            {--model= : Only this permissionable (alias or class)}
                            {--sync : Run now instead of queueing}';

    protected $description = 'Pre-load cached permission sets for all permissionable models';

    public function handle(): int
    {
        $job = new WarmPermissionCacheJob($this->option('model'));

        if ($this->option('sync')) {
            $this->info('Warming permission cache...');
            dispatch_sync($job);
            $this->info('Done.');
        } else {
            dispatch($job);
            $this->info('Warm-up queued.');
        }

        return self::SUCCESS;
    }
}