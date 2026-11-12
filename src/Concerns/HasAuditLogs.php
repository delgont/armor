<?php
/**
 * Delgont Armor
 *
 * @link      https://github.com/delgont/armor
 * @copyright Copyright (c) 2024 - Present Delgont Technologies Co. Ltd
 * @license   MIT License (https://opensource.org/licenses/MIT)
 *
 */

namespace Delgont\Armor\Concerns;

use Delgont\Armor\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAuditLogs
{
    /**
     * Audit logs performed by this model.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(
            AuditLog::class,
            'user',
            'user_type',
            'user_id'
        );
    }

    /**
     * Latest audit log.
     */
    public function latestAuditLog()
    {
        return $this->auditLogs()
            ->latest('performed_at')
            ->first();
    }
}