<?php

namespace Delgont\Armor;

use Illuminate\Support\ServiceProvider;

use Illuminate\Routing\Router;

use Delgont\Armor\Concerns\RegistersCommands;

use Delgont\Armor\Http\Middleware\PermissionMiddleware;
use Delgont\Armor\Http\Middleware\RolePermissionMiddleware;

use Delgont\Armor\Http\Middleware\RoleMiddleware;
use Delgont\Armor\Http\Middleware\UserTypeMiddleware;
use Delgont\Armor\Http\Middleware\PermissionViaSingleRole;
use Delgont\Armor\Http\Middleware\DetectIpChange;
use Delgont\Armor\Http\Middleware\CheckSuspended;


use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Support\Facades\Blade;

use Illuminate\Support\Str;


class ArmorServiceProvider extends ServiceProvider
{
    use RegistersCommands;
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        app('router')->aliasMiddleware('usertype', UserTypeMiddleware::class);
        app('router')->aliasMiddleware('check.suspended', CheckSuspended::class);

        $this->registerCommands();
        
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/armor.php' => config_path('armor.php')
        ], 'armor');

        $router = $this->app->make(Router::class);
        
        $router->aliasMiddleware('permission', PermissionMiddleware::class);
        $router->aliasMiddleware('role.permission', RolePermissionMiddleware::class);

        $router->aliasMiddleware('role', RoleMiddleware::class);
        $router->aliasMiddleware('hasRole', RoleMiddleware::class);
        $router->aliasMiddleware('permissionViaSingleRole', PermissionViaSingleRole::class);
        $router->aliasMiddleware('detect.ip.change', DetectIpChange::class);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerBladeExtensions();

        
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


    protected function registerBladeExtensions()
    {
        Blade::directive('hasRole', function ($arguments) {
            list($role, $guard) = explode(',', $arguments.',');
            return "<?php if(auth({$guard})->user()->hasRole({$role})): ?>";
        });

        Blade::directive('elsehasRole', function ($arguments) {
            list($role, $guard) = explode(',', $arguments.',');

            return "<?php elseif(auth({$guard})->user()->hasRole({$role})): ?>";
        });

        Blade::directive('endhasRole', function () {
            return '<?php endif; ?>';
        });


        //User Type Directive
        Blade::directive('usertype', function ($arguments) {
            list($usertypes, $guard) = explode(',', $arguments.',');
            return "<?php if(auth()->check() && in_array(Str::lower(end(explode('\\', auth({$guard})->user()->usertype))), explode('|', $usertypes))): ?>";
        });
    
        Blade::directive('elseusertype', function () {
            return '<?php else: ?>';
        });
    
        Blade::directive('endusertype', function () {
            return '<?php endif; ?>';
        });


        // New permission directive with delimiter support and default guard
        Blade::directive('can', function ($arguments) {
            list($permissions, $guard) = explode(',', $arguments.',');
            return "<?php if( auth({$guard})->check() && auth({$guard})->user()->hasAnyPermission(explode('|', {$permissions})) ): ?>";
        });

        Blade::directive('elsecan', function () {
            return '<?php else: ?>';
        });

        Blade::directive('endcan', function () {
            return '<?php endif; ?>';
        });


        // User role directive - check if the user
        Blade::directive('rolecan', function ($arguments) {
            list($permissions, $guard) = explode(',', $arguments.',');
            return "<?php if( auth({$guard})->check() && auth({$guard})->user()->role && auth({$guard})->user()->role->hasAnyPermission(explode('|', {$permissions})) ): ?>";
        });

        Blade::directive('elserolecan', function () {
            return '<?php else: ?>';
        });

        Blade::directive('endrolecan', function () {
            return '<?php endif; ?>';
        });





    }

  
}
