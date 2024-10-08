<?php

namespace Delgont\Armor\Models;

use Illuminate\Database\Eloquent\Model;

use Delgont\Armor\Models\Role;

class RoleGroup extends Model
{
    protected $fillable = ['name'];

    /**
     * A permission may belong to specific group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function roles()
    {
        return $this->hasMany(Role::class, 'role_group_id');
    }

}
