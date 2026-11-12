<?php

namespace Delgont\Armor\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemActionGroupSynchronized
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The synchronized actions.
     *
     * @var array
     */
    public $actions;

    /**
     * Create a new event instance.
     */
    public function __construct($actions)
    {
        $this->actions = $actions;
    }
}