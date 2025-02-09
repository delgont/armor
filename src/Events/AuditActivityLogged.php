<?php

namespace Delgont\Armor\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class AuditActivityLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ?object $user,
        public readonly string $action,
        public readonly ?string $message,
        public readonly Request $request,
        public readonly mixed $before = null,
        public readonly mixed $after = null
    ) {}
}
