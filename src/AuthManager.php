<?php

namespace Delgont\Armor;


use Delgont\Armor\Events\PermissionsSynchronized;



class AuthManager
{
    public function syncPermissions() : string
    {
        $permissions = config('armor.permission_registrars');

        if (is_array($permissions) && count($permissions) > 0) {
            foreach ($permissions as $permission) {
                // Check if the class exists
                if (class_exists($permission)) {
                    app($permission)->sync();
                } else {
                    // Log or handle the missing class if needed
                    \Log::warning("Permission registrar class {$permission} does not exist.");
                }
            }
            //PermissionsSynchronized Event Here
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
