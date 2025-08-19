<?php

namespace Delgont\Armor\Services;

use Delgont\Armor\Events\AuditActivityLogged;

class AuditLogger
{
    public static function log(
        $user,
        string $action,
        ?string $message = null,
        array $requestData = [],
        $before = null,
        $after = null,
        array $links = []
    ) {
        if (!empty($links)) {
            $event = AuditActivityLogged::withLinks(
                $user,
                $action,
                $message,
                $requestData,
                $before,
                $after,
                $links
            );
        } else {
            $event = new AuditActivityLogged(
                $user,
                $action,
                $message,
                $requestData,
                $before,
                $after
            );
        }

        event($event);
    }
}
