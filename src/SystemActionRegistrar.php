<?php
namespace Delgont\Armor;

use ReflectionClass;
use Delgont\Armor\Models\SystemAction;
use Delgont\Armor\Models\SystemActionGroup;
use Delgont\Armor\Events\SystemActionGroupSynchronized;

abstract class SystemActionRegistrar
{
    protected $group = null;

    protected $key = null;

    protected $actions = null;

    protected $descriptions = null;

    /**
     * Sync registrar.
     */
    public function sync() : void
    {
        $group = $this->syncActionGroup();

        $this->syncActions($group);

        event(
            new SystemActionGroupSynchronized(
                $this->getActions()
            )
        );
    }

    public function getActions()
    {
        return $this->actions
            ?: (new ReflectionClass($this))
                ->getConstants();
    }

    public function getDescriptions() : array
    {
        return $this->descriptions
            ?? $this->descriptions();
    }

    public function getKey() : string
    {
        return $this->key
            ?? static::class;
    }

    public function getUndoable() : array
    {
        return $this->undoable();
    }

    public function getGroup() : ?string
    {
        return $this->group;
    }

    protected function syncActionGroup()
    {
        return SystemActionGroup::firstOrCreate(
            [
                'key' => $this->getKey()
                    ?? static::class,
                'registrar' => static::class
            ],
            [
                'name' => $this->getGroup()
                    ?? static::class,
                'registrar' => static::class
            ]
        );
    }

    protected function syncActions(
    SystemActionGroup $group
    ): void {

        $order = 1;

        foreach ($this->getActions() as $action) {

            SystemAction::updateOrCreate(
                [
                    'action' => $action,
                    'system_action_group_id' => $group->id,
                ],
                [
                    'action' => $action,

                    'description' =>
                        $this->getDescriptions()[$action]
                        ?? null,

                    'supports_undo' =>
                        in_array(
                            $action,
                            $this->getUndoable()
                        ),

                    'order' => $order++,
                ]
            );
        }
    }

    /**
     * Get all actions associated with the current group from the database.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getActionsFromDb()
    {
        $groupName = $this->getGroup() ?? static::class;

        $group = SystemActionGroup::where(
            'name',
            $groupName
        )->first();

        if (! $group) {
            return collect();
        }

        return SystemAction::where(
            'system_action_group_id',
            $group->id
        )
        ->orderBy('order')
        ->get();
    }
    /**
     * Returns action keys from database.
     *
     * @return array
     */
    public function getActionKeysFromDb(): array
    {
        return $this->getActionsFromDb()
            ->pluck('action')
            ->toArray();
    }

    protected function descriptions(): array
    {
        return [];
    }

    protected function undoable(): array
    {
        return [];
    }
}