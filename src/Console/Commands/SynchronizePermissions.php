<?php
namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Delgont\Armor\AuthManager;

class SynchronizePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'armor:sync-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync permissions with the system';

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
        $this->info('You are about to sync the permissions to your system.');

        // Ask for user confirmation before proceeding
        if ($this->confirm('Do you wish to proceed with syncing the permissions?', true)) {

            // If the user confirms, proceed to sync permissions
            $this->info('Syncing permissions...');

            // Call the method to sync permissions
            app(AuthManager::class)->syncPermissions();

            // Provide feedback to the user
            $this->info('Permissions have been successfully synced.');

        } else {
            // If the user cancels, provide a message
            $this->info('Permission sync operation has been canceled.');
        }

        return Command::SUCCESS;
    }
}
