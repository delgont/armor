<?php

namespace Delgont\Armor\Listeners;

use Delgont\Armor\Events\AuditActivityLogged;
use Delgont\Armor\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogAuditActivity implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(AuditActivityLogged $event): void
    {
        AuditLog::create([
            'user_id'    => optional($event->user)->id,
            'user_type'  => $event->user ? get_class($event->user) : null,
            'action'     => $event->action,
            'message'    => $event->message,
            'method'     => $event->request->method(),
            'url'        => $event->request->fullUrl(),
            'before'     => $this->encodeJson($event->before),
            'after'      => $this->encodeJson($event->after),
            'ip_address' => $event->request->ip(),
            'user_agent' => $event->request->header('User-Agent'),
        ]);
    }

    /**
     * Encode data as JSON safely.
     */
    private function encodeJson(mixed $data): ?string
    {
        return !empty($data) ? json_encode($data, JSON_THROW_ON_ERROR) : null;
    }
}
