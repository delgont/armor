<?php

namespace Delgont\Armor\Models;

use Illuminate\Database\Eloquent\Model;

use Delgont\Armor\Models\Permission;
use Illuminate\Support\Str;

class PermissionGroup extends Model
{
    protected $fillable = ['name', 'key', 'registrar', 'description', 'parent_id'];

    protected static function booted()
    {
        static::creating(function ($group) {

            if (! $group->uuid) {
                $group->uuid = (string) Str::uuid();
            }
        });
    }

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
