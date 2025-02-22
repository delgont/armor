<?php

namespace Delgont\Armor\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditActivityLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user, $action, $message, $request, $before, $after;

   public function __construct($user, string $action, ?string $message, array $request = [], $before = null, $after = null) {
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
    }

}
