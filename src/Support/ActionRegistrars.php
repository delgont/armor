<?php

namespace Delgont\Armor\Support;

use Delgont\Armor\Armor;
use Delgont\Armor\SystemActionRegistrar;

class ActionRegistrars
{
    /**
     * Resolve the registered action registrars.
     *
     * @return array{0: array<string, SystemActionRegistrar>, 1: string[], 2: string[]} instances, missing classes, wrong base class
     */
    public static function resolve(): array
    {
        $instances = [];
        $absent = [];
        $invalid = [];

        $classes = array_unique(array_map(
            fn ($c) => is_string($c) ? $c : json_encode($c),
            Armor::getActionRegistrars()
        ));

        foreach ($classes as $class) {
            if (! class_exists($class)) {
                $absent[] = $class;
            } elseif (! is_subclass_of($class, SystemActionRegistrar::class)) {
                $invalid[] = $class;
            } else {
                $instances[$class] = app($class);
            }
        }

        return [$instances, $absent, $invalid];
    }

    /** The group name the registrar's sync writes. */
    public static function groupName(SystemActionRegistrar $registrar): string
    {
        return $registrar->getGroup() ?? $registrar::class;
    }

    /** String action keys only, in declaration order. */
    public static function keys(SystemActionRegistrar $registrar): array
    {
        return array_values(array_filter($registrar->getActions(), 'is_string'));
    }
}