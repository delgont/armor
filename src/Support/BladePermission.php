<?php

namespace Delgont\Armor\Support;

use Illuminate\Database\Eloquent\Model;

class BladePermission
{
    /**
     * @param  string|array  $permissions  'a', 'a|b' or ['a', 'b']
     * @param  Model|string|null  $subject  a model, a guard name, or null for the default guard's user
     */
    public static function check($permissions, $subject = null): bool
    {
        $subject = static::subject($subject);

        if (! $subject || ! method_exists($subject, 'hasAnyPermission')) {
            return false;
        }

        $names = static::names($permissions);

        return $names !== [] && $subject->hasAnyPermission(...$names);
    }

    protected static function subject($subject)
    {
        if ($subject instanceof Model) {
            return $subject;
        }

        return auth(is_string($subject) && $subject !== '' ? $subject : null)->user();
    }

    protected static function names($permissions): array
    {
        $delimiter = config('armor.permission_delimiter', '|');

        $list = is_array($permissions)
            ? $permissions
            : explode($delimiter, (string) $permissions);

        return array_values(array_filter(array_map('trim', $list), fn ($n) => $n !== ''));
    }
}