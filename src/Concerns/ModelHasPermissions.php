<?php

namespace Delgont\Armor\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Delgont\Armor\Models\Permission;

use Delgont\Armor\Exceptions\PermissionDoesNotExist;


trait ModelHasPermissions
{

    public static function bootHasPermissions()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }
            $model->permissions()->detach();
        });
    }

    /**
     * A model may have multiple permissions.
     */
    public function permissions() : BelongsToMany
    {
        return $this->morphToMany(Permission::class, 'model', 'model_has_permissions', 'model_id', 'permission_id');
    }

     /**
     * Determine if the model has, via roles, the given permission.
     *
     * @param \Delgont\Armor\Contracts\Permission $permission
     *
     * @return bool
     */
    public function hasPermissionViaRole() : string
    {
        return 'hello';
    }

    /**
     * Determine if the model has the given permission.
     *
     * @return bool
     * @throws PermissionDoesNotExist
     */
    public function hasPermission($permission) : bool
    {
        if (is_string($permission)) {
            $permission = Permission::whereName($permission)->first();
        }

        if (is_int($permission)) {
            $permission = Permission::whereId($permission)->first();
        }

        if (!$permission instanceof Permission) {
            throw new PermissionDoesNotExist();
        }

        return $this->permissions->contains($permission->getKeyName(), $permission->getKey());
    }


    public function givePermissionTo(...$permissions)
    {
        $permissions = collect($permissions)->flatten()->reduce(function($array, $permission){
            if ($permission instanceof Permission) {
                array_push($array, $permission->getKey());
            }

            if(is_numeric($permission)){
                array_push( $array, $permission );
            }

            if(is_string($permission)){
                $permissionModel = Permission::whereName($permission)->first();
                if ($permissionModel) {
                    array_push($array, $permissionModel->getKey());
                } else {
                    throw PermissionDoesNotExist::create($permission);
                }
                
            }

            return $array;
        }, []);

        $model = $this->getModel();
        if($model->exists){
            $this->permissions()->sync($permissions, true);
        }
        return $this;
    }


    public function withdrawPermissionsTo(...$permissions)
    {
        return $this;
    }


     /**
     * Determine if the model may perform the given permission.
     *
     * @param string|int$permission 
     *
     * @return bool
     * @throws PermissionDoesNotExist
     */
    public function hasPermissionTo($permission) : bool
    {
       return $this->hasPermission($permission);
    }

    /**
     * An alias to hasPermissionTo(), but avoids throwing an exception.
     *
     * @param string|int|\Delgont\Armor\Contracts\Permission $permission
     * @param string|null $guardName
     *
     * @return bool
     */
    public function checkPermissionTo($permission, $guardName = null): bool
    {
        try {
            return $this->hasPermissionTo($permission, $guardName);
        } catch (PermissionDoesNotExist $e) {
            return false;
        }
    }


    /**
     * Remove all current permissions and set the given ones.
     *
     * @param string|int|array
     *
     * @return $this
     */
    public function syncPermissions(...$permissions)
    {
        $this->permissions()->detach();
        return $this->givePermissionTo($permissions);
    }

     /**
     * Determine if the model has any of the given permissions.
     *
     * @param string|int|array|\Spatie\Permission\Contracts\Permission|\Illuminate\Support\Collection ...$permissions
     *
     * @return bool
     */
    public function hasAnyPermission(...$permissions): bool
    {
        $permissions = collect($permissions)->flatten();

        foreach ($permissions as $permission) {
            if ($this->checkPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

}

