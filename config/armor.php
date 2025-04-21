<?php

return [

    'cache_store' => env('ARMOR_CACHE_DRIVER','file'),

    'add_usertype_to_users_model' => true,


    /**
     * Permission configuration
     */
    'permission_delimiter' => '|',

    'permission_registrars' => [
      /*
      * your permission registrars - have permission constants
      */
      App\Permissions\ExamplePermissionRegistrar::class,
    ],

    /**
     * Role Configuration
     */

    'role_delimiter' => '|',

    'role_registrars' => [
      /*
      * your permission registrars - have permission constants
      */
      App\Roles\ExampleRoleRegistrar::class,
    ],

    'role_registrars' => [
      /*
      * your permission registrars - have permission constants
      */
      App\Roles\ExampleRoleRegistrar::class,

      'redirect_suspended' => route('account.suspended')
    ],

    'permissionables' => [
    ]

];
