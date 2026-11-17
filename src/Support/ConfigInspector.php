<?php

namespace Delgont\Armor\Support;

use Delgont\Armor\Armor;
use Delgont\Armor\Permission;
use Delgont\Armor\PermissionRegistrar;
use Delgont\Armor\RoleRegistrar;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

class ConfigInspector
{
    private const MODES = ['direct', 'shadow', 'effective'];

    /** Config key => the .env variable that feeds it. Used only to label a value's source. */
    private const ENV = [
        'cache_store'      => 'ARMOR_CACHE_DRIVER',
        'cache_duration'   => 'ARMOR_CACHE_DURATION',
        'permission_mode'  => 'ARMOR_PERMISSION_MODE',
        'whoami'           => 'ARMOR_WHOAMI',
        'log_guest_checks' => 'ARMOR_LOG_GUEST_CHECKS',
    ];

    /** The package's own defaults, as shipped. */
    public function defaults(): array
    {
        return require __DIR__.'/../../config/armor.php';
    }

    /**
     * Problems with the effective config.
     * 'fail' = something is broken or unsafe. 'warn' = probably a mistake.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function validate(): array
    {
        $out = [];
        $add = function (string $level, string $message) use (&$out) {
            $out[] = [$level, $message];
        };

        $cfg = config('armor', []);

        // Unknown keys are almost always typos that silently do nothing.
        foreach (array_diff(array_keys($cfg), array_keys($this->defaults())) as $key) {
            $add('warn', "Unknown key '{$key}' in the armor config (typo?).");
        }

        // Cache
        $store = $cfg['cache_store'] ?? null;
        if (! is_string($store) || ! array_key_exists($store, config('cache.stores', []))) {
            $add('fail', 'cache_store '.json_encode($store).' is not defined in config/cache.php.');
        }

        $ttl = $cfg['cache_duration'] ?? null;
        if (! is_numeric($ttl) || (int) $ttl <= 0) {
            $add('fail', 'cache_duration must be a positive number of seconds, got '.json_encode($ttl).'.');
        } elseif ((int) $ttl < 60) {
            $add('warn', "cache_duration is {$ttl}s. Very short values defeat the cache.");
        }

        // Permission mode: a typo silently behaves as 'direct', so a typo for 'effective' looks like a role bug.
        $mode = $cfg['permission_mode'] ?? null;
        if (! in_array($mode, self::MODES, true)) {
            $add('fail', 'permission_mode '.json_encode($mode).' is invalid and behaves as "direct". Use: '.implode(', ', self::MODES).'.');
        }

        // Delimiters, and names that could never be matched because they contain one.
        foreach (['permission_delimiter' => 'definedPermissionNames', 'role_delimiter' => 'definedRoleNames'] as $key => $source) {
            $delimiter = $cfg[$key] ?? null;

            if (! is_string($delimiter) || $delimiter === '') {
                $add('fail', "{$key} must be a non-empty string.");
                continue;
            }

            try {
                foreach (Armor::{$source}() as $name) {
                    if (str_contains($name, $delimiter)) {
                        $add('fail', "'{$name}' contains the {$key} '{$delimiter}', so it can never be matched in a list.");
                    }
                }
            } catch (Throwable) {
                // A broken registrar is reported by the registrar checks below.
            }
        }

        // Registrars
        $this->registrars('permission_registrars', [PermissionRegistrar::class, Permission::class], $add);
        $this->registrars('role_registrars', [RoleRegistrar::class], $add);
        $this->registrars('action_registrars', [\Delgont\Armor\SystemActionRegistrar::class], $add);

        // Permissionables: shape only. The trait check lives in the doctor's database section.
        $permissionables = $cfg['permissionables'] ?? [];
        if (! is_array($permissionables)) {
            $add('fail', 'permissionables must be an array of alias => class.');
        } else {
            foreach ($permissionables as $alias => $class) {
                if (! is_string($class) || ! class_exists($class)) {
                    $add('fail', "permissionables['{$alias}'] is not an existing class: ".json_encode($class));
                }
            }
        }

        // Suspended redirect: a path, a URL or a registered route name.
        $redirect = $cfg['redirect_suspended'] ?? null;
        if ($redirect !== null) {
            $isPath = is_string($redirect) && (str_starts_with($redirect, '/') || str_starts_with($redirect, 'http'));

            if (! is_string($redirect)) {
                $add('fail', 'redirect_suspended must be null, a path or a route name.');
            } elseif (! $isPath && ! Route::has($redirect)) {
                $add('warn', "redirect_suspended '{$redirect}' is neither a path nor a known route name.");
            }
        }

        // Booleans: env() already turns "true"/"false" into booleans, so anything else is a mistake.
        foreach (['whoami', 'log_guest_checks', 'add_usertype_to_users_model'] as $key) {
            if (array_key_exists($key, $cfg) && ! is_bool($cfg[$key])) {
                $add('warn', "{$key} should be true or false, got ".json_encode($cfg[$key]).'.');
            }
        }

        if (($cfg['whoami'] ?? false) === true && app()->isProduction()) {
            $add('warn', 'whoami is on in production. The panel never renders there, so the setting has no effect.');
        }

        return $out;
    }

    /**
     * Every setting with its effective value and where it came from.
     *
     * @return array<int, array{0: string, 1: string, 2: string}> [key, value, source]
     */
    public function snapshot(): array
    {
        $defaults = $this->defaults();
        $cached = app()->configurationIsCached();   // env() is unavailable outside config files when cached
        $rows = [];

        foreach (config('armor', []) as $key => $value) {
            $envName = self::ENV[$key] ?? null;
            $envSet = $envName && ! $cached && env($envName) !== null;

            $source = match (true) {
                ! array_key_exists($key, $defaults) => 'unknown key',
                $value === $defaults[$key]          => $envSet ? '.env' : 'default',
                $cached                             => 'overridden (config is cached)',
                $envSet                             => '.env',
                default                             => 'app config or provider',
            };

            $rows[] = [$key, $this->display($key, $value), $source];
        }

        return $rows;
    }

