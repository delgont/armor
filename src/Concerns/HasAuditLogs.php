<?php

namespace Delgont\Armor\Concerns;

use Delgont\Armor\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAuditLogs
{
    /**
     * Get all audit logs for the user.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'user');
    }

   
}
