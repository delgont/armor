<?php

namespace Delgont\Armor;

use Delgont\Armor\Models\Permission;
use Delgont\Armor\Models\PermissionGroup;

use Delgont\Armor\Events\PermissionsSynchronized;


abstract class PermissionRegistrar
{
    protected $group = null;
    protected $permissions = null;
    protected $descriptions = null;


    protected function getGroup()
    {
        return $this->group;
    }

    public function getPermissions()
    {
        return ($this->permissions) ? $permissions : (new \ReflectionClass($this))->getConstants();
    }

    public function getDescriptions()
    {
        return ($this->descriptions) ? $this->descriptions : $this->descriptions();
    }


    public function sync()
    {
        $permissionGroup = ($this->getGroup()) ? PermissionGroup::firstOrCreate([
            'name' => $this->getGroup()
        ],['name' => get_class($this), 'registrar' => get_class($this)]) : null;

        $permissions = $this->getPermissions();

        if (count($permissions) > 0) {
            foreach ($this->getPermissions() as $key => $permission) {
                Permission::updateOrCreate([
                    'name' => $permission
                ], [
                    'name' => $permission,
                    'description' => (is_array($this->getDescriptions()) && count($this->getDescriptions()) > 0) ? (array_key_exists($permission, $this->getDescriptions())) ? $this->getDescriptions()[$permission] : null : null,
                    'permission_group_id' => ($permissionGroup) ? $permissionGroup->id : null
                ]);
            }
            event(new PermissionsSynchronized($permissions));
        }
    }

    public function cache()
    {

    }

    protected function descriptions()
    {
        return [];
    }
}
