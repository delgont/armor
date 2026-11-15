<?php

namespace Delgont\Armor;

use Delgont\Armor\Events\PermissionGranted;
use Delgont\Armor\Models\Permission as PermissionModel;
use Delgont\Armor\Models\PermissionGroup;
use Delgont\Armor\Models\Role;
use Delgont\Armor\Models\RoleGroup;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class RoleRegistrar
{
    protected $group = null;
    protected $roles = null;

    /** Permission registrar classes whose permissions every role in this class receives. */
    protected $permissionRegistrars = [];

    /** Permission group names whose permissions every role in this class receives. */
    protected $permissionGroups = [];

    /** false: only adds permissions. true: roles match the declaration exactly. */
    protected $strict = false;

    protected function getGroup()
    {
        return $this->group;
    }

    protected function getRoles()
    {
        return $this->roles ?: (new \ReflectionClass($this))->getConstants();
    }

    public function groupName(): ?string
    {
        return $this->getGroup();
    }

    public function isStrict(): bool
    {
        return (bool) $this->strict;
    }

    /** Role names this registrar declares (string constants only). */
    public function roleNames(): array
    {
        return array_values(array_filter($this->getRoles(), 'is_string'));
    }

    public function declaresPermissions(): bool
    {
        return $this->permissionRegistrars !== [] || $this->permissionGroups !== [];
    }

    /**
     * Resolve the declaration to real permissions: [name => id].
     * Throws, listing every problem at once, if anything cannot be resolved.
     */
    public function grantedPermissions(): array
    {
        $problems = [];
        $names = [];
        $granted = [];

        foreach ((array) $this->permissionRegistrars as $class) {
            $valid = is_string($class) && class_exists($class)
                && (is_subclass_of($class, PermissionRegistrar::class) || is_subclass_of($class, Permission::class));

            if (! $valid) {
                $problems[] = 'permission registrar '.json_encode($class).' does not exist or is not a permission registrar';
                continue;
            }

            $declared = array_filter(app($class)->getPermissions(), 'is_string');

            if ($declared === []) {
                $problems[] = class_basename($class).' declares no permissions';
            }

            $names = array_merge($names, array_values($declared));
        }

        // Permissions must already be in the database to be attached.
        $names = array_values(array_unique($names));

        if ($names !== []) {
            $found = PermissionModel::whereIn('name', $names)->pluck('id', 'name');

            if ($missing = array_diff($names, $found->keys()->all())) {
                $problems[] = 'not in the database: '.implode(', ', $missing).' (run armor:permissions:sync first)';
            }

            $granted += $found->all();
        }

        foreach ((array) $this->permissionGroups as $groupName) {
            $group = PermissionGroup::where('name', $groupName)->first();

            if (! $group) {
                $problems[] = "permission group '{$groupName}' does not exist (run armor:permissions:sync first)";
                continue;
            }

            $found = PermissionModel::where('permission_group_id', $group->id)->pluck('id', 'name');

            if ($found->isEmpty()) {
                $problems[] = "permission group '{$groupName}' has no permissions";
            }

            $granted += $found->all();
        }

        if ($problems !== []) {
            throw new RuntimeException(static::class.' cannot grant permissions: '.implode('; ', $problems));
        }

        return $granted;
    }

    /** Create or update the group, recording which class owns it. */
    public function syncGroup(): ?RoleGroup
    {
        $name = $this->getGroup();

        if (! $name) {
            return null;
        }

        $group = RoleGroup::firstOrNew(['name' => $name]);
        $group->forceFill(['registrar' => static::class])->save();

        return $group;
    }

    public function sync(?callable $onProgress = null)
    {
        // 1. Validate first. A bad declaration must not leave half the roles written.
        $grants = $this->declaresPermissions() ? $this->grantedPermissions() : [];

        if ($this->strict && $grants === []) {
            throw new RuntimeException(static::class.' is strict but grants no permissions. Refusing to strip its roles.');
        }

        // 2. Write everything or nothing.
        $changes = DB::transaction(function () use ($grants, $onProgress) {
            $group = $this->syncGroup();
            $names = $this->roleNames();
            $changes = [];

            foreach ($names as $i => $name) {
                $role = Role::firstOrNew(['name' => $name]);
                $role->forceFill(['role_group_id' => $group?->id])->save();

                if ($grants !== []) {
                    $ids = array_values($grants);
                    $result = $this->strict
                        ? $role->permissions()->sync($ids)
                        : $role->permissions()->syncWithoutDetaching($ids);

                    if ($result['attached'] || $result['detached']) {
                        $changes[] = [$role, $result];
                    }
                }

                if ($onProgress) {
                    $onProgress($i + 1, count($names));
                }
            }

            return $changes;
        });

        // 3. After commit: refresh caches and announce, so listeners never see uncommitted data.
        foreach ($changes as [$role, $result]) {
            $role->invalidatePermissionCache();

            foreach (PermissionModel::whereIn('id', $result['attached'])->get() as $permission) {
                event(new PermissionGranted($role, $permission, $permission->group ?? null));
            }
        }
    }

    public function cache()
    {
    }
}