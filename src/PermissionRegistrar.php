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

    /** @var class-string<PermissionRegistrar>|null */
    protected $parent = null;

   /**
     * Get the name of the permission group.
     *
     * @return string|null
     */
    protected function getGroup(): ?string
    {
        return $this->group;
    }

    public function groupName(): string
    {
        return $this->getGroup() ?? static::class;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    protected function parentRegistrar(): ?self
    {
        if ($this->parent === null) {
            return null;
        }

        if (! is_subclass_of($this->parent, self::class)) {
            throw new InvalidArgumentException(
                static::class." declares parent {$this->parent}, which is not a PermissionRegistrar."
            );
        }

        return app($this->parent);
    }


    public function getPermissions()
    {
        return ($this->permissions) ? $permissions : (new \ReflectionClass($this))->getConstants();
    }

    /**
     * Get all permissions associated with the current group from the database.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPermissionsFromDb()
    {
        $groupName = $this->getGroup() ?? static::class;

        $group = PermissionGroup::where('name', $groupName)->first();

        if (!$group) {
            return collect(); // Return empty collection if group doesn't exist
        }

        return Permission::where('permission_group_id', $group->id)->get();
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



    public function sync(?callable $onProgress = null): void
    {
        $group = $this->syncGroup();

        $this->syncPermissions($group, $onProgress);

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
    private function syncPermissionsDe(PermissionGroup $permissionGroup): void
    {
        foreach ($this->getPermissions() as $index => $permission) {
            Permission::updateOrCreate(
                [
                    'name' => $permission,
                    'permission_group_id' => $permissionGroup->id,
                ],
                [
                    'description' => $this->getDescriptions()[$permission] ?? null,
                    'permission_group_id' => $permissionGroup->id,
                    'order' => (int)$index + 1,
                ]
            );
        }
    }

    private function syncPermissions(PermissionGroup $permissionGroup, ?callable $onProgress = null): void
    {
        $permissions = $this->getPermissions();
        $total = count($permissions);
        $done = 0;

        foreach ($permissions as $index => $permission) {
            Permission::updateOrCreate(
                [
                    'name' => $permission,
                    'permission_group_id' => $permissionGroup->id,
                ],
                [
                    'description' => $this->getDescriptions()[$permission] ?? null,
                    'permission_group_id' => $permissionGroup->id,
                    'order' => (int)$index + 1,
                ]
            );

            if ($onProgress) {
                $onProgress(++$done, $total);
            }
        }
    }

    private function syncPermissionsByDefinitions(PermissionGroup $permissionGroup, ?callable $onProgress = null): void
    {
        $definitions = $this->permissionDefinitions();
        $total = count($definitions);
        $done = 0;

        foreach ($definitions as $name => $definition) {
            Permission::updateOrCreate(
                ['name' => $name, 'permission_group_id' => $permissionGroup->id],
                $definition + ['permission_group_id' => $permissionGroup->id]
            );

            if ($onProgress) {
                $onProgress(++$done, $total);
            }
        }
    }

    /**
     * What sync() will write for each permission, keyed by permission name.
     *
     * @return array<string, array{description: ?string, order: int}>
     */
    public function permissionDefinitions(): array
    {
        $descriptions = $this->getDescriptions();
        $definitions = [];

        foreach ($this->getPermissions() as $index => $permission) {
            $definitions[$permission] = [
                'description' => $descriptions[$permission] ?? null,
                'order'       => (int) $index + 1,
            ];
        }

        return $definitions;
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

    /**
     * Ensure this registrar's group exists with the correct parent,
     * creating ancestor groups first. Does not sync permissions.
     *
     * @param  string[]  $trail  Registrars already visited (cycle guard)
     */
    public function syncGroup(array $trail = []): PermissionGroup
    {
        if (in_array(static::class, $trail, true)) {
            throw new LogicException(
                'Circular registrar parent chain: '.implode(' -> ', [...$trail, static::class])
            );
        }

        $parentGroup = $this->parentRegistrar()?->syncGroup([...$trail, static::class]);

        return PermissionGroup::updateOrCreate(
            ['name' => $this->groupName()],
            [
                'registrar' => static::class,
                'parent_id' => $parentGroup?->id,
            ]
        );
    }
}
