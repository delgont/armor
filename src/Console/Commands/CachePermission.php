<?php
/**
 * Delgont Armor
 *
 * @link      https://github.com/delgont/armor
 * @copyright Copyright (c) 2024 - Present Delgont Technologies Co. Ltd
 * @license   MIT License (https://opensource.org/licenses/MIT)
 *
 */

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;

class CachePermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return 0;
    }
}
