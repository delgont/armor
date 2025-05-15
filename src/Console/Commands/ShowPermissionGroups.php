<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Delgont\Armor\Models\PermissionGroup;

class ShowPermissionGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'armor:show-permission-groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $groups = PermissionGroup::all();

        if ($groups->isEmpty()) {
            $this->info('No permissions found');
            return 0;
        }

        //Prepare roles data for table display
        $groupsArray = $groups->map(function ($group) {
            return [
                'ID' => $group->id,
                'Group Name' => $group->name,
            ];
        })->toArray();

        //Display the table
        $this->line('');
        $this->table(['ID', 'Group Name'], $groupsArray);
        $this->line('');

        return 0;
    }
}
