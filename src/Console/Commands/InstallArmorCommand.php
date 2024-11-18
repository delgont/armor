<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;

class InstallArmorCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'armor:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Armor package and run its migrations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Armor installation...');

        // Use the relative path for migrations - vendor at start for deployment
        if (config('app.armor') == 'dev') {
         $migrationsPath = 'delgont/armor/database/migrations';
        } else {
         $migrationsPath = 'vendor/delgont/armor/database/migrations';
        }
        
        $this->call('migrate', [
            '--path' => $migrationsPath,
        ]);

        $this->info('Armor installation completed!');
        return Command::SUCCESS;
    }
}
