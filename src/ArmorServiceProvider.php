<?php
/**
 * Delgont Armor  (https://delgont.co.ug).
 *
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace Delgont\Armor;

use Delgont\Armor\Concerns\RegistersCommands;
use Delgont\Armor\Events\AuditActivityLogged;
use Delgont\Armor\Http\Middleware\CheckSuspended;
use Delgont\Armor\Http\Middleware\DetectIpChange;
use Delgont\Armor\Http\Middleware\PermissionMiddleware;
use Delgont\Armor\Http\Middleware\PermissionViaSingleRole;
use Delgont\Armor\Http\Middleware\RoleMiddleware;
use Delgont\Armor\Http\Middleware\RolePermissionMiddleware;
use Delgont\Armor\Http\Middleware\TrackPageAccess;
use Delgont\Armor\Http\Middleware\UserTypeMiddleware;
use Delgont\Armor\Listeners\LogAuditActivity;
use Delgont\Armor\Models\Role;
use Delgont\Armor\Support\BladeDirectives;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ArmorServiceProvider extends ServiceProvider
{
    use RegistersCommands;

    /** Middleware alias => class. */
    protected array $middleware = [
        'permission'              => PermissionMiddleware::class,
        'role.permission'         => RolePermissionMiddleware::class,
        'role'                    => RoleMiddleware::class,
        'hasRole'                 => RoleMiddleware::class,
        'permissionViaSingleRole' => PermissionViaSingleRole::class,
        'detect.ip.change'        => DetectIpChange::class,
        'usertype'                => UserTypeMiddleware::class,
        'check.suspended'         => CheckSuspended::class,
        'track.page-access'       => TrackPageAccess::class,
    ];

    public function register()
    {
        $this->registerCommands();
    }

    public function boot()
    {
        // Package defaults. App values (published file, .env) take priority.
        $this->mergeConfigFrom(__DIR__.'/../config/armor.php', 'armor');

        $this->registerMiddleware();
        $this->registerMigrations();
        $this->registerEvents();
        $this->registerPermissionables();

        BladeDirectives::register();
    }


    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);
        foreach ($this->middleware as $alias => $class) {
            $router->aliasMiddleware($alias, $class);
        }
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function registerEvents(): void
    {
        Event::listen(AuditActivityLogged::class, LogAuditActivity::class);
    }

    protected function registerPermissionables(): void
    {
        Armor::registerPermissionables(['role' => Role::class]);
    }
}