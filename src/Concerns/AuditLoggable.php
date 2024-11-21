<?php

namespace Delgont\Armor\Concerns;

use Delgont\Armor\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait AuditLoggable
{
    /**
     * Get all audit logs for the model.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditlogable');
    }

}
