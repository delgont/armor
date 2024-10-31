<?php

namespace Delgont\Armor\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Delgont\Armor\Models\Permission;

use Delgont\Armor\Exceptions\PermissionDoesNotExist;

use Illuminate\Support\Facades\Cache;


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
     * Get the permission cache prefix based on the model type.
     */
    protected function getPermissionCachePrefix(): string
    {
        // Get the class name without the namespace
        $className = class_basename($this);

        // Extract the last word in the class name
        $lastWord = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));

        return $lastWord . '_permission_';
    }

    /**
     * Invalidate the permission cache for the role or user.
     */
    protected function invalidatePermissionCache($permissions)
    {
        foreach ($permissions as $permission) {
            Cache::forget($this->getPermissionCachePrefix(). $this->id.'_'.$permission);
        }
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
        }else{
            //Permission not found
        }

        return $this->permissions->contains('name', $permission->name);
    }


    public function givePermissionTo(...$permissions)
    {
        $permissions = collect($permissions)->flatten()->reduce(function($array, $permission){

            // Initialize arrays for IDs and names if they don't exist
            if (!isset($array['ids'])) {
                $array['ids'] = [];
            }
            if (!isset($array['names'])) {
                $array['names'] = [];
            }
            if(is_string($permission)){
                $permissionModel = Permission::whereName($permission)->first();
                if ($permissionModel) {
                   // Store permission ID and name
                    $array['ids'][] = $permissionModel->id;
                    $array['names'][] = $permissionModel->name;
                } else {
                    throw PermissionDoesNotExist::create($permission);
                }
            } elseif ($permission instanceof Permission) {
                // Store permission ID and name
                $array['ids'][] = $permission->id;
                $array['names'][] = $permission->name;
            } else {
                // Handle case when permission is not found
                throw new \InvalidArgumentException('Invalid permission type.');
            }

            return $array;
        }, []);

        $model = $this->getModel();
        if($model->exists){
            $this->permissions()->sync($permissions['ids'], true);
        }

        //Remove permissions from cache
        $this->invalidatePermissionCache($permissions['names']);

        return $this;
    }


    public function withdrawPermissionsTo(...$permissions)
    {
        // Flatten the permissions to handle array of permissions
        $permissions = collect($permissions)->flatten()->reduce(function($array, $permission) {
             // Initialize arrays for IDs and names if they don't exist
             if (!isset($array['ids'])) {
                $array['ids'] = [];
            }
            if (!isset($array['names'])) {
                $array['names'] = [];
            }

            if (is_string($permission)) {
                $permissionModel = Permission::whereName($permission)->first();
                if ($permissionModel) {
                    $array['ids'][] = $permissionModel->id;
                    $array['names'][] = $permissionModel->name;
                } else {
                    throw PermissionDoesNotExist::create($permission);
                }
            } elseif ($permission instanceof Permission) {
                $array['ids'][] = $permission->id;
                $array['names'][] = $permission->name;
            } else {
                // Handle case when permission is not found
                throw new \InvalidArgumentException('Invalid permission type.');
            }
            return $array;
        }, []);

        // Check if the model exists and withdraw the permissions
        $model = $this->getModel();
        if ($model->exists) {
            $this->permissions()->detach($permissions['ids']);
        }

        // Invalidate cache for the withdrawn permissions
        $this->invalidatePermissionCache($permissions['names']);

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
        $cacheDuration = config('armor.cache_duration', 60);

        return Cache::remember($this->getPermissionCachePrefix().$this->id.'_'.$permission, $cacheDuration, function () use ($permission) {
            if (is_string($permission)) {
                $permissionModel = Permission::whereName($permission)->first();
                if (!$permissionModel) {
                    throw PermissionDoesNotExist::create($permission);
                }
            }else{
                throw new \InvalidArgumentException('Invalid permission type.');
            }

            return $this->permissions->contains('name', $permissionModel->name);
        });
       //return $this->hasPermission($permission);
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

        $this->invalidatePermissionsCache();

        return $this->givePermissionTo($permissions);
    }

     /**
     * Determine if the model has any of the given permissions.
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

