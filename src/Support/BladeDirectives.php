<?php

namespace Delgont\Armor\Support;

use Illuminate\Support\Facades\Blade;

class BladeDirectives
{
    public static function register(): void
    {
        static::permission();
        static::roles();
        static::role();
        static::can();
    }

    /** New: @permission, @elsepermission, @endpermission, @unlesspermission */
    protected static function permission(): void
    {
        Blade::if('permission', fn ($permissions, $subject = null) => BladePermission::check($permissions, $subject));
    }

    /** @role and @hasRole families. Behavior unchanged. */
    protected static function roles(): void
    {
        Blade::directive('role', function ($arguments) {
            [$role, $guard] = static::split($arguments);
            return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
        });

        Blade::directive('elserole', function ($arguments) {
            [$role, $guard] = static::split($arguments);
            return "<?php elseif(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
        });

        Blade::directive('endrole', fn () => '<?php endif; ?>');

        Blade::directive('hasRole', function ($arguments) {
            [$role, $guard] = static::split($arguments);
            return "<?php if(auth({$guard})->user()->hasRole({$role})): ?>";
        });

        Blade::directive('elsehasRole', function ($arguments) {
            [$role, $guard] = static::split($arguments);
            return "<?php elseif(auth({$guard})->user()->hasRole({$role})): ?>";
        });

        Blade::directive('endhasRole', fn () => '<?php endif; ?>');
    }

    /** @can and @rolecan families. Behavior unchanged. */
    protected static function can(): void
    {
        Blade::directive('can', function ($arguments) {
            [$permissions, $guard] = static::split($arguments);
            return "<?php if( auth({$guard})->check() && auth({$guard})->user()->hasAnyPermission(explode('|', {$permissions})) ): ?>";
        });

        Blade::directive('elsecan', fn () => '<?php else: ?>');
        Blade::directive('endcan', fn () => '<?php endif; ?>');

        Blade::directive('rolecan', function ($arguments) {
            [$permissions, $guard] = static::split($arguments);
            return "<?php if( auth({$guard})->check() && auth({$guard})->user()->role && auth({$guard})->user()->role->hasAnyPermission(explode('|', {$permissions})) ): ?>";
        });

        Blade::directive('elserolecan', fn () => '<?php else: ?>');
        Blade::directive('endrolecan', fn () => '<?php endif; ?>');
    }

    protected static function role() : void 
    {
        Blade::directive('role', function ($arguments) {
            list($role, $guard) = explode(',', $arguments.',');
            return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
        });

        Blade::directive('elserole', function ($arguments) {
            list($role, $guard) = explode(',', $arguments.',');

            return "<?php elseif(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
        });

        Blade::directive('endrole', function () {
            return '<?php endif; ?>';
        });
    }

    

    /** Split "'a', 'guard'" into [role-or-permissions, guard]. The guard defaults to ''. */
    protected static function split(string $arguments): array
    {
        return array_slice(explode(',', $arguments.','), 0, 2);
    }
}