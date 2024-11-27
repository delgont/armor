<?php

namespace Delgont\Armor\Events;

use Illuminate\Queue\SerializesModels;
use Delgont\Armor\Models\Permission;
use Delgont\Armor\Models\PermissionGroup;

class PermissionGranted
{
    use SerializesModels;

    public $model;
    public $permission;
    public $group;

    /**
     * Create a new event instance.
     *
     * @param mixed $model
     * @param Permission $permission
     * @param PermissionGroup|null $group
     */
    public function __construct($model, Permission $permission, ?PermissionGroup $group = null)
    {
        $this->model = $model;
        $this->permission = $permission;
        $this->group = $group;
    }
}
