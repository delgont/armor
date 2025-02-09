<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Delgont\Armor\Armor;

class ShowPermissionables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'armor:show-permissionables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display all registered permissionable classes';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $permissionables = Armor::getPermissionables();

        if (empty($permissionables)) {
            $this->info('No permissionables registered.');
            return;
        }

        $this->table(['Key', 'Class'], array_map(fn($key, $class) => [$key, $class], array_keys($permissionables), $permissionables));
    }
}
