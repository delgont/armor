<?php

namespace Delgont\Armor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

/**
 * A named collection of permissions, optionally nested under a parent group.
 *
 * Groups are normally created and kept up to date by a PermissionRegistrar
 * (see PermissionRegistrar::sync()). The `registrar` column records which
 * class owns the group, and `parent_id` mirrors the registrar's $parent
 * declaration. Code is the source of truth, so manual edits to a managed
 * group are overwritten on the next sync.
 *
 * @property int         $id
 * @property string|null $uuid
 * @property int|null    $parent_id
 * @property string      $name
 * @property string|null $description
 * @property string|null $registrar
 */
class PermissionGroup extends Model
{
    /**
     * Safety limit when walking up the hierarchy, so corrupt data
     * (for example a cycle written outside Eloquent) cannot loop forever.
     */
    private const MAX_DEPTH = 50;

    protected $fillable = ['name', 'description', 'registrar', 'parent_id'];

    /**
     * Note: `key` was removed from $fillable. The permission_groups table
     * has no `key` column, so mass-assigning it would fail with an SQL error.
     */

    /*
    |--------------------------------------------------------------------------
    | Model events
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        // Every group gets a public identifier, so URLs and APIs never
        // need to expose sequential ids.
        static::creating(function (self $group) {
            if (! $group->uuid) {
                $group->uuid = (string) Str::uuid();
            }
        });

        // Reject a parent that would create a loop (A -> B -> A) before
        // it reaches the database. Runs only when parent_id actually changes.
        static::saving(function (self $group) {
            if ($group->parent_id === null || ! $group->isDirty('parent_id')) {
                return;
            }

            $cursor = $group->parent_id;
            $seen = [];

            while ($cursor !== null && count($seen) < self::MAX_DEPTH) {
                if ($group->exists && (int) $cursor === (int) $group->getKey()) {
                    throw new LogicException(
                        "Permission group [{$group->name}] cannot be its own ancestor."
                    );
                }

                if (in_array($cursor, $seen, true)) {
                    break; // pre-existing cycle above us; not ours to fix here
                }

                $seen[] = $cursor;
                $cursor = static::query()->whereKey($cursor)->value('parent_id');
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Route binding
    |--------------------------------------------------------------------------
    */

    /** Resolve {group} route parameters by uuid, not by numeric id. */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /** Permissions that belong directly to this group (not its children). */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'permission_group_id');
    }

    /** The group this one is nested under, or null for a root group. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Direct children only. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    /**
     * Children with their own children, recursively.
     * Use with eager loading: PermissionGroup::with('descendants').
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /*
    |--------------------------------------------------------------------------
    | Query scopes
    |--------------------------------------------------------------------------
    */

    /** Top-level groups (no parent). */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /** Groups owned by a specific registrar class. */
    public function scopeOwnedBy(Builder $query, string $registrar): Builder
    {
        return $query->where('registrar', $registrar);
    }

    /** Groups managed by code (created by a registrar). */
    public function scopeManaged(Builder $query): Builder
    {
        return $query->whereNotNull('registrar');
    }

    /** Groups created by hand, with no registrar. */
    public function scopeUnmanaged(Builder $query): Builder
    {
        return $query->whereNull('registrar');
    }

    /*
    |--------------------------------------------------------------------------
    | Hierarchy helpers
    |--------------------------------------------------------------------------
    */

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function isLeaf(): bool
    {
        return ! $this->children()->exists();
    }

    /** True when a registrar class owns this group. */
    public function isManaged(): bool
    {
        return $this->registrar !== null;
    }

    /**
     * Ancestors ordered from the root down to the direct parent.
     * Costs one query per level, which is fine for shallow trees.
     *
     * @return Collection<int, static>
     */
    public function ancestors(): Collection
    {
        $chain = [];
        $seen = [$this->getKey()];
        $cursor = $this->parent_id;

        while ($cursor !== null && count($chain) < self::MAX_DEPTH) {
            if (in_array($cursor, $seen, true)) {
                break; // cycle guard
            }

            $parent = static::query()->find($cursor);

            if (! $parent) {
                break;
            }

            $seen[] = $cursor;
            array_unshift($chain, $parent);
            $cursor = $parent->parent_id;
        }

        return new Collection($chain);
    }

    /** Number of ancestors. A root group has depth 0. */
    public function depth(): int
    {
        return $this->ancestors()->count();
    }

    /**
     * Human-readable breadcrumb, for example "Users > Security".
     */
    public function path(string $separator = ' > '): string
    {
        return $this->ancestors()->pluck('name')->push($this->name)->implode($separator);
    }

    /**
     * Ids of every group below this one, at any depth.
     * Walks one level per query, so cost grows with depth and not with size.
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $frontier = [$this->getKey()];

        while ($frontier !== []) {
            $frontier = static::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->diff([...$ids, $this->getKey()]) // skip visited ids (cycle guard)
                ->values()
                ->all();

            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }

    /**
     * Permissions in this group and in every group below it.
     * Returns a query so callers can filter, paginate or count.
     */
    public function allPermissions(): Builder
    {
        return Permission::query()->whereIn(
            'permission_group_id',
            [$this->getKey(), ...$this->descendantIds()]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Tree building
    |--------------------------------------------------------------------------
    */

    /**
     * Load every group in one query and return the roots with
     * `children` already attached, so a UI can render the whole
     * hierarchy without N+1 queries.
     *
     * @return Collection<int, static>
     */
    public static function tree(): Collection
    {
        $groups = static::query()
            ->withCount('permissions')
            ->orderBy('name')
            ->get();

        $byParent = $groups->groupBy('parent_id');

        $groups->each(function (self $group) use ($byParent) {
            $group->setRelation('children', $byParent->get($group->getKey(), new Collection()));
        });

        return $byParent->get(null, new Collection());
    }
}