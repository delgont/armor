<?php

use Delgont\Armor\Services\AuditLogger;
use Delgont\Armor\AuthManager;

if (! function_exists('armor')) {
    /**
     * Get the Armor AuthManager instance.
     *
     * @return \Delgont\Armor\AuthManager
     */
    function armor(): AuthManager
    {
        return app(AuthManager::class);
    }
}

if (!function_exists('audit_log')) {
    /**
     * Simple helper to log audit activity
     *
     * @param mixed $user
     * @param string $action
     * @param string|null $message
     * @param mixed|null $before
     * @param mixed|null $after
     * @param array $links
     * @param array|null $requestData
     */
    function audit_log(
        $user,
        string $action,
        ?string $message = null,
        ?array $requestData = null,
        $before = null,
        $after = null,
        array $links = []
    ) {
        AuditLogger::log(
            $user,
            $action,
            $message,
            $requestData ?? [],
            $before,
            $after,
            $links
        );
    }
}
