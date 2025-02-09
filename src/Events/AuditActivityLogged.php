<?php

namespace Delgont\Armor\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditActivityLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ?object $user,
        public readonly string $action,
        public readonly ?string $message,
        public readonly array $request = [],
        public readonly mixed $before = null,
        public readonly mixed $after = null
    ) {
        $this->request = [
            'method'     => $request['method'] ?? null,
            'url'        => $request['url'] ?? null,
            'ip'         => $request['ip'] ?? null,
            'user_agent' => $request['user_agent'] ?? null,
        ];
    }
}
