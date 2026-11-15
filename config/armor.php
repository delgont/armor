<?php

/*
|--------------------------------------------------------------------------
| Delgont Armor configuration
|--------------------------------------------------------------------------
| This file is merged automatically, so you do not need to publish it.
| Every value is a default. Override one in any of three ways:
|
|   1. .env                 settings that differ per environment
|   2. config/armor.php     publish with: php artisan vendor:publish --tag=armor-config
|                           (keep only the keys you change)
|   3. A service provider   registrars and permissionables, via the Armor class
|
| Laravel merges config one level deep: a list you define (for example
| 'permission_registrars') replaces the default list instead of adding to it.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    | Permission sets are cached per model so checks avoid database queries.
    | In production with more than one server, use a shared store (redis or
    | memcached). With "file", a revoked permission can stay active on
    | servers that did not process the change.
    */

    // Cache store name from config/cache.php.
    'cache_store' => env('ARMOR_CACHE_DRIVER', 'file'),

    // Seconds an unused cached set is kept. Freshness does not depend on it:
    // every change rotates a version token, so a long value is safe.
    'cache_duration' => env('ARMOR_CACHE_DURATION', 3600),

    /*
    |--------------------------------------------------------------------------
    | Permission checks
    |--------------------------------------------------------------------------
    */

    // How hasPermissionTo() treats roles:
    //   direct     only permissions given to the model itself (default, safe)
    //   shadow     same answers as "direct"; logs where roles would differ
    //   effective  direct permissions plus the permissions of its roles
    // Move to "effective" only after reviewing the shadow log.
    'permission_mode' => env('ARMOR_PERMISSION_MODE', 'direct'),

    // Separator for several permissions in one string, e.g. 'can_add|can_edit'.
    // Read by the permission middleware and @permission.
    'permission_delimiter' => '|',

    // Reserved for several roles in one string. Role checks currently
    // split on '|' regardless of this value.
    'role_delimiter' => '|',

    /*
    |--------------------------------------------------------------------------
    | Registrars
    |--------------------------------------------------------------------------
    | Classes that declare permissions, roles and actions as constants.
    | Leave these empty here and register your own, either by listing them
    | below in a published file, or in a service provider:
    |
    |     Armor::registerPermissionRegistrars([\App\Permissions\UserPermissionRegistrar::class]);
    |     Armor::registerRoleRegistrars([\App\Roles\StaffRoleRegistrar::class]);
    |     Armor::registerActionRegistrars([\App\Actions\UserActionRegistrar::class]);
    |
    | Whatever is listed is written to your database when you sync, so
    | never list example classes.
    */

    'permission_registrars' => [],

    'role_registrars' => [],

    'action_registrars' => [],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    */

    // Models that can hold permissions, as alias => class. Armor registers
    // 'role' itself. Add yours, e.g. 'user' => \App\User::class.
    'permissionables' => [],

    // Used by the user-type feature (HasUserTypes). Leave as is unless you
    // know you do not use it.
    'add_usertype_to_users_model' => true,

    /*
    |--------------------------------------------------------------------------
    | Account protection
    |--------------------------------------------------------------------------
    */

    // Where CheckSuspended sends a suspended user: a route name or a path.
    // Do not call route() here. Routes are not loaded when config is read.
    'redirect_suspended' => null,

    /*
    |--------------------------------------------------------------------------
    | Debugging
    |--------------------------------------------------------------------------
    */

    // Show the @whoami panel (signed-in user, roles, permissions).
    // Never renders in production, whatever this is set to.
    'whoami' => env('ARMOR_WHOAMI', false),

    // Log permission checks made by guests, once per request per permission.
    // A log line on a page that should require sign-in means a missing auth
    // middleware.
    'log_guest_checks' => env('ARMOR_LOG_GUEST_CHECKS', false),

];