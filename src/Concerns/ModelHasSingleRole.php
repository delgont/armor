<?php

namespace Delgont\Armor\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Delgont\Armor\Exceptions\RoleDoesNotExist;

use Delgont\Armor\Models\Role;


trait ModelHasSingleRole
{
    /**
     * A model may have a role.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }


    /**
     * Determine if the model has the given role
     *
     * @param string|int|array
     * @return bool
     */
    public function hasRole($role): bool
    {
        //user does not have any role asigned to 
        if(is_null($this->role)){
            return false;
        }

        if(is_string($role)){
            return ($this->role->name == $role) ? true : false;
        }

        if(is_int($role)){
            return ($this->role->id == $role) ? true : false;
        }

        if(is_array($role)){
            return (in_array($this->role->name, $role)) ? true : false;
        }
        return false;
    }


    /**
     * Assign the given role to the model.
     *
     * @param array|string|int
     * @return $this
     */
    public function assignRole($role)
    {
        $role_id = null;

        if ($role instanceof Role) {
            $role_id = $role->getKey();
        }

        if(is_numeric($role)){
            $role_id = $role;
         }

        if(is_string($role)){
            $roleModel = Role::whereName($role)->first();
            if ($roleModel) {
                $role_id = $roleModel->getKey();
            } else {
                throw RoleDoesNotExist::named($role);
            }
            
        }

        $model = $this->getModel();

        if($model->exists){
            $this->role_id = $role_id;
            $this->save();
        }
        return $this;
    }


    public function hasPermissionViaSingleRole($permission) : bool
    {
        if (!$this->role) {
            return false;
        }

        return ($this->role->hasPermissionTo($permission)) ? true : false;

    }


    

   
}