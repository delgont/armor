<?php
/**
 * Delgont Armor
 *
 * @link      https://github.com/delgont/armor
 * @copyright Copyright (c) 2026 Delgont Technologies Co. Ltd
 * @license   MIT License (https://opensource.org/licenses/MIT)
 *
 */

namespace Delgont\Armor;

class Armor
{
    protected static $permissionables = [];

    /**
     * Register permissionable classes dynamically.
     *
     * @param array|string $classes
     */
    public static function registerPermissionables($classes): void
    {
        $classes = is_array($classes) ? $classes : [$classes];
        foreach ($classes as $key => $class) {
            static::$permissionables[is_numeric($key) ? $class : $key] = $class;
        }
    }

    /**
     * Resolve a permissionable class by key or full class name.
     *
     * @param string $key
     * @return string|null
     */
    public static function resolvePermissionable(string $key): ?string
    {
        // Merge registered and config-defined permissionables
        $permissionables = array_merge(
            config('armor.permissionables', []),
            static::$permissionables
        );

        // Return by key (e.g., 'user') or directly if the class name matches
        return $permissionables[$key] ?? (in_array($key, $permissionables) ? $key : null);
    }

    /**
     * Get all registered permissionable classes.
     *
     * @return array
     */
    public static function getPermissionables(): array
    {
        return array_merge(config('armor.permissionables', []), static::$permissionables);
    }


    /**
     * Register permission registrar classes dynamically.
     *
     * @param array|string $registrars
     */
    public static function registerPermissionRegistrars(array|string $registrars): void
    {
        static::appendToConfig('armor.permission_registrars', $registrars);
    }

    public static function registerActionRegistrars(array|string $registrars): void
    {
        static::appendToConfig('armor.action_registrars', $registrars);
    }

    /**
     * Get all permissions from all registered registrars.
     *
     * @return array
     */
    public static function getPermissions() : array
    {
        $registrars = config('armor.permission_registrars', []);
        $permissions = [];

        foreach ($registrars as $registrar) {
            if (class_exists($registrar)) {
                $instance = app($registrar);
                $permissions = array_merge($permissions, $instance->getPermissions());
            }
        }

        return $permissions;
    }

     /**
     * Get all registered permission registrar classes.
     *
     * @return array
     */
    public static function getPermissionRegistrars(): array
    {
        return config('armor.permission_registrars', []);
    }

     /**
     * Registered action registrars.
     */
    protected static array $actionRegistrars = [];


    /**
     * Get registered action registrars.
     *
     * @return array
     */
    public static function getActionRegistrars(): array
    {
        return config(
            'armor.action_registrars',
            []
        );
    }

    /**
     * Build the registrar hierarchy as a tree.
     *
     * @return array<int, array{registrar: string, group: string, permissions: array, children: array}>
     */
    public static function getPermissionTree(): array
    {
        $nodes = [];
        $queue = array_values(array_unique(static::getPermissionRegistrars()));

        while (($class = array_shift($queue)) !== null) {
            if (isset($nodes[$class]) || ! class_exists($class)) {
                continue;
            }

            $instance = app($class);
            $isRegistrar = $instance instanceof PermissionRegistrar;
            $parent = $isRegistrar ? $instance->getParent() : null;

            $nodes[$class] = [
                'registrar'   => $class,
                'group'       => $isRegistrar ? $instance->groupName() : ($instance->getGroup() ?? $class),
                'permissions' => $instance->getPermissions(),
                'parent'      => $parent,
            ];

            if ($parent !== null) {
                $queue[] = $parent; // pull in unregistered ancestors
            }
        }

        $childrenOf = [];
        $roots = [];

        foreach ($nodes as $class => $node) {
            $parent = $node['parent'];

            if ($parent !== null && isset($nodes[$parent])) {
                $childrenOf[$parent][] = $class;
            } else {
                $roots[] = $class;
            }
        }

        $build = function (string $class) use (&$build, $nodes, $childrenOf): array {
            $node = $nodes[$class];
            unset($node['parent']);

            $node['children'] = array_map($build, $childrenOf[$class] ?? []);

            return $node;
        };

        return array_map($build, $roots);
    }

    /**
     * Every permission name declared by the configured registrars
     * and by their declared parents.
     *
     * @return string[]
     */
    public static function definedPermissionNames(bool $unique = true): array
    {
        $names = [];
        $seen = [];
        $queue = array_values(array_unique(static::getPermissionRegistrars()));

        while (($class = array_shift($queue)) !== null) {
            if (isset($seen[$class]) || ! class_exists($class)) {
                continue;
            }

            $seen[$class] = true;
            $instance = app($class);

            foreach ($instance->getPermissions() as $value) {
                $names[] = $value;
            }

            if ($instance instanceof PermissionRegistrar && $instance->getParent() !== null) {
                $queue[] = $instance->getParent();
            }
        }

        return $unique ? array_values(array_unique($names)) : $names;
    }

    /** @return string[] */
    public static function definedRoleNames(): array
    {
        $names = [];

        foreach (array_filter((array) config('armor.role_registrars', []), 'is_string') as $class) {
            if (class_exists($class) && is_subclass_of($class, RoleRegistrar::class)) {
                array_push($names, ...app($class)->roleNames());
            }
        }

        return array_values(array_unique($names));
    }

    /** Register role registrar classes, like permission registrars. */
    public static function registerRoleRegistrars(array|string $registrars): void
    {
        static::appendToConfig('armor.role_registrars', $registrars);
    }

    /** @return string[] */
    public static function getRoleRegistrars(): array
    {
        return config('armor.role_registrars', []);
    }

    /** Append to a config list, skipping duplicates. */
    protected static function appendToConfig(string $key, array|string $items): void
    {
        $items = is_array($items) ? $items : [$items];

        config([$key => array_values(array_unique(array_merge(config($key, []), $items)))]);
    }

}
