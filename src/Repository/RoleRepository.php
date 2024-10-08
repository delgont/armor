<?php

namespace Delgont\Armor\Repository;

use Delgont\Armor\Cache\HandlesModelCaching;
use Delgont\Armor\Models\Role;
use Illuminate\Support\Facades\Cache;

class RoleRepository
{
    use HandlesModelCaching;

    public function __construct(Role $role)
    {
        $this->model = $role;
    }

    public function all()
    {
        if ($this->fromCache) {
           $cached = Cache::get('role:all');
           if ($cached) {
              return $cached;
           }else{
              $roles = $this->model->all();
              $this->storeCollectionInCache($roles, 'role:all');
              return $roles;
           }
        }
        return $this->model::all();
    }

}