    /** Check one list of registrar classes. */
    private function registrars(string $key, array $bases, callable $add): void
    {
        $list = config("armor.{$key}", []);

        if (! is_array($list)) {
            $add('fail', "{$key} must be an array.");
            return;
        }

        foreach ($list as $index => $class) {
            // A string key means a setting was pasted inside the list (it happened with redirect_suspended).
            if (is_string($index)) {
                $add('fail', "{$key} has the string key '{$index}'. It must be a plain list of class names. If '{$index}' is a setting, move it to the top level.");
                continue;
            }

            if (! is_string($class) || ! class_exists($class)) {
                $add('fail', "{$key}[{$index}] is not an existing class: ".json_encode($class));
                continue;
            }

            if ($bases !== [] && ! array_filter($bases, fn ($b) => is_subclass_of($class, $b))) {
                $add('warn', "{$class} in {$key} does not extend ".class_basename($bases[0]).'.');
            }

            if (str_starts_with(class_basename($class), 'Example')) {
                $add('warn', "{$key} lists an example registrar ({$class}). It is written to your database on sync.");
            }
        }

        foreach (array_keys(array_filter(array_count_values(array_filter($list, 'is_string')), fn ($n) => $n > 1)) as $class) {
            $add('warn', "{$key} lists {$class} more than once.");
        }
    }

    /** Short, safe text for a table cell. */
    private function display(string $key, mixed $value): string
    {
        if (preg_match('/key|secret|token|password/i', $key)) {
            return '(hidden)';   // future-proofing: never print credentials
        }

        return match (true) {
            is_bool($value)  => $value ? 'true' : 'false',
            $value === null  => 'null',
            is_array($value) => $value === [] ? '[] (empty)' : Str::limit(json_encode($value, JSON_UNESCAPED_SLASHES), 70),
            default          => (string) $value,
        };
    }
}