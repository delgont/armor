<?php

namespace Delgont\Armor;

class PermissionRepository
{

    protected $group = null;
    

    public function ofGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    public function getPermissions() : Collection
    {

    }

}