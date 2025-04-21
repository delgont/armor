<?php

namespace Delgont\Armor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Delgont\Armor\Models\Permission;

class ArmorCachePermissionsCommand extends Command
{
    protected $signature = 'armor:cache-permissions';
    protected $description = 'Cache all permissions for the Armor system';

    public function handle()
    {
        $cacheDuration = config('armor.cache_duration', 60); // in minutes
        $cacheStore = config('armor.cache_store', 'file');

        // Cache the permissions collection
        Cache::store($cacheStore)->remember('permissions', $cacheDuration, function () {
            return Permission::all();
        });

        $this->info('✔ All permissions have been cached successfully.');
    }
}
