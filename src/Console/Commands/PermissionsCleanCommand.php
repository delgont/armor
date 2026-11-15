<?php

namespace Delgont\Armor\Console\Commands;

use Delgont\Armor\Armor;
use Delgont\Armor\Models\Permission;
use Delgont\Armor\Models\PermissionGroup;
use Delgont\Armor\Support\PermissionCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class PermissionsCleanCommand extends Command
{
    protected $signature = 'armor:permissions:clean
                            {--dry-run : List what would be removed without deleting}
                            {--include-unmanaged : Also consider permissions whose group has no registrar, or that have no group}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Remove permissions from the database that are no longer defined in code';

    public function handle(PermissionCache $cache): int
    {
        // 1. Refuse to act on an incomplete picture of "what is in code".
        foreach (Armor::getPermissionRegistrars() as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                $this->error('Registrar is missing: '.json_encode($class).'. Fix the config first so nothing is deleted by mistake.');
                return self::FAILURE;
            }
        }

        try {
            $defined = Armor::definedPermissionNames();
        } catch (Throwable $e) {
            $this->error('A registrar failed to load: '.$e->getMessage());
            return self::FAILURE;
        }

        if ($defined === []) {
            $this->error('No permissions are defined in code. Refusing to remove everything.');
            return self::FAILURE;
        }

        // 2. Stale = in the database, not in code. Managed groups only, by default.
        $query = Permission::query()->whereNotIn('name', $defined);

        if (! $this->option('include-unmanaged')) {
            $query->whereIn('permission_group_id', PermissionGroup::query()->whereNotNull('registrar')->select('id'));
        }

        $stale = $query->get(['id', 'name', 'permission_group_id']);

        if ($stale->isEmpty()) {
            $this->info('Nothing to clean. The database matches the code.');
            return self::SUCCESS;
        }

        // 3. Show who would lose access.
        $ids = $stale->pluck('id');
        $direct = DB::table('model_has_permissions')->whereIn('permission_id', $ids)->groupBy('permission_id')->pluck(DB::raw('count(*)'), 'permission_id');
        $roles = DB::table('role_has_permissions')->whereIn('permission_id', $ids)->groupBy('permission_id')->pluck(DB::raw('count(*)'), 'permission_id');

        $this->table(
            ['Permission', 'Direct holders', 'Roles'],
            $stale->map(fn ($p) => [$p->name, $direct[$p->id] ?? 0, $roles[$p->id] ?? 0])->all()
        );

        if ($this->option('dry-run')) {
            $this->warn($stale->count().' permission(s) would be removed. Nothing was changed.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Remove '.$stale->count().' permission(s)? Holders lose them immediately.', false)) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        // 4. Delete, then rotate the epoch. Bulk deletes skip model events.
        DB::transaction(function () use ($ids) {
            foreach ($ids->chunk(500) as $chunk) {
                Permission::whereIn('id', $chunk)->delete();   // pivots cascade through foreign keys
            }
        });

        $cache->flushAll();

        $this->info($stale->count().' permission(s) removed and caches refreshed.');

        return self::SUCCESS;
    }
}