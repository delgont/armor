<?php

namespace Delgont\Armor\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditActivityLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $action;
    public $message;
    public $request;
    public $before;
    public $after;
    public $links;

    /**
     * Original constructor (backward compatible)
     */
    public function __construct(
        $user,
        string $action,
        ?string $message,
        array $request = [],
        $before = null,
        $after = null
    ) {
        $this->user = $user;
        $this->action = $action;
        $this->message = $message;
        $this->request = [
            'method'     => $request['method'] ?? null,
            'url'        => $request['url'] ?? null,
            'ip'         => $request['ip'] ?? null,
            'user_agent' => $request['user_agent'] ?? null,
        ];
        $this->before = $before;
        $this->after = $after;
        $this->links = []; // default empty for backward compatibility
    }

    /**
     * Factory method to include links
     */
    public static function withLinks(
        $user,
        string $action,
        ?string $message = null,
        array $request = [],
        $before = null,
        $after = null,
        array $links = []
    ): self {
        $event = new self($user, $action, $message, $request, $before, $after);
        $event->links = $links;
        return $event;
    }
}