<?php

namespace Delgont\Armor;

use Delgont\Armor\Models\Permission;
use Delgont\Armor\Models\PermissionGroup;

use Delgont\Armor\Events\PermissionGroupSynchronized;


abstract class PermissionRegistrar
{
    protected $group = null; // Permission group name
    protected $permissions = null; // Permission constants or array
    protected $descriptions = null; // Descriptions of permissions


   /**
     * Get the name of the permission group.
     *
     * @return string|null
     */
    protected function getGroup(): ?string
    {
        return $this->group;
    }

    public function getPermissions()
    {
        return ($this->permissions) ? $permissions : (new \ReflectionClass($this))->getConstants();
    }

     /**
     * Get the descriptions for the permissions.
     *
     * @return array
     */
    public function getDescriptions() : array
    {
        return $this->descriptions ?? $this->descriptions();
    }


   /**
     * Synchronize permissions and groups with the database.
     */
    public function sync(): void
    {
        $permissionGroup = $this->syncPermissionGroup();

        $this->syncPermissions($permissionGroup);

        event(new PermissionGroupSynchronized($this->getPermissions()));
    }

    /**
     * Synchronize the permission group.
     *
     * @return PermissionGroup
     */
    private function syncPermissionGroup() : PermissionGroup
    {
        return PermissionGroup::firstOrCreate(
            ['name' => $this->getGroup() ?? static::class],
            ['registrar' => static::class]
        );
    }

    /**
     * Synchronize permissions within the group.
     *
     * @param PermissionGroup $permissionGroup
     */
    private function syncPermissions(PermissionGroup $permissionGroup): void
    {
        foreach ($this->getPermissions() as $index => $permission) {
            Permission::updateOrCreate(
                [
                    'name' => $permission,
                    'description' => $this->getDescriptions()[$permission] ?? null,
                    'permission_group_id' => $permissionGroup->id,
                    'order' => (int)$index + 1,
                ],
                [
                    'description' => $this->getDescriptions()[$permission] ?? null,
                    'permission_group_id' => $permissionGroup->id,
                    'order' => (int)$index + 1,
                ]
            );
        }
    }

    /**
     * Placeholder for future caching logic.
     */
    public function cache(): void
    {
        // Future implementation: Cache permissions to reduce DB queries
    }

    /**
    * Override to define custom descriptions for permissions.
    *
    * @return array
    */
   protected function descriptions(): array
   {
       return [];
   }
}
