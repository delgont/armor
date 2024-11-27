<?php

namespace Delgont\Armor;

use Delgont\Armor\Models\Permission as PermissionModel;
use Delgont\Armor\Models\PermissionGroup;


abstract class Permission
{
    protected $group;
    protected $guard;

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    public function getPermissions()
    {
        $reflection = new \ReflectionClass($this);
        return $reflection->getConstants();
    }

    public function syncPermissions()
    {
        $permissionGroup = ($this->getGroup()) ? PermissionGroup::firstOrCreate([
            'name' => $this->getGroup()
        ],['name' => $this->getGroup()]) : null;

        if (count($this->getPermissions()) > 0) {
            foreach ($this->getPermissions() as $key => $permission) {
                PermissionModel::updateOrCreate([
                    'name' => $permission
                ], [
                    'name' => $permission,
                    'permission_group_id' => ($permissionGroup) ? $permissionGroup->id : null
                ]);
            }
        }
    }

  


}
