<?php

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
}
