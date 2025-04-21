<?php

namespace Delgont\Armor\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Delgont\Armor\Models\Role;
use Delgont\Armor\Models\PermissionGroup;

use Delgont\Armor\Contracts\Permission as PermissionContract;

use Illuminate\Support\Facades\Cache;



class Permission extends Model implements PermissionContract
{

    protected $guarded = [];


    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::store(config('armor.cache_store'))->forget('permissions');
        });

        static::deleted(function () {
            Cache::store(config('armor.cache_store'))->forget('permissions');
        });
    }



    public function roles() : BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions', 'permission_id', 'role_id');
    }


    /**
     * A permission may belong to specific group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group() : BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'permission_group_id');
    }

      /**
     * A permission belongs to a specific permission group.
     *

    * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function permissionGroup()
    {
        return $this->belongsTo(PermissionGroup::class, 'permission_group_id');
    }

    /**
     * Get permissions of specific group.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfGroup($query, $group)
    {
        return $query->whereHas('group', function($groupQuery) use ($group){
            $groupQuery->whereName($group);
        });
    }

    protected function getAllPermissions(): \Illuminate\Support\Collection
    {
        $cacheDuration = config('armor.cache_duration', 60);
        return $this->getCacheStore()->remember('permissions', $cacheDuration, function () {
            return Permission::all();
        });
    }


}
