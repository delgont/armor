<?php

namespace Delgont\Armor\Console\Commands;

use Delgont\Armor\Armor;
use Delgont\Armor\Models\Role;
use Delgont\Armor\Models\RoleGroup;
use Delgont\Armor\Support\PermissionCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RolesCleanCommand extends Command
{
    protected $signature = 'armor:roles:clean
                            {--dry-run : List what would be removed without deleting}
                            {--include-unmanaged : Also consider roles that are not in a registrar-managed group}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Remove roles from the database that are no longer defined in code';

    public function handle(PermissionCache $cache): int
    {
        foreach ((array) config('armor.role_registrars', []) as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                $this->error('Registrar is missing: '.json_encode($class).'. Fix the config first so nothing is deleted by mistake.');
                return self::FAILURE;
            }
        }

        $defined = Armor::definedRoleNames();

        if ($defined === []) {
            $this->error('No roles are defined in code. Refusing to remove everything.');
            return self::FAILURE;
        }

        $query = Role::query()->whereNotIn('name', $defined);

        if (! $this->option('include-unmanaged')) {
            $query->whereIn('role_group_id', RoleGroup::query()->whereNotNull('registrar')->select('id'));
        }

        $stale = $query->get(['id', 'name']);

        if ($stale->isEmpty()) {
            $this->info('Nothing to clean. The database matches the code.');
            return self::SUCCESS;
        }

        $members = DB::table('model_has_roles')->whereIn('role_id', $stale->pluck('id'))->groupBy('role_id')->pluck(DB::raw('count(*)'), 'role_id');

        $this->table(['Role', 'Members who lose it'], $stale->map(fn ($r) => [$r->name, $members[$r->id] ?? 0])->all());

        if ($this->option('dry-run')) {
            $this->warn($stale->count().' role(s) would be removed. Nothing was changed.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Remove '.$stale->count().' role(s)? Members lose them immediately.', false)) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        DB::transaction(fn () => Role::whereIn('id', $stale->pluck('id'))->delete());   // pivots cascade through foreign keys

        $cache->flushAll();

        $this->info($stale->count().' role(s) removed and caches refreshed.');

        return self::SUCCESS;
    }
}