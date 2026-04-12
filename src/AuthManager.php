<?php
/**
 * Delgont Armor  (https://delgont.co.ug).
 *
 * @link https://github.com/delgont/armor source repository
 *
 * @copyright Copyright (c) 2024. Delgont Technologies Co. Ltd (https://delgont.co.ug)
 *
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace Delgont\Armor;


use Delgont\Armor\Events\PermissionsSynchronized;


class AuthManager
{
    public function syncPermissions() : string
    {
        $registrars = config('armor.permission_registrars');
        $allPermissions = [];

        if (is_array($registrars) && count($registrars) > 0) {
            foreach ($registrars as $registrar) {
                if (class_exists($registrar)) {
                    $instance = app($registrar);
                    $instance->sync();

                    // Collect permissions from this registrar
                    $allPermissions = array_merge($allPermissions, $instance->getPermissions());
                } else {
                    \Log::warning("Permission registrar class {$registrar} does not exist.");
                }
            }

            // Dispatch event with actual permissions
            event(new PermissionsSynchronized($allPermissions));

            return 'Permissions successfully synchronized';
        } else {
            return 'There are no permissions to sync';
        }
    }


    public function syncRoles() : string
    {
        $roles =  config('armor.role_registrars');
        if (is_array($roles) && count($roles) > 0) {
            foreach ($roles as $role) {
                app($role)->sync();
            }
            return 'roles synchronized successfully';
        }else{
            return 'no roles to sync';
        }
    }

}
