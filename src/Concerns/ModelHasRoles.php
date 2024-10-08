<?php

namespace Delgont\Armor\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Delgont\Armor\Models\Role;

use Delgont\Armor\Exceptions\RoleDoesNotExist;



trait ModelHasRoles
{

    public static function bootHasRoles()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->roles()->detach();
        });
    }
    

    /**
     * A model may have multiple roles.
     */
    public function roles() : BelongsToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }

     /**
     * Assign the given role to the model.
     *
     * @param array|string|int
     *
     * @return $this
     */
    public function assignRole(...$roles)
    {
        $roles = collect($roles)->flatten()->reduce(function($array, $role){
            if ($role instanceof Role) {
                array_push($array, $role->getKey());
            }

            if(is_numeric($role)){
                array_push( $array, $role );
            }

            if(is_string($role)){
                $roleModel = Role::whereName($role)->first();
                if ($roleModel) {
                    array_push($array, $roleModel->getKey());
                } else {
                    throw RoleDoesNotExist::named($role);
                }
                
            }
            return $array;
        }, []);

        $model = $this->getModel();
        if($model->exists){
            $this->roles()->sync($roles, false);
        }
        return $this;
    }

     /**
     * Determine if the model has (one of) the given role(s).
     *
     * @param string|int|array
     * @param string|null $guard
     * @return bool
     */
    public function hasRole($roles): bool
    {
        if (is_string($roles) && false !== strpos($roles, '|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)) {
            return $this->roles->contains('name', $roles);
        }

        if (is_int($roles)) {
            $key = app(Role::class)->getKeyName();
            return $this->roles->contains($key, $roles);
        }

        if ($roles instanceof Role) {
            return $this->roles->contains($roles->getKeyName(), $roles->getKey());
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }

            return false;
        }

        return $roles->intersect($guard ? $this->roles->where('guard_name', $guard) : $this->roles)->isNotEmpty();
    }

     /**
     * Determine if the model has any of the given role(s).
     *
     * Alias to hasRole() but without Guard controls
     *
     * @param string|int|array|\Spatie\Permission\Contracts\Role|\Illuminate\Support\Collection $roles
     *
     * @return bool
     */
    public function hasAnyRole(...$roles): bool
    {
        return $this->hasRole($roles);
    }

     /**
     * Determine if the model has exactly all of the given role(s).
     *
     * @param  string|array|\Delgont\Armor\Contracts\Role
     * @param  string|null  $guard
     * @return bool
     */
    public function hasExactRoles($roles, string $guard = null): bool
    {
        if (is_string($roles) && false !== strpos($roles, '|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        if ($roles instanceof Role) {
            $roles = [$roles->name];
        }

        $roles = collect()->make($roles)->map(function ($role) {
            return $role instanceof Role ? $role->name : $role;
        });

        return $this->roles->count() == $roles->count() && $this->hasAllRoles($roles, $guard);
    }

    protected function convertPipeToArray(string $pipeString)
    {
        $pipeString = trim($pipeString);

        if (strlen($pipeString) <= 2) {
            return $pipeString;
        }

        $quoteCharacter = substr($pipeString, 0, 1);
        $endCharacter = substr($quoteCharacter, -1, 1);

        if ($quoteCharacter !== $endCharacter) {
            return explode('|', $pipeString);
        }

        if (! in_array($quoteCharacter, ["'", '"'])) {
            return explode('|', $pipeString);
        }

        return explode('|', trim($pipeString, $quoteCharacter));
    }

    protected function getStoredRole($role): Role
    {

        if (is_numeric($role)) {
            return app(Role::class)->whereId($role)->first();
        }

        if (is_string($role)) {
            return app(Role::class)->whereName($role)->first();
        }

        return $role;
    }

    public function getRoleNames(): Collection
    {
        return $this->roles->pluck('name');
    }

     /**
     * Revoke the given role from the model.
     *
     * @param string|int
     */
    public function removeRole($role)
    {
        $this->roles()->detach($this->getStoredRole($role));

        $this->load('roles');
        return $this;
    }
   
}