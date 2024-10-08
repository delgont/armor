<?php

namespace Delgont\Armor;

class AuthManager
{
    public function syncPermissions() : string
    {
        $permissions =  config('armor.permission_registrars');
        
        if (is_array($permissions) && count($permissions) > 0) {
            foreach ($permissions as $permission) {
                app($permission)->sync();
            }
            return 'Permissions successfully synchronized';
        }else{
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
