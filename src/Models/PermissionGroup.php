<?php

namespace Delgont\Armor\Models;

use Illuminate\Database\Eloquent\Model;

use Delgont\Armor\Models\Permission;

class PermissionGroup extends Model
{
    protected $fillable = ['name', 'registrar', 'description'];

    /**
     * A permission may belong to specific group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'permission_group_id');
    }

}
