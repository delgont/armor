<?php

namespace Delgont\Armor;

use Delgont\Armor\Models\Role;
use Delgont\Armor\Models\RoleGroup;


abstract class RoleRegistrar
{
    protected $group = null;
    protected $roles = null;


    protected function getGroup()
    {
        return $this->group;
    }

    protected function getRoles()
    {
        return ($this->roles) ? $roles : (new \ReflectionClass($this))->getConstants();
    }

    public function sync()
    {
        $roleGroup = ($this->getGroup()) ? RoleGroup::firstOrCreate([
            'name' => $this->getGroup()
        ],['name' => $this->getGroup()]) : null;

        if (count($this->getRoles()) > 0) {
            foreach ($this->getRoles() as $key => $role) {
                Role::updateOrCreate([
                    'name' => $role
                ], [
                    'name' => $role,
                    'role_group_id' => ($roleGroup) ? $roleGroup->id : null
                ]);
            }
        }
    }

    public function cache()
    {

    }
}
