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

use Delgont\Armor\Armor;
use Delgont\Armor\Models\Permission;
use Delgont\Armor\PermissionRegistrar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DoctorCommand extends Command
{
    protected $signature = 'armor:doctor
                        {--strict : Fail on warnings too}
                        {--config : Also print the effective configuration and where each value comes from}';

    protected $description = 'Check Armor configuration, cache and data for problems';

    private int $fails = 0;
    private int $warns = 0;

    public function handle(\Delgont\Armor\Support\ConfigInspector $config): int

    {
        $this->newLine();

        // ---- Configuration
        $this->title('Configuration');

        $findings = $config->validate();

        foreach ($findings as [$level, $message]) {
            $level === 'fail' ? $this->bad($message) : $this->caution($message);
        }

        $findings === [] ? $this->ok('Configuration is valid') : null;

        if ($this->option('config')) {
            $this->newLine();
            $this->table(['Key', 'Value', 'Source'], $config->snapshot());
        }

        // ---- Cache
        $this->title('Cache');

        $this->callSilently('armor:cache:check') === 0
            ? $this->ok('Cache store reads, writes and rotates tokens')
            : $this->bad('Cache store failed. Run: php artisan armor:cache:check');

        if (config('armor.cache_store', 'file') === 'file' && app()->environment('production')) {
            $this->caution('Cache store is "file" in production: revocations are not shared between servers');
        }

        // ---- Database
        $this->title('Database');

        foreach (['permission_groups', 'permissions', 'roles', 'role_groups', 'model_has_permissions', 'model_has_roles', 'role_has_permissions'] as $table) {
            Schema::hasTable($table) ? null : $this->bad("Table {$table} is missing");
        }

        if (Schema::hasTable('permission_groups') && ! Schema::hasColumn('permission_groups', 'parent_id')) {
            $this->caution('permission_groups.parent_id is missing: run the parent/child migration');
        }

        foreach (Armor::getPermissionables() as $alias => $class) {
            if (! class_exists($class)) {
                $this->bad("Permissionable '{$alias}' points at a missing class: {$class}");
                continue;
            }

            if (! method_exists($class, 'permissionNames')) {
                $this->bad("{$class} is registered as permissionable but does not use ModelHasPermissions");
                continue;
            }

            $model = new $class;
            $table = $model->getTable();
            $key = $model->getKeyName();
            $morph = $model->getMorphClass();

            foreach (['model_has_permissions', 'model_has_roles'] as $pivot) {
                if (! Schema::hasTable($pivot) || ! Schema::hasTable($table)) {
                    continue;
                }

                $orphans = DB::table($pivot)
                    ->where('model_type', $morph)
                    ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from($table)->whereColumn("{$table}.{$key}", "{$pivot}.model_id"))
                    ->count();

                $orphans > 0
                    ? $this->caution("{$pivot} has {$orphans} orphaned row(s) for {$alias}")
                    : $this->ok("{$pivot}: no orphaned rows for {$alias}");
            }
        }

        // ---- Permission definitions
        $this->title('Permission definitions');

        // Values follow one convention. Existing names are not renamed: a new value is a new permission.
        $notSnake = array_filter(Armor::definedPermissionNames(), fn ($n) => ! preg_match('/^[a-z][a-z0-9]*(_[a-z0-9]+)*$/', $n));
        $notSnake === []
            ? $this->ok('All permission names are snake_case')
            : $this->caution(count($notSnake).' permission name(s) are not snake_case, e.g. '.implode(', ', array_slice($notSnake, 0, 3)));

        // Two registrars sharing a group name fight over the group's registrar column.
        $byGroup = [];
        foreach (Armor::getPermissionRegistrars() as $class) {
            if (class_exists($class) && ($i = app($class)) instanceof PermissionRegistrar) {
                $byGroup[$i->groupName()][] = class_basename($class);
            }
        }
        foreach (array_filter($byGroup, fn ($c) => count($c) > 1) as $group => $classes) {
            $this->caution("Registrars ".implode(', ', $classes)." share the group '{$group}'.");
        }

        try {
            $all = Armor::definedPermissionNames(false);
            $duplicates = array_keys(array_filter(array_count_values($all), fn ($n) => $n > 1));

            $duplicates === []
                ? $this->ok(count($all).' permission name(s) declared, none duplicated')
                : $this->bad('Declared more than once: '.implode(', ', $duplicates));

            foreach (Armor::getPermissionRegistrars() as $class) {
                if (class_exists($class) && ($instance = app($class)) instanceof PermissionRegistrar && method_exists($instance, 'permissionDefinitions')) {
                    $missing = collect($instance->permissionDefinitions())->filter(fn ($d) => empty($d['description']))->count();
                    $missing > 0 ? $this->caution(class_basename($class)." has {$missing} permission(s) without a description") : null;
                }
            }

            if (Schema::hasTable('permissions')) {
                $stale = Permission::query()->whereNotIn('name', Armor::definedPermissionNames())->count();
                $stale > 0
                    ? $this->caution("{$stale} permission(s) in the database are not defined in code. See: armor:permissions:clean --dry-run")
                    : $this->ok('Database permissions all exist in code');
            }
        } catch (Throwable $e) {
            $this->bad('Could not read registrars: '.$e->getMessage());
        }

        $this->newLine();
        $this->line("  {$this->fails} failure(s), {$this->warns} warning(s)");
        $this->newLine();

        return ($this->fails > 0 || ($this->option('strict') && $this->warns > 0)) ? self::FAILURE : self::SUCCESS;
    }

    private function title(string $text): void
    {
        $this->newLine();
        $this->line("<options=bold>{$text}</>");
    }

    private function ok(string $text): void
    {
        $this->line("  <fg=green>OK</>    {$text}");
    }

    private function caution(string $text): void
    {
        $this->warns++;
        $this->line("  <fg=yellow>WARN</>  {$text}");
    }

    private function bad(string $text): void
    {
        $this->fails++;
        $this->line("  <fg=red>FAIL</>  {$text}");
    }
}