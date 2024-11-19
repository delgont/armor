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
    protected $description = 'Install the Armor package and run its migrations, configurations, and other tasks.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Welcome to the Armor installation process!');
        $this->info('This command will set up the necessary database tables and configurations for Armor.');

        // Step 1: Confirm running migrations
        if ($this->confirm('Do you wish to proceed with running the database migrations?', true)) {
            $this->runMigrations();
        } else {
            $this->warn('Skipping database migrations.');
        }

        // Step 2: Confirm seeding roles and permissions
        if ($this->confirm('Do you wish to sync the roles and permissions?', true)) {
            $this->syncRolesAndPermissions();
        } else {
            $this->warn('Skipping roles and permissions sync.');
        }

        // Step 3: Confirm additional configurations (if any)
        if ($this->confirm('Do you wish to perform additional configuration steps?', true)) {
            $this->performAdditionalConfig();
        } else {
            $this->warn('Skipping additional configuration.');
        }

        $this->info('Armor installation process completed!');
        return Command::SUCCESS;
    }

    /**
     * Run migrations for the Armor package.
     *
     * @return void
     */
    protected function runMigrations()
    {
        $this->info('Running Armor migrations...');
        $migrationsPath = config('app.armor') == 'dev'
            ? 'delgont/armor/database/migrations'
            : 'vendor/delgont/armor/database/migrations';

        $this->info("Using migration path: $migrationsPath");

        // Start progress bar
        $this->output->progressStart(3);
        $this->output->progressAdvance();
        
        // Run migrations
        $this->call('migrate', [
            '--path' => $migrationsPath,
        ]);
        $this->output->progressAdvance();

        $this->output->progressFinish();
        $this->info('Migrations completed successfully!');
    }

    /**
     * Sync roles and permissions for Armor.
     *
     * @return void
     */
    protected function syncRolesAndPermissions()
    {
        $this->info('Syncing Armor roles and permissions...');
        $this->call('permissions:sync');
        $this->info('Roles and permissions synced successfully!');
    }

    /**
     * Perform additional configuration steps.
     *
     * @return void
     */
    protected function performAdditionalConfig()
    {
        $this->info('Performing additional configuration steps...');
        // Insert any additional configuration tasks here
        // For example, setting up user accounts or other package-specific setup
        $this->info('Additional configuration completed!');
    }
}
