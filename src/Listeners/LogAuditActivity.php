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
            'method'     => $event->request['method'],  // Accessing method directly from the array
            'url'        => $event->request['url'],     // Accessing URL directly from the array
            'before'     => $this->encodeJson($event->before),
            'after'      => $this->encodeJson($event->after),
            'ip_address' => $event->request['ip'],      // Accessing IP address directly from the array
            'user_agent' => $event->request['user_agent'], // Accessing user agent directly from the array
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
